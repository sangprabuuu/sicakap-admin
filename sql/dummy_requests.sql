-- Insert data dummy untuk letter_templates
INSERT INTO letter_templates (id, code, name, content) VALUES
(1, 'KTP', 'Surat Pengantar KTP', 'Template surat pengantar KTP'),
(2, 'KK', 'Surat Pengantar KK', 'Template surat pengantar KK'),
(3, 'SKCK', 'Surat Keterangan Catatan Kepolisian', 'Template SKCK'),
(4, 'DOMISILI', 'Surat Keterangan Domisili', 'Template surat domisili'),
(5, 'USAHA', 'Surat Keterangan Usaha', 'Template surat usaha'),
(6, 'SKTM', 'Surat Keterangan Tidak Mampu', 'Template SKTM');

-- Insert data dummy untuk residents (jika belum ada)
INSERT INTO residents (id, nik, name, tempat_lahir, tanggal_lahir, jenis_kelamin, alamat, rt, rw, agama, pekerjaan, status_perkawinan, created_at) VALUES
(1, '3201010101010001', 'Budi Santoso', 'Jakarta', '1985-05-15', 'Laki-laki', 'Jl. Merdeka No. 10', '001', '004', 'Islam', 'Wiraswasta', 'Kawin', NOW()),
(2, '3201010202020002', 'Siti Aisyah', 'Bandung', '1990-08-20', 'Perempuan', 'Jl. Sudirman No. 25', '002', '003', 'Islam', 'Ibu Rumah Tangga', 'Kawin', NOW()),
(3, '3201010303030003', 'Ahmad Wijaya', 'Surabaya', '1988-03-10', 'Laki-laki', 'Jl. Pahlawan No. 5', '003', '002', 'Islam', 'Pegawai Swasta', 'Kawin', NOW()),
(4, '3201010404040004', 'Dewi Lestari', 'Yogyakarta', '1992-11-25', 'Perempuan', 'Jl. Gatot Subroto No. 18', '001', '005', 'Kristen', 'Guru', 'Kawin', NOW()),
(5, '3201010505050005', 'Eko Prasetyo', 'Semarang', '1987-07-08', 'Laki-laki', 'Jl. Ahmad Yani No. 30', '004', '002', 'Islam', 'PNS', 'Kawin', NOW()),
(6, '3201010606060006', 'Rina Wati', 'Malang', '1995-01-12', 'Perempuan', 'Jl. Diponegoro No. 12', '002', '004', 'Hindu', 'Mahasiswa', 'Belum Kawin', NOW()),
(7, '3201010707070007', 'Hendra Gunawan', 'Solo', '1983-09-30', 'Laki-laki', 'Jl. Imam Bonjol No. 22', '005', '003', 'Islam', 'Pengusaha', 'Kawin', NOW()),
(8, '3201010808080008', 'Maya Puspita', 'Bogor', '1991-04-18', 'Perempuan', 'Jl. Veteran No. 8', '003', '001', 'Buddha', 'Karyawan Swasta', 'Belum Kawin', NOW())
ON DUPLICATE KEY UPDATE name=name;

-- Insert data dummy untuk letter_requests dengan berbagai status
INSERT INTO letter_requests (no_request, resident_id, resident_nik, resident_name, template_id, requested_at, status, notes, created_at) VALUES
-- Status: pending (menunggu)
('REQ/2025/11/001', 2, '3201000000000002', 'Siti Aminah', 1, '2025-11-18 08:30:00', 'pending', 'Mohon diproses segera', '2025-11-18 08:30:00'),
('REQ/2025/11/002', 3, '2211104037', 'Aditya Prabu', 4, '2025-11-18 09:15:00', 'pending', 'Untuk keperluan administrasi bank', '2025-11-18 09:15:00'),
('REQ/2025/11/003', NULL, '3201010606060006', 'Rina Wati', 6, '2025-11-18 10:00:00', 'pending', 'Untuk beasiswa kuliah', '2025-11-18 10:00:00'),

-- Status: verifikasi (sedang diverifikasi)
('REQ/2025/11/004', 2, '3201000000000002', 'Siti Aminah', 3, '2025-11-17 14:20:00', 'verifikasi', 'Sedang dalam proses verifikasi dokumen', '2025-11-17 14:20:00'),
('REQ/2025/11/005', NULL, '3201010707070007', 'Hendra Gunawan', 5, '2025-11-17 15:45:00', 'verifikasi', 'Menunggu verifikasi RT/RW', '2025-11-17 15:45:00'),

-- Status: approved (disetujui)
('REQ/2025/11/006', 3, '2211104037', 'Aditya Prabu', 2, '2025-11-16 11:00:00', 'approved', 'Sudah disetujui, menunggu proses cetak', '2025-11-16 11:00:00'),
('REQ/2025/11/007', NULL, '3201010808080008', 'Maya Puspita', 4, '2025-11-16 13:30:00', 'approved', 'Disetujui oleh kepala desa', '2025-11-16 13:30:00'),

-- Status: processing (sedang diproses/dicetak)
('REQ/2025/11/008', 2, '3201000000000002', 'Siti Aminah', 1, '2025-11-15 08:00:00', 'processing', 'Sedang dalam proses pencetakan surat', '2025-11-15 08:00:00'),
('REQ/2025/11/009', NULL, '3201010101010001', 'Budi Santoso', 6, '2025-11-15 09:30:00', 'processing', 'Dokumen dalam proses finishing', '2025-11-15 09:30:00'),
('REQ/2025/11/010', 3, '2211104037', 'Aditya Prabu', 5, '2025-11-15 11:00:00', 'processing', 'Sedang diproses di bagian administrasi', '2025-11-15 11:00:00'),

-- Status: finished (selesai)
('REQ/2025/11/011', 2, '3201000000000002', 'Siti Aminah', 3, '2025-11-14 10:00:00', 'finished', 'Surat sudah selesai dan bisa diambil', '2025-11-14 10:00:00'),
('REQ/2025/11/012', NULL, '3201010606060006', 'Rina Wati', 1, '2025-11-13 14:00:00', 'finished', 'Sudah selesai diproses', '2025-11-13 14:00:00'),
('REQ/2025/11/013', 3, '2211104037', 'Aditya Prabu', 4, '2025-11-12 09:00:00', 'finished', 'Surat telah diserahkan kepada pemohon', '2025-11-12 09:00:00'),
('REQ/2025/11/014', NULL, '3201010707070007', 'Hendra Gunawan', 2, '2025-11-11 15:30:00', 'finished', 'Proses selesai', '2025-11-11 15:30:00'),

-- Status: rejected (ditolak)
('REQ/2025/11/015', 2, '3201000000000002', 'Siti Aminah', 3, '2025-11-10 11:00:00', 'rejected', 'Dokumen pendukung tidak lengkap', '2025-11-10 11:00:00'),
('REQ/2025/11/016', NULL, '3201010808080008', 'Maya Puspita', 6, '2025-11-09 13:00:00', 'rejected', 'Data tidak sesuai dengan persyaratan', '2025-11-09 13:00:00');
