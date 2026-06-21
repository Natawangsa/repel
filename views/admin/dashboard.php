<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
requireAuth('admin');

$today = date('Y-m-d');

// Order selesai dari finishing, belum diambil pelanggan
$readyOrders = dbSelect("
    SELECT o.order_id, c.name AS customer_name, c.phone,
           o.payment_status, o.remaining_balance, o.total_price, o.deadline
    FROM orders o JOIN customers c ON o.customer_id = c.id
    WHERE o.status = 'done'
    ORDER BY o.updated_at DESC LIMIT 20
");

// Order yang perlu approval design dari desainer
$needApproval = dbSelect("
    SELECT o.order_id, c.name AS customer_name, c.phone, o.design_file
    FROM orders o JOIN customers c ON o.customer_id = c.id
    WHERE o.design_status = 'need_approval'
    ORDER BY o.updated_at DESC LIMIT 10
");

layoutStart('Dashboard', 'dashboard');
?>
<h1 class="page-title">DASHBOARD</h1>

<!-- Order Siap di Ambil -->
<div class="card">
  <h2 class="card-title text-center">Order Siap di Ambil</h2>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>ID ORDER</th>
          <th>Customer Name</th>
          <th>Phone Number</th>
          <th>Status Bayar</th>
          <th>DONE</th>
        </tr>
      </thead>
      <tbody>
        <?php if ($readyOrders): foreach ($readyOrders as $o):
          $isUrgent = ($o->deadline === $today);
          $isPaid   = ($o->payment_status === 'paid');
        ?>
        <tr <?= $isUrgent ? 'style="background:#fff8e1;"' : '' ?>>
          <td>
            <?= h($o->order_id) ?>
            <?php if ($isUrgent): ?>
            <?= urgentIcon('Deadline hari ini!') ?>
            <?php endif; ?>
          </td>
          <td><?= h($o->customer_name) ?></td>
          <td><?= h($o->phone) ?></td>
          <td><?= payBadge($o->payment_status) ?></td>
          <td>
            <?php if ($isPaid): ?>
            <form method="POST" action="/admin/order/pickup" style="margin:0;">
              <input type="hidden" name="_token" value="<?= csrfToken() ?>">
              <input type="hidden" name="order_id" value="<?= h($o->order_id) ?>">
              <button type="submit" class="btn btn-green" style="font-size:11px;padding:5px 14px;"
                onclick="return confirm('Tandai order <?= h($o->order_id) ?> sudah diambil?')">
                ✓ DONE
              </button>
            </form>
            <?php else: ?>
            <span title="Selesaikan payment dulu"
              style="display:inline-block;padding:5px 14px;background:#e5e7eb;color:#9ca3af;border-radius:6px;font-size:11px;font-weight:700;cursor:not-allowed;">
              ✓ DONE
            </span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr>
          <td colspan="5" class="text-center" style="color:#9ca3af;padding:24px;">
            Belum ada order selesai
          </td>
        </tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Need Approval -->
<div class="card">
  <h2 class="card-title text-center">Need Approval Design</h2>
  <div class="table-wrap">
    <table>
      <thead><tr><th>ID ORDER</th><th>Customer Name</th><th>Phone Number</th><th>Design</th></tr></thead>
      <tbody>
        <?php if ($needApproval): foreach ($needApproval as $o): ?>
        <tr>
          <td><?= h($o->order_id) ?></td>
          <td><?= h($o->customer_name) ?></td>
          <td><?= h($o->phone) ?></td>
          <td>
            <button onclick="openApproval('<?= h($o->order_id) ?>','<?= h($o->design_file ?? '') ?>')"
              style="background:#6B4EFF;color:#fff;font-size:11px;padding:5px 14px;border-radius:5px;cursor:pointer;border:none;font-weight:700;">
              REVIEW
            </button>
          </td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="4" class="text-center" style="color:#9ca3af;padding:24px;">Tidak ada design yang perlu di-approve</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Approval Modal -->
<div id="approvalModal" class="modal-overlay hidden">
  <div class="modal">
    <h2 class="modal-title">NEED APPROVAL</h2>
    <p class="modal-sub" id="modalOrderId"></p>
    <div style="text-align:center;margin-bottom:20px;">
      <img id="modalImg" src="" alt="Design" style="max-width:100%;max-height:260px;border-radius:8px;display:none;">
      <div id="modalNoImg" style="height:160px;background:#f3f4f6;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#9ca3af;">No image uploaded</div>
    </div>
    <div style="display:flex;gap:10px;">
      <button onclick="doAction('revision')" class="btn btn-red btn-lg" style="flex:1;">REVISION</button>
      <button onclick="doAction('approve')"  class="btn btn-green btn-lg" style="flex:1;">APPROVE</button>
    </div>
  </div>
</div>

<!-- Revision Modal -->
<div id="revisionModal" class="modal-overlay hidden">
  <div class="modal">
    <h2 class="modal-title">REVISION NOTE</h2>
    <p class="modal-sub" id="revOrderLabel"></p>
    <form method="POST" action="/admin/approve">
      <input type="hidden" name="_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="order_id" id="revOrderId">
      <input type="hidden" name="action" value="revision">
      <div class="form-group">
        <textarea name="revision_note" class="form-control" placeholder="Tulis catatan revisi..." rows="4" required></textarea>
      </div>
      <button type="submit" class="btn btn-purple btn-full btn-lg">SUBMIT</button>
    </form>
  </div>
</div>

<script>
let currentId = '';
function openApproval(id, file) {
  currentId = id;
  document.getElementById('modalOrderId').textContent = id;
  const img = document.getElementById('modalImg');
  const noImg = document.getElementById('modalNoImg');
  if (file) { img.src = '/uploads/' + file; img.style.display = 'block'; noImg.style.display = 'none'; }
  else { img.style.display = 'none'; noImg.style.display = 'flex'; }
  document.getElementById('approvalModal').classList.remove('hidden');
}
function doAction(action) {
  document.getElementById('approvalModal').classList.add('hidden');
  if (action === 'approve') {
    const f = document.createElement('form');
    f.method = 'POST'; f.action = '/admin/approve';
    f.innerHTML = '<input name="_token" value="<?= csrfToken() ?>"><input name="order_id" value="' + currentId + '"><input name="action" value="approve">';
    document.body.appendChild(f); f.submit();
  } else {
    document.getElementById('revOrderLabel').textContent = currentId;
    document.getElementById('revOrderId').value = currentId;
    document.getElementById('revisionModal').classList.remove('hidden');
  }
}
document.getElementById('approvalModal').addEventListener('click', e => { if (e.target === e.currentTarget) e.currentTarget.classList.add('hidden'); });
document.getElementById('revisionModal').addEventListener('click', e => { if (e.target === e.currentTarget) e.currentTarget.classList.add('hidden'); });
</script>
<?php layoutEnd(); ?>
