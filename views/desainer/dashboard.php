<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
requireAuth('desainer');

$filter = $_GET['filter'] ?? 'all';
$q      = trim($_GET['q'] ?? '');

// Tampilkan order yang masih perlu dikerjakan desainer
// Sembunyikan yang sudah diteruskan ke operator (printing, finishing, done)
$today  = date('Y-m-d');
$sql    = "SELECT o.*, c.name AS customer_name FROM orders o
           JOIN customers c ON o.customer_id = c.id
           WHERE o.status NOT IN ('ready_print','printing','finishing','done','picked_up','cancelled')";
$params = [];

if ($filter === 'new_order')   { $sql .= " AND o.status='new_order'"; }
if ($filter === 'in_revision') { $sql .= " AND o.status='in_revision'"; }
if ($filter === 'approved')    { $sql .= " AND o.status='approved'"; }

if ($q) {
    $sql     .= " AND (c.name LIKE ? OR o.order_id LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
}
$sql .= " ORDER BY o.is_urgent DESC, o.created_at ASC";
$orders = dbSelect($sql, $params);

layoutStart('Dashboard','dashboard');
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;flex-wrap:wrap;gap:12px;">
  <form method="GET" action="/desainer/dashboard" style="flex:1;min-width:200px;">
    <input type="hidden" name="filter" value="<?= h($filter) ?>">
    <div class="search-wrap" style="margin-bottom:0;">
      <input type="text" name="q" class="search-input" placeholder="Search order..." value="<?= h($q) ?>">
      <button type="submit" class="search-icon" style="background:none;border:none;cursor:pointer;">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/>
        </svg>
      </button>
    </div>
  </form>
  <div class="filter-tabs" style="flex-shrink:0;">
    <?php foreach (['all'=>'ALL','new_order'=>'NEW ORDER','in_revision'=>'IN REVISION','approved'=>'APPROVED'] as $key=>$lbl): ?>
    <a href="?filter=<?= $key ?><?= $q ? '&q='.urlencode($q) : '' ?>"
       class="filter-tab <?= $filter===$key?'active':'' ?>"><?= $lbl ?></a>
    <?php endforeach; ?>
  </div>
</div>

<div class="order-grid">
  <?php if ($orders): foreach ($orders as $o): ?>
  <div class="order-card">
    <div class="order-card-header">
      <span class="order-card-id">
        <?= h($o->order_id) ?>
        <?= $o->is_urgent ? urgentIcon('Urgent') : '' ?>
        <?= ($o->deadline === $today) ? urgentIcon('Deadline hari ini!') : '' ?>
      </span>
      <?= statusBadge($o->status) ?>
    </div>
    <div class="order-card-info">
      <?= h($o->customer_name) ?><br>
      <?= h($o->product_type) ?>
      <?= $o->panjang ? '<br>'.h($o->panjang).'×'.h($o->lebar).' M' : '' ?>
    </div>
    <div class="order-card-img">
      <?php
      $ext2    = $o->design_file ? strtolower(pathinfo($o->design_file, PATHINFO_EXTENSION)) : '';
      $isImg2  = in_array($ext2, ['jpg','jpeg','png','gif','webp','bmp']);
      ?>
      <?php if ($o->design_file && $isImg2): ?>
        <img src="/uploads/<?= h($o->design_file) ?>" alt="Design">
      <?php elseif ($o->design_file): ?>
        <div style="display:flex;flex-direction:column;align-items:center;justify-content:center;height:100%;color:#6B7280;">
          <div style="font-size:28px;">📄</div>
          <div style="font-size:10px;margin-top:4px;"><?= strtoupper(h($ext2)) ?> file</div>
        </div>
      <?php else: ?>
        <span class="no-img">No file</span>
      <?php endif; ?>
    </div>
    <a href="/desainer/order?order_id=<?= h($o->order_id) ?>" class="btn btn-purple btn-full">WORK</a>
  </div>
  <?php endforeach; else: ?>
  <div style="grid-column:1/-1;text-align:center;color:#9ca3af;padding:48px 0;">
    Tidak ada order yang perlu dikerjakan
  </div>
  <?php endif; ?>
</div>
<?php layoutEnd(); ?>
