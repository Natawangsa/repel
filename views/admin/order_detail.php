<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
requireAuth('admin');

$orderId = trim($_GET['order_id'] ?? '');
$order   = dbSelectOne("
    SELECT o.*, c.name AS customer_name, c.phone, c.customer_id AS cust_code, c.is_member
    FROM orders o JOIN customers c ON o.customer_id = c.id
    WHERE o.order_id = ?
", [$orderId]);

if (!$order) { setFlash('error','Order tidak ditemukan.'); redirect('/admin/dashboard'); }

layoutStart('Detail Order', 'order');
?>
<h1 class="page-title">DETAIL ORDER — <?= h($order->order_id) ?></h1>
<div class="detail-grid">
  <div class="detail-panel">
    <div class="detail-panel-title">Customer</div>
    <div class="detail-row"><span class="detail-key">ID</span><span class="detail-val"><?= h($order->cust_code) ?></span></div>
    <div class="detail-row"><span class="detail-key">Nama</span><span class="detail-val"><?= h($order->customer_name) ?></span></div>
    <div class="detail-row"><span class="detail-key">Phone</span><span class="detail-val"><?= h($order->phone) ?></span></div>
    <div class="detail-row"><span class="detail-key">Member</span><span class="detail-val"><?= $order->is_member ? '⭐ Ya' : 'Tidak' ?></span></div>
  </div>
  <div class="detail-panel">
    <div class="detail-panel-title">Order</div>
    <div class="detail-row"><span class="detail-key">Order ID</span><span class="detail-val"><?= h($order->order_id) ?></span></div>
    <div class="detail-row"><span class="detail-key">Produk</span><span class="detail-val"><?= h($order->product_type) ?></span></div>
    <div class="detail-row"><span class="detail-key">Ukuran</span><span class="detail-val"><?= h($order->panjang) ?>×<?= h($order->lebar) ?> M</span></div>
    <div class="detail-row"><span class="detail-key">Bahan</span><span class="detail-val"><?= h($order->bahan) ?></span></div>
    <div class="detail-row"><span class="detail-key">Qty</span><span class="detail-val"><?= h($order->quantity) ?></span></div>
    <div class="detail-row"><span class="detail-key">Finishing</span><span class="detail-val"><?= h($order->finishing_type) ?></span></div>
    <div class="detail-row"><span class="detail-key">Deadline</span><span class="detail-val"><?= h($order->deadline) ?></span></div>
    <div class="detail-row"><span class="detail-key">Status</span><span class="detail-val"><?= statusBadge($order->status) ?></span></div>
  </div>
  <div class="detail-panel">
    <div class="detail-panel-title">Payment</div>
    <div class="detail-row"><span class="detail-key">Total</span><span class="detail-val"><?= formatRp($order->total_price) ?></span></div>
    <div class="detail-row"><span class="detail-key">DP</span><span class="detail-val"><?= formatRp($order->down_payment) ?></span></div>
    <div class="detail-row"><span class="detail-key">Sisa</span><span class="detail-val"><?= formatRp($order->remaining_balance) ?></span></div>
    <div class="detail-row"><span class="detail-key">Metode</span><span class="detail-val"><?= strtoupper(h($order->payment_method)) ?></span></div>
    <div class="detail-row"><span class="detail-key">Status</span><span class="detail-val"><?= payBadge($order->payment_status) ?></span></div>
  </div>
</div>
<?php if ($order->design_file): ?>
<div class="card" style="max-width:400px;">
  <h3 class="card-title">File Design</h3>
  <img src="/uploads/<?= h($order->design_file) ?>" alt="Design" style="max-width:100%;border-radius:8px;">
</div>
<?php endif; ?>
<?php if ($order->revision_note): ?>
<div class="card" style="max-width:600px;">
  <h3 class="card-title">Revision Note</h3>
  <p style="font-size:14px;"><?= h($order->revision_note) ?></p>
</div>
<?php endif; ?>
<?php layoutEnd(); ?>
