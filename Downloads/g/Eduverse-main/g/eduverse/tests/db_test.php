<?php
// Database-backed tests. They run against a throw-away database named eduverse_test.

test('Register stores a hashed password, never plaintext', function () {
    needdb();
    $id = make_user('student', 'hash@example.test');
    $h = Db::val('SELECT password_hash FROM users WHERE id = ?', [$id]);
    ok($h !== 'password123' && password_verify('password123', $h));
});
test('Register rejects duplicate email (case-insensitive)', function () {
    needdb();
    make_user('student', 'dup@example.test');
    $r = Auth::register('Other Person', 'DUP@example.test', 'password123');
    eq(false, $r['ok']);
});
test('Register validates email, password length and role', function () {
    needdb();
    eq(false, Auth::register('Ab', 'not-an-email', 'password123')['ok']);
    eq(false, Auth::register('Valid Name', 'v1@example.test', 'short')['ok']);
    eq(false, Auth::register('Valid Name', 'v2@example.test', 'password123', 'superuser')['ok']);
});
test('Login: correct password works, wrong password fails', function () {
    needdb();
    make_user('student', 'login@example.test');
    ok(Auth::attempt('login@example.test', 'password123') !== null);
    eq(null, Auth::attempt('login@example.test', 'wrong-password'));
});
test('Login: disabled account cannot sign in', function () {
    needdb();
    $id = make_user('student', 'off@example.test');
    Db::exec('UPDATE users SET is_active = 0 WHERE id = ?', [$id]);
    eq(null, Auth::attempt('off@example.test', 'password123'));
});
test('Login: locks after 5 failures, even for the right password', function () {
    needdb();
    make_user('student', 'lock@example.test');
    for ($i = 0; $i < 5; $i++) Auth::attempt('lock@example.test', 'bad' . $i);
    ok(Auth::isLocked('lock@example.test'));
    eq(null, Auth::attempt('lock@example.test', 'password123'));
});

test('Access control: enrollment and ownership', function () {
    needdb();
    $lec = make_user('lecturer'); $other = make_user('lecturer'); $stu = make_user('student'); $stu2 = make_user('student'); $adm = make_user('admin');
    $cid = Db::insert('INSERT INTO courses (code, title, lecturer_id) VALUES (?,?,?)', ['T' . random_int(1000, 9999), 'Test course', $lec]);
    Db::exec('INSERT INTO enrollments (course_id, student_id) VALUES (?,?)', [$cid, $stu]);
    $c = Access::course($cid);
    $U = fn($id, $role) => ['id' => $id, 'role' => $role];
    ok(Access::canViewCourse($U($stu, 'student'), $c), 'enrolled student');
    ok(!Access::canViewCourse($U($stu2, 'student'), $c), 'unenrolled student');
    ok(Access::ownsCourse($U($lec, 'lecturer'), $c), 'owner');
    ok(!Access::ownsCourse($U($other, 'lecturer'), $c), 'other lecturer');
    ok(!Access::canViewCourse($U($other, 'lecturer'), $c), 'other lecturer view');
    ok(Access::ownsCourse($U($adm, 'admin'), $c), 'admin');
    throws(fn() => Access::requireCourse($U($stu2, 'student'), $cid), HttpException::class, 403);
    throws(fn() => Access::requireCourse($U($stu, 'student'), $cid, true), HttpException::class, 403);
    throws(fn() => Access::requireCourse($U($stu, 'student'), 999999), HttpException::class, 404);
});

test('Analytics: averages and struggling flag from stored attempts', function () {
    needdb();
    $lec = make_user('lecturer'); $good = make_user('student'); $weak = make_user('student');
    $cid = Db::insert('INSERT INTO courses (code, title, lecturer_id) VALUES (?,?,?)', ['A' . random_int(1000, 9999), 'Analytics course', $lec]);
    $lid = Db::insert('INSERT INTO lessons (course_id, chapter, title) VALUES (?,?,?)', [$cid, '1', 'L1']);
    foreach ([$good, $weak] as $s) Db::exec('INSERT INTO enrollments (course_id, student_id) VALUES (?,?)', [$cid, $s]);
    Db::exec('INSERT INTO attempts (student_id, lesson_id, num_items, score) VALUES (?,?,?,?)', [$good, $lid, 10, 9]);
    Db::exec('INSERT INTO attempts (student_id, lesson_id, num_items, score) VALUES (?,?,?,?)', [$weak, $lid, 10, 3]);
    $rows = [];
    foreach (Analytics::withAverages(Analytics::courseStudents($cid)) as $r) $rows[(int)$r['id']] = $r;
    eq(90.0, $rows[$good]['avg']); eq(false, $rows[$good]['struggling']);
    eq(30.0, $rows[$weak]['avg']); eq(true, $rows[$weak]['struggling']);
});

