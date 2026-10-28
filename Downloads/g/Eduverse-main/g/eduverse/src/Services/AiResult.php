<?php
declare(strict_types=1);

class AiResult
{
    public function __construct(
        public bool $ok,
        public string $text,
        public string $provider,
        public bool $isMock,
        public string $status = 'ok',      // ok | rate_limited | auth_error | unavailable | error | local_rate_limit
        public ?string $error = null
    ) {}

    /** Message safe to show to end users when ok is false. */
    public function userMessage(): string
    {
        return match ($this->status) {
            'rate_limited' => 'The AI provider is rate-limiting requests. Please wait a minute and try again.',
            'local_rate_limit' => 'You have reached the hourly AI request limit. Please try again later.',
            'auth_error' => 'The AI service is not configured correctly. Please contact the administrator.',
            'unavailable' => 'The AI service is currently unavailable. Please try again later.',
            default => 'The AI service could not produce an answer. Please try again.',
        };
    }
}
