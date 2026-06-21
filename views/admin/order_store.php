<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAuth('admin');
verifyCsrf();

$name        = trim($_POST['name'] ?? '');
$phone       = trim($_POST['phone'] ?? '');
$isMember    = !empty($_POST['is_member']) ? 1 : 0;
$productType = trim($_POST['product_type'] ?? '');
$panjang     = (float)($_POST['panjang'] ?? 0);
$lebar       = (float)($_POST['lebar'] ?? 0);
$bahan       = trim($_POST['bahan'] ?? '');
$qty         = (int)($_POST['quantity'] ?? 1);
$finishing   = trim($_POST['finishing_type'] ?? '');
$deadline    = $_POST['deadline'] ?? null;
$desc        = trim($_POST['description'] ?? '');

if (!$name || !$phone || !$productType) {
    setFlash('error', 'Data tidak lengkap.');
    redirect('/admin/order');
}

// Cari atau buat customer
$cust = dbSelectOne("SELECT * FROM customers WHERE phone = ?", [$phone]);
if (!$cust) {
    $custId   = generateCustomerId();
    $custDbId = dbInsert('customers', [
        'customer_id' => $custId,
        'name'        => $name,
        'phone'       => $phone,
        'is_member'   => $isMember,
    ]);
} else {
    $custDbId = $cust->id;
}

// Upload file design dari pelanggan (jika ada) — semua tipe file diterima
$designFile = null;
if (!empty($_FILES['design_file']['name']) && $_FILES['design_file']['error'] === 0) {
    $file      = $_FILES['design_file'];
    $oriName   = $file['name'];
    $ext       = strtolower(pathinfo($oriName, PATHINFO_EXTENSION));
    $filename  = uniqid() . ($ext ? '.' . $ext : '');
    $uploadDir = __DIR__ . '/../../public/uploads/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
    move_uploaded_file($file['tmp_name'], $uploadDir . $filename);
    $designFile = $filename;
}

// SELALU ke desainer dulu (new_order) — ada atau tidak ada file dari pelanggan
// File dari pelanggan hanya sebagai referensi desainer, bukan skip desainer
$orderId = generateOrderId();
dbInsert('orders', [
    'order_id'       => $orderId,
    'customer_id'    => $custDbId,
    'product_type'   => $productType,
    'panjang'        => $panjang,
    'lebar'          => $lebar,
    'bahan'          => $bahan,
    'quantity'       => $qty,
    'finishing_type' => $finishing,
    'deadline'       => $deadline,
    'description'    => $desc,
    'design_file'    => $designFile,
    'status'         => 'new_order',
    'design_status'  => 'pending',
]);

$msg = $designFile
    ? "Order {$orderId} dibuat! File referensi dari pelanggan tersimpan, diteruskan ke Desainer."
    : "Order {$orderId} dibuat & diteruskan ke Desainer!";

setFlash('success', $msg);
redirect('/admin/dashboard');
