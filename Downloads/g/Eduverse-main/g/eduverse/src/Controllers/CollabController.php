<?php
declare(strict_types=1);

class CollabController
{
    public static function forum(): void
    {
        $u = require_login();
        $c = Access::requireCourse($u, qint('course'));
        $threads = Db::all('SELECT t.*, u.name AS author, (SELECT COUNT(*) FROM posts p WHERE p.thread_id = t.id) AS replies FROM threads t JOIN users u ON u.id = t.user_id WHERE t.course_id = ? ORDER BY t.id DESC', [$c['id']]);
        view('forum/index', ['course' => $c, 'threads' => $threads]);
    }

    public static function thread(): void
    {
        $u = require_login();
        $t = Db::one('SELECT t.*, u.name AS author FROM threads t JOIN users u ON u.id = t.user_id WHERE t.id = ?', [qint('id')]);
        if (!$t) abort(404, 'Thread not found.');
        $c = Access::requireCourse($u, (int)$t['course_id']);
        $posts = Db::all('SELECT p.*, u.name AS author, u.role FROM posts p JOIN users u ON u.id = p.user_id WHERE p.thread_id = ? ORDER BY p.id', [$t['id']]);
        $draft = (string)($_SESSION['draft_' . $t['id']] ?? '');
        unset($_SESSION['draft_' . $t['id']]);
        view('forum/thread', ['course' => $c, 'thread' => $t, 'posts' => $posts, 'draft' => $draft]);
    }

    public static function threadCreate(): void
    {
        $u = require_login();
        $c = Access::requireCourse($u, pint('course_id'));
        $title = pstr('title');
        $body = pstr('body');
        if (mb_strlen($title) < 3 || mb_strlen($title) > 190 || $body === '' || mb_strlen($body) > 5000) {
            flash('error', 'A title (3-190 characters) and a message (up to 5000) are required.'); redirect('/forum?course=' . (int)$c['id']);
        }
        $tid = Db::insert('INSERT INTO threads (course_id, user_id, title) VALUES (?,?,?)', [$c['id'], $u['id'], $title]);
        Db::exec('INSERT INTO posts (thread_id, user_id, body) VALUES (?,?,?)', [$tid, $u['id'], $body]);
        redirect('/thread?id=' . $tid);
    }

    public static function postCreate(): void
    {
        $u = require_login();
        $t = Db::one('SELECT * FROM threads WHERE id = ?', [pint('thread_id')]);
        if (!$t) abort(404, 'Thread not found.');
        Access::requireCourse($u, (int)$t['course_id']);
        $body = pstr('body');
        if ($body === '' || mb_strlen($body) > 5000) { flash('error', 'Reply must be 1 to 5000 characters.'); redirect('/thread?id=' . (int)$t['id']); }
        Db::exec('INSERT INTO posts (thread_id, user_id, body) VALUES (?,?,?)', [$t['id'], $u['id'], $body]);
        redirect('/thread?id=' . (int)$t['id']);
    }

    public static function chat(): void
    {
        $u = require_login();
        $c = Access::requireCourse($u, qint('course'));
        view('chat/index', ['course' => $c]);
    }

    public static function chatPoll(): void
    {
        $u = require_login();
        $c = Access::requireCourse($u, qint('course'));
        $rows = Db::all('SELECT m.id, m.body, m.created_at, u.name FROM chat_messages m JOIN users u ON u.id = m.user_id WHERE m.course_id = ? AND m.id > ? ORDER BY m.id DESC LIMIT 50', [$c['id'], qint('after')]);
        json_out(['messages' => array_reverse($rows)]);
    }

    public static function chatSend(): void
    {
        $u = require_login();
        $c = Access::requireCourse($u, pint('course_id'));
        $body = pstr('body');
        if ($body === '' || mb_strlen($body) > 500) json_out(['error' => 'Message must be 1 to 500 characters.'], 422);
        Db::exec('INSERT INTO chat_messages (course_id, user_id, body) VALUES (?,?,?)', [$c['id'], $u['id'], $body]);
        json_out(['ok' => true]);
    }
}
