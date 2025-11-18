<?php
// public/debug_residents.php — buat sementara untuk men-debug halaman residents
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// include sama seperti index.php supaya fungsi db(), auth, functions tersedia
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';
require_once __DIR__ . '/../app/auth.php';
require_once __DIR__ . '/../app/functions.php';

echo "<h3>Debug include residents.php</h3>";
$path = __DIR__ . '/../app/pages/residents.php';
echo "<p>Path residents.php: " . htmlspecialchars($path) . "</p>";

if (file_exists($path)) {
    echo "<p>File exists. Attempting to include...</p>";
    include $path;
    echo "<p>Include finished.</p>";
} else {
    echo "<p style='color:red'>File tidak ditemukan: $path</p>";
}