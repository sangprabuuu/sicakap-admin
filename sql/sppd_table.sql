-- Tabel untuk data SPPD (Surat Perintah Perjalanan Dinas)
CREATE TABLE IF NOT EXISTS sppd (
  id INT AUTO_INCREMENT PRIMARY KEY,
  nomor VARCHAR(100) NOT NULL,
  tanggal DATE NOT NULL,
  nama VARCHAR(200) NOT NULL,
  nip VARCHAR(50) NOT NULL,
  jabatan VARCHAR(200) NOT NULL,
  maksud VARCHAR(500) NOT NULL,
  tempat_tujuan VARCHAR(200) NOT NULL,
  durasi ENUM('1_hari', 'lebih_dari') NOT NULL DEFAULT '1_hari',
  tanggal_mulai DATE NOT NULL,
  tanggal_selesai DATE NOT NULL,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample data
INSERT INTO sppd (nomor, tanggal, nama, nip, jabatan, maksud, tempat_tujuan, durasi, tanggal_mulai, tanggal_selesai) VALUES
('001/SPPD/Campakoan/X/2025', '2025-09-20', 'Sang Prabu', '1020', 'Kabupaten', 'Rapat koordinasi', 'Bandung', '1_hari', '2025-09-20', '2025-09-20');
