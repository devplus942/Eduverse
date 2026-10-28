<?php $title = $assignment['title']; ?>
<p><a href="<?= e(url('/course')) ?>?id=<?= (int)$course['id'] ?>">&larr; <?= e($course['code']) ?></a></p>
<h1><?= e($assignment['title']) ?></h1>
<p class="muted">Due <?= e($assignment['due_at']) ?> <?= $open ? '' : '(closed)' ?></p>
<p class="pre"><?= e($assignment['instructions']) ?></p>
<?php if ($manage): ?>
  <p><a class="button" href="<?= e(url('/lecturer/submissions')) ?>?assignment=<?= (int)$assignment['id'] ?>">View submissions</a></p>
<?php elseif ($user['role'] === 'student'): ?>
  <?php if ($sub): ?>
  <section><h2>Your submission</h2>
    <p class="muted">Submitted <?= e($sub['submitted_at']) ?></p>
    <?php if ($sub['file_path']): ?><p><a href="<?= e(url('/download')) ?>?type=submission&amp;id=<?= (int)$sub['id'] ?>"><?= e($sub['file_name']) ?></a></p><?php endif; ?>
    <?php if ($sub['lecturer_grade'] !== null): ?><p><strong>Grade: <?= e($sub['lecturer_grade']) ?> / 100</strong></p>
      <?php if ($sub['lecturer_comment']): ?><p class="pre"><?= e($sub['lecturer_comment']) ?></p><?php endif; ?>
    <?php else: ?><p class="muted">Not graded yet.</p><?php endif; ?>
  </section><?php endif; ?>
  <?php if ($open): ?>
  <section><h2><?= $sub ? 'Resubmit' : 'Submit' ?></h2>
  <form method="post" action="<?= e(url('/assignment/submit')) ?>" enctype="multipart/form-data" class="stack narrow"><?= csrf_field() ?>
    <input type="hidden" name="assignment_id" value="<?= (int)$assignment['id'] ?>">
    <label>Typed answer (used for plagiarism check and AI feedback; a .txt or .docx upload is also read) <textarea name="text_content" rows="8" maxlength="20000"><?= e($sub['text_content'] ?? '') ?></textarea></label>
    <label>Attach a file (optional) <input type="file" name="file"></label>
    <button type="submit">Save submission</button>
  </form></section>
  <?php endif; ?>
<?php endif; ?>
