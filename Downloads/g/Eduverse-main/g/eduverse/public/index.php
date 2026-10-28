<?php
declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

$path = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/') ?: '/';
$base = base_path();
if ($base !== '' && ($path === $base || str_starts_with($path, $base . '/'))) $path = substr($path, strlen($base)) ?: '/';
if (PHP_SAPI === 'cli-server' && $path !== '/' && is_file(__DIR__ . $path)) return false; // serve static assets

header('X-Frame-Options: DENY');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: same-origin');
header("Content-Security-Policy: default-src 'self'; img-src 'self' data:; frame-ancestors 'none'; form-action 'self'");

$https = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
if ($https) header('Strict-Transport-Security: max-age=31536000');
ini_set('session.use_strict_mode', '1');
session_set_cookie_params(['path' => $base ?: '/', 'httponly' => true, 'samesite' => 'Lax', 'secure' => $https]);
session_start();

$routes = require ROOT . '/src/routes.php';
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    $handler = $routes["$method $path"] ?? null;
    if (!$handler) abort(404, 'Page not found.');
    if ($method === 'POST') csrf_check();
    [$class, $fn] = $handler;
    $class::$fn();
} catch (HttpException $e) {
    http_response_code($e->getCode() ?: 500);
    view('error', ['code' => $e->getCode(), 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    @file_put_contents(ROOT . '/storage/logs/app.log', '[' . date('c') . '] ' . $e . "\n", FILE_APPEND);
    http_response_code(500);
    $msg = Env::get('APP_ENV') === 'development' ? $e->getMessage() : 'Something went wrong. Please try again.';
    try { view('error', ['code' => 500, 'message' => $msg]); }
    catch (Throwable $e2) { header('Content-Type: text/plain'); echo "500: $msg"; }
}
