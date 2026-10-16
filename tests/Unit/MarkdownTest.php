<?php

namespace Tests\Unit;

use App\Support\Markdown;
use Tests\TestCase;

class MarkdownTest extends TestCase
{
    public function test_bold_markdown_renders_as_html(): void
    {
        $html = Markdown::toHtml('**negrito**');
        $this->assertStringContainsString('<strong>negrito</strong>', $html);
    }

    public function test_raw_html_is_stripped(): void
    {
        $html = Markdown::toHtml('<script>alert("xss")</script>');
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('alert', $html);
    }

    public function test_inline_event_handlers_are_stripped(): void
    {
        $html = Markdown::toHtml('<img src=x onerror="alert(1)">');
        $this->assertStringNotContainsString('onerror', $html);
    }

    public function test_unsafe_links_are_stripped(): void
    {
        $html = Markdown::toHtml('[clique](javascript:alert(1))');
        $this->assertStringNotContainsString('javascript:', $html);
    }
}
