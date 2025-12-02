<?php
// Cek login
if (!is_logged_in()) {
    header('Location: ' . APP_URL . '/?p=login');
    exit;
}

$id = $_GET['id'] ?? '';

if (!$id) {
    header('Location: ' . APP_URL . '/?p=undangan');
    exit;
}

// Get data dari Supabase
$result = supabase_request('GET', "surat_undangan?id=eq.$id&select=*");

if (empty($result['data'])) {
    flash_set('Data undangan tidak ditemukan');
    header('Location: ' . APP_URL . '/?p=undangan');
    exit;
}

$data = $result['data'][0];

// Get pejabat data
$nama_kepala_desa = $data['nama_kepaladesa'] ?? 'KUSTOMO';
$ttd_kepala_desa_url = $data['ttd_kepaladesa'] ?? '';

// Format tanggal Indonesia
$bulan_indo = [
    1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
    'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
];

$tgl = date('d', strtotime($data['tanggal_surat']));
$bln = (int)date('m', strtotime($data['tanggal_surat']));
$thn = date('Y', strtotime($data['tanggal_surat']));
$tanggal_indo = "$tgl " . $bulan_indo[$bln] . " $thn";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Print Surat Undangan - <?= h($data['nomor_surat']) ?></title>
    <style>
        @page {
            size: A4;
            margin: 1cm;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: white;
        }
        
        .container {
            max-width: 21cm;
            margin: 0 auto;
        }
        
        .kop-surat {
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .kop-surat h2, .kop-surat h1 {
            font-weight: bold;
            line-height: 1.2;
            margin: 2px 0;
        }
        
        .nomor-surat {
            margin: 20px 0;
        }
        
        .nomor-surat table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .nomor-surat td {
            padding: 3px 0;
            vertical-align: top;
            font-size: 11pt;
        }
        
        .nomor-surat td:first-child {
            width: 100px;
        }
        
        .nomor-surat td:nth-child(2) {
            width: 15px;
            text-align: center;
        }
        
        .isi-surat {
            margin: 20px 0;
            text-align: justify;
            font-size: 12pt;
        }
        
        .isi-surat p {
            margin: 10px 0;
            line-height: 1.8;
        }
        
        .detail-undangan {
            margin: 20px 0 20px 60px;
        }
        
        .detail-undangan table {
            border-collapse: collapse;
        }
        
        .detail-undangan table td {
            padding: 5px 0;
            vertical-align: top;
            font-size: 12pt;
        }
        
        .detail-undangan td:first-child {
            width: 140px;
        }
        
        .detail-undangan td:nth-child(2) {
            width: 15px;
            text-align: center;
        }
        
        .ttd {
            margin-top: 40px;
            text-align: center;
            float: right;
            width: 250px;
        }
        
        .ttd p {
            margin: 5px 0;
            font-size: 12pt;
        }
        
        .ttd-space {
            height: 70px;
        }
        
        .ttd-image {
            max-width: 150px;
            max-height: 60px;
            margin: 5px auto;
            display: block;
        }
        
        .tembusan {
            margin-top: 100px;
            clear: both;
            font-size: 11pt;
        }
        
        .tembusan p {
            margin: 5px 0;
        }
        
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
            .container {
                padding: 0;
            }
        }
        
        .no-print {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1000;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 5px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }
        
        .btn-primary {
            background: #4a7c2c;
            color: white;
        }
        
        .btn-primary:hover {
            background: #3a6c1c;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print</button>
        <a href="<?= APP_URL ?>/?p=undangan" class="btn btn-secondary">← Kembali</a>
    </div>

    <div class="container">
    <div class="kop-surat">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 100px; vertical-align: top; padding-right: 15px;">
                    <img src="<?= rtrim(APP_URL, '/') ?>/assets/images/logo_kabupaten.jpg" alt="Logo" style="width: 90px; height: auto;">
                </td>
                <td style="text-align: center; vertical-align: middle;">
                    <h2 style="margin: 0; font-size: 13pt; line-height: 1.2;">PEMERINTAH KABUPATEN PURBALINGGA</h2>
                    <h2 style="margin: 0; font-size: 13pt; line-height: 1.2;">KECAMATAN MREBET</h2>
                    <h1 style="margin: 3px 0; font-size: 18pt; font-weight: bold;">DESA CAMPAKOAH</h1>
                    <p style="margin: 2px 0; font-size: 10pt;">Alamat: Jalan Desa Campakoah &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Kode Pos: 53352</p>
                </td>
            </tr>
        </table>
    </div>

    <div class="nomor-surat">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div style="flex: 1;">
                <table>
                    <tr>
                        <td>Nomor</td>
                        <td>:</td>
                        <td><?= h($data['nomor_surat']) ?></td>
                    </tr>
                    <tr>
                        <td>Lamp</td>
                        <td>:</td>
                        <td>-</td>
                    </tr>
                    <tr>
                        <td>Hal</td>
                        <td>:</td>
                        <td><strong><?= h($data['hal'] ?? $data['perihal']) ?></strong></td>
                    </tr>
                </table>
                
                <div style="margin-top: 20px;">
                    <p style="margin: 5px 0;">Kepada</p>
                    <p style="margin: 5px 0;">Yth. Bpk/Ibu/Sdr.</p>
                    <p style="margin: 5px 0; padding-left: 40px;"><strong><?= h($data['nama']) ?></strong></p>
                    <p style="margin: 5px 0; padding-left: 40px;">di <strong><?= h($data['alamat']) ?></strong></p>
                </div>
            </div>
            
            <div style="text-align: right; padding-top: 0;">
                <p>Campakoah, <?= $tanggal_indo ?></p>
            </div>
        </div>
    </div>

    <div class="isi-surat">
        <p style="text-indent: 00px; font-style: italic; margin: 25px 0 20px 0;">
            <em>Bismillahirrahmanirrahim</em><br>
            <em>Assalamu'alaikum Wr. Wb.</em>
        </p>
        
        <p style="text-indent: 50px; margin: 20px 0;">
            Dengan hormat, sehubungan dengan pelaksanaan <strong><?= h($data['hal'] ?? $data['perihal']) ?></strong> dalam rangka <strong><?= h($data['perihal']) ?></strong>, maka kami mohon kehadiran Bapak/Ibu/Sdr. pada:
        </p>
    </div>

    <div class="detail-undangan">
        <table>
            <tr>
                <td>Hari/Tanggal</td>
                <td>:</td>
                <td><?= h($data['hari_tanggal']) ?></td>
            </tr>
            <tr>
                <td>Pukul</td>
                <td>:</td>
                <td><?= date('H:i', strtotime(h($data['jam']))) ?></td>
            </tr>
            <tr>
                <td>Tempat</td>
                <td>:</td>
                <td><?= h($data['tempat_pelaksanaan']) ?></td>
            </tr>
            <?php if (!empty($data['agenda'])): ?>
            <tr>
                <td>Agenda</td>
                <td>:</td>
                <td><?= nl2br(h($data['agenda'])) ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="isi-surat">
        <p style="text-indent: 50px; margin: 20px 0;">
            Demikian undangan ini kami sampaikan, atas perhatian dan kehadirannya disampaikan terimakasih.
        </p>
        
        <p style="text-indent: 00px; font-style: italic; margin: 20px 0;">
            <em>Wassalamu'alaikum Wr. Wb.</em>
        </p>
    </div>

    <div class="ttd">
        <p><strong>Kepala Desa Campakoah,</strong></p>
        <?php if (!empty($ttd_kepala_desa_url)): ?>
            <img src="<?= h($ttd_kepala_desa_url) ?>" alt="TTD" class="ttd-image" onerror="this.style.display='none'; this.nextElementSibling.style.display='block';">
            <div class="ttd-space" style="display:none;"></div>
        <?php else: ?>
            <div class="ttd-space"></div>
        <?php endif; ?>
        <p><strong><u><?= h(strtoupper($nama_kepala_desa)) ?></u></strong></p>
    </div>

    <div class="tembusan">
        <p><strong>Tembusan disampaikan kepada:</strong></p>
        <?php if (!empty($data['tembusan_kepada'])): ?>
        <p style="margin-left: 20px;"><?= nl2br(h($data['tembusan_kepada'])) ?></p>
        <?php else: ?>
        <p style="margin-left: 20px;">-</p>
        <?php endif; ?>
    </div>
    
    </div><!-- end container -->
</body>
</html>
