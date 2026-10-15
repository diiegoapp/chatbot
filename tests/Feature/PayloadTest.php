<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Services\Ai\ChatClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PayloadTest extends TestCase
{
    use RefreshDatabase;

    private function capturingClient(): object
    {
        return new class implements ChatClient
        {
            public array $captured = [];

            public function chat(array $messages): string
            {
                $this->captured = $messages;

                return 'Quatro';
            }

            public function streamChat(array $messages, callable $onChunk): void
            {
                $this->captured = $messages;
                $onChunk('Quatro');
            }
        };
    }

    public function test_current_message_is_last_in_ai_payload(): void
    {
        $client = $this->capturingClient();
        $this->app->instance(ChatClient::class, $client);

        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        // Freeze time so all messages share the same created_at timestamp.
        // This exposes the non-determinism in ORDER BY created_at when the
        // controller uses ->latest() without a tie-breaker column.
        $this->freezeTime();

        $conversation->messages()->create(['role' => 'user', 'content' => 'Primeira pergunta']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Primeira resposta']);

        $this->actingAs($user)
            ->postJson(route('messages.store', $conversation), ['content' => 'Quanto é 2+2?'])
            ->assertOk();

        $captured = $client->captured;

        // 1st entry must be the system prompt
        $this->assertNotEmpty($captured);
        $this->assertEquals('system', $captured[0]['role']);

        // History in chronological order
        $this->assertEquals('user', $captured[1]['role']);
        $this->assertEquals('Primeira pergunta', $captured[1]['content']);
        $this->assertEquals('assistant', $captured[2]['role']);
        $this->assertEquals('Primeira resposta', $captured[2]['content']);

        // The LAST entry must be the current user message
        $last = end($captured);
        $this->assertEquals('user', $last['role']);
        $this->assertEquals('Quanto é 2+2?', $last['content']);
    }

    public function test_stream_current_message_is_last_in_ai_payload(): void
    {
        $client = $this->capturingClient();
        $this->app->instance(ChatClient::class, $client);

        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        $this->freezeTime();

        $conversation->messages()->create(['role' => 'user', 'content' => 'Primeira pergunta']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Primeira resposta']);

        $response = $this->actingAs($user)
            ->postJson(route('messages.stream', $conversation), ['content' => 'Quanto é 2+2?']);

        $response->assertOk();
        $response->streamedContent(); // executes the stream callback

        $captured = $client->captured;

        $this->assertNotEmpty($captured);
        $this->assertEquals('system', $captured[0]['role']);
        $this->assertEquals('user', $captured[1]['role']);
        $this->assertEquals('Primeira pergunta', $captured[1]['content']);
        $this->assertEquals('assistant', $captured[2]['role']);
        $this->assertEquals('Primeira resposta', $captured[2]['content']);

        $last = end($captured);
        $this->assertEquals('user', $last['role']);
        $this->assertEquals('Quanto é 2+2?', $last['content']);
    }

    public function test_current_message_is_last_with_distinct_timestamps(): void
    {
        // Reproduces the production bug: messages have different created_at values.
        // Before the fix, the relationship's ->orderBy('created_at') combined with
        // ->latest('id') caused ->reverse() to put the current message first, not last.
        $client = $this->capturingClient();
        $this->app->instance(ChatClient::class, $client);

        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        $conversation->messages()->create(['role' => 'user', 'content' => 'Primeira pergunta']);
        $this->travel(2)->seconds();
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Primeira resposta']);
        $this->travel(2)->seconds();

        $this->actingAs($user)
            ->postJson(route('messages.store', $conversation), ['content' => 'Quanto é 2+2?'])
            ->assertOk();

        $captured = $client->captured;

        $this->assertNotEmpty($captured);
        $this->assertEquals('system', $captured[0]['role']);
        $this->assertEquals('Primeira pergunta', $captured[1]['content']);
        $this->assertEquals('Primeira resposta', $captured[2]['content']);

        $last = end($captured);
        $this->assertEquals('user', $last['role']);
        $this->assertEquals('Quanto é 2+2?', $last['content']);
    }

    public function test_context_window_uses_latest_n_messages(): void
    {
        // Use a small window to keep the test fast and predictable
        $window = 4;
        config()->set('chatbot.context_window', $window);

        $client = $this->capturingClient();
        $this->app->instance(ChatClient::class, $client);

        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        // Create window+1 pairs so the oldest pair is pushed out by the limit
        for ($i = 1; $i <= $window + 1; $i++) {
            $conversation->messages()->create(['role' => 'user', 'content' => "Pergunta {$i}"]);
            $conversation->messages()->create(['role' => 'assistant', 'content' => "Resposta {$i}"]);
        }

        $this->actingAs($user)
            ->postJson(route('messages.store', $conversation), ['content' => 'Pergunta final'])
            ->assertOk();

        $captured = $client->captured;
        $contents = array_column($captured, 'content');

        // Oldest pair must be excluded (window limit)
        $this->assertNotContains('Pergunta 1', $contents, 'Oldest message should be excluded by context window');
        $this->assertNotContains('Resposta 1', $contents, 'Oldest response should be excluded');

        // Current message must be present and last
        $this->assertContains('Pergunta final', $contents, 'Current message must be in payload');
        $this->assertEquals('system', $captured[0]['role']);
        $this->assertEquals('Pergunta final', $captured[count($captured) - 1]['content']);
    }
}
