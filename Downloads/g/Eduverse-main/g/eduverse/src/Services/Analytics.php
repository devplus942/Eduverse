<?php
declare(strict_types=1);

/** Rule-based learning analytics computed from stored quiz and submission data. */
class Analytics
{
    public const STRUGGLING_BELOW = 50.0;

    public static function courseStudents(int $courseId): array
    {
        return Db::all(
            'SELECT u.id, u.name, u.email,
                    (SELECT COUNT(*) FROM attempts a JOIN lessons l ON l.id = a.lesson_id WHERE a.student_id = u.id AND l.course_id = e.course_id) AS attempts,
                    (SELECT COALESCE(SUM(a.score),0) FROM attempts a JOIN lessons l ON l.id = a.lesson_id WHERE a.student_id = u.id AND l.course_id = e.course_id) AS score_sum,
                    (SELECT COALESCE(SUM(a.num_items),0) FROM attempts a JOIN lessons l ON l.id = a.lesson_id WHERE a.student_id = u.id AND l.course_id = e.course_id) AS item_sum,
                    (SELECT COUNT(*) FROM submissions s JOIN assignments x ON x.id = s.assignment_id WHERE s.student_id = u.id AND x.course_id = e.course_id) AS submissions
             FROM enrollments e JOIN users u ON u.id = e.student_id WHERE e.course_id = ? ORDER BY u.name', [$courseId]);
    }

    public static function withAverages(array $rows): array
    {
        foreach ($rows as &$r) {
            $r['avg'] = (int)$r['attempts'] > 0 ? pct((int)$r['score_sum'], (int)$r['item_sum']) : null;
            $r['struggling'] = $r['avg'] !== null && $r['avg'] < self::STRUGGLING_BELOW;
        }
        return $rows;
    }

    public static function mostMissed(int $courseId, int $limit = 5): array
    {
        $limit = max(1, $limit);
        return Db::all(
            'SELECT x.id, x.question, l.title AS lesson, COUNT(*) AS answered, SUM(1 - aa.is_correct) AS missed
             FROM attempt_answers aa JOIN exercises x ON x.id = aa.exercise_id JOIN lessons l ON l.id = x.lesson_id
             WHERE l.course_id = ? GROUP BY x.id, x.question, l.title HAVING missed > 0 ORDER BY missed DESC LIMIT ' . $limit, [$courseId]);
    }

    public static function platformCounts(): array
    {
        return [
            'students' => (int)Db::val("SELECT COUNT(*) FROM users WHERE role='student'"),
            'lecturers' => (int)Db::val("SELECT COUNT(*) FROM users WHERE role='lecturer'"),
            'courses' => (int)Db::val('SELECT COUNT(*) FROM courses'),
            'attempts' => (int)Db::val('SELECT COUNT(*) FROM attempts'),
            'submissions' => (int)Db::val('SELECT COUNT(*) FROM submissions'),
            'ai_requests' => (int)Db::val('SELECT COUNT(*) FROM ai_interactions'),
        ];
    }

    /** CSV-safe cell: neutralises spreadsheet formula injection. */
    public static function csvCell($v): string
    {
        $s = (string)$v;
        return ($s !== '' && in_array($s[0], ['=', '+', '-', '@'], true)) ? "'" . $s : $s;
    }
}
