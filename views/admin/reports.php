<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
requireAuth('admin');

$month    = (int)($_GET['month'] ?? date('n'));
$year     = (int)($_GET['year']  ?? date('Y'));
$monthStr = sprintf('%04d-%02d', $year, $month);
$months   = ['','Januari','Februari','Maret','April','Mei','Juni',
             'Juli','Agustus','September','Oktober','November','Desember'];

// ── Pemasukan dari order ────────────────────────────────────────────────────
// down_payment = uang yang sudah masuk (DP atau lunas)
// Hitung dari order yang dibuat bulan ini
$paidOrders = dbSelect("
    SELECT o.order_id, c.name AS customer_name, o.product_type,
           o.total_price, o.down_payment, o.remaining_balance,
           o.payment_status, o.payment_method, o.created_at
    FROM orders o
    JOIN customers c ON o.customer_id = c.id
    WHERE strftime('%Y-%m', o.created_at) = ?
    AND o.payment_status IN ('paid','partial')
    ORDER BY o.created_at DESC
", [$monthStr]);

// Total pemasukan = total_price dari semua order yang sudah ada pembayarannya
$totalIncome = (float)(dbSelectOne("
    SELECT COALESCE(SUM(total_price), 0) AS s
    FROM orders
    WHERE strftime('%Y-%m', created_at) = ?
    AND payment_status IN ('paid','partial')
", [$monthStr])->s ?? 0);

// Total piutang (sisa yang belum dibayar) bulan ini
$totalPiutang = (float)(dbSelectOne("
    SELECT COALESCE(SUM(remaining_balance), 0) AS s
    FROM orders
    WHERE strftime('%Y-%m', created_at) = ?
    AND payment_status IN ('partial','unpaid')
    AND status NOT IN ('cancelled')
", [$monthStr])->s ?? 0);

// Total order bulan ini
$totalOrders = (int)(dbSelectOne("
    SELECT COUNT(*) AS c FROM orders
    WHERE strftime('%Y-%m', created_at) = ?
", [$monthStr])->c ?? 0);

// Order selesai bulan ini
$doneOrders = (int)(dbSelectOne("
    SELECT COUNT(*) AS c FROM orders
    WHERE strftime('%Y-%m', created_at) = ?
    AND status IN ('done','picked_up')
", [$monthStr])->c ?? 0);

// ── Pengeluaran ─────────────────────────────────────────────────────────────
$expenses     = dbSelect("
    SELECT * FROM expenses
    WHERE strftime('%Y-%m', created_at) = ?
    ORDER BY created_at DESC
", [$monthStr]);
$totalExpense = array_sum(array_column(array_map(fn($e)=>(array)$e, $expenses), 'amount'));

$laba = $totalIncome - $totalExpense;

// ── Chart 6 bulan ───────────────────────────────────────────────────────────
$chartLabels = $chartOrders = $chartIncome = $chartExpense = [];
for ($i = 5; $i >= 0; $i--) {
    $ts  = mktime(0, 0, 0, $month - $i, 1, $year);
    $ms  = date('Y-m', $ts);
    $chartLabels[]  = date('M', $ts);
    $chartOrders[]  = (int)(dbSelectOne(
        "SELECT COUNT(*) AS c FROM orders WHERE strftime('%Y-%m',created_at)=?", [$ms]
    )->c ?? 0);
    $chartIncome[]  = (float)(dbSelectOne(
        "SELECT COALESCE(SUM(total_price),0) AS s FROM orders WHERE strftime('%Y-%m',created_at)=? AND payment_status IN ('paid','partial')", [$ms]
    )->s ?? 0);
    $chartExpense[] = (float)(dbSelectOne(
        "SELECT COALESCE(SUM(amount),0) AS s FROM expenses WHERE strftime('%Y-%m',created_at)=?", [$ms]
    )->s ?? 0);
}

layoutStart('Reports', 'reports');
?>
<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:4px;flex-wrap:wrap;gap:8px;">
  <h1 class="page-title" style="margin:0;">REPORTS</h1>
  <div style="display:flex;align-items:center;gap:8px;">
    <span style="font-size:13px;color:#6B7280;">Periode: <strong><?= $months[$month] ?> <?= $year ?></strong></span>
    <button class="btn btn-outline" style="padding:4px 12px;font-size:12px;"
      onclick="document.getElementById('filterModal').classList.remove('hidden')">
      Ganti Periode
    </button>
  </div>
</div>
<hr class="page-title-divider">

<!-- Stats ringkas -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:14px;margin-bottom:20px;">
  <div class="card" style="padding:16px;text-align:center;">
    <div style="font-size:22px;font-weight:800;color:#6B4EFF;"><?= $totalOrders ?></div>
    <div style="font-size:11px;color:#6B7280;margin-top:3px;">Total Order</div>
  </div>
  <div class="card" style="padding:16px;text-align:center;">
    <div style="font-size:22px;font-weight:800;color:#22c55e;"><?= $doneOrders ?></div>
    <div style="font-size:11px;color:#6B7280;margin-top:3px;">Order Selesai</div>
  </div>
  <div class="card" style="padding:16px;text-align:center;">
    <div style="font-size:14px;font-weight:800;color:#3b82f6;"><?= formatRp($totalIncome) ?></div>
    <div style="font-size:11px;color:#6B7280;margin-top:3px;">Uang Masuk</div>
  </div>
  <div class="card" style="padding:16px;text-align:center;">
    <div style="font-size:14px;font-weight:800;color:#f97316;"><?= formatRp($totalPiutang) ?></div>
    <div style="font-size:11px;color:#6B7280;margin-top:3px;">Piutang (Sisa)</div>
  </div>
</div>

<div class="report-grid">

  <!-- Kiri: Rincian order + pengeluaran -->
  <div style="display:flex;flex-direction:column;gap:16px;">

    <!-- Tabel order yang sudah bayar bulan ini -->
    <div class="card">
      <h3 class="card-title">Pemasukan dari Order — <?= $months[$month] ?> <?= $year ?></h3>
      <div class="table-wrap">
        <table>
          <thead>
            <tr>
              <th>Tgl</th>
              <th>Order ID</th>
              <th>Customer</th>
              <th>Produk</th>
              <th>Total Harga</th>
              <th>Total Bayar</th>
              <th>Sisa</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($paidOrders): foreach ($paidOrders as $o): ?>
            <tr>
              <td style="white-space:nowrap;font-size:11px;"><?= date('d/m', strtotime($o->created_at)) ?></td>
              <td style="font-weight:700;"><?= h($o->order_id) ?></td>
              <td><?= h($o->customer_name) ?></td>
              <td style="font-size:12px;"><?= h($o->product_type) ?></td>
              <td class="green" style="white-space:nowrap;"><?= formatRp($o->total_price) ?></td>
              <td style="white-space:nowrap;color:#3b82f6;font-weight:600;"><?= formatRp($o->total_price) ?></td>
              <td style="white-space:nowrap;<?= $o->remaining_balance > 0 ? 'color:#ef4444;font-weight:600;' : 'color:#9ca3af;' ?>">
                <?= $o->remaining_balance > 0 ? formatRp($o->remaining_balance) : '—' ?>
              </td>
              <td><?= payBadge($o->payment_status) ?></td>
            </tr>
            <?php endforeach; ?>
            <!-- Baris total -->
            <tr style="font-weight:700;border-top:2px solid #e5e7eb;background:#f9fafb;">
              <td colspan="4" style="text-align:right;">Total</td>
              <td class="green"><?= formatRp(array_sum(array_column(array_map(fn($o)=>(array)$o,$paidOrders),'total_price'))) ?></td>
              <td style="color:#3b82f6;"><?= formatRp(array_sum(array_column(array_map(fn($o)=>(array)$o,$paidOrders),'total_price'))) ?></td>
              <td style="color:#ef4444;"><?= formatRp($totalPiutang) ?></td>
              <td></td>
            </tr>
            <?php else: ?>
            <tr><td colspan="8" class="text-center" style="color:#9ca3af;padding:20px;">Belum ada pemasukan bulan ini</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Pengeluaran -->
    <div class="card">
      <h3 class="card-title">Pengeluaran — <?= $months[$month] ?> <?= $year ?></h3>
      <div class="table-wrap">
        <table>
          <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Jumlah</th></tr></thead>
          <tbody>
            <?php if ($expenses): foreach ($expenses as $e): ?>
            <tr>
              <td style="white-space:nowrap;"><?= date('d/m/Y', strtotime($e->created_at)) ?></td>
              <td style="text-align:left;"><?= h($e->description) ?></td>
              <td class="red"><?= formatRp($e->amount) ?></td>
            </tr>
            <?php endforeach; ?>
            <tr style="font-weight:700;border-top:2px solid #e5e7eb;">
              <td colspan="2">Total Pengeluaran</td>
              <td class="red"><?= formatRp($totalExpense) ?></td>
            </tr>
            <?php else: ?>
            <tr><td colspan="3" class="text-center" style="color:#9ca3af;padding:20px;">Belum ada pengeluaran</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <!-- Ringkasan laba -->
      <div style="margin-top:14px;padding:14px;background:#f9fafb;border-radius:10px;">
        <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13px;">
          <span>Uang Masuk</span>
          <span class="green" style="font-weight:700;"><?= formatRp($totalIncome) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;margin-bottom:6px;font-size:13px;">
          <span>Total Pengeluaran</span>
          <span class="red" style="font-weight:700;"><?= formatRp($totalExpense) ?></span>
        </div>
        <div style="display:flex;justify-content:space-between;padding-top:8px;border-top:1px solid #e5e7eb;font-size:14px;font-weight:800;">
          <span>Laba Bersih</span>
          <span style="color:<?= $laba>=0?'#22c55e':'#ef4444' ?>;"><?= formatRp($laba) ?></span>
        </div>
      </div>

      <div style="display:flex;justify-content:flex-end;margin-top:12px;">
        <button class="btn btn-purple"
          onclick="document.getElementById('addExpModal').classList.remove('hidden')">
          + Tambah Pengeluaran
        </button>
      </div>
    </div>

  </div>

  <!-- Kanan: Chart -->
  <div class="card" style="align-self:start;">
    <h3 class="card-title" style="font-size:14px;">Tren 6 Bulan</h3>
    <div class="chart-wrap"><canvas id="reportsChart"></canvas></div>
  </div>

</div>

<!-- Filter Modal -->
<div id="filterModal" class="modal-overlay hidden">
  <div class="modal" style="max-width:360px;">
    <h2 class="modal-title">PILIH PERIODE</h2>
    <form method="GET" action="/admin/reports">
      <div class="form-group">
        <label class="form-label">Bulan</label>
        <select name="month" class="form-control">
          <?php foreach ($months as $i => $mName): if (!$i) continue; ?>
          <option value="<?= $i ?>" <?= $i==$month?'selected':'' ?>><?= $mName ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Tahun</label>
        <select name="year" class="form-control">
          <?php for ($y=date('Y')-3; $y<=date('Y'); $y++): ?>
          <option value="<?= $y ?>" <?= $y==$year?'selected':'' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-purple btn-full">TAMPILKAN</button>
    </form>
  </div>
</div>

<!-- Add Expense Modal -->
<div id="addExpModal" class="modal-overlay hidden">
  <div class="modal" style="max-width:400px;">
    <h2 class="modal-title">Tambah Pengeluaran</h2>
    <form method="POST" action="/admin/reports/filter">
      <input type="hidden" name="_token" value="<?= csrfToken() ?>">
      <input type="hidden" name="month" value="<?= $month ?>">
      <input type="hidden" name="year"  value="<?= $year ?>">
      <div class="form-group">
        <label class="form-label">Jumlah (Rp)</label>
        <input name="amount" type="number" step="1" class="form-control"
               placeholder="contoh: 15500" min="1" required>
      </div>
      <div class="form-group">
        <label class="form-label">Keterangan</label>
        <input name="description" type="text" class="form-control" placeholder="Keterangan">
      </div>
      <button type="submit" class="btn btn-purple btn-full btn-lg">SIMPAN</button>
    </form>
  </div>
</div>

<script>
new Chart(document.getElementById('reportsChart'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($chartLabels) ?>,
        datasets: [
            {
                label: 'Jumlah Order',
                data: <?= json_encode($chartOrders) ?>,
                backgroundColor: 'rgba(107,78,255,0.7)',
                yAxisID: 'y'
            },
            {
                label: 'Pemasukan',
                data: <?= json_encode($chartIncome) ?>,
                type: 'line',
                borderColor: '#22c55e',
                backgroundColor: 'transparent',
                yAxisID: 'y1',
                tension: 0.3,
                pointRadius: 4
            },
            {
                label: 'Pengeluaran',
                data: <?= json_encode($chartExpense) ?>,
                type: 'line',
                borderColor: '#ef4444',
                backgroundColor: 'transparent',
                yAxisID: 'y1',
                tension: 0.3,
                pointRadius: 4,
                borderDash: [5,5]
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } },
        scales: {
            y:  { type:'linear', position:'left',  ticks:{ font:{ size:9 } } },
            y1: { type:'linear', position:'right', grid:{ drawOnChartArea:false },
                  ticks:{ font:{ size:9 }, callback: v => 'Rp' + v.toLocaleString('id-ID') } }
        }
    }
});
document.getElementById('addExpModal').addEventListener('click', e => {
    if (e.target === e.currentTarget) e.currentTarget.classList.add('hidden');
});
document.getElementById('filterModal').addEventListener('click', e => {
    if (e.target === e.currentTarget) e.currentTarget.classList.add('hidden');
});
</script>
<?php layoutEnd(); ?>