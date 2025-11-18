<?php
// Konfigurasi dasar (sesuaikan untuk XAMPP/phpMyAdmin)
define('DB_HOST', '127.0.0.1');
define('DB_NAME', 'sicakap_db');      // ganti sesuai db yang kamu buat
define('DB_USER', 'root');             // default XAMPP
define('DB_PASS', '');                 // default XAMPP biasanya kosong
define('APP_URL', 'http://localhost/sicakap-admin/public'); // sesuaikan path jika perlu

// Folder untuk upload & hasil generate (pastikan writable)
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('GENERATED_DIR', __DIR__ . '/generated');

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!is_dir(GENERATED_DIR)) mkdir(GENERATED_DIR, 0755, true);


