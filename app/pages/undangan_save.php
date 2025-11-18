<?php
$pdo = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ?p=undangan');
    exit;
}

$id = $_POST['id'] ?? '';
$tanggal_surat = $_POST['tanggal_surat'] ?? '';
$nomor_surat = trim($_POST['nomor_surat'] ?? '');
$perihal = trim($_POST['perihal'] ?? '');
$nama = trim($_POST['nama'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$tembusan_kepada = trim($_POST['tembusan_kepada'] ?? '');
$hari_tanggal = trim($_POST['hari_tanggal'] ?? '');
$tempat_pelaksanaan = trim($_POST['tempat_pelaksanaan'] ?? '');
$jam = $_POST['jam'] ?? '';

// Validasi
if (!$tanggal_surat || !$nomor_surat || !$perihal || !$nama || !$alamat || !$hari_tanggal || !$tempat_pelaksanaan || !$jam) {
    flash_set('Semua field harus diisi');
    header('Location: ' . ($id ? "?p=undangan_form&id=$id" : '?p=undangan_form'));
    exit;
}

try {
    if ($id) {
        // Update
        $stmt = $pdo->prepare("
            UPDATE surat_undangan SET
                tanggal_surat = ?,
                nomor_surat = ?,
                perihal = ?,
                nama = ?,
                alamat = ?,
                tembusan_kepada = ?,
                hari_tanggal = ?,
                tempat_pelaksanaan = ?,
                jam = ?,
                updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->execute([
            $tanggal_surat, $nomor_surat, $perihal, $nama, $alamat, 
            $tembusan_kepada, $hari_tanggal, $tempat_pelaksanaan, $jam, $id
        ]);
        flash_set('Data undangan berhasil diupdate');
    } else {
        // Insert
        $stmt = $pdo->prepare("
            INSERT INTO surat_undangan (
                tanggal_surat, nomor_surat, perihal, nama, alamat, 
                tembusan_kepada, hari_tanggal, tempat_pelaksanaan, jam,
                created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->execute([
            $tanggal_surat, $nomor_surat, $perihal, $nama, $alamat,
            $tembusan_kepada, $hari_tanggal, $tempat_pelaksanaan, $jam
        ]);
        flash_set('Data undangan berhasil ditambahkan');
    }
    
    header('Location: ?p=undangan');
    exit;
} catch (Exception $e) {
    flash_set('Error: ' . $e->getMessage());
    header('Location: ' . ($id ? "?p=undangan_form&id=$id" : '?p=undangan_form'));
    exit;
}
