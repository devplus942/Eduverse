<?php
// Usage: php database/seed_admin.php   (creates the first admin from ADMIN_* in .env; no other data is seeded)
declare(strict_types=1);
require __DIR__ . '/../src/bootstrap.php';

if (Env::get('ADMIN_PASSWORD') === 'change-me-to-a-long-password' || Env::get('ADMIN_EMAIL') === 'admin@example.com') {
    fwrite(STDERR, "Edit ADMIN_EMAIL and ADMIN_PASSWORD in .env first; the placeholder values are refused.\n");
    exit(1);
}
$r = Auth::register((string)Env::get('ADMIN_NAME', 'Administrator'), (string)Env::get('ADMIN_EMAIL', ''), (string)Env::get('ADMIN_PASSWORD', ''), 'admin');
if ($r['ok']) { echo "Admin created.\n"; exit(0); }
echo "Admin not created: " . implode(' ', $r['errors']) . "\n";
exit(in_array('That email is already registered.', $r['errors'], true) ? 0 : 1);
