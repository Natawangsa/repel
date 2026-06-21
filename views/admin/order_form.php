<?php
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/layout.php';
requireAuth('admin');
layoutStart('Order Form', 'order');
$productTypes = ['Banner','Sticker Indoor','Sticker A3+','Bendera','UV Print','Cutting Laser','DTF'];
?>
<h1 class="page-title">Order Form</h1>

<div id="step1">
  <div class="card">
    <h3 class="card-title">Customer Data</h3>
    <div class="form-row">
      <div class="form-group">
        <label class="form-label">Name</label>
        <input type="text" id="custName" class="form-control" placeholder="Name">
      </div>
      <div class="form-group">
        <label class="form-label">Phone</label>
        <input type="text" id="custPhone" class="form-control" placeholder="Phone Number">
      </div>
    </div>
    <div class="form-group">
      <label style="display:flex;align-items:center;gap:8px;font-weight:600;font-size:13px;cursor:pointer;">
        <input type="checkbox" id="isMember" style="width:16px;height:16px;"> Member
      </label>
    </div>
  </div>
  <div class="card">
    <h3 class="card-title">Pilih Produk</h3>
    <div class="product-types">
      <?php foreach ($productTypes as $pt): ?>
      <button type="button" class="product-btn" onclick="selectProduct(this,'<?= h($pt) ?>')"><?= h($pt) ?></button>
      <?php endforeach; ?>
    </div>
  </div>
  <button class="btn btn-purple btn-full btn-lg" onclick="goStep2()">NEXT</button>
</div>

<div id="step2" class="hidden">
  <form method="POST" action="/admin/order/store" enctype="multipart/form-data">
    <input type="hidden" name="_token" value="<?= csrfToken() ?>">
    <input type="hidden" name="product_type" id="hiddenProduct">
    <input type="hidden" name="name"          id="hiddenName">
    <input type="hidden" name="phone"         id="hiddenPhone">
    <input type="hidden" name="is_member"     id="hiddenMember" value="">
    <h1 class="page-title" id="formTitle">Order Form</h1>
    <div class="card">
      <h3 class="card-title">Detail Produk</h3>
      <div class="form-row">
        <div class="form-group"><label class="form-label">Panjang (m)</label><input name="panjang" type="number" step="0.01" class="form-control" placeholder="Panjang"></div>
        <div class="form-group"><label class="form-label">Lebar (m)</label><input name="lebar" type="number" step="0.01" class="form-control" placeholder="Lebar"></div>
        <div class="form-group"><label class="form-label">Bahan</label><input name="bahan" type="text" class="form-control" placeholder="Material"></div>
        <div class="form-group"><label class="form-label">Quantity</label><input name="quantity" type="number" min="1" class="form-control" value="1"></div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label class="form-label">Finishing</label>
          <input name="finishing_type" type="text" class="form-control"
                 placeholder="Contoh: Bordir, Kisscut, Laminasi, dll">
        </div>
        <div class="form-group"><label class="form-label">Deadline</label><input name="deadline" type="date" class="form-control" min="<?= date('Y-m-d') ?>"></div>
      </div>
      <div class="form-group"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="3" placeholder="Desc"></textarea></div>
      <div class="form-group"><label class="form-label">Upload File Design</label><input name="design_file" type="file" class="form-control" accept=".jpg,.jpeg,.png,.pdf,.ai,.psd"></div>
    </div>
    <div style="display:flex;gap:12px;">
      <button type="button" class="btn btn-outline btn-lg" style="flex:1;" onclick="goBack()">KEMBALI</button>
      <button type="submit" class="btn btn-purple btn-lg" style="flex:2;">DONE</button>
    </div>
  </form>
</div>

<script>
let selProduct = '';
function selectProduct(btn, p) {
  document.querySelectorAll('.product-btn').forEach(b => b.classList.remove('selected'));
  btn.classList.add('selected'); selProduct = p;
}
function goStep2() {
  const name = document.getElementById('custName').value.trim();
  const phone = document.getElementById('custPhone').value.trim();
  if (!name || !phone) { alert('Lengkapi nama dan nomor telepon!'); return; }
  if (!selProduct) { alert('Pilih jenis produk terlebih dahulu!'); return; }
  document.getElementById('hiddenProduct').value = selProduct;
  document.getElementById('hiddenName').value = name;
  document.getElementById('hiddenPhone').value = phone;
  document.getElementById('hiddenMember').value = document.getElementById('isMember').checked ? '1' : '';
  document.getElementById('formTitle').textContent = 'Order Form ' + selProduct;
  document.getElementById('step1').classList.add('hidden');
  document.getElementById('step2').classList.remove('hidden');
}
function goBack() {
  document.getElementById('step2').classList.add('hidden');
  document.getElementById('step1').classList.remove('hidden');
}
</script>
<?php layoutEnd(); ?>
