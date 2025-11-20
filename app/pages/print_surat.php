<?php
$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    die('ID tidak valid');
}

// Get request detail dari Supabase
$endpoint = "pengajuan_dokumen?id=eq.$id&select=*";
$result = supabase_request('GET', $endpoint);

if ($result['code'] !== 200 || empty($result['data'])) {
    die('Data tidak ditemukan');
}

$request = $result['data'][0];

// Get nama lengkap instansi (dummy)
$nama_instansi = "PEMERINTAH KABUPATEN/KOTA";
$nama_dinas = "DINAS KEPENDUDUKAN DAN PENCATATAN SIPIL";
$alamat_dinas = "Jl. Contoh No. 123, Kota, Provinsi 12345";
$telepon_dinas = "Telp. (021) 1234567 | Email: disdukcapil@example.com";

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Surat <?= h($request['jenis_dokumen']) ?></title>
    <style>
        @media print {
            @page {
                size: A4;
                margin: 2cm;
            }
            body {
                margin: 0;
                padding: 0;
            }
            .no-print {
                display: none;
            }
        }
        
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 12pt;
            line-height: 1.6;
            color: #000;
            max-width: 21cm;
            margin: 0 auto;
            padding: 20px;
            background: white;
        }
        
        .kop-surat {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 10px;
            margin-bottom: 30px;
        }
        
        .kop-surat h2 {
            margin: 5px 0;
            font-size: 16pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .kop-surat h3 {
            margin: 5px 0;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .kop-surat p {
            margin: 3px 0;
            font-size: 10pt;
        }
        
        .nomor-surat {
            text-align: center;
            margin: 30px 0;
        }
        
        .nomor-surat h4 {
            margin: 5px 0;
            text-decoration: underline;
            font-size: 14pt;
            text-transform: uppercase;
        }
        
        .nomor-surat p {
            margin: 5px 0;
            font-size: 11pt;
        }
        
        .isi-surat {
            text-align: justify;
            margin: 30px 0;
            text-indent: 50px;
        }
        
        .data-pemohon {
            margin: 20px 0 20px 100px;
            line-height: 2;
        }
        
        .data-pemohon table {
            border-collapse: collapse;
        }
        
        .data-pemohon td {
            padding: 5px 10px;
            vertical-align: top;
        }
        
        .data-pemohon td:first-child {
            width: 200px;
        }
        
        .data-pemohon td:nth-child(2) {
            width: 20px;
        }
        
        .penutup {
            margin: 40px 0;
            text-align: justify;
            text-indent: 50px;
        }
        
        .ttd {
            margin-top: 50px;
            float: right;
            text-align: center;
            width: 250px;
        }
        
        .ttd-space {
            height: 80px;
        }
        
        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
        }
        
        .no-print {
            text-align: center;
            margin: 20px 0;
        }
        
        .btn {
            padding: 12px 30px;
            font-size: 14pt;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            margin: 5px;
        }
        
        .btn-primary {
            background: #2563eb;
            color: white;
        }
        
        .btn-secondary {
            background: #64748b;
            color: white;
        }
        
        .btn:hover {
            opacity: 0.9;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print / Save PDF</button>
        <a href="?p=request_detail&id=<?= h($id) ?>" class="btn btn-secondary">← Kembali</a>
    </div>

    <!-- KOP SURAT -->
    <div class="kop-surat">
        <h2><?= h($nama_instansi) ?></h2>
        <h3><?= h($nama_dinas) ?></h3>
        <p><?= h($alamat_dinas) ?></p>
        <p><?= h($telepon_dinas) ?></p>
    </div>

    <!-- NOMOR SURAT -->
    <div class="nomor-surat">
        <h4><?= h($request['jenis_dokumen']) ?></h4>
        <p>Nomor: <?= h($request['nomor_pengajuan'] ?? '___________________') ?></p>
    </div>

    <!-- ISI SURAT -->
    <div class="isi-surat">
        <p>Yang bertanda tangan di bawah ini, Kepala Dinas Kependudukan dan Pencatatan Sipil 
        <?= h($nama_instansi) ?>, dengan ini menerangkan bahwa:</p>
    </div>

    <!-- DATA PEMOHON -->
    <div class="data-pemohon">
        <table>
            <tr>
                <td>Nama Lengkap</td>
                <td>:</td>
                <td><strong><?= h($request['nama']) ?></strong></td>
            </tr>
            <tr>
                <td>NIK</td>
                <td>:</td>
                <td><strong><?= h($request['nik']) ?></strong></td>
            </tr>
            <tr>
                <td>Alamat</td>
                <td>:</td>
                <td><?= h($request['alamat']) ?></td>
            </tr>
            <tr>
                <td>Tujuan Pembuatan</td>
                <td>:</td>
                <td><?= h($request['tujuan_pembuatan'] ?? '-') ?></td>
            </tr>
        </table>
    </div>

    <!-- PENUTUP -->
    <div class="penutup">
        <p>Adalah benar warga yang tercatat dalam database kependudukan kami. 
        Surat keterangan ini dibuat untuk <?= h($request['tujuan_pembuatan'] ?? 'keperluan yang bersangkutan') ?> 
        dan dapat dipergunakan sebagaimana mestinya.</p>
        
        <p>Demikian surat keterangan ini dibuat dengan sebenarnya untuk dapat dipergunakan sebagaimana mestinya.</p>
    </div>

    <!-- TANDA TANGAN -->
    <div style="clear: both;"></div>
    <div class="ttd">
        <p>Dikeluarkan di: _____________</p>
        <p>Tanggal: <?= date('d F Y') ?></p>
        <br>
        <p>Kepala Dinas</p>
        <div class="ttd-space"></div>
        <p class="ttd-nama">(__________________)</p>
        <p>NIP. ___________________</p>
    </div>
    <div style="clear: both;"></div>

    <script>
        // Auto print saat halaman dibuka
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
