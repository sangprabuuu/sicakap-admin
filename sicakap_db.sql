-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Waktu pembuatan: 25 Okt 2025 pada 20.08
-- Versi server: 10.4.32-MariaDB
-- Versi PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `sicakap_db`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `issued_letters`
--

CREATE TABLE `issued_letters` (
  `id` int(11) NOT NULL,
  `letter_no` varchar(100) DEFAULT NULL,
  `request_id` int(11) DEFAULT NULL,
  `template_id` int(11) DEFAULT NULL,
  `generated_pdf` varchar(255) DEFAULT NULL,
  `qr_code` varchar(255) DEFAULT NULL,
  `issued_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `letter_requests`
--

CREATE TABLE `letter_requests` (
  `id` int(11) NOT NULL,
  `no_request` varchar(100) DEFAULT NULL,
  `resident_id` int(11) DEFAULT NULL,
  `resident_nik` varchar(50) DEFAULT NULL,
  `resident_name` varchar(255) DEFAULT NULL,
  `template_id` int(11) DEFAULT NULL,
  `requested_at` datetime DEFAULT NULL,
  `status` enum('pending','verifikasi','approved','rejected','processing','finished') DEFAULT 'pending',
  `attachments` text DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `letter_templates`
--

CREATE TABLE `letter_templates` (
  `id` int(11) NOT NULL,
  `code` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `content` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Struktur dari tabel `residents`
--

CREATE TABLE `residents` (
  `id` int(11) NOT NULL,
  `nik` varchar(50) NOT NULL,
  `name` varchar(255) NOT NULL,
  `tempat_lahir` varchar(255) DEFAULT NULL,
  `tanggal_lahir` date DEFAULT NULL,
  `jenis_kelamin` varchar(20) DEFAULT NULL,
  `agama` varchar(50) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `rt` varchar(10) DEFAULT NULL,
  `rw` varchar(10) DEFAULT NULL,
  `desa` varchar(255) DEFAULT NULL,
  `pekerjaan` varchar(255) DEFAULT NULL,
  `status_perkawinan` varchar(50) DEFAULT NULL,
  `kewarganegaraan` varchar(50) DEFAULT NULL,
  `nama_ayah` varchar(255) DEFAULT NULL,
  `nama_ibu` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `residents`
--

INSERT INTO `residents` (`id`, `nik`, `name`, `tempat_lahir`, `tanggal_lahir`, `jenis_kelamin`, `agama`, `alamat`, `rt`, `rw`, `desa`, `pekerjaan`, `status_perkawinan`, `kewarganegaraan`, `nama_ayah`, `nama_ibu`, `created_at`) VALUES
(1, '3201000000000001', 'Budi Santoso', 'Bandung', '1990-01-01', 'Laki-laki', 'Islam', 'Jl. Mawar No.1', '001', '002', 'Desa Contoh', 'Karyawan', NULL, NULL, NULL, NULL, '2025-10-24 08:33:57'),
(2, '3201000000000002', 'Siti Aminah', 'Bandung', '1992-05-03', 'Perempuan', 'Islam', 'Jl. Melati No.2', '001', '002', 'Desa Contoh', 'Ibu Rumah Tangga', NULL, NULL, NULL, NULL, '2025-10-24 08:33:57');

-- --------------------------------------------------------

--
-- Struktur dari tabel `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `email` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `users`
--

INSERT INTO `users` (`id`, `email`, `password`, `name`, `created_at`) VALUES
(1, 'admin@sicakap.local', '$2y$10$3mq6EEx/1gMNPn5WUOroJ.aLvXKzhHSchmBRIy8QNN.FYxqM/y3au', 'Administrator', '2025-10-24 08:11:02');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `issued_letters`
--
ALTER TABLE `issued_letters`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `letter_no` (`letter_no`),
  ADD KEY `request_id` (`request_id`),
  ADD KEY `template_id` (`template_id`);

--
-- Indeks untuk tabel `letter_requests`
--
ALTER TABLE `letter_requests`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `no_request` (`no_request`),
  ADD KEY `resident_id` (`resident_id`),
  ADD KEY `template_id` (`template_id`);

--
-- Indeks untuk tabel `letter_templates`
--
ALTER TABLE `letter_templates`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `code` (`code`);

--
-- Indeks untuk tabel `residents`
--
ALTER TABLE `residents`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nik` (`nik`);

--
-- Indeks untuk tabel `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `issued_letters`
--
ALTER TABLE `issued_letters`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `letter_requests`
--
ALTER TABLE `letter_requests`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `letter_templates`
--
ALTER TABLE `letter_templates`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT untuk tabel `residents`
--
ALTER TABLE `residents`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT untuk tabel `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `issued_letters`
--
ALTER TABLE `issued_letters`
  ADD CONSTRAINT `issued_letters_ibfk_1` FOREIGN KEY (`request_id`) REFERENCES `letter_requests` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `issued_letters_ibfk_2` FOREIGN KEY (`template_id`) REFERENCES `letter_templates` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `letter_requests`
--
ALTER TABLE `letter_requests`
  ADD CONSTRAINT `letter_requests_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `residents` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `letter_requests_ibfk_2` FOREIGN KEY (`template_id`) REFERENCES `letter_templates` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
