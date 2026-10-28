<?php $title = 'Submissions'; ?>
<p><a href="<?= e(url('/course')) ?>?id=<?= (int)$course['id'] ?>">&larr; <?= e($course['code']) ?></a></p>
<h1>Submissions: <?= e($assignment['title']) ?></h1>
<?php if ($ai->isMock()): ?><p class="flash warn">AI mode: MOCK. No AI provider is configured, so "AI feedback" is a labelled placeholder, not real analysis.</p>
<?php else: ?><p class="muted">AI provider: <?= e($ai->name()) ?>. AI feedback is advisory; you give the final grade.</p><?php endif; ?>

<section><h2>Plagiarism check</h2>
<p class="muted">Compares typed answers pairwise using word 3-gram overlap (threshold <?= e($threshold) ?>). A high score is a prompt to read both answers, not proof of copying. Only typed text, or text extracted from .txt/.docx uploads, is analysed (PDF is not).</p>
<form method="post" action="<?= e(url('/lecturer/plagiarism')) ?>"><?= csrf_field() ?><input type="hidden" name="assignment_id" value="<?= (int)$assignment['id'] ?>"><button type="submit">Run check</button></form>
<?php if ($reports): ?><ul class="plain"><?php foreach ($reports as $r): ?>
  <li><?= e($r['a_name']) ?> and <?= e($r['b_name']) ?>: <strong><?= e(round((float)$r['similarity'] * 100)) ?>%</strong> similar</li>
<?php endforeach; ?></ul><?php endif; ?>
</section>

<section><h2>Student work</h2>
<?php if (!$subs): ?><p class="empty">No submissions yet.</p><?php endif; ?>
<?php foreach ($subs as $s): ?>
<article class="sub">
  <h3><?= e($s['student']) ?> <span class="muted"><?= e($s['submitted_at']) ?></span></h3>
  <?php if ($s['text_content']): ?><p class="pre"><?= e($s['text_content']) ?></p><?php endif; ?>
  <?php if ($s['file_path']): ?><p><a href="<?= e(url('/download')) ?>?type=submission&amp;id=<?= (int)$s['id'] ?>"><?= e($s['file_name']) ?></a></p><?php endif; ?>
  <?php if ($s['ai_feedback']): ?>
    <div class="ai"><strong><?= $s['ai_is_mock'] ? 'MOCK placeholder (not AI generated)' : 'AI feedback (' . e($s['ai_provider']) . ')' ?></strong><p class="pre"><?= e($s['ai_feedback']) ?></p></div>
  <?php endif; ?>
  <form method="post" action="<?= e(url('/lecturer/ai-feedback')) ?>" class="inline"><?= csrf_field() ?><input type="hidden" name="submission_id" value="<?= (int)$s['id'] ?>"><button type="submit" class="ghost">Get AI feedback</button></form>
  <form method="post" action="<?= e(url('/lecturer/grade')) ?>" class="stack narrow"><?= csrf_field() ?>
    <input type="hidden" name="submission_id" value="<?= (int)$s['id'] ?>">
    <label>Grade (0-100) <input name="grade" type="number" min="0" max="100" step="0.5" required value="<?= e($s['lecturer_grade']) ?>"></label>
    <label>Comment <textarea name="comment" rows="2" maxlength="2000"><?= e($s['lecturer_comment']) ?></textarea></label>
    <button type="submit">Save grade</button>
  </form>
</article>
<?php endforeach; ?>
</section>
