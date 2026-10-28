<?php
// Usage: php database/migrate.php
// Setup AND updater: creates the database if needed, applies schema.sql (idempotent), then any new files in
// database/migrations/ (applied once each, in name order, recorded in schema_migrations). Safe to re-run after every update.
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

$name = Env::get('DB_NAME', 'eduverse');
if (!preg_match('/^[A-Za-z0-9_]+$/', $name)) { fwrite(STDERR, "Invalid DB_NAME\n"); exit(1); }
try {
    $root = new PDO(sprintf('mysql:host=%s;port=%s;charset=utf8mb4', Env::get('DB_HOST', '127.0.0.1'), Env::get('DB_PORT', '3306')),
        Env::get('DB_USER', 'root'), Env::get('DB_PASS', ''), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $root->exec("CREATE DATABASE IF NOT EXISTS `$name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    Db::reset();
    $n = Schema::apply(Db::pdo());
    $applied = Schema::migrate(Db::pdo());
    echo "Database '$name' ready ($n schema statements). Migrations applied: " . ($applied ? implode(', ', $applied) : 'none') . ".\n";
} catch (PDOException $e) {
    fwrite(STDERR, "Database error: " . $e->getMessage() . "\nIs MySQL running and are DB_* values in .env correct?\n");
    exit(1);
}
