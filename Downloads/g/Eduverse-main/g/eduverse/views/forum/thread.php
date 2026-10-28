<?php $title = $thread['title']; ?>
<p><a href="<?= e(url('/forum')) ?>?course=<?= (int)$course['id'] ?>">&larr; Forum</a></p>
<h1><?= e($thread['title']) ?></h1>
<?php foreach ($posts as $p): ?>
<article class="post"><p class="muted"><strong><?= e($p['author']) ?></strong> (<?= e($p['role']) ?>), <?= e($p['created_at']) ?></p><p class="pre"><?= e($p['body']) ?></p></article>
<?php endforeach; ?>
<form method="post" action="<?= e(url('/post/create')) ?>" class="stack narrow"><?= csrf_field() ?>
  <input type="hidden" name="thread_id" value="<?= (int)$thread['id'] ?>">
  <?php if ($draft !== ''): ?><p class="flash warn">This draft was suggested by the AI tutor service (or is a labelled MOCK). Check and edit it before posting.</p><?php endif; ?>
  <label>Reply <textarea name="body" rows="4" required maxlength="5000"><?= e($draft) ?></textarea></label>
  <button type="submit">Reply</button>
</form>
<form method="post" action="<?= e(url('/thread/suggest')) ?>" class="stack narrow"><?= csrf_field() ?>
  <input type="hidden" name="thread_id" value="<?= (int)$thread['id'] ?>">
  <button type="submit" class="ghost">Suggest a reply with AI</button>
</form>
