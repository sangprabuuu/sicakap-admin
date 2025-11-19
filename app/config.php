<?php
// Konfigurasi dasar
define('APP_URL', 'http://localhost/sicakap-admin/public'); // sesuaikan path jika perlu

// Supabase Configuration
define('SUPABASE_URL', 'https://darhzpdhpbrtxxyzklkr.supabase.co');
define('SUPABASE_KEY', 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImRhcmh6cGRocGJydHh4eXprbGtyIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NjM1MjU2MDgsImV4cCI6MjA3ODg4NTYwOH0.WCNB2EXbAhZbUwkgRTniqsxhyg5p14RL3ItqTuJUHqU');

// Supabase PostgreSQL Database Connection
// Session pooler (port 6543) - username tanpa prefix untuk session mode
define('DB_HOST', 'aws-0-ap-southeast-2.pooler.supabase.com');
define('DB_PORT', '6543');  // Session mode
define('DB_NAME', 'postgres');
define('DB_USER', 'postgres');  // Tanpa .project-ref untuk session pooler
define('DB_PASS', 'Ts0rPqXTCupTxix6');
define('DB_TYPE', 'pgsql');  // PostgreSQL

// Folder untuk upload & hasil generate (pastikan writable)
define('UPLOAD_DIR', __DIR__ . '/uploads');
define('GENERATED_DIR', __DIR__ . '/generated');

if (!is_dir(UPLOAD_DIR)) mkdir(UPLOAD_DIR, 0755, true);
if (!is_dir(GENERATED_DIR)) mkdir(GENERATED_DIR, 0755, true);


