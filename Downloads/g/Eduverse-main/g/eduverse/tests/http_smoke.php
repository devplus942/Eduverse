<?php
// Usage: php -S 127.0.0.1:8000 -t public public/index.php   (in one window)
//        php tests/http_smoke.php [http://127.0.0.1:8000]   (in another)
declare(strict_types=1);

$base = rtrim($argv[1] ?? 'http://127.0.0.1:8000', '/');
$fail = 0;

function req(string $method, string $url, string $body = ''): array
{
    $ctx = stream_context_create(['http' => ['method' => $method, 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 10,
        'header' => "Content-Type: application/x-www-form-urlencoded\r\n", 'content' => $body]]);
    $html = @file_get_contents($url, false, $ctx);
    if ($html === false) return [0, [], ''];
    preg_match('#HTTP/\S+ (\d+)#', $http_response_header[0] ?? '', $m);
    return [(int)($m[1] ?? 0), $http_response_header, (string)$html];
}
function check(string $name, bool $cond): void { global $fail; echo ($cond ? '  PASS  ' : '  FAIL  ') . $name . "\n"; if (!$cond) $fail++; }

[$code, $h, $body] = req('GET', "$base/login");
if ($code === 0) { echo "Cannot reach $base. Is the server running?\n"; exit(2); }
check('GET /login returns 200 with a form', $code === 200 && str_contains($body, '<form'));
check('Security headers present', (bool)preg_grep('/^X-Frame-Options: DENY/i', $h) && (bool)preg_grep('/^Content-Security-Policy:/i', $h));
[$c] = req('GET', "$base/"); check('GET / without login redirects (302)', $c === 302);
[$c] = req('GET', "$base/admin/users"); check('GET /admin/users without login redirects (302)', $c === 302);
[$c] = req('GET', "$base/lecturer/analytics?course=1"); check('Lecturer analytics without login redirects (302)', $c === 302);
[$c] = req('POST', "$base/login", 'email=a@b.co&password=x'); check('POST /login without CSRF token is rejected (419)', $c === 419);
[$c] = req('GET', "$base/no-such-page"); check('Unknown path returns 404', $c === 404);
[$c] = req('GET', "$base/download?type=lesson&id=1"); check('Download without login redirects (302)', $c === 302);

echo $fail ? "\n$fail check(s) failed\n" : "\nAll smoke checks passed\n";
exit($fail ? 1 : 0);
