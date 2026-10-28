<?php
declare(strict_types=1);

class Access
{
    public static function course(int $id): ?array { return Db::one('SELECT * FROM courses WHERE id = ?', [$id]); }

    public static function isEnrolled(int $courseId, int $studentId): bool
    {
        return (int)Db::val('SELECT COUNT(*) FROM enrollments WHERE course_id = ? AND student_id = ?', [$courseId, $studentId]) > 0;
    }

    public static function ownsCourse(array $user, array $course): bool
    {
        return $user['role'] === 'admin' || ($user['role'] === 'lecturer' && (int)$course['lecturer_id'] === (int)$user['id']);
    }

    public static function canViewCourse(array $user, array $course): bool
    {
        if (self::ownsCourse($user, $course)) return true;
        return $user['role'] === 'student' && self::isEnrolled((int)$course['id'], (int)$user['id']);
    }

    /** Load a course and enforce view access, or abort 404/403. */
    public static function requireCourse(array $user, int $id, bool $manage = false): array
    {
        $c = self::course($id);
        if (!$c) abort(404, 'Course not found.');
        $ok = $manage ? self::ownsCourse($user, $c) : self::canViewCourse($user, $c);
        if (!$ok) abort(403, 'You do not have access to this course.');
        return $c;
    }
}
