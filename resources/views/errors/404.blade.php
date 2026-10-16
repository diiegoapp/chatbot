<!DOCTYPE html>
<html lang="pt-BR" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Página não encontrada · {{ config('app.name', 'Chatbot') }}</title>
    <link rel="icon" href="/favicon.svg?v=2" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico?v=2" sizes="any">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2">
    <script>
        (function () {
            const s = localStorage.getItem('theme');
            const p = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (s === 'dark' || (!s && p)) document.documentElement.classList.add('dark');
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="h-full bg-gray-50 dark:bg-gray-900 text-gray-900 dark:text-gray-100 flex items-center justify-center p-6">
    <div class="text-center max-w-md">
        <div class="text-7xl mb-6">🔍</div>
        <h1 class="text-3xl font-bold mb-2">Página não encontrada</h1>
        <p class="text-gray-500 dark:text-gray-400 mb-8">O endereço que você acessou não existe ou foi removido.</p>
        @auth
            <a href="{{ route('conversations.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-medium transition-colors">
                Voltar ao chat
            </a>
        @else
            <a href="{{ route('login') }}" class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-500 text-white rounded-xl font-medium transition-colors">
                Fazer login
            </a>
        @endauth
    </div>
</body>
</html>
