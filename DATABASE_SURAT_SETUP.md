# Cara Koneksi Database untuk Surat SPPD & Undangan

## 1. Jalankan SQL Schema di Supabase

### Via Dashboard Supabase:
1. Buka https://supabase.com/dashboard
2. Pilih project Anda
3. Klik **SQL Editor** di sidebar kiri
4. Klik **New Query**
5. Copy-paste isi file `sql/surat_schema.sql`
6. Klik **Run** atau tekan `Ctrl+Enter`
7. Tunggu sampai selesai (success message)

### Via CLI (Optional):
```bash
supabase db push
```

## 2. Verifikasi Tabel Sudah Dibuat

### Via Dashboard:
1. Klik **Table Editor** di sidebar
2. Cek apakah tabel `surat_sppd` dan `surat_undangan` sudah muncul

### Via SQL Editor:
```sql
SELECT * FROM surat_sppd LIMIT 5;
SELECT * FROM surat_undangan LIMIT 5;
```

## 3. Struktur Tabel

### Tabel: `surat_sppd`
- `id` - UUID primary key
- `nomor_surat` - VARCHAR(100)
- `tanggal_surat` - DATE
- `nama_pegawai` - VARCHAR(255)
- `nip` - VARCHAR(50)
- `pangkat_golongan` - VARCHAR(100)
- `jabatan` - VARCHAR(255)
- `maksud_perjalanan` - TEXT
- `alat_angkutan` - VARCHAR(100)
- `tempat_berangkat` - VARCHAR(255)
- `tempat_tujuan` - VARCHAR(255)
- `tanggal_berangkat` - DATE
- `tanggal_kembali` - DATE
- `lama_perjalanan` - VARCHAR(50)
- `pengikut` - JSONB (data pengikut dalam format array)
- `instansi_pembebanan` - VARCHAR(255)
- `mata_anggaran` - VARCHAR(255)
- `keterangan` - TEXT
- `status` - VARCHAR(50) (draft/terbit/batal)
- `created_by` - VARCHAR(255)
- `created_at` - TIMESTAMP
- `updated_at` - TIMESTAMP

### Tabel: `surat_undangan`
- `id` - UUID primary key
- `nomor_surat` - VARCHAR(100)
- `tanggal_surat` - DATE
- `perihal` - VARCHAR(255)
- `jenis_acara` - VARCHAR(100)
- `hari` - VARCHAR(50)
- `tanggal_acara` - DATE
- `waktu_mulai` - TIME
- `waktu_selesai` - TIME
- `tempat_acara` - VARCHAR(255)
- `ditujukan_kepada` - VARCHAR(255)
- `jabatan_penerima` - VARCHAR(255)
- `alamat_penerima` - TEXT
- `penandatangan_nama` - VARCHAR(255)
- `penandatangan_nip` - VARCHAR(50)
- `penandatangan_jabatan` - VARCHAR(255)
- `agenda` - JSONB (array agenda acara)
- `tembusan` - JSONB (array tembusan)
- `keterangan` - TEXT
- `dress_code` - VARCHAR(100)
- `status` - VARCHAR(50) (draft/terkirim/batal)
- `created_by` - VARCHAR(255)
- `created_at` - TIMESTAMP
- `updated_at` - TIMESTAMP

## 4. Koneksi dari PHP

Koneksi sudah otomatis menggunakan function `supabase_request()` dari `app/db.php`.

### Contoh Query SELECT:
```php
// Get all SPPD
$result = supabase_request('GET', 'surat_sppd?select=*&order=created_at.desc');
$data_sppd = $result['data'];

// Get SPPD by ID
$id = 'uuid-here';
$result = supabase_request('GET', "surat_sppd?id=eq.$id&select=*");
$sppd = $result['data'][0];
```

### Contoh INSERT:
```php
$data = json_encode([
    'nomor_surat' => '001/SPPD/2025',
    'tanggal_surat' => '2025-01-15',
    'nama_pegawai' => 'John Doe',
    'jabatan' => 'Kepala Dinas',
    'maksud_perjalanan' => 'Rapat Koordinasi',
    'tempat_tujuan' => 'Jakarta',
    'tanggal_berangkat' => '2025-01-20',
    'tanggal_kembali' => '2025-01-22',
    'status' => 'draft'
]);

$result = supabase_request('POST', 'surat_sppd', $data);
```

### Contoh UPDATE:
```php
$id = 'uuid-here';
$data = json_encode([
    'status' => 'terbit',
    'nomor_surat' => '001/SPPD/2025/FINAL'
]);

$result = supabase_request('PATCH', "surat_sppd?id=eq.$id", $data);
```

### Contoh DELETE:
```php
$id = 'uuid-here';
$result = supabase_request('DELETE', "surat_sppd?id=eq.$id");
```

## 5. Field JSONB (pengikut, agenda, tembusan)

### Format pengikut (untuk SPPD):
```json
[
  {
    "nama": "Jane Doe",
    "nip": "198501012010012001",
    "pangkat": "Pengatur Muda",
    "jabatan": "Staff"
  },
  {
    "nama": "Bob Smith",
    "nip": "198601012011012001",
    "pangkat": "Pengatur",
    "jabatan": "Staff"
  }
]
```

### Format agenda (untuk Undangan):
```json
[
  {
    "waktu": "08:00 - 08:30",
    "kegiatan": "Registrasi Peserta"
  },
  {
    "waktu": "08:30 - 09:00",
    "kegiatan": "Pembukaan"
  },
  {
    "waktu": "09:00 - 12:00",
    "kegiatan": "Acara Inti"
  }
]
```

### Format tembusan (untuk Undangan):
```json
[
  "Yth. Bupati sebagai laporan",
  "Yth. Sekretaris Daerah",
  "Arsip"
]
```

## 6. Status Workflow

### SPPD:
- `draft` - Baru dibuat, belum diterbitkan
- `terbit` - Sudah diterbitkan dengan nomor surat
- `batal` - Dibatalkan

### Undangan:
- `draft` - Baru dibuat, belum dikirim
- `terkirim` - Sudah dikirim ke penerima
- `batal` - Dibatalkan

## 7. Testing

Setelah tabel dibuat, test dengan query sederhana:

```sql
-- Test insert SPPD
INSERT INTO surat_sppd (
  nomor_surat, 
  tanggal_surat, 
  nama_pegawai, 
  jabatan, 
  maksud_perjalanan, 
  tempat_tujuan, 
  tanggal_berangkat, 
  tanggal_kembali
) VALUES (
  '001/SPPD/TEST/2025',
  '2025-11-20',
  'Test User',
  'Kepala Bagian',
  'Testing SPPD',
  'Bandung',
  '2025-11-25',
  '2025-11-27'
);

-- Lihat hasilnya
SELECT * FROM surat_sppd;
```

## 8. Next Steps

Setelah database siap:
1. ✅ Tabel sudah dibuat
2. ⏳ Buat form input untuk SPPD
3. ⏳ Buat form input untuk Undangan  
4. ⏳ Simpan data ke database via PHP
5. ⏳ Tampilkan list data di halaman
6. ⏳ Generate PDF dengan template yang Anda berikan

---

**Siap melanjutkan ke pembuatan form setelah template diberikan!** 📋
