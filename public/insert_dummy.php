<?php
try {
    $pdo = new PDO('mysql:host=127.0.0.1;dbname=sicakap_db', 'root', '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Delete existing data
    $pdo->exec('DELETE FROM letter_requests');
    echo "Data lama dihapus.\n";
    
    // Insert data dummy
    $sql = file_get_contents(__DIR__ . '/../sql/dummy_requests.sql');
    
    // Split by INSERT statements
    preg_match('/INSERT INTO letter_requests.*?;/s', $sql, $matches);
    
    if (!empty($matches[0])) {
        $pdo->exec($matches[0]);
        echo "✓ Data dummy letter_requests berhasil ditambahkan!\n";
        
        // Count
        $count = $pdo->query('SELECT COUNT(*) FROM letter_requests')->fetchColumn();
        echo "Total: $count pengajuan surat\n";
    } else {
        echo "SQL INSERT tidak ditemukan\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
