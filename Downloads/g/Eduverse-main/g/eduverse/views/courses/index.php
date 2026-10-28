<?php $title = 'Courses'; ?>
<h1>Courses</h1>
<?php if (!$courses): ?><p class="empty">No courses have been created yet.</p><?php else: ?>
<ul class="plain rows"><?php foreach ($courses as $c): $in = in_array((int)$c['id'], $enrolled, true); ?>
  <li>
    <div><strong><?= e($c['code']) ?></strong> <?= e($c['title']) ?><br><span class="muted">Lecturer: <?= e($c['lecturer']) ?></span>
    <?php if ($c['description']): ?><br><?= e($c['description']) ?><?php endif; ?></div>
    <div>
      <?php if ($user['role'] === 'student' && !$in): ?>
        <form method="post" action="<?= e(url('/courses/enroll')) ?>"><?= csrf_field() ?><input type="hidden" name="course_id" value="<?= (int)$c['id'] ?>"><button type="submit">Enroll</button></form>
      <?php elseif ($in || $user['role'] !== 'student'): ?>
        <a href="<?= e(url('/course')) ?>?id=<?= (int)$c['id'] ?>">Open</a>
      <?php endif; ?>
    </div>
  </li>
<?php endforeach; ?></ul><?php endif; ?>
