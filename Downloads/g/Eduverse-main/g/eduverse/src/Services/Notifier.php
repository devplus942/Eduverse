<?php
declare(strict_types=1);

class Notifier
{
    public static function notify(int $userId, string $message): void
    {
        Db::exec('INSERT INTO notifications (user_id, message) VALUES (?, ?)', [$userId, mb_substr($message, 0, 255)]);
    }

    /** Development mail transport: writes to storage/logs/mail.log. SMTP (PHPMailer) is not implemented yet. */
    public static function email(string $to, string $subject, string $body): void
    {
        $line = sprintf("[%s] MAIL_MODE=log To:%s Subject:%s\n%s\n\n", date('c'), $to, $subject, $body);
        @file_put_contents(ROOT . '/storage/logs/mail.log', $line, FILE_APPEND);
    }

    public static function notifyCourse(int $courseId, string $message, string $subject): void
    {
        foreach (Db::all('SELECT u.id, u.email FROM enrollments e JOIN users u ON u.id = e.student_id WHERE e.course_id = ?', [$courseId]) as $s) {
            self::notify((int)$s['id'], $message);
            self::email($s['email'], $subject, $message);
        }
    }
}
