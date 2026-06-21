<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAuth('admin');
verifyCsrf();

$orderId   = trim($_POST['order_id'] ?? '');
$total     = (float)($_POST['total_price']  ?? 0);
$dp        = (float)($_POST['down_payment'] ?? 0);
$remaining = max(0, $total - $dp);
$method    = $_POST['payment_method'] ?? 'cash';
$payStatus = ($remaining <= 0) ? 'paid' : ($dp > 0 ? 'partial' : 'unpaid');

if (!$orderId) {
    setFlash('error', 'Order tidak valid.');
    redirect('/admin/dashboard');
}

dbRun("UPDATE orders SET
    total_price=?, down_payment=?, remaining_balance=?,
    payment_method=?, payment_status=?, updated_at=datetime('now')
    WHERE order_id=?",
    [$total, $dp, $remaining, $method, $payStatus, $orderId]
);

$msg = $payStatus === 'paid'
    ? "Payment order {$orderId} LUNAS ✓"
    : "Payment order {$orderId} tersimpan (sisa " . number_format($remaining,0,',','.') . ")";

setFlash('success', $msg);
redirect('/admin/dashboard');
