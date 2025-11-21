<?php
$user = current_user();

// Ambil data pengajuan yang sudah selesai dari Supabase
$endpoint = 'pengajuan_dokumen?select=*&order=created_at.desc';

// Search by nama atau NIK
$search = $_GET['search'] ?? '';
if ($search) {
    $endpoint .= "&or=(nama.ilike.*$search*,nik.ilike.*$search*,nomor_pengajuan.ilike.*$search*)";
}

$result = supabase_request('GET', $endpoint);
$all_requests = ($result['code'] === 200 && !empty($result['data'])) ? $result['data'] : [];

// Debug mode
if (isset($_GET['debug'])) {
    echo '<pre style="background:#f5f5f5;padding:20px;margin:20px;border:1px solid #ccc;">';
    echo "Total Pengajuan: " . count($all_requests) . "\n\n";
}

// Ambil semua status dari tabel riwayat
$status_map = [];
if (!empty($all_requests)) {
    $all_riwayat_endpoint = "riwayat?select=pengajuan_id,status,created_at&order=created_at.desc";
    $all_riwayat_result = supabase_request('GET', $all_riwayat_endpoint);
    
    if ($all_riwayat_result['code'] === 200 && !empty($all_riwayat_result['data'])) {
        foreach ($all_riwayat_result['data'] as $riwayat) {
            $pid = $riwayat['pengajuan_id'];
            if (!isset($status_map[$pid])) {
                $status_map[$pid] = $riwayat['status'];
            }
        }
        
        if (isset($_GET['debug'])) {
            echo "Total Riwayat: " . count($all_riwayat_result['data']) . "\n";
            echo "Status Map:\n";
            print_r($status_map);
        }
    }
    
    // Set status dan filter hanya yang selesai
    $requests = [];
    foreach ($all_requests as $req) {
        $req['status'] = $status_map[$req['id']] ?? 'Diajukan';
        if ($req['status'] === 'Selesai') {
            $requests[] = $req;
        }
    }
    
    if (isset($_GET['debug'])) {
        echo "\nTotal Selesai: " . count($requests) . "\n";
        echo '</pre>';
    }
} else {
    $requests = [];
}

// Hitung statistik
$total = count($requests);

// Filter hari ini
$today = date('Y-m-d');
$today_count = 0;
foreach ($requests as $req) {
    if (date('Y-m-d', strtotime($req['created_at'])) === $today) {
        $today_count++;
    }
}

// Filter bulan ini
$this_month = date('Y-m');
$month_count = 0;
foreach ($requests as $req) {
    if (date('Y-m', strtotime($req['created_at'])) === $this_month) {
        $month_count++;
    }
}

$stats = [
    'total' => $total,
    'today' => $today_count,
    'this_month' => $month_count,
];

// Pagination manual
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 20;
$total_pages = ceil($total / $limit);
$offset = ($page - 1) * $limit;
$letters = array_slice($requests, $offset, $limit);

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
    <h1>Dokumen Selesai</h1>

    <?php if ($flash): ?>
    <div class="alert alert-success"><?= h($flash) ?></div>
    <?php endif; ?>

    <!-- Statistics Cards -->
    <div class="cards">
      <div class="card">
        <div class="card-title">Total Surat</div>
        <div class="card-value" style="color: #000;"><?= intval($stats['total']) ?></div>
        <div class="card-desc">Surat yang telah diterbitkan</div>
      </div>

      <div class="card">
        <div class="card-title">Hari Ini</div>
        <div class="card-value" style="color: #000;"><?= intval($stats['today']) ?></div>
        <div class="card-desc">Surat diterbitkan hari ini</div>
      </div>

      <div class="card">
        <div class="card-title">Bulan Ini</div>
        <div class="card-value" style="color: #000;"><?= intval($stats['this_month']) ?></div>
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
            <th>NIK</th>
            <th>Nama Pemohon</th>
            <th>Jenis Dokumen</th>
            <th>Alamat</th>
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
              <td><strong><?= h($letter['nomor_pengajuan'] ?? '-') ?></strong></td>
              <td><?= date('d/m/Y', strtotime($letter['created_at'])) ?></td>
              <td><?= h($letter['nik']) ?></td>
              <td><?= h($letter['nama']) ?></td>
              <td><?= h($letter['jenis_dokumen']) ?></td>
              <td><?= h($letter['alamat']) ?></td>
              <td class="actions">
                <a href="?p=print_surat&id=<?= h($letter['id']) ?>" 
                   class="btn btn-sm btn-secondary" 
                   target="_blank" 
                   title="Download PDF">
                  <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M.5 9.9a.5.5 0 0 1 .5.5v2.5a1 1 0 0 0 1 1h12a1 1 0 0 0 1-1v-2.5a.5.5 0 0 1 1 0v2.5a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2v-2.5a.5.5 0 0 1 .5-.5z"/>
                    <path d="M7.646 11.854a.5.5 0 0 0 .708 0l3-3a.5.5 0 0 0-.708-.708L8.5 10.293V1.5a.5.5 0 0 0-1 0v8.793L5.354 8.146a.5.5 0 1 0-.708.708l3 3z"/>
                  </svg>
                </a>
                <a href="?p=request_detail&id=<?= h($letter['id']) ?>" 
                   class="btn btn-sm btn-primary" 
                   title="Lihat Detail">
                  <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/>
                    <path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8zM1.173 8a13.133 13.133 0 0 1 1.66-2.043C4.12 4.668 5.88 3.5 8 3.5c2.12 0 3.879 1.168 5.168 2.457A13.133 13.133 0 0 1 14.828 8c-.058.087-.122.183-.195.288-.335.48-.83 1.12-1.465 1.755C11.879 11.332 10.119 12.5 8 12.5c-2.12 0-3.879-1.168-5.168-2.457A13.134 13.134 0 0 1 1.172 8z"/>
                  </svg>
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
