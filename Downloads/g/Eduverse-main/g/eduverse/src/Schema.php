<?php
declare(strict_types=1);

/** Applies database/schema.sql (idempotent baseline) and tracked migrations from database/migrations/*.sql. */
class Schema
{
    /** Split SQL on statement-ending semicolons. Do not put semicolons inside string literals in schema/migration files. */
    public static function statements(string $sql): array
    {
        $out = [];
        foreach (preg_split('/;\s*(?:\n|$)/', $sql) as $s) {
            $s = trim($s);
            if ($s !== '') $out[] = $s;
        }
        return $out;
    }

    public static function apply(PDO $pdo): int
    {
        $n = 0;
        foreach (self::statements((string)file_get_contents(ROOT . '/database/schema.sql')) as $st) { $pdo->exec($st); $n++; }
        return $n;
    }

    /** Runs migration files not yet recorded in schema_migrations, in file-name order. @return string[] names applied */
    public static function migrate(PDO $pdo, ?string $dir = null): array
    {
        $dir ??= ROOT . '/database/migrations';
        $done = array_column($pdo->query('SELECT name FROM schema_migrations')->fetchAll(PDO::FETCH_ASSOC), 'name');
        $files = glob($dir . '/*.sql') ?: [];
        sort($files);
        $applied = [];
        foreach ($files as $f) {
            $name = basename($f);
            if (in_array($name, $done, true)) continue;
            foreach (self::statements((string)file_get_contents($f)) as $st) $pdo->exec($st);
            $pdo->prepare('INSERT INTO schema_migrations (name) VALUES (?)')->execute([$name]);
            $applied[] = $name;
        }
        return $applied;
    }
}
