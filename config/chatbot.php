<?php

return [
    'streaming' => (bool) env('CHATBOT_STREAMING', false),
    'context_window' => (int) env('CHATBOT_CONTEXT_WINDOW', 20),
    'max_message_length' => (int) env('CHATBOT_MAX_MESSAGE_LENGTH', 4000),
    'daily_limit' => max(1, (int) env('CHATBOT_DAILY_LIMIT', 100)),

    'registration_enabled' => (bool) env('REGISTRATION_ENABLED', true),
    'demo_mode' => (bool) env('DEMO_MODE', false),
    'demo_show_credentials' => (bool) env('DEMO_SHOW_CREDENTIALS', false),
    'demo_user' => [
        'name' => env('DEMO_USER_NAME'),
        'email' => env('DEMO_USER_EMAIL'),
        'password' => env('DEMO_USER_PASSWORD'),
    ],

    'system_prompt' => 'Você é um assistente útil e objetivo. Responda sempre em português do Brasil. '.
                       'Pode usar Markdown para formatar suas respostas quando for útil.',
];
