<?php
$pdo = db();
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

// Build where clause for date filter
$where_request = "WHERE DATE(lr.created_at) BETWEEN :date_from AND :date_to";
$where_issued = "WHERE DATE(il.issued_at) BETWEEN :date_from AND :date_to";
$params = [':date_from' => $date_from, ':date_to' => $date_to];

// 1. STATISTIK UMUM
$stats = [];

// Total requests in period
$sql = "SELECT COUNT(*) FROM letter_requests lr $where_request";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$stats['total_requests'] = $stmt->fetchColumn();

// Requests by status
$sql = "SELECT status, COUNT(*) as count FROM letter_requests lr $where_request GROUP BY status";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$stats['by_status'] = [];
while ($row = $stmt->fetch()) {
    $stats['by_status'][$row['status']] = $row['count'];
}

// Total issued letters in period
$sql = "SELECT COUNT(*) FROM issued_letters il $where_issued";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$stats['total_issued'] = $stmt->fetchColumn();

// Average processing time (from created to finished)
$sql = "SELECT AVG(TIMESTAMPDIFF(HOUR, lr.created_at, il.issued_at)) as avg_hours
        FROM letter_requests lr
        JOIN issued_letters il ON lr.id = il.request_id
        $where_issued";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$avg = $stmt->fetchColumn();
$stats['avg_processing_hours'] = $avg ? round($avg, 1) : 0;
$stats['avg_processing_days'] = $avg ? round($avg / 24, 1) : 0;

// 2. LAPORAN PER JENIS SURAT
$sql = "SELECT lt.name, lt.code, COUNT(*) as total
        FROM letter_requests lr
        JOIN letter_templates lt ON lr.template_id = lt.id
        $where_request
        GROUP BY lt.id, lt.name, lt.code
        ORDER BY total DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$by_template = $stmt->fetchAll();

// 3. LAPORAN ISSUED PER JENIS SURAT
$sql = "SELECT lt.name, lt.code, COUNT(*) as total
        FROM issued_letters il
        JOIN letter_templates lt ON il.template_id = lt.id
        $where_issued
        GROUP BY lt.id, lt.name, lt.code
        ORDER BY total DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$issued_by_template = $stmt->fetchAll();

// 4. TREND HARIAN (untuk chart)
$sql = "SELECT DATE(lr.created_at) as date, COUNT(*) as count
        FROM letter_requests lr
        $where_request
        GROUP BY DATE(lr.created_at)
        ORDER BY date ASC";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$daily_trend = $stmt->fetchAll();

