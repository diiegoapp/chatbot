<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'content' => [
                'required',
                'string',
                'max:'.config('chatbot.max_message_length', 4000),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'content.required' => 'A mensagem não pode estar vazia.',
            'content.max' => 'A mensagem não pode ultrapassar :max caracteres.',
        ];
    }
}
