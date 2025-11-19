# Cara Aktifkan PostgreSQL Extension di XAMPP

## Langkah-langkah:

### 1. Edit php.ini
1. Buka XAMPP Control Panel
2. Klik tombol **Config** di baris Apache
3. Pilih **PHP (php.ini)**
4. Cari baris berikut (gunakan Ctrl+F):
   ```
   ;extension=pdo_pgsql
   ;extension=pgsql
   ```
5. **Hapus tanda semicolon (;)** di depan kedua baris tersebut sehingga menjadi:
   ```
   extension=pdo_pgsql
   extension=pgsql
   ```
6. Save file (Ctrl+S)

### 2. Restart Apache
1. Kembali ke XAMPP Control Panel
2. Klik **Stop** pada Apache
3. Tunggu beberapa detik
4. Klik **Start** pada Apache

### 3. Verifikasi Extension Aktif
Jalankan command ini di PowerShell:
```powershell
php -m | Select-String -Pattern "pgsql"
```

Harusnya muncul:
```
pdo_pgsql
pgsql
```

### 4. Update Password Database di config.php

1. Buka Supabase Dashboard → **Settings** → **Database**
2. Di bagian **Connection String**, pilih **Session mode**
3. Copy password dari connection string atau buat password baru
4. Buka file `app/config.php`
5. Update baris ini dengan password kamu:
   ```php
   define('DB_PASS', 'YOUR_DATABASE_PASSWORD_HERE');
   ```

### 5. Test Koneksi
Buka browser: `http://localhost/sicakap-admin/public/?p=dashboard`

Jika berhasil, data akan muncul dari Supabase!

---

## Troubleshooting

**Error: "could not find driver"**
- Extension belum aktif, ulangi langkah 1-2

**Error: "password authentication failed"**
- Password salah, cek di Supabase Dashboard → Settings → Database

**Error: "no pg_hba.conf entry"**
- Pastikan gunakan `sslmode=require` di connection string
- Sudah diatur di `db.php`

**Error: "connection refused"**
- Cek internet connection
- Pastikan firewall tidak block port 6543
