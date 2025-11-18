# Panduan Setup Supabase untuk SiCakap

## 1. Buat Project Supabase

1. Buka [https://supabase.com](https://supabase.com)
2. Sign in / Sign up dengan GitHub atau email
3. Klik **New Project**
4. Isi:
   - **Project Name**: sicakap-mobile
   - **Database Password**: (simpan baik-baik!)
   - **Region**: Southeast Asia (Singapore) - untuk performa terbaik
   - **Pricing Plan**: Free tier (sudah cukup untuk development)
5. Tunggu ~2 menit sampai project selesai dibuat

## 2. Import Database Schema

1. Di dashboard Supabase, klik **SQL Editor** di sidebar kiri
2. Klik **New query**
3. Copy seluruh isi file `sql/supabase_schema.sql`
4. Paste ke SQL Editor
5. Klik **Run** atau tekan Ctrl+Enter
6. Pastikan muncul notifikasi "Success. No rows returned"

## 3. Setup Authentication

### A. Konfigurasi Auth Settings

1. Dari halaman **Authentication** → **Users**, klik tab **Configuration** di sebelah kanan
2. Scroll ke bagian **Email Auth**:
   - ✅ **Enable email confirmations**: MATIKAN (toggle OFF) - untuk development
   - ✅ **Enable email sign-ups**: Pastikan ON
   
3. Scroll ke bagian **Auth Providers**:
   - ✅ **Email**: ON (default sudah aktif)
   - 📧 **Google, Facebook, dll**: OFF (tidak dipakai untuk sekarang)

4. Scroll ke bagian **Password Requirements**:
   - Minimum characters: `6` (minimal 6 karakter)
   
5. Klik tombol **Save** di bagian bawah

### B. Disable Email Confirmation (Penting!)

1. Masih di **Configuration**, cari **Email confirmation settings**
2. Toggle **Confirm email** menjadi **OFF**
3. Ini akan memungkinkan user langsung login tanpa verifikasi email (untuk development)

### C. Setup Redirect URLs (untuk production nanti)

1. Di bagian **URL Configuration**:
   - **Site URL**: `http://localhost` (untuk development)
   - **Redirect URLs**: Kosongkan dulu
2. Klik **Save**

**✅ Authentication sudah siap digunakan!**

## 4. Setup Storage untuk PDF

1. Klik **Storage** di sidebar
2. Klik **Create a new bucket**
3. Isi:
   - **Name**: `letters`
   - **Public bucket**: ON (agar PDF bisa diakses)
4. Klik **Save**
5. Di bucket `letters`, klik **Policies**
6. Tambah policy:
   - **Policy name**: `Allow public read`
   - **Policy definition**: SELECT
   - **Target roles**: `public`
   - Klik **Review** → **Save**

## 5. Get API Keys

1. Klik **Settings** (icon gear) di sidebar
2. Klik **API**
3. Copy nilai berikut:
   - **Project URL**: `https://xxxxx.supabase.co`
   - **anon/public key**: `eyJhbGc...` (key yang panjang)
   - **service_role key**: `eyJhbGc...` (JANGAN share ke publik!)

## 6. Konfigurasi PHP API

1. Buka file `public/api/index.php`
2. Ganti nilai konstanta:
   ```php
   define('SUPABASE_URL', 'https://xxxxx.supabase.co'); // Project URL dari step 5
   define('SUPABASE_KEY', 'eyJhbGc...'); // anon key dari step 5
   ```
3. Save file

## 7. Test API Endpoints

### Test dengan cURL (PowerShell):

```powershell
# Test get templates (tidak perlu auth)
curl http://localhost/sicakap-admin/api/templates

# Test register
curl -X POST http://localhost/sicakap-admin/api/auth/register `
  -H "Content-Type: application/json" `   
  -d '{"email":"test@example.com","password":"password123","nik":"1234567890123456","name":"Test User"}'

# Test login
curl -X POST http://localhost/sicakap-admin/api/auth/login `
  -H "Content-Type: application/json" `
  -d '{"email":"test@example.com","password":"password123"}'

# Test create request (perlu token dari login)
curl -X POST http://localhost/sicakap-admin/api/requests `
  -H "Content-Type: application/json" `
  -H "Authorization: Bearer YOUR_TOKEN_HERE" `
  -d '{"resident_id":"uuid-dari-register","template_id":"uuid-dari-templates","notes":"Test request"}'

# Test get requests
curl http://localhost/sicakap-admin/api/requests `
  -H "Authorization: Bearer YOUR_TOKEN_HERE"
```

### Test dengan Postman:

1. Import collection dari file `postman_collection.json` (akan dibuat terpisah)
2. Set environment variable:
   - `base_url`: `http://localhost/sicakap-admin/api`
   - `token`: (akan diisi otomatis setelah login)

## 8. Setup Row Level Security (RLS)

RLS sudah disetup di schema, tapi pastikan aktif:

1. Klik **Database** → **Tables** di Supabase dashboard
2. Untuk setiap table, pastikan **Enable RLS** ON
3. Policies sudah dibuat di schema (lihat `supabase_schema.sql`)

## 9. Monitoring & Logs

1. **API Logs**: Klik **API** di sidebar → tab **Logs**
2. **Database Logs**: Klik **Database** → tab **Logs**
3. **Auth Logs**: Klik **Authentication** → tab **Logs**

## 10. Security Checklist

- ✅ RLS enabled untuk semua tables
- ✅ `anon key` digunakan untuk API publik
- ✅ `service_role key` TIDAK di-commit ke Git
- ✅ Email confirmation dimatikan (development only)
- ✅ Password minimal 6 karakter (sesuaikan di Auth settings)
- ❌ HTTPS enabled (production only)
- ❌ Rate limiting (akan disetup di Flutter)

## 11. Struktur API untuk Flutter

### Base URL
```
http://localhost/sicakap-admin/api (development)
https://yourdomain.com/api (production)
```

### Headers
```
Content-Type: application/json
Authorization: Bearer {access_token} (untuk protected routes)
```

### Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| POST | `/auth/login` | No | Login user |
| POST | `/auth/register` | No | Register user baru |
| GET | `/templates` | No | List template surat |
| GET | `/residents/{nik}` | Yes | Get data penduduk by NIK |
| POST | `/requests` | Yes | Buat permintaan surat |
| GET | `/requests` | Yes | List permintaan user |
| GET | `/requests/{id}` | Yes | Detail permintaan |
| GET | `/notifications` | Yes | List notifikasi |

### Response Format

**Success:**
```json
{
  "success": true,
  "data": { ... },
  "message": "Optional message"
}
```

**Error:**
```json
{
  "error": "Error message"
}
```

## 12. Integrasi dengan PHP Admin

Admin panel tetap menggunakan MySQL lokal untuk sementara. Untuk migrasi penuh:

1. Update `app/db.php` untuk connect ke Supabase (via REST API atau pgSQL driver)
2. Atau, setup **Database Replication** dari MySQL → PostgreSQL
3. Atau, gunakan **Dual Write** (write ke MySQL & Supabase bersamaan)

## 13. Next Steps untuk Flutter

1. Install package:
   ```yaml
   dependencies:
     supabase_flutter: ^2.0.0
     http: ^1.1.0
   ```

2. Initialize Supabase:
   ```dart
   await Supabase.initialize(
     url: 'YOUR_SUPABASE_URL',
     anonKey: 'YOUR_ANON_KEY',
   );
   ```

3. Make API calls:
   ```dart
   // Via Supabase client (recommended)
   final response = await Supabase.instance.client
     .from('letter_requests')
     .select()
     .eq('resident_id', userId);
   
   // Via REST API
   final response = await http.get(
     Uri.parse('$baseUrl/requests'),
     headers: {'Authorization': 'Bearer $token'},
   );
   ```

## Troubleshooting

### Error: "relation does not exist"
- Schema belum dijalankan atau gagal. Re-run `supabase_schema.sql`

### Error: "Invalid API key"
- Cek API key di `public/api/index.php` sudah benar

### Error: "JWT expired"
- Token login expired (default 1 jam). User harus login ulang

### Error: "RLS policy violation"
- Policy RLS terlalu ketat atau user tidak punya akses

### Error: "CORS policy"
- Pastikan header CORS sudah ada di `public/api/index.php`

## Support

- Supabase Docs: [https://supabase.com/docs](https://supabase.com/docs)
- Supabase Discord: [https://discord.supabase.com](https://discord.supabase.com)
