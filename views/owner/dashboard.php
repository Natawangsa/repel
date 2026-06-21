<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
requireAuth('owner');

$filterMonth = (int)($_GET['month'] ?? date('n'));
$filterYear  = (int)($_GET['year']  ?? date('Y'));
$monthStr    = sprintf('%04d-%02d', $filterYear, $filterMonth);

// Stats — total keseluruhan
$totalOrders  = (int)(dbSelectOne("SELECT COUNT(*) AS c FROM orders")->c ?? 0);
$totalRevenue = (float)(dbSelectOne("SELECT COALESCE(SUM(total_price),0) AS s FROM orders WHERE payment_status IN ('paid','partial')")->s ?? 0);

// Stats — bulan yang dipilih (pakai filterMonth/filterYear)
$monthOrders  = (int)(dbSelectOne("SELECT COUNT(*) AS c FROM orders WHERE strftime('%Y-%m',created_at)=?", [$monthStr])->c ?? 0);
$monthRevenue = (float)(dbSelectOne("SELECT COALESCE(SUM(total_price),0) AS s FROM orders WHERE payment_status IN ('paid','partial') AND strftime('%Y-%m',created_at)=?", [$monthStr])->s ?? 0);
$monthExpense = (float)(dbSelectOne("SELECT COALESCE(SUM(amount),0) AS s FROM expenses WHERE strftime('%Y-%m',created_at)=?", [$monthStr])->s ?? 0);
$laba         = $monthRevenue - $monthExpense;

// Chart 6 bulan terakhir
$chartLabels = $chartOrders = $chartRevenue = [];
for ($i = 5; $i >= 0; $i--) {
    $ts  = mktime(0, 0, 0, $filterMonth - $i, 1, $filterYear);
    $ms  = date('Y-m', $ts);
    $chartLabels[]  = date('M Y', $ts);
    $chartOrders[]  = (int)(dbSelectOne("SELECT COUNT(*) AS c FROM orders WHERE strftime('%Y-%m',created_at)=?", [$ms])->c ?? 0);
    $chartRevenue[] = (float)(dbSelectOne("SELECT COALESCE(SUM(total_price),0) AS s FROM orders WHERE payment_status IN ('paid','partial') AND strftime('%Y-%m',created_at)=?", [$ms])->s ?? 0);
}

