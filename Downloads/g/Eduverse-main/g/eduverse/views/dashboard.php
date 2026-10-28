<?php $title = 'Dashboard'; ?>
<h1>Hello, <?= e($user['name']) ?></h1>
<p class="muted">Signed in as <?= e($user['role']) ?>.</p>

<?php if ($user['role'] === 'student'): ?>
  <section>
    <h2>My courses</h2>
    <?php if (!$courses): ?><p class="empty">You are not enrolled in any course yet. <a href="<?= e(url('/courses')) ?>">Browse courses</a>.</p>
    <?php else: ?><ul class="plain"><?php foreach ($courses as $c): ?>
      <li><a href="<?= e(url('/course')) ?>?id=<?= (int)$c['id'] ?>"><strong><?= e($c['code']) ?></strong> <?= e($c['title']) ?></a></li>
    <?php endforeach; ?></ul><?php endif; ?>
  </section>
  <section>
    <h2>Suggested next study steps</h2>
    <p class="muted">Rule-based: lessons where your latest quiz was below 60%, then quizzes you have not tried.</p>
    <?php if (!$recs): ?><p class="empty">Nothing to suggest yet. Quizzes appear here once your lecturers add them.</p>
    <?php else: ?><ul class="plain"><?php foreach ($recs as $r): ?>
      <li><a href="<?= e(url('/lesson')) ?>?id=<?= (int)$r['id'] ?>"><?= e($r['title']) ?></a> <span class="muted">(<?= e($r['course_title']) ?>) <?= e($r['reason']) ?></span></li>
    <?php endforeach; ?></ul><?php endif; ?>
  </section>
  <section>
    <h2>Upcoming classes</h2>
    <?php if (!$sessions): ?><p class="empty">No upcoming classes scheduled.</p>
    <?php else: ?><ul class="plain"><?php foreach ($sessions as $s): ?>
      <li><?= e($s['starts_at']) ?>: <?= e($s['title']) ?> <span class="muted">(<?= e($s['course_title']) ?>)</span>
        <a href="<?= e(Jitsi::url($s['room_name'])) ?>" target="_blank" rel="noopener noreferrer">Join</a></li>
    <?php endforeach; ?></ul><?php endif; ?>
  </section>
<?php elseif ($user['role'] === 'lecturer'): ?>
  <section>
    <h2>My courses</h2>
    <p class="muted">Submissions awaiting a grade: <?= (int)$ungraded ?></p>
    <?php if (!$courses): ?><p class="empty">No courses yet. Create your first below.</p>
    <?php else: ?><ul class="plain"><?php foreach ($courses as $c): ?>
      <li><a href="<?= e(url('/course')) ?>?id=<?= (int)$c['id'] ?>"><strong><?= e($c['code']) ?></strong> <?= e($c['title']) ?></a> <span class="muted"><?= (int)$c['students'] ?> enrolled</span></li>
    <?php endforeach; ?></ul><?php endif; ?>
  </section>
  <section>
    <h2>Create a course</h2>
    <form method="post" action="<?= e(url('/lecturer/course/create')) ?>" class="stack narrow"><?= csrf_field() ?>
      <label>Course code <input name="code" required maxlength="20" placeholder="CSC401"></label>
      <label>Title <input name="title" required maxlength="190"></label>
      <label>Description <textarea name="description" rows="3" maxlength="2000"></textarea></label>
      <button type="submit">Create course</button>
    </form>
  </section>
<?php else: ?>
  <section>
    <h2>Platform totals</h2>
    <dl class="facts"><?php foreach ($counts as $k => $v): ?><dt><?= e(str_replace('_', ' ', $k)) ?></dt><dd><?= (int)$v ?></dd><?php endforeach; ?></dl>
    <p><a href="<?= e(url('/admin/users')) ?>">Manage users</a> | <a href="<?= e(url('/admin/analytics')) ?>">Course overview</a></p>
  </section>
<?php endif; ?>

<section>
  <h2>Notifications</h2>
  <?php if (!$notes): ?><p class="empty">No notifications.</p>
  <?php else: ?><ul class="plain"><?php foreach ($notes as $n): ?><li><?= e($n['message']) ?> <span class="muted"><?= e($n['created_at']) ?></span></li><?php endforeach; ?></ul><?php endif; ?>
</section>
