<?php
// test_db.php — file uji koneksi DB. Simpan di public/test_db.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../app/config.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "<h3 style='color:green'>DB connection OK</h3>";
    $row = $pdo->query("SELECT COUNT(*) AS c FROM information_schema.tables WHERE table_schema = '".addslashes(DB_NAME)."'")->fetch();
    echo "<p>Tables in database '".htmlspecialchars(DB_NAME)."': " . intval($row['c']) . "</p>";
} catch (PDOException $e) {
    echo "<h3 style='color:red'>DB connection FAILED</h3>";
    echo "<pre>" . htmlspecialchars($e->getMessage()) . "</pre>";
}