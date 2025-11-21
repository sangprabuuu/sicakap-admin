<?php
$user = current_user();

// Get filter parameters
$period = $_GET['period'] ?? 'month';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

// Set date range based on period
$today = date('Y-m-d');
switch ($period) {
    case 'today':
        $date_from = $date_to = $today;
        break;
    case 'week':
        $date_from = date('Y-m-d', strtotime('-7 days'));
        $date_to = $today;
        break;
    case 'month':
        $date_from = date('Y-m-01');
        $date_to = date('Y-m-t');
        break;
    case 'year':
        $date_from = date('Y-01-01');
        $date_to = date('Y-12-31');
        break;
    case 'custom':
        // Use provided dates or default to current month
        if (!$date_from) $date_from = date('Y-m-01');
        if (!$date_to) $date_to = date('Y-m-t');
        break;
}

// Get data from Supabase
$filter = "tanggal=gte.$date_from&tanggal=lte.$date_to";
$result = supabase_request('GET', "pelaporan_masalah?$filter&select=*&order=created_at.desc");
$laporans = $result['data'] ?? [];

// Get SPPD data
$sppd_result = supabase_request('GET', "pengajuan_sppd?tanggal_pembuatan=gte.$date_from&tanggal_pembuatan=lte.$date_to&select=*");
$sppds = $sppd_result['data'] ?? [];

// Get Undangan data
$undangan_result = supabase_request('GET', "surat_undangan?tanggal_surat=gte.$date_from&tanggal_surat=lte.$date_to&select=*");
$undangans = $undangan_result['data'] ?? [];

// Calculate statistics
$stats = [];
$stats['total_laporan'] = count($laporans);
$stats['total_sppd'] = count($sppds);
$stats['total_undangan'] = count($undangans);
$stats['total_semua'] = $stats['total_laporan'] + $stats['total_sppd'] + $stats['total_undangan'];

// Group by category
$by_category = [];
foreach ($laporans as $lap) {
    $cat = $lap['kategori_permasalahan'] ?? 'Lainnya';
    $by_category[$cat] = ($by_category[$cat] ?? 0) + 1;
}
arsort($by_category);

// Daily trend for laporan
$daily_trend = [];
foreach ($laporans as $lap) {
    $date = substr($lap['tanggal'] ?? '', 0, 10);
    $daily_trend[$date] = ($daily_trend[$date] ?? 0) + 1;
}
ksort($daily_trend);

