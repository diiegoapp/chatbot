<?php

namespace App\Services\Ai;

interface ChatClient
{
    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     */
    public function chat(array $messages): string;

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  callable(string): void  $onChunk  Called with each text delta
     */
    public function streamChat(array $messages, callable $onChunk): void;
}
