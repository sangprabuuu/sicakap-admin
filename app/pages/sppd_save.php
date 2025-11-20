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
$maksud_perjalanan = trim($_POST['maksud_perjalanan'] ?? '');
$tempat_tujuan = trim($_POST['tempat_tujuan'] ?? '');
$jenis_durasi = $_POST['jenis_durasi'] ?? '';
$tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
$tanggal_selesai = $_POST['tanggal_selesai'] ?? '';

// Validasi
if (!$tanggal_pembuatan || !$nomor_sppd || !$nama_pegawai || !$nip || !$jabatan || !$maksud_perjalanan || !$tempat_tujuan || !$jenis_durasi || !$tanggal_mulai || !$tanggal_selesai) {
    flash_set('Semua field harus diisi');
    header('Location: ' . APP_URL . '/' . ($id ? "?p=sppd_form&id=$id" : '?p=sppd_form'));
    exit;
}

if ($id) {
    // Update
    $data = json_encode([
        'tanggal_pembuatan' => $tanggal_pembuatan,
        'nomor_sppd' => $nomor_sppd,
        'nama_pegawai' => $nama_pegawai,
        'nip' => $nip,
        'jabatan' => $jabatan,
        'maksud_perjalanan' => $maksud_perjalanan,
        'tempat_tujuan' => $tempat_tujuan,
        'jenis_durasi' => $jenis_durasi,
        'tanggal_mulai' => $tanggal_mulai,
        'tanggal_selesai' => $tanggal_selesai
    ]);
    
    $result = supabase_request('PATCH', "pengajuan_sppd?id=eq.$id", $data);
    
    if ($result['code'] === 200) {
        flash_set('Data SPPD berhasil diupdate');
    } else {
        flash_set('Error update: ' . json_encode($result));
    }
} else {
    // Insert
    $data = json_encode([
        'tanggal_pembuatan' => $tanggal_pembuatan,
        'nomor_sppd' => $nomor_sppd,
        'nama_pegawai' => $nama_pegawai,
        'nip' => $nip,
        'jabatan' => $jabatan,
        'maksud_perjalanan' => $maksud_perjalanan,
        'tempat_tujuan' => $tempat_tujuan,
        'jenis_durasi' => $jenis_durasi,
        'tanggal_mulai' => $tanggal_mulai,
        'tanggal_selesai' => $tanggal_selesai
    ]);
    
    $result = supabase_request('POST', 'pengajuan_sppd', $data);
    
    if ($result['code'] === 201) {
        flash_set('Data SPPD berhasil ditambahkan');
    } else {
        flash_set('Error insert: ' . json_encode($result));
    }
}

header('Location: ' . APP_URL . '/?p=sppd');
exit;
