<?php
// Session sudah di-start di index.php, tidak perlu start lagi
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../functions.php';
if (!is_logged_in()) exit('Unauthorized');

// Get parameters
$pengajuan_id = $_POST['pengajuan_id'] ?? $_GET['pengajuan_id'] ?? '';
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if (empty($pengajuan_id)) {
    flash_set('ID pengajuan tidak valid');
    header('Location: ' . APP_URL . '/?p=requests');
    exit;
}

// Validate action
$valid_actions = ['proses', 'selesai', 'tolak', 'update_nomor', 'kirim_komentar'];
if (!in_array($action, $valid_actions)) {
    flash_set('Aksi tidak valid');
    header('Location: ' . APP_URL . '/?p=requests');
    exit;
}

// Handle update_nomor action - Update nomor pengajuan
if ($action === 'update_nomor') {
    $nomor_pengajuan = trim($_POST['nomor_pengajuan'] ?? '');
    
    if (empty($nomor_pengajuan)) {
        flash_set('No. Pengajuan tidak boleh kosong');
        header('Location: ' . APP_URL . '/?p=request_detail&id=' . $pengajuan_id);
        exit;
    }
    
    // Update nomor pengajuan di Supabase
    $update_data = json_encode(['nomor_pengajuan' => $nomor_pengajuan]);
    $result = supabase_request('PATCH', "pengajuan_dokumen?id=eq.$pengajuan_id", $update_data);
    
    if ($result['code'] === 200 || $result['code'] === 204) {
        flash_set('No. Pengajuan berhasil diperbarui');
    } else {
        flash_set('Gagal memperbarui No. Pengajuan');
    }
    
    header('Location: ' . APP_URL . '/?p=request_detail&id=' . $pengajuan_id);
    exit;
}

// Handle kirim_komentar action - Add comment to existing "Ditolak" status
if ($action === 'kirim_komentar') {
    $komentar = trim($_POST['komentar'] ?? '');
    
    if (empty($komentar)) {
        flash_set('Komentar tidak boleh kosong');
        header('Location: ' . APP_URL . '/?p=request_detail&id=' . $pengajuan_id);
        exit;
    }
    
    // Ambil data pengajuan untuk notifikasi
    $pengajuan_endpoint = "pengajuan_dokumen?id=eq.$pengajuan_id&select=firebase_user_id,jenis_dokumen,created_at,nama";
    $pengajuan_result = supabase_request('GET', $pengajuan_endpoint);
    
    if ($pengajuan_result['code'] !== 200 || empty($pengajuan_result['data'])) {
        flash_set('Pengajuan tidak ditemukan');
        header('Location: ' . APP_URL . '/?p=requests');
        exit;
    }
    
    $pengajuan = $pengajuan_result['data'][0];
    $firebase_user_id = $pengajuan['firebase_user_id'] ?? '';
    $jenis_dokumen = $pengajuan['jenis_dokumen'] ?? '';
    $tanggal_pengajuan = $pengajuan['created_at'] ?? '';
    $nama_pemohon = $pengajuan['nama'] ?? '';
    
    // Insert riwayat baru dengan komentar
    $riwayat_data = json_encode([
        'pengajuan_id' => $pengajuan_id,
        'status' => 'Ditolak',
        'keterangan' => $komentar,
        'firebase_user_id' => $firebase_user_id,
        'jenis_pengajuan' => $jenis_dokumen,
        'tanggal_pengajuan' => $tanggal_pengajuan
    ]);
    
    $result = supabase_request('POST', 'riwayat', $riwayat_data);
    
    if ($result['code'] === 201 || $result['code'] === 200) {
        // Kirim notifikasi ke user (jika ada firebase_user_id)
        if (!empty($firebase_user_id)) {
            $notif_data = json_encode([
                'firebase_user_id' => $firebase_user_id,
                'title' => 'Komentar Baru pada Pengajuan',
                'message' => "Komentar baru ditambahkan pada pengajuan {$jenis_dokumen} Anda: {$komentar}",
                'type' => 'komentar',
                'pengajuan_id' => $pengajuan_id,
                'is_read' => false
            ]);
            
            supabase_request('POST', 'notifikasi', $notif_data);
        }
        
        flash_set('Komentar berhasil dikirim dan notifikasi terkirim ke pemohon');
    } else {
        // Debug error
        $error_detail = '';
        if (isset($result['data']['message'])) {
            $error_detail = $result['data']['message'];
        } elseif (isset($result['data']['error'])) {
            $error_detail = $result['data']['error'];
        }
        flash_set('Gagal mengirim komentar: ' . $error_detail);
    }
    
    header('Location: ' . APP_URL . '/?p=request_detail&id=' . $pengajuan_id);
    exit;
}

