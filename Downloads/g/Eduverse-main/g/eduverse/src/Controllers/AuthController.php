<?php
declare(strict_types=1);

class AuthController
{
    public static function loginForm(): void { if (current_user()) redirect('/'); view('auth/login'); }

    public static function login(): void
    {
        $email = pstr('email');
        if (Auth::isLocked($email)) { flash('error', 'Too many failed attempts. Try again in 15 minutes.'); redirect('/login'); }
        $u = Auth::attempt($email, (string)($_POST['password'] ?? ''));
        if (!$u) { flash('error', 'Invalid email or password, or the account is disabled.'); redirect('/login'); }
        Auth::login($u);
        redirect('/');
    }

    public static function registerForm(): void { if (current_user()) redirect('/'); view('auth/register'); }

    public static function register(): void
    {
        $pw = (string)($_POST['password'] ?? '');
        if ($pw !== (string)($_POST['confirm'] ?? '')) { flash('error', 'Passwords do not match.'); redirect('/register'); }
        $r = Auth::register(pstr('name'), pstr('email'), $pw, 'student', pstr('department'));
        if (!$r['ok']) { foreach ($r['errors'] as $e) flash('error', $e); redirect('/register'); }
        Auth::login(['id' => $r['id']]);
        flash('ok', 'Welcome to EduVerse.');
        redirect('/');
    }

    public static function logout(): void { Auth::logout(); redirect('/login'); }

    public static function home(): void
    {
        $u = require_login();
        $d = ['notes' => Db::all('SELECT message, created_at FROM notifications WHERE user_id = ? ORDER BY id DESC LIMIT 5', [$u['id']])];
        if ($u['role'] === 'student') {
            $d['courses'] = Db::all('SELECT c.* FROM enrollments e JOIN courses c ON c.id = e.course_id WHERE e.student_id = ? ORDER BY c.title', [$u['id']]);
            $lessons = Db::all('SELECT l.id, l.title, c.title AS course_title, c.id AS course_id,
                       (SELECT COUNT(*) FROM exercises x WHERE x.lesson_id = l.id) AS exercise_count
                       FROM lessons l JOIN courses c ON c.id = l.course_id JOIN enrollments e ON e.course_id = c.id WHERE e.student_id = ?', [$u['id']]);
            $latest = [];
            foreach (Db::all('SELECT lesson_id, score, num_items FROM attempts WHERE student_id = ? ORDER BY id', [$u['id']]) as $a) {
                $latest[(int)$a['lesson_id']] = pct((int)$a['score'], (int)$a['num_items']);
            }
            $d['recs'] = array_slice(Recommender::forStudent($lessons, $latest), 0, 5);
            $d['sessions'] = Db::all('SELECT s.*, c.title AS course_title FROM class_sessions s JOIN courses c ON c.id = s.course_id
                       JOIN enrollments e ON e.course_id = c.id WHERE e.student_id = ? AND s.starts_at >= ? ORDER BY s.starts_at LIMIT 5', [$u['id'], date('Y-m-d H:i:s', time() - 3600)]);
        } elseif ($u['role'] === 'lecturer') {
            $d['courses'] = Db::all('SELECT c.*, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS students FROM courses c WHERE lecturer_id = ? ORDER BY title', [$u['id']]);
            $d['ungraded'] = (int)Db::val('SELECT COUNT(*) FROM submissions s JOIN assignments a ON a.id = s.assignment_id JOIN courses c ON c.id = a.course_id WHERE c.lecturer_id = ? AND s.lecturer_grade IS NULL', [$u['id']]);
        } else {
            $d['counts'] = Analytics::platformCounts();
        }
        view('dashboard', $d);
    }
}
