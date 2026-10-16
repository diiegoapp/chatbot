<?php

namespace Tests\Unit;

use App\Exceptions\AiBadResponse;
use App\Exceptions\AiRateLimited;
use App\Exceptions\AiUnavailable;
use App\Services\Ai\ZaiClient;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ZaiClientTest extends TestCase
{
    private function successBody(string $content = 'Olá!'): array
    {
        return [
            'choices' => [
                ['message' => ['content' => $content, 'role' => 'assistant']],
            ],
        ];
    }

    public function test_returns_content_on_success(): void
    {
        Http::fake([
            '*' => Http::response($this->successBody('ok'), 200),
        ]);

        $client = new ZaiClient;
        $result = $client->chat([['role' => 'user', 'content' => 'ping']]);

        $this->assertSame('ok', $result);
    }

    public function test_reasoning_content_is_never_returned(): void
    {
        Http::fake([
            '*' => Http::response(array_merge_recursive(
                $this->successBody('resposta'),
                ['choices' => [['message' => ['reasoning_content' => 'raciocínio interno']]]],
            ), 200),
        ]);

        $client = new ZaiClient;
        $result = $client->chat([['role' => 'user', 'content' => 'pense']]);

        $this->assertSame('resposta', $result);
        $this->assertStringNotContainsString('raciocínio interno', $result);
    }

    public function test_throws_ai_rate_limited_on_429(): void
    {
        Http::fake([
            '*' => Http::response(['error' => ['message' => 'rate limited']], 429),
        ]);

        $this->expectException(AiRateLimited::class);

        (new ZaiClient)->chat([['role' => 'user', 'content' => 'x']]);
    }

    public function test_throws_ai_unavailable_on_server_error(): void
    {
        Http::fake([
            '*' => Http::response([], 500),
        ]);

        $this->expectException(AiUnavailable::class);

        (new ZaiClient)->chat([['role' => 'user', 'content' => 'x']]);
    }

    public function test_throws_ai_bad_response_on_malformed_json(): void
    {
        Http::fake([
            '*' => Http::response(['unexpected' => 'format'], 200),
        ]);

        $this->expectException(AiBadResponse::class);

        (new ZaiClient)->chat([['role' => 'user', 'content' => 'x']]);
    }

    public function test_throws_ai_unavailable_on_connection_error(): void
    {
        Http::fake([
            '*' => function () {
                throw new ConnectionException('Connection refused');
            },
        ]);

        $this->expectException(AiUnavailable::class);

        (new ZaiClient)->chat([['role' => 'user', 'content' => 'x']]);
    }
}
