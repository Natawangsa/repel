<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
requireAuth('operator');

$session = dbSelectOne("SELECT * FROM print_sessions WHERE id=1");
$selectedOrders = $session ? dbSelect("SELECT o.order_id FROM print_session_orders pso JOIN orders o ON pso.order_id=o.id WHERE pso.session_id=1") : [];

// Get linked printer (if any)
$printerId = $session->printer_id ?? 0;
$printer = $printerId ? dbSelectOne("SELECT * FROM printers WHERE id=?", [$printerId]) : null;

$progress = $session->progress     ?? 0;
$estimate = $session->estimate_time ?? '00:00';
$status   = $session->status        ?? 'idle';
// Use printer data if linked, otherwise fallback to session data
$inkC = $printer ? (int)$printer->ink_c : ($session->ink_c ?? 75);
$inkM = $printer ? (int)$printer->ink_m : ($session->ink_m ?? 60);
$inkY = $printer ? (int)$printer->ink_y : ($session->ink_y ?? 80);
$inkK = $printer ? (int)$printer->ink_k : ($session->ink_k ?? 30);
$sc   = ['idle'=>'#6c757d','printing'=>'#3b82f6','paused'=>'#f97316'][$status] ?? '#6c757d';

layoutStart('Progress','progress');
?>
<h1 class="page-title">PROGRESS</h1>
<hr class="page-title-divider">
<div style="display:grid;grid-template-columns:200px 1fr 220px;gap:20px;align-items:start;">
  <!-- Work List -->
  <div class="card">
    <h3 class="card-title text-center" style="font-size:16px;">WORK LIST</h3>
    <?php if ($selectedOrders): ?>
    <div style="display:flex;flex-direction:column;gap:8px;">
      <?php foreach ($selectedOrders as $so): ?>
      <div style="padding:8px 10px;border:2px solid #e5e7eb;border-radius:8px;font-weight:700;font-size:13px;"><?= h($so->order_id) ?></div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div style="text-align:center;color:#9ca3af;font-size:12px;padding:16px 0;">Belum ada order dipilih</div>
    <?php endif; ?>
  </div>

  <!-- Print Progress -->
  <div class="card">
    <h3 class="card-title text-center" style="font-size:16px;">PRINT PROGRESS</h3>
    <?php if ($printer):
      $isUsb = ($printer->connection_type ?? 'lan') === 'usb';
    ?>
    <div style="text-align:center;margin-bottom:12px;font-size:12px;color:#6B7280;">
      Mesin: <strong style="color:var(--text);"><?= h($printer->name) ?></strong>
      <span style="color:#9ca3af;">(<?= h($printer->machine) ?>)</span>
      <span style="font-size:10px;padding:1px 6px;border-radius:3px;margin-left:4px;background:<?= $isUsb ? '#f3f4f6' : '#ecfdf5' ?>;color:<?= $isUsb ? '#6B7280' : '#059669' ?>;font-weight:600;"><?= $isUsb ? 'USB' : 'LAN' ?></span>
    </div>
    <?php endif; ?>
    <div class="progress-wrap" style="margin-bottom:12px;">
      <div class="progress-bar" id="progressBar" style="width:<?= $progress ?>%;"><?= $progress ?>%</div>
    </div>
    <div style="text-align:center;font-size:14px;font-weight:700;margin-bottom:20px;">ESTIMATE TIME <?= h($estimate) ?></div>
    <?php if (in_array($status,['printing','paused'])): ?>
    <form method="POST" action="/operator/progress/update" style="margin-bottom:16px;">
      <input type="hidden" name="_token" value="<?= csrfToken() ?>">
      <div style="display:flex;gap:10px;align-items:center;margin-bottom:8px;">
        <label style="font-size:12px;font-weight:600;white-space:nowrap;">Progress %</label>
        <input name="progress" type="number" min="0" max="100" class="form-control" value="<?= $progress ?>" style="width:80px;">
        <label style="font-size:12px;font-weight:600;white-space:nowrap;">Est. Time</label>
        <input name="estimate_time" type="text" class="form-control" value="<?= h($estimate) ?>" placeholder="MM:SS" style="width:80px;">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;margin-bottom:8px;">
        <div><label style="font-size:11px;font-weight:600;">Ink C (%)</label><input name="ink_c" type="number" min="0" max="100" class="form-control" value="<?= $inkC ?>"></div>
        <div><label style="font-size:11px;font-weight:600;">Ink M (%)</label><input name="ink_m" type="number" min="0" max="100" class="form-control" value="<?= $inkM ?>"></div>
        <div><label style="font-size:11px;font-weight:600;">Ink Y (%)</label><input name="ink_y" type="number" min="0" max="100" class="form-control" value="<?= $inkY ?>"></div>
        <div><label style="font-size:11px;font-weight:600;">Ink K (%)</label><input name="ink_k" type="number" min="0" max="100" class="form-control" value="<?= $inkK ?>"></div>
      </div>
      <button type="submit" class="btn btn-purple btn-full">Update</button>
    </form>
    <?php endif; ?>
    <div style="text-align:center;margin-bottom:16px;">
      <span id="statusBadge" class="badge" style="background:<?= $sc ?>;color:#fff;font-size:13px;padding:6px 16px;"><?= strtoupper($status) ?></span>
    </div>
  </div>

  <!-- Resource Monitor -->
  <div class="card">
    <h3 class="card-title text-center" style="font-size:15px;">RESOURCE MONITOR</h3>
    <?php if ($printer):
      $isUsbMon = ($printer->connection_type ?? 'lan') === 'usb';
    ?>
    <div style="text-align:center;margin-bottom:10px;">
      <?php if ($isUsbMon): ?>
        <span style="font-size:10px;color:#f97316;">⚡ USB — Input manual</span>
      <?php else: ?>
        <span id="printerConnection" style="font-size:10px;color:<?= $printer->last_seen ? '#22c55e' : '#9ca3af' ?>;">
          <?php if ($printer->last_seen): ?>
            ● Terhubung — <?= h($printer->machine) ?>
          <?php else: ?>
            ○ Belum terhubung
          <?php endif; ?>
        </span>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <?php foreach ([['INK LEVEL: C',$inkC,'#38bdf8','inkC'],['INK LEVEL: M',$inkM,'#ec4899','inkM'],['INK LEVEL: Y',$inkY,'#eab308','inkY'],['INK LEVEL: K',$inkK,'#374151','inkK']] as [$lbl,$val,$bg,$barId]): ?>
    <div class="ink-item">
      <div class="ink-label"><?= $lbl ?></div>
      <div style="background:#e5e7eb;border-radius:6px;overflow:hidden;height:28px;">
        <div class="ink-bar" id="<?= $barId ?>" style="width:<?= $val ?>%;background:<?= $bg ?>;min-width:36px;"><?= $val ?>%</div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</div>

