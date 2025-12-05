<?php
$user = current_user();

$id = isset($_GET['id']) ? $_GET['id'] : '';

if (empty($id)) {
    flash_set('ID permintaan tidak valid');
    header('Location: ' . APP_URL . '/?p=requests');
    exit;
}

// Get request detail dari Supabase
$endpoint = "pengajuan_dokumen?id=eq.$id&select=*";
$result = supabase_request('GET', $endpoint);

if ($result['code'] !== 200 || empty($result['data'])) {
    flash_set('Permintaan tidak ditemukan');
    header('Location: ' . APP_URL . '/?p=requests');
    exit;
}

$request = $result['data'][0];

// Ambil status dari tabel riwayat
$status_endpoint = "riwayat?select=*&pengajuan_id=eq.$id&order=created_at.desc";
$status_result = supabase_request('GET', $status_endpoint);
$riwayat_list = ($status_result['code'] === 200 && !empty($status_result['data'])) ? $status_result['data'] : [];

// Debug - tampilkan jika parameter debug ada
if (isset($_GET['debug'])) {
    echo '<pre style="background:#f5f5f5;padding:20px;margin:20px;border:1px solid #ccc;">';
    echo "Pengajuan ID: $id\n\n";
    echo "Status Endpoint: $status_endpoint\n\n";
    echo "Status Result Code: " . $status_result['code'] . "\n\n";
    echo "Riwayat Data:\n";
    print_r($riwayat_list);
    echo '</pre>';
}

// Status terkini
$current_status = !empty($riwayat_list) ? $riwayat_list[0]['status'] : 'Diajukan';

