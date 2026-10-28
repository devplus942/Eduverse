<?php
declare(strict_types=1);

if (PHP_VERSION_ID < 80100) { http_response_code(500); exit('EduVerse requires PHP 8.1 or newer (found ' . PHP_VERSION . ").\n"); }
foreach (['pdo_mysql', 'mbstring'] as $ext) {
    if (!extension_loaded($ext)) { http_response_code(500); exit("EduVerse requires the PHP extension '$ext'. Enable it in php.ini and restart Apache.\n"); }
}

define('ROOT', dirname(__DIR__));

spl_autoload_register(function (string $class): void {
    foreach (['src/', 'src/Services/', 'src/Controllers/'] as $dir) {
        $file = ROOT . '/' . $dir . $class . '.php';
        if (is_file($file)) { require $file; return; }
    }
});

require ROOT . '/src/helpers.php';
Env::load(ROOT . '/.env');
date_default_timezone_set(Env::get('APP_TIMEZONE', 'Africa/Lagos'));
