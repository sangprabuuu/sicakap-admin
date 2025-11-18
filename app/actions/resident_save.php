<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../functions.php';
if (!is_logged_in()) exit('Unauthorized');

// Handler untuk menyimpan atau update data penduduk

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../?p=residents');
    exit;
}

$pdo = db();

// Ambil data dari form
$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$nik = trim($_POST['nik'] ?? '');
$name = trim($_POST['name'] ?? '');
$tempat_lahir = trim($_POST['tempat_lahir'] ?? '');
$tanggal_lahir = trim($_POST['tanggal_lahir'] ?? '');
$jenis_kelamin = trim($_POST['jenis_kelamin'] ?? '');
$agama = trim($_POST['agama'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$rt = trim($_POST['rt'] ?? '');
$rw = trim($_POST['rw'] ?? '');
$desa = trim($_POST['desa'] ?? '');
$pekerjaan = trim($_POST['pekerjaan'] ?? '');
$status_perkawinan = trim($_POST['status_perkawinan'] ?? '');
$kewarganegaraan = trim($_POST['kewarganegaraan'] ?? '');
$nama_ayah = trim($_POST['nama_ayah'] ?? '');
$nama_ibu = trim($_POST['nama_ibu'] ?? '');

// Validasi
$errors = [];

if (empty($nik)) {
    $errors[] = 'NIK harus diisi';
}

if (empty($name)) {
    $errors[] = 'Nama lengkap harus diisi';
}

// Cek apakah NIK sudah ada (kecuali untuk data yang sedang diedit)
if ($nik) {
    $check_sql = "SELECT id FROM residents WHERE nik = :nik";
    if ($id > 0) {
        $check_sql .= " AND id != :id";
    }
    $stmt = $pdo->prepare($check_sql);
    $stmt->bindValue(':nik', $nik);
    if ($id > 0) {
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
    }
    $stmt->execute();
    if ($stmt->fetch()) {
        $errors[] = 'NIK sudah terdaftar untuk penduduk lain';
    }
}

// Validasi format tanggal jika diisi
if ($tanggal_lahir && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal_lahir)) {
    $errors[] = 'Format tanggal lahir tidak valid';
}

// Jika ada error, kembali ke form
if (!empty($errors)) {
    flash_set(implode(', ', $errors));
    if ($id > 0) {
        header("Location: ../?p=resident_form&id=$id");
    } else {
        header("Location: ../?p=resident_form");
    }
    exit;
}

try {
    if ($id > 0) {
        // Update data
        $sql = "UPDATE residents SET 
                nik = :nik,
                name = :name,
                tempat_lahir = :tempat_lahir,
                tanggal_lahir = :tanggal_lahir,
                jenis_kelamin = :jenis_kelamin,
                agama = :agama,
                alamat = :alamat,
                rt = :rt,
                rw = :rw,
                desa = :desa,
                pekerjaan = :pekerjaan,
                status_perkawinan = :status_perkawinan,
                kewarganegaraan = :kewarganegaraan,
                nama_ayah = :nama_ayah,
                nama_ibu = :nama_ibu
                WHERE id = :id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':id' => $id,
            ':nik' => $nik,
            ':name' => $name,
            ':tempat_lahir' => $tempat_lahir ?: null,
            ':tanggal_lahir' => $tanggal_lahir ?: null,
            ':jenis_kelamin' => $jenis_kelamin ?: null,
            ':agama' => $agama ?: null,
            ':alamat' => $alamat ?: null,
            ':rt' => $rt ?: null,
            ':rw' => $rw ?: null,
            ':desa' => $desa ?: null,
            ':pekerjaan' => $pekerjaan ?: null,
            ':status_perkawinan' => $status_perkawinan ?: null,
            ':kewarganegaraan' => $kewarganegaraan ?: null,
            ':nama_ayah' => $nama_ayah ?: null,
            ':nama_ibu' => $nama_ibu ?: null,
        ]);
        
        flash_set('Data penduduk berhasil diperbarui');
    } else {
        // Insert data baru
        $sql = "INSERT INTO residents (
                    nik, name, tempat_lahir, tanggal_lahir, jenis_kelamin,
                    agama, alamat, rt, rw, desa, pekerjaan,
                    status_perkawinan, kewarganegaraan, nama_ayah, nama_ibu
                ) VALUES (
                    :nik, :name, :tempat_lahir, :tanggal_lahir, :jenis_kelamin,
                    :agama, :alamat, :rt, :rw, :desa, :pekerjaan,
                    :status_perkawinan, :kewarganegaraan, :nama_ayah, :nama_ibu
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':nik' => $nik,
            ':name' => $name,
            ':tempat_lahir' => $tempat_lahir ?: null,
            ':tanggal_lahir' => $tanggal_lahir ?: null,
            ':jenis_kelamin' => $jenis_kelamin ?: null,
            ':agama' => $agama ?: null,
            ':alamat' => $alamat ?: null,
            ':rt' => $rt ?: null,
            ':rw' => $rw ?: null,
            ':desa' => $desa ?: null,
            ':pekerjaan' => $pekerjaan ?: null,
            ':status_perkawinan' => $status_perkawinan ?: null,
            ':kewarganegaraan' => $kewarganegaraan ?: null,
            ':nama_ayah' => $nama_ayah ?: null,
            ':nama_ibu' => $nama_ibu ?: null,
        ]);
        
        flash_set('Data penduduk berhasil ditambahkan');
    }
    
    header('Location: ../?p=residents');
    exit;
    
} catch (PDOException $e) {
    error_log('Error saving resident: ' . $e->getMessage());
    flash_set('Terjadi kesalahan saat menyimpan data');
    if ($id > 0) {
        header("Location: ../?p=resident_form&id=$id");
    } else {
        header("Location: ../?p=resident_form");
    }
    exit;
}