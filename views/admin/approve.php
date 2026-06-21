<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
requireAuth('admin');
verifyCsrf();

$orderId = trim($_POST['order_id'] ?? '');
$action  = $_POST['action'] ?? '';
$note    = trim($_POST['revision_note'] ?? '');

if ($action === 'approve') {
    dbUpdate('orders', ['design_status' => 'approved', 'status' => 'approved'], 'order_id = ?', [$orderId]);
    setFlash('success', "Order {$orderId} disetujui!");
} elseif ($action === 'revision') {
    dbUpdate('orders', ['design_status' => 'in_revision', 'status' => 'in_revision', 'revision_note' => $note], 'order_id = ?', [$orderId]);
    setFlash('success', "Revisi dikirim untuk {$orderId}.");
}
redirect('/admin/dashboard');
