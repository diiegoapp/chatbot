<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"
      class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Chatbot') }}</title>
    <link rel="icon" href="/favicon.svg?v=2" type="image/svg+xml">
    <link rel="icon" href="/favicon.ico?v=2" sizes="any">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png?v=2">
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet"/>
    <script>
        (function () {
            const s = localStorage.getItem('theme');
            const p = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (s === 'dark' || (!s && p)) document.documentElement.classList.add('dark');
        })();
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased h-full bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100 transition-colors">
<div class="min-h-full flex flex-col sm:justify-center items-center pt-6 sm:pt-0">
    <div class="mb-6 flex flex-col items-center gap-2">
        <div class="w-12 h-12 rounded-2xl bg-indigo-600 flex items-center justify-center text-white text-2xl">💬</div>
        <span class="text-xl font-bold text-gray-800 dark:text-gray-100">{{ config('app.name', 'Chatbot') }}</span>
    </div>

    <div class="w-full sm:max-w-md px-6 py-6 bg-white dark:bg-gray-800 shadow-md overflow-hidden sm:rounded-xl">
        {{ $slot }}
    </div>

    <button
        onclick="(function(){const d=document.documentElement.classList.toggle('dark');localStorage.setItem('theme',d?'dark':'light');})()"
        class="mt-4 text-xs text-gray-400 dark:text-gray-500 hover:text-gray-600 dark:hover:text-gray-300 transition-colors"
        aria-label="Alternar tema">
        <span class="dark:hidden">🌙 Modo escuro</span>
        <span class="hidden dark:inline">☀️ Modo claro</span>
    </button>
</div>
</body>
</html>