$flash = flash_get();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Laporan - SiCakap</title>
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
      <h1>Recap & Pelaporan Masalah</h1>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-success"><?= h($flash) ?></div>
    <?php endif; ?>

    <!-- Filter Periode -->
    <div class="report-filter">
      <form method="get" class="filter-form">
        <input type="hidden" name="p" value="reports">
        
        <div class="filter-group">
          <label>Periode:</label>
          <select name="period" id="period" onchange="toggleCustomDate()">
            <option value="today" <?= $period == 'today' ? 'selected' : '' ?>>Hari Ini</option>
            <option value="week" <?= $period == 'week' ? 'selected' : '' ?>>7 Hari Terakhir</option>
            <option value="month" <?= $period == 'month' ? 'selected' : '' ?>>Bulan Ini</option>
            <option value="year" <?= $period == 'year' ? 'selected' : '' ?>>Tahun Ini</option>
            <option value="custom" <?= $period == 'custom' ? 'selected' : '' ?>>Custom</option>
          </select>
        </div>

        <div class="filter-group" id="custom-dates" style="<?= $period != 'custom' ? 'display:none' : '' ?>">
          <label>Dari:</label>
          <input type="date" name="date_from" value="<?= h($date_from) ?>">
          <label>Sampai:</label>
          <input type="date" name="date_to" value="<?= h($date_to) ?>">
        </div>

        <button type="submit" class="btn btn-primary">Tampilkan</button>
      </form>
    </div>

    <div class="report-period-info">
      Periode: <strong><?= date('d/m/Y', strtotime($date_from)) ?></strong> s/d <strong><?= date('d/m/Y', strtotime($date_to)) ?></strong>
    </div>

      <!-- Detail Pelaporan Masalah -->
    <div class="report-section">
      <h3>📋 Detail Pelaporan Masalah</h3>
      <div class="table-responsive">
        <table class="data-table compact">
          <thead>
            <tr>
              <th>No</th>
              <th>Nomor Laporan</th>
              <th>Tanggal</th>
              <th>Kategori</th>
              <th>Deskripsi</th>
              <th>File</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($laporans)): ?>
            <tr><td colspan="7" class="text-center">Tidak ada data</td></tr>
            <?php else: ?>
              <?php foreach ($laporans as $i => $lap): ?>
              <?php
                $kategori = strtoupper(trim($lap['kategori_permasalahan'] ?? 'Lainnya'));
                $badge_class = 'badge-gray';
                
                if (strpos($kategori, 'DOKUMEN') !== false) {
                    $badge_class = 'badge-blue';
                } elseif (strpos($kategori, 'TEKNIS') !== false) {
                    $badge_class = 'badge-orange';
                } elseif (strpos($kategori, 'ADMINISTRASI') !== false || strpos($kategori, 'LAYANAN') !== false) {
                    $badge_class = 'badge-purple';
                } elseif (strpos($kategori, 'PENGADUAN') !== false) {
                    $badge_class = 'badge-red';
                } elseif (strpos($kategori, 'INFORMASI') !== false) {
                    $badge_class = 'badge-green';
                }
              ?>
              <tr>
                <td><?= $i + 1 ?></td>
                <td><?= h($lap['nomor_laporan']) ?></td>
                <td><?= date('d/m/Y', strtotime($lap['tanggal'])) ?></td>
                <td><span class="badge <?= $badge_class ?>"><?= h($lap['kategori_permasalahan'] ?? 'Lainnya') ?></span></td>
                <td style="max-width: 300px;"><?= h(substr($lap['description'] ?? '-', 0, 80)) ?><?= strlen($lap['description'] ?? '') > 80 ? '...' : '' ?></td>
                <td>
                  <?php if (!empty($lap['file_url'])): ?>
                  <a href="<?= h($lap['file_url']) ?>" target="_blank" class="btn-icon-action btn-icon-view" title="Lihat File">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                      <path d="M5 4a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1H5zm-.5 2.5A.5.5 0 0 1 5 6h6a.5.5 0 0 1 0 1H5a.5.5 0 0 1-.5-.5zM5 8a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1H5zm0 2a.5.5 0 0 0 0 1h3a.5.5 0 0 0 0-1H5z"/>
                      <path d="M2 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V2zm10-1H4a1 1 0 0 0-1 1v12a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1z"/>
                    </svg>
                  </a>
                  <?php else: ?>
                  <span style="color:#999;">-</span>
                  <?php endif; ?>
                </td>
                <td>
                  <button onclick="confirmDelete('<?= h($lap['id']) ?>', '<?= h($lap['nomor_laporan']) ?>')" class="btn-icon-action" title="Hapus">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor">
                      <path d="M5.5 5.5A.5.5 0 0 1 6 6v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm2.5 0a.5.5 0 0 1 .5.5v6a.5.5 0 0 1-1 0V6a.5.5 0 0 1 .5-.5zm3 .5a.5.5 0 0 0-1 0v6a.5.5 0 0 0 1 0V6z"/>
                      <path fill-rule="evenodd" d="M14.5 3a1 1 0 0 1-1 1H13v9a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V4h-.5a1 1 0 0 1-1-1V2a1 1 0 0 1 1-1H6a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1h3.5a1 1 0 0 1 1 1v1zM4.118 4 4 4.059V13a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1V4.059L11.882 4H4.118zM2.5 3V2h11v1h-11z"/>
                    </svg>
                  </button>
                </td>
              </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Statistik Cards -->
    <div class="cards">
    <div class="card report-card">
      <div class="card-title">📝 Pelaporan Masalah</div>
      <div class="card-value"><?= $stats['total_laporan'] ?></div>
      <div class="card-desc">Laporan masuk</div>
    </div>
    
      <div class="card report-card">
        <div class="card-title">📋 Total Semua</div>
        <div class="card-value"><?= $stats['total_semua'] ?></div>
        <div class="card-desc">Laporan, SPPD & Undangan</div>
      </div>

      <div class="card report-card">
        <div class="card-title">✈️ SPPD</div>
        <div class="card-value"><?= $stats['total_sppd'] ?></div>
        <div class="card-desc">Surat Perintah/Tugas</div>
      </div>

      <div class="card report-card">
        <div class="card-title">📨 Undangan</div>
        <div class="card-value"><?= $stats['total_undangan'] ?></div>
        <div class="card-desc">Surat Undangan</div>
      </div>
    </div>

    <!-- Laporan per Kategori & List -->
    <div class="report-row">
      <div class="report-section report-half">
        <h3>📊 Pelaporan per Kategori</h3>
        <div class="table-responsive">
          <table class="data-table compact">
            <thead>
              <tr>
                <th>No</th>
                <th>Kategori</th>
                <th>Jumlah</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($by_category)): ?>
              <tr><td colspan="3" class="text-center">Tidak ada data</td></tr>
              <?php else: ?>
                <?php $no = 1; foreach ($by_category as $cat => $count): ?>
                <tr>
                  <td><?= $no++ ?></td>
                  <td><?= h($cat) ?></td>
                  <td><strong><?= $count ?></strong></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- <div class="report-section report-half">
        <h3>📄 SPPD & Undangan</h3>
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
          <div style="text-align: center; padding: 20px; background: #f0f8ff; border-radius: 8px;">
            <div style="font-size: 36px; color: #4a7c2c; font-weight: bold;"><?= $stats['total_sppd'] ?></div>
            <div style="color: #666; margin-top: 8px;">Surat SPPD</div>
          </div>
          <div style="text-align: center; padding: 20px; background: #fff0f0; border-radius: 8px;">
            <div style="font-size: 36px; color: #4a7c2c; font-weight: bold;"><?= $stats['total_undangan'] ?></div>
            <div style="color: #666; margin-top: 8px;">Surat Undangan</div>
          </div>
        </div>
      </div> -->
    </div>

  </section>
