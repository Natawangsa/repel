<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';

sessionStart();
verifyCsrf();

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

$user = dbSelectOne("SELECT * FROM users WHERE username = ?", [$username]);

if ($user && password_verify($password, $user->password)) {
    $_SESSION['user'] = [
        'id'       => $user->id,
        'username' => $user->username,
        'role'     => $user->role,
    ];
    session_regenerate_id(true);
    clearOld();
    redirect('/' . $user->role . '/dashboard');
}

saveOld(['username' => $username]);
setFlash('error', 'Username atau password salah.');
redirect('/login');
