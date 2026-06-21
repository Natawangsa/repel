<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
requireAuth('finishing');

$today     = date('Y-m-d');
$orders    = dbSelect("SELECT o.*, c.name AS customer_name, c.phone FROM orders o JOIN customers c ON o.customer_id=c.id WHERE o.status='finishing' ORDER BY o.is_urgent DESC, o.deadline ASC");
$workId    = $_GET['work'] ?? '';
$workOrder = $workId ? dbSelectOne("SELECT o.*, c.name AS customer_name, c.phone FROM orders o JOIN customers c ON o.customer_id=c.id WHERE o.order_id=?", [$workId]) : null;

layoutStart('Dashboard','dashboard');
?>
<h1 class="page-title">DASHBOARD</h1>
<hr class="page-title-divider">
<div style="display:grid;grid-template-columns:1fr 380px;gap:20px;align-items:start;">
  <div class="card">
    <h3 class="card-title">Order Finishing</h3>
    <div class="work-list">
      <?php if ($orders): foreach ($orders as $o): ?>
      <div class="work-item">
        <div class="work-item-info">
          <h4>
            <?= h($o->order_id) ?>
            <?= $o->is_urgent ? urgentIcon('Urgent') : '' ?>
            <?= ($o->deadline === $today) ? urgentIcon('Deadline hari ini!') : '' ?>
          </h4>
          <p><?= h($o->customer_name) ?> — <?= h($o->product_type) ?></p>
          <p style="font-size:11px;color:#9ca3af;">Deadline: <?= h($o->deadline) ?></p>
        </div>
        <a href="/finishing/dashboard?work=<?= h($o->order_id) ?>" class="btn btn-purple" style="white-space:nowrap;">WORK</a>
      </div>
      <?php endforeach; else: ?>
      <div style="text-align:center;color:#9ca3af;padding:32px;">Tidak ada order finishing</div>
      <?php endif; ?>
    </div>
  </div>
  <div class="card">
    <h3 class="card-title text-center">Work Detail</h3>
    <?php if ($workOrder): ?>
    <div style="margin-bottom:16px;">
      <div style="font-size:18px;font-weight:700;margin-bottom:4px;"><?= h($workOrder->order_id) ?></div>
      <div style="font-size:13px;color:#6B7280;"><?= h($workOrder->customer_name) ?></div>
    </div>
    <div class="detail-row"><span class="detail-key">Produk</span><span class="detail-val"><?= h($workOrder->product_type) ?></span></div>
    <div class="detail-row"><span class="detail-key">Ukuran</span><span class="detail-val"><?= h($workOrder->panjang) ?>×<?= h($workOrder->lebar) ?> M</span></div>
    <div class="detail-row"><span class="detail-key">Bahan</span><span class="detail-val"><?= h($workOrder->bahan) ?></span></div>
    <div class="detail-row"><span class="detail-key">Qty</span><span class="detail-val"><?= h($workOrder->quantity) ?></span></div>
    <div class="detail-row"><span class="detail-key">Finishing</span><span class="detail-val"><?= h($workOrder->finishing_type) ?></span></div>
    <div class="detail-row"><span class="detail-key">Deadline</span><span class="detail-val"><?= h($workOrder->deadline) ?></span></div>
    <div class="detail-row"><span class="detail-key">Phone</span><span class="detail-val"><?= h($workOrder->phone) ?></span></div>
    <?php if ($workOrder->design_file): ?>
    <img src="/uploads/<?= h($workOrder->design_file) ?>" alt="Design" style="width:100%;border-radius:8px;margin:12px 0;">
    <?php endif; ?>
    <form method="POST" action="/finishing/done" style="margin-top:16px;">
      <input type="hidden" name="_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" value="<?= h($workOrder->order_id) ?>">
      <button type="submit" class="btn btn-green btn-full btn-lg"
        onclick="return confirm('Tandai order ini sebagai SELESAI?')">✓ MARK AS DONE</button>
    </form>
    <?php else: ?>
    <div style="text-align:center;color:#9ca3af;padding:32px;">Pilih order dari daftar</div>
    <?php endif; ?>
  </div>
</div>
<?php layoutEnd(); ?>
