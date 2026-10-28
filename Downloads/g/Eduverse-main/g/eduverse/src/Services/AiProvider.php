<?php
declare(strict_types=1);

interface AiProvider
{
    public function complete(string $system, string $prompt): AiResult;
    public function name(): string;
    public function isMock(): bool;
}
