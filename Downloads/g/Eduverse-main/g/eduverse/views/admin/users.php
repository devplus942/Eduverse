<?php $title = 'Users'; ?>
<h1>User management</h1>
<section><h2>Create a user</h2>
<form method="post" action="<?= e(url('/admin/users/create')) ?>" class="stack narrow"><?= csrf_field() ?>
  <label>Name <input name="name" required maxlength="100"></label>
  <label>Email <input type="email" name="email" required></label>
  <label>Department <input name="department" maxlength="120"></label>
  <label>Role <select name="role"><option value="lecturer">Lecturer</option><option value="student">Student</option><option value="admin">Admin</option></select></label>
  <label>Temporary password (8 to 72 characters) <input type="password" name="password" required minlength="8" maxlength="72" autocomplete="new-password"></label>
  <button type="submit">Create user</button>
</form></section>
<section><h2>All users (<?= count($users) ?>)</h2>
<div class="scroll"><table>
  <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th></th></tr></thead>
  <tbody><?php foreach ($users as $u2): ?>
    <tr><td><?= e($u2['name']) ?></td><td><?= e($u2['email']) ?></td><td><?= e($u2['role']) ?></td><td><?= $u2['is_active'] ? 'Active' : 'Disabled' ?></td>
    <td><?php if ((int)$u2['id'] !== (int)$user['id']): ?><form method="post" action="<?= e(url('/admin/users/toggle')) ?>"><?= csrf_field() ?><input type="hidden" name="user_id" value="<?= (int)$u2['id'] ?>"><button type="submit" class="ghost"><?= $u2['is_active'] ? 'Disable' : 'Enable' ?></button></form><?php endif; ?></td></tr>
  <?php endforeach; ?></tbody>
</table></div></section>
