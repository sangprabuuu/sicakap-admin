<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
if (!is_logged_in()) exit('Unauthorized');

$pdo = db();

// Get parameters
$format = $_GET['format'] ?? 'excel';
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
        if (!$date_from) $date_from = date('Y-m-01');
        if (!$date_to) $date_to = date('Y-m-t');
        break;
}

$where_request = "WHERE DATE(lr.created_at) BETWEEN :date_from AND :date_to";
$params = [':date_from' => $date_from, ':date_to' => $date_to];

// Get data for export
$sql = "SELECT 
            lr.no_request,
            lr.resident_nik,
            lr.resident_name,
            lt.name as template_name,
            lr.status,
            DATE_FORMAT(lr.created_at, '%d/%m/%Y %H:%i') as tanggal_request,
            DATE_FORMAT(il.issued_at, '%d/%m/%Y %H:%i') as tanggal_selesai,
            il.letter_no
        FROM letter_requests lr
        LEFT JOIN letter_templates lt ON lr.template_id = lt.id
        LEFT JOIN issued_letters il ON lr.id = il.request_id
        $where_request
        ORDER BY lr.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

// Get statistics
$stats_sql = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'verifikasi' THEN 1 ELSE 0 END) as verifikasi,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
    SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as processing,
    SUM(CASE WHEN status = 'finished' THEN 1 ELSE 0 END) as finished,
    SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
FROM letter_requests lr $where_request";
$stmt = $pdo->prepare($stats_sql);
$stmt->execute($params);
$stats = $stmt->fetch();

if ($format === 'excel') {
    exportExcel($data, $stats, $date_from, $date_to);
} else {
    exportPDF($data, $stats, $date_from, $date_to);
}

/**
 * Export to Excel (CSV format)
 */
function exportExcel($data, $stats, $date_from, $date_to) {
    $filename = 'laporan_surat_' . date('Ymd') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Title
    fputcsv($output, ['LAPORAN ADMINISTRASI SURAT']);
    fputcsv($output, ['Periode: ' . date('d/m/Y', strtotime($date_from)) . ' s/d ' . date('d/m/Y', strtotime($date_to))]);
    fputcsv($output, []);
    
    // Statistics
    fputcsv($output, ['RINGKASAN']);
    fputcsv($output, ['Total Permintaan', $stats['total']]);
    fputcsv($output, ['Pending', $stats['pending']]);
    fputcsv($output, ['Verifikasi', $stats['verifikasi']]);
    fputcsv($output, ['Disetujui', $stats['approved']]);
    fputcsv($output, ['Diproses', $stats['processing']]);
    fputcsv($output, ['Selesai', $stats['finished']]);
    fputcsv($output, ['Ditolak', $stats['rejected']]);
    fputcsv($output, []);
    
    // Data header
    fputcsv($output, [
        'No. Request',
        'NIK',
        'Nama Pemohon',
        'Jenis Surat',
        'Status',
        'Tanggal Request',
        'Tanggal Selesai',
        'No. Surat'
    ]);
    
    // Data rows
    foreach ($data as $row) {
        fputcsv($output, [
            $row['no_request'] ?? '-',
            $row['resident_nik'],
            $row['resident_name'],
            $row['template_name'],
            ucfirst($row['status']),
            $row['tanggal_request'],
            $row['tanggal_selesai'] ?? '-',
            $row['letter_no'] ?? '-'
        ]);
    }
    
    fclose($output);
    exit;
}

/**
 * Export to PDF (simple HTML-based)
 */
function exportPDF($data, $stats, $date_from, $date_to) {
    $filename = 'laporan_surat_' . date('Ymd') . '.pdf';
    
    // For proper PDF, you should use TCPDF or similar library
    // This is a simple HTML version
    
    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    // For now, we'll create an HTML that can be printed to PDF
    // In production, use a proper PDF library
    
    ob_start();
    ?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 12px; }
        h2 { text-align: center; }
        .stats { margin: 20px 0; }
        .stats div { padding: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f2f2f2; }
    </style>
</head>
<body>
    <h2>LAPORAN ADMINISTRASI SURAT</h2>
    <p style="text-align: center;">
        Periode: <?= date('d/m/Y', strtotime($date_from)) ?> s/d <?= date('d/m/Y', strtotime($date_to)) ?>
    </p>
    
    <div class="stats">
        <h3>RINGKASAN</h3>
        <div>Total Permintaan: <strong><?= $stats['total'] ?></strong></div>
        <div>Pending: <?= $stats['pending'] ?></div>
        <div>Verifikasi: <?= $stats['verifikasi'] ?></div>
        <div>Disetujui: <?= $stats['approved'] ?></div>
        <div>Diproses: <?= $stats['processing'] ?></div>
        <div>Selesai: <?= $stats['finished'] ?></div>
        <div>Ditolak: <?= $stats['rejected'] ?></div>
    </div>
    
    <h3>DETAIL PERMINTAAN</h3>
    <table>
        <thead>
            <tr>
                <th>No. Request</th>
                <th>NIK</th>
                <th>Nama</th>
                <th>Jenis Surat</th>
                <th>Status</th>
                <th>Tanggal Request</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($data as $row): ?>
            <tr>
                <td><?= htmlspecialchars($row['no_request'] ?? '-') ?></td>
                <td><?= htmlspecialchars($row['resident_nik']) ?></td>
                <td><?= htmlspecialchars($row['resident_name']) ?></td>
                <td><?= htmlspecialchars($row['template_name']) ?></td>
                <td><?= ucfirst($row['status']) ?></td>
                <td><?= htmlspecialchars($row['tanggal_request']) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    
    <p style="margin-top: 30px; text-align: right;">
        Dicetak pada: <?= date('d/m/Y H:i') ?>
    </p>
</body>
</html>
    <?php
    $html = ob_get_clean();
    
    // Output as HTML (user can print to PDF from browser)
    // For proper PDF, install library: composer require tecnickcom/tcpdf
    echo $html;
    exit;
}
