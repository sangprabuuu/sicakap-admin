<?php
$user = current_user();

// Query Supabase untuk data statistik
$pengajuan_endpoint = 'pengajuan_dokumen?select=id';
$pengajuan_response = supabase_request('GET', $pengajuan_endpoint);
$total_pengajuan = !empty($pengajuan_response['data']) ? count($pengajuan_response['data']) : 0;

// Hitung dokumen selesai dari tabel riwayat
$selesai_endpoint = 'riwayat?status=eq.Selesai&select=id';
$selesai_response = supabase_request('GET', $selesai_endpoint);
$surat_selesai = !empty($selesai_response['data']) ? count($selesai_response['data']) : 0;

// Hitung total laporan (SPPD + Undangan)
$sppd_endpoint = 'pengajuan_sppd?select=id';
$sppd_response = supabase_request('GET', $sppd_endpoint);
$total_sppd = !empty($sppd_response['data']) ? count($sppd_response['data']) : 0;

$undangan_endpoint = 'surat_undangan?select=id';
$undangan_response = supabase_request('GET', $undangan_endpoint);
$total_undangan = !empty($undangan_response['data']) ? count($undangan_response['data']) : 0;

$total_laporan = $total_sppd + $total_undangan;
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
        <div class="card-value"><?= intval($total_pengajuan) ?></div>
        <div class="card-desc">Total pengajuan dokumen</div>
      </div>

      <div class="card">
        <div class="card-title">Dokumen Selesai</div>
        <div class="card-value"><?= intval($surat_selesai) ?></div>
        <div class="card-desc">Jumlah surat yang selesai dibuat</div>
      </div>

      <div class="card">
        <div class="card-title">Laporan</div>
        <div class="card-value"><?= intval($total_laporan) ?></div>
        <div class="card-desc">SPPD (<?= intval($total_sppd) ?>) & Undangan (<?= intval($total_undangan) ?>)</div>
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