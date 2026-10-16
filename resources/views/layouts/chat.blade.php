@props(['conversations' => collect(), 'conversation' => null])
<!DOCTYPE html>
<html lang="pt-BR" x-data="{ sidebarOpen: window.innerWidth >= 768 }" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }}</title>
    <link rel="icon" href="/favicon.svg?v=2" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico?v=2" sizes="any">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100">

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
        <div class="flex items-center justify-between px-4 py-4 border-b border-gray-700">
            <span class="text-lg font-semibold text-white">💬 Chatbot</span>
            <button @click="sidebarOpen = false" class="md:hidden text-gray-400 hover:text-white">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="px-3 py-3">
            <form method="POST" action="{{ route('conversations.store') }}">
                @csrf
                <button type="submit"
                    class="w-full flex items-center gap-2 px-3 py-2 rounded-lg bg-indigo-600 hover:bg-indigo-500 text-white text-sm font-medium transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nova conversa
                </button>
            </form>
        </div>

        <nav class="flex-1 overflow-y-auto px-2 py-2 space-y-1">
            @foreach($conversations as $conv)
                <div class="group flex items-center rounded-lg {{ isset($conversation) && $conversation->id === $conv->id ? 'bg-gray-700' : 'hover:bg-gray-700' }} transition-colors">
                    <a href="{{ route('conversations.show', $conv) }}"
                       class="flex-1 truncate px-3 py-2 text-sm text-gray-200">
                        {{ $conv->title ?? 'Nova conversa' }}
                    </a>
                    <form method="POST" action="{{ route('conversations.destroy', $conv) }}" class="pr-2 hidden group-hover:block">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            onclick="return confirm('Excluir esta conversa?')"
                            class="text-gray-400 hover:text-red-400 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </button>
                    </form>
                </div>
            @endforeach
        </nav>

        <div class="px-4 py-4 border-t border-gray-700">
            <div class="text-xs text-gray-400 mb-2 truncate">{{ auth()->user()->name }}</div>
            <div class="flex gap-2">
                <a href="{{ route('profile.edit') }}" class="text-xs text-gray-400 hover:text-white transition-colors">Perfil</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-xs text-gray-400 hover:text-white transition-colors">Sair</button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Overlay for mobile --}}
    <div
        x-show="sidebarOpen"
        @click="sidebarOpen = false"
        class="fixed inset-0 z-30 bg-black/50 md:hidden"
        x-transition:enter="transition-opacity duration-200"
        x-transition:leave="transition-opacity duration-200"
    ></div>

    {{-- Main content --}}
    <main class="flex flex-col flex-1 min-w-0">
        <header class="flex items-center gap-3 px-4 py-3 border-b border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">
            <button @click="sidebarOpen = !sidebarOpen" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                </svg>
            </button>
            <h1 class="font-semibold truncate">{{ isset($conversation) ? ($conversation->title ?? 'Nova conversa') : 'Chatbot' }}</h1>
        </header>

        {{ $slot }}
    </main>
</div>

</body>
</html>
