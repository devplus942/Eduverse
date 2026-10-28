<?php $title = $course['code']; $cid = (int)$course['id']; ?>
<h1><?= e($course['code']) ?>: <?= e($course['title']) ?></h1>
<p><?= e($course['description']) ?></p>
<p class="actions"><a href="<?= e(url('/forum')) ?>?course=<?= $cid ?>">Discussion forum</a> <a href="<?= e(url('/chat')) ?>?course=<?= $cid ?>">Live chat</a>
<?php if ($manage): ?><a href="<?= e(url('/lecturer/analytics')) ?>?course=<?= $cid ?>">Student analytics</a><?php endif; ?></p>

<section><h2>Lessons</h2>
<?php if (!$lessons): ?><p class="empty">No lessons yet.</p><?php else: ?>
<ul class="plain"><?php foreach ($lessons as $l): ?>
  <li><span class="muted"><?= e($l['chapter']) ?></span> <a href="<?= e(url('/lesson')) ?>?id=<?= (int)$l['id'] ?>"><?= e($l['title']) ?></a>
    <?php if ($l['category']): ?><span class="muted">[<?= e($l['category']) ?>]</span><?php endif; ?>
    <?php if ($l['file_path']): ?> <a href="<?= e(url('/download')) ?>?type=lesson&amp;id=<?= (int)$l['id'] ?>">Download</a><?php endif; ?>
    <span class="muted"><?= (int)$l['exercise_count'] ?> quiz questions</span></li>
<?php endforeach; ?></ul><?php endif; ?>
<?php if ($manage): ?>
<details><summary>Add a lesson</summary>
<form method="post" action="<?= e(url('/lecturer/lesson/create')) ?>" enctype="multipart/form-data" class="stack narrow"><?= csrf_field() ?>
  <input type="hidden" name="course_id" value="<?= $cid ?>">
  <label>Chapter <input name="chapter" required maxlength="60"></label>
  <label>Title <input name="title" required maxlength="190"></label>
  <label>Category <input name="category" maxlength="60" placeholder="Lecture note, Video, Reading"></label>
  <label>File (<?= e(implode(', ', Upload::EXT)) ?>; max <?= e(Env::get('MAX_UPLOAD_MB', '20')) ?> MB) <input type="file" name="file"></label>
  <button type="submit">Add lesson</button>
</form></details><?php endif; ?>
</section>

<section><h2>Assignments</h2>
<?php if (!$assignments): ?><p class="empty">No assignments yet.</p><?php else: ?>
<ul class="plain"><?php foreach ($assignments as $a): ?>
  <li><a href="<?= e(url('/assignment')) ?>?id=<?= (int)$a['id'] ?>"><?= e($a['title']) ?></a> <span class="muted">due <?= e($a['due_at']) ?></span>
  <?php if ($manage): ?> <a href="<?= e(url('/lecturer/submissions')) ?>?assignment=<?= (int)$a['id'] ?>">Submissions</a><?php endif; ?></li>
<?php endforeach; ?></ul><?php endif; ?>
<?php if ($manage): ?>
<details><summary>Create an assignment</summary>
<form method="post" action="<?= e(url('/lecturer/assignment/create')) ?>" class="stack narrow"><?= csrf_field() ?>
  <input type="hidden" name="course_id" value="<?= $cid ?>">
  <label>Title <input name="title" required maxlength="190"></label>
  <label>Instructions <textarea name="instructions" rows="4" maxlength="5000"></textarea></label>
  <label>Deadline <input type="datetime-local" name="due_at" required></label>
  <button type="submit">Create and notify students</button>
</form></details><?php endif; ?>
</section>

<section><h2>Live classes (Jitsi Meet)</h2>
<?php if (!$sessions): ?><p class="empty">No classes scheduled.</p><?php else: ?>
<ul class="plain"><?php foreach ($sessions as $s): ?>
  <li><?= e($s['starts_at']) ?>: <?= e($s['title']) ?> <a href="<?= e($s['url']) ?>" target="_blank" rel="noopener noreferrer">Join</a></li>
<?php endforeach; ?></ul><?php endif; ?>
<?php if ($manage): ?>
<details><summary>Schedule a class</summary>
<form method="post" action="<?= e(url('/lecturer/session/create')) ?>" class="stack narrow"><?= csrf_field() ?>
  <input type="hidden" name="course_id" value="<?= $cid ?>">
  <label>Title <input name="title" required maxlength="190"></label>
  <label>Start time <input type="datetime-local" name="starts_at" required></label>
  <button type="submit">Schedule and notify students</button>
</form></details><?php endif; ?>
</section>