test('Submissions: one per student per assignment (unique constraint)', function () {
    needdb();
    $lec = make_user('lecturer'); $stu = make_user('student');
    $cid = Db::insert('INSERT INTO courses (code, title, lecturer_id) VALUES (?,?,?)', ['S' . random_int(1000, 9999), 'Sub course', $lec]);
    $aid = Db::insert('INSERT INTO assignments (course_id, title, due_at) VALUES (?,?,?)', [$cid, 'HW', date('Y-m-d H:i:s', time() + 3600)]);
    Db::exec('INSERT INTO submissions (assignment_id, student_id, text_content) VALUES (?,?,?)', [$aid, $stu, 'one']);
    throws(fn() => Db::exec('INSERT INTO submissions (assignment_id, student_id, text_content) VALUES (?,?,?)', [$aid, $stu, 'two']), PDOException::class);
});

test('Deleting a course cascades to lessons and exercises', function () {
    needdb();
    $lec = make_user('lecturer');
    $cid = Db::insert('INSERT INTO courses (code, title, lecturer_id) VALUES (?,?,?)', ['C' . random_int(1000, 9999), 'Cascade', $lec]);
    $lid = Db::insert('INSERT INTO lessons (course_id, chapter, title) VALUES (?,?,?)', [$cid, '1', 'L']);
    Db::exec('INSERT INTO exercises (lesson_id, question, choice_a, choice_b, choice_c, choice_d, answer) VALUES (?,?,?,?,?,?,?)', [$lid, 'q', 'a', 'b', 'c', 'd', 'A']);
    Db::exec('DELETE FROM courses WHERE id = ?', [$cid]);
    eq(0, (int)Db::val('SELECT COUNT(*) FROM exercises WHERE lesson_id = ?', [$lid]));
});

test('AiService logs mock interactions with is_mock = 1', function () {
    needdb();
    Env::set('AI_RATE_LIMIT_PER_HOUR', '20');
    $u = make_user('student');
    $r = AiService::ask($u, 'chat', 'sys', 'Explain osmosis', new MockProvider());
    ok($r->ok && $r->isMock);
    $row = Db::one('SELECT is_mock, status FROM ai_interactions WHERE user_id = ?', [$u]);
    eq(1, (int)$row['is_mock']); eq('ok', $row['status']);
});
test('AiService: provider failure and exception are handled and logged', function () {
    needdb();
    $u = make_user('student');
    $failing = new class implements AiProvider {
        public function complete(string $s, string $p): AiResult { return new AiResult(false, '', 'huggingface', false, 'unavailable', 'down'); }
        public function name(): string { return 'huggingface'; } public function isMock(): bool { return false; }
    };
    $throwing = new class implements AiProvider {
        public function complete(string $s, string $p): AiResult { throw new RuntimeException('boom'); }
        public function name(): string { return 'huggingface'; } public function isMock(): bool { return false; }
    };
    eq('unavailable', AiService::ask($u, 'chat', 's', 'q', $failing)->status);
    eq('error', AiService::ask($u, 'chat', 's', 'q', $throwing)->status);
    eq(2, (int)Db::val('SELECT COUNT(*) FROM ai_interactions WHERE user_id = ? AND response IS NULL', [$u]));
});
test('AiService: hourly limit blocks further calls without calling the provider', function () {
    needdb();
    Env::set('AI_RATE_LIMIT_PER_HOUR', '2');
    $u = make_user('student');
    $counting = new class implements AiProvider {
        public int $calls = 0;
        public function complete(string $s, string $p): AiResult { $this->calls++; return new AiResult(true, 'ok', 'huggingface', false); }
        public function name(): string { return 'huggingface'; } public function isMock(): bool { return false; }
    };
    AiService::ask($u, 'chat', 's', 'one', $counting);
    AiService::ask($u, 'chat', 's', 'two', $counting);
    eq('local_rate_limit', AiService::ask($u, 'chat', 's', 'three', $counting)->status);
    eq(2, $counting->calls);
    Env::set('AI_RATE_LIMIT_PER_HOUR', '20');
});

test('Updater: migrations apply once, in order, and are recorded', function () {
    needdb();
    $dir = sys_get_temp_dir() . '/ev_mig_' . bin2hex(random_bytes(4));
    mkdir($dir);
    file_put_contents($dir . '/001_a.sql', "CREATE TABLE IF NOT EXISTS mig_test (id INT);\n");
    file_put_contents($dir . '/002_b.sql', "ALTER TABLE mig_test ADD COLUMN note VARCHAR(20) NULL;\n");
    $pdo = Db::pdo();
    eq(['001_a.sql', '002_b.sql'], Schema::migrate($pdo, $dir));
    eq([], Schema::migrate($pdo, $dir), 'second run must apply nothing');
    ok((int)Db::val("SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'mig_test' AND column_name = 'note'") === 1);
    $pdo->exec('DROP TABLE mig_test');
    array_map('unlink', glob($dir . '/*.sql')); rmdir($dir);
});
test('Schema::apply is idempotent (safe to re-run on an existing database)', function () {
    needdb();
    ok(Schema::apply(Db::pdo()) > 10);
});
