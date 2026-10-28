<?php
declare(strict_types=1);

class AdminController
{
    public static function users(): void
    {
        require_role('admin');
        view('admin/users', ['users' => Db::all('SELECT id, name, email, role, department, is_active, created_at FROM users ORDER BY created_at DESC')]);
    }

    public static function createUser(): void
    {
        require_role('admin');
        $r = Auth::register(pstr('name'), pstr('email'), (string)($_POST['password'] ?? ''), pstr('role'), pstr('department'));
        if ($r['ok']) flash('ok', 'User created.');
        else foreach ($r['errors'] as $e) flash('error', $e);
        redirect('/admin/users');
    }

    public static function toggle(): void
    {
        $u = require_role('admin');
        $id = pint('user_id');
        if ($id === (int)$u['id']) { flash('error', 'You cannot disable your own account.'); redirect('/admin/users'); }
        Db::exec('UPDATE users SET is_active = 1 - is_active WHERE id = ?', [$id]);
        flash('ok', 'User status updated.');
        redirect('/admin/users');
    }

    public static function analytics(): void
    {
        require_role('admin');
        view('admin/analytics', ['counts' => Analytics::platformCounts(),
            'courses' => Db::all('SELECT c.id, c.code, c.title, u.name AS lecturer, (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.id) AS students FROM courses c JOIN users u ON u.id = c.lecturer_id ORDER BY c.title')]);
    }
}
