<?php
declare(strict_types=1);

class AiController
{
    public static function chatbot(): void
    {
        $u = require_login();
        $hist = Db::all("SELECT prompt, response, is_mock, status, created_at FROM ai_interactions WHERE user_id = ? AND kind = 'chat' ORDER BY id DESC LIMIT 10", [$u['id']]);
        view('ai/chatbot', ['history' => array_reverse($hist), 'provider' => AiService::provider()]);
    }

    public static function suggestReply(): void
    {
        $u = require_login();
        $t = Db::one('SELECT * FROM threads WHERE id = ?', [pint('thread_id')]);
        if (!$t) abort(404, 'Thread not found.');
        Access::requireCourse($u, (int)$t['course_id']);
        $posts = Db::all('SELECT p.body, x.name FROM posts p JOIN users x ON x.id = p.user_id WHERE p.thread_id = ? ORDER BY p.id DESC LIMIT 5', [$t['id']]);
        $ctx = "Discussion title: {$t['title']}\n";
        foreach (array_reverse($posts) as $p) $ctx .= "{$p['name']}: {$p['body']}\n";
        $r = AiService::ask((int)$u['id'], 'reply', 'You help a student draft a short, polite, helpful reply to a course discussion. Reply with the draft only.', $ctx);
        if ($r->ok) $_SESSION['draft_' . $t['id']] = $r->text; else flash('error', $r->userMessage());
        redirect('/thread?id=' . (int)$t['id']);
    }

    public static function ask(): void
    {
        $u = require_login();
        $q = pstr('question');
        if ($q === '' || mb_strlen($q) > 2000) { flash('error', 'Ask a question of 1 to 2000 characters.'); redirect('/chatbot'); }
        $r = AiService::ask((int)$u['id'], 'chat', AiService::TUTOR_SYSTEM, $q);
        if (!$r->ok) flash('error', $r->userMessage());
        redirect('/chatbot');
    }
}
