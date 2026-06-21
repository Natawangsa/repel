<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
requireAuth('operator');

$session = dbSelectOne("SELECT * FROM print_sessions WHERE id=1");
$selectedOrders = $session ? dbSelect("SELECT o.order_id FROM print_session_orders pso JOIN orders o ON pso.order_id=o.id WHERE pso.session_id=1") : [];

$progress = $session->progress     ?? 0;
$estimate = $session->estimate_time ?? '00:00';
$status   = $session->status        ?? 'idle';
$inkC = $session->ink_c ?? 75;
$inkM = $session->ink_m ?? 60;
$inkY = $session->ink_y ?? 80;
$inkK = $session->ink_k ?? 30;
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
    <div class="progress-wrap" style="margin-bottom:12px;">
      <div class="progress-bar" style="width:<?= $progress ?>%;"><?= $progress ?>%</div>
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
      <span class="badge" style="background:<?= $sc ?>;color:#fff;font-size:13px;padding:6px 16px;"><?= strtoupper($status) ?></span>
    </div>
  </div>

  <!-- Resource Monitor -->
  <div class="card">
    <h3 class="card-title text-center" style="font-size:15px;">RESOURCE MONITOR</h3>
    <?php foreach ([['INK LEVEL: C',$inkC,'#38bdf8'],['INK LEVEL: M',$inkM,'#ec4899'],['INK LEVEL: Y',$inkY,'#eab308'],['INK LEVEL: K',$inkK,'#374151']] as [$lbl,$val,$bg]): ?>
    <div class="ink-item">
      <div class="ink-label"><?= $lbl ?></div>
      <div style="background:#e5e7eb;border-radius:6px;overflow:hidden;height:28px;">
        <div class="ink-bar" style="width:<?= $val ?>%;background:<?= $bg ?>;min-width:36px;"><?= $val ?>%</div>
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
<?php layoutEnd(); ?>
