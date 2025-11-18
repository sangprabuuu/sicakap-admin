<?php
$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?p=sppd');
    exit;
}

$id = $_POST['id'] ?? '';
$nomor = trim($_POST['nomor'] ?? '');
$tanggal = $_POST['tanggal'] ?? '';
$nama = trim($_POST['nama'] ?? '');
$nip = trim($_POST['nip'] ?? '');
$jabatan = trim($_POST['jabatan'] ?? '');
$maksud = trim($_POST['maksud'] ?? '');
$tempat_tujuan = trim($_POST['tempat_tujuan'] ?? '');
$durasi = $_POST['durasi'] ?? '';
$tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
$tanggal_selesai = $_POST['tanggal_selesai'] ?? '';

// Validasi
if (!$nomor || !$tanggal || !$nama || !$nip || !$jabatan || !$maksud || !$tempat_tujuan || !$durasi || !$tanggal_mulai || !$tanggal_selesai) {
    flash_set('Semua field harus diisi');
    header('Location: ' . ($id ? "?p=sppd_form&id=$id" : '?p=sppd_form'));
    exit;
}

try {
    if ($id) {
        // Update
        $stmt = $pdo->prepare("
            UPDATE sppd SET
                nomor = ?,
                tanggal = ?,
                nama = ?,
                nip = ?,
                jabatan = ?,
                maksud = ?,
                tempat_tujuan = ?,
                durasi = ?,
                tanggal_mulai = ?,
                tanggal_selesai = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $nomor, $tanggal, $nama, $nip, $jabatan, $maksud, 
            $tempat_tujuan, $durasi, $tanggal_mulai, $tanggal_selesai, $id
        ]);
        flash_set('Data SPPD berhasil diupdate');
    } else {
        // Insert
        $stmt = $pdo->prepare("
            INSERT INTO sppd (
                nomor, tanggal, nama, nip, jabatan, maksud, 
                tempat_tujuan, durasi, tanggal_mulai, tanggal_selesai,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $nomor, $tanggal, $nama, $nip, $jabatan, $maksud,
            $tempat_tujuan, $durasi, $tanggal_mulai, $tanggal_selesai
        ]);
        flash_set('Data SPPD berhasil ditambahkan');
    }
    
    header('Location: ?p=sppd');
    exit;
} catch (Exception $e) {
    flash_set('Error: ' . $e->getMessage());
    header('Location: ' . ($id ? "?p=sppd_form&id=$id" : '?p=sppd_form'));
    exit;
}
