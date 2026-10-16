<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('conversations.index'))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_view_conversations(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('conversations.index'))
            ->assertOk();
    }

    public function test_user_can_create_conversation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('conversations.store'))
            ->assertRedirect();

        $this->assertDatabaseHas('conversations', ['user_id' => $user->id]);
    }

    public function test_user_can_view_own_conversation(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->get(route('conversations.show', $conversation))
            ->assertOk();
    }

    public function test_chat_view_does_not_break_alpine_x_data_attribute(): void
    {
        // User whose name contains a double-quote character — the worst case for x-data injection
        $user = User::factory()->create(['name' => '"Hacker"']);
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        $html = $this->actingAs($user)
            ->get(route('conversations.show', $conversation))
            ->assertOk()
            ->getContent();

        // The x-data attribute must use &quot; (HTML-encoded), never a raw double-quote
        // that would break the attribute boundary
        $this->assertStringNotContainsString('x-data="chatApp(\''.route('messages.store', $conversation).'\', false, """)', $html);
        $this->assertStringContainsString('x-data="chatApp(', $html);
    }

    public function test_user_cannot_view_other_users_conversation(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($attacker)
            ->get(route('conversations.show', $conversation))
            ->assertForbidden();
    }

    public function test_user_can_delete_own_conversation(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)
            ->delete(route('conversations.destroy', $conversation))
            ->assertRedirect(route('conversations.index'));

        $this->assertDatabaseMissing('conversations', ['id' => $conversation->id]);
    }

    public function test_user_cannot_delete_other_users_conversation(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $owner->id]);

        $this->actingAs($attacker)
            ->delete(route('conversations.destroy', $conversation))
            ->assertForbidden();
    }

    public function test_user_can_rename_own_conversation(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id, 'title' => 'Título original']);

        $this->actingAs($user)
            ->patchJson(route('conversations.update', $conversation), ['title' => 'Novo título'])
            ->assertOk()
            ->assertJsonPath('title', 'Novo título');

        $this->assertDatabaseHas('conversations', ['id' => $conversation->id, 'title' => 'Novo título']);
    }

    public function test_user_cannot_rename_other_users_conversation(): void
    {
        $owner = User::factory()->create();
        $attacker = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $owner->id, 'title' => 'Título original']);

        $this->actingAs($attacker)
            ->patchJson(route('conversations.update', $conversation), ['title' => 'Hackeado'])
            ->assertForbidden();

        $this->assertDatabaseHas('conversations', ['id' => $conversation->id, 'title' => 'Título original']);
    }

    public function test_rename_rejects_empty_title(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id, 'title' => 'Título']);

        $this->actingAs($user)
            ->patchJson(route('conversations.update', $conversation), ['title' => '   '])
            ->assertStatus(422);
    }

    public function test_nova_conversa_redirects_to_existing_empty_conversation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('conversations.store'));
        $this->assertDatabaseCount('conversations', 1);

        $this->actingAs($user)->post(route('conversations.store'));
        $this->assertDatabaseCount('conversations', 1);
    }

    public function test_nova_conversa_creates_new_after_message_was_sent(): void
    {
        $user = User::factory()->create();
        $conversation = Conversation::factory()->create(['user_id' => $user->id, 'title' => null]);
        $conversation->messages()->create(['role' => 'user', 'content' => 'Olá']);

        $this->actingAs($user)->post(route('conversations.store'));

        $this->assertDatabaseCount('conversations', 2);
    }
}
