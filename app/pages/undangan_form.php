<?php
$pdo = db();
$user = current_user();

// Get undangan data if editing
$id = $_GET['id'] ?? '';
$undangan = null;

if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM surat_undangan WHERE id = ?");
    $stmt->execute([$id]);
    $undangan = $stmt->fetch();
    
    if (!$undangan) {
        flash_set('Data undangan tidak ditemukan');
        header('Location: ?p=undangan');
        exit;
    }
}

$flash = flash_get();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $undangan ? 'Edit' : 'Tambah' ?> Surat Undangan - SiCakap</title>
  <link rel="stylesheet" href="<?= h(rtrim(APP_URL, '/')) ?>/assets/css/style.css">
  <style>
    .form-group {
      margin-bottom: 20px;
    }
    .form-group label {
      display: block;
      margin-bottom: 8px;
      font-weight: 600;
      color: #2d5016;
    }
    .form-group input[type="text"],
    .form-group input[type="date"],
    .form-group input[type="time"],
    .form-group textarea {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
      background: #f5f5f5;
    }
    .form-group textarea {
      resize: vertical;
      min-height: 80px;
    }
    .form-group input:focus,
    .form-group textarea:focus {
      outline: none;
      border-color: #4a7c2c;
      background: #fff;
    }
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 20px;
    }
    .form-actions {
      margin-top: 30px;
      display: flex;
      gap: 10px;
    }
    .form-header {
      background: #4a7c2c;
      color: white;
      padding: 15px 20px;
      border-radius: 4px;
      margin-bottom: 30px;
      text-align: center;
      font-size: 18px;
      font-weight: 600;
    }
  </style>
</head>
<body>
<?php include __DIR__ . '/_sidebar.php'; ?>
<div class="main">
  <header class="topbar">
    <div class="brand">SiCakap</div>
    <div class="user">
      <span>Hai, <?= h($user['name'] ?? $user['email'] ?? 'Administrator') ?></span>
      <a href="?p=logout" class="logout">Logout</a>
    </div>
  </header>

  <section class="content">
    <div style="max-width: 1000px; margin: 0 auto;">
      <?php if ($flash): ?>
      <div class="alert alert-danger"><?= h($flash) ?></div>
      <?php endif; ?>

      <div class="form-header">
        <?= $undangan ? 'Edit' : 'Input' ?> Surat Undangan
      </div>

      <form method="post" action="?p=undangan_save">
        <?php if ($undangan): ?>
        <input type="hidden" name="id" value="<?= h($undangan['id']) ?>">
        <?php endif; ?>

        <div class="form-group">
          <label>Tanggal Pembuatan Surat</label>
          <input type="date" name="tanggal_surat" value="<?= h($undangan['tanggal_surat'] ?? date('Y-m-d')) ?>" required>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Nomor Surat</label>
            <input type="text" name="nomor_surat" placeholder="Nomor Surat" value="<?= h($undangan['nomor_surat'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Perihal</label>
            <input type="text" name="perihal" placeholder="Perihal" value="<?= h($undangan['perihal'] ?? '') ?>" required>
          </div>
        </div>

        <div class="form-group">
          <label>Nama</label>
          <input type="text" name="nama" placeholder="Nama" value="<?= h($undangan['nama'] ?? '') ?>" required>
        </div>

        <div class="form-group">
          <label>Alamat</label>
          <textarea name="alamat" placeholder="Alamat" required><?= h($undangan['alamat'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
          <label>Tembusan Kepada</label>
          <textarea name="tembusan_kepada" placeholder="Tembusan Kepada (Opsional)"><?= h($undangan['tembusan_kepada'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Tanggal Pelaksanaan</label>
            <input type="date" name="tanggal_pelaksanaan" id="tanggal_pelaksanaan" value="<?= h($undangan['tanggal_pelaksanaan'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Jam</label>
            <input type="time" name="jam" id="jam" value="<?= h($undangan['jam'] ?? '') ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Hari/ Tanggal</label>
            <input type="text" name="hari_tanggal" id="hari_tanggal" placeholder="Otomatis terisi dari Tanggal Pelaksanaan" value="<?= h($undangan['hari_tanggal'] ?? '') ?>" required readonly style="background: #e8f5e9;">
          </div>
          <div class="form-group">
            <label>Tempat Pelaksanaan</label>
            <input type="text" name="tempat_pelaksanaan" placeholder="Tempat Pelaksanaan" value="<?= h($undangan['tempat_pelaksanaan'] ?? '') ?>" required>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Simpan</button>
          <a href="?p=undangan" class="btn" style="background:#e63939; color:white;">Batal</a>
        </div>
      </form>
    </div>
  </section>
</div>

<script>
// Fungsi untuk format tanggal ke Indonesia
function formatTanggalIndonesia(tanggal) {
  const hari = ['minggu', 'senin', 'selasa', 'rabu', 'kamis', 'jumat', 'sabtu'];
  const bulan = ['januari', 'februari', 'maret', 'april', 'mei', 'juni', 'juli', 'agustus', 'september', 'oktober', 'november', 'desember'];
  
  const date = new Date(tanggal);
  const namaHari = hari[date.getDay()];
  const tanggalAngka = date.getDate();
  const namaBulan = bulan[date.getMonth()];
  const tahun = date.getFullYear();
  
  return `${namaHari}, ${tanggalAngka} ${namaBulan} ${tahun}`;
}

// Auto-fill hari/tanggal ketika tanggal pelaksanaan dipilih
document.getElementById('tanggal_pelaksanaan').addEventListener('change', function() {
  const tanggalPelaksanaan = this.value;
  if (tanggalPelaksanaan) {
    const hariTanggal = formatTanggalIndonesia(tanggalPelaksanaan);
    document.getElementById('hari_tanggal').value = hariTanggal;
  }
});

// Auto-fill saat halaman dimuat jika sudah ada tanggal pelaksanaan
window.addEventListener('DOMContentLoaded', function() {
  const tanggalPelaksanaan = document.getElementById('tanggal_pelaksanaan').value;
  const hariTanggalField = document.getElementById('hari_tanggal');
  
  // Hanya auto-fill jika field kosong dan ada tanggal pelaksanaan
  if (tanggalPelaksanaan && !hariTanggalField.value) {
    hariTanggalField.value = formatTanggalIndonesia(tanggalPelaksanaan);
  }
});
</script>
</body>
</html>
