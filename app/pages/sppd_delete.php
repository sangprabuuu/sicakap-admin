<?php
$pdo = db();

$id = $_GET['id'] ?? '';

if (!$id) {
    header('Location: ?p=sppd');
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM sppd WHERE id = ?");
    $stmt->execute([$id]);
    flash_set('Data SPPD berhasil dihapus');
} catch (Exception $e) {
    flash_set('Error: ' . $e->getMessage());
}

header('Location: ?p=sppd');
exit;
