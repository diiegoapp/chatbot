@props(['message', 'canRegenerate' => false])

<div class="{{ $message->role === 'user' ? 'flex items-start gap-3 justify-end' : 'group flex items-start gap-3' }}"
     @if($message->role !== 'user') data-role="assistant" @endif>

    @if($message->role !== 'user')
        <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0" aria-hidden="true">IA</div>
        <div class="flex flex-col gap-1 min-w-0">
            <div class="msg-content relative prose prose-sm dark:prose-invert max-w-prose bg-white dark:bg-gray-800 rounded-2xl rounded-tl-sm px-4 py-3 shadow-sm border border-gray-100 dark:border-gray-700 break-words [&>*:last-child]:mb-0">
                {!! \App\Support\Markdown::toHtml($message->content) !!}
                <button
                    onclick="copyMessage(this)"
                    class="copy-btn absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity p-1 rounded text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                    aria-label="Copiar resposta">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                </button>
            </div>
            @if($canRegenerate)
                <button
                    onclick="document.dispatchEvent(new CustomEvent('regen-request'))"
                    class="regen-btn self-start flex items-center gap-1 px-2 py-1 text-xs text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 opacity-60 md:opacity-0 md:group-hover:opacity-100 transition-opacity rounded"
                    aria-label="Regenerar resposta">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    Regenerar
                </button>
            @endif
        </div>
    @else
        <div class="max-w-prose bg-indigo-600 text-white rounded-2xl rounded-tr-sm px-4 py-3 text-sm whitespace-pre-wrap break-words">{{ $message->content }}</div>
    @endif
</div>
