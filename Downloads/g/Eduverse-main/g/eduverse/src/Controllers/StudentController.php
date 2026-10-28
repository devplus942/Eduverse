<?php
declare(strict_types=1);

class StudentController
{
    public static function courses(): void
    {
        $u = require_login();
        $rows = Db::all('SELECT c.*, u.name AS lecturer FROM courses c JOIN users u ON u.id = c.lecturer_id ORDER BY c.title');
        $mine = array_column(Db::all('SELECT course_id FROM enrollments WHERE student_id = ?', [$u['id']]), 'course_id');
        view('courses/index', ['courses' => $rows, 'enrolled' => array_map('intval', $mine)]);
    }

    public static function enroll(): void
    {
        $u = require_role('student');
        $c = Access::course(pint('course_id'));
        if (!$c) abort(404, 'Course not found.');
        Db::exec('INSERT IGNORE INTO enrollments (course_id, student_id) VALUES (?, ?)', [$c['id'], $u['id']]);
        flash('ok', 'Enrolled in ' . $c['title'] . '.');
        redirect('/course?id=' . (int)$c['id']);
    }

    public static function course(): void
    {
        $u = require_login();
        $c = Access::requireCourse($u, qint('id'));
        $lessons = Db::all('SELECT l.*, (SELECT COUNT(*) FROM exercises x WHERE x.lesson_id = l.id) AS exercise_count FROM lessons l WHERE course_id = ? ORDER BY chapter, id', [$c['id']]);
        $assignments = Db::all('SELECT * FROM assignments WHERE course_id = ? ORDER BY due_at', [$c['id']]);
        $sessions = Db::all('SELECT * FROM class_sessions WHERE course_id = ? ORDER BY starts_at DESC', [$c['id']]);
        foreach ($sessions as &$s) $s['url'] = Jitsi::url($s['room_name']);
        view('courses/show', ['course' => $c, 'lessons' => $lessons, 'assignments' => $assignments, 'sessions' => $sessions,
            'manage' => Access::ownsCourse($u, $c)]);
    }

    public static function lesson(): void
    {
        $u = require_login();
        $l = Db::one('SELECT * FROM lessons WHERE id = ?', [qint('id')]);
        if (!$l) abort(404, 'Lesson not found.');
        $c = Access::requireCourse($u, (int)$l['course_id']);
        $ex = Db::all('SELECT * FROM exercises WHERE lesson_id = ? ORDER BY id', [$l['id']]);
        $history = $u['role'] === 'student' ? Db::all('SELECT * FROM attempts WHERE student_id = ? AND lesson_id = ? ORDER BY id DESC LIMIT 5', [$u['id'], $l['id']]) : [];
        view('lessons/show', ['lesson' => $l, 'course' => $c, 'exercises' => $ex, 'history' => $history, 'manage' => Access::ownsCourse($u, $c)]);
    }

    public static function quiz(): void
    {
        $u = require_role('student');
        $l = Db::one('SELECT * FROM lessons WHERE id = ?', [qint('lesson')]);
        if (!$l) abort(404, 'Lesson not found.');
        Access::requireCourse($u, (int)$l['course_id']);
        $ex = Db::all('SELECT id, question, choice_a, choice_b, choice_c, choice_d FROM exercises WHERE lesson_id = ? ORDER BY id', [$l['id']]);
        if (!$ex) { flash('error', 'This lesson has no quiz questions yet.'); redirect('/lesson?id=' . (int)$l['id']); }
        view('quiz/take', ['lesson' => $l, 'exercises' => $ex]);
    }

    public static function quizSubmit(): void
    {
        $u = require_role('student');
        $l = Db::one('SELECT * FROM lessons WHERE id = ?', [pint('lesson_id')]);
        if (!$l) abort(404, 'Lesson not found.');
        Access::requireCourse($u, (int)$l['course_id']);
        $ex = Db::all('SELECT id, answer FROM exercises WHERE lesson_id = ?', [$l['id']]);
        if (!$ex) abort(400, 'This lesson has no quiz questions.');
        $chosen = is_array($_POST['q'] ?? null) ? $_POST['q'] : [];
        $g = Grader::mcq($ex, $chosen);
        $pdo = Db::pdo();
        $pdo->beginTransaction();
        try {
            $aid = Db::insert('INSERT INTO attempts (student_id, lesson_id, num_items, score) VALUES (?,?,?,?)', [$u['id'], $l['id'], $g['total'], $g['score']]);
            foreach ($g['rows'] as $r) {
                Db::exec('INSERT INTO attempt_answers (attempt_id, exercise_id, chosen, is_correct) VALUES (?,?,?,?)', [$aid, $r['exercise_id'], $r['chosen'], (int)$r['is_correct']]);
            }
            $pdo->commit();
        } catch (Throwable $e) { $pdo->rollBack(); throw $e; }
        redirect('/quiz/result?id=' . $aid);
    }