// 5. TOP PEMOHON
$sql = "SELECT lr.resident_name, lr.resident_nik, COUNT(*) as total
        FROM letter_requests lr
        $where_request
        GROUP BY lr.resident_nik, lr.resident_name
        HAVING total > 1
        ORDER BY total DESC
        LIMIT 10";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$top_requesters = $stmt->fetchAll();

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
      <h1>📊 Laporan Administrasi Surat</h1>
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
        <a href="?p=export_report&period=<?= h($period) ?>&date_from=<?= h($date_from) ?>&date_to=<?= h($date_to) ?>&format=excel" class="btn btn-success">📥 Export Excel</a>
        <a href="?p=export_report&period=<?= h($period) ?>&date_from=<?= h($date_from) ?>&date_to=<?= h($date_to) ?>&format=pdf" class="btn btn-danger">📄 Export PDF</a>
      </form>
    </div>

    <div class="report-period-info">
      Periode: <strong><?= date('d/m/Y', strtotime($date_from)) ?></strong> s/d <strong><?= date('d/m/Y', strtotime($date_to)) ?></strong>
    </div>

    <!-- Statistik Cards -->
    <div class="cards">
      <div class="card report-card">
        <div class="card-title">📨 Total Permintaan</div>
        <div class="card-value"><?= intval($stats['total_requests']) ?></div>
        <div class="card-desc">Permintaan masuk dalam periode</div>
      </div>

      <div class="card report-card">
        <div class="card-title">⏳ Pending</div>
        <div class="card-value"><?= intval($stats['by_status']['pending'] ?? 0) ?></div>
        <div class="card-desc">Menunggu diproses</div>
      </div>

      <div class="card report-card">
        <div class="card-title">✅ Selesai</div>
        <div class="card-value"><?= intval($stats['total_issued']) ?></div>
        <div class="card-desc">Surat telah diterbitkan</div>
      </div>

      <div class="card report-card">
        <div class="card-title">⏱️ Rata-rata Proses</div>
        <div class="card-value"><?= $stats['avg_processing_days'] ?> hari</div>
        <div class="card-desc"><?= $stats['avg_processing_hours'] ?> jam</div>
      </div>
    </div>

    <!-- Status Breakdown -->
    <div class="report-section">
      <h3>📋 Rincian Status Permintaan</h3>
      <div class="status-breakdown">
        <?php
        $status_labels = [
            'pending' => ['Pending', '#fbbf24'],
            'verifikasi' => ['Verifikasi', '#60a5fa'],
            'approved' => ['Disetujui', '#34d399'],
            'processing' => ['Diproses', '#a78bfa'],
            'finished' => ['Selesai', '#10b981'],
            'rejected' => ['Ditolak', '#f87171']
        ];
        foreach ($status_labels as $status => $info):
            $count = $stats['by_status'][$status] ?? 0;
            $percentage = $stats['total_requests'] > 0 ? round(($count / $stats['total_requests']) * 100, 1) : 0;
        ?>
        <div class="status-item">
          <div class="status-bar-container">
            <div class="status-label"><?= h($info[0]) ?></div>
            <div class="status-bar">
              <div class="status-bar-fill" style="width: <?= $percentage ?>%; background: <?= $info[1] ?>"></div>
            </div>
            <div class="status-count"><?= $count ?> (<?= $percentage ?>%)</div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>

    <!-- Laporan per Jenis Surat -->
    <div class="report-row">
      <div class="report-section report-half">
        <h3>📝 Permintaan per Jenis Surat</h3>
        <div class="table-responsive">
          <table class="data-table compact">
            <thead>
              <tr>
                <th>No</th>
                <th>Jenis Surat</th>
                <th>Kode</th>
                <th>Jumlah</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($by_template)): ?>
              <tr><td colspan="4" class="text-center">Tidak ada data</td></tr>
              <?php else: ?>
                <?php foreach ($by_template as $i => $t): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= h($t['name']) ?></td>
                  <td><?= h($t['code']) ?></td>
                  <td><strong><?= $t['total'] ?></strong></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="report-section report-half">
        <h3>✅ Surat Diterbitkan per Jenis</h3>
        <div class="table-responsive">
          <table class="data-table compact">
            <thead>
              <tr>
                <th>No</th>
                <th>Jenis Surat</th>
                <th>Kode</th>
                <th>Jumlah</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($issued_by_template)): ?>
              <tr><td colspan="4" class="text-center">Tidak ada data</td></tr>
              <?php else: ?>
                <?php foreach ($issued_by_template as $i => $t): ?>
                <tr>
                  <td><?= $i + 1 ?></td>
                  <td><?= h($t['name']) ?></td>
                  <td><?= h($t['code']) ?></td>
                  <td><strong><?= $t['total'] ?></strong></td>
                </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Trend Chart -->
    <?php if (!empty($daily_trend)): ?>
    <div class="report-section">
      <h3>📈 Trend Permintaan Harian</h3>
      <div class="chart-container">
        <?php
        $max_count = max(array_column($daily_trend, 'count'));
        foreach ($daily_trend as $day):
            $height = $max_count > 0 ? ($day['count'] / $max_count) * 100 : 0;
        ?>
        <div class="chart-bar-wrapper">
          <div class="chart-bar" style="height: <?= $height ?>%">
            <span class="bar-value"><?= $day['count'] ?></span>
          </div>
          <div class="chart-label"><?= date('d/m', strtotime($day['date'])) ?></div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endif; ?>

    <!-- Top Requesters -->
    <?php if (!empty($top_requesters)): ?>
    <div class="report-section">
      <h3>👥 Pemohon Terbanyak</h3>
      <div class="table-responsive">
        <table class="data-table compact">
          <thead>
            <tr>
              <th>No</th>
              <th>NIK</th>
              <th>Nama</th>
              <th>Jumlah Permintaan</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($top_requesters as $i => $r): ?>
            <tr>
              <td><?= $i + 1 ?></td>
              <td><?= h($r['resident_nik']) ?></td>
              <td><?= h($r['resident_name']) ?></td>
              <td><strong><?= $r['total'] ?></strong></td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
    <?php endif; ?>

  </section>
</div>

<script>
function toggleCustomDate() {
  const period = document.getElementById('period').value;
  const customDates = document.getElementById('custom-dates');
  customDates.style.display = period === 'custom' ? 'flex' : 'none';
}
</script>
</body>
</html>
