<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAuth('owner');
verifyCsrf();

$new     = $_POST['new_password']     ?? '';
$confirm = $_POST['confirm_password'] ?? '';

if (strlen($new) < 6) {
    setFlash('error', 'Password baru minimal 6 karakter.');
    redirect('/owner/settings');
}
if ($new !== $confirm) {
    setFlash('error', 'Konfirmasi password tidak cocok.');
    redirect('/owner/settings');
}

$targetId = (int)($_POST['target_user_id'] ?? 0);
if (!$targetId) {
    setFlash('error', 'Pilih user terlebih dahulu.');
    redirect('/owner/settings');
}

$targetUser = dbSelectOne("SELECT * FROM users WHERE id=?", [$targetId]);
if (!$targetUser) {
    setFlash('error', 'User tidak ditemukan.');
    redirect('/owner/settings');
}

dbRun("UPDATE users SET password=?, updated_at=datetime('now') WHERE id=?",
    [password_hash($new, PASSWORD_DEFAULT), $targetId]);

setFlash('success', "Password user '{$targetUser->username}' ({$targetUser->role}) berhasil direset!");
redirect('/owner/settings');