    public static function quizResult(): void
    {
        $u = require_role('student');
        $a = Db::one('SELECT * FROM attempts WHERE id = ? AND student_id = ?', [qint('id'), $u['id']]);
        if (!$a) abort(404, 'Result not found.');
        $rows = Db::all('SELECT x.question, x.answer, aa.chosen, aa.is_correct FROM attempt_answers aa JOIN exercises x ON x.id = aa.exercise_id WHERE aa.attempt_id = ? ORDER BY x.id', [$a['id']]);
        view('quiz/result', ['attempt' => $a, 'rows' => $rows, 'lesson' => Db::one('SELECT * FROM lessons WHERE id = ?', [$a['lesson_id']])]);
    }

    public static function assignment(): void
    {
        $u = require_login();
        $a = Db::one('SELECT * FROM assignments WHERE id = ?', [qint('id')]);
        if (!$a) abort(404, 'Assignment not found.');
        $c = Access::requireCourse($u, (int)$a['course_id']);
        $sub = $u['role'] === 'student' ? Db::one('SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?', [$a['id'], $u['id']]) : null;
        view('assignments/show', ['assignment' => $a, 'course' => $c, 'sub' => $sub, 'manage' => Access::ownsCourse($u, $c),
            'open' => new DateTime() <= new DateTime($a['due_at'])]);
    }

    public static function assignmentSubmit(): void
    {
        $u = require_role('student');
        $a = Db::one('SELECT * FROM assignments WHERE id = ?', [pint('assignment_id')]);
        if (!$a) abort(404, 'Assignment not found.');
        Access::requireCourse($u, (int)$a['course_id']);
        $back = '/assignment?id=' . (int)$a['id'];
        if (new DateTime() > new DateTime($a['due_at'])) { flash('error', 'The deadline has passed. Submissions are closed.'); redirect($back); }
        $text = trim((string)($_POST['text_content'] ?? ''));
        if (mb_strlen($text) > 20000) { flash('error', 'Text answer is too long (max 20000 characters).'); redirect($back); }
        [$path, $orig, $err] = Upload::store($_FILES['file'] ?? null, 'submissions');
        if ($err) { flash('error', $err); redirect($back); }
        if ($text === '' && $path) {
            $extracted = TextExtract::fromFile($path, (string)$orig);
            if ($extracted) { $text = $extracted; flash('ok', 'Text was extracted from your file for plagiarism and AI feedback.'); }
        }
        if ($text === '' && !$path) { flash('error', 'Write an answer or attach a file.'); redirect($back); }
        Db::exec('INSERT INTO submissions (assignment_id, student_id, text_content, file_path, file_name) VALUES (?,?,?,?,?)
                  ON DUPLICATE KEY UPDATE text_content = VALUES(text_content), file_path = COALESCE(VALUES(file_path), file_path),
                  file_name = COALESCE(VALUES(file_name), file_name), submitted_at = CURRENT_TIMESTAMP,
                  ai_feedback = NULL, ai_provider = NULL, ai_is_mock = NULL, lecturer_grade = NULL, lecturer_comment = NULL, graded_at = NULL',
            [$a['id'], $u['id'], $text ?: null, $path, $orig]);
        flash('ok', 'Submission saved.');
        redirect($back);
    }

    public static function download(): void
    {
        $u = require_login();
        $type = (string)($_GET['type'] ?? '');
        $id = qint('id');
        if ($type === 'lesson') {
            $l = Db::one('SELECT * FROM lessons WHERE id = ?', [$id]);
            if (!$l || !$l['file_path']) abort(404, 'File not found.');
            Access::requireCourse($u, (int)$l['course_id']);
            Upload::send($l['file_path'], (string)$l['file_name']);
        }
        if ($type === 'submission') {
            $s = Db::one('SELECT s.*, a.course_id FROM submissions s JOIN assignments a ON a.id = s.assignment_id WHERE s.id = ?', [$id]);
            if (!$s || !$s['file_path']) abort(404, 'File not found.');
            $c = Access::course((int)$s['course_id']);
            if (!($u['role'] === 'student' && (int)$s['student_id'] === (int)$u['id']) && !Access::ownsCourse($u, $c)) abort(403, 'Not allowed.');
            Upload::send($s['file_path'], (string)$s['file_name']);
        }
        abort(404, 'File not found.');
    }
}
