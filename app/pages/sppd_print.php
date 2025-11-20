<?php
// Cek login
if (!is_logged_in()) {
    header('Location: ' . APP_URL . '/?p=login');
    exit;
}

// Get ID from URL
$id = $_GET['id'] ?? '';

if (!$id) {
    flash_set('ID SPPD tidak ditemukan');
    header('Location: ' . APP_URL . '/?p=sppd');
    exit;
}

// Get SPPD data dari Supabase
$result = supabase_request('GET', "pengajuan_sppd?id=eq.$id&select=*");

if (empty($result['data'])) {
    flash_set('Data SPPD tidak ditemukan');
    header('Location: ' . APP_URL . '/?p=sppd');
    exit;
}

$sppd = $result['data'][0];

// Format tanggal Indonesia
function formatTanggalIndo($date) {
    if (!$date) return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $split = explode('-', $date);
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

// Hitung lama perjalanan
$tanggal_mulai = strtotime($sppd['tanggal_mulai']);
$tanggal_selesai = strtotime($sppd['tanggal_selesai']);
$diff_days = ceil(($tanggal_selesai - $tanggal_mulai) / (60 * 60 * 24)) + 1;
$lama_perjalanan = $diff_days . ' hari';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat Tugas - <?= h($sppd['nomor_sppd']) ?></title>
    <style>
        @page {
            size: A4;
            margin: 2cm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
            background: #fff;
            padding: 20px;
        }
        
        .container {
            max-width: 21cm;
            margin: 0 auto;
            background: white;
            padding: 2cm;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
        }
        
        .logo {
            width: 80px;
            height: 80px;
            margin: 0 auto 10px;
        }
        
        .header h1 {
            font-size: 18pt;
            font-weight: bold;
            margin: 5px 0;
            text-transform: uppercase;
        }
        
        .header h2 {
            font-size: 16pt;
            font-weight: bold;
            margin: 5px 0;
        }
        
        .header p {
            font-size: 10pt;
            margin: 2px 0;
        }
        
        .title {
            text-align: center;
            margin: 30px 0 20px 0;
        }
        
        .title h3 {
            font-size: 14pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 5px;
        }
        
        .title p {
            font-size: 12pt;
        }
        
        .content {
            margin: 30px 0;
            text-align: justify;
        }
        
        .content p {
            margin-bottom: 15px;
        }
        
        .content table {
            width: 100%;
            margin: 20px 0;
            border-collapse: collapse;
        }
        
        .content table td {
            padding: 5px;
            vertical-align: top;
        }
        
        .content table td:first-child {
            width: 30%;
        }
        
        .content table td:nth-child(2) {
            width: 5%;
        }
        
        .content table td:last-child {
            width: 65%;
        }
        
        .signature {
            margin-top: 50px;
            text-align: right;
        }
        
        .signature-box {
            display: inline-block;
            text-align: center;
            min-width: 250px;
        }
        
        .signature-box p {
            margin: 5px 0;
        }
        
        .signature-name {
            font-weight: bold;
            text-decoration: underline;
            margin-top: 80px;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .container {
                padding: 0;
            }
            
            .no-print {
                display: none;
            }
        }
        
        .print-button {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #4a7c2c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        
        .print-button:hover {
            background: #3a6c1c;
        }
        
        .back-button {
            position: fixed;
            top: 20px;
            right: 140px;
            padding: 10px 20px;
            background: #666;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
        }
        
        .back-button:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <a href="<?= APP_URL ?>/?p=sppd" class="back-button no-print">← Kembali</a>
    <button onclick="window.print()" class="print-button no-print">🖨️ Cetak</button>
    
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>PEMERINTAH KABUPATEN BANDUNG</h1>
            <h2>KECAMATAN [NAMA KECAMATAN]</h2>
            <p>Jl. [Alamat Kantor] Telp. (022) [Nomor Telepon]</p>
            <p>Email: [email@kecamatan.go.id]</p>
        </div>
        
        <!-- Title -->
        <div class="title">
            <h3>SURAT TUGAS</h3>
            <p>Nomor: <?= h($sppd['nomor_sppd'] ?? '-') ?></p>
        </div>
        
        <!-- Content -->
        <div class="content">
            <p>Yang bertanda tangan di bawah ini:</p>
            
            <table>
                <tr>
                    <td>Nama</td>
                    <td>:</td>
                    <td><strong>[Nama Camat]</strong></td>
                </tr>
                <tr>
                    <td>Jabatan</td>
                    <td>:</td>
                    <td><strong>Camat [Nama Kecamatan]</strong></td>
                </tr>
            </table>
            
            <p>Dengan ini menugaskan kepada:</p>
            
            <table>
                <tr>
                    <td>Nama</td>
                    <td>:</td>
                    <td><strong><?= h($sppd['nama_pegawai']) ?></strong></td>
                </tr>
                <tr>
                    <td>NIP</td>
                    <td>:</td>
                    <td><?= h($sppd['nip']) ?></td>
                </tr>
                <tr>
                    <td>Jabatan</td>
                    <td>:</td>
                    <td><?= h($sppd['jabatan']) ?></td>
                </tr>
                <tr>
                    <td>Maksud Perjalanan</td>
                    <td>:</td>
                    <td><?= h($sppd['maksud_perjalanan']) ?></td>
                </tr>
                <tr>
                    <td>Tujuan</td>
                    <td>:</td>
                    <td><?= h($sppd['tempat_tujuan']) ?></td>
                </tr>
                <tr>
                    <td>Lama Perjalanan</td>
                    <td>:</td>
                    <td><?= $lama_perjalanan ?> (<?= formatTanggalIndo($sppd['tanggal_mulai']) ?> s/d <?= formatTanggalIndo($sppd['tanggal_selesai']) ?>)</td>
                </tr>
                <tr>
                    <td>Jenis Durasi</td>
                    <td>:</td>
                    <td><?= ucfirst(h($sppd['jenis_durasi'])) ?></td>
                </tr>
            </table>
            
            <p>Demikian surat tugas ini dibuat untuk dapat dilaksanakan dengan penuh tanggung jawab.</p>
        </div>
        
        <!-- Signature -->
        <div class="signature">
            <div class="signature-box">
                <p><?= h($sppd['tempat_tujuan']) ?>, <?= formatTanggalIndo($sppd['tanggal_pembuatan']) ?></p>
                <p><strong>Camat [Nama Kecamatan]</strong></p>
                <p class="signature-name">[Nama Camat]</p>
                <p>NIP. [NIP Camat]</p>
            </div>
        </div>
    </div>
    
    <script>
        // Auto print on load (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
