<?php
$pdo = db();
$residents_count = $pdo->query("SELECT COUNT(*) FROM residents")->fetchColumn();
$requests_count = $pdo->query("SELECT COUNT(*) FROM letter_requests")->fetchColumn();
$finished_count = $pdo->query("SELECT COUNT(*) FROM letter_requests WHERE status='finished'")->fetchColumn();
$user = current_user();
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
      <span>Hai, <?= h($user['name'] ?? $user['email'] ?? 'Administrator') ?></span>
      <a href="?p=logout" class="logout">Logout</a>
    </div>
  </header>

  <section class="content">
    <h1>Selamat Datang</h1>

    <div class="welcome">
      <p>Selamat datang di panel admin SiCakap. Gunakan menu di samping untuk mengelola data penduduk dan permintaan surat.</p>
    </div>

    <div class="cards">
      <div class="card">
        <div class="card-title">Data Penduduk</div>
        <div class="card-value"><?= intval($residents_count) ?></div>
        <div class="card-desc">Jumlah data penduduk terdaftar</div>
      </div>

      <div class="card">
        <div class="card-title">Permintaan Surat</div>
        <div class="card-value"><?= intval($requests_count) ?></div>
        <div class="card-desc">Total permintaan surat</div>
      </div>

      <div class="card">
        <div class="card-title">Surat Selesai</div>
        <div class="card-value"><?= intval($finished_count) ?></div>
        <div class="card-desc">Jumlah surat yang selesai dibuat</div>
      </div>
    </div>

    <div class="quick-actions">
      <a class="btn" href="?p=residents">Kelola Penduduk</a>
      <a class="btn outline" href="?p=requests">Kelola Permintaan</a>
    </div>
  </section>
</div>
</body>
</html>