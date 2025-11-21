<?php
$user = current_user();

// Get SPPD data if editing
$id = $_GET['id'] ?? '';
$sppd = null;

if ($id) {
    $result = supabase_request('GET', "pengajuan_sppd?id=eq.$id&select=*");
    if (!empty($result['data'])) {
        $sppd = $result['data'][0];
    } else {
        flash_set('Data SPPD tidak ditemukan');
        header('Location: ' . APP_URL . '/?p=sppd');
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
  <title><?= $sppd ? 'Edit' : 'Tambah' ?> SPPD - SiCakap</title>
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
    .form-group input[type="number"],
    .form-group select {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
      background: #f5f5f5;
    }
    .form-group input:focus,
    .form-group select:focus {
      outline: none;
      border-color: #4a7c2c;
      background: #fff;
    }
    .form-row {
      display: grid;
      grid-template-columns: 1fr 1fr 1fr;
      gap: 20px;
    }
    .form-actions {
      margin-top: 30px;
      display: flex;
      gap: 10px;
    }
    .radio-group {
      display: flex;
      gap: 20px;
      margin-top: 8px;
    }
    .radio-group label {
      font-weight: normal;
      display: flex;
      align-items: center;
      gap: 5px;
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
    <div style="max-width: 1200px; margin: 0 auto;">
      <?php if ($flash): ?>
      <div class="alert alert-danger"><?= h($flash) ?></div>
      <?php endif; ?>

      <div class="form-header">
        <?= $sppd ? 'Edit' : 'Input' ?> SPPD
      </div>

      <form method="post" action="?p=sppd_save">
        <?php if ($sppd): ?>
        <input type="hidden" name="id" value="<?= h($sppd['id']) ?>">
        <?php endif; ?>

        <div class="form-row">
          <div class="form-group">
            <label>Tanggal Pembuatan Surat</label>
            <input type="date" name="tanggal_pembuatan" value="<?= h($sppd['tanggal_pembuatan'] ?? date('Y-m-d')) ?>" required>
          </div>
          <div class="form-group"></div>
          <div class="form-group"></div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Nomor SPPD</label>
            <input type="text" name="nomor_sppd" placeholder="Contoh: 001/SPPD/2025" value="<?= h($sppd['nomor_sppd'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Nama Pegawai</label>
            <input type="text" name="nama_pegawai" placeholder="Nama Pegawai" value="<?= h($sppd['nama_pegawai'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>NIP</label>
            <input type="text" name="nip" placeholder="NIP" value="<?= h($sppd['nip'] ?? '') ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Jabatan</label>
            <input type="text" name="jabatan" placeholder="Jabatan" value="<?= h($sppd['jabatan'] ?? '') ?>" required>
          </div>
          <div class="form-group">
            <label>Alamat Tempat Tinggal</label>
            <input type="text" name="alamat_tempat_tinggal" placeholder="Alamat Tempat Tinggal" value="<?= h($sppd['alamat_tempat_tinggal'] ?? 'Desa Campakoah') ?>" required>
          </div>
          <div class="form-group">
            <label>Maksud Perjalanan</label>
            <input type="text" name="maksud_perjalanan" placeholder="Maksud Perjalanan" value="<?= h($sppd['maksud_perjalanan'] ?? '') ?>" required>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Tempat Tujuan</label>
            <input type="text" name="tempat_tujuan" placeholder="Tempat Tujuan" value="<?= h($sppd['tempat_tujuan'] ?? '') ?>" required>
          </div>
          <div class="form-group"></div>
          <div class="form-group"></div>
        </div>

        <div class="form-group">
          <label>Jenis Durasi</label>
          <div class="radio-group">
            <label>
              <input type="radio" name="jenis_durasi" value="harian" <?= ($sppd['jenis_durasi'] ?? '') == 'harian' ? 'checked' : '' ?> required>
              Harian
            </label>
            <label>
              <input type="radio" name="jenis_durasi" value="lebih dari 1 hari" <?= ($sppd['jenis_durasi'] ?? '') == 'lebih dari 1 hari' ? 'checked' : '' ?>>
              Lebih dari 1 hari
            </label>
          </div>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label>Tanggal Mulai</label>
            <input type="date" name="tanggal_mulai" value="<?= h($sppd['tanggal_mulai'] ?? '') ?>" required>
          </div>
          <div class="form-group" id="tanggal-selesai-group">
            <label>Tanggal Selesai</label>
            <input type="date" name="tanggal_selesai" id="tanggal-selesai" value="<?= h($sppd['tanggal_selesai'] ?? '') ?>" required>
          </div>
          <div class="form-group"></div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">Simpan</button>
          <a href="?p=sppd" class="btn" style="background:#e63939; color:white;">Batal</a>
        </div>
      </form>
    </div>
  </section>
</div>

<script>
  // Toggle tanggal selesai based on jenis durasi
  const radioHarian = document.querySelector('input[name="jenis_durasi"][value="harian"]');
  const radioLebihDari1Hari = document.querySelector('input[name="jenis_durasi"][value="lebih dari 1 hari"]');
  const tanggalSelesaiGroup = document.getElementById('tanggal-selesai-group');
  const tanggalSelesaiInput = document.getElementById('tanggal-selesai');
  const tanggalMulaiInput = document.querySelector('input[name="tanggal_mulai"]');

  function toggleTanggalSelesai() {
    if (radioHarian.checked) {
      tanggalSelesaiGroup.style.display = 'none';
      tanggalSelesaiInput.removeAttribute('required');
      // Set tanggal selesai sama dengan tanggal mulai untuk harian
      tanggalSelesaiInput.value = tanggalMulaiInput.value;
    } else {
      tanggalSelesaiGroup.style.display = 'block';
      tanggalSelesaiInput.setAttribute('required', 'required');
    }
  }

  // Update tanggal selesai ketika tanggal mulai berubah (jika harian)
  tanggalMulaiInput.addEventListener('change', function() {
    if (radioHarian.checked) {
      tanggalSelesaiInput.value = tanggalMulaiInput.value;
    }
  });

  radioHarian.addEventListener('change', toggleTanggalSelesai);
  radioLebihDari1Hari.addEventListener('change', toggleTanggalSelesai);

  // Initialize on page load
  toggleTanggalSelesai();
</script>

</body>
</html>
