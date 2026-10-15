<x-layouts.chat :conversations="$conversations">

<div class="flex-1 overflow-y-auto p-6">
    <div class="max-w-2xl mx-auto space-y-6">

        <h2 class="text-xl font-bold text-gray-900 dark:text-gray-100">{{ __('Profile') }}</h2>

        {{-- Informações do Perfil --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            @include('profile.partials.update-profile-information-form')
        </div>

        {{-- Atualizar Senha --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-200 dark:border-gray-700 p-6">
            @include('profile.partials.update-password-form')
        </div>

        {{-- Excluir Conta --}}
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-red-200 dark:border-red-900 p-6">
            @include('profile.partials.delete-user-form')
        </div>

        <div class="text-center">
            <a href="{{ route('conversations.index') }}" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
                ← Voltar ao chat
            </a>
        </div>
    </div>
</div>

</x-layouts.chat>
