<?php $title = 'Analytics'; ?>
<p><a href="<?= e(url('/course')) ?>?id=<?= (int)$course['id'] ?>">&larr; <?= e($course['code']) ?></a></p>
<h1>Analytics: <?= e($course['title']) ?></h1>
<p class="muted">Computed from stored quiz attempts and submissions. Students averaging below <?= e(Analytics::STRUGGLING_BELOW) ?>% are flagged by a fixed rule, not a predictive model.</p>
<p><a class="button" href="<?= e(url('/lecturer/report')) ?>?course=<?= (int)$course['id'] ?>">Printable report (PDF)</a> <a class="button" href="<?= e(url('/lecturer/export')) ?>?course=<?= (int)$course['id'] ?>">Export CSV</a></p>
<?php if (!$students): ?><p class="empty">No students are enrolled yet.</p><?php else: ?>
<div class="scroll"><table>
  <thead><tr><th>Student</th><th>Quiz attempts</th><th>Average quiz</th><th>Submissions</th><th>Flag</th></tr></thead>
  <tbody><?php foreach ($students as $s): ?>
    <tr><td><?= e($s['name']) ?><br><span class="muted"><?= e($s['email']) ?></span></td><td><?= (int)$s['attempts'] ?></td>
    <td><?= $s['avg'] === null ? 'no attempts' : e($s['avg']) . '%' ?></td><td><?= (int)$s['submissions'] ?></td>
    <td><?= $s['struggling'] ? '<span class="bad">Needs support</span>' : '' ?></td></tr>
  <?php endforeach; ?></tbody>
</table></div><?php endif; ?>
<h2>Most-missed questions</h2>
<?php if (!$missed): ?><p class="empty">No incorrect answers recorded yet.</p><?php else: ?>
<ol><?php foreach ($missed as $m): ?><li><?= e($m['question']) ?> <span class="muted">(<?= e($m['lesson']) ?>: missed <?= (int)$m['missed'] ?> of <?= (int)$m['answered'] ?>)</span></li><?php endforeach; ?></ol><?php endif; ?>
