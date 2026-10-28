<?php
// Usage: php tests/run.php        (unit tests always run; DB tests need MySQL and are reported SKIPPED otherwise)
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

class SkipTest extends Exception {}
$GLOBALS['T'] = ['pass' => 0, 'fail' => 0, 'skip' => 0];

function test(string $name, callable $fn): void
{
    try { $fn(); $GLOBALS['T']['pass']++; echo "  PASS  $name\n"; }
    catch (SkipTest $s) { $GLOBALS['T']['skip']++; echo "  SKIP  $name ({$s->getMessage()})\n"; }
    catch (Throwable $e) { $GLOBALS['T']['fail']++; echo "  FAIL  $name\n        " . get_class($e) . ': ' . $e->getMessage() . "\n"; }
}
function eq($expected, $actual, string $msg = ''): void
{
    if ($expected !== $actual) throw new Exception(trim("$msg expected " . var_export($expected, true) . ' got ' . var_export($actual, true)));
}
function ok($cond, string $msg = 'assertion failed'): void { if (!$cond) throw new Exception($msg); }
function throws(callable $fn, string $class, ?int $code = null): void
{
    try { $fn(); } catch (Throwable $e) {
        if (!($e instanceof $class)) throw new Exception("expected $class, got " . get_class($e));
        if ($code !== null && $e->getCode() !== $code) throw new Exception("expected code $code, got " . $e->getCode());
        return;
    }
    throw new Exception("expected $class to be thrown");
}

/** Creates a fresh eduverse_test database (never touches your real database) and applies schema.sql. */
function db_ready(): bool
{
    static $ready = null;
    if ($ready !== null) return $ready;
    try {
        $name = 'eduverse_test';
        Env::set('DB_NAME', $name);
        $root = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', Env::get('DB_HOST', '127.0.0.1'), Env::get('DB_PORT', '3306')),
            Env::get('DB_USER', 'root'), Env::get('DB_PASS', ''), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $root->exec("DROP DATABASE IF EXISTS `$name`");
        $root->exec("CREATE DATABASE `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        Db::reset();
        Schema::apply(Db::pdo());
        return $ready = true;
    } catch (Throwable $e) {
        $GLOBALS['db_why'] = $e->getMessage();
        return $ready = false;
    }
}
function needdb(): void { if (!db_ready()) throw new SkipTest('MySQL not reachable: ' . ($GLOBALS['db_why'] ?? '')); }

function make_user(string $role, ?string $email = null): int
{
    static $n = 0; $n++;
    $r = Auth::register("Test $role $n", $email ?? "$role$n@example.test", 'password123', $role);
    if (!$r['ok']) throw new Exception('fixture failed: ' . implode(' ', $r['errors']));
    return (int)$r['id'];
}

foreach (['unit_test.php', 'ai_test.php', 'db_test.php'] as $f) { echo "\n== $f ==\n"; require __DIR__ . '/' . $f; }

$t = $GLOBALS['T'];
echo "\nSummary: {$t['pass']} passed, {$t['fail']} failed, {$t['skip']} skipped\n";
exit($t['fail'] > 0 ? 1 : 0);
