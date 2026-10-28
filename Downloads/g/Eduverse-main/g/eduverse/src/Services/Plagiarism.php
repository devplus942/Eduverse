<?php
declare(strict_types=1);

/** Local text-overlap check between student submissions. Not a substitute for commercial plagiarism services. */
class Plagiarism
{
    public static function tokens(string $text): array
    {
        $t = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', mb_strtolower($text));
        return preg_split('/\s+/u', trim((string)$t), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    public static function shingles(string $text, int $k = 3): array
    {
        $w = self::tokens($text);
        $n = count($w);
        if ($n === 0) return [];
        if ($n < $k) return ['s:' . implode(' ', $w) => true];
        $s = [];
        for ($i = 0; $i <= $n - $k; $i++) $s['s:' . implode(' ', array_slice($w, $i, $k))] = true;
        return $s;
    }

    /** Jaccard similarity of word 3-gram sets, 0.0 to 1.0. */
    public static function similarity(string $a, string $b): float
    {
        $x = self::shingles($a);
        $y = self::shingles($b);
        if (!$x || !$y) return 0.0;
        $inter = count(array_intersect_key($x, $y));
        $union = count($x) + count($y) - $inter;
        return $union > 0 ? round($inter / $union, 4) : 0.0;
    }

    /** Compare every pair among rows with 'id' and 'text_content'; return pairs at or above threshold. */
    public static function findPairs(array $subs, float $threshold): array
    {
        $out = [];
        $n = count($subs);
        for ($i = 0; $i < $n; $i++) {
            for ($j = $i + 1; $j < $n; $j++) {
                $s = self::similarity((string)$subs[$i]['text_content'], (string)$subs[$j]['text_content']);
                if ($s >= $threshold) $out[] = ['a' => (int)$subs[$i]['id'], 'b' => (int)$subs[$j]['id'], 'similarity' => $s];
            }
        }
        return $out;
    }
}
