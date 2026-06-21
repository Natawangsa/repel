<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAuth('desainer');
verifyCsrf();

$orderId = trim($_POST['order_id'] ?? '');

if (empty($_FILES['design_file']['name']) || $_FILES['design_file']['error'] !== 0) {
    setFlash('error', 'Gagal upload — tidak ada file dipilih.');
    redirect('/desainer/order?order_id=' . $orderId);
}

$file      = $_FILES['design_file'];
$oriName   = $file['name'];
$ext       = strtolower(pathinfo($oriName, PATHINFO_EXTENSION));
// Terima SEMUA tipe file — tidak ada filter ekstensi
$filename  = uniqid() . ($ext ? '.' . $ext : '');
$uploadDir = __DIR__ . '/../../public/uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

if (move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
    // Simpan juga nama asli file untuk referensi
    dbRun("UPDATE orders SET design_file=?, updated_at=datetime('now') WHERE order_id=?",
        [$filename, $orderId]);
    setFlash('success', 'Design berhasil diupload: ' . h($oriName));
} else {
    setFlash('error', 'Gagal menyimpan file. Coba lagi.');
}

redirect('/desainer/order?order_id=' . $orderId);
