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
            margin: 2cm;
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background: white;
        }
        
        .kop-surat {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 20px;
        }
        
        .kop-surat h2 {
            margin: 5px 0;
            font-size: 16pt;
            font-weight: bold;
        }
        
        .kop-surat p {
            margin: 2px 0;
            font-size: 11pt;
        }
        
        .nomor-surat {
            margin: 20px 0;
        }
        
        .nomor-surat table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .nomor-surat td {
            padding: 3px 5px;
            vertical-align: top;
        }
        
        .nomor-surat td:first-child {
            width: 120px;
        }
        
        .nomor-surat td:nth-child(2) {
            width: 20px;
        }
        
        .isi-surat {
            margin: 30px 0;
            text-align: justify;
        }
        
        .isi-surat p {
            margin: 10px 0;
        }
        
        .detail-undangan {
            margin: 20px 0 20px 40px;
        }
        
        .detail-undangan table td {
            padding: 5px 10px;
            vertical-align: top;
        }
        
        .detail-undangan td:first-child {
            width: 150px;
        }
        
        .detail-undangan td:nth-child(2) {
            width: 20px;
        }
        
        .ttd {
            margin-top: 40px;
            text-align: right;
        }
        
        .ttd p {
            margin: 5px 0;
        }
        
        .ttd-space {
            height: 80px;
        }
        
        .tembusan {
            margin-top: 30px;
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
        <h2>PEMERINTAH KABUPATEN FLORES TIMUR</h2>
        <h2>KECAMATAN SOLOR TIMUR</h2>
        <p>Jl. Trans Solor, Desa Kalike, Kec. Solor Timur</p>
        <p>Email: solortimur@florestimur.go.id | Telp: (0383) 123456</p>
    </div>

    <div class="nomor-surat">
        <table>
            <tr>
                <td>Nomor</td>
                <td>:</td>
                <td><?= h($data['nomor_surat']) ?></td>
            </tr>
            <tr>
                <td>Perihal</td>
                <td>:</td>
                <td><strong><?= h($data['perihal']) ?></strong></td>
            </tr>
        </table>
    </div>

    <div style="margin: 30px 0;">
        <p style="margin: 0;">Kepada Yth,</p>
        <p style="margin: 5px 0 0 0;"><strong><?= h($data['nama']) ?></strong></p>
        <p style="margin: 0;">di -</p>
        <p style="margin: 5px 0 0 40px;"><?= nl2br(h($data['alamat'])) ?></p>
    </div>

    <div class="isi-surat">
        <p style="text-indent: 40px;">
            Dengan hormat, sehubungan dengan akan dilaksanakannya kegiatan sebagaimana 
            perihal di atas, dengan ini kami mengundang Bapak/Ibu/Saudara/i untuk dapat 
            hadir pada:
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
                <td>Waktu</td>
                <td>:</td>
                <td><?= h($data['jam']) ?> WITA</td>
            </tr>
            <tr>
                <td>Tempat</td>
                <td>:</td>
                <td><?= h($data['tempat_pelaksanaan']) ?></td>
            </tr>
        </table>
    </div>

    <div class="isi-surat">
        <p style="text-indent: 40px;">
            Demikian undangan ini kami sampaikan. Atas perhatian dan kehadiran 
            Bapak/Ibu/Saudara/i, kami ucapkan terima kasih.
        </p>
    </div>

    <div class="ttd">
        <p><?= $tanggal_indo ?></p>
        <p><strong>CAMAT SOLOR TIMUR</strong></p>
        <div class="ttd-space"></div>
        <p><strong><u>Nama Camat</u></strong></p>
        <p>NIP. 19700101 199003 1 001</p>
    </div>

    <?php if (!empty($data['tembusan_kepada'])): ?>
    <div class="tembusan">
        <p><strong>Tembusan:</strong></p>
        <p><?= nl2br(h($data['tembusan_kepada'])) ?></p>
    </div>
    <?php endif; ?>
</body>
</html>
