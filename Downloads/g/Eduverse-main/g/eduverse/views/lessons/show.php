<?php $title = $lesson['title']; ?>
<p><a href="<?= e(url('/course')) ?>?id=<?= (int)$course['id'] ?>">&larr; <?= e($course['code']) ?></a></p>
<h1><?= e($lesson['title']) ?></h1>
<p class="muted"><?= e($lesson['chapter']) ?><?= $lesson['category'] ? ' | ' . e($lesson['category']) : '' ?></p>
<?php if ($lesson['file_path']): ?><p><a href="<?= e(url('/download')) ?>?type=lesson&amp;id=<?= (int)$lesson['id'] ?>">Download <?= e($lesson['file_name']) ?></a></p><?php endif; ?>

<section><h2>Quiz</h2>
<?php if (!$exercises): ?><p class="empty">No quiz questions yet.</p>
<?php elseif ($user['role'] === 'student'): ?>
  <p><?= count($exercises) ?> multiple-choice questions, graded automatically. <a class="button" href="<?= e(url('/quiz')) ?>?lesson=<?= (int)$lesson['id'] ?>">Start quiz</a></p>
  <?php if ($history): ?><h3>Recent attempts</h3><ul class="plain"><?php foreach ($history as $h): ?>
    <li><a href="<?= e(url('/quiz/result')) ?>?id=<?= (int)$h['id'] ?>"><?= (int)$h['score'] ?>/<?= (int)$h['num_items'] ?> (<?= pct((int)$h['score'], (int)$h['num_items']) ?>%)</a> <span class="muted"><?= e($h['submitted_at']) ?></span></li>
  <?php endforeach; ?></ul><?php endif; ?>
<?php else: ?>
  <ol><?php foreach ($exercises as $x): ?><li><?= e($x['question']) ?><br><span class="muted">Answer: <?= e($x['answer']) ?></span></li><?php endforeach; ?></ol>
<?php endif; ?>
<?php if ($manage): ?>
<details><summary>Add a question</summary>
<form method="post" action="<?= e(url('/lecturer/exercise/create')) ?>" class="stack narrow"><?= csrf_field() ?>
  <input type="hidden" name="lesson_id" value="<?= (int)$lesson['id'] ?>">
  <label>Question <textarea name="question" required rows="2" maxlength="1000"></textarea></label>
  <label>Choice A <input name="choice_a" required maxlength="255"></label>
  <label>Choice B <input name="choice_b" required maxlength="255"></label>
  <label>Choice C <input name="choice_c" required maxlength="255"></label>
  <label>Choice D <input name="choice_d" required maxlength="255"></label>
  <label>Correct answer <select name="answer" required><option>A</option><option>B</option><option>C</option><option>D</option></select></label>
  <button type="submit">Add question</button>
</form></details><?php endif; ?>
</section>
