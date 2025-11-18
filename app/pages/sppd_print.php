<?php
$pdo = db();

$id = $_GET['id'] ?? '';

if (!$id) {
    redirect('?p=sppd');
}

$stmt = $pdo->prepare("SELECT * FROM sppd WHERE id = ?");
$stmt->execute([$id]);
$sppd = $stmt->fetch();

if (!$sppd) {
    flash_set('Data SPPD tidak ditemukan');
    redirect('?p=sppd');
}

// Format tanggal Indonesia
function format_tanggal_indonesia($date) {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $split = explode('-', $date);
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak SPPD - <?= h($sppd['nomor']) ?></title>
    <style>
        @media print {
            .no-print {
                display: none;
            }
            body {
                margin: 0;
                padding: 20px;
            }
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            line-height: 1.6;
            padding: 40px;
            max-width: 21cm;
            margin: 0 auto;
            background: #fff;
        }
        
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 3px solid #000;
            padding-bottom: 15px;
        }
        
        .header h2 {
            margin: 5px 0;
            font-size: 18px;
            font-weight: bold;
        }
        
        .header h1 {
            margin: 10px 0;
            font-size: 20px;
            font-weight: bold;
            text-decoration: underline;
        }
        
        .nomor-surat {
            text-align: center;
            margin-bottom: 30px;
            font-weight: bold;
        }
        
        .content {
            margin: 20px 0;
        }
        
        .content table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .content table td {
            padding: 8px 10px;
            vertical-align: top;
        }
        
        .content table td:first-child {
            width: 30%;
        }
        
        .content table td:nth-child(2) {
            width: 5%;
        }
        
        .ttd-section {
            margin-top: 50px;
            display: flex;
            justify-content: space-between;
        }
        
        .ttd-box {
            width: 45%;
            text-align: center;
        }
        
        .ttd-box p {
            margin: 5px 0;
        }
        
        .ttd-space {
            height: 80px;
        }
        
        .btn-print {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 10px 20px;
            background: #4a7c2c;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .btn-print:hover {
            background: #3a6c1c;
        }
        
        .btn-back {
            position: fixed;
            top: 20px;
            right: 150px;
            padding: 10px 20px;
            background: #666;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .btn-back:hover {
            background: #555;
        }
    </style>
</head>
<body>
    <a href="?p=sppd" class="btn-back no-print">← Kembali</a>
    <button onclick="window.print()" class="btn-print no-print">🖨️ Print</button>
    
    <div class="header">
        <h2>PEMERINTAH KABUPATEN/KOTA</h2>
        <h2>KECAMATAN</h2>
        <h2>DESA/KELURAHAN</h2>
        <h1>SURAT PERINTAH PERJALANAN DINAS (SPPD)</h1>
    </div>
    
    <div class="nomor-surat">
        Nomor: <?= h($sppd['nomor']) ?>
    </div>
    
    <div class="content">
        <table>
            <tr>
                <td>1. Pejabat yang memberi perintah</td>
                <td>:</td>
                <td>Kepala Desa/Kelurahan</td>
            </tr>
            <tr>
                <td>2. Nama/NIP pegawai yang diperintah</td>
                <td>:</td>
                <td>
                    <strong><?= h($sppd['nama']) ?></strong><br>
                    NIP. <?= h($sppd['nip']) ?>
                </td>
            </tr>
            <tr>
                <td>3. Jabatan</td>
                <td>:</td>
                <td><?= h($sppd['jabatan']) ?></td>
            </tr>
            <tr>
                <td>4. Maksud perjalanan dinas</td>
                <td>:</td>
                <td><?= h($sppd['maksud']) ?></td>
            </tr>
            <tr>
                <td>5. Tempat tujuan</td>
                <td>:</td>
                <td><?= h($sppd['tempat_tujuan']) ?></td>
            </tr>
            <tr>
                <td>6. Lamanya perjalanan dinas</td>
                <td>:</td>
                <td>
                    <?php if ($sppd['durasi'] == '1_hari'): ?>
                        1 (satu) hari
                    <?php else: ?>
                        <?php
                        $start = new DateTime($sppd['tanggal_mulai']);
                        $end = new DateTime($sppd['tanggal_selesai']);
                        $diff = $start->diff($end)->days + 1;
                        ?>
                        <?= $diff ?> hari
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td>7. Tanggal berangkat</td>
                <td>:</td>
                <td><?= format_tanggal_indonesia($sppd['tanggal_mulai']) ?></td>
            </tr>
            <tr>
                <td>8. Tanggal harus kembali</td>
                <td>:</td>
                <td><?= format_tanggal_indonesia($sppd['tanggal_selesai']) ?></td>
            </tr>
            <tr>
                <td>9. Keterangan lain-lain</td>
                <td>:</td>
                <td>-</td>
            </tr>
        </table>
    </div>
    
    <div class="ttd-section">
        <div class="ttd-box">
            <p>&nbsp;</p>
        </div>
        <div class="ttd-box">
            <p>Dikeluarkan di : _______________</p>
            <p>Tanggal : <?= format_tanggal_indonesia($sppd['tanggal']) ?></p>
            <p style="margin-top: 10px;">Kepala Desa/Kelurahan</p>
            <div class="ttd-space"></div>
            <p>____________________________</p>
            <p>NIP. ______________________</p>
        </div>
    </div>
</body>
</html>
