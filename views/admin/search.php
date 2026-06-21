<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
requireAuth('admin');

$q = trim($_GET['q'] ?? '');
if ($q !== '') {
    $like      = "%{$q}%";
    $customers = dbSelect("SELECT * FROM customers WHERE name LIKE ? OR customer_id LIKE ? OR phone LIKE ? ORDER BY name", [$like,$like,$like]);
} else {
    $customers = dbSelect("SELECT * FROM customers ORDER BY id DESC");
}
layoutStart('Search', 'search');
?>
<h1 class="page-title">SEARCH</h1>
<form method="GET" action="/admin/search" style="margin-bottom:20px;">
  <div class="search-wrap">
    <input type="text" name="q" class="search-input" placeholder="Cari nama, ID, atau nomor HP..." value="<?= h($q) ?>" autofocus>
    <button type="submit" class="search-icon" style="background:none;border:none;cursor:pointer;">
      <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    </button>
  </div>
</form>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>ID</th><th>Nama</th><th>Phone</th><th>Member</th></tr></thead>
      <tbody>
        <?php if ($customers): foreach ($customers as $c): ?>
        <tr>
          <td><?= h($c->customer_id) ?></td>
          <td><?= h($c->name) ?></td>
          <td><?= h($c->phone) ?></td>
          <td><?= $c->is_member ? '⭐' : '-' ?></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="4" class="text-center" style="color:#9ca3af;padding:24px;">Tidak ada hasil</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php layoutEnd(); ?>
