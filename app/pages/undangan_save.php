<?php
if (!is_logged_in()) {
    header('Location: ' . APP_URL . '/?p=login');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . APP_URL . '/?p=undangan');
    exit;
}

$id = $_POST['id'] ?? '';
$tanggal_surat = $_POST['tanggal_surat'] ?? '';
$nomor_surat = trim($_POST['nomor_surat'] ?? '');
$perihal = trim($_POST['perihal'] ?? '');
$hal = trim($_POST['hal'] ?? '');
$agenda = trim($_POST['agenda'] ?? '');
$nama = trim($_POST['nama'] ?? '');
$alamat = trim($_POST['alamat'] ?? '');
$tembusan_kepada = trim($_POST['tembusan_kepada'] ?? '');
$tanggal_pelaksanaan = $_POST['tanggal_pelaksanaan'] ?? '';
$hari_tanggal = trim($_POST['hari_tanggal'] ?? '');
$tempat_pelaksanaan = trim($_POST['tempat_pelaksanaan'] ?? '');
$jam = $_POST['jam'] ?? '';

// Validasi
if (!$tanggal_surat || !$nomor_surat || !$perihal || !$nama || !$alamat || !$hari_tanggal || !$tempat_pelaksanaan || !$jam) {
    flash_set('Semua field harus diisi');
    header('Location: ' . APP_URL . '/' . ($id ? "?p=undangan_form&id=$id" : '?p=undangan_form'));
    exit;
}

// Prepare data
$data = json_encode([
    'tanggal_surat' => $tanggal_surat,
    'nomor_surat' => $nomor_surat,
    'perihal' => $perihal,
    'hal' => $hal,
    'agenda' => $agenda,
    'nama' => $nama,
    'alamat' => $alamat,
    'tembusan_kepada' => $tembusan_kepada,
    'tanggal_pelaksanaan' => $tanggal_pelaksanaan,
    'jam' => $jam,
    'hari_tanggal' => $hari_tanggal,
    'tempat_pelaksanaan' => $tempat_pelaksanaan
]);

try {
    if ($id) {
        // Update
        $result = supabase_request('PATCH', "surat_undangan?id=eq.$id", $data);
        
        if ($result['code'] === 200) {
            flash_set('Data undangan berhasil diupdate');
        } else {
            throw new Exception('Gagal mengupdate data');
        }
    } else {
        // Insert
        $result = supabase_request('POST', 'surat_undangan', $data);
        
        if ($result['code'] === 201) {
            flash_set('Data undangan berhasil ditambahkan');
        } else {
            throw new Exception('Gagal menambahkan data');
        }
    }
    
    header('Location: ' . APP_URL . '/?p=undangan');
    exit;
} catch (Exception $e) {
    flash_set('Error: ' . $e->getMessage());
    header('Location: ' . APP_URL . '/' . ($id ? "?p=undangan_form&id=$id" : '?p=undangan_form'));
    exit;
}
