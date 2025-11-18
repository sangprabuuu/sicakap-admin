<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../functions.php';
if (!is_logged_in()) exit('Unauthorized');

$pdo = db();
$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    flash_set('ID tidak valid');
    header('Location: ../?p=residents');
    exit;
}

try {
    // Cek apakah penduduk ada
    $stmt = $pdo->prepare("SELECT name FROM residents WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $resident = $stmt->fetch();
    
    if (!$resident) {
        flash_set('Data penduduk tidak ditemukan');
        header('Location: ../?p=residents');
        exit;
    }
    
    // Cek apakah penduduk ini memiliki permintaan surat
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM letter_requests WHERE resident_id = :id");
    $stmt->execute([':id' => $id]);
    $request_count = $stmt->fetchColumn();
    
    if ($request_count > 0) {
        flash_set('Data tidak dapat dihapus karena penduduk ini memiliki ' . $request_count . ' permintaan surat');
        header('Location: ../?p=residents');
        exit;
    }
    
    // Hapus data
    $stmt = $pdo->prepare("DELETE FROM residents WHERE id = :id");
    $stmt->execute([':id' => $id]);
    
    flash_set('Data penduduk "' . $resident['name'] . '" berhasil dihapus');
    header('Location: ../?p=residents');
    exit;
    
} catch (PDOException $e) {
    error_log('Error deleting resident: ' . $e->getMessage());
    flash_set('Terjadi kesalahan saat menghapus data');
    header('Location: ../?p=residents');
    exit;
}