</div>

<script>
function toggleCustomDate() {
  const period = document.getElementById('period').value;
  const customDates = document.getElementById('custom-dates');
  customDates.style.display = period === 'custom' ? 'flex' : 'none';
}

function confirmDelete(id, nomor) {
  if (confirm(`Apakah Anda yakin ingin menghapus laporan ${nomor}?\n\nData yang dihapus tidak dapat dikembalikan.`)) {
    window.location.href = `?p=reports_delete&id=${id}&period=<?= h($period) ?>&date_from=<?= h($date_from) ?>&date_to=<?= h($date_to) ?>`;
  }
}
</script>

<style>
.badge {
  display: inline-block;
  padding: 4px 10px;
  border-radius: 12px;
  font-size: 11px;
  font-weight: 600;
}
.badge-blue {
  background: #e3f2fd;
  color: #1976d2;
}
.badge-orange {
  background: #fff3e0;
  color: #f57c00;
}
.badge-purple {
  background: #f3e5f5;
  color: #7b1fa2;
}
.badge-red {
  background: #ffebee;
  color: #c62828;
}
.badge-green {
  background: #e8f5e9;
  color: #2e7d32;
}
.badge-gray {
  background: #f5f5f5;
  color: #616161;
}
.btn-icon-action {
  background: #e63939;
  color: white;
  border: none;
  cursor: pointer;
  padding: 8px 12px;
  font-size: 18px;
  border-radius: 6px;
  transition: all 0.2s;
  display: inline-flex;
  align-items: center;
  justify-content: center;
}
.btn-icon-action:hover {
  background: #d32f2f;
  transform: scale(1.1);
}
.btn-icon-view {
  background: #64B5F6;
  text-decoration: none;
}
.btn-icon-view:hover {
  background: #42A5F5;
}
</style>
</body>
</html>
