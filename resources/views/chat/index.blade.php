<x-layouts.chat :conversations="$conversations">
    <div class="flex flex-col items-center justify-center flex-1 text-center p-8">
        <div class="text-4xl mb-4">💬</div>
        <h2 class="text-xl font-semibold text-gray-700 dark:text-gray-300 mb-2">Bem-vindo ao Chatbot</h2>
        <p class="text-gray-500 dark:text-gray-400 mb-6">Selecione uma conversa ao lado ou inicie uma nova.</p>
        <form method="POST" action="{{ route('conversations.store') }}">
            @csrf
            <button type="submit"
                class="px-6 py-3 rounded-xl bg-indigo-600 hover:bg-indigo-500 text-white font-medium transition-colors">
                Nova conversa
            </button>
        </form>
    </div>
</x-layouts.chat>
