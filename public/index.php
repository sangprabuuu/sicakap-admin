<?php
// Aktifkan tampilan error sementara untuk debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/functions.php';

$page = $_GET['p'] ?? 'dashboard';

// halaman publik
if ($page === 'login') {
    require __DIR__ . '/../app/pages/login.php';
    exit;
}
if ($page === 'logout') {
    logout();
    header('Location: ?p=login');
    exit;
}

// cek auth untuk halaman lain
if (!is_logged_in()) {
    header('Location: ?p=login');
    exit;
}

// routing halaman terproteksi
switch ($page) {
    case 'dashboard':
        require __DIR__ . '/../app/pages/dashboard.php';
        break;
    case 'residents':
        require __DIR__ . '/../app/pages/residents.php';
        break;
    case 'resident_form':
        require __DIR__ . '/../app/pages/resident_form.php';
        break;
    case 'requests':
        require __DIR__ . '/../app/pages/requests.php';
        break;
    case 'request_detail':
        require __DIR__ . '/../app/pages/request_detail.php';
        break;
    case 'issued':
        require __DIR__ . '/../app/pages/issued.php';
        break;
    case 'reports':
        require __DIR__ . '/../app/pages/reports.php';
        break;
    case 'export_report':
        require __DIR__ . '/../app/pages/export_report.php';
        break;
    case 'sppd':
        require __DIR__ . '/../app/pages/sppd.php';
        break;
    case 'sppd_form':
        require __DIR__ . '/../app/pages/sppd_form.php';
        break;
    case 'sppd_save':
        require __DIR__ . '/../app/pages/sppd_save.php';
        break;
    case 'sppd_delete':
        require __DIR__ . '/../app/pages/sppd_delete.php';
        break;
    case 'sppd_print':
        require __DIR__ . '/../app/pages/sppd_print.php';
        break;
    case 'undangan':
        require __DIR__ . '/../app/pages/undangan.php';
        break;
    case 'undangan_form':
        require __DIR__ . '/../app/pages/undangan_form.php';
        break;
    case 'undangan_save':
        require __DIR__ . '/../app/pages/undangan_save.php';
        break;
    case 'undangan_delete':
        require __DIR__ . '/../app/pages/undangan_delete.php';
        break;
    case 'undangan_print':
        require __DIR__ . '/../app/pages/undangan_print.php';
        break;
    case 'resident_save':
        require __DIR__ . '/../app/actions/resident_save.php';
        break;
    case 'resident_delete':
        require __DIR__ . '/../app/actions/resident_delete.php';
        break;
    case 'request_approve':
        require __DIR__ . '/../app/actions/request_approve.php';
        break;
    case 'generate_pdf':
        require __DIR__ . '/../app/actions/generate_pdf.php';
        break;
    default:
        echo "Halaman tidak ditemukan";
}