<?php
$pdo = db();

$id = $_GET['id'] ?? '';

if (!$id) {
    header('Location: ?p=undangan');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM surat_undangan WHERE id = ?");
    $stmt->execute([$id]);
    flash_set('Data undangan berhasil dihapus');
} catch (Exception $e) {
    flash_set('Error: ' . $e->getMessage());
}

header('Location: ?p=undangan');
exit;
