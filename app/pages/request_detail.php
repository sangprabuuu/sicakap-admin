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
  <script>
    function cetakDanSelesai(id) {
      // Buka tab baru untuk print surat
      window.open('?p=print_surat&id=' + id, '_blank');
      
      // Tunggu 2 detik lalu submit form selesai
      setTimeout(function() {
        if (confirm('Apakah dokumen sudah dicetak? Tandai pengajuan sebagai selesai?')) {
          document.getElementById('formSelesai').submit();
        }
      }, 2000);
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
      <!-- Info Permintaan -->
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

      <!-- Info Pemohon -->
      <div class="detail-card">
        <div class="detail-header">
          <h3>Data Pemohon</h3>
        </div>
        
        <table class="detail-table">
          <tr>
            <td class="label">NIK</td>
            <td><strong><?= h($request['nik']) ?></strong></td>
          </tr>
          <tr>
            <td class="label">Nama Lengkap</td>
            <td><strong><?= h($request['nama']) ?></strong></td>
          </tr>
          <tr>
            <td class="label">Alamat</td>
            <td><?= h($request['alamat']) ?></td>
          </tr>
        </table>
      </div>

      <!-- Riwayat Status -->
      <?php if (!empty($riwayat_list)): ?>
      <div class="detail-card">
        <div class="detail-header">
          <h3>Riwayat Status</h3>
        </div>
        
        <div class="timeline">
          <?php foreach ($riwayat_list as $riwayat): ?>
          <div class="timeline-item">
            <div class="timeline-marker"></div>
            <div class="timeline-content">
              <div class="timeline-header">
                <?php
                $badge_class = match($riwayat['status']) {
                    'Selesai' => 'badge-success',
                    'Diproses' => 'badge-warning',
                    'Ditolak' => 'badge-danger',
                    'Diajukan' => 'badge-secondary',
                    default => 'badge-secondary'
                };
                ?>
                <span class="badge <?= $badge_class ?>"><?= h($riwayat['status']) ?></span>
                <span class="timeline-date"><?= date('d M Y, H:i', strtotime($riwayat['created_at'])) ?></span>
              </div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Actions -->
      <div class="detail-card">
        <div class="detail-header">
          <h3>Aksi</h3>
        </div>
        
        <div style="display: flex; gap: 10px; padding: 20px;">
          <?php if ($current_status === 'Diajukan'): ?>
            <form method="post" action="?p=request_approve" style="display: inline-block;">
              <input type="hidden" name="pengajuan_id" value="<?= h($id) ?>">
              <input type="hidden" name="action" value="proses">
              <button type="submit" class="btn btn-warning" onclick="return confirm('Proses pengajuan ini?')">
                🔄 Proses Pengajuan
              </button>
            </form>
          <?php endif; ?>
          
          <?php if ($current_status === 'Diproses'): ?>
            <form method="post" action="?p=request_approve" style="display: inline-block;" id="formSelesai">
              <input type="hidden" name="pengajuan_id" value="<?= h($id) ?>">
              <input type="hidden" name="action" value="selesai">
              <button type="button" class="btn btn-success" onclick="cetakDanSelesai('<?= h($id) ?>')">
                ✅ Tandai Selesai
              </button>
            </form>
          <?php endif; ?>
          
          <?php if ($current_status !== 'Ditolak' && $current_status !== 'Selesai'): ?>
            <form method="post" action="?p=request_approve" style="display: inline-block;">
              <input type="hidden" name="pengajuan_id" value="<?= h($id) ?>">
              <input type="hidden" name="action" value="tolak">
              <button type="submit" class="btn btn-danger" onclick="return confirm('Tolak pengajuan ini?')">
                ❌ Tolak Pengajuan
              </button>
            </form>
          <?php endif; ?>
          
          <a href="?p=requests" class="btn btn-light">← Kembali</a>
        </div>
      </div>
    </div>

  </section>
</div>
</body>
</html>