// Ringkasan status order bulan ini
$statusSummary = dbSelect("
    SELECT status, COUNT(*) AS jumlah FROM orders
    WHERE strftime('%Y-%m',created_at)=?
    GROUP BY status ORDER BY jumlah DESC
", [$monthStr]);

$months = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
layoutStart('Dashboard','dashboard');
?>
<h1 class="page-title">DASHBOARD</h1>
<div style="display:flex;align-items:center;gap:12px;margin-bottom:4px;">
  <span style="font-size:13px;color:#6B7280;">Periode: <strong><?= $months[$filterMonth] ?> <?= $filterYear ?></strong></span>
  <button class="btn btn-outline" style="padding:4px 12px;font-size:12px;"
    onclick="document.getElementById('filterModal').classList.remove('hidden')">Ganti Periode</button>
</div>
<hr class="page-title-divider">

<!-- Stats Cards -->
<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;">
  <div class="card" style="text-align:center;padding:20px;">
    <div style="font-size:28px;font-weight:800;color:#6B4EFF;"><?= $totalOrders ?></div>
    <div style="font-size:12px;color:#6B7280;font-weight:600;margin-top:4px;">Total Semua Order</div>
  </div>
  <div class="card" style="text-align:center;padding:20px;">
    <div style="font-size:28px;font-weight:800;color:#22c55e;"><?= $monthOrders ?></div>
    <div style="font-size:12px;color:#6B7280;font-weight:600;margin-top:4px;">Order Bulan Ini</div>
  </div>
  <div class="card" style="text-align:center;padding:20px;">
    <div style="font-size:15px;font-weight:800;color:#3b82f6;"><?= formatRp($monthRevenue) ?></div>
    <div style="font-size:12px;color:#6B7280;font-weight:600;margin-top:4px;">Pemasukan Bulan Ini</div>
  </div>
  <div class="card" style="text-align:center;padding:20px;">
    <div style="font-size:15px;font-weight:800;color:<?= $laba >= 0 ? '#22c55e' : '#ef4444' ?>;">
      <?= formatRp($laba) ?>
    </div>
    <div style="font-size:12px;color:#6B7280;font-weight:600;margin-top:4px;">Laba Bersih Bulan Ini</div>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
  <!-- Laporan ringkasan -->
  <div class="card">
    <h3 class="card-title">Laporan <?= $months[$filterMonth] ?> <?= $filterYear ?></h3>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Keterangan</th><th>Jumlah</th></tr></thead>
        <tbody>
          <tr><td>Pemasukan</td><td class="green"><?= formatRp($monthRevenue) ?></td></tr>
          <tr><td>Pengeluaran</td><td class="red"><?= formatRp($monthExpense) ?></td></tr>
          <tr style="font-weight:700;border-top:2px solid #e5e7eb;">
            <td>Laba Bersih</td>
            <td style="color:<?= $laba>=0?'#22c55e':'#ef4444' ?>;"><?= formatRp($laba) ?></td>
          </tr>
        </tbody>
      </table>
    </div>
    <?php if ($statusSummary): ?>
    <div style="margin-top:16px;">
      <div style="font-size:12px;font-weight:700;color:#6B7280;margin-bottom:8px;">STATUS ORDER BULAN INI</div>
      <?php foreach ($statusSummary as $ss): ?>
      <div style="display:flex;justify-content:space-between;align-items:center;padding:5px 0;border-bottom:1px solid #f3f4f6;font-size:13px;">
        <?= statusBadge($ss->status) ?>
        <strong><?= $ss->jumlah ?></strong>
      </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>

  <!-- Chart -->
  <div class="card">
    <h3 class="card-title" style="font-size:14px;">Tren 6 Bulan</h3>
    <div style="height:250px;"><canvas id="salesChart"></canvas></div>
  </div>
</div>

<!-- Filter Modal -->
<div id="filterModal" class="modal-overlay hidden">
  <div class="modal" style="max-width:360px;">
    <h2 class="modal-title">PILIH PERIODE</h2>
    <form method="GET" action="/owner/dashboard">
      <div class="form-group">
        <label class="form-label">Bulan</label>
        <select name="month" class="form-control">
          <?php foreach ($months as $i => $mName): if (!$i) continue; ?>
          <option value="<?= $i ?>" <?= $i==$filterMonth?'selected':'' ?>><?= $mName ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Tahun</label>
        <select name="year" class="form-control">
          <?php for ($y=date('Y')-3; $y<=date('Y'); $y++): ?>
          <option value="<?= $y ?>" <?= $y==$filterYear?'selected':'' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <button type="submit" class="btn btn-purple btn-full">TAMPILKAN</button>
    </form>
  </div>
</div>

<script>
new Chart(document.getElementById('salesChart'), {
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
                data: <?= json_encode($chartRevenue) ?>,
                type: 'line',
                borderColor: '#f97316',
                backgroundColor: 'transparent',
                yAxisID: 'y1',
                tension: 0.3,
                pointRadius: 4
            }
        ]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { position: 'bottom', labels: { font: { size: 10 } } } },
        scales: {
            y:  { type: 'linear', position: 'left',  ticks: { font: { size: 9 } } },
            y1: { type: 'linear', position: 'right', grid: { drawOnChartArea: false },
                  ticks: { font: { size: 9 }, callback: v => 'Rp' + v.toLocaleString('id-ID') } }
        }
    }
});
document.getElementById('filterModal').addEventListener('click', e => {
    if (e.target === e.currentTarget) e.currentTarget.classList.add('hidden');
});
</script>
<?php layoutEnd(); ?>
