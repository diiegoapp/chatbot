<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_root_redirects_to_conversations(): void
    {
        $this->get('/')
            ->assertRedirect(route('conversations.index'));
    }
}
