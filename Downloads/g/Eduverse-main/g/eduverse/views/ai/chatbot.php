<?php $title = 'AI tutor'; ?>
<h1>AI study tutor</h1>
<?php if ($provider->isMock()): ?>
  <p class="flash warn">MOCK mode: no AI provider is configured. Replies below are fixed placeholders, not AI generated.</p>
<?php else: ?>
  <p class="muted">Answers come from the <?= e($provider->name()) ?> model configured by your administrator. They can be wrong; check important facts against your course material.</p>
<?php endif; ?>
<div id="convo" class="convo" aria-live="polite">
<?php if (!$history): ?><p class="empty">Ask your first question below.</p><?php endif; ?>
<?php foreach ($history as $h): ?>
  <div class="q"><?= e($h['prompt']) ?></div>
  <?php if ($h['response'] !== null): ?>
    <div class="a"><?= $h['is_mock'] ? '<span class="tag">MOCK</span> ' : '' ?><span class="answer-text"><?= e($h['response']) ?></span></div>
  <?php else: ?><div class="a err">No answer (<?= e($h['status']) ?>).</div><?php endif; ?>
<?php endforeach; ?>
</div>
<form method="post" action="<?= e(url('/chatbot/ask')) ?>" class="stack"><?= csrf_field() ?>
  <label>Your question <textarea id="question" name="question" rows="3" required maxlength="2000"></textarea></label>
  <div class="actions">
    <button type="submit">Ask</button>
    <button type="button" class="ghost" id="stt" hidden>Speak question</button>
    <button type="button" class="ghost" id="tts" hidden>Read last answer aloud</button>
  </div>
</form>
