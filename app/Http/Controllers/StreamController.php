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
use Symfony\Component\HttpFoundation\StreamedResponse;

class StreamController extends Controller
{
    public function __construct(private readonly ChatClient $ai) {}

    public function stream(SendMessageRequest $request, Conversation $conversation): StreamedResponse
    {
        $this->authorize('view', $conversation);

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

        $fullReply = '';

        return response()->stream(function () use ($payload, $conversation, $userMessage, &$fullReply, $newTitle) {
            try {
                $this->ai->streamChat($payload, function (string $delta) use (&$fullReply) {
                    $fullReply .= $delta;
                    echo 'data: '.json_encode(['delta' => $delta])."\n\n";
                    ob_flush();
                    flush();
                });
            } catch (AiRateLimited $e) {
                $userMessage->delete();
                echo 'data: '.json_encode(['error' => $e->getMessage(), 'status' => 429])."\n\n";
                ob_flush();
                flush();

                return;
            } catch (AiBadResponse|AiUnavailable $e) {
                $userMessage->delete();
                echo 'data: '.json_encode(['error' => $e->getMessage(), 'status' => 502])."\n\n";
                ob_flush();
                flush();

                return;
            }

            if ($fullReply !== '') {
                $message = $conversation->messages()->create([
                    'role' => 'assistant',
                    'content' => $fullReply,
                ]);

                echo 'data: '.json_encode([
                    'done' => true,
                    'id' => $message->id,
                    'html' => Markdown::toHtml($fullReply),
                    'title' => $newTitle,
                ])."\n\n";
                ob_flush();
                flush();
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