// Handle status changes - Insert into riwayat table
$status_map = [
    'proses' => 'Diproses',
    'selesai' => 'Selesai',
    'tolak' => 'Ditolak'
];

$new_status = $status_map[$action] ?? '';

if (empty($new_status)) {
    flash_set('Status tidak valid');
    header('Location: ' . APP_URL . '/?p=requests');
    exit;
}

// Ambil firebase_user_id dari pengajuan_dokumen
$pengajuan_endpoint = "pengajuan_dokumen?id=eq.$pengajuan_id&select=firebase_user_id,jenis_dokumen,created_at";
$pengajuan_result = supabase_request('GET', $pengajuan_endpoint);

if ($pengajuan_result['code'] !== 200 || empty($pengajuan_result['data'])) {
    flash_set('Pengajuan tidak ditemukan');
    header('Location: ' . APP_URL . '/?p=requests');
    exit;
}

$pengajuan = $pengajuan_result['data'][0];
$firebase_user_id = $pengajuan['firebase_user_id'] ?? '';
$jenis_dokumen = $pengajuan['jenis_dokumen'] ?? '';
$tanggal_pengajuan = $pengajuan['created_at'] ?? '';

if (empty($firebase_user_id)) {
    flash_set('Firebase User ID tidak ditemukan');
    header('Location: ' . APP_URL . '/?p=requests');
    exit;
}

// Insert riwayat baru dengan semua field yang diperlukan
$riwayat_data = json_encode([
    'pengajuan_id' => $pengajuan_id,
    'status' => $new_status,
    'firebase_user_id' => $firebase_user_id,
    'jenis_pengajuan' => $jenis_dokumen,
    'tanggal_pengajuan' => $tanggal_pengajuan
]);

$result = supabase_request('POST', 'riwayat', $riwayat_data);

// Selalu tampilkan debug info jika ada error
if ($result['code'] !== 201 && $result['code'] !== 200) {
    echo '<pre style="background:#ffe6e6;padding:20px;margin:20px;border:2px solid #ff0000;">';
    echo "<strong>ERROR: Gagal menyimpan riwayat</strong>\n\n";
    echo "Action: $action\n";
    echo "Pengajuan ID: $pengajuan_id\n";
    echo "New Status: $new_status\n";
    echo "Data sent: $riwayat_data\n\n";
    echo "Result Code: " . $result['code'] . "\n";
    echo "Result Data:\n";
    print_r($result['data']);
    echo '</pre>';
    echo '<a href="' . APP_URL . '/?p=requests">Kembali ke Daftar Pengajuan</a>';
    exit;
}

if ($result['code'] === 201 || $result['code'] === 200) {
    $message_map = [
        'proses' => 'Pengajuan berhasil diproses',
        'selesai' => 'Pengajuan berhasil diselesaikan',
        'tolak' => 'Pengajuan berhasil ditolak'
    ];
    flash_set($message_map[$action] ?? 'Status berhasil diperbarui');
} else {
    $error_msg = 'Gagal memperbarui status pengajuan';
    if (!empty($result['data']['message'])) {
        $error_msg .= ': ' . $result['data']['message'];
    }
    flash_set($error_msg);
}

header('Location: ' . APP_URL . '/?p=requests');
exit;
