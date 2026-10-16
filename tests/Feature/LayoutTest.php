<?php

namespace Tests\Feature;

use App\Models\User;
use DOMDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    /** Both desktop toggle buttons must be in their respective headers on every render. */
    public function test_sidebar_buttons_are_rendered_in_their_respective_headers(): void
    {
        $user = User::factory()->create();

        $html = $this->actingAs($user)
            ->get(route('conversations.index'))
            ->assertOk()
            ->getContent();

        $this->assertSidebarButtons($html);
    }

    /** The same controls remain present when a conversation is open. */
    public function test_sidebar_buttons_are_present_on_conversation_page(): void
    {
        $user = User::factory()->create();
        $conversation = $user->conversations()->create(['title' => 'Test']);

        $html = $this->actingAs($user)
            ->get(route('conversations.show', $conversation))
            ->assertOk()
            ->getContent();

        $this->assertSidebarButtons($html);
    }

    public function test_composer_shares_the_message_column_in_empty_and_populated_conversations(): void
    {
        $user = User::factory()->create();
        $conversation = $user->conversations()->create(['title' => 'Test']);

        foreach ([false, true] as $populated) {
            if ($populated) {
                $conversation->messages()->create(['role' => 'user', 'content' => 'Olá']);
            }

            $html = $this->actingAs($user)
                ->get(route('conversations.show', $conversation))
                ->assertOk()
                ->getContent();

            $document = new DOMDocument;
            $previous = libxml_use_internal_errors(true);
            $document->loadHTML($html);
            libxml_clear_errors();
            libxml_use_internal_errors($previous);

            $column = $document->getElementById('conversation-column');
            $messageList = $document->getElementById('message-list');
            $suggestions = $document->getElementById('empty-suggestions');
            $composer = $document->getElementById('message-composer');

            $this->assertNotNull($column);
            $this->assertSame($column, $messageList->parentNode);
            $this->assertSame($column, $suggestions->parentNode);
            $this->assertSame($column, $composer->parentNode);
            $this->assertSame(1, $composer->getElementsByTagName('textarea')->length);
            $this->assertSame(1, $composer->getElementsByTagName('form')->length);
            $this->assertStringNotContainsString('border-t', $composer->getAttribute('class'));
        }
    }

    private function assertSidebarButtons(string $html): void
    {
        $this->assertSame(1, preg_match('/<aside\b[^>]*id="main-sidebar"[^>]*>(.*?)<\/aside>/s', $html, $sidebar));
        $this->assertSame(1, preg_match('/<main\b[^>]*>\s*<header\b[^>]*>(.*?)<\/header>/s', $html, $mainHeader));

        $this->assertMatchesRegularExpression(
            '/<button\b(?=[^>]*x-ref="sidebarCollapseButton")(?=[^>]*aria-controls="main-sidebar")[^>]*>/s',
            $sidebar[1],
        );
        $this->assertMatchesRegularExpression(
            '/<button\b(?=[^>]*x-ref="headerSidebarButton")(?=[^>]*aria-controls="main-sidebar")[^>]*>/s',
            $mainHeader[1],
        );
    }
}
