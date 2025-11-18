<?php
$pdo = db();
$user = current_user();

// Ambil data penduduk dengan pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Search functionality
$search = $_GET['search'] ?? '';
$where = '';
$params = [];

if ($search) {
    $where = " WHERE nik LIKE :search OR name LIKE :search OR alamat LIKE :search";
    $params[':search'] = "%$search%";
}

// Count total
$count_sql = "SELECT COUNT(*) FROM residents" . $where;
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $limit);

// Get residents
$sql = "SELECT * FROM residents" . $where . " ORDER BY name ASC LIMIT :limit OFFSET :offset";
$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$residents = $stmt->fetchAll();

$flash = flash_get();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Data Penduduk - SiCakap</title>
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
    <h1>Data Penduduk</h1>

    <?php if ($flash): ?>
    <div class="alert alert-success"><?= h($flash) ?></div>
    <?php endif; ?>

    <div class="toolbar">
      <div class="toolbar-left">
        <a href="?p=resident_form" class="btn btn-primary">+ Tambah Penduduk</a>
      </div>
      <div class="toolbar-right">
        <form method="get" class="search-form">
          <input type="hidden" name="p" value="residents">
          <input type="text" name="search" placeholder="Cari NIK, Nama, atau Alamat..." value="<?= h($search) ?>" class="search-input">
          <button type="submit" class="btn btn-secondary">Cari</button>
          <?php if ($search): ?>
            <a href="?p=residents" class="btn btn-light">Reset</a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>No</th>
            <th>NIK</th>
            <th>Nama</th>
            <th>Tempat/Tanggal Lahir</th>
            <th>Jenis Kelamin</th>
            <th>Alamat</th>
            <th>RT/RW</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($residents)): ?>
          <tr>
            <td colspan="8" class="text-center">Tidak ada data penduduk.</td>
          </tr>
          <?php else: ?>
            <?php foreach ($residents as $i => $r): ?>
            <tr>
              <td><?= $offset + $i + 1 ?></td>
              <td><?= h($r['nik']) ?></td>
              <td><?= h($r['name']) ?></td>
              <td>
                <?php
                $ttl = [];
                if ($r['tempat_lahir']) $ttl[] = h($r['tempat_lahir']);
                if ($r['tanggal_lahir']) $ttl[] = date('d-m-Y', strtotime($r['tanggal_lahir']));
                echo implode(', ', $ttl);
                ?>
              </td>
              <td><?= h($r['jenis_kelamin']) ?></td>
              <td><?= h($r['alamat']) ?></td>
              <td>
                <?php
                $rtrw = [];
                if ($r['rt']) $rtrw[] = 'RT ' . h($r['rt']);
                if ($r['rw']) $rtrw[] = 'RW ' . h($r['rw']);
                echo implode('/', $rtrw);
                ?>
              </td>
              <td class="actions">
                <a href="?p=resident_form&id=<?= $r['id'] ?>" class="btn-small btn-edit" title="Edit">Edit</a>
                <a href="?p=resident_delete&id=<?= $r['id'] ?>" class="btn-small btn-delete" 
                   onclick="return confirm('Yakin ingin menghapus data <?= h($r['name']) ?>?')" 
                   title="Hapus">Hapus</a>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?p=residents&page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="btn btn-light">« Sebelumnya</a>
      <?php endif; ?>
      
      <span class="page-info">Halaman <?= $page ?> dari <?= $total_pages ?></span>
      
      <?php if ($page < $total_pages): ?>
        <a href="?p=residents&page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="btn btn-light">Selanjutnya »</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <div class="info-text">
      Total: <?= $total ?> penduduk
      <?php if ($search): ?>
        (hasil pencarian untuk "<?= h($search) ?>")
      <?php endif; ?>
    </div>

  </section>
</div>
</body>
</html>
