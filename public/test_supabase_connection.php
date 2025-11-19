<?php
// Test koneksi dan cek apakah table sudah ada di Supabase
require_once __DIR__ . '/../app/config.php';
require_once __DIR__ . '/../app/db.php';

header('Content-Type: application/json');

try {
    $pdo = db();
    
    // Cek table admin_users
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM admin_users");
    $adminCount = $stmt->fetch();
    
    // Cek table residents
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM residents");
    $residentCount = $stmt->fetch();
    
    // Cek table letter_requests
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM letter_requests");
    $requestCount = $stmt->fetch();
    
    // List semua tables
    $stmt = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' ORDER BY table_name");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo json_encode([
        'status' => 'success',
        'message' => 'Koneksi Supabase berhasil!',
        'data' => [
            'admin_users_count' => $adminCount['count'],
            'residents_count' => $residentCount['count'],
            'letter_requests_count' => $requestCount['count'],
            'total_tables' => count($tables),
            'tables' => $tables
        ]
    ], JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'hint' => 'Pastikan schema SQL sudah dijalankan di Supabase Dashboard → SQL Editor'
    ], JSON_PRETTY_PRINT);
}
