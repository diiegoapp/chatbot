<?php

return [
    'streaming' => (bool) env('CHATBOT_STREAMING', false),
    'context_window' => (int) env('CHATBOT_CONTEXT_WINDOW', 20),
    'max_message_length' => (int) env('CHATBOT_MAX_MESSAGE_LENGTH', 4000),

    'system_prompt' => 'Você é um assistente útil e objetivo. Responda sempre em português do Brasil. '.
                       'Pode usar Markdown para formatar suas respostas quando for útil.',
];
