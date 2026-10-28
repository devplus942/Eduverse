<?php
// AI service layer tests using a fake transport. No network and no API key are used.

function hf(callable $t): HuggingFaceProvider { return new HuggingFaceProvider('test-token', 'test-model', 'https://example.invalid/v1', $t); }
$okBody = json_encode(['choices' => [['message' => ['content' => '  Mitochondria make ATP.  ']]]]);

test('HF provider: success parses text and sends auth header + model', function () use ($okBody) {
    $seen = null;
    $r = hf(function ($url, $headers, $json) use (&$seen, $okBody) { $seen = [$headers, $json]; return [200, $okBody, null]; })->complete('sys', 'q');
    ok($r->ok); eq('Mitochondria make ATP.', $r->text); eq(false, $r->isMock);
    ok(in_array('Authorization: Bearer test-token', $seen[0], true));
    eq('test-model', json_decode($seen[1], true)['model']);
});
test('HF provider: 429 -> rate_limited', fn() => eq('rate_limited', hf(fn() => [429, '', null])->complete('s', 'q')->status));
test('HF provider: 401 -> auth_error', fn() => eq('auth_error', hf(fn() => [401, '', null])->complete('s', 'q')->status));
test('HF provider: 503 -> unavailable', fn() => eq('unavailable', hf(fn() => [503, '', null])->complete('s', 'q')->status));
test('HF provider: network failure -> unavailable', fn() => eq('unavailable', hf(fn() => [0, '', 'timeout'])->complete('s', 'q')->status));
test('HF provider: malformed JSON -> error, not ok', function () {
    $r = hf(fn() => [200, 'not json', null])->complete('s', 'q'); eq(false, $r->ok); eq('error', $r->status);
});
test('HF provider: empty content -> error', function () {
    $r = hf(fn() => [200, json_encode(['choices' => [['message' => ['content' => '']]]]), null])->complete('s', 'q'); eq(false, $r->ok);
});
test('Failure messages are friendly and never leak the token', function () {
    foreach (['rate_limited', 'local_rate_limit', 'auth_error', 'unavailable', 'error'] as $st) {
        $m = (new AiResult(false, '', 'huggingface', false, $st, 'secret-detail'))->userMessage();
        ok($m !== '' && !str_contains($m, 'secret') && !str_contains($m, 'test-token'));
    }
});
test('Mock provider output is clearly labelled and flagged', function () {
    $r = (new MockProvider())->complete('s', 'What is DNA?');
    ok(str_starts_with($r->text, MockProvider::LABEL)); eq(true, $r->isMock); eq('mock', $r->provider);
});
test('AiService::provider uses mock without token/model, Hugging Face with both', function () {
    Env::set('AI_MODE', 'auto'); Env::set('HF_API_TOKEN', ''); Env::set('HF_MODEL', '');
    ok(AiService::provider()->isMock());
    Env::set('HF_API_TOKEN', 'x'); Env::set('HF_MODEL', 'm');
    ok(!AiService::provider()->isMock());
    Env::set('AI_MODE', 'mock');
    ok(AiService::provider()->isMock());
    Env::set('AI_MODE', 'auto'); Env::set('HF_API_TOKEN', ''); Env::set('HF_MODEL', '');
});
