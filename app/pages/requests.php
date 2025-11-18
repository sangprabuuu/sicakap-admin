<?php
$pdo = db();
$user = current_user();

// Pagination
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$offset = ($page - 1) * $limit;

// Filter status
$status_filter = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$where = [];
$params = [];

if ($status_filter) {
    $where[] = "lr.status = :status";
    $params[':status'] = $status_filter;
}

if ($search) {
    $where[] = "(lr.no_request LIKE :search OR lr.resident_nik LIKE :search OR lr.resident_name LIKE :search)";
    $params[':search'] = "%$search%";
}

$where_sql = $where ? ' WHERE ' . implode(' AND ', $where) : '';

// Count total
$count_sql = "SELECT COUNT(*) FROM letter_requests lr" . $where_sql;
$stmt = $pdo->prepare($count_sql);
$stmt->execute($params);
$total = $stmt->fetchColumn();
$total_pages = ceil($total / $limit);

// Get requests with template name
$sql = "SELECT lr.*, lt.name as template_name 
        FROM letter_requests lr
        LEFT JOIN letter_templates lt ON lr.template_id = lt.id
        {$where_sql}
        ORDER BY lr.requested_at DESC, lr.created_at DESC
        LIMIT :limit OFFSET :offset";

$stmt = $pdo->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$requests = $stmt->fetchAll();

// Get status count for filter badges
$status_counts = [];
$status_sql = "SELECT status, COUNT(*) as count FROM letter_requests GROUP BY status";
foreach ($pdo->query($status_sql) as $row) {
    $status_counts[$row['status']] = $row['count'];
}

