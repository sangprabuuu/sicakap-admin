<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../functions.php';
if (!is_logged_in()) exit('Unauthorized');

$pdo = db();

// Get parameters
$id = isset($_POST['id']) ? intval($_POST['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($id <= 0) {
    flash_set('ID permintaan tidak valid');
    header('Location: ../?p=requests');
    exit;
}

// Validate action
$valid_actions = ['approve', 'reject', 'process', 'finish', 'verifikasi', 'update_no_request'];
if (!in_array($action, $valid_actions)) {
    flash_set('Aksi tidak valid');
    header('Location: ../?p=requests');
    exit;
}

// Handle update_no_request action separately
if ($action === 'update_no_request') {
    $no_request = trim($_POST['no_request'] ?? '');
    
    if (empty($no_request)) {
        flash_set('No. Request tidak boleh kosong');
        header('Location: /sicakap-admin/public/?p=request_detail&id=' . $id);
        exit;
    }
    
    try {
        $stmt = $pdo->prepare("UPDATE letter_requests SET no_request = :no_request WHERE id = :id");
        $stmt->execute([
            ':no_request' => $no_request,
            ':id' => $id
        ]);
        
        flash_set('No. Request berhasil diperbarui');
        header('Location: /sicakap-admin/public/?p=request_detail&id=' . $id);
        exit;
    } catch (PDOException $e) {
        error_log('Error updating no_request: ' . $e->getMessage());
        flash_set('Terjadi kesalahan saat memperbarui No. Request');
        header('Location: /sicakap-admin/public/?p=request_detail&id=' . $id);
        exit;
    }
}

try {
    // Get current request
    $stmt = $pdo->prepare("SELECT * FROM letter_requests WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $request = $stmt->fetch();
    
    if (!$request) {
        flash_set('Permintaan tidak ditemukan');
        header('Location: ../?p=requests');
        exit;
    }

    // Determine new status based on action
    $new_status = '';
    $message = '';
    
    switch ($action) {
        case 'approve':
            if ($request['status'] !== 'pending' && $request['status'] !== 'verifikasi') {
                flash_set('Permintaan tidak dapat disetujui karena status saat ini: ' . $request['status']);
                header('Location: ../?p=request_detail&id=' . $id);
                exit;
            }
            $new_status = 'approved';
            $message = 'Permintaan berhasil disetujui';
            break;
            
        case 'reject':
            if ($request['status'] === 'finished' || $request['status'] === 'rejected') {
                flash_set('Permintaan tidak dapat ditolak karena status saat ini: ' . $request['status']);
                header('Location: ../?p=request_detail&id=' . $id);
                exit;
            }
            $new_status = 'rejected';
            $message = 'Permintaan berhasil ditolak';
            break;
            
        case 'verifikasi':
            if ($request['status'] !== 'pending') {
                flash_set('Hanya permintaan pending yang dapat diverifikasi');
                header('Location: ../?p=request_detail&id=' . $id);
                exit;
            }
            $new_status = 'verifikasi';
            $message = 'Permintaan berhasil dipindahkan ke verifikasi';
            break;
            
        case 'process':
            if ($request['status'] !== 'approved') {
                flash_set('Hanya permintaan yang disetujui yang dapat diproses');
                header('Location: ../?p=request_detail&id=' . $id);
                exit;
            }
            $new_status = 'processing';
            $message = 'Permintaan mulai diproses';
            break;
            
        case 'finish':
            if ($request['status'] !== 'processing') {
                flash_set('Hanya permintaan yang sedang diproses yang dapat diselesaikan');
                header('Location: ../?p=request_detail&id=' . $id);
                exit;
            }
            $new_status = 'finished';
            $message = 'Permintaan berhasil diselesaikan';
            break;
    }

    // Update status
    $stmt = $pdo->prepare("UPDATE letter_requests SET status = :status WHERE id = :id");
    $stmt->execute([
        ':status' => $new_status,
        ':id' => $id
    ]);
    
    flash_set($message);
    
    // Redirect based on source
    if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], 'request_detail') !== false) {
        header('Location: ../?p=request_detail&id=' . $id);
    } else {
        header('Location: ../?p=requests');
    }
    exit;
    
} catch (PDOException $e) {
    error_log('Error updating request status: ' . $e->getMessage());
    flash_set('Terjadi kesalahan saat mengubah status permintaan');
    header('Location: ../?p=requests');
    exit;
}
