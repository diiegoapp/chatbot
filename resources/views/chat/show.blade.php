<x-layouts.chat :conversations="$conversations" :conversation="$conversation">

@php
    $streamingEnabled = config('chatbot.streaming', false);
    $endpoint = $streamingEnabled
        ? route('messages.stream', $conversation)
        : route('messages.store', $conversation);
    $regenEndpoint = route('messages.regenerate', $conversation);
    $userInitial   = json_encode(mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)));
    $hasMessages   = $messages->isNotEmpty();
@endphp

<div
    x-data="chatApp('{{ $endpoint }}', '{{ $regenEndpoint }}', {{ $streamingEnabled ? 'true' : 'false' }}, {{ $userInitial }}, {{ $conversation->id }}, {{ $hasMessages ? 'true' : 'false' }})"
    @regen-request.document="regenerate()"
    class="flex flex-col flex-1 min-h-0 overflow-hidden"
>
    {{-- Messages area --}}
    <div
        id="messages"
        x-ref="messages"
        @scroll="checkScroll()"
        class="flex-1 overflow-y-auto"
        aria-live="polite"
        aria-label="Mensagens da conversa"
    >
        {{-- Empty state --}}
        <div x-show="!hasMessages" class="flex flex-col items-center justify-center h-full p-8 text-center">
            <div class="text-5xl mb-4">💬</div>
            <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">Como posso ajudar?</h2>
            <p class="text-gray-500 dark:text-gray-400 mb-8 max-w-sm text-balance">Escreva uma mensagem ou escolha uma sugestão para começar.</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full max-w-lg">
                @foreach([
                    'Explique um conceito de programação',
                    'Ajude a revisar um texto',
                    'Crie um plano de estudos',
                    'Tire uma dúvida de SQL',
                ] as $suggestion)
                    <button
                        @click="useSuggestion('{{ $suggestion }}')"
                        class="text-left px-4 py-3 rounded-xl border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 hover:border-indigo-400 dark:hover:border-indigo-500 hover:shadow-sm transition-all text-sm text-gray-700 dark:text-gray-300">
                        {{ $suggestion }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Messages list --}}
        <div x-show="hasMessages" class="max-w-3xl mx-auto px-4 py-6 space-y-6">
            @foreach($messages as $message)
                <x-chat.message
                    :message="$message"
                    :canRegenerate="$loop->last && $message->role === 'assistant'"
                />
            @endforeach

            {{-- Typing indicator --}}
            <div x-show="typing" x-cloak class="flex items-start gap-3">
                <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0" aria-hidden="true">IA</div>
                <div class="bg-white dark:bg-gray-800 rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm border border-gray-100 dark:border-gray-700">
                    <div class="flex gap-1" aria-label="IA está digitando">
                        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                        <span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scroll-to-bottom button --}}
    <div class="relative">
        <button
            x-show="!isAtBottom"
            x-cloak
            @click="forceScrollBottom()"
            class="absolute bottom-2 right-4 z-10 w-9 h-9 rounded-full bg-white dark:bg-gray-700 shadow-md border border-gray-200 dark:border-gray-600 flex items-center justify-center text-gray-500 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-600 transition-colors"
            aria-label="Ir para o fim">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
            </svg>
        </button>
    </div>

    {{-- Error banner --}}
    <div
        x-show="errorMsg"
        x-cloak
        class="mx-4 mb-2 px-4 py-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-xl text-red-700 dark:text-red-300 text-sm flex items-center justify-between gap-2"
        role="alert"
    >
        <span x-text="errorMsg" class="flex-1"></span>
        <div class="flex items-center gap-2 flex-shrink-0">
            <button
                x-show="pendingContent"
                @click="retrySend()"
                class="text-xs font-medium underline hover:no-underline"
                aria-label="Tentar novamente">Tentar novamente</button>
            <button @click="errorMsg = ''; pendingContent = ''" class="text-red-500 hover:text-red-700 text-lg leading-none" aria-label="Fechar erro">&times;</button>
        </div>
    </div>

    {{-- Input area --}}
    <div class="border-t border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 py-4 flex-shrink-0 safe-area-bottom">
        <div class="max-w-3xl mx-auto px-4">
            <form @submit.prevent="sendMessage()" class="flex items-end gap-3">
                <textarea
                    x-ref="input"
                    x-model="draft"
                    @keydown.enter="if (!$event.shiftKey && !sending) { $event.preventDefault(); sendMessage() }"
                    @input="autoResize($refs.input)"
                    rows="1"
                    placeholder="Escreva sua mensagem…"
                    class="flex-1 resize-none rounded-xl border border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-gray-100 placeholder-gray-400 dark:placeholder-gray-500 px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 max-h-40 overflow-y-hidden"
                    aria-label="Mensagem"
                ></textarea>
                <button
                    type="submit"
                    :disabled="sending || !draft.trim()"
                    class="flex-shrink-0 w-10 h-10 rounded-xl bg-indigo-600 hover:bg-indigo-500 disabled:bg-gray-300 dark:disabled:bg-gray-600 text-white flex items-center justify-center transition-colors"
                    aria-label="Enviar mensagem">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M12 5l7 7-7 7"/>
                    </svg>
                </button>
            </form>
            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1.5 text-center select-none">Enter envia · Shift+Enter quebra linha</p>
        </div>
    </div>