$flash = flash_get();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Permintaan Surat - SiCakap</title>
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
    <h1>Permintaan Surat</h1>

    <?php if ($flash): ?>
    <div class="alert alert-success"><?= h($flash) ?></div>
    <?php endif; ?>

    <!-- Status Filter -->
    <div class="status-filter">
      <a href="?p=requests" class="filter-badge <?= !$status_filter ? 'active' : '' ?>">
        Semua (<?= array_sum($status_counts) ?>)
      </a>
      <a href="?p=requests&status=pending" class="filter-badge badge-pending <?= $status_filter == 'pending' ? 'active' : '' ?>">
        Pending (<?= $status_counts['pending'] ?? 0 ?>)
      </a>
      <a href="?p=requests&status=verifikasi" class="filter-badge badge-verifikasi <?= $status_filter == 'verifikasi' ? 'active' : '' ?>">
        Verifikasi (<?= $status_counts['verifikasi'] ?? 0 ?>)
      </a>
      <a href="?p=requests&status=approved" class="filter-badge badge-approved <?= $status_filter == 'approved' ? 'active' : '' ?>">
        Disetujui (<?= $status_counts['approved'] ?? 0 ?>)
      </a>
      <a href="?p=requests&status=processing" class="filter-badge badge-processing <?= $status_filter == 'processing' ? 'active' : '' ?>">
        Proses (<?= $status_counts['processing'] ?? 0 ?>)
      </a>
      <a href="?p=requests&status=finished" class="filter-badge badge-finished <?= $status_filter == 'finished' ? 'active' : '' ?>">
        Selesai (<?= $status_counts['finished'] ?? 0 ?>)
      </a>
      <a href="?p=requests&status=rejected" class="filter-badge badge-rejected <?= $status_filter == 'rejected' ? 'active' : '' ?>">
        Ditolak (<?= $status_counts['rejected'] ?? 0 ?>)
      </a>
    </div>

    <!-- Toolbar -->
    <div class="toolbar">
      <div class="toolbar-left">
        <span class="info-text">Menampilkan <?= count($requests) ?> dari <?= $total ?> permintaan</span>
      </div>
      <div class="toolbar-right">
        <form method="get" class="search-form">
          <input type="hidden" name="p" value="requests">
          <?php if ($status_filter): ?>
          <input type="hidden" name="status" value="<?= h($status_filter) ?>">
          <?php endif; ?>
          <input type="text" name="search" placeholder="Cari No. Request, NIK, atau Nama..." value="<?= h($search) ?>" class="search-input">
          <button type="submit" class="btn btn-secondary">Cari</button>
          <?php if ($search): ?>
            <a href="?p=requests<?= $status_filter ? '&status=' . h($status_filter) : '' ?>" class="btn btn-light">Reset</a>
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
            <th>No. Request</th>
            <th>Tanggal</th>
            <th>NIK</th>
            <th>Nama Pemohon</th>
            <th>Jenis Surat</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($requests)): ?>
          <tr>
            <td colspan="8" class="text-center">Tidak ada permintaan surat.</td>
          </tr>
          <?php else: ?>
            <?php foreach ($requests as $i => $req): ?>
            <tr>
              <td><?= $offset + $i + 1 ?></td>
              <td><strong><?= h($req['no_request'] ?? '-') ?></strong></td>
              <td>
                <?php
                if ($req['requested_at']) {
                    echo date('d/m/Y H:i', strtotime($req['requested_at']));
                } else {
                    echo date('d/m/Y', strtotime($req['created_at']));
                }
                ?>
              </td>
              <td><?= h($req['resident_nik']) ?></td>
              <td><?= h($req['resident_name']) ?></td>
              <td><?= h($req['template_name'] ?? 'Template #' . $req['template_id']) ?></td>
              <td>
                <?php
                $status_labels = [
                    'pending' => 'Pending',
                    'verifikasi' => 'Verifikasi',
                    'approved' => 'Disetujui',
                    'rejected' => 'Ditolak',
                    'processing' => 'Diproses',
                    'finished' => 'Selesai'
                ];
                $status = $req['status'];
                $label = $status_labels[$status] ?? $status;
                ?>
                <span class="badge badge-<?= h($status) ?>"><?= h($label) ?></span>
              </td>
              <td class="actions">
                <a href="?p=request_detail&id=<?= $req['id'] ?>" class="btn btn-sm btn-primary" title="Detail">
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M8 2C4.5 2 1.5 4.5 0 8c1.5 3.5 4.5 6 8 6s6.5-2.5 8-6c-1.5-3.5-4.5-6-8-6zm0 10c-2.2 0-4-1.8-4-4s1.8-4 4-4 4 1.8 4 4-1.8 4-4 4zm0-6.5c-1.4 0-2.5 1.1-2.5 2.5s1.1 2.5 2.5 2.5 2.5-1.1 2.5-2.5-1.1-2.5-2.5-2.5z"/>
                  </svg>
                </a>
                <?php if ($status === 'pending' || $status === 'verifikasi'): ?>
                <a href="?p=request_approve&id=<?= $req['id'] ?>&action=approve" 
                   class="btn btn-sm btn-success" 
                   onclick="return confirm('Setujui permintaan ini?')" 
                   title="Setujui">
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M13.5 2L6 9.5 2.5 6 1 7.5l5 5 9-9z"/>
                  </svg>
                </a>
                <a href="?p=request_approve&id=<?= $req['id'] ?>&action=reject" 
                   class="btn btn-sm btn-danger" 
                   onclick="return confirm('Tolak permintaan ini?')" 
                   title="Tolak">
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                    <path d="M3.5 2L2 3.5 6.5 8 2 12.5 3.5 14 8 9.5 12.5 14 14 12.5 9.5 8 14 3.5 12.5 2 8 6.5z"/>
                  </svg>
                </a>
                <?php endif; ?>
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
        <a href="?p=requests&page=<?= $page - 1 ?><?= $status_filter ? '&status=' . h($status_filter) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="btn btn-light">« Sebelumnya</a>
      <?php endif; ?>
      
      <span class="page-info">Halaman <?= $page ?> dari <?= $total_pages ?></span>
      
      <?php if ($page < $total_pages): ?>
        <a href="?p=requests&page=<?= $page + 1 ?><?= $status_filter ? '&status=' . h($status_filter) : '' ?><?= $search ? '&search=' . urlencode($search) : '' ?>" class="btn btn-light">Selanjutnya »</a>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  </section>
</div>
</body>
</html>
