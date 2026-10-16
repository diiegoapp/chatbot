<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Services\Ai\ChatClient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegenerateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->app->bind(ChatClient::class, fn () => new class implements ChatClient
        {
            public function chat(array $messages): string
            {
                return 'Nova resposta';
            }

            public function streamChat(array $messages, callable $onChunk): void
            {
                $onChunk('Nova resposta');
            }
        });
    }

    public function test_user_can_regenerate_last_assistant_message(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Pergunta']);
        $old = $conversation->messages()->create(['role' => 'assistant', 'content' => 'Resposta antiga']);

        $this->actingAs($user)
            ->postJson(route('messages.regenerate', $conversation))
            ->assertOk()
            ->assertJsonStructure(['id', 'content', 'html']);

        $this->assertDatabaseMissing('messages', ['id' => $old->id]);
        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'role' => 'assistant',
            'content' => 'Nova resposta',
        ]);
    }

    public function test_other_user_cannot_regenerate(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $owner->id]);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Pergunta']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Resposta']);

        $this->actingAs($attacker)
            ->postJson(route('messages.regenerate', $conversation))
            ->assertForbidden();
    }

    public function test_regenerate_returns_422_when_last_message_is_not_assistant(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Pergunta sem resposta']);

        $this->actingAs($user)
            ->postJson(route('messages.regenerate', $conversation))
            ->assertUnprocessable()
            ->assertJsonStructure(['error']);
    }

    public function test_regenerate_returns_422_when_conversation_has_no_messages(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->postJson(route('messages.regenerate', $conversation))
            ->assertUnprocessable();
    }

    public function test_regenerate_payload_does_not_contain_deleted_message(): void
    {
        $client = new class implements ChatClient
        {
            public array $captured = [];

            public function chat(array $messages): string
            {
                $this->captured = $messages;

                return 'Nova resposta';
            }

            public function streamChat(array $messages, callable $onChunk): void {}
        };
        $this->app->instance(ChatClient::class, $client);

        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Pergunta']);
        $conversation->messages()->create(['role' => 'assistant', 'content' => 'Resposta antiga que deve ser apagada']);

        $this->actingAs($user)
            ->postJson(route('messages.regenerate', $conversation))
            ->assertOk();

        $contents = array_column($client->captured, 'content');
        $this->assertNotContains('Resposta antiga que deve ser apagada', $contents);
        $this->assertContains('Pergunta', $contents);
    }
}
