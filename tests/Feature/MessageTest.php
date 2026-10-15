<?php

namespace Tests\Feature;

use App\Exceptions\AiRateLimited;
use App\Exceptions\AiUnavailable;
use App\Models\Conversation;
use App\Models\User;
use App\Services\Ai\ChatClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MessageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Bind a fake ChatClient that returns a predictable answer
        $this->app->bind(ChatClient::class, fn () => new class implements ChatClient
        {
            public function chat(array $messages): string
            {
                return 'Resposta da IA';
            }

            public function streamChat(array $messages, callable $onChunk): void
            {
                $onChunk('Resposta da IA');
            }
        });
    }

    public function test_user_can_send_message_and_gets_ai_reply(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)
            ->postJson(route('messages.store', $conversation), ['content' => 'Olá!']);

        $response->assertOk()
            ->assertJsonStructure(['id', 'content', 'html']);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'user',
            'content' => 'Olá!',
        ]);

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Resposta da IA',
        ]);
    }

    public function test_user_cannot_send_message_to_other_users_conversation(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($attacker)
            ->postJson(route('messages.store', $conversation), ['content' => 'Hack!'])
            ->assertForbidden();
    }

    public function test_empty_message_is_rejected(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson(route('messages.store', $conversation), ['content' => ''])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');
    }

    public function test_message_too_long_is_rejected(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson(route('messages.store', $conversation), ['content' => str_repeat('a', 4001)])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('content');
    }

    public function test_first_message_sets_conversation_title(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id, 'title' => null]);

        $this->actingAs($user)
            ->postJson(route('messages.store', $conversation), ['content' => 'Qual é a capital do Brasil?']);

        $this->assertNotNull($conversation->fresh()->title);
    }

    public function test_first_message_response_includes_title_without_trailing_punctuation(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id, 'title' => null]);

        $this->actingAs($user)
            ->postJson(route('messages.store', $conversation), ['content' => 'Responda apenas com a palavra banana.'])
            ->assertOk()
            ->assertJsonPath('title', 'Responda apenas com a palavra banana');
    }

    public function test_subsequent_message_response_has_null_title(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id, 'title' => 'Título existente']);

        $this->actingAs($user)
            ->postJson(route('messages.store', $conversation), ['content' => 'Segunda pergunta'])
            ->assertOk()
            ->assertJsonPath('title', null);
    }

    public function test_ai_unavailable_returns_502_and_does_not_persist_user_message(): void
    {
        $this->app->bind(ChatClient::class, fn () => new class implements ChatClient
        {
            public function chat(array $messages): string
            {
                throw new AiUnavailable;
            }

            public function streamChat(array $messages, callable $onChunk): void {}
        });

        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson(route('messages.store', $conversation), ['content' => 'Olá!'])
            ->assertStatus(502)
            ->assertJsonStructure(['error']);

        $this->assertDatabaseMissing('messages', ['conversation_id' => $conversation->id]);
    }

    public function test_ai_rate_limited_returns_429_and_does_not_persist_user_message(): void
    {
        $this->app->bind(ChatClient::class, fn () => new class implements ChatClient
        {
            public function chat(array $messages): string
            {
                throw new AiRateLimited;
            }

            public function streamChat(array $messages, callable $onChunk): void {}
        });

        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson(route('messages.store', $conversation), ['content' => 'Olá!'])
            ->assertStatus(429)
            ->assertJsonStructure(['error']);

        $this->assertDatabaseMissing('messages', ['conversation_id' => $conversation->id]);
    }
}
