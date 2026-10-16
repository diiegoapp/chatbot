<?php

/**
 * Smoke test for Z.AI API.
 * Usage: php scripts/smoke-test-ai.php
 * Reads ZAI_API_KEY from .env (never prints the key).
 */
$envFile = __DIR__.'/../.env';
if (! file_exists($envFile)) {
    echo "ERROR: .env not found. Copy .env.example to .env and set ZAI_API_KEY.\n";
    exit(1);
}

$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || ! str_contains($line, '=')) {
        continue;
    }
    [$key, $value] = explode('=', $line, 2);
    $env[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
}

$key = $env['ZAI_API_KEY'] ?? '';
$baseUrl = $env['ZAI_BASE_URL'] ?? 'https://api.z.ai/api/paas/v4';
$model = $env['ZAI_MODEL'] ?? 'glm-4.7-flash';
$thinking = $env['ZAI_THINKING'] ?? 'disabled';

if (empty($key)) {
    echo "ERROR: ZAI_API_KEY is not set in .env.\n";
    exit(1);
}

$models = [$model, $model === 'glm-4.7-flash' ? 'glm-4.5-flash' : null];

foreach (array_filter($models) as $testModel) {
    echo "Testing model: {$testModel} ...\n";

    $payload = json_encode([
        'model' => $testModel,
        'messages' => [['role' => 'user', 'content' => 'Responda apenas: ok']],
        'max_tokens' => 20,
        'thinking' => ['type' => $thinking],
    ]);

    $start = microtime(true);
    $ch = curl_init("{$baseUrl}/chat/completions");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer '.$key,
        ],
    ]);

    $raw = curl_exec($ch);
    $ms = round((microtime(true) - $start) * 1000);
    $http = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr = curl_error($ch);
    curl_close($ch);

    if ($curlErr) {
        echo "  CURL ERROR: {$curlErr}\n";

        continue;
    }

    $data = json_decode($raw, true);

    if ($http === 200 && isset($data['choices'][0]['message']['content'])) {
        $content = $data['choices'][0]['message']['content'];
        $hasReasoning = isset($data['choices'][0]['message']['reasoning_content']);
        echo "  OK ({$ms}ms) | Response: {$content}\n";
        echo '  reasoning_content present: '.($hasReasoning ? 'yes (will be stripped)' : 'no')."\n";
        echo "SUCCESS: model {$testModel} works.\n";
        exit(0);
    }

    $errorCode = $data['error']['code'] ?? $http;
    $errorMsg = $data['error']['message'] ?? 'unknown error';
    echo "  FAILED (HTTP {$http}) | code={$errorCode} | {$errorMsg}\n";
}

echo "All models failed. Check your ZAI_API_KEY and account status.\n";
exit(1);
