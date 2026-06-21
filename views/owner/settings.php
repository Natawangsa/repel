<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
requireAuth('owner');

$users = dbSelect("SELECT id, username, role FROM users ORDER BY role ASC");
layoutStart('Settings','settings');
?>
<h1 class="page-title">SETTINGS</h1>

<!-- Reset Password Semua User (termasuk owner) -->
<div class="card" style="max-width:500px;">
  <h3 class="card-title">Reset Password User</h3>
  <p style="font-size:13px;color:#6B7280;margin-bottom:16px;">
    Pilih user yang ingin diganti passwordnya, termasuk akun Owner sendiri.
    Tidak perlu tahu password lama.
  </p>
  <form method="POST" action="/owner/settings/change-password">
    <input type="hidden" name="_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="mode" value="reset">
    <div class="form-group">
      <label class="form-label">Pilih User</label>
      <select name="target_user_id" class="form-control" required>
        <option value="">— Pilih User —</option>
        <?php foreach ($users as $u): ?>
        <option value="<?= h($u->id) ?>">
          <?= h($u->username) ?> (<?= h($u->role) ?>)
        </option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label class="form-label">Password Baru</label>
      <input type="password" name="new_password" class="form-control"
             placeholder="Min. 6 karakter" required>
    </div>
    <div class="form-group">
      <label class="form-label">Konfirmasi Password Baru</label>
      <input type="password" name="confirm_password" class="form-control"
             placeholder="Ulangi password baru" required>
    </div>
    <button type="submit" class="btn btn-purple btn-full btn-lg"
      onclick="return confirm('Reset password user ini?')">
      RESET PASSWORD
    </button>
  </form>
</div>

<!-- Daftar User -->
<div class="card" style="max-width:500px;margin-top:24px;">
  <h3 class="card-title">Daftar User</h3>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Username</th><th>Role</th></tr></thead>
      <tbody>
        <?php
        $roleColors = ['admin'=>'#FF8C00','desainer'=>'#007bff','operator'=>'#6f42c1','finishing'=>'#20c997','owner'=>'#dc3545'];
        foreach ($users as $u):
            $rc = $roleColors[$u->role] ?? '#6c757d';
        ?>
        <tr>
          <td><?= h($u->username) ?></td>
          <td><span class="badge" style="background:<?= $rc ?>;color:#fff;"><?= strtoupper(h($u->role)) ?></span></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php layoutEnd(); ?>
