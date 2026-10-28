<?php
declare(strict_types=1);

class Grader
{
    /**
     * Auto-grade multiple-choice answers.
     * @param array $exercises rows with 'id' and 'answer' (A-D)
     * @param array $chosen    map exercise_id => chosen letter
     */
    public static function mcq(array $exercises, array $chosen): array
    {
        $score = 0;
        $rows = [];
        foreach ($exercises as $ex) {
            $c = strtoupper(trim((string)($chosen[$ex['id']] ?? '')));
            $valid = in_array($c, ['A', 'B', 'C', 'D'], true);
            $ok = $valid && $c === $ex['answer'];
            if ($ok) $score++;
            $rows[] = ['exercise_id' => (int)$ex['id'], 'chosen' => $valid ? $c : null, 'is_correct' => $ok];
        }
        return ['score' => $score, 'total' => count($exercises), 'rows' => $rows];
    }
}
