<?php
$user = current_user();

// Ambil data pengajuan dari Supabase via REST API
$endpoint = 'pengajuan_dokumen?select=*&order=created_at.desc';

// Search by nama atau NIK
$search = $_GET['search'] ?? '';
if ($search) {
    $endpoint .= "&or=(nama.ilike.*$search*,nik.ilike.*$search*)";
}

$result = supabase_request('GET', $endpoint);

// Debug - tampilkan response
if (isset($_GET['debug'])) {
    echo '<pre style="background:#f5f5f5;padding:20px;margin:20px;border:1px solid #ccc;">';
    echo "Endpoint: " . $endpoint . "\n\n";
    echo "Response Code: " . $result['code'] . "\n\n";
    echo "Response Data:\n";
    print_r($result);
    echo '</pre>';
}

$requests = ($result['code'] === 200 && !empty($result['data'])) ? $result['data'] : [];

// Ambil status dari tabel riwayat untuk setiap pengajuan
if (!empty($requests)) {
    foreach ($requests as &$req) {
        // Query status terbaru dari tabel riwayat berdasarkan pengajuan_id
        $status_endpoint = "riwayat?select=status&pengajuan_id=eq." . $req['id'] . "&order=created_at.desc&limit=1";
        $status_result = supabase_request('GET', $status_endpoint);
        
        if ($status_result['code'] === 200 && !empty($status_result['data'])) {
            $req['status'] = $status_result['data'][0]['status'] ?? 'Diajukan';
        } else {
            $req['status'] = 'Diajukan';
        }
    }
    unset($req); // break reference
}

// Hitung status untuk filter SEBELUM filtering
$all_requests = $requests; // Simpan semua data untuk perhitungan
$status_counts = [
    'all' => count($all_requests),
    'Diajukan' => 0,
    'Diproses' => 0,
    'Ditolak' => 0,
    'Selesai' => 0
];

foreach ($all_requests as $req) {
    $status = $req['status'] ?? 'Diajukan';
    if (isset($status_counts[$status])) {
        $status_counts[$status]++;
    }
}

// Filter status jika ada (filter manual di PHP, bukan di API)
$status_filter = $_GET['status'] ?? '';
if ($status_filter) {
    $requests = array_filter($requests, function($req) use ($status_filter) {
        return ($req['status'] ?? 'Diajukan') === $status_filter;
    });
}

// Pagination manual (karena sudah dapat semua data)
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$total = count($requests);
$total_pages = ceil($total / $limit);
$offset = ($page - 1) * $limit;
$requests = array_slice($requests, $offset, $limit);

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
    <h1>Pengajuan Dokumen</h1>

    <?php if ($flash = flash_get()): ?>
    <div class="alert alert-success"><?= h($flash) ?></div>
    <?php endif; ?>

    <!-- Status Filter -->
    <div class="status-filter">
      <a href="?p=requests" class="filter-badge <?= empty($status_filter) ? 'active' : '' ?>">
        Semua (<?= $status_counts['all'] ?>)
      </a>
      <a href="?p=requests&status=Diajukan" class="filter-badge <?= $status_filter === 'Diajukan' ? 'active' : '' ?>">
        Diajukan (<?= $status_counts['Diajukan'] ?>)
      </a>
      <a href="?p=requests&status=Diproses" class="filter-badge <?= $status_filter === 'Diproses' ? 'active' : '' ?>">
        Diproses (<?= $status_counts['Diproses'] ?>)
      </a>
      <a href="?p=requests&status=Ditolak" class="filter-badge <?= $status_filter === 'Ditolak' ? 'active' : '' ?>">
        Ditolak (<?= $status_counts['Ditolak'] ?>)
      </a>
      <a href="?p=requests&status=Selesai" class="filter-badge <?= $status_filter === 'Selesai' ? 'active' : '' ?>">
        Selesai (<?= $status_counts['Selesai'] ?>)
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
          <input type="text" name="search" placeholder="Cari NIK, atau Nama..." value="<?= h($search) ?>" class="search-input">
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
            <th>No. Surat</th>
            <th>Tanggal</th>
            <th>NIK</th>
            <th>Nama Pemohon</th>
            <th>Jenis Dokumen</th>
            <th>Alamat</th>
            <th>Status</th>
            <th>Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($requests)): ?>
          <tr>
            <td colspan="9" class="text-center">Tidak ada pengajuan dokumen.</td>
          </tr>
          <?php else: ?>
            <?php foreach ($requests as $i => $req): ?>
            <tr>
              <td><?= $offset + $i + 1 ?></td>
              <td><strong><?= h($req['nomor_pengajuan'] ?? '-') ?></strong></td>
              <td><?= date('d/m/Y', strtotime($req['created_at'])) ?></td>
              <td><?= h($req['nik']) ?></td>
              <td><?= h($req['nama']) ?></td>
              <td><?= h($req['jenis_dokumen']) ?></td>
              <td><?= h($req['alamat']) ?></td>
              <td>
                <?php 
                $status = $req['status'] ?? 'Diajukan';
                $badge_class = match($status) {
                  'Selesai' => 'badge-success',
                  'Diproses' => 'badge-warning',
                  'Ditolak' => 'badge-danger',
                  'Diajukan' => 'badge-secondary',
                  default => 'badge-secondary'
                };
                ?>
                <span class="badge <?= $badge_class ?>"><?= h($status) ?></span>
              </td>
              <td>
                <a href="?p=request_detail&id=<?= h($req['id']) ?>" class="btn btn-sm btn-primary">Detail</a>
                <?php if (!empty($req['file_url'])): ?>
                  <a href="<?= h($req['file_url']) ?>" target="_blank" class="btn btn-sm btn-secondary">Download</a>
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
