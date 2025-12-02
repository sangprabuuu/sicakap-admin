<?php
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/?p=sppd');
    exit;
}

$id = $_POST['id'] ?? '';
$tanggal_pembuatan = $_POST['tanggal_pembuatan'] ?? '';
$nomor_sppd = trim($_POST['nomor_sppd'] ?? '');
$nama_pegawai = trim($_POST['nama_pegawai'] ?? '');
$nip = trim($_POST['nip'] ?? '');
$jabatan = trim($_POST['jabatan'] ?? '');
$alamat_tempat_tinggal = trim($_POST['alamat_tempat_tinggal'] ?? '');
$maksud_perjalanan = trim($_POST['maksud_perjalanan'] ?? '');
$tempat_tujuan = trim($_POST['tempat_tujuan'] ?? '');
$jenis_durasi = $_POST['jenis_durasi'] ?? '';
$tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
$tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
$nama_kepaladesa = trim($_POST['nama_kepaladesa'] ?? '');

// Handle file upload TTD
$ttd_kepaladesa_url = '';
if (isset($_FILES['ttd_kepaladesa']) && $_FILES['ttd_kepaladesa']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/png', 'image/jpeg', 'image/jpg'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($_FILES['ttd_kepaladesa']['type'], $allowed_types)) {
        flash_set('File harus berupa gambar PNG atau JPG');
        header('Location: ' . APP_URL . '/' . ($id ? "?p=sppd_form&id=$id" : '?p=sppd_form'));
        exit;
    }
    
    if ($_FILES['ttd_kepaladesa']['size'] > $max_size) {
        flash_set('Ukuran file maksimal 2MB');
        header('Location: ' . APP_URL . '/' . ($id ? "?p=sppd_form&id=$id" : '?p=sppd_form'));
        exit;
    }
    
    $upload_dir = __DIR__ . '/../../public/uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_ext = pathinfo($_FILES['ttd_kepaladesa']['name'], PATHINFO_EXTENSION);
    $new_filename = 'ttd_sppd_' . uniqid() . '_' . time() . '.' . $file_ext;
    $upload_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($_FILES['ttd_kepaladesa']['tmp_name'], $upload_path)) {
        $ttd_kepaladesa_url = APP_URL . '/uploads/' . $new_filename;
    }
}

// Validasi
if (!$tanggal_pembuatan || !$nomor_sppd || !$nama_pegawai || !$nip || !$jabatan || !$alamat_tempat_tinggal || !$maksud_perjalanan || !$tempat_tujuan || !$jenis_durasi || !$tanggal_mulai || !$tanggal_selesai) {
    flash_set('Semua field harus diisi');
    header('Location: ' . APP_URL . '/' . ($id ? "?p=sppd_form&id=$id" : '?p=sppd_form'));
    exit;
}

if ($id) {
    // Update
    $data_array = [
        'tanggal_pembuatan' => $tanggal_pembuatan,
        'nomor_sppd' => $nomor_sppd,
        'nama_pegawai' => $nama_pegawai,
        'nip' => $nip,
        'jabatan' => $jabatan,
        'alamat_tempat_tinggal' => $alamat_tempat_tinggal,
        'maksud_perjalanan' => $maksud_perjalanan,
        'tempat_tujuan' => $tempat_tujuan,
        'jenis_durasi' => $jenis_durasi,
        'tanggal_mulai' => $tanggal_mulai,
        'tanggal_selesai' => $tanggal_selesai
    ];
    
    if ($nama_kepaladesa) {
        $data_array['nama_kepaladesa'] = $nama_kepaladesa;
    }
    if ($ttd_kepaladesa_url) {
        $data_array['ttd_kepaladesa'] = $ttd_kepaladesa_url;
    }
    
    $data = json_encode($data_array);
    
    $result = supabase_request('PATCH', "pengajuan_sppd?id=eq.$id", $data);
    
    if ($result['code'] === 200) {
        flash_set('Data SPPD berhasil diupdate');
    } else {
        flash_set('Error update: ' . json_encode($result));
    }
} else {
    // Insert
    $data_array = [
        'tanggal_pembuatan' => $tanggal_pembuatan,
        'nomor_sppd' => $nomor_sppd,
        'nama_pegawai' => $nama_pegawai,
        'nip' => $nip,
        'jabatan' => $jabatan,
        'alamat_tempat_tinggal' => $alamat_tempat_tinggal,
        'maksud_perjalanan' => $maksud_perjalanan,
        'tempat_tujuan' => $tempat_tujuan,
        'jenis_durasi' => $jenis_durasi,
        'tanggal_mulai' => $tanggal_mulai,
        'tanggal_selesai' => $tanggal_selesai
    ];
    
    if ($nama_kepaladesa) {
        $data_array['nama_kepaladesa'] = $nama_kepaladesa;
    }
    if ($ttd_kepaladesa_url) {
        $data_array['ttd_kepaladesa'] = $ttd_kepaladesa_url;
    }
    
    $data = json_encode($data_array);
    
    $result = supabase_request('POST', 'pengajuan_sppd', $data);
    
    if ($result['code'] === 201) {
        flash_set('Data SPPD berhasil ditambahkan');
    } else {
        flash_set('Error insert: ' . json_encode($result));
    }
}

header('Location: ' . APP_URL . '/?p=sppd');
exit;
