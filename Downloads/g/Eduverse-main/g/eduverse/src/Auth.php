<?php
declare(strict_types=1);

class Auth
{
    public const ROLES = ['student', 'lecturer', 'admin'];
    private const MAX_FAILS = 5;
    private const WINDOW_MIN = 15;

    /** @return array{ok:bool, errors:string[], id:?int} */
    public static function register(string $name, string $email, string $password, string $role = 'student', ?string $dept = null): array
    {
        $errors = [];
        $name = trim($name);
        $email = strtolower(trim($email));
        if (mb_strlen($name) < 2 || mb_strlen($name) > 100) $errors[] = 'Name must be 2 to 100 characters.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 190) $errors[] = 'Enter a valid email address.';
        if (strlen($password) < 8 || strlen($password) > 72) $errors[] = 'Password must be 8 to 72 characters.';
        if (!in_array($role, self::ROLES, true)) $errors[] = 'Invalid role.';
        if (!$errors && Db::val('SELECT COUNT(*) FROM users WHERE email = ?', [$email]) > 0) $errors[] = 'That email is already registered.';
        if ($errors) return ['ok' => false, 'errors' => $errors, 'id' => null];

        try {
            $id = Db::insert('INSERT INTO users (name, email, password_hash, role, department) VALUES (?,?,?,?,?)',
                [$name, $email, password_hash($password, PASSWORD_DEFAULT), $role, $dept ?: null]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') return ['ok' => false, 'errors' => ['That email is already registered.'], 'id' => null];
            throw $e;
        }
        return ['ok' => true, 'errors' => [], 'id' => $id];
    }

    public static function isLocked(string $email): bool
    {
        $n = (int)Db::val('SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > (NOW() - INTERVAL ' . self::WINDOW_MIN . ' MINUTE)',
            [strtolower(trim($email))]);
        return $n >= self::MAX_FAILS;
    }

    /** Returns the user row on success, null on failure (wrong credentials, inactive, or locked). */
    public static function attempt(string $email, string $password): ?array
    {
        $email = strtolower(trim($email));
        if (self::isLocked($email)) return null;
        $u = Db::one('SELECT * FROM users WHERE email = ?', [$email]);
        $hash = $u['password_hash'] ?? password_hash('dummy-password-for-timing', PASSWORD_DEFAULT);
        $ok = password_verify($password, $hash);
        if (!$u || !$ok || !$u['is_active']) {
            Db::exec('INSERT INTO login_attempts (email) VALUES (?)', [$email]);
            return null;
        }
        Db::exec('DELETE FROM login_attempts WHERE email = ?', [$email]);
        return $u;
    }

    public static function login(array $u): void { session_regenerate_id(true); $_SESSION['uid'] = (int)$u['id']; }
    public static function logout(): void { $_SESSION = []; if (session_status() === PHP_SESSION_ACTIVE) session_destroy(); }
}
