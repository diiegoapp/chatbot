@props(['conversations' => collect(), 'conversation' => null])
@php
    $groups = \App\Support\ConversationGrouper::group($conversations);
@endphp
<!DOCTYPE html>
<html lang="pt-BR"
    x-data="{
        sidebarOpen: window.__sidebarOpen,
        darkMode: document.documentElement.classList.contains('dark'),
        toggleTheme() {
            this.darkMode = !this.darkMode;
            document.documentElement.classList.toggle('dark', this.darkMode);
            localStorage.setItem('theme', this.darkMode ? 'dark' : 'light');
        },
        openSidebar() {
            this.sidebarOpen = true;
            if (window.innerWidth >= 768) {
                document.documentElement.classList.add('sidebar-open');
                localStorage.setItem('sidebarOpen', 'true');
                this.$nextTick(() => this.$refs.sidebarCollapseButton.focus());
            }
        },
        closeMobileSidebar() {
            this.sidebarOpen = false;
            document.documentElement.classList.remove('sidebar-open');
        },
        collapseSidebar() {
            this.sidebarOpen = false;
            document.documentElement.classList.remove('sidebar-open');
            localStorage.setItem('sidebarOpen', 'false');
            this.$nextTick(() => this.$refs.headerSidebarButton.focus());
        }
    }"
    class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@isset($conversation){{ ($conversation->title ?? 'Nova conversa') . ' · ' }}@endisset{{ config('app.name', 'Chatbot') }}</title>
    <meta name="description" content="Assistente de IA disponível para responder suas dúvidas.">
    <link rel="icon" href="/favicon.svg?v=2" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico?v=2" sizes="any">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2">
    <script>
        (function () {
            // Theme: apply before first paint to avoid flash
            const s = localStorage.getItem('theme');
            const p = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (s === 'dark' || (!s && p)) document.documentElement.classList.add('dark');

            // Sidebar: mobile always closed; desktop reads localStorage (default open)
            const mobile = window.innerWidth < 768;
            window.__sidebarOpen = mobile ? false : localStorage.getItem('sidebarOpen') !== 'false';
            document.documentElement.classList.toggle('sidebar-open', window.__sidebarOpen);
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors">

<div class="flex h-[100dvh] md:h-full overflow-hidden">

    {{-- Full-height sidebar: desktop main content reserves its width; mobile remains an overlay drawer. --}}
    <aside
        id="main-sidebar"
        :class="{ 'sidebar-open': sidebarOpen }"
        :aria-hidden="!sidebarOpen ? 'true' : 'false'"
        :inert="!sidebarOpen ? '' : null"
        class="fixed top-0 left-0 z-40 flex flex-col w-64 h-full bg-gray-800 dark:bg-gray-950"
    >
        {{-- Sidebar header: shared height and divider with the main header. --}}
        <div class="flex items-center justify-between px-4 h-16 border-b border-gray-700 dark:border-gray-800 flex-shrink-0">
            <span class="flex items-center gap-2 text-white font-semibold">
                <span class="text-lg">💬</span>
                <span>{{ config('app.name', 'Chatbot') }}</span>
            </span>
            <button
                @click="closeMobileSidebar()"
                class="md:hidden text-gray-400 hover:text-white min-h-[44px] min-w-[44px] flex items-center justify-center"
                aria-label="Fechar menu">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
            <button
                type="button"
                x-ref="sidebarCollapseButton"
                @click="collapseSidebar()"
                class="sidebar-toggle-button hidden md:flex"
                :aria-label="sidebarOpen ? 'Recolher barra lateral' : 'Abrir barra lateral'"
                :aria-expanded="sidebarOpen"
                aria-controls="main-sidebar">
                <x-icons.sidebar-toggle />
            </button>
        </div>

        {{-- Nova conversa --}}
        <div class="px-3 py-3 flex-shrink-0">
            <form method="POST" action="{{ route('conversations.store') }}">
                @csrf
                <button type="submit"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium transition-colors min-h-[44px]">
                    <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
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
                                @click="if (window.innerWidth < 768) closeMobileSidebar()"
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

        {{-- Bug A: user menu footer --}}
        <div
            class="px-3 py-3 border-t border-gray-700 dark:border-gray-800 relative flex-shrink-0"
            x-data="{ menuOpen: false }"
            @click.outside="menuOpen = false"
            @keydown.escape.window="if (menuOpen) { menuOpen = false; $nextTick(() => $refs.userMenuBtn.focus()); }"
        >
            {{-- Dropdown: positioned ABOVE button (bottom-full) --}}
            {{-- Transition: slides up from slightly below its final position --}}
            <div
                x-show="menuOpen"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="opacity-0 translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 translate-y-1"
                class="absolute bottom-full left-3 right-3 mb-1 bg-gray-700 dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden max-h-64 overflow-y-auto"
                role="menu"
                aria-label="Menu do usuário"
            >
                <a
                    href="{{ route('profile.edit') }}"
                    class="flex items-center gap-2 px-4 py-2.5 text-sm text-gray-200 hover:bg-gray-600 transition-colors min-h-[44px]"
                    role="menuitem"
                >
                    <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Perfil
                </a>

                {{-- Theme toggle with current state label --}}
                <button
                    @click="toggleTheme(); menuOpen = false"
                    class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-gray-200 hover:bg-gray-600 transition-colors min-h-[44px]"
                    role="menuitem"
                >
                    {{-- Moon: shown in light mode → switching to dark --}}
                    <svg x-show="!darkMode" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                    {{-- Sun: shown in dark mode → switching to light --}}
                    <svg x-show="darkMode" class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span x-text="darkMode ? 'Tema: escuro' : 'Tema: claro'"></span>
                </button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-gray-200 hover:bg-gray-600 transition-colors min-h-[44px]"
                        role="menuitem"
                    >
                        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        Sair
                    </button>
                </form>
            </div>

            {{-- Bug A: trigger button — arrow UP when closed (rotate-180), DOWN when open --}}
            <button
                x-ref="userMenuBtn"
                @click="menuOpen = !menuOpen"
                class="w-full flex items-center gap-3 hover:bg-gray-700 rounded-lg px-2 py-2 transition-colors min-h-[44px]"
                aria-label="Menu do usuário"
                aria-haspopup="menu"
                :aria-expanded="menuOpen"
            >
                <div class="w-8 h-8 rounded-full bg-indigo-600 flex items-center justify-center text-white text-sm font-bold flex-shrink-0">
                    {{ mb_strtoupper(mb_substr(auth()->user()->name, 0, 1)) }}
                </div>
                <span class="flex-1 text-sm text-gray-200 text-left truncate">{{ auth()->user()->name }}</span>
                {{-- chevron-down SVG: rotate-180 = UP (closed), no rotation = DOWN (open) --}}
                <svg
                    class="w-5 h-5 text-gray-400 transition-transform duration-150"
                    :class="menuOpen ? '' : 'rotate-180'"
                    fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"
                    aria-hidden="true"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
        </div>
    </aside>

    {{-- Mobile overlay: shown when sidebar open on small screens --}}
    <div
        x-show="sidebarOpen"
        @click="closeMobileSidebar()"
        @keydown.escape.window="if (window.innerWidth < 768) closeMobileSidebar()"
        class="fixed inset-0 z-30 bg-black/50 md:hidden"
        x-transition:enter="transition-opacity duration-200"
        x-transition:leave="transition-opacity duration-200"
        aria-hidden="true"
    ></div>

    {{-- Desktop margin follows the sidebar without moving the header independently of the content. --}}
    <main class="chat-main flex flex-col flex-1 min-w-0 min-h-0">
        <header class="flex items-center gap-3 px-4 h-16 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 flex-shrink-0">
            <button
                type="button"
                x-ref="headerSidebarButton"
                @click="openSidebar()"
                class="sidebar-toggle-button header-sidebar-button flex"
                :aria-label="sidebarOpen ? 'Recolher barra lateral' : 'Abrir barra lateral'"
                :aria-expanded="sidebarOpen"
                aria-controls="main-sidebar">
                <x-icons.sidebar-toggle />
            </button>
            <h1 id="conversation-title" class="font-semibold truncate flex-1">{{ isset($conversation) ? ($conversation->title ?? 'Nova conversa') : config('app.name', 'Chatbot') }}</h1>
            {{-- Header theme button: CSS-driven icons (no Alpine needed, no flash) --}}
            <button
                @click="toggleTheme()"
                class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 min-h-[44px] min-w-[44px] flex items-center justify-center"
                aria-label="Alternar tema">
                {{-- Moon: shown in light mode --}}
                <svg class="w-5 h-5 dark:hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                </svg>
                {{-- Sun: shown in dark mode --}}
                <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                </svg>
            </button>
        </header>

        {{ $slot }}
    </main>
</div>

</body>
</html>
