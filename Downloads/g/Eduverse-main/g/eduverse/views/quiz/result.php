<?php $title = 'Quiz result'; ?>
<h1>Result: <?= (int)$attempt['score'] ?> / <?= (int)$attempt['num_items'] ?> (<?= pct((int)$attempt['score'], (int)$attempt['num_items']) ?>%)</h1>
<p><a href="<?= e(url('/lesson')) ?>?id=<?= (int)$lesson['id'] ?>">&larr; <?= e($lesson['title']) ?></a></p>
<ol><?php foreach ($rows as $r): ?>
  <li><?= e($r['question']) ?><br>
    <?php if ($r['is_correct']): ?><span class="good">Correct (<?= e($r['chosen']) ?>)</span>
    <?php else: ?><span class="bad">Your answer: <?= e($r['chosen'] ?? 'none') ?>. Correct: <?= e($r['answer']) ?></span><?php endif; ?></li>
<?php endforeach; ?></ol>
