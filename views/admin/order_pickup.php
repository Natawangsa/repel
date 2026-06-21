<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAuth('admin');
verifyCsrf();

$orderId = trim($_POST['order_id'] ?? '');
if (!$orderId) {
    setFlash('error', 'Order tidak valid.');
    redirect('/admin/dashboard');
}

// Tandai sudah diambil pelanggan → status picked_up
dbRun("UPDATE orders SET status='picked_up', updated_at=datetime('now') WHERE order_id=?", [$orderId]);
setFlash('success', "Order {$orderId} sudah diambil pelanggan!");
redirect('/admin/dashboard');
