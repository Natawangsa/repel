<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
requireAuth('admin');

$month    = (int)($_GET['month'] ?? date('n'));
$year     = (int)($_GET['year']  ?? date('Y'));
$monthStr = sprintf('%04d-%02d', $year, $month);

$expenses     = dbSelect("SELECT * FROM expenses WHERE strftime('%Y-%m', created_at) = ? ORDER BY created_at DESC", [$monthStr]);
$totalExpense = array_sum(array_column(array_map(fn($e) => (array)$e, $expenses), 'amount'));

$incRow      = dbSelectOne("SELECT COALESCE(SUM(down_payment),0) AS s FROM orders WHERE strftime('%Y-%m',created_at) = ?", [$monthStr]);
$totalIncome = $incRow->s ?? 0;

$chartLabels = $chartOrders = $chartIncome = [];
for ($i = 5; $i >= 0; $i--) {
    $ts   = mktime(0, 0, 0, date('n') - $i, 1, date('Y'));
    $ms   = date('Y-m', $ts);
    $chartLabels[] = date('M', $ts);
    $chartOrders[] = (int)(dbSelectOne("SELECT COUNT(*) AS c FROM orders WHERE strftime('%Y-%m',created_at)=?",[$ms])->c ?? 0);
    $chartIncome[] = (float)(dbSelectOne("SELECT COALESCE(SUM(down_payment),0) AS s FROM orders WHERE strftime('%Y-%m',created_at)=?",[$ms])->s ?? 0);
}

layoutStart('Reports', 'reports');
?>
<h1 class="page-title">REPORTS</h1>
<hr class="page-title-divider">
<div class="report-grid">
  <div class="card">
    <h3 class="card-title">Pengeluaran — <?= date('F Y', mktime(0,0,0,$month,1,$year)) ?></h3>
    <div class="table-wrap">
      <table>
        <thead><tr><th>Tanggal</th><th>Keterangan</th><th>Jumlah</th></tr></thead>
        <tbody>
          <?php if ($expenses): foreach ($expenses as $e): ?>
          <tr>
            <td><?= date('d/m/Y', strtotime($e->created_at)) ?></td>
            <td style="text-align:left;"><?= h($e->description) ?></td>
            <td class="red"><?= formatRp($e->amount) ?></td>
          </tr>
          <?php endforeach;
                $laba = $totalIncome - $totalExpense; ?>
          <tr style="font-weight:700;">
            <td colspan="2">Total</td>
            <td class="red"><?= formatRp($totalExpense) ?></td>
          </tr>
          <?php else: ?>
          <tr><td colspan="3" class="text-center" style="color:#9ca3af;padding:24px;">Belum ada pengeluaran</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;flex-wrap:wrap;gap:8px;">
      <div style="font-size:14px;font-weight:600;">
        Pemasukan: <span class="green"><?= formatRp($totalIncome) ?></span>
        &nbsp;|&nbsp;
        Laba Bersih: <span style="color:<?= ($totalIncome-$totalExpense)>=0?'#22c55e':'#ef4444' ?>;"><?= formatRp($totalIncome-$totalExpense) ?></span>
      </div>
      <button class="btn btn-purple" onclick="document.getElementById('addExpModal').classList.remove('hidden')">+ Pengeluaran</button>
    </div>
  </div>
  <div class="card">
    <h3 class="card-title" style="font-size:14px;">Order & Pemasukan (6 Bulan Terakhir)</h3>
    <div class="chart-wrap"><canvas id="reportsChart"></canvas></div>
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
      <div class="form-group"><label class="form-label">Jumlah (Rp)</label><input name="amount" type="number" step="1000" class="form-control" placeholder="0" min="1" required></div>
      <div class="form-group"><label class="form-label">Keterangan</label><input name="description" type="text" class="form-control" placeholder="Keterangan"></div>
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
            { label:'Jumlah Order', data:<?= json_encode($chartOrders) ?>, backgroundColor:'rgba(107,78,255,0.7)', yAxisID:'y' },
            { label:'Pemasukan', data:<?= json_encode($chartIncome) ?>, type:'line', borderColor:'#f97316', backgroundColor:'transparent', yAxisID:'y1', tension:0.3, pointRadius:4 }
        ]
    },
    options: { responsive:true, maintainAspectRatio:false,
        plugins:{ legend:{ position:'bottom', labels:{ font:{ size:10 } } } },
        scales:{ y:{type:'linear',position:'left',ticks:{font:{size:9}}}, y1:{type:'linear',position:'right',grid:{drawOnChartArea:false},ticks:{font:{size:9},callback:v=>'Rp'+v.toLocaleString()}} }
    }
});
document.getElementById('addExpModal').addEventListener('click',e=>{if(e.target===e.currentTarget)e.currentTarget.classList.add('hidden');});
</script>
<?php layoutEnd(); ?>
