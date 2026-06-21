<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

sessionStart();
if (authUser()) redirect('/' . authUser()['role'] . '/dashboard');

$error = getFlash();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1.0">
<title>Login - HaFI85 Digital Printing</title>
<link rel="stylesheet" href="/css/app.css">
</head>
<body>
<div class="login-page">
  <div class="login-logo">
    <div style="font-size:42px;font-weight:900;line-height:1;letter-spacing:-1px;">
      HaF<span style="color:#E53E3E">I</span>85
    </div>
    <div style="font-size:13px;color:#6B7280;font-weight:600;letter-spacing:1px;margin-top:4px;">
      Digital Printing
    </div>
  </div>
  <div class="login-card">
    <div class="login-title">LOGIN</div>
    <?php if ($error): ?>
    <div class="alert alert-error" style="margin-bottom:16px;"><?= h($error['msg']) ?></div>
    <?php endif; ?>
    <form method="POST" action="/login">
      <input type="hidden" name="_token" value="<?= csrfToken() ?>">
      <div class="login-row">
        <label>Username</label>
        <input type="text" name="username" class="form-control"
               placeholder="Username" value="<?= old('username') ?>" required autofocus>
      </div>
      <div class="login-row">
        <label>Password</label>
        <input type="password" name="password" class="form-control" placeholder="Password" required>
      </div>
      <button type="submit" class="login-btn">LOGIN</button>
    </form>
  </div>
</div>
</body>
</html>
