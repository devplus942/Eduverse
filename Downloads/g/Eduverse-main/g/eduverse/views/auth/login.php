<?php $title = 'Log in'; ?>
<h1>Log in</h1>
<form method="post" action="<?= e(url('/login')) ?>" class="stack narrow">
  <?= csrf_field() ?>
  <label>Email <input type="email" name="email" required autocomplete="username"></label>
  <label>Password <input type="password" name="password" required autocomplete="current-password"></label>
  <button type="submit">Log in</button>
  <p>New student? <a href="<?= e(url('/register')) ?>">Create an account</a>. Lecturer accounts are created by an administrator.</p>
</form>
