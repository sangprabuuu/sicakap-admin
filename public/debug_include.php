<?php
// debug_include.php — untuk men-debug include login page
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h3>Debug include login.php</h3>";
$path = __DIR__ . '/../app/pages/login.php';
echo "<p>Path login.php: " . htmlspecialchars($path) . "</p>";
if (file_exists($path)) {
    echo "<p>File exists. Attempting to include...</p>";
    include $path;
    echo "<p>Include finished.</p>";
} else {
    echo "<p style='color:red'>File tidak ditemukan: $path</p>";
}