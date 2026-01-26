-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql312.infinityfree.com
-- Waktu pembuatan: 16 Apr 2025 pada 04.04
-- Versi server: 10.6.19-MariaDB
-- Versi PHP: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_38706218_antrean_bps`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `antrian`
--

CREATE TABLE `antrian` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` text DEFAULT NULL,
  `jk` enum('L','P') DEFAULT NULL,
  `lahir` varchar(30) DEFAULT NULL,
  `pendidikan` varchar(50) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `pekerjaan` varchar(100) DEFAULT NULL,
  `data_yang_diperlukan` text DEFAULT NULL,
  `metode` varchar(100) DEFAULT NULL,
  `telepon` varchar(20) NOT NULL,
  `instansi` varchar(100) NOT NULL,
  `jenis` enum('umum','disabilitas','whatsapp') NOT NULL,
  `nomor` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('menunggu','dipanggil') DEFAULT 'menunggu'
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data untuk tabel `antrian`
--

INSERT INTO `antrian` (`id`, `nama`, `email`, `jk`, `lahir`, `pendidikan`, `alamat`, `pekerjaan`, `data_yang_diperlukan`, `metode`, `telepon`, `instansi`, `jenis`, `nomor`, `tanggal`, `status`) VALUES
(41, 'Komang indah Wulandari wismaya', 'Wismayaewc@gmail.com', 'P', NULL, 'S1/DIV', NULL, 'Mahasiswa', 'Mencari lokasi untuk penanaman pohon', 'kunjungan langsung', '081936662555', 'perorangan', 'umum', 2, '2025-04-14', 'menunggu'),
(42, 'Nazzala Qinthara Nafi', 'nzalthara@gmail.com', 'P', NULL, 'S1/DIV', NULL, 'ASN', 'vyig', 'kunjungan langsung', '09', 'ygyug', 'umum', 1, '2025-04-15', 'dipanggil'),
(36, 'Komang Indah Wulandari Wismaya', 'komangindah@gmail.com', 'P', '1999-06-01', 'S1/DIV', 'Panji asri T06', 'Mahasiswa', 'Menari lokasi untuk penanaman pohon', 'kunjungan langsung', '081936662555', 'perorangan', 'umum', 1, '2025-04-14', 'dipanggil');

--
-- Indexes for dumped tables
--

--
-- Indeks untuk tabel `antrian`
--
ALTER TABLE `antrian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_tanggal_jenis_status` (`tanggal`,`jenis`,`status`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `antrian`
--
ALTER TABLE `antrian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=43;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
