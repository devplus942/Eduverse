<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'EduVerse') ?> | EduVerse</title>
<link rel="stylesheet" href="<?= e(url('/assets/css/app.css')) ?>">
<script src="<?= e(url('/assets/js/app.js')) ?>" defer></script>
<meta name="csrf" content="<?= e(csrf_token()) ?>">
<meta name="base" content="<?= e(base_path()) ?>">
</head>
<body>
<a class="skip" href="#main">Skip to content</a>
<header class="top">
  <a class="brand" href="<?= e(url('/')) ?>">EduVerse</a>
  <nav aria-label="Main">
    <?php if ($user): ?>
      <a href="<?= e(url('/')) ?>">Dashboard</a>
      <a href="<?= e(url('/courses')) ?>">Courses</a>
      <a href="<?= e(url('/chatbot')) ?>">AI tutor</a>
      <?php if ($user['role'] === 'admin'): ?><a href="<?= e(url('/admin/users')) ?>">Users</a><a href="<?= e(url('/admin/analytics')) ?>">Analytics</a><?php endif; ?>
    <?php else: ?>
      <a href="<?= e(url('/login')) ?>">Log in</a><a href="<?= e(url('/register')) ?>">Register</a>
    <?php endif; ?>
  </nav>
  <div class="tools">
    <button type="button" class="ghost" data-font="-1" aria-label="Decrease text size">A-</button>
    <button type="button" class="ghost" data-font="1" aria-label="Increase text size">A+</button>
    <button type="button" class="ghost" id="theme-toggle" aria-label="Toggle dark mode">Theme</button>
    <?php if ($user): ?>
      <form method="post" action="<?= e(url('/logout')) ?>" class="inline"><?= csrf_field() ?><button class="ghost" type="submit">Log out (<?= e($user['name']) ?>)</button></form>
    <?php endif; ?>
  </div>
</header>
<main id="main">
  <?php foreach (flashes() as $f): ?>
    <div class="flash <?= $f['t'] === 'ok' ? 'ok' : 'err' ?>" role="<?= $f['t'] === 'ok' ? 'status' : 'alert' ?>"><?= e($f['m']) ?></div>
  <?php endforeach; ?>
  <?= $content ?>
</main>
<footer class="foot">EduVerse is a reconstruction of an AUK final-year project. Mock AI output is always labelled.</footer>
</body>
</html>
