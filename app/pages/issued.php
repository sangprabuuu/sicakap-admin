<?php
$pdo = db();
$user = current_user();

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Search
$search = $_GET['search'] ?? '';

$where = [];
$params = [];

if ($search) {
    $where[] = "(il.letter_no LIKE :search OR lr.resident_nik LIKE :search OR lr.resident_name LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

// Count total
$count_sql = "SELECT COUNT(*) 
              FROM issued_letters il
              LEFT JOIN letter_requests lr ON il.request_id = lr.id
              {$where_sql}";
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $limit);

// Get issued letters
$sql = "SELECT il.*, 
               lr.resident_nik, lr.resident_name, lr.no_request,
               lt.name as template_name, lt.code as template_code
        FROM issued_letters il
        LEFT JOIN letter_requests lr ON il.request_id = lr.id
        LEFT JOIN letter_templates lt ON il.template_id = lt.id
        {$where_sql}
        ORDER BY il.issued_at DESC, il.created_at DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$letters = $stmt->fetchAll();

// Get statistics
$stats = [
    'total' => $pdo->query("SELECT COUNT(*) FROM issued_letters")->fetchColumn(),
    'today' => $pdo->query("SELECT COUNT(*) FROM issued_letters WHERE DATE(issued_at) = CURDATE()")->fetchColumn(),
    'this_month' => $pdo->query("SELECT COUNT(*) FROM issued_letters WHERE MONTH(issued_at) = MONTH(CURDATE()) AND YEAR(issued_at) = YEAR(CURDATE())")->fetchColumn(),
];

$flash = flash_get();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Surat Selesai - SiCakap</title>
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
    <h1>Surat Selesai</h1>

    <?php if ($flash): ?>
    <div class="alert alert-success"><?= h($flash) ?></div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="cards">
      <div class="card">
        <div class="card-title">Total Surat</div>
        <div class="card-value"><?= intval($stats['total']) ?></div>
        <div class="card-desc">Surat yang telah diterbitkan</div>
      </div>

      <div class="card">
        <div class="card-title">Hari Ini</div>
        <div class="card-value"><?= intval($stats['today']) ?></div>
        <div class="card-desc">Surat diterbitkan hari ini</div>
      </div>

      <div class="card">
        <div class="card-title">Bulan Ini</div>
        <div class="card-value"><?= intval($stats['this_month']) ?></div>
        <div class="card-desc">Surat diterbitkan bulan ini</div>
      </div>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
      <div class="toolbar-left">
        <span class="info-text">Menampilkan <?= count($letters) ?> dari <?= $total ?> surat</span>
      </div>
      <div class="toolbar-right">
        <form method="get" class="search-form">
          <input type="hidden" name="p" value="issued">
          <input type="text" name="search" placeholder="Cari No. Surat, NIK, atau Nama..." value="<?= h($search) ?>" class="search-input">
          <button type="submit" class="btn btn-secondary">Cari</button>
          <?php if ($search): ?>
            <a href="?p=issued" class="btn btn-light">Reset</a>
          <?php endif; ?>
        </form>
      </div>
    </div>

    <!-- Table -->
    <div class="table-responsive">
      <table class="data-table">
        <thead>
          <tr>
            <th>No</th>
            <th>No. Surat</th>
            <th>Tanggal Terbit</th>
            <th>No. Request</th>
            <th>NIK</th>
            <th>Nama Pemohon</th>
            <th>Jenis Surat</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($letters)): ?>
          <tr>
            <td colspan="8" class="text-center">Tidak ada surat yang diterbitkan.</td>
          </tr>
          <?php else: ?>
            <?php foreach ($letters as $i => $letter): ?>
            <tr>
              <td><?= $offset + $i + 1 ?></td>
              <td><strong><?= h($letter['letter_no'] ?? '-') ?></strong></td>
              <td>
                <?php
                if ($letter['issued_at']) {
                    echo date('d/m/Y H:i', strtotime($letter['issued_at']));
                } else {
                    echo date('d/m/Y', strtotime($letter['created_at']));
                }
                ?>
              </td>
              <td><?= h($letter['no_request'] ?? '-') ?></td>
              <td><?= h($letter['resident_nik'] ?? '-') ?></td>
              <td><?= h($letter['resident_name'] ?? '-') ?></td>
              <td><?= h($letter['template_name'] ?? $letter['template_code'] ?? 'Template #' . $letter['template_id']) ?></td>
              <td class="actions">
                <?php if ($letter['generated_pdf']): ?>
                  <a href="<?= h(rtrim(APP_URL, '/')) ?>/app/generated/<?= h($letter['generated_pdf']) ?>" 
                     class="btn-small btn-info" 
                     target="_blank" 
                     title="Lihat PDF">
                    📄 PDF
                  </a>
                <?php else: ?>
                  <a href="?p=generate_pdf&request_id=<?= $letter['request_id'] ?>" 
                     class="btn-small btn-secondary" 
                     title="Generate PDF">
                    Generate
                  </a>
                <?php endif; ?>
                <a href="?p=request_detail&id=<?= $letter['request_id'] ?>" 
                   class="btn-small btn-light" 
                   title="Lihat Detail Request">
                  Detail
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- Pagination -->
    <?php if ($total_pages > 1): ?>
    <div class="pagination">
      <?php if ($page > 1): ?>
        <a href="?p=issued&page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="btn btn-light">« Sebelumnya</a>
      <?php endif; ?>
      
      <span class="page-info">Halaman <?= $page ?> dari <?= $total_pages ?></span>
      
      <?php if ($page < $total_pages): ?>
        <a href="?p=issued&page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="btn btn-light">Selanjutnya »</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Info -->
    <div class="info-box">
      <h3>📋 Informasi</h3>
      <ul>
        <li>Halaman ini menampilkan semua surat yang telah diterbitkan</li>
        <li>Klik tombol <strong>PDF</strong> untuk melihat atau mengunduh surat</li>
        <li>Klik tombol <strong>Detail</strong> untuk melihat informasi lengkap permintaan</li>
        <li>Surat yang sudah diterbitkan tidak dapat dihapus</li>
      </ul>
    </div>

  </section>
</div>
</body>
</html>
