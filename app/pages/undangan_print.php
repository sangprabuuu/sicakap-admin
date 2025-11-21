<?php
$pdo = db();
$id = $_GET['id'] ?? '';

if (!$id) {
    header('Location: ?p=undangan');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM surat_undangan WHERE id = ?");
$stmt->execute([$id]);
$data = $stmt->fetch();

if (!$data) {
    flash_set('Data undangan tidak ditemukan');
    header('Location: ?p=undangan');
    exit;
}

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
            font-size: 10pt;
            line-height: 1.4;
            margin: 0;
            padding: 15px;
            background: white;
        }
        
        .kop-surat {
            border-bottom: 3px solid #000;
            padding-bottom: 8px;
            margin-bottom: 15px;
        }
        
        .kop-surat h2, .kop-surat h1 {
            font-weight: bold;
            line-height: 1.1;
        }
        
        .nomor-surat {
            margin: 15px 0;
        }
        
        .nomor-surat table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .nomor-surat td {
            padding: 2px 5px;
            vertical-align: top;
        }
        
        .nomor-surat td:first-child {
            width: 120px;
        }
        
        .nomor-surat td:nth-child(2) {
            width: 20px;
        }
        
        .isi-surat {
            margin: 15px 0;
            text-align: justify;
        }
        
        .isi-surat p {
            margin: 8px 0;
        }
        
        .detail-undangan {
            margin: 15px 0 15px 40px;
        }
        
        .detail-undangan table td {
            padding: 3px 10px;
            vertical-align: top;
        }
        
        .detail-undangan td:first-child {
            width: 150px;
        }
        
        .detail-undangan td:nth-child(2) {
            width: 20px;
        }
        
        .ttd {
            margin-top: 30px;
            text-align: right;
        }
        
        .ttd p {
            margin: 4px 0;
        }
        
        .ttd-space {
            height: 60px;
        }
        
        .tembusan {
            margin-top: 20px;
        }
        
        .tembusan p {
            margin: 4px 0;
        }
        
        @media print {
            body {
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
        
        .no-print {
            margin-bottom: 20px;
            text-align: center;
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
        }
        
        .btn-primary {
            background: #007bff;
            color: white;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print</button>
        <a href="?p=undangan" class="btn btn-secondary">← Kembali</a>
    </div>

    <div class="kop-surat">
        <table style="width: 100%; border: none;">
            <tr>
                <td style="width: 100px; vertical-align: top; padding-right: 15px;">
                    <img src="<?= rtrim(APP_URL, '/') ?>/assets/images/logo_kabupaten.jpg" alt="Logo" style="width: 90px; height: auto;">
                </td>
                <td style="text-align: center; vertical-align: middle;">
                    <h2 style="margin: 2px 0; font-size: 14pt;">PEMERINTAH KABUPATEN PURBALINGGA</h2>
                    <h2 style="margin: 2px 0; font-size: 14pt;">KECAMATAN MREBET</h2>
                    <h1 style="margin: 5px 0; font-size: 18pt; font-weight: bold;">DESA CAMPAKOAH</h1>
                    <p style="margin: 2px 0; font-size: 10pt;">Alamat: Jalan Desa Campakoah</p>
                    <div style="display: flex; justify-content: space-between; margin-top: 5px; font-size: 10pt;">
                        <span>Alamat: Jalan Desa Campakoah</span>
                        <span>Kode Pos: 53352</span>
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <div class="nomor-surat">
        <table style="width: 100%;">
            <tr>
                <td style="width: 80px;">Nomor</td>
                <td style="width: 10px;">:</td>
                <td><?= h($data['nomor_surat']) ?></td>
                <td style="text-align: right; padding-right: 0;">Campakoah, <?= $tanggal_indo ?></td>
            </tr>
            <tr>
                <td>Lamp.</td>
                <td>:</td>
                <td>-</td>
                <td></td>
            </tr>
            <tr>
                <td>Hal</td>
                <td>:</td>
                <td><?= h($data['hal'] ?? $data['perihal']) ?></td>
                <td></td>
            </tr>
            <tr>
                <td colspan="4" style="padding-top: 10px;"><strong>Kepada</strong></td>
            </tr>
            <tr>
                <td colspan="4">Yth.Bpk/Ibu/Sdr.</td>
            </tr>
            <tr>
                <td colspan="4" style="padding-left: 20px;"><?= h($data['nama']) ?></td>
            </tr>
            <tr>
                <td colspan="4" style="padding-left: 0;">di- <?= h($data['alamat']) ?></td>
            </tr>
        </table>
    </div>

    <div class="isi-surat">
        <p style="text-indent: 40px; font-style: italic; margin: 20px 0;">
            Bismillahirrahmanirrahim<br>
            Assalamu'alaikum Wr. Wb.
        </p>
        
        <p style="text-indent: 40px; margin: 20px 0;">
            Dengan hormat, sehubungan dengan pelaksanaan <?= h($data['hal'] ?? $data['perihal']) ?> dalam rangka <?= h($data['perihal']) ?>, maka kami mohon kehadiran Bapak/Ibu/Sdr. pada:
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
                <td><?= h($data['jam']) ?></td>
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
        <p style="text-indent: 40px; margin: 20px 0;">
            Demikian undangan ini kami sampaikan, atas perhatian dan kehadirannya disampaikan terimakasih.
        </p>
        
        <p style="text-indent: 40px; font-style: italic; margin: 20px 0;">
            Wassalamu'alaikum Wr. Wb.
        </p>
    </div>

    <div class="ttd">
        <p>Kepala Desa Campakoah,</p>
        <div class="ttd-space"></div>
        <p><strong><u>KUSTOMO</u></strong></p>
    </div>

    <div class="tembusan">
        <p><strong>Tembusan disampaikan kepada :</strong></p>
        <?php if (!empty($data['tembusan_kepada'])): ?>
        <p><?= nl2br(h($data['tembusan_kepada'])) ?></p>
        <?php else: ?>
        <p><strong>Tembusan kepada</strong></p>
        <?php endif; ?>
    </div>
</body>
</html>
