<?php
declare(strict_types=1);

class LecturerController
{
    private static function parseDate(string $v): ?string
    {
        $d = DateTime::createFromFormat('Y-m-d\TH:i', $v);
        return $d ? $d->format('Y-m-d H:i:s') : null;
    }

    public static function courseCreate(): void
    {
        $u = require_role('lecturer');
        $code = strtoupper(pstr('code'));
        $title = pstr('title');
        if (!preg_match('/^[A-Z0-9\-]{2,20}$/', $code) || mb_strlen($title) < 3 || mb_strlen($title) > 190) {
            flash('error', 'Course code (2-20 letters/numbers/hyphens) and a title (3-190 characters) are required.'); redirect('/');
        }
        if (Db::val('SELECT COUNT(*) FROM courses WHERE code = ?', [$code]) > 0) { flash('error', 'That course code already exists.'); redirect('/'); }
        $id = Db::insert('INSERT INTO courses (code, title, description, lecturer_id) VALUES (?,?,?,?)', [$code, $title, mb_substr(pstr('description'), 0, 2000) ?: null, $u['id']]);
        flash('ok', 'Course created.');
        redirect('/course?id=' . $id);
    }

    public static function lessonCreate(): void
    {
        $u = require_role('lecturer', 'admin');
        $c = Access::requireCourse($u, pint('course_id'), true);
        $title = pstr('title');
        $chapter = pstr('chapter');
        $back = '/course?id=' . (int)$c['id'];
        if ($title === '' || $chapter === '' || mb_strlen($title) > 190 || mb_strlen($chapter) > 60) { flash('error', 'Chapter and title are required.'); redirect($back); }
        [$path, $orig, $err] = Upload::store($_FILES['file'] ?? null, 'lessons');
        if ($err) { flash('error', $err); redirect($back); }
        Db::exec('INSERT INTO lessons (course_id, chapter, title, category, file_path, file_name) VALUES (?,?,?,?,?,?)',
            [$c['id'], $chapter, $title, mb_substr(pstr('category'), 0, 60) ?: null, $path, $orig]);
        flash('ok', 'Lesson added.');
        redirect($back);
    }

    public static function exerciseCreate(): void
    {
        $u = require_role('lecturer', 'admin');
        $l = Db::one('SELECT * FROM lessons WHERE id = ?', [pint('lesson_id')]);
        if (!$l) abort(404, 'Lesson not found.');
        Access::requireCourse($u, (int)$l['course_id'], true);
        $back = '/lesson?id=' . (int)$l['id'];
        $ans = strtoupper(pstr('answer'));
        $f = ['question', 'choice_a', 'choice_b', 'choice_c', 'choice_d'];
        foreach ($f as $k) if (pstr($k) === '' || mb_strlen(pstr($k)) > 1000) { flash('error', 'Question and all four choices are required.'); redirect($back); }
        if (!in_array($ans, ['A', 'B', 'C', 'D'], true)) { flash('error', 'Choose the correct answer (A-D).'); redirect($back); }
        Db::exec('INSERT INTO exercises (lesson_id, question, choice_a, choice_b, choice_c, choice_d, answer) VALUES (?,?,?,?,?,?,?)',
            [$l['id'], pstr('question'), mb_substr(pstr('choice_a'), 0, 255), mb_substr(pstr('choice_b'), 0, 255), mb_substr(pstr('choice_c'), 0, 255), mb_substr(pstr('choice_d'), 0, 255), $ans]);
        flash('ok', 'Question added.');
        redirect($back);
    }

    public static function assignmentCreate(): void
    {
        $u = require_role('lecturer', 'admin');
        $c = Access::requireCourse($u, pint('course_id'), true);
        $back = '/course?id=' . (int)$c['id'];
        $due = self::parseDate(pstr('due_at'));
        if (pstr('title') === '' || mb_strlen(pstr('title')) > 190 || !$due) { flash('error', 'A title and a valid deadline are required.'); redirect($back); }
        Db::exec('INSERT INTO assignments (course_id, title, instructions, due_at) VALUES (?,?,?,?)', [$c['id'], pstr('title'), mb_substr(pstr('instructions'), 0, 5000) ?: null, $due]);
        Notifier::notifyCourse((int)$c['id'], 'New assignment in ' . $c['code'] . ': ' . pstr('title'), 'New assignment: ' . $c['code']);
        flash('ok', 'Assignment created and students notified.');
        redirect($back);
    }

