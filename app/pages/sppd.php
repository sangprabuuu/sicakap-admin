<?php
$pdo = db();
$user = current_user();

// Ambil data SPPD dengan pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Search functionality
$search = $_GET['search'] ?? '';
$where = '';
$params = [];

if ($search) {
    $where = " WHERE nomor LIKE :search OR nama LIKE :search OR tempat_tujuan LIKE :search";
    $params[':search'] = "%$search%";
}

// Count total
$count_sql = "SELECT COUNT(*) FROM sppd" . $where;
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $limit);

// Get SPPD
$sql = "SELECT * FROM sppd" . $where . " ORDER BY tanggal DESC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$sppd_list = $stmt->fetchAll();

$flash = flash_get();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Data SPPD - SiCakap</title>
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
    <h1>Data SPPD</h1>

    <?php if ($flash): ?>
    <div class="alert alert-success"><?= h($flash) ?></div>
    <?php endif; ?>

    <div class="toolbar">
      <div class="toolbar-left">
        <a href="?p=sppd_form" class="btn btn-primary">+ Buat Surat</a>
      </div>
      <div class="toolbar-right">
        <form method="get" class="search-form">
          <input type="hidden" name="p" value="sppd">
          <input type="text" name="search" placeholder="Cari" value="<?= h($search) ?>" class="search-input">
          <button type="submit" class="btn btn-secondary">Cari</button>
          <?php if ($search): ?>
            <a href="?p=sppd" class="btn btn-light">Reset</a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>No.</th>
            <th>Nomor</th>
            <th>Tanggal</th>
            <th>NIP</th>
            <th>Nama</th>
            <th>Tempat Tujuan</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($sppd_list)): ?>
          <tr>
            <td colspan="7" class="text-center">Tidak ada data SPPD.</td>
          </tr>
          <?php else: ?>
          <?php foreach ($sppd_list as $i => $item): ?>
          <tr>
            <td><?= $offset + $i + 1 ?></td>
            <td><?= h($item['nomor']) ?></td>
            <td><?= h(date('d F Y', strtotime($item['tanggal']))) ?></td>
            <td><?= h($item['nip']) ?></td>
            <td><?= h($item['nama']) ?></td>
            <td><?= h($item['tempat_tujuan']) ?></td>
            <td>
              <a href="?p=sppd_print&id=<?= h($item['id']) ?>" class="btn btn-sm btn-success" target="_blank" title="Print Surat">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2H5zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1z"/>
                  <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2V7zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
                </svg>
              </a>
              <a href="?p=sppd_form&id=<?= h($item['id']) ?>" class="btn btn-sm btn-primary" title="Edit">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M12.146.146a.5.5 0 0 1 .708 0l3 3a.5.5 0 0 1 0 .708l-10 10a.5.5 0 0 1-.168.11l-5 2a.5.5 0 0 1-.65-.65l2-5a.5.5 0 0 1 .11-.168l10-10zM11.207 2.5 13.5 4.793 14.793 3.5 12.5 1.207 11.207 2.5zm1.586 3L10.5 3.207 4 9.707V10h.5a.5.5 0 0 1 .5.5v.5h.5a.5.5 0 0 1 .5.5v.5h.293l6.5-6.5zm-9.761 5.175-.106.106-1.528 3.821 3.821-1.528.106-.106A.5.5 0 0 1 5 12.5V12h-.5a.5.5 0 0 1-.5-.5V11h-.5a.5.5 0 0 1-.468-.325z"/>
                </svg>
              </a>
              <a href="?p=sppd_delete&id=<?= h($item['id']) ?>" class="btn btn-sm btn-danger" onclick="return confirm('Hapus data ini?')" title="Hapus">
                <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                  <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                  <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                </svg>
              </a>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <div class="pagination-info">
      Total: <?= $total ?> SPPD
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?p=sppd&page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="btn btn-sm btn-light">← Prev</a>
      <?php endif; ?>
      
      <span class="page-info">Halaman <?= $page ?> dari <?= $total_pages ?></span>
      
      <?php if ($page < $total_pages): ?>
        <a href="?p=sppd&page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="btn btn-sm btn-light">Next →</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </section>
</div>
</body>
</html>
