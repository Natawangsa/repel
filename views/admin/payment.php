<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
requireAuth('admin');

$orderId = trim($_GET['order_id'] ?? '');
$order   = null;

if ($orderId) {
    // Akses dari dashboard (tombol PAYMENT) — cari order spesifik
    $order = dbSelectOne("
        SELECT o.*, c.name AS customer_name, c.phone
        FROM orders o JOIN customers c ON o.customer_id = c.id
        WHERE o.order_id = ?
    ", [$orderId]);
}

// Daftar order yang belum lunas (untuk sidebar payment — tanpa order_id di URL)
$unpaidOrders = dbSelect("
    SELECT o.order_id, c.name AS customer_name, o.payment_status, o.total_price, o.remaining_balance
    FROM orders o JOIN customers c ON o.customer_id = c.id
    WHERE o.payment_status IN ('unpaid','partial')
    AND o.status NOT IN ('cancelled')
    ORDER BY o.updated_at DESC
");

layoutStart('Payment', 'payment');
?>
<h1 class="page-title">PAYMENT</h1>

<div style="display:grid;grid-template-columns:<?= $order ? '1fr 320px' : '1fr' ?>;gap:20px;align-items:start;">

  <!-- Form Payment -->
  <div>
    <?php if (!$order): ?>
    <div class="card">
      <p style="color:#9ca3af;text-align:center;padding:16px;">
        Pilih order dari daftar di samping, atau cari order:
      </p>
      <form method="GET" action="/admin/payment">
        <div style="display:flex;gap:8px;">
          <input type="text" name="order_id" class="form-control"
                 placeholder="Masukkan Order ID (contoh: HF10001)">
          <button type="submit" class="btn btn-purple" style="white-space:nowrap;">Cari</button>
        </div>
      </form>
    </div>
    <?php else: ?>
    <div class="card" style="max-width:580px;">
      <!-- Info Order -->
      <div style="background:#f9fafb;border-radius:10px;padding:14px 16px;margin-bottom:20px;">
        <div style="font-size:17px;font-weight:800;margin-bottom:4px;">
          <?= h($order->order_id) ?> — <?= h($order->customer_name) ?>
        </div>
        <div style="font-size:13px;color:#6B7280;">
          <?= h($order->phone) ?> &nbsp;|&nbsp; <?= h($order->product_type) ?>
          <?php if ($order->panjang): ?>&nbsp;|&nbsp; <?= h($order->panjang) ?>×<?= h($order->lebar) ?> M<?php endif; ?>
        </div>
        <div style="margin-top:8px;">
          Status bayar: <?= payBadge($order->payment_status) ?>
          <?php if ($order->total_price > 0): ?>
          &nbsp;| Total: <strong><?= formatRp($order->total_price) ?></strong>
          &nbsp;| Sisa: <strong style="color:#ef4444;"><?= formatRp($order->remaining_balance) ?></strong>
          <?php endif; ?>
        </div>
      </div>

      <h3 class="card-title">Input Pembayaran</h3>

      <form method="POST" action="/admin/payment/store">
        <input type="hidden" name="_token" value="<?= csrfToken() ?>">
        <input type="hidden" name="order_id" value="<?= h($order->order_id) ?>">

        <div class="form-group">
          <label class="form-label">Total Harga (Rp)</label>
          <input name="total_price" id="totalInput" type="number" step="1" min="0"
                 class="form-control" placeholder="contoh: 18500"
                 value="<?= h($order->total_price ?: '') ?>"
                 oninput="calcRem()" required>
          <div style="font-size:11px;color:#9ca3af;margin-top:3px;">Bisa desimal, contoh: 10500, 18845, 75000</div>
        </div>

        <div class="form-group">
          <label class="form-label">Jumlah Dibayar (Rp)</label>
          <input name="down_payment" id="dpInput" type="number" step="1" min="0"
                 class="form-control" placeholder="contoh: 10000"
                 value="<?= h($order->down_payment ?: '') ?>"
                 oninput="calcRem()">
          <div style="font-size:11px;color:#9ca3af;margin-top:3px;">Kosongkan jika belum bayar</div>
        </div>

        <div class="form-group">
          <label class="form-label">Sisa Pembayaran (Rp)</label>
          <input id="remDisplay" type="text" class="form-control" readonly
                 style="background:#f3f4f6;font-weight:700;color:#ef4444;"
                 value="<?= $order->remaining_balance > 0 ? number_format($order->remaining_balance, 0, ',', '.') : '0' ?>">
        </div>

        <div class="form-group">
          <label class="form-label">Metode Pembayaran</label>
          <div style="display:flex;gap:20px;margin-top:8px;">
            <?php foreach (['cash'=>'💵 Cash','transfer'=>'🏦 Transfer','term'=>'📋 Term'] as $val=>$lbl): ?>
            <label style="display:flex;align-items:center;gap:6px;font-size:13px;cursor:pointer;">
              <input type="radio" name="payment_method" value="<?= $val ?>"
                     <?= ($order->payment_method ?: 'cash') === $val ? 'checked' : '' ?>>
              <?= $lbl ?>
            </label>
            <?php endforeach; ?>
          </div>
        </div>

        <div style="display:flex;gap:12px;">
          <a href="/admin/payment" class="btn btn-outline btn-lg" style="flex:1;text-align:center;">← Kembali</a>
          <button type="submit" class="btn btn-purple btn-lg" style="flex:2;">💾 Simpan Payment</button>
        </div>
      </form>
    </div>
    <?php endif; ?>
  </div>

  <!-- Daftar Order Belum Lunas -->
  <div class="card">
    <h3 class="card-title" style="font-size:14px;">Order Belum Lunas</h3>
    <?php if ($unpaidOrders): ?>
    <div style="display:flex;flex-direction:column;gap:6px;">
      <?php foreach ($unpaidOrders as $u): ?>
      <a href="/admin/payment?order_id=<?= h($u->order_id) ?>"
         style="display:block;padding:10px 12px;border-radius:8px;border:2px solid <?= ($order && $order->order_id === $u->order_id) ? '#6B4EFF' : '#e5e7eb' ?>;text-decoration:none;color:inherit;transition:border-color .15s;"
         onmouseover="this.style.borderColor='#6B4EFF'" onmouseout="this.style.borderColor='<?= ($order && $order->order_id === $u->order_id) ? '#6B4EFF' : '#e5e7eb' ?>'">
        <div style="font-weight:700;font-size:13px;"><?= h($u->order_id) ?></div>
        <div style="font-size:12px;color:#6B7280;"><?= h($u->customer_name) ?></div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:4px;">
          <?= payBadge($u->payment_status) ?>
          <?php if ($u->remaining_balance > 0): ?>
          <span style="font-size:11px;color:#ef4444;font-weight:700;">Sisa <?= formatRp($u->remaining_balance) ?></span>
          <?php endif; ?>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;color:#9ca3af;font-size:12px;padding:24px 0;">
      Semua order sudah lunas 🎉
    </div>
    <?php endif; ?>
  </div>

</div>

<script>
function calcRem() {
  const total = parseFloat(document.getElementById('totalInput')?.value) || 0;
  const dp    = parseFloat(document.getElementById('dpInput')?.value) || 0;
  const rem   = Math.max(0, total - dp);
  const el    = document.getElementById('remDisplay');
  if (el) el.value = rem.toLocaleString('id-ID');
}
</script>
<?php layoutEnd(); ?>
