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

// Cek jenis dokumen
$is_skck = ($request['jenis_dokumen'] === 'SKCK');
$is_pengantar = (stripos($request['jenis_dokumen'], 'pengantar') !== false || stripos($request['jenis_dokumen'], 'dibawah umur') !== false);
$is_usaha = (stripos($request['jenis_dokumen'], 'usaha') !== false);
$is_sktm = (stripos($request['jenis_dokumen'], 'sktm') !== false || stripos($request['jenis_dokumen'], 'tidak mampu') !== false);
$is_keterangan = (stripos($request['jenis_dokumen'], 'keterangan') !== false || stripos($request['jenis_dokumen'], 'domisili') !== false);

// Get nama lengkap instansi (dummy)
$nama_instansi = "PEMERINTAH KABUPATEN/KOTA";
$nama_dinas = "DINAS KEPENDUDUKAN DAN PENCATATAN SIPIL";
$alamat_dinas = "Jl. Contoh No. 123, Kota, Provinsi 12345";
$telepon_dinas = "Telp. (021) 1234567 | Email: disdukcapil@example.com";

// Data khusus untuk SKCK, SKTM, Surat Keterangan, Surat Usaha, dan Surat Pengantar
if ($is_skck || $is_pengantar || $is_usaha || $is_sktm || $is_keterangan) {
    $nama_instansi = "PEMERINTAH KABUPATEN PURBALINGGA";
    $nama_kecamatan = "KECAMATAN MREBET";
    $nama_desa = "DESA CAMPAKOAH";
    $alamat_desa = "Jln Desa Campakoah RT 02 RW 03";
    $kode_pos = "53352";
    $kode_wilayah = "33.03.062014";
    
    // Ambil nama pejabat dari database
    $nama_kepala_desa = $request['nama_kepaladesa'] ?? '...........';
    $nama_camat = $request['nama_camat'] ?? '...........';
    $ttd_kepala_desa_url = $request['ttd_kepaladesa'] ?? '';
}

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
                margin: 0.8cm;
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
            font-size: 10pt;
            line-height: 1.3;
            color: #000;
            max-width: 21cm;
            margin: 0 auto;
            padding: 10px;
            background: white;
        }
        
        /* Logo untuk SKCK */
        .logo-container {
            position: absolute;
            left: 20px;
            top: 20px;
            width: 70px;
            height: 70px;
        }
        
        .logo-container img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }
        
        .kop-surat {
            text-align: center;
            border-bottom: 3px solid #000;
            padding-bottom: 8px;
            margin-bottom: 15px;
            position: relative;
        }
        
        .kop-surat.with-logo {
            padding-left: 90px;
        }
        
        .kop-surat h2 {
            margin: 3px 0;
            font-size: 14pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .kop-surat h3 {
            margin: 3px 0;
            font-size: 12pt;
            font-weight: bold;
            text-transform: uppercase;
        }
        
        .kop-surat p {
            margin: 2px 0;
            font-size: 9pt;
        }
        
        .nomor-surat {
            text-align: center;
            margin: 12px 0;
        }
        
        .nomor-surat h4 {
            margin: 3px 0;
            text-decoration: underline;
            font-size: 11pt;
            text-transform: uppercase;
        }
        
        .nomor-surat p {
            margin: 3px 0;
            font-size: 10pt;
        }
        
        .isi-surat {
            text-align: justify;
            margin: 12px 0;
            text-indent: 40px;
            font-size: 10pt;
        }
        
        .data-pemohon {
            margin: 10px 0 10px 40px;
            line-height: 1.4;
            font-size: 10pt;
        }
        
        .data-pemohon table {
            border-collapse: collapse;
        }
        
        .data-pemohon td {
            padding: 2px 8px;
            vertical-align: top;
        }
        
        .data-pemohon td:first-child {
            width: 220px;
        }
        
        .data-pemohon td:nth-child(2) {
            width: 15px;
        }
        
        .penutup {
            margin: 12px 0;
            text-align: justify;
            text-indent: 40px;
            font-size: 10pt;
        }
        
        .keterangan-tambahan {
            margin: 12px 0 12px 40px;
            font-size: 10pt;
            line-height: 1.4;
        }
        
        .ttd {
            margin-top: 20px;
            float: right;
            text-align: center;
            width: 230px;
            font-size: 10pt;
        }
        
        .ttd-kiri {
            margin-top: 20px;
            float: left;
            text-align: center;
            width: 230px;
            font-size: 10pt;
        }
        
        .ttd-space {
            height: 46px;
        }
        
        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
        }
        
        .ttd-image {
            max-width: 165px;
            max-height: 75px;
            margin: 5px auto;
            display: block;
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
        
        .garis-titik {
            border-bottom: 1px dotted #000;
            display: inline-block;
            min-width: 180px;
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" class="btn btn-primary">🖨️ Print / Save PDF</button>
        <a href="?p=request_detail&id=<?= h($id) ?>" class="btn btn-secondary">← Kembali</a>
    </div>

    <?php if ($is_skck): ?>
    <!-- TEMPLATE KHUSUS SKCK -->
    
    <!-- LOGO -->
    <div class="logo-container">
        <img src="<?= APP_URL ?>/assets/images/logo_kabupaten.jpg" alt="Logo" onerror="this.style.display='none'">
    </div>

    <!-- KOP SURAT SKCK -->
    <div class="kop-surat with-logo">
        <h2><?= h($nama_instansi) ?></h2>
        <h3><?= h($nama_kecamatan) ?></h3>
        <h3><?= h($nama_desa) ?></h3>
        <p>Alamat : <?= h($alamat_desa) ?></p>
        <p>Kode Pos : <?= h($kode_pos) ?></p>
    </div>

    <!-- NOMOR SURAT SKCK -->
    <div class="nomor-surat">
        <h4>PENGANTAR PERMOHONAN</h4>
        <h4>SURAT KETERANGAN CATATAN KEPOLISIAN ( SKCK )</h4>
        <p>Nomor : <?= h($request['nomor_pengajuan'] ?? '___________________') ?></p>
    </div>

    <!-- ISI SURAT SKCK -->
    <div class="isi-surat">
        <p>Yang bertanda tangan di bawah ini, Kepala Desa <?= h($nama_desa) ?> Kecamatan <?= h($nama_kecamatan) ?> Kabupaten <?= h($nama_instansi) ?>, dengan ini menerangkan bahwa:</p>
    </div>

    <!-- DATA PEMOHON SKCK -->
    <div class="data-pemohon">
        <table>
            <tr>
                <td>1. Nama</td>
                <td>:</td>
                <td><?= h($request['nama'] ?? $request['nama_ortu'] ?? '') ?></td>
            </tr>
            <tr>
                <td>2. Tempat dan tanggal lahir</td>
                <td>:</td>
                <td><?= h($request['tempat_lahir'] ?? '') ?>, <?= h($request['tanggal_lahir'] ?? '') ?></td>
            </tr>
            <tr>
                <td>3. Jenis Kelamin</td>
                <td>:</td>
                <td><?= h($request['jenis_kelamin'] ?? '') ?></td>
            </tr>
            <tr>
                <td>4. Kewarganegaraan</td>
                <td>:</td>
                <td><?= h($request['kewarganegaraan'] ?? 'Indonesia') ?></td>
            </tr>
            <tr>
                <td>5. Agama</td>
                <td>:</td>
                <td><?= h($request['agama'] ?? '') ?></td>
            </tr>
            <tr>
                <td>6. Nomor Induk Kependudukan</td>
                <td>:</td>
                <td><?= h($request['nik'] ?? '') ?></td>
            </tr>
            <tr>
                <td>7. Status Perkawinan</td>
                <td>:</td>
                <td><?= h($request['status_perkawinan'] ?? '') ?></td>
            </tr>
            <tr>
                <td>8. tempat tinggal</td>
                <td>:</td>
                <td><?= h($request['alamat'] ?? '') ?></td>
            </tr>
            <tr>
                <td>9. Keperluan</td>
                <td>:</td>
                <td>Membuat Surat Keterangan Catatan Kepolisian ( SKCK ) ke POLSEK MREBET untuk melengkapi persyaratan</td>
            </tr>
            <tr>
                <td style="vertical-align: top;">10. Berlaku mulai</td>
                <td style="vertical-align: top;">:</td>
                <td>
                    <?php if (!empty($request['tanggal_mulai_skck'])): ?>
                        <?= date('d-m-Y', strtotime($request['tanggal_mulai_skck'])) ?>
                    <?php else: ?>
                        _______________
                    <?php endif; ?>
                    s/d 
                    <?php if (!empty($request['tanggal_kadaluarsa_skck'])): ?>
                        <?= date('d-m-Y', strtotime($request['tanggal_kadaluarsa_skck'])) ?>
                    <?php else: ?>
                        _______________
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td style="vertical-align: top;">11. Keterangan lain-lain</td>
                <td style="vertical-align: top;">:</td>
                <td>Orang tersebut benar-benar warga desa kami, dan menurut pengetahuan kami tidak mempunyai catatan khusus (berkelakuan baik).</td>
            </tr>
        </table>
    </div>


    <!-- PENUTUP SKCK -->
    <div class="penutup">
        <p>Demikian Surat Keterangan / Pengantar ini kami buat untuk mengiklankan maklum bagi yang berkepentingan.</p>
    </div>

    <!-- TANDA TANGAN SKCK -->
    <div style="clear: both;"></div>
    <div class="ttd-kiri">
        <p>Mengetahui,</p>
        <p><strong>CAMAT MREBET</strong></p>
        <div class="ttd-space"></div>
        <div class="ttd-space"></div>
        <p style="min-width: 200px;"><?= h($nama_camat) ?></p>
    </div>
    
    <div class="ttd">
        <p><?= h($nama_desa) ?>,</p>
        <p>an. <strong>KEPALA DESA <?= strtoupper(h($nama_desa)) ?></strong></p>
        <p></p>
        <?php if (!empty($ttd_kepala_desa_url)): ?>
        <img src="<?= h($ttd_kepala_desa_url) ?>" alt="TTD Kepala Desa" class="ttd-image" onerror="this.style.display='none'; console.error('Gagal load gambar TTD:', '<?= h($ttd_kepala_desa_url) ?>');">
        <!-- Debug: <?= h($ttd_kepala_desa_url) ?> -->
        <?php else: ?>
        <div class="ttd-space"></div>
        <?php endif; ?>
        <p class="ttd-nama"><?= h($nama_kepala_desa) ?></p>
    </div>
    <div style="clear: both;"></div>

    <?php elseif ($is_pengantar): ?>
    <!-- TEMPLATE SURAT PENGANTAR -->
    
    <!-- LOGO -->
    <div class="logo-container">
        <img src="<?= APP_URL ?>/assets/images/logo_kabupaten.jpg" alt="Logo" onerror="this.style.display='none'">
    </div>

    <!-- KOP SURAT PENGANTAR -->
    <div class="kop-surat with-logo">
        <h2><?= h($nama_instansi) ?></h2>
        <h3><?= h($nama_kecamatan) ?></h3>
        <h3><?= h($nama_desa) ?></h3>
        <p>Kode Wilayah : 33.03.082014</p>
        <p>Kode Pos : <?= h($kode_pos) ?></p>
    </div>

    <!-- NOMOR SURAT PENGANTAR -->
    <div class="nomor-surat">
        <h4><u>SURAT PENGANTAR</u></h4>
        <p>Nomor : <?= h($request['nomor_pengajuan'] ?? '_____ / _____ / _____') ?></p>
    </div>

    <!-- ISI SURAT PENGANTAR -->
    <div class="isi-surat">
        <p>Yang bertanda tangan dibawah ini Kepala Desa <?= h($nama_desa) ?> Kecamatan <?= h($nama_kecamatan) ?> Kabupaten Purbalingga, dengan ini menerangkan bahwa :</p>
    </div>

    <!-- DATA PEMOHON PENGANTAR -->
    <div class="data-pemohon">
        <table>
            <tr>
                <td>1. Nama</td>
                <td>:</td>
                <td><?= h($request['nama'] ?? $request['nama_ortu'] ?? '') ?></td>
            </tr>
            <tr>
                <td>2. Jenis Kelamin</td>
                <td>:</td>
                <td><?= h($request['jenis_kelamin'] ?? '') ?></td>
            </tr>
            <tr>
                <td>3. Tempat Tanggal Lahir / Umur</td>
                <td>:</td>
                <td><?= h($request['tempat_lahir'] ?? '') ?>, <?= h($request['tanggal_lahir'] ?? '') ?> ( <?= h($request['umur'] ?? '') ?> Tahun )</td>
            </tr>
            <tr>
                <td>4. Warganegara</td>
                <td>:</td>
                <td><?= h($request['kewarganegaraan'] ?? 'Indonesia') ?></td>
            </tr>      
            <tr>
                <td>5. status pendiikan</td>
                <td>:</td>
                <td><?= h($request['status'] ?? '') ?></td>
            </tr>
            <tr>
                <td>6. alamat tempat tinggal</td>
                <td>:</td>
                <td><?= h($request['alamat'] ?? '') ?></td>
            </tr>
            <tr>
                <td style="vertical-align: top;">7. Keterangan</td>
                <td style="vertical-align: top;">:</td>
                <td><?= h($request['keterangan'] ?? 'Menerangkan bahwa orang tersebut diatas akan mengajukan sidang ke Pengadilan Agama Purbalingga,karena orang tersebut hendak melakukan pernikahan di bawah umur.') ?></td>
            </tr>
        </table>
    </div>

    <!-- PENUTUP PENGANTAR -->
    <div class="penutup">
        <p>Demikian Surat Keterangan / Pengantar ini kami buat untuk menjadikan maklum bagi yang berkepentingan.</p>
    </div>

    <!-- TANDA TANGAN PENGANTAR -->
    <div style="clear: both;"></div>
    <div class="ttd">
        <p><?= h($nama_desa) ?>,</p>
        <p><strong>KEPALA DESA <?= strtoupper(h($nama_desa)) ?></strong></p>
        <?php if (!empty($ttd_kepala_desa_url)): ?>
        <img src="<?= h($ttd_kepala_desa_url) ?>" alt="TTD" class="ttd-image">
        <?php else: ?>
        <div class="ttd-space"></div>
        <?php endif; ?>
        <p class="ttd-nama"><u><?= h($nama_kepala_desa) ?></u></p>
    </div>
    <div style="clear: both;"></div>

    <?php elseif ($is_usaha): ?>
    <!-- TEMPLATE SURAT KETERANGAN USAHA -->
    
    <!-- LOGO -->
    <div class="logo-container">
        <img src="<?= APP_URL ?>/assets/images/logo_kabupaten.jpg" alt="Logo" onerror="this.style.display='none'">
    </div>

    <!-- KOP SURAT USAHA -->
    <div class="kop-surat with-logo">
        <h2><?= h($nama_instansi) ?></h2>
        <h3><?= h($nama_kecamatan) ?></h3>
        <h3><?= h($nama_desa) ?></h3>
        <p><?= h($alamat_desa) ?> RT.002 RW.003</p>
        <p>Kode Pos Mrebet : <?= h($kode_pos) ?></p>
    </div>

    <!-- NOMOR SURAT USAHA -->
    <div class="nomor-surat">
        <h4><u>SURAT KETERANGAN USAHA</u></h4>
        <p>Nomor : <?= h($request['nomor_pengajuan'] ?? '') ?></p>
    </div>

    <!-- ISI SURAT USAHA -->
    <div class="isi-surat">
        <p>Yang bertandatangan di bawah ini, Kepala Desa <?= h($nama_desa) ?> Kecamatan <?= h($nama_kecamatan) ?> Kabupaten Purbalingga menerangkan bahwa :</p>
    </div>

    <!-- DATA PEMOHON USAHA -->
    <div class="data-pemohon">
        <table>
            <tr>
                <td>1. Nama</td>
                <td>:</td>
                <td><?= h($request['nama'] ?? $request['nama_ortu'] ?? '') ?></td>
            </tr>
            <tr>
                <td>2. Jenis Kelamin</td>
                <td>:</td>
                <td><?= h($request['jenis_kelamin'] ?? '') ?></td>
            </tr>
            <tr>
                <td>3. Tempat/tgl lahir</td>
                <td>:</td>
                <td><?= h($request['tempat_lahir'] ?? '') ?>, <?= h($request['tanggal_lahir'] ?? '') ?></td>
            </tr>
            <tr>
                <td>4. Kewarganegaraan</td>
                <td>:</td>
                <td><?= h($request['kewarganegaraan'] ?? 'Indonesia') ?></td>
            </tr>
            
            <tr>
                <td>5. Nomor Induk</td>
                <td>:</td>
                <td><?= h($request['nik'] ?? '') ?></td>
            </tr>
            <tr>
                <td>6. Pekerjaan</td>
                <td>:</td>
                <td><?= h($request['pekerjaan'] ?? '') ?></td>
            </tr>
            <tr>
                <td>7. alamat tempat tinggal</td>
                <td>:</td>
                <td><?= h($request['alamat'] ?? '') ?></td>
            </tr>
        </table>
    </div>

    <!-- TEKS TENGAH USAHA -->
    <div class="isi-surat" style="margin-top: 15px;">
        <p>Orang tersebut di atas adalah benar-benar usaha di bidang :</p>
    </div>

    <!-- DATA USAHA -->
    <div class="data-pemohon">
        <table>
            <tr>
                <td>1. Jenis Usaha</td>
                <td>:</td>
                <td><?= h($request['jenis_usaha'] ?? '') ?></td>
            </tr>
            <tr>
                <td>2. Tempat Usaha</td>
                <td>:</td>
                <td><?= h($request['alamat_usaha'] ?? '') ?></td>
            </tr>
            <tr>
                <td>3. Lama Usaha</td>
                <td>:</td>
                <td><?= h($request['lama_usaha'] ?? '') ?></td>
            </tr>
        </table>
    </div>

    <!-- PENUTUP USAHA -->
    <div class="penutup">
        <p>Demikian <em>Surat Keterangan Usaha</em> ini kami buat agar dapat dipergunakan sebagaimana mestinya dan untuk menjadikan periksa bagi yang berkepentingan.</p>
    </div>

    <!-- TANDA TANGAN USAHA -->
    <div style="clear: both;"></div>
    <div class="ttd">
        <p><?= h($nama_desa) ?>,</p>
        <p>an. KEPALA DESA <?= strtoupper(h($nama_desa)) ?></p>
        <p><em></em></p>
        <?php if (!empty($ttd_kepala_desa_url)): ?>
        <img src="<?= h($ttd_kepala_desa_url) ?>" alt="TTD" class="ttd-image">
        <?php else: ?>
        <div class="ttd-space"></div>
        <?php endif; ?>
        <p class="ttd-nama"><?= h($nama_kepala_desa) ?></p>
    </div>
    <div style="clear: both;"></div>

    <?php elseif ($is_sktm): ?>
    <!-- TEMPLATE SKTM -->
    
    <!-- LOGO -->
    <div class="logo-container">
        <img src="<?= APP_URL ?>/assets/images/logo_kabupaten.jpg" alt="Logo" onerror="this.style.display='none'">
    </div>

    <!-- KOP SURAT SKTM -->
    <div class="kop-surat with-logo">
        <h2><?= h($nama_instansi) ?></h2>
        <h3><?= h($nama_kecamatan) ?></h3>
        <h3><?= h($nama_desa) ?></h3>
        <p><?= h($alamat_desa) ?></p>
        <p>Kode Pos : <?= h($kode_pos) ?></p>
    </div>

    <!-- NOMOR SURAT SKTM -->
    <div class="nomor-surat">
        <h4><u>SURAT KETERANGAN KELUARGA TIDAK MAMPU</u></h4>
        <p>Nomor : <?= h($request['nomor_pengajuan'] ?? '') ?></p>
    </div>

    <!-- ISI SURAT SKTM -->
    <div class="isi-surat">
        <p>Yang bertanda tangan dibawah ini Kepala Desa <?= h($nama_desa) ?> Kecamatan <?= h($nama_kecamatan) ?> Kab. Purbalingga, dengan ini menerangkan bahwa :</p>
    </div>

    <!-- DATA ANAK SKTM -->
    <div class="data-pemohon">
        <table>
            <tr>
                <td>1. Nama</td>
                <td>:</td>
                <td><?= h($request['nama_anak'] ?? '') ?></td>
            </tr>
            <tr>
                <td>2. Jenis Kelamin</td>
                <td>:</td>
                <td><?= h($request['jk_anak'] ?? '') ?></td>
            </tr>
            <tr>
                <td>3. Tempat Tanggal Lahir</td>
                <td>:</td>
                <td><?= h($request['tempat_lahir_anak']  ?? '') ?>, <?= h($request['tanggal_lahir_anak'] ?? '') ?></td>
            </tr>
            <tr>
                <td>4. Warganegara</td>
                <td>:</td>
                <td><?= h($request['kewarganegaraan_anak'] ?? $request['kewarganegaraan'] ?? 'Indonesia') ?></td>
            </tr>
            <tr>
                <td>5. Agama</td>
                <td>:</td>
                <td><?= h($request['agama_anak'] ?? $request['agama'] ?? '') ?></td>
            </tr>
            <tr>
                <td>6. alamat tempat tinggal</td>
                <td>:</td>
                <td><?= h($request['alamat'] ?? '') ?></td>
            </tr>
        </table>
    </div>

    <!-- TEKS TENGAH SKTM -->
    <div class="isi-surat" style="margin-top: 15px;">
        <p>Orang tersebut diatas adalah benar-benar anak dari Keluarga :</p>
    </div>

    <!-- DATA ORANG TUA/KELUARGA SKTM -->
    <div class="data-pemohon">
        <table>
            <tr>
                <td>1. Nama</td>
                <td>:</td>
                <td><?= h($request['nama_ortu'] ?? '') ?></td>
            </tr>
            <tr>
                <td>2. Jenis Kelamin</td>
                <td>:</td>
                <td><?= h($request['jk_ortu'] ?? '') ?></td>
            </tr>
            <tr>
                <td>3. Tempat Tanggal Lahir</td>
                <td>:</td>
                <td><?= h($request['tempat_lahir_ortu'] ?? '') ?>, <?= h($request['tanggal_lahir_ortu'] ?? '') ?></td>
            </tr>
            <tr>
                <td>4. Warganegara</td>
                <td>:</td>
                <td><?= h($request['kewarganegaraan'] ?? 'Indonesia') ?></td>
            </tr>
            <tr>
                <td>5. Agama</td>
                <td>:</td>
                <td><?= h($request['agama'] ?? '') ?></td>
            </tr>
            <tr>
                <td>6. Status dalam Keluarga</td>
                <td>:</td>
                <td><?= h($request['status_keluarga'] ?? '') ?></td>
            </tr>
            <tr>
                <td>7. Pekerjaan</td>
                <td>:</td>
                <td><?= h($request['pekerjaan'] ?? '') ?></td>
            </tr>
            <tr>
               <td>8. alamat tempat tinggal</td>
                <td>:</td>
                <td><?= h($request['alamat_ortu'] ?? '') ?></td>
            </tr>
        </table>
    </div>

    <!-- PENUTUP SKTM -->
    <div class="penutup">
        <p>Menerangkan bahwa Keluarga tersebut diatas adalah termasuk kategori Keluarga Tidak Mampu, dan anaknya sedang sekolah di <?= h($request['nama_sekolah'] ?? '___________') ?>.</p>
        
        <p>Demikian Surat Keterangan Keluarga Tidak Mampu ini kami buat dengan sebenar-benarnya dan untuk digunakan sebagaimana mestinya.</p>
    </div>

    <!-- TANDA TANGAN SKTM -->
    <div style="clear: both;"></div>
    <div class="ttd">
        <p><?= h($nama_desa) ?>,</p>
        <p>an. KEPALA DESA <?= strtoupper(h($nama_desa)) ?></p>
        <p></p>
        <?php if (!empty($ttd_kepala_desa_url)): ?>
        <img src="<?= h($ttd_kepala_desa_url) ?>" alt="TTD" class="ttd-image">
        <?php else: ?>
        <div class="ttd-space"></div>
        <?php endif; ?>
        <p class="ttd-nama"><?= h($nama_kepala_desa) ?></p>
    </div>
    <div style="clear: both;"></div>

    <?php elseif ($is_keterangan): ?>
    <!-- TEMPLATE SURAT KETERANGAN -->
    
    <!-- LOGO -->
    <div class="logo-container">
        <img src="<?= APP_URL ?>/assets/images/logo_kabupaten.jpg" alt="Logo" onerror="this.style.display='none'">
    </div>

    <!-- KOP SURAT KETERANGAN -->
    <div class="kop-surat with-logo">
        <h2><?= h($nama_instansi) ?></h2>
        <h3><?= h($nama_kecamatan) ?></h3>
        <h3><?= h($nama_desa) ?></h3>
        <p>Kode Wilayah : <?= h($kode_wilayah) ?></p>
        <p>Kode Pos : <?= h($kode_pos) ?></p>
    </div>

    <!-- NOMOR SURAT KETERANGAN -->
    <div class="nomor-surat">
        <h4><u>SURAT KETERANGAN</u></h4>
        <p>Nomor : <?= h($request['nomor_pengajuan'] ?? '_____ / _____ / _____') ?></p>
    </div>

    <!-- ISI SURAT KETERANGAN -->
    <div class="isi-surat">
        <p>Yang bertanda tangan dibawah ini Kepala Desa <?= h($nama_desa) ?> Kecamatan <?= h($nama_kecamatan) ?> Kabupaten <?= h($nama_instansi) ?>, menerangkan dengan sebenar-benarnya :</p>
    </div>

    <!-- DATA PEMOHON KETERANGAN -->
    <div class="data-pemohon">
        <table>
            <tr>
                <td>1. Nama</td>
                <td>:</td>
                <td><?= h($request['nama'] ?? $request['nama_ortu'] ?? '') ?></td>
            </tr>
            <tr>
                <td>2. Jenis Kelamin</td>
                <td>:</td>
                <td><?= h($request['jenis_kelamin'] ?? '') ?></td>
            </tr>
            <tr>
                <td>3. Tempat Tanggal Lahir / Umur</td>
                <td>:</td>
                <td><?= h($request['tempat_lahir'] ?? '') ?> / <?= h($request['tanggal_lahir'] ?? '') ?> ( <?= h($request['umur'] ?? '') ?> tahun )</td>
            </tr>
            <tr>
                <td>4. Warganegara</td>
                <td>:</td>
                <td><?= h($request['kewarganegaraan'] ?? 'Indonesia') ?></td>
            </tr>
            <tr>
                <td>5. Agama</td>
                <td>:</td>
                <td><?= h($request['agama'] ?? '') ?></td>
            </tr>
            <tr>
                <td>7. Pekerjaan</td>
                <td>:</td>
                <td><?= h($request['pekerjaan'] ?? '') ?></td>
            </tr>
            <tr>
                <td>8. alamat tempat tinggal</td>
                <td>:</td>
                <td><?= h($request['alamat'] ?? '') ?></td>
            </tr>
        </table>
    </div>

    <!-- PENUTUP KETERANGAN -->
    <div class="penutup">
        <p>Orang tersebut berdomisili di desa kami tepatnya di <?= h($request['alamat'] ?? '') ?>, namun sejak tanggal  <?= h($request['tgl_pergi'] ?? '') ?> sampai dengan sekarang telah pergi tanpa sepengetahuan Pemerintah Desa dan tidak diketahui keberadaannya / tempat tinggalnya.</p>
        
        <p>Demikian Surat Keterangan ini kami buat dengan sebenar-benarnya dan untuk dapat digunakan sebagaimana mestinya.</p>
    </div>

    <!-- TANDA TANGAN KETERANGAN -->
    <div style="clear: both;"></div>
    <div class="ttd">
        <p><?= h($nama_desa) ?>,</p>
        <p>an. <strong>KEPALA DESA <?= strtoupper(h($nama_desa)) ?></strong></p>
        <p></p>
        <?php if (!empty($ttd_kepala_desa_url)): ?>
        <img src="<?= h($ttd_kepala_desa_url) ?>" alt="TTD" class="ttd-image">
        <?php else: ?>
        <div class="ttd-space"></div>
        <?php endif; ?>
        <p class="ttd-nama"><?= h($nama_kepala_desa) ?></p>
    </div>
    <div style="clear: both;"></div>

    <?php endif; ?>

    <script>
        // Auto print saat halaman dibuka
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
