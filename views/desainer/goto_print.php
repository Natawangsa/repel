<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAuth('desainer');
verifyCsrf();

$orderId = trim($_POST['order_id'] ?? '');
$order   = dbSelectOne("SELECT * FROM orders WHERE order_id=?", [$orderId]);

if ($order && $order->design_status === 'approved') {
    // Status langsung 'ready_print' — desainer tidak perlu lihat lagi
    // Filter desainer sudah exclude 'ready_print' sehingga langsung hilang dari list
    dbRun("UPDATE orders SET status='ready_print', updated_at=datetime('now') WHERE order_id=?", [$orderId]);
    setFlash('success', "Order {$orderId} berhasil dikirim ke Operator!");
} else {
    setFlash('error', 'Design harus diapprove terlebih dahulu.');
}
redirect('/desainer/dashboard');
