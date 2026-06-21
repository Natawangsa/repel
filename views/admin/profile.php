<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
requireAuth('admin');
$u = authUser();
layoutStart('Profile','profile');
?>
<h1 class="page-title">PROFILE</h1>
<div class="card" style="max-width:400px;">
  <div style="font-size:14px;line-height:2;">
    <div><strong>Username:</strong> <?= h($u['username']) ?></div>
    <div><strong>Role:</strong> Admin</div>
  </div>
</div>
<?php layoutEnd(); ?>
