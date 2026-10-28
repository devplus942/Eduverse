<?php $title = 'Live chat'; ?>
<p><a href="<?= e(url('/course')) ?>?id=<?= (int)$course['id'] ?>">&larr; <?= e($course['code']) ?></a></p>
<h1>Live chat: <?= e($course['title']) ?></h1>
<p class="muted">Messages refresh every few seconds.</p>
<div id="chat-log" class="chatlog" data-course="<?= (int)$course['id'] ?>" aria-live="polite"></div>
<form id="chat-form" class="chatform"><label class="sr">Message <input id="chat-input" maxlength="500" required autocomplete="off"></label><button type="submit">Send</button></form>
<p id="chat-error" class="bad" role="alert"></p>
