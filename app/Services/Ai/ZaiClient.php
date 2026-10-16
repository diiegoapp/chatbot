<?php

namespace App\Services\Ai;

use App\Exceptions\AiBadResponse;
use App\Exceptions\AiRateLimited;
use App\Exceptions\AiUnavailable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZaiClient implements ChatClient
{
    private string $baseUrl;

    private string $model;

    private int $timeout;

    private int $connectTimeout;

    private int $maxTokens;

    private string $thinking;

    public function __construct()
    {
        $this->baseUrl = config('services.zai.base_url');
        $this->model = config('services.zai.model');
        $this->timeout = config('services.zai.timeout');
        $this->connectTimeout = config('services.zai.connect_timeout', 5);
        $this->maxTokens = config('services.zai.max_tokens');
        $this->thinking = config('services.zai.thinking');
    }

    public function chat(array $messages): string
    {
        try {
            $response = Http::withToken(config('services.zai.api_key'))
                ->baseUrl($this->baseUrl)
                ->timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->retry(
                    2,
                    500,
                    fn (\Throwable $e) => $e instanceof ConnectionException
                        || ($e instanceof RequestException && $e->response->serverError()),
                    throw: false
                )
                ->post('/chat/completions', [
                    'model' => $this->model,
                    'messages' => $messages,
                    'max_tokens' => $this->maxTokens,
                    'thinking' => ['type' => $this->thinking],
                ]);
        } catch (ConnectionException $e) {
            Log::warning('ZaiClient connection error', ['error' => $e->getMessage()]);
            throw new AiUnavailable;
        }

        if ($response->status() === 429) {
            Log::warning('ZaiClient rate limited');
            throw new AiRateLimited;
        }

        if ($response->serverError() || $response->failed()) {
            Log::warning('ZaiClient server error', ['status' => $response->status()]);
            throw new AiUnavailable;
        }

        $data = $response->json();

        if (! isset($data['choices'][0]['message']['content'])) {
            Log::warning('ZaiClient bad response structure');
            throw new AiBadResponse;
        }

        // Deliberately ignore reasoning_content — never expose it to users
        return $data['choices'][0]['message']['content'];
    }

    public function streamChat(array $messages, callable $onChunk): void
    {
        $body = json_encode([
            'model' => $this->model,
            'messages' => $messages,
            'max_tokens' => $this->maxTokens,
            'stream' => true,
            'thinking' => ['type' => $this->thinking],
        ]);

        $ch = curl_init("{$this->baseUrl}/chat/completions");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => false,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer '.config('services.zai.api_key'),
            ],
            CURLOPT_WRITEFUNCTION => function ($ch, $data) use ($onChunk) {
                foreach (explode("\n", $data) as $line) {
                    $line = trim($line);
                    if (! str_starts_with($line, 'data:')) {
                        continue;
                    }
                    $json = trim(substr($line, 5));
                    if ($json === '[DONE]') {
                        continue;
                    }
                    $chunk = json_decode($json, true);
                    $delta = $chunk['choices'][0]['delta']['content'] ?? null;
                    if ($delta !== null) {
                        $onChunk($delta);
                    }
                }

                return strlen($data);
            },
        ]);

        $error = null;
        if (! curl_exec($ch)) {
            $error = curl_error($ch);
        }
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($error) {
            Log::warning('ZaiClient stream connection error', ['error' => $error]);
            throw new AiUnavailable;
        }

        if ($httpCode === 429) {
            throw new AiRateLimited;
        }

        if ($httpCode >= 500) {
            Log::warning('ZaiClient stream server error', ['status' => $httpCode]);
            throw new AiUnavailable;
        }
    }
}