<div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;margin-top:16px;">
  <?php if ($status === 'paused'): ?>
  <form method="POST" action="/operator/resume">
    <input type="hidden" name="_token" value="<?= csrfToken() ?>">
    <button class="btn btn-blue btn-full btn-lg">▶ RESUME</button>
  </form>
  <?php else: ?>
  <form method="POST" action="/operator/pause">
    <input type="hidden" name="_token" value="<?= csrfToken() ?>">
    <button class="btn btn-outline btn-full btn-lg" <?= $status==='idle'?'disabled':'' ?>>⏸ PAUSE</button>
  </form>
  <?php endif; ?>
  <form method="POST" action="/operator/cancel">
    <input type="hidden" name="_token" value="<?= csrfToken() ?>">
    <button class="btn btn-red btn-full btn-lg" <?= $status==='idle'?'disabled':'' ?>
      onclick="return confirm('Batalkan print session?')">CANCEL</button>
  </form>
  <form method="POST" action="/operator/finish">
    <input type="hidden" name="_token" value="<?= csrfToken() ?>">
    <button class="btn btn-blue btn-full btn-lg" <?= $status==='idle'?'disabled':'' ?>>FINISH</button>
  </form>
</div>

<?php if ($printer): ?>
<script>
// Auto-refresh ink & progress from connected printer every 10 seconds
const PRINTER_ID = <?= (int)$printer->id ?>;
function refreshFromPrinter() {
  fetch('/api/printer-status')
    .then(r => r.json())
    .then(data => {
      if (!data.printers) return;
      const p = data.printers.find(x => x.id === PRINTER_ID);
      if (!p) return;

      // Update ink bars
      const inkMap = {inkC:'ink_c', inkM:'ink_m', inkY:'ink_y', inkK:'ink_k'};
      for (const [elId, key] of Object.entries(inkMap)) {
        const bar = document.getElementById(elId);
        if (bar && p[key] !== undefined) {
          bar.style.width = p[key] + '%';
          bar.textContent = p[key] + '%';
        }
      }

      // Update progress bar if printer reports progress
      if (p.progress !== undefined && p.progress > 0) {
        const progBar = document.getElementById('progressBar');
        if (progBar) {
          progBar.style.width = p.progress + '%';
          progBar.textContent = p.progress + '%';
        }
      }

      // Update form inputs to match
      for (const [, key] of Object.entries(inkMap)) {
        const input = document.querySelector(`input[name="${key}"]`);
        if (input && p[key] !== undefined) input.value = p[key];
      }
      const progInput = document.querySelector('input[name="progress"]');
      if (progInput && p.progress !== undefined && p.progress > 0) progInput.value = p.progress;

      // Update connection indicator
      const conn = document.getElementById('printerConnection');
      if (conn) {
        const isOnline = p.last_seen && (Date.now()/1000 - new Date(p.last_seen.replace(' ','T')+'Z').getTime()/1000) < 120;
        conn.style.color = isOnline ? '#22c55e' : '#9ca3af';
        conn.textContent = isOnline
          ? '● Terhubung — ' + (p.machine || '')
          : '○ Tidak terhubung';
      }
    })
    .catch(() => {});
}
setInterval(refreshFromPrinter, 10000);
</script>
<?php endif; ?>
<?php layoutEnd(); ?>
