@props(['conversations' => collect(), 'conversation' => null])
@php
    $groups = \App\Support\ConversationGrouper::group($conversations);
@endphp
<!DOCTYPE html>
<html lang="pt-BR" x-data="{ sidebarOpen: window.innerWidth >= 768 }" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@isset($conversation){{ ($conversation->title ?? 'Nova conversa') . ' · ' }}@endisset{{ config('app.name', 'Chatbot') }}</title>
    <meta name="description" content="Assistente de IA disponível para responder suas dúvidas.">
    <link rel="icon" href="data:image/svg+xml,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'><text y='.9em' font-size='90'>💬</text></svg>">
    <script>
        (function () {
            const s = localStorage.getItem('theme');
            const p = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (s === 'dark' || (!s && p)) document.documentElement.classList.add('dark');
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors">

<div class="flex h-full overflow-hidden">

    {{-- Sidebar --}}
    <aside
        x-show="sidebarOpen"
        x-transition:enter="transition ease-in-out duration-200"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transition ease-in-out duration-200"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="fixed inset-y-0 left-0 z-40 flex flex-col w-64 bg-gray-800 dark:bg-gray-950 md:relative md:flex"
    >
        {{-- Sidebar header --}}
        <div class="flex items-center justify-between px-4 h-16 border-b border-gray-700 dark:border-gray-800">
            <span class="flex items-center gap-2 text-white font-semibold">
                <span class="text-lg">💬</span>
                <span>{{ config('app.name', 'Chatbot') }}</span>
            </span>
            <button @click="sidebarOpen = false" class="md:hidden text-gray-400 hover:text-white min-h-[44px] min-w-[44px] flex items-center justify-center" aria-label="Fechar menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Nova conversa --}}
        <div class="px-3 py-3">
            <form method="POST" action="{{ route('conversations.store') }}">
                @csrf
                <button type="submit"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium transition-colors min-h-[44px]">
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nova conversa
                </button>
            </form>
        </div>

        {{-- Conversation list --}}
        <nav class="flex-1 overflow-y-auto px-2 py-1 space-y-3" aria-label="Conversas">
            @forelse($groups as $groupName => $convs)
                <div>
                    <div class="px-3 py-1 text-xs text-gray-500 font-medium uppercase tracking-wider">{{ $groupName }}</div>
                    @foreach($convs as $conv)
                        <div
                            class="group flex items-center min-w-0 rounded-lg {{ isset($conversation) && $conversation->id === $conv->id ? 'bg-gray-700' : 'hover:bg-gray-700' }} transition-colors"
                            x-data="{
                                editing: false,
                                editTitle: {{ json_encode($conv->title ?? 'Nova conversa') }},
                                async save() {
                                    const r = await fetch('{{ route('conversations.update', $conv) }}', {
                                        method: 'PATCH',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': document.querySelector('[name=csrf-token]').content,
                                        },
                                        body: JSON.stringify({ title: this.editTitle }),
                                    });
                                    if (r.ok) {
                                        @if(isset($conversation) && $conversation->id === $conv->id)
                                        document.getElementById('conversation-title').textContent = this.editTitle;
                                        document.title = this.editTitle + ' · {{ config('app.name', 'Chatbot') }}';
                                        @endif
                                    }
                                    this.editing = false;
                                }
                            }"
                        >
                            <a
                                x-show="!editing"
                                href="{{ route('conversations.show', $conv) }}"
                                @click="sidebarOpen = window.innerWidth >= 768 ? sidebarOpen : false"
                                class="flex-1 min-w-0 overflow-hidden px-3 py-2 text-sm text-gray-200 min-h-[44px] flex items-center"
                                title="{{ $conv->title ?? 'Nova conversa' }}"
                                data-conversation-id="{{ $conv->id }}"
                            >
                                <span class="truncate" x-text="editTitle"></span>
                            </a>
                            <input
                                x-show="editing"
                                x-model="editTitle"
                                x-ref="editInput"
                                @keydown.enter.prevent="save()"
                                @keydown.escape="editing = false; editTitle = {{ json_encode($conv->title ?? 'Nova conversa') }}"
                                class="flex-1 bg-gray-600 text-white text-sm px-2 py-1 rounded mx-1 focus:outline-none focus:ring-1 focus:ring-indigo-500"
                                aria-label="Renomear conversa"
                            />
                            <div x-show="!editing" class="flex flex-shrink-0 items-center gap-0.5 pr-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                <button
                                    @click="editing = true; $nextTick(() => { $refs.editInput.focus(); $refs.editInput.select(); })"
                                    class="p-1 text-gray-400 hover:text-gray-200 transition-colors min-h-[44px] min-w-[32px] flex items-center justify-center"
                                    aria-label="Renomear conversa">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('conversations.destroy', $conv) }}" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button
                                        type="submit"
                                        onclick="return confirm('Excluir esta conversa?')"
                                        class="p-1 text-gray-400 hover:text-red-400 transition-colors min-h-[44px] min-w-[32px] flex items-center justify-center"
                                        aria-label="Excluir conversa">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                            <button
                                x-show="editing"
                                @click="save()"
                                class="px-2 py-1 mr-1 text-xs bg-indigo-600 hover:bg-indigo-500 text-white rounded transition-colors"
                                aria-label="Salvar nome">✓</button>
                        </div>
                    @endforeach
                </div>
            @empty
                <div class="px-3 py-2 text-sm text-gray-500">Nenhuma conversa ainda.</div>
            @endforelse
        </nav>

        {{-- Sidebar footer --}}
        <div class="px-3 py-3 border-t border-gray-700 dark:border-gray-800" x-data="{ menuOpen: false }">
            <button
                @click="menuOpen = !menuOpen"
                class="w-full flex items-center gap-3 hover:bg-gray-700 rounded-lg px-2 py-2 transition-colors min-h-[44px]"
                aria-label="Menu do usuário"
                aria-haspopup="true"
                :aria-expanded="menuOpen">
                <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                </div>
                <span class="flex-1 text-sm text-gray-200 text-left truncate">{{ auth()->user()->name }}</span>
                <svg class="w-4 h-4 text-gray-400 transition-transform" :class="menuOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>

            <div
                x-show="menuOpen"
                @click.outside="menuOpen = false"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                class="mt-1 bg-gray-700 dark:bg-gray-800 rounded-lg overflow-hidden shadow-lg"
                role="menu">
                <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-200 hover:bg-gray-600 transition-colors min-h-[44px]" role="menuitem">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Perfil
                </a>
                <button
                    onclick="(function(){const d=document.documentElement.classList.toggle('dark');localStorage.setItem('theme',d?'dark':'light');})()"
                    class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-gray-200 hover:bg-gray-600 transition-colors min-h-[44px]"
                    role="menuitem">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    Alternar tema
                </button>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-gray-200 hover:bg-gray-600 transition-colors min-h-[44px]" role="menuitem">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Sair
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Mobile overlay --}}
    <div
        x-show="sidebarOpen"
        @click="sidebarOpen = false"
        @keydown.escape.window="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-black/50 md:hidden"
        x-transition:enter="transition-opacity duration-200"
        x-transition:leave="transition-opacity duration-200"
        aria-hidden="true"
    ></div>

    {{-- Main content --}}
    <main class="flex flex-col flex-1 min-w-0 min-h-0">
        <header class="flex items-center gap-3 px-4 h-16 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex-shrink-0">
            <button
                @click="sidebarOpen = !sidebarOpen"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 min-h-[44px] min-w-[44px] flex items-center justify-center"
                aria-label="Alternar menu lateral">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <h1 id="conversation-title" class="font-semibold truncate flex-1">{{ isset($conversation) ? ($conversation->title ?? 'Nova conversa') : config('app.name', 'Chatbot') }}</h1>
            <button
                onclick="(function(){const d=document.documentElement.classList.toggle('dark');localStorage.setItem('theme',d?'dark':'light');})()"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 min-h-[44px] min-w-[44px] flex items-center justify-center"
                aria-label="Alternar tema">
                <svg class="w-5 h-5 dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </button>
        </header>

        {{ $slot }}
    </main>
</div>

</body>
</html>
