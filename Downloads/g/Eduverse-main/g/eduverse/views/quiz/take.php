<?php $title = 'Quiz'; ?>
<h1>Quiz: <?= e($lesson['title']) ?></h1>
<form method="post" action="<?= e(url('/quiz/submit')) ?>" class="stack"><?= csrf_field() ?>
  <input type="hidden" name="lesson_id" value="<?= (int)$lesson['id'] ?>">
  <?php foreach ($exercises as $i => $x): ?>
  <fieldset><legend><?= $i + 1 ?>. <?= e($x['question']) ?></legend>
    <?php foreach (['A' => 'choice_a', 'B' => 'choice_b', 'C' => 'choice_c', 'D' => 'choice_d'] as $L => $col): ?>
      <label class="choice"><input type="radio" name="q[<?= (int)$x['id'] ?>]" value="<?= $L ?>"> <?= $L ?>. <?= e($x[$col]) ?></label>
    <?php endforeach; ?>
  </fieldset>
  <?php endforeach; ?>
  <button type="submit">Submit answers</button>
</form>
