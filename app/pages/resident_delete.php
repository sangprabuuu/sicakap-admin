<?php
session_start();
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../auth.php';

if (!is_logged_in()) {
    header('Location: ' . APP_URL . '/?p=login');
    exit;
}

$id = intval($_POST['id'] ?? 0);
if (!$id) {
    $_SESSION['flash'] = "ID tidak valid.";
    header('Location: ' . APP_URL . '/?p=residents');
    exit;
}

try {
    $pdo = db();
    $stmt = $pdo->prepare("DELETE FROM residents WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['flash'] = "Data penduduk dihapus.";
} catch (Exception $e) {
    $_SESSION['flash'] = "Gagal menghapus: " . $e->getMessage();
}

header('Location: ' . APP_URL . '/?p=residents');
exit;