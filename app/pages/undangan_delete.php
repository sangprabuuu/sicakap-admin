<?php
if (!is_logged_in()) {
    header('Location: ' . APP_URL . '/?p=login');
    exit;
}

$id = $_GET['id'] ?? '';

if (!$id) {
    header('Location: ' . APP_URL . '/?p=undangan');
    exit;
}

try {
    $result = supabase_request('DELETE', "surat_undangan?id=eq.$id");
    
    if ($result['code'] === 204 || $result['code'] === 200) {
        flash_set('Data undangan berhasil dihapus');
    } else {
        throw new Exception('Gagal menghapus data');
    }
} catch (Exception $e) {
    flash_set('Error: ' . $e->getMessage());
}

header('Location: ' . APP_URL . '/?p=undangan');
exit;
