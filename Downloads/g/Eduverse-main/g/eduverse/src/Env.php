<?php
declare(strict_types=1);

class Env
{
    private static array $vars = [];

    public static function load(string $file): void
    {
        if (!is_file($file)) return;
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
            [$k, $v] = explode('=', $line, 2);
            self::$vars[trim($k)] = trim(trim($v), "\"'");
        }
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        if (array_key_exists($key, self::$vars)) return self::$vars[$key];
        $env = getenv($key);
        return $env !== false ? $env : $default;
    }

    public static function set(string $key, string $value): void { self::$vars[$key] = $value; }
}
