<?php
$user = current_user();

// Query Supabase untuk data statistik

// Total pengajuan dokumen dari halaman requests (pengajuan_dokumen)
$pengajuan_result = supabase_request('GET', 'pengajuan_dokumen?select=id');
$total_pengajuan = 0;
if ($pengajuan_result['code'] === 200 && !empty($pengajuan_result['data'])) {
    $total_pengajuan = count($pengajuan_result['data']);
}

// Hitung dokumen selesai dari halaman issued
$riwayat_result = supabase_request('GET', 'riwayat?status=eq.Selesai&select=pengajuan_id');
$total_selesai = 0;
if ($riwayat_result['code'] === 200 && !empty($riwayat_result['data'])) {
    $pengajuan_ids = array_unique(array_column($riwayat_result['data'], 'pengajuan_id'));
    $total_selesai = count($pengajuan_ids);
}

// Hitung pelaporan masalah dari halaman reports
$laporan_result = supabase_request('GET', 'pelaporan_masalah?select=id');
$total_laporan = 0;
if ($laporan_result['code'] === 200 && !empty($laporan_result['data'])) {
    $total_laporan = count($laporan_result['data']);
}
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Dashboard - SiCakap</title>
  <link rel="stylesheet" href="<?= h(rtrim(APP_URL, '/')) ?>/assets/css/style.css">
</head>
<body>
<?php include __DIR__ . '/_sidebar.php'; ?>
<div class="main">
  <header class="topbar">
    <div class="brand">SiCakap</div>
    <div class="user">
      <span>Hai, <?= h($user['name'] ?? $user['username'] ?? 'Administrator') ?></span>
      <a href="?p=logout" class="logout">Logout</a>
    </div>
  </header>

  <section class="content">
    <h1>Selamat Datang</h1>

    <div class="welcome">
      <p>Selamat datang di panel admin SiCakap. Gunakan menu di samping untuk mengelola pengajuan dokumen dan buat surat.</p>
    </div>

    <div class="cards">
      <div class="card">
        <div class="card-title">Pengajuan Dokumen</div>
        <div class="card-value"><?= $total_pengajuan ?></div>
        <div class="card-desc">Total pengajuan dokumen</div>
      </div>

      <div class="card">
        <div class="card-title">Dokumen Selesai</div>
        <div class="card-value"><?= $total_selesai ?></div>
        <div class="card-desc">Jumlah surat yang selesai dibuat</div>
      </div>

      <div class="card">
        <div class="card-title">Laporan</div>
        <div class="card-value"><?= $total_laporan ?></div>
        <div class="card-desc">Detail pelaporan masalah</div>
      </div>
    </div>

    <div class="quick-actions">
      <a class="btn" href="?p=requests">Pengajuan Dokumen</a>
      <a class="btn outline" href="?p=sppd">Buat Surat</a>
    </div>
  </section>
</div>
</body>
</html>