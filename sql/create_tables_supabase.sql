-- Hapus tabel lama jika ada (HATI-HATI: Ini akan menghapus semua data!)
DROP TABLE IF EXISTS pengajuan_sppd CASCADE;
DROP TABLE IF EXISTS surat_undangan CASCADE;

-- Buat tabel pengajuan_sppd
CREATE TABLE pengajuan_sppd (
  id SERIAL PRIMARY KEY,
  tanggal_pembuatan DATE NOT NULL,
  nomor_sppd VARCHAR(255),
  nama_pegawai VARCHAR(255) NOT NULL,
  nip VARCHAR(50),
  jabatan VARCHAR(255),
  maksud_perjalanan TEXT,
  tempat_tujuan VARCHAR(255),
  jenis_durasi VARCHAR(50) CHECK (jenis_durasi IN ('harian', 'lebih dari 1 hari')),
  tanggal_mulai DATE,
  tanggal_selesai DATE,
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Buat tabel surat_undangan
CREATE TABLE surat_undangan (
  id SERIAL PRIMARY KEY,
  tanggal_pembuatan DATE NOT NULL,
  nomor_surat VARCHAR(255),
  perihal VARCHAR(255) NOT NULL,
  nama VARCHAR(255) NOT NULL,
  alamat TEXT,
  tembusan_kepada VARCHAR(255),
  hari_tanggal VARCHAR(255),
  tempat_pelaksanaan VARCHAR(255),
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Index untuk performa
CREATE INDEX idx_pengajuan_sppd_tanggal ON pengajuan_sppd(tanggal_pembuatan);
CREATE INDEX idx_pengajuan_sppd_nama ON pengajuan_sppd(nama_pegawai);
CREATE INDEX idx_surat_undangan_tanggal ON surat_undangan(tanggal_pembuatan);
CREATE INDEX idx_surat_undangan_nama ON surat_undangan(nama);

-- Trigger untuk auto-update updated_at
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
   NEW.updated_at = NOW();
   RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_pengajuan_sppd_updated_at 
BEFORE UPDATE ON pengajuan_sppd
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

CREATE TRIGGER update_surat_undangan_updated_at 
BEFORE UPDATE ON surat_undangan
FOR EACH ROW EXECUTE FUNCTION update_updated_at_column();

-- Insert data dummy untuk testing (opsional, hapus jika tidak diperlukan)
INSERT INTO pengajuan_sppd (
  tanggal_pembuatan,
  nomor_sppd,
  nama_pegawai,
  nip,
  jabatan,
  maksud_perjalanan,
  tempat_tujuan,
  jenis_durasi,
  tanggal_mulai,
  tanggal_selesai
) VALUES (
  '2025-11-20',
  '001/SPPD/XI/2025',
  'John Doe',
  '199001012015011001',
  'Kepala Bagian Umum',
  'Menghadiri rapat koordinasi tingkat provinsi',
  'Jakarta',
  'lebih dari 1 hari',
  '2025-11-25',
  '2025-11-27'
);

-- Verifikasi
SELECT 'Tabel pengajuan_sppd berhasil dibuat!' as status, COUNT(*) as jumlah_data FROM pengajuan_sppd;
SELECT 'Tabel surat_undangan berhasil dibuat!' as status, COUNT(*) as jumlah_data FROM surat_undangan;
