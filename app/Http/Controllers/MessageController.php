<?php

namespace App\Http\Controllers;

use App\Exceptions\AiBadResponse;
use App\Exceptions\AiRateLimited;
use App\Exceptions\AiUnavailable;
use App\Http\Requests\SendMessageRequest;
use App\Models\Conversation;
use App\Services\Ai\ChatClient;
use App\Support\Markdown;
use App\Support\SystemPrompt;
use Illuminate\Http\JsonResponse;

class MessageController extends Controller
{
    public function __construct(private readonly ChatClient $ai) {}

    public function store(SendMessageRequest $request, Conversation $conversation): JsonResponse
    {
        $this->authorize('view', $conversation);

        // Give PHP enough time to outlast the HTTP timeout so the exception is caught properly
        set_time_limit(config('services.zai.timeout', 25) + 10);

        $userContent = $request->validated('content');

        $userMessage = $conversation->messages()->create([
            'role' => 'user',
            'content' => $userContent,
        ]);

        $newTitle = null;
        if ($conversation->title === null) {
            $newTitle = preg_replace('/[\s.!?,;:…\-–—]+$/u', '', mb_substr($userContent, 0, 60));
            $conversation->update(['title' => $newTitle]);
        }

        $window = config('chatbot.context_window', 20);
        $history = $conversation->messages()
            ->latest('id')
            ->limit($window)
            ->get()
            ->reverse()
            ->values();

        $payload = collect([['role' => 'system', 'content' => SystemPrompt::build()]])
            ->concat($history->map(fn ($m) => ['role' => $m->role, 'content' => $m->content]))
            ->all();

        try {
            $reply = $this->ai->chat($payload);
        } catch (AiRateLimited $e) {
            $userMessage->delete();

            return response()->json(['error' => $e->getMessage()], 429);
        } catch (AiBadResponse|AiUnavailable $e) {
            $userMessage->delete();

            return response()->json(['error' => $e->getMessage()], 502);
        }

        $message = $conversation->messages()->create([
            'role' => 'assistant',
            'content' => $reply,
        ]);

        return response()->json([
            'id' => $message->id,
            'content' => $reply,
            'html' => Markdown::toHtml($reply),
            'title' => $newTitle,
        ]);
    }
}
