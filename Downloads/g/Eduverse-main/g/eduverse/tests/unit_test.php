<?php
// Pure-logic tests: no database or network required.

test('Grader: all correct', function () {
    $g = Grader::mcq([['id' => 1, 'answer' => 'A'], ['id' => 2, 'answer' => 'C']], [1 => 'A', 2 => 'C']);
    eq(2, $g['score']); eq(2, $g['total']);
});
test('Grader: wrong, blank and invalid answers score zero', function () {
    $g = Grader::mcq([['id' => 1, 'answer' => 'A'], ['id' => 2, 'answer' => 'B'], ['id' => 3, 'answer' => 'D']], [1 => 'B', 3 => 'Z']);
    eq(0, $g['score']); eq(null, $g['rows'][1]['chosen']); eq(null, $g['rows'][2]['chosen']);
});
test('Grader: lowercase answer accepted', function () {
    eq(1, Grader::mcq([['id' => 1, 'answer' => 'B']], [1 => 'b'])['score']);
});

test('Plagiarism: identical text is 1.0', fn() => eq(1.0, Plagiarism::similarity('The cell is the basic unit of life', 'The cell is the basic unit of life')));
test('Plagiarism: unrelated text is 0.0', fn() => eq(0.0, Plagiarism::similarity('alpha beta gamma delta', 'one two three four')));
test('Plagiarism: ignores case and punctuation', fn() => eq(1.0, Plagiarism::similarity('Hello, World! This is a test.', 'hello world this is a test')));
test('Plagiarism: partial overlap is between 0 and 1', function () {
    $s = Plagiarism::similarity('the quick brown fox jumps over the lazy dog', 'the quick brown fox sleeps under the old tree');
    ok($s > 0.0 && $s < 1.0, "got $s");
});
test('Plagiarism: empty text is 0.0', fn() => eq(0.0, Plagiarism::similarity('', 'something here now')));
test('Plagiarism: findPairs respects threshold', function () {
    $subs = [['id' => 1, 'text_content' => 'photosynthesis converts light energy into chemical energy'],
             ['id' => 2, 'text_content' => 'photosynthesis converts light energy into chemical energy'],
             ['id' => 3, 'text_content' => 'completely different words about another subject entirely']];
    $p = Plagiarism::findPairs($subs, 0.5);
    eq(1, count($p)); eq([1, 2], [$p[0]['a'], $p[0]['b']]);
});

test('Recommender: weak lessons first (lowest score), then not attempted, strong excluded', function () {
    $lessons = [['id' => 1, 'title' => 'A', 'exercise_count' => 3], ['id' => 2, 'title' => 'B', 'exercise_count' => 3],
                ['id' => 3, 'title' => 'C', 'exercise_count' => 3], ['id' => 4, 'title' => 'D', 'exercise_count' => 3], ['id' => 5, 'title' => 'E', 'exercise_count' => 0]];
    $r = Recommender::forStudent($lessons, [1 => 40.0, 2 => 90.0, 4 => 20.0]);
    eq([4, 1, 3], array_map(fn($x) => $x['id'], $r));
});

test('Upload::validate accepts allowed type', fn() => eq(null, Upload::validate('notes.pdf', 1000, 5000)));
test('Upload::validate rejects php/exe/no-extension', function () {
    foreach (['shell.php', 'virus.exe', 'README', 'x.phtml'] as $n) ok(Upload::validate($n, 100, 5000) !== null, "$n accepted");
});
test('Upload::validate rejects oversize and empty', function () {
    ok(Upload::validate('a.pdf', 6000, 5000) !== null); ok(Upload::validate('a.pdf', 0, 5000) !== null);
});

test('CSV cells neutralise formula injection', function () {
    eq("'=SUM(A1)", Analytics::csvCell('=SUM(A1)')); eq("'@x", Analytics::csvCell('@x')); eq('Ada', Analytics::csvCell('Ada'));
});
test('e() escapes HTML', fn() => eq('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;', e('<script>alert("x")</script>')));

test('Jitsi: URL uses configured domain and encodes room', function () {
    Env::set('JITSI_DOMAIN', 'meet.example.org');
    eq('https://meet.example.org/room%201', Jitsi::url('room 1'));
    ok(preg_match('/^eduverse-csc401-[0-9a-f]{12}$/', Jitsi::roomName('CSC-401')) === 1);
    Env::set('JITSI_DOMAIN', 'meet.jit.si');
});

test('CSRF: missing token is rejected with 419', function () {
    $_SESSION = []; $_POST = [];
    throws(fn() => csrf_check(), HttpException::class, 419);
});
test('CSRF: correct token passes, wrong token fails', function () {
    $_SESSION = []; $_POST = ['_csrf' => csrf_token()];
    csrf_check();
    $_POST = ['_csrf' => 'wrong'];
    throws(fn() => csrf_check(), HttpException::class, 419);
});

test('TextExtract reads .txt uploads', function () {
    @mkdir(ROOT . '/storage/uploads/tmp', 0775, true);
    file_put_contents(ROOT . '/storage/uploads/tmp/t.txt', "Hello extracted world\n");
    eq('Hello extracted world', TextExtract::fromFile('tmp/t.txt', 'essay.txt'));
    unlink(ROOT . '/storage/uploads/tmp/t.txt');
    eq(null, TextExtract::fromFile('tmp/missing.txt', 'x.txt'));
});
test('TextExtract reads .docx text', function () {
    if (!class_exists('ZipArchive')) throw new SkipTest('zip extension not enabled');
    @mkdir(ROOT . '/storage/uploads/tmp', 0775, true);
    $f = ROOT . '/storage/uploads/tmp/t.docx';
    $z = new ZipArchive(); $z->open($f, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $z->addFromString('word/document.xml', '<w:document><w:body><w:p><w:r><w:t>First paragraph</w:t></w:r></w:p><w:p><w:r><w:t>Second</w:t></w:r></w:p></w:body></w:document>');
    $z->close();
    eq("First paragraph\nSecond", TextExtract::fromFile('tmp/t.docx', 'essay.docx'));
    unlink($f);
});

test('CSRF: empty submitted token with empty session token is rejected (regression)', function () {
    $_SESSION = ['csrf' => '']; $_POST = ['_csrf' => ''];
    throws(fn() => csrf_check(), HttpException::class, 419);
    $_SESSION = []; $_POST = [];
});

test('base_path/url: empty at web root, prefixed in a sub-folder', function () {
    Env::set('APP_BASE_PATH', '');
    eq('/login', url('/login')); eq('', base_path());
    Env::set('APP_BASE_PATH', '/eduverse/');
    eq('/eduverse', base_path()); eq('/eduverse/login', url('/login')); eq('/eduverse/', url('/'));
    Env::set('APP_BASE_PATH', '');
});
test('Schema::statements splits SQL on statement-ending semicolons', function () {
    eq(['CREATE TABLE a (id INT)', 'CREATE TABLE b (id INT)'], Schema::statements("CREATE TABLE a (id INT);\nCREATE TABLE b (id INT);\n"));
    eq([], Schema::statements("  \n"));
});
test('schema.sql defines every table the code expects', function () {
    $sql = file_get_contents(ROOT . '/database/schema.sql');
    foreach (['users','courses','enrollments','lessons','exercises','attempts','attempt_answers','assignments','submissions','plagiarism_reports','class_sessions','threads','posts','chat_messages','ai_interactions','notifications','login_attempts','schema_migrations'] as $t) {
        ok(str_contains($sql, "CREATE TABLE IF NOT EXISTS $t "), "missing table $t");
    }
});
