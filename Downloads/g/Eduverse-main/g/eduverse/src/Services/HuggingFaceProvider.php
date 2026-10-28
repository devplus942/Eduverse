<?php
declare(strict_types=1);

/**
 * Calls an OpenAI-compatible chat-completions endpoint (default: Hugging Face router).
 * $transport is injectable for tests: fn(string $url, array $headers, string $json): array{0:int,1:string,2:?string}
 * returning [httpStatus, body, transportError].
 */
class HuggingFaceProvider implements AiProvider
{
    public function __construct(
        private string $token,
        private string $model,
        private string $endpoint,
        private $transport = null,
        private int $timeout = 30
    ) {}

    public function name(): string { return 'huggingface'; }
    public function isMock(): bool { return false; }

    public function complete(string $system, string $prompt): AiResult
    {
        $payload = json_encode([
            'model' => $this->model,
            'messages' => [['role' => 'system', 'content' => $system], ['role' => 'user', 'content' => $prompt]],
            'max_tokens' => 512,
        ]);
        $headers = ['Authorization: Bearer ' . $this->token, 'Content-Type: application/json'];
        [$status, $body, $err] = ($this->transport ?? [$this, 'curl'])($this->endpoint, $headers, $payload);

        if ($err !== null || $status === 0) return $this->fail('unavailable', $err ?? 'no response');
        if ($status === 429) return $this->fail('rate_limited', 'HTTP 429');
        if ($status === 401 || $status === 403) return $this->fail('auth_error', 'HTTP ' . $status);
        if ($status >= 500) return $this->fail('unavailable', 'HTTP ' . $status);
        if ($status !== 200) return $this->fail('error', 'HTTP ' . $status);

        $data = json_decode($body, true);
        $text = is_array($data) ? ($data['choices'][0]['message']['content'] ?? null) : null;
        if (!is_string($text) || trim($text) === '') return $this->fail('error', 'empty or malformed response');
        return new AiResult(true, trim($text), 'huggingface', false);
    }

    private function fail(string $status, string $detail): AiResult
    {
        return new AiResult(false, '', 'huggingface', false, $status, $detail);
    }

    private function curl(string $url, array $headers, string $json): array
    {
        if (!function_exists('curl_init')) return [0, '', 'cURL extension is not enabled'];
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true, CURLOPT_POSTFIELDS => $json, CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => $this->timeout, CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $body = curl_exec($ch);
        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = $body === false ? curl_error($ch) : null;
        curl_close($ch);
        return [$status, (string)$body, $err];
    }
}
