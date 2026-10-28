<?php $title = 'Error ' . (int)$code; ?>
<h1><?= (int)$code ?></h1>
<p><?= e($message ?: 'Something went wrong.') ?></p>
<p><a href="<?= e(url('/')) ?>">Back to dashboard</a></p>
