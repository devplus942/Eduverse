<?php $title = 'Register'; ?>
<h1>Student registration</h1>
<form method="post" action="<?= e(url('/register')) ?>" class="stack narrow">
  <?= csrf_field() ?>
  <label>Full name <input name="name" required maxlength="100" autocomplete="name"></label>
  <label>Email <input type="email" name="email" required autocomplete="email"></label>
  <label>Department (optional) <input name="department" maxlength="120"></label>
  <label>Password (8 to 72 characters) <input type="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password"></label>
  <label>Confirm password <input type="password" name="confirm" required minlength="8" maxlength="72" autocomplete="new-password"></label>
  <button type="submit">Create account</button>
</form>
