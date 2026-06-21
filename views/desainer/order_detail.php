<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
requireAuth('desainer');

$orderId = trim($_GET['order_id'] ?? '');
$order   = dbSelectOne("
    SELECT o.*, c.name AS customer_name, c.phone
    FROM orders o JOIN customers c ON o.customer_id=c.id
    WHERE o.order_id=?
", [$orderId]);
if (!$order) { redirect('/desainer/dashboard'); }

// Cek apakah file bisa ditampilkan sebagai gambar
$imageExts = ['jpg','jpeg','png','gif','webp','bmp'];
$ext       = $order->design_file ? strtolower(pathinfo($order->design_file, PATHINFO_EXTENSION)) : '';
$isImage   = in_array($ext, $imageExts);

layoutStart('Order Detail','dashboard');
?>
<h1 class="page-title">ORDER DETAIL — <?= h($order->order_id) ?></h1>

<div class="detail-grid">
  <div class="detail-panel">
    <div class="detail-panel-title">Customer</div>
    <div class="detail-row"><span class="detail-key">Nama</span><span class="detail-val"><?= h($order->customer_name) ?></span></div>
    <div class="detail-row"><span class="detail-key">Phone</span><span class="detail-val"><?= h($order->phone) ?></span></div>
  </div>
  <div class="detail-panel">
    <div class="detail-panel-title">Order</div>
    <div class="detail-row"><span class="detail-key">Produk</span><span class="detail-val"><?= h($order->product_type) ?></span></div>
    <div class="detail-row"><span class="detail-key">Ukuran</span><span class="detail-val"><?= h($order->panjang) ?>×<?= h($order->lebar) ?> M</span></div>
    <div class="detail-row"><span class="detail-key">Bahan</span><span class="detail-val"><?= h($order->bahan) ?></span></div>
    <div class="detail-row"><span class="detail-key">Qty</span><span class="detail-val"><?= h($order->quantity) ?></span></div>
    <div class="detail-row"><span class="detail-key">Finishing</span><span class="detail-val"><?= h($order->finishing_type) ?></span></div>
    <div class="detail-row"><span class="detail-key">Deadline</span><span class="detail-val"><?= h($order->deadline) ?></span></div>
    <?php if ($order->description): ?>
    <div class="detail-row"><span class="detail-key">Desc</span><span class="detail-val"><?= h($order->description) ?></span></div>
    <?php endif; ?>
  </div>
  <div class="detail-panel">
    <div class="detail-panel-title">Status Design</div>
    <div class="detail-row"><?= statusBadge($order->design_status) ?></div>
    <?php if ($order->revision_note): ?>
    <div style="margin-top:12px;background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:10px;font-size:13px;">
      <strong>Revision Note:</strong><br><?= h($order->revision_note) ?>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- File Referensi dari Pelanggan / Design -->
<div class="card" style="max-width:520px;">
  <h3 class="card-title">File Design</h3>

  <?php if ($order->design_file): ?>
    <?php if ($isImage): ?>
    <!-- Tampilkan preview jika gambar -->
    <img src="/uploads/<?= h($order->design_file) ?>" alt="Design"
         style="max-width:100%;border-radius:8px;margin-bottom:12px;display:block;">
    <?php else: ?>
    <!-- Tampilkan info file jika bukan gambar -->
    <div style="background:#f3f4f6;border-radius:8px;padding:16px;margin-bottom:12px;display:flex;align-items:center;gap:12px;">
      <div style="font-size:32px;">📄</div>
      <div>
        <div style="font-weight:700;font-size:13px;"><?= h($order->design_file) ?></div>
        <div style="font-size:12px;color:#6B7280;margin-top:2px;">File <?= strtoupper(h($ext)) ?></div>
      </div>
    </div>
    <?php endif; ?>

    <!-- Tombol Download file dari pelanggan/admin -->
    <a href="/uploads/<?= h($order->design_file) ?>" download
       class="btn btn-blue btn-full" style="margin-bottom:16px;">
      ⬇ Download File Referensi
    </a>

  <?php else: ?>
    <div style="height:120px;background:#f3f4f6;border-radius:8px;display:flex;align-items:center;justify-content:center;color:#9ca3af;margin-bottom:16px;">
      Belum ada file
    </div>
  <?php endif; ?>

  <!-- Form upload design (semua tipe file) -->
  <form method="POST" action="/desainer/upload" enctype="multipart/form-data">
    <input type="hidden" name="_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="order_id" value="<?= h($order->order_id) ?>">
    <div class="form-group">
      <label class="form-label">Upload / Ganti File Design</label>
      <input type="file" name="design_file" class="form-control" required>
      <div style="font-size:11px;color:#9ca3af;margin-top:4px;">
        Semua tipe file diterima (AI, PSD, CDR, PDF, PNG, JPG, dll)
      </div>
    </div>
    <button type="submit" class="btn btn-purple btn-full">⬆ Upload Design</button>
  </form>
</div>

<!-- Tombol Aksi -->
<div style="display:flex;gap:12px;flex-wrap:wrap;margin-top:8px;">
  <?php if ($order->design_file): ?>
  <form method="POST" action="/desainer/request-approval">
    <input type="hidden" name="_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="order_id" value="<?= h($order->order_id) ?>">
    <button type="submit" class="btn btn-orange btn-lg">📋 Request Approval</button>
  </form>
  <?php endif; ?>

  <?php if ($order->design_status === 'approved'): ?>
  <form method="POST" action="/desainer/goto-print">
    <input type="hidden" name="_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="order_id" value="<?= h($order->order_id) ?>">
    <button type="submit" class="btn btn-blue btn-lg">→ Kirim ke Operator</button>
  </form>
  <?php endif; ?>

  <a href="/desainer/dashboard" class="btn btn-outline btn-lg">Kembali</a>
</div>
<?php layoutEnd(); ?>
