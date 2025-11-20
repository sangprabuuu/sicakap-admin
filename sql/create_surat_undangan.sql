-- Hapus tabel lama jika ada (HATI-HATI: Ini akan menghapus semua data!)
DROP TABLE IF EXISTS surat_undangan CASCADE;

-- Buat tabel surat_undangan (sesuai dengan field di form)
CREATE TABLE surat_undangan (
  id SERIAL PRIMARY KEY,
  tanggal_surat DATE NOT NULL,
  nomor_surat VARCHAR(255),
  perihal VARCHAR(255) NOT NULL,
  nama VARCHAR(255) NOT NULL,
  alamat TEXT,
  tembusan_kepada TEXT,
  tanggal_pelaksanaan DATE,
  jam TIME,
  hari_tanggal VARCHAR(255),
  tempat_pelaksanaan VARCHAR(255),
  created_at TIMESTAMP DEFAULT NOW(),
  updated_at TIMESTAMP DEFAULT NOW()
);

-- Index untuk performa
CREATE INDEX idx_surat_undangan_tanggal ON surat_undangan(tanggal_surat);
CREATE INDEX idx_surat_undangan_nama ON surat_undangan(nama);

-- Trigger untuk auto-update updated_at
CREATE OR REPLACE FUNCTION update_surat_undangan_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
   NEW.updated_at = NOW();
   RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TRIGGER update_surat_undangan_updated_at 
BEFORE UPDATE ON surat_undangan
FOR EACH ROW EXECUTE FUNCTION update_surat_undangan_updated_at_column();

-- Verifikasi
SELECT 'Tabel surat_undangan berhasil dibuat!' as status;
