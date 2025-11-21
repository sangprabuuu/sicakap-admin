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

// Format hari dalam bahasa Indonesia
function formatHariIndo($date) {
    if (!$date) return '-';
    $hari = [
        'Sunday' => 'Minggu',
        'Monday' => 'Senin',
        'Tuesday' => 'Selasa',
        'Wednesday' => 'Rabu',
        'Thursday' => 'Kamis',
        'Friday' => 'Jumat',
        'Saturday' => 'Sabtu'
    ];
    $day_name = date('l', strtotime($date));
    return $hari[$day_name] ?? '-';
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
    <title>Surat Perintah/Tugas - <?= h($sppd['nomor_sppd']) ?></title>
    <style>
        @page {
            size: A4;
            margin: 1.5cm 2cm;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            line-height: 1.5;
            color: #000;
            background: #fff;
            padding: 20px;
        }
        
        .container {
            max-width: 21cm;
            margin: 0 auto;
            background: white;
        }
        
        .header {
            text-align: center;
            margin-bottom: 15px;
            border-bottom: 3px solid #000;
            padding-bottom: 8px;
            position: relative;
        }
        
        .logo {
            position: absolute;
            left: 20px;
            top: 19px;
            width: 110px;
            height: 110px;
        }
        
        .header-text {
            padding-top: 10px;
            margin-left: 60px;
        }
        
        .header h1 {
            font-size: 14pt;
            font-weight: bold;
            margin: 2px 0;
            text-transform: uppercase;
        }
        
        .header h2 {
            font-size: 13pt;
            font-weight: bold;
            margin: 2px 0;
            text-transform: uppercase;
        }
        
        .header h3 {
            font-size: 16pt;
            font-weight: bold;
            margin: 5px 0;
            text-transform: uppercase;
        }
        
        .header p {
            font-size: 11pt;
            margin: 2px 0;
        }
        
        .title {
            text-align: center;
            margin: 20px 0 15px 0;
        }
        
        .title h3 {
            font-size: 14pt;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 10px;
        }
        
        .title p {
            font-size: 12pt;
        }
        
        .content {
            margin: 20px 0;
            text-align: justify;
        }
        
        .content p {
            margin-bottom: 12px;
            text-indent: 50px;
        }
        
        .content p.no-indent {
            text-indent: 0;
        }
        
        .detail-list {
            margin: 15px 0 15px 40px;
        }
        
        .detail-list ol {
            list-style: decimal;
            padding-left: 20px;
        }
        
        .detail-list li {
            margin: 8px 0;
        }
        
        .detail-row {
            display: flex;
            margin: 5px 0;
        }
        
        .detail-row .label {
            width: 150px;
        }
        
        .detail-row .colon {
            width: 20px;
        }
        
        .detail-row .value {
            flex: 1;
        }
        
        .signature {
            margin-top: 30px;
            text-align: right;
        }
        
        .signature-box {
            display: inline-block;
            text-align: center;
            min-width: 200px;
        }
        
        .signature-box p {
            margin: 5px 0;
        }
        
        .signature-name {
            font-weight: bold;
            margin-top: 60px;
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
    <!-- <a href="<?= APP_URL ?>/?p=sppd" class="back-button no-print">← Kembali</a> -->
    <button onclick="window.print()" class="print-button no-print">
        <svg width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
             <path d="M5 1a2 2 0 0 0-2 2v1h10V3a2 2 0 0 0-2-2H5zm6 8H5a1 1 0 0 0-1 1v3a1 1 0 0 0 1 1h6a1 1 0 0 0 1-1v-3a1 1 0 0 0-1-1z"/>
             <path d="M0 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v3a2 2 0 0 1-2 2h-1v-2a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2H2a2 2 0 0 1-2-2V7zm2.5 1a.5.5 0 1 0 0-1 .5.5 0 0 0 0 1z"/>
        </svg>
    </button>
    
    <div class="container">
        <!-- Header -->
        <div class="header">
            <img src="<?= rtrim(APP_URL, '/') ?>/assets/images/logo_kabupaten.jpg" alt="Logo Kabupaten" class="logo">
            <div class="header-text">
                <h1>PEMERINTAH KABUPATEN PURBALINGGA</h1>
                <h2>KECAMATAN MREBET</h2>
                <h3>DESA CAMPAKOAH</h3>
                <p>Alamat : Jalan Desa Campakoah &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Kode Pos : 53352</p>
            </div>
        </div>
        
        <!-- Title -->
        <div class="title">
            <h3>SURAT PERINTAH / TUGAS</h3>
            <p>Nomor : &nbsp;&nbsp; <?= h($sppd['nomor_sppd'] ?? '_____ / _____ / _____') ?></p>
        </div>
        
        <!-- Content -->
        <div class="content">
            <p>Yang bertanda tangan dibawah ini Kepala Desa Campakoah  Kecamatan Mrebet Kabupaten Purbalingga, dengan ini memberikan perintah kepada saudara :</p>
            
            <div class="detail-list">
                <ol>
                    <li>
                        <div class="detail-row">
                            <div class="label">Nama</div>
                            <div class="colon">:</div>
                            <div class="value"><?= h($sppd['nama_pegawai']) ?></div>
                        </div>
                    </li>
                    <li>
                        <div class="detail-row">
                            <div class="label">Jabatan</div>
                            <div class="colon">:</div>
                            <div class="value"><?= h($sppd['jabatan']) ?></div>
                        </div>
                    </li>
                    <li>
                        <div class="detail-row">
                            <div class="label">Alamat Tempat tinggal</div>
                            <div class="colon">:</div>
                            <div class="value"><?= h($sppd['alamat_tempat_tinggal'] ?? 'Desa Campakoah') ?></div>
                        </div>
                    </li>
                </ol>
            </div>
            
            <p>Untuk melaksanakan perintah dan mengadakan perjalanan dinas dalam rangka  sebagai berikut :</p>
            
            <div class="detail-list">
                <ol>
                    <li>
                        <div class="detail-row">
                            <div class="label">Hari</div>
                            <div class="colon">:</div>
                            <div class="value"><?= formatHariIndo($sppd['tanggal_mulai']) ?></div>
                        </div>
                    </li>
                    <li>
                        <div class="detail-row">
                            <div class="label">Tanggal</div>
                            <div class="colon">:</div>
                            <div class="value">
                                <?php 
                                if ($sppd['tanggal_mulai'] === $sppd['tanggal_selesai']) {
                                    echo formatTanggalIndo($sppd['tanggal_mulai']);
                                } else {
                                    echo formatTanggalIndo($sppd['tanggal_mulai']) . ' s/d ' . formatTanggalIndo($sppd['tanggal_selesai']);
                                }
                                ?>
                            </div>
                        </div>
                    </li>
                    <li>
                        <div class="detail-row">
                            <div class="label">Kantor Tujuan</div>
                            <div class="colon">:</div>
                            <div class="value"><?= h($sppd['tempat_tujuan']) ?></div>
                        </div>
                    </li>
                    <li>
                        <div class="detail-row">
                            <div class="label">Kegiatan</div>
                            <div class="colon">:</div>
                            <div class="value"><?= h($sppd['maksud_perjalanan']) ?></div>
                        </div>
                    </li>
                </ol>
            </div>
            
            <p>Demikian Surat Tugas ini kami buat untuk dapat dilaksanakan sesuai dengan ketentuan dan harap maklum bagi yang berkepentingan.</p>
        </div>
        
        <!-- Signature -->
        <div class="signature">
            <div class="signature-box">
                <p>Campakoah, <?= formatTanggalIndo($sppd['tanggal_pembuatan']) ?></p>
                <p><strong>KEPALA DESA CAMPAKOAH</strong></p>
                <br><br><br><br>
                <p class="signature-name">SUTOMO</p>
            </div>
        </div>
    </div>
    
    <script>
        // Auto print on load (optional)
        // window.onload = function() { window.print(); }
    </script>
</body>
</html>
