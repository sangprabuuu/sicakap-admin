# Cara Mengisi Tabel Surat Selesai (issued_letters)

## 🔄 Alur Otomatis (Recommended)

Tabel `issued_letters` akan **terisi otomatis** saat admin melakukan generate PDF surat. Berikut prosesnya:

### 1️⃣ Workflow Normal
```
Permintaan Masuk → Verifikasi → Disetujui → Proses → Generate PDF → Surat Selesai
     (pending)    (verifikasi)  (approved) (processing)              (finished)
                                                ↓
                                        [Otomatis tersimpan ke 
                                         issued_letters]
```

### 2️⃣ Proses Generate PDF
Ketika admin klik tombol **"Generate PDF"**:

1. **Generate Nomor Surat Otomatis**
   - Format: `001/SKT/DESA/X/2025`
   - Nomor urut berdasarkan bulan dan tahun
   - Contoh: `001/SKT/DESA/X/2025`, `002/SKT/DESA/X/2025`

2. **Buat File PDF**
   - Ambil data dari `letter_requests` dan `residents`
   - Generate PDF dengan template surat
   - Simpan di folder `app/generated/`
   - Nama file: `surat_[request_id]_[timestamp].pdf`

3. **Simpan ke Database** (`issued_letters`)
   ```sql
   INSERT INTO issued_letters (
       letter_no,           -- Nomor surat yang di-generate
       request_id,          -- ID permintaan
       template_id,         -- ID template surat
       generated_pdf,       -- Nama file PDF
       issued_at            -- Waktu penerbitan
   ) VALUES (...)
   ```

4. **Update Status Request**
   - Status `letter_requests` diubah menjadi `finished`

5. **Tampilkan PDF**
   - PDF langsung ditampilkan ke admin untuk preview

---

## 📋 Contoh Data yang Tersimpan

Setelah generate PDF, tabel `issued_letters` akan berisi:

| id | letter_no | request_id | template_id | generated_pdf | issued_at |
|----|-----------|------------|-------------|---------------|-----------|
| 1 | 001/SKT/DESA/X/2025 | 5 | 1 | surat_5_1729753200.pdf | 2025-10-24 10:30:00 |
| 2 | 002/SKT/DESA/X/2025 | 7 | 1 | surat_7_1729753300.pdf | 2025-10-24 11:15:00 |

---

## 🎯 Cara Menggunakan dari UI

### Dari Halaman Permintaan Surat:
1. Buka menu **"Permintaan Surat"**
2. Pilih permintaan dengan status **"Processing"** atau **"Approved"**
3. Klik **"Detail"**
4. Klik tombol **"Generate PDF"** atau **"📄 Generate PDF"**
5. PDF akan muncul dan data otomatis tersimpan ke `issued_letters`

### Dari Halaman Detail Permintaan:
1. Jika status **"Approved"**, klik **"→ Proses Surat"**
2. Status berubah menjadi **"Processing"**
3. Klik **"📄 Generate PDF"**
4. PDF akan ter-generate dan tersimpan otomatis

### Melihat Hasil:
1. Buka menu **"Surat Selesai"**
2. Semua surat yang sudah di-generate akan muncul di sini
3. Klik **"PDF"** untuk lihat/download ulang

---

## 🔢 Format Nomor Surat

Sistem menggunakan format standar surat desa:

```
[NOMOR URUT] / [KODE TEMPLATE] / [DESA] / [BULAN ROMAWI] / [TAHUN]

Contoh:
- 001/SKT/DESA/I/2025      (Januari)
- 002/SKT/DESA/I/2025      (Januari)
- 001/SKT/DESA/II/2025     (Februari - reset nomor)
- 001/SKCK/DESA/III/2025   (Maret - template berbeda)
```

**Keterangan:**
- Nomor urut reset setiap bulan
- Kode template bisa: SKT, SKCK, SKDU, dll
- Bulan romawi: I-XII
- Otomatis increment per bulan

---

## 🛠️ Pengembangan Lebih Lanjut

### Untuk PDF yang Lebih Baik:
Saat ini generate PDF masih menggunakan format text sederhana. 
Untuk hasil lebih profesional, install library PDF:

```bash
composer require tecnickcom/tcpdf
# atau
composer require mpdf/mpdf
```

Kemudian modifikasi fungsi `generateSimplePDF()` di file:
`app/actions/generate_pdf.php`

### Custom Template:
Edit fungsi `generateSimplePDF()` untuk menyesuaikan layout:
- Logo desa
- Kop surat
- Format tabel
- Tanda tangan digital
- QR Code

---

## 📊 Statistik di Halaman Surat Selesai

Halaman "Surat Selesai" menampilkan:
- **Total Surat**: Semua surat yang diterbitkan
- **Hari Ini**: Surat yang diterbitkan hari ini
- **Bulan Ini**: Surat bulan berjalan

Sangat berguna untuk monitoring produktivitas kantor desa! 📈

---

## ✅ Kesimpulan

**Tidak perlu input manual!** Sistem sudah otomatis:
1. ✅ Generate nomor surat
2. ✅ Buat file PDF
3. ✅ Simpan ke database `issued_letters`
4. ✅ Update status request
5. ✅ Tampilkan di halaman "Surat Selesai"

Admin hanya perlu:
1. Terima permintaan
2. Verifikasi
3. Setujui
4. Generate PDF
5. Selesai! 🎉
