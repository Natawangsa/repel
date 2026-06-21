<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAuth('admin');
verifyCsrf();

$month  = (int)($_POST['month'] ?? date('n'));
$year   = (int)($_POST['year']  ?? date('Y'));
$amount = (float)($_POST['amount'] ?? 0);
$desc   = trim($_POST['description'] ?? '');

if ($amount > 0) {
    dbInsert('expenses', ['amount' => $amount, 'description' => $desc]);
    setFlash('success', 'Pengeluaran berhasil ditambahkan!');
}
redirect("/admin/reports?month={$month}&year={$year}");
