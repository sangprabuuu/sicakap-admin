<?php
if (!is_logged_in()) {
    header('Location: ' . APP_URL . '/?p=login');
    exit;
}

$id = $_GET['id'] ?? '';
$period = $_GET['period'] ?? 'month';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';

if (!$id) {
    flash_set('ID laporan tidak ditemukan');
    header('Location: ' . APP_URL . '/?p=reports');
    exit;
}

// Delete from Supabase
$result = supabase_request('DELETE', "pelaporan_masalah?id=eq.$id");

if ($result['code'] === 204 || $result['code'] === 200) {
    flash_set('Laporan berhasil dihapus');
} else {
    flash_set('Error: Gagal menghapus laporan - ' . ($result['message'] ?? 'Unknown error'));
}

// Redirect back with filter parameters
$redirect = '?p=reports&period=' . urlencode($period);
if ($date_from) $redirect .= '&date_from=' . urlencode($date_from);
if ($date_to) $redirect .= '&date_to=' . urlencode($date_to);

header('Location: ' . APP_URL . '/' . $redirect);
exit;
