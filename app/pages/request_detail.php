<?php
$pdo = db();
$user = current_user();

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id <= 0) {
    flash_set('ID permintaan tidak valid');
    header('Location: ?p=requests');
    exit;
}

// Get request detail
$sql = "SELECT lr.*, lt.name as template_name, lt.code as template_code,
               r.name as resident_full_name, r.alamat, r.rt, r.rw, r.desa,
               r.tempat_lahir, r.tanggal_lahir, r.jenis_kelamin, r.agama,
               r.pekerjaan, r.status_perkawinan, r.kewarganegaraan
        FROM letter_requests lr
        LEFT JOIN letter_templates lt ON lr.template_id = lt.id
        LEFT JOIN residents r ON lr.resident_id = r.id
        WHERE lr.id = :id";

$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id]);
$request = $stmt->fetch();

if (!$request) {
    flash_set('Permintaan tidak ditemukan');
    header('Location: ?p=requests');
    exit;
}

$flash = flash_get();
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Detail Permintaan - SiCakap</title>
  <link rel="stylesheet" href="<?= h(rtrim(APP_URL, '/')) ?>/assets/css/style.css">
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
          $status_labels = [
              'pending' => 'Pending',
              'verifikasi' => 'Verifikasi',
              'approved' => 'Disetujui',
              'rejected' => 'Ditolak',
              'processing' => 'Diproses',
              'finished' => 'Selesai'
          ];
          $status = $request['status'];
          $label = $status_labels[$status] ?? $status;
          ?>
          <span class="badge badge-<?= h($status) ?> badge-large"><?= h($label) ?></span>
        </div>
        
        <table class="detail-table">
          <tr>
            <td class="label">No. Request</td>
            <td>
              <form method="post" action="?p=request_approve" style="margin: 0;">
                <input type="hidden" name="id" value="<?= $id ?>">
                <input type="hidden" name="action" value="update_no_request">
                <div style="display: flex; gap: 10px; align-items: center;">
                  <input type="text" name="no_request" value="<?= h($request['no_request'] ?? '') ?>" 
                         placeholder="Masukkan No. Request" required
                         style="width: 300px; padding: 10px; border: 2px solid #ddd; border-radius: 4px; font-size: 14px; background: white; color: #333; font-weight: 500;">
                  <button type="submit" class="btn btn-sm btn-success">Simpan</button>
                </div>
              </form>
            </td>
          </tr>
          <tr>
            <td class="label">Tanggal Permintaan</td>
            <td>
              <?php
              if ($request['requested_at']) {
                  echo date('d F Y, H:i', strtotime($request['requested_at']));
              } else {
                  echo date('d F Y', strtotime($request['created_at']));
              }
              ?>
            </td>
          </tr>
          <tr>
            <td class="label">Jenis Surat</td>
            <td><strong><?= h($request['template_name'] ?? 'Template #' . $request['template_id']) ?></strong></td>
          </tr>
          <?php if ($request['notes']): ?>
          <tr>
            <td class="label">Catatan</td>
            <td><?= nl2br(h($request['notes'])) ?></td>
          </tr>
          <?php endif; ?>
          <?php if ($request['attachments']): ?>
          <tr>
            <td class="label">Lampiran</td>
            <td>
              <?php
              $attachments = explode(',', $request['attachments']);
              foreach ($attachments as $file) {
                  echo '<a href="' . h(rtrim(APP_URL, '/')) . '/app/uploads/' . h(trim($file)) . '" target="_blank" class="attachment-link">' . h(trim($file)) . '</a><br>';
              }
              ?>
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
            <td><strong><?= h($request['resident_nik']) ?></strong></td>
          </tr>
          <tr>
            <td class="label">Nama Lengkap</td>
            <td><strong><?= h($request['resident_name']) ?></strong></td>
          </tr>
          <?php if ($request['resident_full_name']): ?>
          <tr>
            <td class="label">Tempat/Tanggal Lahir</td>
            <td>
              <?php
              $ttl = [];
              if ($request['tempat_lahir']) $ttl[] = h($request['tempat_lahir']);
              if ($request['tanggal_lahir']) $ttl[] = date('d-m-Y', strtotime($request['tanggal_lahir']));
              echo implode(', ', $ttl);
              ?>
            </td>
          </tr>
          <tr>
            <td class="label">Jenis Kelamin</td>
            <td><?= h($request['jenis_kelamin'] ?? '-') ?></td>
          </tr>
          <tr>
            <td class="label">Agama</td>
            <td><?= h($request['agama'] ?? '-') ?></td>
          </tr>
          <tr>
            <td class="label">Pekerjaan</td>
            <td><?= h($request['pekerjaan'] ?? '-') ?></td>
          </tr>
          <tr>
            <td class="label">Status Perkawinan</td>
            <td><?= h($request['status_perkawinan'] ?? '-') ?></td>
          </tr>
          <tr>
            <td class="label">Alamat</td>
            <td>
              <?= h($request['alamat'] ?? '-') ?>
              <?php
              $rtrw = [];
              if ($request['rt']) $rtrw[] = 'RT ' . h($request['rt']);
              if ($request['rw']) $rtrw[] = 'RW ' . h($request['rw']);
              if ($rtrw) echo '<br>' . implode('/', $rtrw);
              if ($request['desa']) echo '<br>' . h($request['desa']);
              ?>
            </td>
          </tr>
          <?php endif; ?>
        </table>
      </div>

      <!-- Actions -->
      <div class="detail-actions">
        <?php if ($status === 'pending' || $status === 'verifikasi'): ?>
        <form method="post" action="?p=request_approve" style="display: inline;">
          <input type="hidden" name="id" value="<?= $id ?>">
          <input type="hidden" name="action" value="approve">
          <button type="submit" class="btn btn-success" onclick="return confirm('Setujui permintaan ini?')">
            ✓ Setujui Permintaan
          </button>
        </form>
        
        <form method="post" action="?p=request_approve" style="display: inline;">
          <input type="hidden" name="id" value="<?= $id ?>">
          <input type="hidden" name="action" value="reject">
          <button type="submit" class="btn btn-danger" onclick="return confirm('Tolak permintaan ini?')">
            ✗ Tolak Permintaan
          </button>
        </form>
        <?php endif; ?>

        <?php if ($status === 'approved'): ?>
        <form method="post" action="?p=request_approve" style="display: inline;">
          <input type="hidden" name="id" value="<?= $id ?>">
          <input type="hidden" name="action" value="process">
          <button type="submit" class="btn btn-primary">
            → Proses Surat
          </button>
        </form>
        <?php endif; ?>

        <?php if ($status === 'processing'): ?>
        <a href="?p=generate_pdf&request_id=<?= $id ?>" class="btn btn-primary">
          📄 Generate PDF
        </a>
        <form method="post" action="?p=request_approve" style="display: inline;">
          <input type="hidden" name="id" value="<?= $id ?>">
          <input type="hidden" name="action" value="finish">
          <button type="submit" class="btn btn-success">
            ✓ Tandai Selesai
          </button>
        </form>
        <?php endif; ?>

        <?php if ($status === 'finished'): ?>
        <a href="?p=generate_pdf&request_id=<?= $id ?>" class="btn btn-secondary" target="_blank">
          📄 Lihat PDF
        </a>
        <?php endif; ?>
      </div>
    </div>

  </section>
</div>
</body>
</html>