</div>

<script>
function chatApp(endpoint, regenEndpoint, streaming, userInitial, conversationId, hasMessages) {
    return {
        draft: '',
        sending: false,
        typing: false,
        errorMsg: '',
        pendingContent: '',
        useStreaming: streaming,
        regenEndpoint,
        userInitial,
        conversationId,
        hasMessages,
        isAtBottom: true,

        init() {
            this.scrollBottom();
            this.$refs.messages?.addEventListener('scroll', () => this.checkScroll());
            if (window.matchMedia('(hover: hover)').matches) {
                this.$nextTick(() => this.$refs.input?.focus());
            }
        },

        checkScroll() {
            const el = this.$refs.messages;
            if (!el) return;
            this.isAtBottom = el.scrollHeight - el.scrollTop - el.clientHeight < 80;
        },

        scrollBottom() {
            this.$nextTick(() => {
                const el = this.$refs.messages;
                if (el) {
                    el.scrollTop = el.scrollHeight;
                    this.isAtBottom = true;
                }
            });
        },

        forceScrollBottom() {
            const el = this.$refs.messages;
            if (el) el.scrollTop = el.scrollHeight;
            this.isAtBottom = true;
        },

        updateTitle(title) {
            document.title = title + ' · ' + '{{ config('app.name', 'Chatbot') }}';
            const h1 = document.getElementById('conversation-title');
            if (h1) h1.textContent = title;
            const link = document.querySelector('[data-conversation-id="' + this.conversationId + '"]');
            if (link) link.textContent = title;
        },

        autoResize(el) {
            el.style.overflowY = 'hidden';
            el.style.height = 'auto';
            const h = Math.min(el.scrollHeight, 160);
            el.style.height = h + 'px';
            if (h >= 160) el.style.overflowY = 'auto';
        },

        useSuggestion(text) {
            this.draft = text;
            this.$nextTick(() => {
                this.autoResize(this.$refs.input);
                this.sendMessage();
            });
        },

        buildRegenBtn() {
            const btn = document.createElement('button');
            btn.className = 'regen-btn self-start flex items-center gap-1 px-2 py-1 text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 opacity-60 md:opacity-0 md:group-hover:opacity-100 transition-opacity rounded';
            btn.setAttribute('aria-label', 'Regenerar resposta');
            btn.onclick = () => document.dispatchEvent(new CustomEvent('regen-request'));
            btn.innerHTML =
                '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">' +
                '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>' +
                '</svg> Regenerar';
            return btn;
        },

        addRegenBtn(el) {
            const inner = el.querySelector('.flex.flex-col');
            if (inner) inner.appendChild(this.buildRegenBtn());
        },

        appendMessage(role, html, raw) {
            const container = this.$refs.messages.querySelector('.max-w-3xl') || this.$refs.messages;
            const wrapper = document.createElement('div');

            if (role === 'assistant') {
                wrapper.className = 'group flex items-start gap-3';
                wrapper.setAttribute('data-role', 'assistant');
                wrapper.innerHTML =
                    '<div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0" aria-hidden="true">IA</div>' +
                    '<div class="flex flex-col gap-1 min-w-0">' +
                    '<div class="msg-content relative prose prose-sm dark:prose-invert max-w-prose bg-white dark:bg-gray-800 rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm border border-gray-100 dark:border-gray-700 break-words [&>*:last-child]:mb-0">' +
                    html +
                    '<button onclick="copyMessage(this)" class="copy-btn absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Copiar resposta">' +
                    '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>' +
                    '</button>' +
                    '</div>' +
                    '</div>';
                this.addCodeCopyButtons(wrapper);
                this.addRegenBtn(wrapper);
            } else {
                wrapper.className = 'flex items-start gap-3 justify-end';
                const escaped = raw.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
                wrapper.innerHTML =
                    '<div class="max-w-prose bg-indigo-600 text-white rounded-2xl rounded-tr-sm px-4 py-3 text-sm whitespace-pre-wrap break-words">' + escaped + '</div>';
            }

            const typing = container.querySelector('[x-show="typing"]');
            if (typing) container.insertBefore(wrapper, typing);
            else container.appendChild(wrapper);

            if (this.isAtBottom) this.scrollBottom();
        },

        addCodeCopyButtons(container) {
            container.querySelectorAll('pre').forEach(pre => {
                if (pre.querySelector('.code-copy-btn')) return;
                const btn = document.createElement('button');
                btn.className = 'code-copy-btn absolute top-2 right-2 px-2 py-1 text-xs bg-gray-700 hover:bg-gray-600 text-gray-300 rounded transition-colors';
                btn.textContent = 'Copiar';
                btn.setAttribute('aria-label', 'Copiar código');
                btn.onclick = function () {
                    const code = pre.querySelector('code')?.textContent || pre.textContent;
                    navigator.clipboard.writeText(code).then(() => {
                        btn.textContent = 'Copiado!';
                        setTimeout(() => { btn.textContent = 'Copiar'; }, 2000);
                    });
                };
                pre.style.position = 'relative';
                pre.appendChild(btn);
            });
        },

        async regenerate() {
            if (this.sending) return;

            const container = this.$refs.messages.querySelector('.max-w-3xl') || this.$refs.messages;
            const allAssistant = container.querySelectorAll('[data-role="assistant"]');
            const lastAssistantEl = allAssistant[allAssistant.length - 1];
            if (! lastAssistantEl) return;

            const contentDiv = lastAssistantEl.querySelector('.msg-content');
            const originalHtml = contentDiv?.innerHTML;

            this.sending  = true;
            this.errorMsg = '';

            if (contentDiv) {
                contentDiv.innerHTML =
                    '<div class="flex gap-1" aria-label="Carregando">' +
                    '<span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>' +
                    '<span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>' +
                    '<span class="w-2 h-2 bg-gray-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>' +
                    '</div>';
            }
            lastAssistantEl.querySelector('.regen-btn')?.remove();

            try {
                const res = await fetch(this.regenEndpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                });
                const data = await res.json();

                if (! res.ok) {
                    if (contentDiv) contentDiv.innerHTML = originalHtml;
                    this.addRegenBtn(lastAssistantEl);
                    this.errorMsg = data.error ?? 'Erro ao regenerar. Tente novamente.';
                    return;
                }

                if (contentDiv) {
                    contentDiv.innerHTML = data.html;
                    this.addCodeCopyButtons(lastAssistantEl);
                }
                this.addRegenBtn(lastAssistantEl);
                if (this.isAtBottom) this.scrollBottom();
            } catch {
                if (contentDiv) contentDiv.innerHTML = originalHtml;
                this.addRegenBtn(lastAssistantEl);
                this.errorMsg = 'Falha na conexão. Verifique sua internet e tente novamente.';
            } finally {
                this.sending = false;
                this.$nextTick(() => this.$refs.input?.focus());
            }
        },

        async retrySend() {
            if (!this.pendingContent || this.sending) return;
            const content = this.pendingContent;
            this.errorMsg = '';
            this.sending  = true;
            this.typing   = true;
            try {
                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ content }),
                });
                const data = await res.json();
                this.typing = false;
                if (!res.ok) {
                    this.errorMsg = data.error ?? 'Erro inesperado. Tente novamente.';
                    return;
                }
                this.pendingContent = '';
                if (data.title) this.updateTitle(data.title);
                this.appendMessage('assistant', data.html, data.content);
            } catch {
                this.typing   = false;
                this.errorMsg = 'Falha na conexão. Verifique sua internet e tente novamente.';
            } finally {
                this.sending = false;
                this.$nextTick(() => this.$refs.input?.focus());
            }
        },

        async sendMessage() {
            const content = this.draft.trim();
            if (!content || this.sending) return;

            this.hasMessages    = true;
            this.pendingContent = content;
            this.errorMsg       = '';
            this.sending        = true;
            this.typing         = true;

            // Remove regen buttons: AI hasn't responded to the new message yet
            document.querySelectorAll('.regen-btn').forEach(b => b.remove());

            this.appendMessage('user', '', content);
            this.draft = '';
            this.$nextTick(() => {
                const el = this.$refs.input;
                if (el) { el.style.height = 'auto'; el.style.overflowY = 'hidden'; }
                this.$refs.input?.focus();
            });

            if (this.useStreaming) {
                await this.sendStreaming(content);
            } else {
                await this.sendRegular(content);
            }
        },

        async sendRegular(content) {
            try {
                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ content }),
                });
                const data = await res.json();
                this.typing = false;

                if (!res.ok) {
                    this.errorMsg = data.error ?? 'Erro inesperado. Tente novamente.';
                    if (res.status === 422 && data.errors?.content) {
                        this.errorMsg = data.errors.content[0];
                    }
                    return;
                }

                this.pendingContent = '';
                if (data.title) this.updateTitle(data.title);
                this.appendMessage('assistant', data.html, data.content);
            } catch {
                this.typing   = false;
                this.errorMsg = 'Falha na conexão. Verifique sua internet e tente novamente.';
            } finally {
                this.sending = false;
                this.$nextTick(() => this.$refs.input?.focus());
            }
        },

        async sendStreaming(content) {
            let assistantEl = null;
            let buffer = '';

            try {
                const res = await fetch(endpoint, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'text/event-stream',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ content }),
                });

                if (!res.ok) {
                    this.typing = false;
                    this.errorMsg = 'Erro no servidor. Tente novamente.';
                    return;
                }

                const reader = res.body.getReader();
                const decoder = new TextDecoder();
                let remainder = '';

                while (true) {
                    const { value, done } = await reader.read();
                    if (done) break;
                    remainder += decoder.decode(value, { stream: true });
                    const lines = remainder.split('\n');
                    remainder = lines.pop();

                    for (const line of lines) {
                        if (!line.startsWith('data:')) continue;
                        const event = JSON.parse(line.slice(5).trim());

                        if (event.error) {
                            this.typing = false;
                            this.errorMsg = event.error;
                            return;
                        }

                        if (event.delta !== undefined) {
                            this.typing = false;
                            buffer += event.delta;
                            if (!assistantEl) {
                                const container = this.$refs.messages.querySelector('.max-w-3xl') || this.$refs.messages;
                                assistantEl = document.createElement('div');
                                assistantEl.className = 'group flex items-start gap-3';
                                assistantEl.setAttribute('data-role', 'assistant');
                                assistantEl.innerHTML =
                                    '<div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">IA</div>' +
                                    '<div class="flex flex-col gap-1 min-w-0">' +
                                    '<div class="msg-content relative prose prose-sm dark:prose-invert max-w-prose bg-white dark:bg-gray-800 rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm border border-gray-100 dark:border-gray-700 [&>*:last-child]:mb-0 streaming-content"></div>' +
                                    '</div>';
                                const typing = container.querySelector('[x-show="typing"]');
                                if (typing) container.insertBefore(assistantEl, typing);
                                else container.appendChild(assistantEl);
                            }
                            assistantEl.querySelector('.streaming-content').textContent = buffer;
                            if (this.isAtBottom) this.scrollBottom();
                        }

                        if (event.done) {
                            if (event.title) this.updateTitle(event.title);
                            if (assistantEl) {
                                const contentDiv = assistantEl.querySelector('.msg-content');
                                contentDiv.innerHTML = event.html +
                                    '<button onclick="copyMessage(this)" class="copy-btn absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300" aria-label="Copiar resposta">' +
                                    '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>' +
                                    '</button>';
                                this.addCodeCopyButtons(assistantEl);
                                this.addRegenBtn(assistantEl);
                            }
                            this.pendingContent = '';
                        }
                    }
                }
            } catch {
                this.typing   = false;
                this.errorMsg = 'Falha na conexão. Verifique sua internet e tente novamente.';
            } finally {
                this.sending = false;
                this.$nextTick(() => this.$refs.input?.focus());
            }
        },
    };
}

function copyMessage(btn) {
    const bubble = btn.closest('.msg-content');
    const text = bubble ? bubble.innerText.replace(/Copiar\n?|Copiado!\n?/g, '').trim() : '';
    navigator.clipboard.writeText(text).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
        setTimeout(() => { btn.innerHTML = orig; }, 2000);
    });
}
</script>

</x-layouts.chat>
