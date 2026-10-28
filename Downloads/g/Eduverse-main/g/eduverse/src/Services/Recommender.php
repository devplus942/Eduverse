<?php
declare(strict_types=1);

/** Rule-based study recommendations. This is not a machine-learning model. */
class Recommender
{
    /**
     * @param array $lessons rows: id, title, exercise_count
     * @param array $latestPct map lesson_id => latest quiz percentage
     */
    public static function forStudent(array $lessons, array $latestPct, float $threshold = 60.0): array
    {
        $weak = [];
        $new = [];
        foreach ($lessons as $l) {
            if ((int)$l['exercise_count'] === 0) continue;
            $id = (int)$l['id'];
            if (isset($latestPct[$id])) {
                if ($latestPct[$id] < $threshold) $weak[] = $l + ['pct' => $latestPct[$id], 'reason' => 'Latest quiz score ' . $latestPct[$id] . '%. Review and retry.'];
            } else {
                $new[] = $l + ['pct' => null, 'reason' => 'Quiz not attempted yet.'];
            }
        }
        usort($weak, fn($a, $b) => $a['pct'] <=> $b['pct']);
        return array_merge($weak, $new);
    }
}
