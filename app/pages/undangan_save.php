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
$nama_kepaladesa = trim($_POST['nama_kepaladesa'] ?? '');

// Handle file upload TTD
$ttd_kepaladesa_url = '';
if (isset($_FILES['ttd_kepaladesa']) && $_FILES['ttd_kepaladesa']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/png', 'image/jpeg', 'image/jpg'];
    $max_size = 2 * 1024 * 1024; // 2MB
    
    if (!in_array($_FILES['ttd_kepaladesa']['type'], $allowed_types)) {
        flash_set('File harus berupa gambar PNG atau JPG');
        header('Location: ' . APP_URL . '/' . ($id ? "?p=undangan_form&id=$id" : '?p=undangan_form'));
        exit;
    }
    
    if ($_FILES['ttd_kepaladesa']['size'] > $max_size) {
        flash_set('Ukuran file maksimal 2MB');
        header('Location: ' . APP_URL . '/' . ($id ? "?p=undangan_form&id=$id" : '?p=undangan_form'));
        exit;
    }
    
    $upload_dir = __DIR__ . '/../../public/uploads/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_ext = pathinfo($_FILES['ttd_kepaladesa']['name'], PATHINFO_EXTENSION);
    $new_filename = 'ttd_undangan_' . uniqid() . '_' . time() . '.' . $file_ext;
    $upload_path = $upload_dir . $new_filename;
    
    if (move_uploaded_file($_FILES['ttd_kepaladesa']['tmp_name'], $upload_path)) {
        $ttd_kepaladesa_url = APP_URL . '/uploads/' . $new_filename;
    }
}

// Validasi
if (!$tanggal_surat || !$nomor_surat || !$perihal || !$nama || !$alamat || !$hari_tanggal || !$tempat_pelaksanaan || !$jam) {
    flash_set('Semua field harus diisi');
    header('Location: ' . APP_URL . '/' . ($id ? "?p=undangan_form&id=$id" : '?p=undangan_form'));
    exit;
}

// Prepare data
$data_array = [
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
];

// Add pejabat data if provided
if ($nama_kepaladesa) {
    $data_array['nama_kepaladesa'] = $nama_kepaladesa;
}
if ($ttd_kepaladesa_url) {
    $data_array['ttd_kepaladesa'] = $ttd_kepaladesa_url;
}

$data = json_encode($data_array);

// Debug mode
$debug = isset($_GET['debug']) && $_GET['debug'] == '1';

if ($debug) {
    echo "<h3>Debug Mode</h3>";
    echo "<h4>POST Data:</h4><pre>" . print_r($_POST, true) . "</pre>";
    echo "<h4>FILES Data:</h4><pre>" . print_r($_FILES, true) . "</pre>";
    echo "<h4>Data Array:</h4><pre>" . print_r($data_array, true) . "</pre>";
    echo "<h4>JSON Data:</h4><pre>" . htmlspecialchars($data) . "</pre>";
}

try {
    if ($id) {
        // Update
        $result = supabase_request('PATCH', "surat_undangan?id=eq.$id", $data);
        
        if ($debug) {
            echo "<h4>Supabase Response (Update):</h4><pre>" . print_r($result, true) . "</pre>";
        }
        
        if ($result['code'] === 200 || $result['code'] === 204) {
            flash_set('Data undangan berhasil diupdate');
        } else {
            $error_msg = isset($result['data']['message']) ? $result['data']['message'] : json_encode($result);
            throw new Exception('Gagal mengupdate data: ' . $error_msg);
        }
    } else {
        // Insert
        $result = supabase_request('POST', 'surat_undangan', $data);
        
        if ($debug) {
            echo "<h4>Supabase Response (Insert):</h4><pre>" . print_r($result, true) . "</pre>";
            exit;
        }
        
        if ($result['code'] === 201) {
            flash_set('Data undangan berhasil ditambahkan');
        } else {
            $error_msg = isset($result['data']['message']) ? $result['data']['message'] : json_encode($result);
            throw new Exception('Gagal menambahkan data: ' . $error_msg);
        }
    }
    
    header('Location: ' . APP_URL . '/?p=undangan');
    exit;
} catch (Exception $e) {
    flash_set('Error: ' . $e->getMessage());
    header('Location: ' . APP_URL . '/' . ($id ? "?p=undangan_form&id=$id" : '?p=undangan_form'));
    exit;
}
