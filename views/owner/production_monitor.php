<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
requireAuth('owner');

$filter = $_GET['filter'] ?? 'all';
$sql    = "SELECT o.order_id, c.name AS customer_name, o.status, o.is_urgent, o.created_at FROM orders o JOIN customers c ON o.customer_id=c.id WHERE 1=1";
if ($filter === 'urgent') $sql .= " AND o.is_urgent=1";
if ($filter === 'latest') $sql .= " AND o.created_at >= date('now','-7 day')";
$sql .= " ORDER BY o.is_urgent DESC, o.created_at DESC";
$orders = dbSelect($sql);

layoutStart('Production Monitor','production-monitor');
?>
<h1 class="page-title">PRODUCTION MONITOR</h1>
<hr class="page-title-divider">
<div class="filter-tabs" style="margin-bottom:20px;">
  <?php foreach (['all'=>'ALL','urgent'=>'URGENT','latest'=>'7 HARI TERAKHIR'] as $key=>$lbl): ?>
  <a href="/owner/production-monitor?filter=<?= $key ?>"
     class="filter-tab <?= $filter===$key?'active':'' ?>"><?= $lbl ?></a>
  <?php endforeach; ?>
</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>Order ID</th><th>Customer</th><th>Status</th><th>Tgl Order</th></tr></thead>
      <tbody>
        <?php if ($orders): foreach ($orders as $o): ?>
        <tr>
          <td><?= h($o->order_id) ?><?= $o->is_urgent?urgentIcon('Urgent'):'' ?></td>
          <td><?= h($o->customer_name) ?></td>
          <td><?= statusBadge($o->status) ?></td>
          <td style="font-size:12px;color:#6B7280;"><?= date('d/m/Y', strtotime($o->created_at)) ?></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="4" class="text-center" style="color:#9ca3af;padding:24px;">Tidak ada order</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php layoutEnd(); ?>
