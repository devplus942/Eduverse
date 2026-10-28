<?php $title = 'Forum'; ?>
<p><a href="<?= e(url('/course')) ?>?id=<?= (int)$course['id'] ?>">&larr; <?= e($course['code']) ?></a></p>
<h1>Forum: <?= e($course['title']) ?></h1>
<?php if (!$threads): ?><p class="empty">No discussions yet. Start one below.</p><?php else: ?>
<ul class="plain"><?php foreach ($threads as $t): ?>
  <li><a href="<?= e(url('/thread')) ?>?id=<?= (int)$t['id'] ?>"><?= e($t['title']) ?></a> <span class="muted">by <?= e($t['author']) ?>, <?= (int)$t['replies'] ?> posts</span></li>
<?php endforeach; ?></ul><?php endif; ?>
<h2>Start a discussion</h2>
<form method="post" action="<?= e(url('/thread/create')) ?>" class="stack narrow"><?= csrf_field() ?>
  <input type="hidden" name="course_id" value="<?= (int)$course['id'] ?>">
  <label>Title <input name="title" required maxlength="190"></label>
  <label>Message <textarea name="body" rows="4" required maxlength="5000"></textarea></label>
  <button type="submit">Post</button>
</form>