$flash = flash_get();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Detail Permintaan - SiCakap</title>
  <link rel="stylesheet" href="<?= h(rtrim(APP_URL, '/')) ?>/assets/css/style.css">
  <style>
    /* Custom Modal Dialog Styles */
    .custom-confirm-overlay {
      position: fixed;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: rgba(0, 0, 0, 0.6);
      backdrop-filter: blur(4px);
      display: none;
      align-items: center;
      justify-content: center;
      z-index: 9999;
      animation: fadeIn 0.2s ease;
    }
    
    .custom-confirm-overlay.active {
      display: flex;
    }
    
    .custom-confirm-box {
      background: white;
      border-radius: 16px;
      box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
      max-width: 440px;
      width: 90%;
      padding: 0;
      animation: slideUp 0.3s ease;
      overflow: hidden;
    }
    
    .custom-confirm-header {
      padding: 24px 28px 20px;
      border-bottom: 1px solid #e5e7eb;
    }
    
    .custom-confirm-icon {
      width: 56px;
      height: 56px;
      margin: 0 auto 16px;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 28px;
    }
    
    .custom-confirm-icon.warning {
      background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
    }
    
    .custom-confirm-icon.success {
      background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
    }
    
    .custom-confirm-icon.danger {
      background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
    }
    
    .custom-confirm-title {
      font-size: 20px;
      font-weight: 600;
      color: #111827;
      text-align: center;
      margin: 0;
    }
    
    .custom-confirm-body {
      padding: 20px 28px 28px;
    }
    
    .custom-confirm-message {
      font-size: 15px;
      color: #6b7280;
      text-align: center;
      line-height: 1.6;
      margin: 0;
    }
    
    .custom-confirm-footer {
      padding: 16px 20px;
      background: #f9fafb;
      display: flex;
      gap: 12px;
      justify-content: flex-end;
    }
    
    .custom-confirm-btn {
      padding: 10px 24px;
      border-radius: 8px;
      border: none;
      font-size: 15px;
      font-weight: 500;
      cursor: pointer;
      transition: all 0.2s ease;
      min-width: 100px;
    }
    
    .custom-confirm-btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    }
    
    .custom-confirm-btn:active {
      transform: translateY(0);
    }
    
    .custom-confirm-btn.cancel {
      background: white;
      color: #374151;
      border: 1.5px solid #d1d5db;
    }
    
    .custom-confirm-btn.cancel:hover {
      background: #f9fafb;
      border-color: #9ca3af;
    }
    
    .custom-confirm-btn.ok {
      background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
      color: white;
      box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
    }
    
    .custom-confirm-btn.ok:hover {
      background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
      box-shadow: 0 4px 12px rgba(37, 99, 235, 0.4);
    }
    
    .custom-confirm-btn.ok.warning {
      background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
      box-shadow: 0 2px 8px rgba(245, 158, 11, 0.3);
    }
    
    .custom-confirm-btn.ok.warning:hover {
      background: linear-gradient(135deg, #d97706 0%, #b45309 100%);
      box-shadow: 0 4px 12px rgba(245, 158, 11, 0.4);
    }
    
    .custom-confirm-btn.ok.danger {
      background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
      box-shadow: 0 2px 8px rgba(239, 68, 68, 0.3);
    }
    
    .custom-confirm-btn.ok.danger:hover {
      background: linear-gradient(135deg, #dc2626 0%, #b91c1c 100%);
      box-shadow: 0 4px 12px rgba(239, 68, 68, 0.4);
    }
    
    .custom-confirm-btn.ok.success {
      background: linear-gradient(135deg, #10b981 0%, #059669 100%);
      box-shadow: 0 2px 8px rgba(16, 185, 129, 0.3);
    }
    
    .custom-confirm-btn.ok.success:hover {
      background: linear-gradient(135deg, #059669 0%, #047857 100%);
      box-shadow: 0 4px 12px rgba(16, 185, 129, 0.4);
    }
    
    @keyframes fadeIn {
      from { opacity: 0; }
      to { opacity: 1; }
    }
    
    @keyframes slideUp {
      from {
        opacity: 0;
        transform: translateY(20px) scale(0.95);
      }
      to {
        opacity: 1;
        transform: translateY(0) scale(1);
      }
    }
  </style>
  <script>
    // Custom Confirm Dialog
    function customConfirm(options) {
      return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className = 'custom-confirm-overlay';
        
        const iconMap = {
          warning: '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
          danger: '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>',
          success: '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>',
          info: '<svg width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>'
        };
        
        overlay.innerHTML = `
          <div class="custom-confirm-box">
            <div class="custom-confirm-header">
              <div class="custom-confirm-icon ${options.type || 'warning'}">
                ${iconMap[options.type] || '❓'}
              </div>
              <h3 class="custom-confirm-title">${options.title || 'Konfirmasi'}</h3>
            </div>
            <div class="custom-confirm-body">
              <p class="custom-confirm-message">${options.message || 'Apakah Anda yakin?'}</p>
            </div>
            <div class="custom-confirm-footer">
              <button class="custom-confirm-btn cancel">${options.cancelText || 'Batal'}</button>
              <button class="custom-confirm-btn ok ${options.type || ''}">${options.okText || 'OK'}</button>
            </div>
          </div>
        `;
        
        document.body.appendChild(overlay);
        setTimeout(() => overlay.classList.add('active'), 10);
        
        const cancelBtn = overlay.querySelector('.custom-confirm-btn.cancel');
        const okBtn = overlay.querySelector('.custom-confirm-btn.ok');
        
        function closeDialog(result) {
          overlay.classList.remove('active');
          setTimeout(() => {
            document.body.removeChild(overlay);
            resolve(result);
          }, 200);
        }
        
        cancelBtn.onclick = () => closeDialog(false);
        okBtn.onclick = () => closeDialog(true);
        overlay.onclick = (e) => {
          if (e.target === overlay) closeDialog(false);
        };
      });
    }
    
    function cetakDanSelesai(id) {
      customConfirm({
        type: 'success',
        title: 'Tandai Selesai',
        message: 'Tandai pengajuan ini sebagai selesai dan cetak surat?',
        okText: 'Ya, Selesai',
        cancelText: 'Batal'
      }).then(result => {
        if (result) {
          document.getElementById('formSelesai').submit();
          setTimeout(function() {
            window.open('?p=print_surat&id=' + id, '_blank');
          }, 500);
        }
      });
    }
    
    function prosesConfirm() {
      customConfirm({
        type: 'success',
        title: 'Proses Pengajuan',
        message: 'Apakah Anda yakin ingin memproses pengajuan ini?',
        okText: 'Ya, Proses',
        cancelText: 'Batal'
      }).then(result => {
        if (result) {
          document.getElementById('formProses').submit();
        }
      });
    }
    
    function tolakConfirm() {
      customConfirm({
        type: 'danger',
        title: 'Tolak Pengajuan',
        message: 'Apakah Anda yakin ingin menolak pengajuan ini? Anda akan diarahkan ke halaman untuk mengisi alasan penolakan.',
        okText: 'Ya, Tolak',
        cancelText: 'Batal'
      }).then(result => {
        if (result) {
          document.getElementById('formTolak').submit();
        }
      });
    }
  </script>
</head>
<body>
<?php include __DIR__ . '/_sidebar.php'; ?>
<div class="main">
  <header class="topbar">
    <div class="brand">SiCakap</div>
    <div class="user">
      <span>Hai, <?= h($user['name'] ?? $user['email'] ?? 'Administrator') ?></span>
      <a href="?p=logout" class="logout">Logout</a>
    </div>
  </header>

  <section class="content">
    <div class="page-header">
      <h1>Detail Permintaan Surat</h1>
      <a href="?p=requests" class="btn btn-light">← Kembali ke Daftar</a>
    </div>

    <?php if ($flash): ?>
    <div class="alert alert-success"><?= h($flash) ?></div>
    <?php endif; ?>

    <div class="detail-container">
      <!-- Form Komentar untuk Status Ditolak -->
      <?php if ($current_status === 'Ditolak'): ?>
      <div class="detail-card">
        <div class="detail-header">
          <h3>Tambah Komentar</h3>
        </div>
        
        <form method="post" action="?p=request_approve" style="padding: 20px;">
          <input type="hidden" name="pengajuan_id" value="<?= h($id) ?>">
          <input type="hidden" name="action" value="kirim_komentar">
          
          <div style="margin-bottom: 15px;">
            <label for="komentar" style="display: block; margin-bottom: 8px; font-weight: 500;">Alasan Penolakan:</label>
            <textarea 
              name="komentar" 
              id="komentar" 
              rows="4" 
              required
              placeholder="Masukkan alasan penolakan untuk pemohon..."
              style="width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 4px; font-size: 14px; font-family: inherit; resize: vertical;"
            ></textarea>
          </div>
          
          <button type="submit" class="btn btn-primary">
            Kirim Alasan Penolakan
          </button>
        </form>
      </div>
      <?php endif; ?>
      
      <!-- Info Permintaan -->
      <?php if ($current_status !== 'Ditolak'): ?>
      <div class="detail-card">
        <div class="detail-header">
          <h3>Informasi Permintaan</h3>
          <?php
          $badge_class = match($current_status) {
              'Selesai' => 'badge-success',
              'Diproses' => 'badge-warning',
              'Ditolak' => 'badge-danger',
              'Diajukan' => 'badge-secondary',
              default => 'badge-secondary'
          };
          ?>
          <span class="badge <?= $badge_class ?> badge-large"><?= h($current_status) ?></span>
        </div>
        
        <table class="detail-table">
          <tr>
            <td class="label">No. Pengajuan</td>
            <td>
              <form method="post" action="?p=request_approve" style="margin: 0;">
                <input type="hidden" name="pengajuan_id" value="<?= h($id) ?>">
                <input type="hidden" name="action" value="update_nomor">
                <div style="display: flex; gap: 10px; align-items: center;">
                  <input type="text" name="nomor_pengajuan" value="<?= h($request['nomor_pengajuan'] ?? '') ?>" 
                         placeholder="Masukkan No. Pengajuan" 
                         style="width: 300px; padding: 10px; border: 2px solid #ddd; border-radius: 4px; font-size: 14px; background: white; color: #333; font-weight: 500;">
                  <button type="submit" class="btn btn-sm btn-success">Simpan</button>
                </div>
              </form>
            </td>
          </tr>
          <tr>
            <td class="label">Tanggal Pengajuan</td>
            <td><?= date('d F Y, H:i', strtotime($request['created_at'])) ?></td>
          </tr>
          <tr>
            <td class="label">Jenis Dokumen</td>
            <td><strong><?= h($request['jenis_dokumen']) ?></strong></td>
          </tr>
          <tr>
            <td class="label">Tujuan Pembuatan</td>
            <td><?= h($request['tujuan_pembuatan'] ?? '-') ?></td>
          </tr>
          <?php if (!empty($request['file_url'])): ?>
          <tr>
            <td class="label">File Lampiran</td>
            <td>
              <a href="<?= h($request['file_url']) ?>" target="_blank" class="btn btn-sm btn-secondary">
                📄 <?= h($request['file_name'] ?? 'Download File') ?>
              </a>
            </td>
          </tr>
          <?php endif; ?>
        </table>
      </div>
      <?php endif; ?>

      <!-- Info Pemohon -->
      <div class="detail-card">
        <div class="detail-header">
          <h3>Data Pemohon</h3>
        </div>
        
        <table class="detail-table">
          <?php if ($request['jenis_dokumen'] === 'SKCK'): ?>
            <!-- Format untuk SKCK -->
            <tr>
              <td class="label">1. Nama</td>
              <td><strong><?= h($request['nama'] ?? $request['nama_ortu'] ?? '') ?></strong></td>
            </tr>
            <tr>
              <td class="label">2. Tempat dan tanggal lahir</td>
              <td><?= h($request['tempat_lahir'] ?? '-') ?>, <?= h($request['tanggal_lahir'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="label">3. Jenis Kelamin</td>
              <td><?= h($request['jenis_kelamin'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="label">4. Kewarganegaraan</td>
              <td><?= h($request['kewarganegaraan'] ?? 'Indonesia') ?></td>
            </tr>
            <tr>
              <td class="label">5. Agama</td>
              <td><?= h($request['agama'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="label">6. Nomor Induk Kependudukan</td>
              <td><strong><?= h($request['nik']) ?></strong></td>
            </tr>
            <tr>
              <td class="label">7. Status Perkawinan</td>
              <td><?= h($request['status_perkawinan'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="label">8. Alamat Tempat Tinggal</td>
                <td><?= h($request['alamat'] ?? '') ?></td>
              </tr>
            <tr>
              <td class="label">9. Keperluan</td>
              <td><?= h($request['tujuan_pembuatan'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="label">10. Berlaku Mulai - Sampai</td>
              <td>
                <form method="post" action="?p=request_approve" style="margin: 0;">
                  <input type="hidden" name="pengajuan_id" value="<?= h($id) ?>">
                  <input type="hidden" name="action" value="update_tanggal_kadaluarsa">
                  <div style="display: flex; gap: 10px; align-items: center;">
                    <input type="date" name="tanggal_mulai_skck" value="<?= h($request['tanggal_mulai_skck'] ?? '') ?>" 
                           placeholder="Tanggal Mulai"
                           style="padding: 8px; border: 2px solid #ddd; border-radius: 4px; font-size: 14px;">
                    <span style="font-weight: bold;">s/d</span>
                    <input type="date" name="tanggal_kadaluarsa_skck" value="<?= h($request['tanggal_kadaluarsa_skck'] ?? '') ?>" 
                           placeholder="Tanggal Kadaluarsa"
                           style="padding: 8px; border: 2px solid #ddd; border-radius: 4px; font-size: 14px;">
                    <button type="submit" class="btn btn-sm btn-success">Simpan</button>
                  </div>
                </form>
              </td>
            </tr>
          <?php elseif (stripos($request['jenis_dokumen'], 'pengantar') !== false || stripos($request['jenis_dokumen'], 'dibawah umur') !== false): ?>
            <!-- Format untuk Surat Pengantar / Dibawah Umur -->
            <tr>
              <td class="label">1. Nama</td>
              <td><strong><?= h($request['nama'] ?? $request['nama_ortu'] ?? '') ?></strong></td>
            </tr>
            <tr>
              <td class="label">2. Jenis Kelamin</td>
              <td><?= h($request['jenis_kelamin'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="label">3. Tempat Tanggal Lahir / Umur</td>
              <td><?= h($request['tempat_lahir'] ?? '-') ?>, <?= h($request['tanggal_lahir'] ?? '-') ?> (<?= h($request['umur'] ?? '-') ?> Tahun)</td>
            </tr>
            <tr>
              <td class="label">4. status</td>
              <td><?= h($request['status'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="label">5. Warganegara</td>
              <td><?= h($request['kewarganegaraan'] ?? 'Indonesia') ?></td>
            </tr>
            <tr>
              <td class="label">6. Keterangan</td>
              <td><?= h($request['keterangan'] ?? 'Menerangkan bahwa orang tersebut diatas akan mengajukan sidang ke Pengadilan Agama Purbalingga, karena orang tersebut hendak melakukan pernikahan di bawah umur.') ?></td>
            </tr>
          <?php elseif (stripos($request['jenis_dokumen'], 'usaha') !== false): ?>
            <!-- Format untuk Surat Keterangan Usaha -->
            <tr>
              <td class="label">1. Nama</td>
              <td><strong><?= h($request['nama'] ?? $request['nama_ortu'] ?? '') ?></strong></td>
            </tr>
            <tr>
              <td class="label">2. Jenis Kelamin</td>
              <td><?= h($request['jenis_kelamin'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="label">3. Tempat/tgl lahir</td>
              <td><?= h($request['tempat_lahir'] ?? '-') ?>, <?= h($request['tanggal_lahir'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="label">4. Kewarganegaraan</td>
              <td><?= h($request['kewarganegaraan'] ?? 'Indonesia') ?></td>
            </tr>
            <tr>
              <td class="label">5. Nomor Induk</td>
              <td><strong><?= h($request['nik']) ?></strong></td>
            </tr>
            <tr>
              <td class="label">6. Pekerjaan</td>
              <td><?= h($request['pekerjaan'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="label">7. Alamat Tempat tinggal</td>
                <td><?= h($request['alamat'] ?? '') ?></td>
            </tr>
            <tr>
              <td class="label" colspan="2"><strong>Orang tersebut di atas adalah benar-benar usaha di bidang:</strong></td>
            </tr>
            <tr>
              <td class="label">1. Jenis Usaha</td>
              <td><?= h($request['jenis_usaha'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="label">2. Tempat Usaha</td>
              <td><?= h($request['alamat_usaha'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="label">3. Lama Usaha</td>
              <td><?= h($request['lama_usaha'] ?? '-') ?></td>
            </tr>
          <?php elseif (stripos($request['jenis_dokumen'], 'sktm') !== false || stripos($request['jenis_dokumen'], 'tidak mampu') !== false): ?>
            <!-- Format untuk SKTM -->
            <tr>
              <td class="label">1. Nama</td>
              <td><strong><?= h($request['nama_ortu']) ?></strong></td>
            </tr>
            <tr>
              <td class="label">2. NIK</td>
              <td><strong><?= h($request['nik']) ?></strong></td>
            </tr>
            <tr>
              <td class="label">3. Tempat, Tanggal Lahir</td>
              <td><?= h($request['tempat_lahir_ortu'] ?? '-') ?>, <?= h($request['tanggal_lahir_ortu'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="label">4. Jenis Kelamin</td>
              <td><?= h($request['jk_ortu'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="label">5. Agama</td>
              <td><?= h($request['agama'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="label">6. Pekerjaan</td>
              <td><?= h($request['pekerjaan'] ?? '-') ?></td>
            </tr>
            
            <tr>
              <td>8. alamat </td>
                <td><?= h($request['alamat'] ) ?></td>
            </tr>
            <tr>
              <td class="label">9. Keperluan</td>
              <td><?= h($request['tujuan_pembuatan'] ?? '-') ?></td>
            </tr>
          <?php elseif (stripos($request['jenis_dokumen'], 'keterangan') !== false || stripos($request['jenis_dokumen'], 'domisili') !== false): ?>
            <!-- Format untuk Surat Keterangan / Domisili -->
            <tr>
              <td class="label">1. Nama</td>
              <td><strong><?= h($request['nama'] ?? $request['nama_ortu'] ?? '') ?></strong></td>
            </tr>
            <tr>
              <td class="label">2. Jenis Kelamin</td>
              <td><?= h($request['jenis_kelamin'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="label">3. Tempat Tanggal Lahir / Umur</td>
              <td><?= h($request['tempat_lahir'] ?? '-') ?> / <?= h($request['tanggal_lahir'] ?? '-') ?> (<?= h($request['umur'] ?? '-') ?> tahun)</td>
            </tr>
            <tr>
              <td class="label">4. Warganegara</td>
              <td><?= h($request['kewarganegaraan'] ?? 'Indonesia') ?></td>
            </tr>
            <tr>
              <td class="label">5. Agama</td>
              <td><?= h($request['agama'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="label">7. Pekerjaan</td>
              <td><?= h($request['pekerjaan'] ?? '-') ?></td>
            </tr>
            <tr>
              <td class="label">8. Alamat Tempat tinggal</td>
                <td><?= h($request['alamat'] ?? '') ?></td>
            </tr>
          <?php endif; ?>
        </table>
      </div>

      <!-- Form Data Pejabat & TTD -->
      <?php if ($current_status !== 'Ditolak'): ?>
      <div class="detail-card">
        <div class="detail-header">
          <h3>Data Pejabat & Tanda Tangan</h3>
        </div>
        
        <form method="post" action="?p=request_approve" enctype="multipart/form-data" style="padding: 20px;">
          <input type="hidden" name="pengajuan_id" value="<?= h($id) ?>">
          <input type="hidden" name="action" value="update_pejabat_ttd">
          
          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
              <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d5016;">Nama Kepala Desa</label>
              <input type="text" name="nama_kepaladesa" value="<?= h($request['nama_kepaladesa'] ?? '') ?>" 
                     placeholder="Masukkan nama kepala desa" 
                     style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 4px; font-size: 14px;">
            </div>
            
            <?php if ($request['jenis_dokumen'] === 'SKCK'): ?>
            <div>
              <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d5016;">Nama Camat</label>
              <input type="text" name="nama_camat" value="<?= h($request['nama_camat'] ?? '') ?>" 
                     placeholder="Masukkan nama camat" 
                     style="width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 4px; font-size: 14px;">
            </div>
            <?php endif; ?>
          </div>
          
          <div style="margin-bottom: 20px;">
            <label style="display: block; margin-bottom: 8px; font-weight: 600; color: #2d5016;">Upload Tanda Tangan Kepala Desa</label>
            <?php if (!empty($request['ttd_kepaladesa'])): ?>
            <div style="margin-bottom: 10px; padding: 10px; background: #f0fdf4; border: 1px solid #86efac; border-radius: 4px;">
              <span style="color: #16a34a; font-size: 13px;">✓ File tanda tangan sudah diupload</span>
              <a href="<?= h($request['ttd_kepaladesa']) ?>" target="_blank" style="margin-left: 10px; color: #2563eb;">Lihat File</a>
            </div>
            <?php endif; ?>
            <input type="file" name="ttd_kepaladesa" accept="image/png,image/jpeg,image/jpg" 
                   style="width: 100%; padding: 10px; border: 2px dashed #ddd; border-radius: 4px; font-size: 14px; cursor: pointer;">
            <small style="display: block; margin-top: 5px; color: #666; font-size: 12px;">Format: PNG, JPG, JPEG (Maks. 2MB). Gunakan background transparan untuk hasil terbaik.</small>
          </div>
          
          <button type="submit" class="btn btn-primary" style="margin-top: 10px;">
            Simpan Data Pejabat & TTD
          </button>
        </form>
      </div>
      <?php endif; ?>

      <!-- Actions -->
      <div class="detail-card">
        <div class="detail-header">
          <h3>Aksi</h3>
        </div>
        
        <div style="display: flex; gap: 10px; padding: 20px;">
          <?php if ($current_status === 'Diajukan'): ?>
            <form method="post" action="?p=request_approve" style="display: inline-block;" id="formProses">
              <input type="hidden" name="pengajuan_id" value="<?= h($id) ?>">
              <input type="hidden" name="action" value="proses">
              <button type="button" class="btn btn-warning" onclick="prosesConfirm()">
                Proses Pengajuan
              </button>
            </form>
          <?php endif; ?>
          
          <?php if ($current_status === 'Diproses'): ?>
            <form method="post" action="?p=request_approve" style="display: inline-block;" id="formSelesai">
              <input type="hidden" name="pengajuan_id" value="<?= h($id) ?>">
              <input type="hidden" name="action" value="selesai">
              <button type="button" class="btn btn-success" onclick="cetakDanSelesai('<?= h($id) ?>')">
                Tandai Selesai
              </button>
            </form>
          <?php endif; ?>
          
          <?php if ($current_status !== 'Ditolak' && $current_status !== 'Selesai'): ?>
            <form method="post" action="?p=request_approve" style="display: inline-block;" id="formTolak">
              <input type="hidden" name="pengajuan_id" value="<?= h($id) ?>">
              <input type="hidden" name="action" value="tolak">
              <button type="button" class="btn btn-danger" onclick="tolakConfirm()">
                Tolak Pengajuan
              </button>
            </form>
          <?php endif; ?>
          
          <?php if ($current_status === 'Selesai'): ?>
            <a href="?p=print_surat&id=<?= h($id) ?>" target="_blank" class="btn btn-primary">
              Download Surat
            </a>
          <?php endif; ?>
          
          <a href="?p=requests" class="btn btn-light">Kembali</a>
        </div>
      </div>
    </div>

  </section>
</div>
</body>
</html>