    public static function sessionCreate(): void
    {
        $u = require_role('lecturer', 'admin');
        $c = Access::requireCourse($u, pint('course_id'), true);
        $back = '/course?id=' . (int)$c['id'];
        $at = self::parseDate(pstr('starts_at'));
        if (pstr('title') === '' || mb_strlen(pstr('title')) > 190 || !$at) { flash('error', 'A title and a valid start time are required.'); redirect($back); }
        Db::exec('INSERT INTO class_sessions (course_id, title, starts_at, room_name) VALUES (?,?,?,?)', [$c['id'], pstr('title'), $at, Jitsi::roomName($c['code'])]);
        Notifier::notifyCourse((int)$c['id'], 'Class scheduled for ' . $c['code'] . ' on ' . $at . ': ' . pstr('title'), 'Class scheduled: ' . $c['code']);
        flash('ok', 'Class scheduled and students notified.');
        redirect($back);
    }

    private static function loadAssignment(array $u, int $id): array
    {
        $a = Db::one('SELECT * FROM assignments WHERE id = ?', [$id]);
        if (!$a) abort(404, 'Assignment not found.');
        $c = Access::requireCourse($u, (int)$a['course_id'], true);
        return [$a, $c];
    }

    public static function submissions(): void
    {
        $u = require_role('lecturer', 'admin');
        [$a, $c] = self::loadAssignment($u, qint('assignment'));
        $subs = Db::all('SELECT s.*, u.name AS student FROM submissions s JOIN users u ON u.id = s.student_id WHERE s.assignment_id = ? ORDER BY u.name', [$a['id']]);
        $reports = Db::all('SELECT p.similarity, ua.name AS a_name, ub.name AS b_name FROM plagiarism_reports p
            JOIN submissions sa ON sa.id = p.submission_id JOIN submissions sb ON sb.id = p.other_submission_id
            JOIN users ua ON ua.id = sa.student_id JOIN users ub ON ub.id = sb.student_id
            WHERE sa.assignment_id = ? ORDER BY p.similarity DESC', [$a['id']]);
        view('lecturer/submissions', ['assignment' => $a, 'course' => $c, 'subs' => $subs, 'reports' => $reports,
            'ai' => AiService::provider(), 'threshold' => (float)Env::get('PLAGIARISM_THRESHOLD', '0.5')]);
    }

    private static function loadSubmission(array $u, int $id): array
    {
        $s = Db::one('SELECT * FROM submissions WHERE id = ?', [$id]);
        if (!$s) abort(404, 'Submission not found.');
        [$a] = self::loadAssignment($u, (int)$s['assignment_id']);
        return [$s, $a];
    }

    public static function grade(): void
    {
        $u = require_role('lecturer', 'admin');
        [$s, $a] = self::loadSubmission($u, pint('submission_id'));
        $back = '/lecturer/submissions?assignment=' . (int)$a['id'];
        $g = pstr('grade');
        if (!is_numeric($g) || (float)$g < 0 || (float)$g > 100) { flash('error', 'Grade must be a number from 0 to 100.'); redirect($back); }
        Db::exec('UPDATE submissions SET lecturer_grade = ?, lecturer_comment = ?, graded_at = NOW() WHERE id = ?', [(float)$g, mb_substr(pstr('comment'), 0, 2000) ?: null, $s['id']]);
        Notifier::notify((int)$s['student_id'], 'Your submission for "' . $a['title'] . '" has been graded.');
        flash('ok', 'Grade saved.');
        redirect($back);
    }

    public static function aiFeedback(): void
    {
        $u = require_role('lecturer', 'admin');
        [$s, $a] = self::loadSubmission($u, pint('submission_id'));
        $back = '/lecturer/submissions?assignment=' . (int)$a['id'];
        if (trim((string)$s['text_content']) === '') { flash('error', 'AI feedback works on typed answers only. This submission has no text.'); redirect($back); }
        $prompt = "Assignment: {$a['title']}\nInstructions: {$a['instructions']}\n\nStudent answer:\n{$s['text_content']}";
        $r = AiService::ask((int)$u['id'], 'essay', AiService::GRADER_SYSTEM, $prompt);
        if (!$r->ok) { flash('error', $r->userMessage()); redirect($back); }
        Db::exec('UPDATE submissions SET ai_feedback = ?, ai_provider = ?, ai_is_mock = ? WHERE id = ?', [$r->text, $r->provider, (int)$r->isMock, $s['id']]);
        flash('ok', $r->isMock ? 'Placeholder (MOCK) feedback stored. No AI provider is configured.' : 'AI feedback stored. Review it before grading.');
        redirect($back);
    }

    public static function plagiarism(): void
    {
        $u = require_role('lecturer', 'admin');
        [$a] = self::loadAssignment($u, pint('assignment_id'));
        $subs = Db::all("SELECT id, text_content FROM submissions WHERE assignment_id = ? AND text_content IS NOT NULL AND text_content <> ''", [$a['id']]);
        $pairs = Plagiarism::findPairs($subs, (float)Env::get('PLAGIARISM_THRESHOLD', '0.5'));
        Db::exec('DELETE p FROM plagiarism_reports p JOIN submissions s ON s.id = p.submission_id WHERE s.assignment_id = ?', [$a['id']]);
        foreach ($pairs as $p) Db::exec('INSERT INTO plagiarism_reports (submission_id, other_submission_id, similarity) VALUES (?,?,?)', [$p['a'], $p['b'], $p['similarity']]);
        flash('ok', 'Compared ' . count($subs) . ' text submissions; ' . count($pairs) . ' pair(s) at or above the threshold.');
        redirect('/lecturer/submissions?assignment=' . (int)$a['id']);
    }

    public static function analytics(): void
    {
        $u = require_role('lecturer', 'admin');
        $c = Access::requireCourse($u, qint('course'), true);
        view('lecturer/analytics', ['course' => $c, 'students' => Analytics::withAverages(Analytics::courseStudents((int)$c['id'])), 'missed' => Analytics::mostMissed((int)$c['id'])]);
    }

    public static function report(): void
    {
        $u = require_role('lecturer', 'admin');
        $c = Access::requireCourse($u, qint('course'), true);
        $students = Analytics::withAverages(Analytics::courseStudents((int)$c['id']));
        $avgs = array_filter(array_column($students, 'avg'), fn($v) => $v !== null);
        $assignments = Db::all('SELECT a.title, a.due_at,
                (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id) AS submitted,
                (SELECT COUNT(*) FROM submissions s WHERE s.assignment_id = a.id AND s.lecturer_grade IS NOT NULL) AS graded
            FROM assignments a WHERE a.course_id = ? ORDER BY a.due_at', [$c['id']]);
        view('lecturer/report', ['course' => $c, 'students' => $students, 'missed' => Analytics::mostMissed((int)$c['id'], 10),
            'classAvg' => $avgs ? round(array_sum($avgs) / count($avgs), 1) : null, 'generated' => date('Y-m-d H:i'), 'assignments' => $assignments]);
    }

    public static function export(): void
    {
        $u = require_role('lecturer', 'admin');
        $c = Access::requireCourse($u, qint('course'), true);
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="course-' . preg_replace('/[^A-Za-z0-9\-]/', '', $c['code']) . '-progress.csv"');
        $out = fopen('php://output', 'w');
        fputcsv($out, ['Student', 'Email', 'Quiz attempts', 'Average quiz %', 'Submissions', 'Flagged below ' . Analytics::STRUGGLING_BELOW . '%']);
        foreach (Analytics::withAverages(Analytics::courseStudents((int)$c['id'])) as $r) {
            fputcsv($out, [Analytics::csvCell($r['name']), Analytics::csvCell($r['email']), $r['attempts'], $r['avg'] ?? '', $r['submissions'], $r['struggling'] ? 'yes' : 'no']);
        }
        fclose($out);
        exit;
    }
}
