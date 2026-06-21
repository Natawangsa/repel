<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
requireAuth('owner');
$customers = dbSelect("SELECT * FROM customers ORDER BY id DESC");
layoutStart('Customer','customer');
?>
<h1 class="page-title">CUSTOMER</h1>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th>ID</th><th>Nama</th><th>Phone</th><th>Member</th><th>Terdaftar</th></tr></thead>
      <tbody>
        <?php if ($customers): foreach ($customers as $c): ?>
        <tr>
          <td><?= h($c->customer_id) ?></td>
          <td><?= h($c->name) ?></td>
          <td><?= h($c->phone) ?></td>
          <td><?= $c->is_member ? '⭐ Member' : '-' ?></td>
          <td><?= date('d/m/Y', strtotime($c->created_at)) ?></td>
        </tr>
        <?php endforeach; else: ?>
        <tr><td colspan="5" class="text-center" style="color:#9ca3af;padding:24px;">Belum ada customer</td></tr>
        <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php layoutEnd(); ?>
