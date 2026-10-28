<?php
declare(strict_types=1);

class AiService
{
    public const TUTOR_SYSTEM = 'You are a patient study tutor for university students. Explain clearly and concisely. If you are unsure, say so. Do not invent citations.';
    public const GRADER_SYSTEM = 'You are a teaching assistant giving advisory feedback on a student answer. Give 3 short strengths/weaknesses and a suggested score out of 100. A human lecturer makes the final grade.';

    public static function provider(): AiProvider
    {
        $mode = Env::get('AI_MODE', 'auto');
        $token = (string)Env::get('HF_API_TOKEN', '');
        $model = (string)Env::get('HF_MODEL', '');
        if ($mode !== 'mock' && $token !== '' && $model !== '') {
            return new HuggingFaceProvider($token, $model, (string)Env::get('HF_ENDPOINT', 'https://router.huggingface.co/v1/chat/completions'));
        }
        return new MockProvider();
    }

    public static function withinLimit(int $userId): bool
    {
        $limit = (int)Env::get('AI_RATE_LIMIT_PER_HOUR', '20');
        $n = (int)Db::val("SELECT COUNT(*) FROM ai_interactions WHERE user_id = ? AND status <> 'local_rate_limit' AND created_at > (NOW() - INTERVAL 1 HOUR)", [$userId]);
        return $n < $limit;
    }

    public static function ask(int $userId, string $kind, string $system, string $prompt, ?AiProvider $provider = null): AiResult
    {
        $provider ??= self::provider();
        $prompt = mb_substr(trim($prompt), 0, 4000);
        if (!self::withinLimit($userId)) {
            $r = new AiResult(false, '', $provider->name(), $provider->isMock(), 'local_rate_limit', 'hourly limit reached');
        } else {
            try {
                $r = $provider->complete($system, $prompt);
            } catch (Throwable $e) {
                $r = new AiResult(false, '', $provider->name(), $provider->isMock(), 'error', $e->getMessage());
            }
        }
        Db::exec('INSERT INTO ai_interactions (user_id, kind, prompt, response, provider, is_mock, status) VALUES (?,?,?,?,?,?,?)',
            [$userId, $kind, $prompt, $r->ok ? $r->text : null, $r->provider, (int)$r->isMock, $r->status]);
        return $r;
    }
}
