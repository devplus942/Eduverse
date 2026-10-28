<?php
declare(strict_types=1);

/** Development-only provider. Its output is NOT AI generated and is always labelled as such. */
class MockProvider implements AiProvider
{
    public const LABEL = '[MOCK RESPONSE - not AI generated] ';

    public function name(): string { return 'mock'; }
    public function isMock(): bool { return true; }

    public function complete(string $system, string $prompt): AiResult
    {
        $snippet = mb_substr(trim($prompt), 0, 120);
        $text = self::LABEL . 'No AI provider is configured, so this is a fixed placeholder reply. '
              . 'Your input began: "' . $snippet . '". Set HF_API_TOKEN and HF_MODEL in .env to use a real model.';
        return new AiResult(true, $text, 'mock', true);
    }
}
