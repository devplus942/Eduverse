<?php $title = 'Platform overview'; ?>
<h1>Platform overview</h1>
<dl class="facts"><?php foreach ($counts as $k => $v): ?><dt><?= e(str_replace('_', ' ', $k)) ?></dt><dd><?= (int)$v ?></dd><?php endforeach; ?></dl>
<h2>Courses</h2>
<?php if (!$courses): ?><p class="empty">No courses yet.</p><?php else: ?>
<ul class="plain"><?php foreach ($courses as $c): ?>
  <li><strong><?= e($c['code']) ?></strong> <?= e($c['title']) ?> <span class="muted"><?= e($c['lecturer']) ?>, <?= (int)$c['students'] ?> enrolled</span>
  <a href="<?= e(url('/lecturer/analytics')) ?>?course=<?= (int)$c['id'] ?>">Analytics</a></li>
<?php endforeach; ?></ul><?php endif; ?>
