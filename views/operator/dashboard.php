<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
requireAuth('operator');
$userId = authUser()['id'];
$session = getOrCreateSession($userId);
$sessionId = $session->id;

// BUG FIX: tambah status 'ready_print' agar order dari desainer muncul di sini
$today  = date('Y-m-d');
$orders = dbSelect("
    SELECT o.*, c.name AS customer_name
    FROM orders o JOIN customers c ON o.customer_id=c.id
    WHERE o.design_status='approved' AND o.status IN ('approved','new_order','ready_print')
    ORDER BY o.is_urgent DESC, o.deadline ASC
");

// $session already fetched via getOrCreateSession() above
$selectedOrders = [];
if ($session) {
    $selectedOrders = dbSelect("
        SELECT o.order_id FROM print_session_orders pso
        JOIN orders o ON pso.order_id=o.id WHERE pso.session_id=?
    ", [$sessionId]);
}
$selectedIds = array_column(array_map(fn($r)=>(array)$r, $selectedOrders), 'order_id');
$printers = dbSelect("SELECT * FROM printers ORDER BY id ASC");

layoutStart('Dashboard','dashboard');
?>
<h1 class="page-title">DASHBOARD</h1>
<hr class="page-title-divider">
<div style="display:grid;grid-template-columns:1fr 300px;gap:20px;align-items:start;">
  <div class="card">
    <h3 class="card-title">Order Siap Cetak</h3>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Order ID</th><th>Customer</th><th>Produk</th><th>Design</th><th>Status</th><th>Pilih</th></tr></thead>
        <tbody>
          <?php if ($orders): foreach ($orders as $o): $isSel = in_array($o->order_id, $selectedIds); ?>
          <tr>
            <td>
              <?= h($o->order_id) ?>
              <?= $o->is_urgent ? urgentIcon('Urgent') : '' ?>
              <?= ($o->deadline === $today) ? urgentIcon('Deadline hari ini!') : '' ?>
            </td>
            <td><?= h($o->customer_name) ?></td>
            <td><?= h($o->product_type) ?></td>
            <td>
              <?php if ($o->design_file): ?>
              <img src="/uploads/<?= h($o->design_file) ?>" style="width:50px;height:35px;object-fit:cover;border-radius:4px;">
              <?php else: ?><span style="color:#9ca3af;font-size:11px;">No file</span><?php endif; ?>
            </td>
            <td>
              <?php if ($o->status === 'ready_print'): ?>
              <span class="badge" style="background:#6B4EFF;color:#fff;font-size:10px;">SIAP CETAK</span>
              <?php else: ?>
              <span class="badge" style="background:#28a745;color:#fff;font-size:10px;">APPROVED</span>
              <?php endif; ?>
            </td>
            <td>
              <form method="POST" action="/operator/select">
                <input type="hidden" name="_token" value="<?= csrfToken() ?>">
                <input type="hidden" name="order_id" value="<?= h($o->order_id) ?>">
                <input type="hidden" name="action" value="<?= $isSel?'remove':'add' ?>">
                <button type="submit" class="btn <?= $isSel?'btn-red':'btn-purple' ?>" style="padding:5px 12px;font-size:11px;">
                  <?= $isSel?'✕ Remove':'+ Pilih' ?>
                </button>
              </form>
            </td>
          </tr>
          <?php endforeach; else: ?>
          <tr><td colspan="6" class="text-center" style="color:#9ca3af;padding:24px;">Belum ada order siap cetak</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
  <div class="card">
    <h3 class="card-title text-center">Work List</h3>
    <?php if ($selectedOrders): ?>
    <div style="display:flex;flex-direction:column;gap:8px;margin-bottom:16px;">
      <?php foreach ($selectedOrders as $so): ?>
      <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 10px;border:2px solid #e5e7eb;border-radius:8px;font-weight:700;font-size:13px;">
        <?= h($so->order_id) ?>
        <form method="POST" action="/operator/select" style="margin:0;">
          <input type="hidden" name="_token" value="<?= csrfToken() ?>">
          <input type="hidden" name="order_id" value="<?= h($so->order_id) ?>">
          <input type="hidden" name="action" value="remove">
          <button type="submit" style="background:none;border:none;color:#ef4444;font-size:16px;font-weight:900;cursor:pointer;">✕</button>
        </form>
      </div>
      <?php endforeach; ?>
    </div>
    <form method="POST" action="/operator/start">
      <input type="hidden" name="_token" value="<?= csrfToken() ?>">
      <select name="printer_id" class="form-control" style="margin-bottom:10px;font-size:12px;">
        <option value="0">-- Pilih Mesin --</option>
        <?php foreach ($printers as $pr):
          $connLabel = ($pr->connection_type ?? 'lan') === 'usb' ? 'USB' : 'LAN';
        ?>
        <option value="<?= $pr->id ?>"><?= h($pr->name) ?> (<?= h($pr->machine) ?>) [<?= $connLabel ?>]</option>
        <?php endforeach; ?>
      </select>
      <button type="submit" class="btn btn-purple btn-full btn-lg">▶ MULAI PRINT</button>
    </form>
    <?php else: ?>
    <div style="text-align:center;color:#9ca3af;font-size:12px;padding:24px 0;">Pilih order dari daftar untuk memulai</div>
    <?php endif; ?>
  </div>
</div>
<?php layoutEnd(); ?>
