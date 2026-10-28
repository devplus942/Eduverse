<?php
declare(strict_types=1);

class HttpException extends RuntimeException {}

function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function abort(int $code, string $msg = ''): never { throw new HttpException($msg, $code); }
/** Sub-folder the app is served from, e.g. "/eduverse" on XAMPP htdocs; empty at the web root. */
function base_path(): string { $b = trim((string)Env::get('APP_BASE_PATH', ''), " /"); return $b === '' ? '' : '/' . $b; }
function url(string $path = '/'): string { return base_path() . $path; }
function redirect(string $to): never { header('Location: ' . (str_starts_with($to, '/') ? url($to) : $to)); exit; }
function flash(string $type, string $msg): void { $_SESSION['flash'][] = ['t' => $type, 'm' => $msg]; }
function flashes(): array { $f = $_SESSION['flash'] ?? []; unset($_SESSION['flash']); return $f; }

function csrf_token(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(32)); }
function csrf_field(): string { return '<input type="hidden" name="_csrf" value="' . e(csrf_token()) . '">'; }
function csrf_check(): void
{
    $t = $_POST['_csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    $s = (string)($_SESSION['csrf'] ?? '');
    if (!is_string($t) || $t === '' || $s === '' || !hash_equals($s, $t)) {
        abort(419, 'Invalid or missing security token. Reload the page and try again.');
    }
}

function pstr(string $k): string { $v = $_POST[$k] ?? ''; return is_string($v) ? trim($v) : ''; }
function pint(string $k): int { return (int)($_POST[$k] ?? 0); }
function qint(string $k): int { return (int)($_GET[$k] ?? 0); }

function current_user(): ?array
{
    $id = $_SESSION['uid'] ?? null;
    if (!$id) return null;
    $u = Db::one('SELECT id, name, email, role, is_active FROM users WHERE id = ?', [$id]);
    if (!$u || !$u['is_active']) { unset($_SESSION['uid']); return null; }
    return $u;
}
function require_login(): array { $u = current_user(); if (!$u) redirect('/login'); return $u; }
function require_role(string ...$roles): array
{
    $u = require_login();
    if (!in_array($u['role'], $roles, true)) abort(403, 'You do not have permission to view this page.');
    return $u;
}

function view(string $name, array $data = []): void
{
    $data['user'] = current_user();
    extract($data, EXTR_SKIP);
    ob_start();
    require ROOT . '/views/' . $name . '.php';
    $content = ob_get_clean();
    require ROOT . '/views/layout.php';
}

function json_out(array $data, int $code = 200): never
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function pct(int $score, int $total): float { return $total > 0 ? round($score * 100 / $total, 1) : 0.0; }
