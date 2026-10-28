<?php $title = 'Course report'; ?>
<p class="noprint"><a href="<?= e(url('/lecturer/analytics')) ?>?course=<?= (int)$course['id'] ?>">&larr; Analytics</a>
  <button type="button" data-print>Print / Save as PDF</button>
  <a class="button" href="<?= e(url('/lecturer/export')) ?>?course=<?= (int)$course['id'] ?>">Download CSV (opens in Excel)</a></p>
<h1>Course report: <?= e($course['code']) ?> <?= e($course['title']) ?></h1>
<p class="muted">Generated <?= e($generated) ?>. Figures are computed from stored quiz attempts, submissions and grades only.</p>
<dl class="facts">
  <dt>Enrolled students</dt><dd><?= count($students) ?></dd>
  <dt>Class average (students with quiz attempts)</dt><dd><?= $classAvg === null ? 'n/a' : e($classAvg) . '%' ?></dd>
  <dt>Flagged below <?= e(Analytics::STRUGGLING_BELOW) ?>%</dt><dd><?= count(array_filter($students, fn($s) => $s['struggling'])) ?></dd>
</dl>
<h2>Students</h2>
<?php if (!$students): ?><p class="empty">No students are enrolled.</p><?php else: ?>
<div class="scroll"><table>
  <thead><tr><th>Student</th><th>Quiz attempts</th><th>Average quiz</th><th>Submissions</th><th>Flag</th></tr></thead>
  <tbody><?php foreach ($students as $s): ?>
    <tr><td><?= e($s['name']) ?></td><td><?= (int)$s['attempts'] ?></td><td><?= $s['avg'] === null ? 'no attempts' : e($s['avg']) . '%' ?></td>
    <td><?= (int)$s['submissions'] ?></td><td><?= $s['struggling'] ? 'Needs support' : '' ?></td></tr>
  <?php endforeach; ?></tbody>
</table></div><?php endif; ?>
<h2>Assignments</h2>
<?php if (!$assignments): ?><p class="empty">No assignments.</p><?php else: ?>
<table><thead><tr><th>Title</th><th>Due</th><th>Submitted</th><th>Graded</th></tr></thead>
<tbody><?php foreach ($assignments as $a): ?><tr><td><?= e($a['title']) ?></td><td><?= e($a['due_at']) ?></td><td><?= (int)$a['submitted'] ?></td><td><?= (int)$a['graded'] ?></td></tr><?php endforeach; ?></tbody></table><?php endif; ?>
<h2>Most-missed questions</h2>
<?php if (!$missed): ?><p class="empty">No incorrect answers recorded.</p><?php else: ?>
<ol><?php foreach ($missed as $m): ?><li><?= e($m['question']) ?> <span class="muted">(<?= e($m['lesson']) ?>: missed <?= (int)$m['missed'] ?> of <?= (int)$m['answered'] ?>)</span></li><?php endforeach; ?></ol><?php endif; ?>
