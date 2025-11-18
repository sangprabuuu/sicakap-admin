<?php
$pdo = db();
$user = current_user();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$is_edit = $id > 0;

// Default values
$data = [
    'id' => '',
    'nik' => '',
    'name' => '',
    'tempat_lahir' => '',
    'tanggal_lahir' => '',
    'jenis_kelamin' => '',
    'agama' => '',
    'alamat' => '',
    'rt' => '',
    'rw' => '',
    'desa' => '',
    'pekerjaan' => '',
    'status_perkawinan' => '',
    'kewarganegaraan' => 'WNI',
    'nama_ayah' => '',
    'nama_ibu' => '',
];

// If edit mode, load data
if ($is_edit) {
    $stmt = $pdo->prepare("SELECT * FROM residents WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $resident = $stmt->fetch();
    
    if (!$resident) {
        flash_set('Data penduduk tidak ditemukan.');
        header('Location: ?p=residents');
        exit;
    }
    
    $data = array_merge($data, $resident);
}

$flash = flash_get();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= $is_edit ? 'Edit' : 'Tambah' ?> Penduduk - SiCakap</title>
  <link rel="stylesheet" href="<?= h(rtrim(APP_URL, '/')) ?>/assets/css/style.css">
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
    <div class="page-header">
      <h1><?= $is_edit ? 'Edit Data Penduduk' : 'Tambah Data Penduduk' ?></h1>
      <a href="?p=residents" class="btn btn-light">← Kembali ke Daftar</a>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-danger"><?= h($flash) ?></div>
    <?php endif; ?>

    <div class="form-container">
      <form method="post" action="?p=resident_save" class="resident-form">
        <?php if ($is_edit): ?>
        <input type="hidden" name="id" value="<?= h($data['id']) ?>">
        <?php endif; ?>

        <div class="form-section">
          <h3>Data Identitas</h3>
          
          <div class="form-row">
            <div class="form-group">
              <label for="nik">NIK <span class="required">*</span></label>
              <input type="text" id="nik" name="nik" value="<?= h($data['nik']) ?>" 
                     required maxlength="50" placeholder="Nomor Induk Kependudukan">
            </div>
            
            <div class="form-group">
              <label for="name">Nama Lengkap <span class="required">*</span></label>
              <input type="text" id="name" name="name" value="<?= h($data['name']) ?>" 
                     required maxlength="255" placeholder="Nama lengkap sesuai KTP">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="tempat_lahir">Tempat Lahir</label>
              <input type="text" id="tempat_lahir" name="tempat_lahir" 
                     value="<?= h($data['tempat_lahir']) ?>" maxlength="255">
            </div>
            
            <div class="form-group">
              <label for="tanggal_lahir">Tanggal Lahir</label>
              <input type="date" id="tanggal_lahir" name="tanggal_lahir" 
                     value="<?= h($data['tanggal_lahir']) ?>">
            </div>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="jenis_kelamin">Jenis Kelamin</label>
              <select id="jenis_kelamin" name="jenis_kelamin">
                <option value="">-- Pilih --</option>
                <option value="Laki-laki" <?= $data['jenis_kelamin'] == 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                <option value="Perempuan" <?= $data['jenis_kelamin'] == 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
              </select>
            </div>
            
            <div class="form-group">
              <label for="agama">Agama</label>
              <select id="agama" name="agama">
                <option value="">-- Pilih --</option>
                <option value="Islam" <?= $data['agama'] == 'Islam' ? 'selected' : '' ?>>Islam</option>
                <option value="Kristen" <?= $data['agama'] == 'Kristen' ? 'selected' : '' ?>>Kristen</option>
                <option value="Katolik" <?= $data['agama'] == 'Katolik' ? 'selected' : '' ?>>Katolik</option>
                <option value="Hindu" <?= $data['agama'] == 'Hindu' ? 'selected' : '' ?>>Hindu</option>
                <option value="Buddha" <?= $data['agama'] == 'Buddha' ? 'selected' : '' ?>>Buddha</option>
                <option value="Konghucu" <?= $data['agama'] == 'Konghucu' ? 'selected' : '' ?>>Konghucu</option>
              </select>
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3>Data Alamat</h3>
          
          <div class="form-group">
            <label for="alamat">Alamat</label>
            <textarea id="alamat" name="alamat" rows="3" placeholder="Jalan, nomor rumah, kelurahan"><?= h($data['alamat']) ?></textarea>
          </div>

          <div class="form-row">
            <div class="form-group">
              <label for="rt">RT</label>
              <input type="text" id="rt" name="rt" value="<?= h($data['rt']) ?>" 
                     maxlength="10" placeholder="001">
            </div>
            
            <div class="form-group">
              <label for="rw">RW</label>
              <input type="text" id="rw" name="rw" value="<?= h($data['rw']) ?>" 
                     maxlength="10" placeholder="001">
            </div>

            <div class="form-group">
              <label for="desa">Desa/Kelurahan</label>
              <input type="text" id="desa" name="desa" value="<?= h($data['desa']) ?>" 
                     maxlength="255" placeholder="Nama desa/kelurahan">
            </div>
          </div>
        </div>

        <div class="form-section">
          <h3>Data Pekerjaan & Status</h3>
          
          <div class="form-row">
            <div class="form-group">
              <label for="pekerjaan">Pekerjaan</label>
              <input type="text" id="pekerjaan" name="pekerjaan" 
                     value="<?= h($data['pekerjaan']) ?>" maxlength="255" placeholder="PNS, Wiraswasta, dll">
            </div>
            
            <div class="form-group">
              <label for="status_perkawinan">Status Perkawinan</label>
              <select id="status_perkawinan" name="status_perkawinan">
                <option value="">-- Pilih --</option>
                <option value="Belum Kawin" <?= $data['status_perkawinan'] == 'Belum Kawin' ? 'selected' : '' ?>>Belum Kawin</option>
                <option value="Kawin" <?= $data['status_perkawinan'] == 'Kawin' ? 'selected' : '' ?>>Kawin</option>
                <option value="Cerai Hidup" <?= $data['status_perkawinan'] == 'Cerai Hidup' ? 'selected' : '' ?>>Cerai Hidup</option>
                <option value="Cerai Mati" <?= $data['status_perkawinan'] == 'Cerai Mati' ? 'selected' : '' ?>>Cerai Mati</option>
              </select>
            </div>
          </div>

          <div class="form-group">
            <label for="kewarganegaraan">Kewarganegaraan</label>
            <input type="text" id="kewarganegaraan" name="kewarganegaraan" 
                   value="<?= h($data['kewarganegaraan']) ?>" maxlength="50" placeholder="WNI">
          </div>
        </div>

        <div class="form-section">
          <h3>Data Orang Tua</h3>
          
          <div class="form-row">
            <div class="form-group">
              <label for="nama_ayah">Nama Ayah</label>
              <input type="text" id="nama_ayah" name="nama_ayah" 
                     value="<?= h($data['nama_ayah']) ?>" maxlength="255">
            </div>
            
            <div class="form-group">
              <label for="nama_ibu">Nama Ibu</label>
              <input type="text" id="nama_ibu" name="nama_ibu" 
                     value="<?= h($data['nama_ibu']) ?>" maxlength="255">
            </div>
          </div>
        </div>

        <div class="form-actions">
          <button type="submit" class="btn btn-primary">
            <?= $is_edit ? 'Simpan Perubahan' : 'Tambah Penduduk' ?>
          </button>
          <a href="?p=residents" class="btn btn-light">Batal</a>
        </div>
      </form>
    </div>

  </section>
</div>
</body>
</html>
