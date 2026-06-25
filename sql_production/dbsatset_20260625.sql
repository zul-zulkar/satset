-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1:3306
-- Waktu pembuatan: 25 Jun 2026 pada 02.44
-- Versi server: 8.4.7-7
-- Versi PHP: 8.1.34

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Basis data: `dbsatset`
--

-- --------------------------------------------------------

--
-- Struktur dari tabel `absensi_piket`
--

CREATE TABLE `absensi_piket` (
  `id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `jam_masuk` datetime DEFAULT NULL,
  `jam_keluar` datetime DEFAULT NULL,
  `lat_masuk` decimal(10,8) DEFAULT NULL,
  `lng_masuk` decimal(11,8) DEFAULT NULL,
  `lat_keluar` decimal(10,8) DEFAULT NULL,
  `lng_keluar` decimal(11,8) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `absensi_piket`
--

INSERT INTO `absensi_piket` (`id`, `pegawai_id`, `tanggal`, `jam_masuk`, `jam_keluar`, `lat_masuk`, `lng_masuk`, `lat_keluar`, `lng_keluar`) VALUES
(2, 11, '2026-04-07', '2026-04-07 07:30:00', '2026-04-07 16:43:11', -8.11528650, 115.08664908, 0.00000000, 0.00000000),
(3, 15, '2026-04-06', '2026-04-06 07:30:00', '2026-04-06 15:30:00', -8.11528650, 115.08664908, -8.11528650, 115.08664908),
(4, 11, '2026-04-06', '2026-04-06 07:30:00', '2026-04-06 15:30:00', -8.11528650, 115.08664908, -8.11528650, 115.08664908),
(5, 15, '2026-04-07', '2026-04-07 07:30:00', '2026-04-07 16:42:26', -8.11528650, 115.08664908, -8.11532836, 115.08665632),
(6, 11, '2026-04-09', '2026-04-09 16:39:58', '2026-04-09 16:40:00', -8.11536930, 115.08665565, -8.11536930, 115.08665565),
(8, 35, '2026-04-13', '2026-04-13 07:43:39', '2026-04-13 16:15:46', -8.11502326, 115.08684351, -8.11531852, 115.08665154),
(9, 15, '2026-04-13', '2026-04-13 07:45:03', '2026-04-13 16:15:55', -8.11531601, 115.08664991, -8.11531852, 115.08665154),
(10, 15, '2026-04-14', '2026-04-14 07:50:31', '2026-04-14 17:18:06', -8.11532337, 115.08665036, -8.11519626, 115.08682259),
(11, 35, '2026-04-14', '2026-04-14 07:50:47', '2026-04-14 17:18:05', -8.11530571, 115.08664981, -8.11530674, 115.08664718),
(12, 15, '2026-04-15', '2026-04-15 07:35:27', '2026-04-15 16:02:30', -8.11528575, 115.08662773, -8.11528570, 115.08663385),
(13, 35, '2026-04-15', '2026-04-15 07:44:54', '2026-04-15 16:02:11', -8.11528570, 115.08663385, -8.11528570, 115.08663385),
(14, 15, '2026-04-16', '2026-04-16 07:54:52', '2026-04-16 16:15:41', -8.11528570, 115.08663385, -8.11519391, 115.08681806),
(15, 35, '2026-04-16', '2026-04-16 07:55:11', '2026-04-16 16:26:44', -8.11528570, 115.08663385, -8.11528570, 115.08663385),
(16, 35, '2026-04-17', '2026-04-17 08:54:55', '2026-04-17 17:45:56', -8.11509480, 115.08665995, -8.11511588, 115.08673279),
(17, 15, '2026-04-17', '2026-04-17 08:55:24', '2026-04-17 17:45:59', -8.11510541, 115.08665277, -8.11511703, 115.08672232),
(18, 11, '2026-04-20', '2026-04-20 08:04:41', '2026-04-20 16:24:41', -8.11528570, 115.08663385, -8.11528575, 115.08662773),
(19, 14, '2026-04-20', '2026-04-20 08:05:15', '2026-04-20 16:24:22', -8.11528575, 115.08662773, -8.11528575, 115.08662773),
(20, 14, '2026-04-21', '2026-04-21 07:53:38', '2026-04-21 16:32:12', -8.11528575, 115.08662773, -8.11528575, 115.08662773),
(21, 11, '2026-04-21', '2026-04-21 07:53:57', '2026-04-21 16:23:03', -8.11528617, 115.08664050, -8.11528575, 115.08662773),
(22, 14, '2026-04-22', '2026-04-22 08:02:30', '2026-04-22 16:49:08', -8.11528575, 115.08662773, -8.11528570, 115.08663385),
(23, 11, '2026-04-22', '2026-04-22 08:02:58', '2026-04-22 16:48:48', -8.11528575, 115.08662773, -8.11528575, 115.08662773),
(24, 11, '2026-04-23', '2026-04-23 07:35:32', '2026-04-23 16:11:32', -8.11528575, 115.08662773, -8.11528570, 115.08663385),
(25, 14, '2026-04-23', '2026-04-23 07:35:53', '2026-04-23 16:11:15', -8.11528570, 115.08663385, -8.11528575, 115.08662773),
(26, 28, '2026-04-28', '2026-04-28 07:58:51', '2026-04-28 16:26:14', -8.11527517, 115.08664685, -8.11528570, 115.08663385),
(27, 28, '2026-04-29', '2026-04-29 07:53:20', '2026-04-29 16:03:53', -8.11528617, 115.08664050, -8.11528570, 115.08663385),
(28, 13, '2026-04-29', '2026-04-29 09:19:45', '2026-04-29 17:13:05', -8.11513609, 115.08672988, -8.11528575, 115.08662773),
(29, 13, '2026-04-30', '2026-04-30 07:49:26', '2026-04-30 17:09:40', -8.11510918, 115.08670691, -8.11528575, 115.08662773),
(30, 28, '2026-04-30', '2026-04-30 15:46:33', '2026-04-30 15:46:51', -8.11541729, 115.08663905, -8.11541729, 115.08663905),
(31, 29, '2026-05-04', '2026-05-04 10:50:21', '2026-05-04 18:53:44', -8.11528500, 115.08662400, -8.11528650, 115.08663150),
(32, 19, '2026-05-04', '2026-05-04 12:27:50', NULL, -8.11528575, 115.08662773, NULL, NULL),
(33, 19, '2026-05-05', '2026-05-05 08:08:30', '2026-05-05 18:06:11', -8.11528500, 115.08662400, -8.11540717, 115.08665357),
(34, 29, '2026-05-05', '2026-05-05 08:36:35', '2026-05-05 18:05:51', -8.11528650, 115.08663150, -8.11542353, 115.08666992),
(35, 29, '2026-05-06', '2026-05-06 07:41:26', NULL, -8.11537735, 115.08665385, NULL, NULL),
(36, 19, '2026-05-06', '2026-05-06 07:43:18', '2026-05-06 17:52:02', -8.11534273, 115.08662882, -8.11538168, 115.08665335),
(37, 29, '2026-05-07', '2026-05-07 07:29:11', NULL, -8.11550311, 115.08664911, NULL, NULL),
(38, 29, '2026-05-08', '2026-05-08 09:07:09', '2026-05-08 16:11:21', 0.00000000, 0.00000000, 0.00000000, 0.00000000),
(39, 19, '2026-05-08', '2026-05-08 09:14:12', '2026-05-08 16:44:15', 0.00000000, 0.00000000, 0.00000000, 0.00000000),
(40, 29, '2026-05-11', '2026-05-11 07:30:06', '2026-05-11 19:05:46', -8.11543930, 115.08663956, -8.11538536, 115.08665472),
(41, 19, '2026-05-11', '2026-05-11 07:30:24', '2026-05-11 19:05:26', -8.11545987, 115.08662362, -8.11538536, 115.08665472),
(42, 19, '2026-05-12', '2026-05-12 07:42:44', '2026-05-12 18:12:17', -8.11543930, 115.08663956, -8.11546608, 115.08664391),
(43, 29, '2026-05-12', '2026-05-12 07:43:28', '2026-05-12 18:11:50', -8.11545987, 115.08662362, -8.11548139, 115.08664867),
(44, 19, '2026-05-13', '2026-05-13 07:57:07', '2026-05-13 19:21:41', -8.11539349, 115.08660451, -8.11539279, 115.08661889),
(45, 29, '2026-05-13', '2026-05-13 07:57:27', '2026-05-13 19:21:25', -8.11543913, 115.08664985, -8.11539279, 115.08661889),
(48, 18, '2026-05-18', '2026-05-18 15:39:09', '2026-05-18 15:39:13', -8.11531867, 115.08663600, -8.11531867, 115.08663600),
(49, 20, '2026-05-18', '2026-05-18 15:40:42', '2026-05-18 15:40:43', -8.11534516, 115.08664936, -8.11534516, 115.08664936),
(51, 14, '2026-01-05', '2026-01-05 07:42:14', '2026-01-05 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(53, 20, '2026-05-19', '2026-05-19 07:47:23', NULL, -8.11511330, 115.08677460, NULL, NULL),
(54, 14, '2026-01-12', '2026-01-12 07:42:16', '2026-01-12 07:42:17', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(55, 18, '2026-05-19', '2026-05-19 07:49:05', '2026-05-19 16:07:56', -8.11542377, 115.08665561, -8.11526788, 115.08662277),
(56, 17, '2026-01-26', '2026-01-26 07:42:14', '2026-01-26 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(57, 17, '2026-02-02', '2026-02-02 07:42:14', '2026-01-26 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(58, 16, '2026-01-12', '2026-01-12 07:42:16', '2026-01-12 07:42:17', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(59, 16, '2026-01-19', '2026-01-19 07:42:16', '2026-01-19 07:42:17', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(60, 15, '2026-01-19', '2026-01-19 07:42:16', '2026-01-19 07:42:17', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(61, 15, '2026-01-26', '2026-01-26 07:42:16', '2026-01-26 07:42:17', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(62, 35, '2026-01-05', '2026-01-05 07:42:14', '2026-01-05 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(63, 35, '2026-02-09', '2026-02-09 07:42:14', '2026-02-09 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(64, 35, '2026-02-16', '2026-02-16 07:42:14', '2026-02-16 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(65, 11, '2026-02-16', '2026-02-16 07:42:14', '2026-02-16 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(66, 11, '2026-02-23', '2026-02-23 07:42:14', '2026-02-23 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(67, 20, '2026-02-02', '2026-02-02 07:42:14', '2026-02-02 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(68, 20, '2026-02-09', '2026-02-09 07:42:14', '2026-02-09 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(69, 20, '2026-03-02', '2026-03-02 07:42:14', '2026-03-02 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(70, 19, '2026-02-23', '2026-02-23 07:42:14', '2026-02-23 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(71, 19, '2026-03-02', '2026-03-02 07:42:14', '2026-03-02 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(72, 13, '2026-03-09', '2026-03-09 07:42:14', '2026-03-09 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(73, 13, '2026-03-16', '2026-03-16 07:42:14', '2026-03-16 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(74, 18, '2026-03-09', '2026-03-09 07:42:14', '2026-03-09 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(75, 18, '2026-03-30', '2026-03-30 07:42:14', '2026-03-30 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(76, 28, '2026-03-16', '2026-03-16 07:42:14', '2026-03-16 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(77, 28, '2026-03-23', '2026-03-23 07:42:14', '2026-03-23 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(78, 29, '2026-03-30', '2026-03-30 07:42:14', '2026-03-30 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(79, 29, '2026-03-23', '2026-03-23 07:42:14', '2026-03-23 07:42:15', -8.11532860, 115.08665281, -8.11532860, 115.08665281),
(80, 20, '2026-05-20', '2026-05-20 09:22:01', NULL, -8.11525890, 115.08657503, NULL, NULL),
(81, 18, '2026-05-20', '2026-05-20 16:55:14', '2026-05-20 16:55:43', -8.11528056, 115.08662690, -8.11528056, 115.08662690),
(82, 20, '2026-05-21', '2026-05-21 07:46:57', '2026-05-21 20:20:39', -8.11511180, 115.08673440, -8.11513400, 115.08674920),
(83, 18, '2026-05-21', '2026-05-21 07:51:45', NULL, -8.11526788, 115.08662277, NULL, NULL),
(84, 20, '2026-05-25', '2026-05-25 07:20:36', NULL, -8.11509410, 115.08675980, NULL, NULL),
(85, 13, '2026-05-25', '2026-05-25 07:48:47', NULL, -8.11507310, 115.08673140, NULL, NULL),
(86, 20, '2026-05-26', '2026-05-26 06:49:05', NULL, -8.11511220, 115.08635290, NULL, NULL),
(87, 13, '2026-05-26', '2026-05-26 08:11:12', NULL, -8.11511904, 115.08676468, NULL, NULL),
(88, 29, '2026-06-08', '2026-06-08 14:40:04', NULL, -8.11531525, 115.08662982, NULL, NULL),
(89, 16, '2026-06-09', '2026-06-09 07:40:28', '2026-06-09 18:20:08', -8.11531237, 115.08661819, -8.11534429, 115.08661946),
(90, 29, '2026-06-09', '2026-06-09 07:40:55', '2026-06-09 18:19:43', -8.11531724, 115.08662867, -8.11533120, 115.08662004),
(91, 16, '2026-06-10', '2026-06-10 07:01:28', '2026-06-10 16:12:00', -8.11574900, 115.08645380, -8.11525156, 115.08663505),
(92, 29, '2026-06-10', '2026-06-10 07:49:23', NULL, -8.11532413, 115.08662183, NULL, NULL),
(93, 16, '2026-06-12', '2026-06-12 07:53:05', NULL, 0.00000000, 0.00000000, NULL, NULL),
(94, 14, '2026-06-22', '2026-06-22 08:02:03', NULL, -8.11529056, 115.08663872, NULL, NULL),
(95, 29, '2026-06-22', '2026-06-22 08:02:33', NULL, -8.11529438, 115.08663577, NULL, NULL),
(96, 14, '2026-06-23', '2026-06-23 07:46:32', NULL, -8.11525983, 115.08667026, NULL, NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `antrian`
--

CREATE TABLE `antrian` (
  `id` int NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` mediumtext COLLATE utf8mb4_unicode_ci,
  `jk` enum('L','P') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `jumlah_orang` int DEFAULT NULL,
  `keperluan` mediumtext COLLATE utf8mb4_unicode_ci,
  `lahir` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pendidikan` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `kelompok_umur` varchar(25) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `alamat` mediumtext COLLATE utf8mb4_unicode_ci,
  `pekerjaan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pemanfaatan_data` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_dibutuhkan` mediumtext COLLATE utf8mb4_unicode_ci,
  `jenis_pelayanan` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `data_yang_diperlukan` mediumtext COLLATE utf8mb4_unicode_ci,
  `metode` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `telepon` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `instansi` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis` enum('umum','disabilitas','whatsapp','surat') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `nomor` int NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('menunggu','dipanggil') COLLATE utf8mb4_unicode_ci DEFAULT 'menunggu',
  `kunjungan_pst` tinyint(1) NOT NULL DEFAULT '0',
  `token` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `token_pes` varchar(64) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `link_surat` varchar(500) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `antrian`
--

INSERT INTO `antrian` (`id`, `nama`, `email`, `jk`, `jumlah_orang`, `keperluan`, `lahir`, `pendidikan`, `kelompok_umur`, `alamat`, `pekerjaan`, `pemanfaatan_data`, `data_dibutuhkan`, `jenis_pelayanan`, `data_yang_diperlukan`, `metode`, `telepon`, `instansi`, `jenis`, `nomor`, `tanggal`, `status`, `kunjungan_pst`, `token`, `token_pes`, `link_surat`, `created_at`) VALUES
(36, 'Komang Indah Wulandari Wismaya', 'komangindah@gmail.com', 'P', NULL, NULL, '1999-06-01', 'S1/DIV', NULL, 'Panji asri T06', 'Mahasiswa', NULL, NULL, NULL, 'Menari lokasi untuk penanaman pohon', 'kunjungan langsung', '081936662555', 'perorangan', 'umum', 1, '2025-04-14', 'dipanggil', 0, NULL, NULL, NULL, '2026-04-19 22:22:13'),
(41, 'Komang indah Wulandari wismaya', 'Wismayaewc@gmail.com', 'P', NULL, NULL, NULL, 'S1/DIV', NULL, NULL, 'Mahasiswa', NULL, NULL, NULL, 'Mencari lokasi untuk penanaman pohon', 'kunjungan langsung', '081936662555', 'perorangan', 'umum', 2, '2025-04-14', 'menunggu', 0, NULL, NULL, NULL, '2026-04-19 22:22:13'),
(42, 'Nazzala Qinthara Nafi', 'nzalthara@gmail.com', 'P', NULL, NULL, NULL, 'S1/DIV', NULL, NULL, 'ASN', NULL, NULL, NULL, 'vyig', 'kunjungan langsung', '09', 'ygyug', 'umum', 1, '2025-04-15', 'dipanggil', 0, NULL, NULL, NULL, '2026-04-19 22:22:13'),
(43, 'Ni Komang Ayu Mirah Senja Paramita', 'ayumirahayumirah@gmail.com', 'P', NULL, NULL, NULL, 'SLTA/Sederajat', '17 - 25 tahun', NULL, 'Pelajar/Mahasiswa', 'Tugas Sekolah/Tugas Kuliah', '[{\"data\":\"Kemiskinan\",\"tahun_dari\":1994,\"tahun_sampai\":2002},{\"data\":\"PDBR ADHK Menurut Lapangan Usaha\",\"tahun_dari\":1994,\"tahun_sampai\":2008},{\"data\":\"Tingkat Pengangguran Terbuka\",\"tahun_dari\":1994,\"tahun_sampai\":2006},{\"data\":\"Tingkat Partisipasi Angkatan Kerja\",\"tahun_dari\":1994,\"tahun_sampai\":2006}]', NULL, '1. Persentase penduduk miskin (tahun 1994-2002); 2. PDRB menurut lapangan usaha harga konstan (1994-2008); 3. Tingkat Pengangguran Terbuka/TPT (1994-2006); 4. Tingkat Partisipasi Angkatan Kerja/TPAK (1994-2006); 5. Distribusi penduduk menurut kelompok umur (1994-2006)', 'whatsapp', '08970834220', 'Universitas Udayana', 'whatsapp', 1, '2026-01-27', 'dipanggil', 0, 'a8642ec66d47529f596bf19fec878b6f', '9811472963632e3805e4952eaefaeb38', NULL, '2026-04-19 22:22:13'),
(44, 'Gusti doni', 'donigungalit@gmail.com', 'L', 1, 'Melakukan Klarifikasi Desil dari DTSEN', NULL, 'D4/S1', '35 - 44 tahun', NULL, 'Pegawai Swasta', 'Pemerintahan', NULL, NULL, 'Desil', 'kunjungan langsung', '081999838039', 'Perorangan', 'umum', 1, '2026-02-03', 'dipanggil', 0, NULL, NULL, NULL, '2026-04-19 22:22:13'),
(46, 'I Komang edi jayantika', 'edijayantikaikomang@gmail.com', 'L', 1, NULL, NULL, 'D4/S1', '35 - 44 tahun', NULL, 'Pegawai Swasta', 'Pemerintahan', '[{\"data\":\"Jumlah Penduduk Usia Produktif Desa Tigawasa\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', NULL, 'Data usia produktif penduduk desa tigawasa', 'kunjungan langsung', '087856301752', 'pemerintah desa tigawasa', 'umum', 1, '2026-02-04', 'dipanggil', 1, 'c10a7a9d1e6abd260b33dd7d83db5950', 'd678cca547ccf97eb875f7b404fd49a3', NULL, '2026-04-19 22:22:13'),
(47, 'I Gede Agus Supriawan', 'gede23agus@gmail.com', 'L', 1, NULL, NULL, 'D4/S1', '45 - 54 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Jumlah Bayi Berat Badan Lahir Rendah\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', NULL, 'data BBLR tahun 2024 dan tahun 2025', 'kunjungan langsung', '081915697344', 'Kepolisian Resort Kabupaten Buleleng', 'umum', 2, '2026-02-04', 'dipanggil', 1, '35b751efd04cf9bd3744e052253af73b', 'd0e4aa9311cb0bc684f07c12e9ebfb8e', NULL, '2026-04-19 22:22:13'),
(48, 'Hadi Setiadi', 'hadiadihadi2020@gmail.com', 'L', 1, NULL, NULL, 'D4/S1', '45 - 54 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Jumlah Koperasi di Kabupaten Buleleng\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', NULL, 'Data Angkatan Kerja', 'kunjungan langsung', '082338681355', 'DisdsgperinkopUKM Kab. Buleleng', 'umum', 1, '2026-02-10', 'dipanggil', 1, '9197f852415673ff4636d44ff29de05d', '0c853fc3b75c4cf680eae676df4cead4', NULL, '2026-04-19 22:22:13'),
(49, 'Ni Made Atmadewi', 'atmadewi.made@gmail.com', 'P', NULL, NULL, NULL, 'S2', '45 - 54 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Konsumsi Beras per Kapita\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', NULL, 'Konsumsi beras per kapita ', 'whatsapp', '08179732608', 'Dinas Pertanian, Ketahanan Pangan dan Perikanan', 'whatsapp', 1, '2026-02-23', 'menunggu', 0, '25d0a1e7da0d84fa6485628ec5d62678', '09be1e1b6d34155319e610e476da78f5', NULL, '2026-04-19 22:22:13'),
(51, 'Luh Putu Anggayanti', 'anggayanti04@gmail.com', 'P', NULL, NULL, NULL, 'SLTA/Sederajat', '17 - 25 tahun', NULL, 'Pelajar/Mahasiswa', 'Tugas Sekolah/Tugas Kuliah', '[{\"data\":\"Tingkat Pengangguran Terbuka\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', NULL, 'Tingkat Pengangguran Terbuka (TPT)', 'whatsapp', '085738244643', 'Universitas Pendidikan Ganesha', 'whatsapp', 3, '2026-02-23', 'menunggu', 0, '8039d983f3264012dc240df7cac3de7c', '49e2c4f7f7241333b487040abe1a2ef3', NULL, '2026-04-19 22:22:13'),
(52, 'Made Ayodhia Sari Widhi Nurjaya', 'ayodhiasariii@gmail.com', 'P', NULL, NULL, NULL, 'SLTA/Sederajat', '17 - 25 tahun', NULL, 'Pelajar/Mahasiswa', 'Tugas Sekolah/Tugas Kuliah', '[{\"data\":\"PDRB per kapita (ADHK dan ADHB)\",\"tahun_dari\":2020,\"tahun_sampai\":2024},{\"data\":\"Pertumbuhan ekonomi per tahun\",\"tahun_dari\":2020,\"tahun_sampai\":2024},{\"data\":\"Indeks Pembangunan Manusia\",\"tahun_dari\":2020,\"tahun_sampai\":2025},{\"data\":\"Data jumlah desa dan aksesibilitas jalan\",\"tahun_dari\":2024,\"tahun_sampai\":2025},{\"data\":\"Produk Domestik Regional Bruto\",\"tahun_dari\":2020,\"tahun_sampai\":2024},{\"data\":\"Tingkat Pengangguran Terbuka\",\"tahun_dari\":2020,\"tahun_sampai\":2025},{\"data\":\"Persentase Penduduk Miskin\",\"tahun_dari\":2020,\"tahun_sampai\":2025},{\"data\":\"Data Kependudukan Kabupaten Buleleng\",\"tahun_dari\":2020,\"tahun_sampai\":2025}]', NULL, 'Sehubungan dengan penelitian tersebut, saya memohon bantuan data berikut untuk periode 2020-2025:  1. PDRB per kapita (ADHK dan ADHB)  2. Pertumbuhan ekonomi per tahun  3. Indeks Pembangunan Manusia (IPM)  4. Data ketimpangan pembangunan (Indeks Williamson jika tersedia).  5. Data jumlah desa dan aksesibilitas jalan  6. Data sosial ekonomi per kecamatan', 'whatsapp', '08873356569', 'UPN Veteran Jawa Timur', 'whatsapp', 1, '2026-02-25', 'menunggu', 0, '611b73974af5fa984fc2f8da435ed150', 'cac4802af45d99712910426a86be88ff', NULL, '2026-04-19 22:22:13'),
(59, 'Wibawa mahardika', 'komangtri50@gmail.com', 'L', NULL, NULL, NULL, 'D4/S1', '26 - 34 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Tingkat Partisipasi Angkatan Kerja\",\"tahun_dari\":2023,\"tahun_sampai\":2025}]', NULL, 'TPAK Kabupaten buleleng 2023-2025', 'whatsapp', '089686309413', 'Bappeda', 'whatsapp', 1, '2026-03-04', 'menunggu', 0, 'afb2b7502a0240c91833808842ed0501', 'f1e682f642167b97a112082aa6cde2cb', NULL, '2026-04-19 22:22:13'),
(60, 'Kristiani Widya Karo', 'kristianiwiwidya@gmail.com', 'P', 1, NULL, NULL, 'D4/S1', '26 - 34 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Jumlah Penduduk Per Kecamatan di Kabupaten Buleleng\",\"tahun_dari\":2026,\"tahun_sampai\":2026}]', NULL, 'Data kependudukan', 'kunjungan langsung', '082284352265', 'Dinas Lingkungan Hidup Provinsi Bali', 'umum', 1, '2026-03-05', 'menunggu', 1, '9c0ae836ee7ee94c667b64076aede854', 'a530ae03e19e7131bfb604e1e15f00bb', NULL, '2026-04-19 22:22:13'),
(61, 'Muzayyinul Ghufron', 'yiyin.guf@gmail.com', 'L', 1, NULL, NULL, 'D4/S1', '26 - 34 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Luas Tanam Pertanian Kabupaten Buleleng (Maret)\",\"tahun_dari\":2026,\"tahun_sampai\":2026}]', NULL, 'Data SP Palawija', 'kunjungan langsung', '085746126882', 'BPSBTPHBUN', 'umum', 1, '2026-03-10', 'menunggu', 1, '26e8b01b6d25bbec9b1d3c808dc110fd', '8c2395ce7306aff13269a9324bbc9a69', NULL, '2026-04-19 22:22:13'),
(62, 'Putu Dhio Agustina', 'dhioagst27@gmail.com', 'L', 1, NULL, NULL, 'SLTA/Sederajat', '17 - 25 tahun', NULL, 'Pelajar/Mahasiswa', 'Tugas Sekolah/Tugas Kuliah', '[{\"data\":\"Jumlah UMKM di Kabupaten Buleleng\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', NULL, 'Produksi Anggur Kabupaten Buleleng', 'kunjungan langsung', '085737279334', 'Universitas Pendidikan Ganesha', 'umum', 1, '2026-03-13', 'dipanggil', 1, 'aedd8135510dbcb9a8e83c445323728b', '22c5bb4641e6deb4b28932dc073daee5', NULL, '2026-04-19 22:22:13'),
(64, 'Ni Komang Ayu Mirah Senja Paramita', 'ayumirahayumirah@gmail.com', 'P', NULL, NULL, NULL, 'SLTA/Sederajat', '17 - 25 tahun', NULL, 'Pelajar/Mahasiswa', 'Tugas Sekolah/Tugas Kuliah', '[{\"data\":\"Distribusi Penduduk Berdasarkan Kelompok Umur\",\"tahun_dari\":1994,\"tahun_sampai\":2006}]', NULL, '1. Rata rata lama sekolah Buleleng periode tahun 2000â€“2010 2. Data jumlah penduduk miskin buleleng tahun 2000â€“2002. ', 'whatsapp', '08970834220', 'Universitas udayana', 'whatsapp', 1, '2026-03-27', 'menunggu', 0, 'e81cf0fb58cec51d1e51b5e6a69cef38', '56465f8599fc3c36e0be51b4a980d295', NULL, '2026-04-19 22:22:13'),
(66, 'Debby', 'debbyroundra@gmail.com', 'P', 3, 'Penawaran Kredit Syariah', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '082216936021', 'Bank Syariah Indonesia', 'umum', 2, '2026-03-30', 'menunggu', 0, '7cb548fcc2bb7cc488013d0a990fd550', NULL, NULL, '2026-04-19 22:22:13'),
(68, 'I Ketut Sudarma Yasa', 'sudarmayasa041981@gmail.com', 'L', 2, NULL, NULL, 'SLTA/Sederajat', '45 - 54 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Jumlah penduduk\",\"tahun_dari\":2025,\"tahun_sampai\":2025},{\"data\":\"Nilai Ekspor Kabupaten Buleleng\",\"tahun_dari\":2025,\"tahun_sampai\":2025},{\"data\":\"Nilai Impor Kabupaten Buleleng\",\"tahun_dari\":2025,\"tahun_sampai\":2025},{\"data\":\"Persentase Kemiskinan\",\"tahun_dari\":2025,\"tahun_sampai\":2025},{\"data\":\"Tingkat Pengangguran Terbuka\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', NULL, NULL, NULL, '081353535711', 'Kodim 1609/Buleleng', 'umum', 1, '2026-04-02', 'dipanggil', 1, 'ff1bb514f130f6a189533304179f42f3', '996804ddb6d46abe1ba1ff232a759800', NULL, '2026-04-19 22:22:13'),
(70, 'Rossyana', 'anarossy695@gmail.com', 'P', NULL, NULL, NULL, 'D4/S1', '17 - 25 tahun', NULL, 'Pelajar/Mahasiswa', 'Tugas Sekolah/Tugas Kuliah', '[{\"data\":\"Analisis Hasil Survei Kebutuhan Data BPS Tahun 2025.\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', NULL, NULL, NULL, '088217404207', 'Universitas Brawijaya', 'whatsapp', 0, '2026-04-06', 'dipanggil', 0, 'a30d002b173e510775eb7a14a8f708d4', '61fffaf4dc6b9c95a00fc0bde2d807fd', NULL, '2026-04-19 22:22:13'),
(71, 'I NYOMANAGUS SWADARMA ADNYANA', 'agus.swadarma.as@gmail.com', 'L', 1, NULL, NULL, 'D4/S1', '45 - 54 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Konsultasi metode survei penyusunan rata-rata lama menginap\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', NULL, NULL, NULL, '081999808644', 'Disbudpar', 'umum', 1, '2026-04-06', 'menunggu', 1, '2993dcd1262255ca005907829e8be97d', 'eedac5592fdd2dd67c810a7d9a8c040c', NULL, '2026-04-19 22:22:13'),
(72, 'Muzayyinul Ghufron', 'yiyin.guf@gmail.com', 'L', NULL, NULL, NULL, 'D4/S1', '26 - 34 tahun', NULL, 'ASN/TNI/Polri', 'Lainnya', '[{\"data\":\"Data Luas Tanaman Palawija\",\"tahun_dari\":2026,\"tahun_sampai\":2026}]', NULL, NULL, NULL, '085746126882', 'BPSBTPHBUN', 'whatsapp', 0, '2026-04-09', 'menunggu', 0, '62671910c571f042c81c49e51f77dae1', '5732c9360554f6ac10cf6ed8dc8ff5eb', NULL, '2026-04-19 22:22:13'),
(73, 'Putu Wijana', 'putuwijana375@gmail.com', 'L', 1, 'Meminta Solusi Kendala Teknis Groundcheck PLN', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '081139609209', 'Billman PLN ULP Singaraja', 'umum', 1, '2026-04-10', 'menunggu', 0, '65175010dce83a7b06e62368834af2bc', '45066af93faa11cb27b71ee27216d78c', NULL, '2026-04-19 22:22:13'),
(74, 'Putu Yasa', 'putuyasa0878@gmail.com', 'L', 1, 'Menemui Kepala BPS Kabupaten Buleleng', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '081936012993', 'BPS Kota Denpasar', 'umum', 1, '2026-04-13', 'dipanggil', 0, 'c68ac7b01ccfc4b734d18ef84f8508ec', NULL, NULL, '2026-04-19 22:22:13'),
(75, 'Komang dewi susanti', 'komangdewisusanti1292@gmail.com', 'P', 4, 'Penyerahan mahasiswa pkl', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '081936537277', 'Institut Mpu Kuturan', 'umum', 2, '2026-04-13', 'dipanggil', 0, '7e1d08cad1ef201d8b8c28d415f461d9', NULL, NULL, '2026-04-19 22:22:13'),
(76, 'Ketut Oky Intan Purnama Sari', 'okyintann@gmail.com', 'P', NULL, NULL, NULL, 'SLTA/Sederajat', '17 - 25 tahun', NULL, 'Pelajar/Mahasiswa', 'Tugas Sekolah/Tugas Kuliah', '[{\"data\":\"Data Hasil Panen Jagung\",\"tahun_dari\":2010,\"tahun_sampai\":2025}]', NULL, NULL, NULL, '083117386482', 'Universitas Pendidikan Ganesha', 'whatsapp', 1, '2026-04-13', 'menunggu', 1, '8aa2f24d7e581ad5a7e709fba25cba45', 'b335805196100e4c868dbcf031f62d60', NULL, '2026-04-19 22:22:13'),
(77, 'Putu Adelina Kartika Dewi', 'adelinakartika16@gmail.com', 'P', 1, 'Pembinaan Statistik Sektoral Bima Sakti Vol 1', NULL, 'S2', '26 - 34 tahun', NULL, 'Pegawai Swasta', 'Pemerintahan', '[{\"data\":\"Nilai Tukar Petani\",\"tahun_dari\":2024,\"tahun_sampai\":2025},{\"data\":\"Laju Pertumbuhan Lapangan Usaha Pertanian\",\"tahun_dari\":2024,\"tahun_sampai\":2025}]', NULL, NULL, NULL, '085792618594', 'Dinas Pertanian, Ketahanan Pangan dan Perikanan', 'whatsapp', 1, '2026-04-14', 'menunggu', 0, 'a582da11eb64042857525d42d731f93c', '86913d05b1d1fe60c5abebfd20ac8a96', NULL, '2026-04-19 22:22:13'),
(78, 'Dwi Novitasari', 'dwnovitasarii@gmail.com', 'P', NULL, NULL, NULL, 'D4/S1', '26 - 34 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Luas Tanam Talas\",\"tahun_dari\":2020,\"tahun_sampai\":2025},{\"data\":\"Produksi Talas\",\"tahun_dari\":2020,\"tahun_sampai\":2025}]', NULL, NULL, NULL, '081238108296', 'Dinas Pertanian Ketahanan Pangan dan Perikanan', 'whatsapp', 2, '2026-04-14', 'menunggu', 0, '8dddb72818a675910e0c0228a41d293c', '3a91db48988f28d1be7ce4b1439503f1', NULL, '2026-04-19 22:22:13'),
(79, 'Putu Sintia Himalia', '', 'P', 1, 'Rapat pembinaan statistik', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '085829221350', 'Dinas Kesehatan', 'umum', 1, '2026-04-16', 'dipanggil', 0, '20dd0baa37bebbb6dd0df8e3e7127117', NULL, NULL, '2026-04-19 22:22:13'),
(80, 'I Gede Widiartha', '', 'L', 1, 'Rapat Bimasakti', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '081703256119', 'Badan Perencanaan Pembangunan Daerah', 'umum', 2, '2026-04-16', 'dipanggil', 0, '48fa193977172f665633c69f85aad764', NULL, NULL, '2026-04-19 22:22:13'),
(81, 'I Gusti Ngurah Agung Sukrisna ', '', NULL, 1, 'Pembinaan Statistik Sektoral Bima Sakti Vol 1', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '081999264333', 'Dinas Komunikasi Informatika Persandian dan Statistik ', 'umum', 3, '2026-04-16', 'dipanggil', 0, '10592a24fd76d5565d5275a83e9f8101', NULL, NULL, '2026-04-19 22:22:13'),
(82, 'I Kadek Adi Ganes Warmadewa', 'ganesadi73@gmail.com', 'L', NULL, NULL, NULL, 'SLTA/Sederajat', '17 - 25 tahun', NULL, 'Pelajar/Mahasiswa', 'Tugas Sekolah/Tugas Kuliah', '[{\"data\":\"Jumlah Industri Mikro dan Kecil (UMKM)\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', NULL, NULL, NULL, '087821421653', 'Universitas Pendidikan Ganesha', 'whatsapp', 1, '2026-03-13', 'menunggu', 0, 'f9258beb1c236ffc570c73e2ab320574', 'dab4b2b6086f21bf797b92c17150b6db', NULL, '2026-04-19 22:22:13'),
(83, 'Kadek Dinda Pramestya', 'dindapramestya9@gmail.com', 'P', NULL, NULL, NULL, 'SLTA/Sederajat', '17 - 25 tahun', NULL, 'Pelajar/Mahasiswa', 'Tugas Sekolah/Tugas Kuliah', '[{\"data\":\"Indeks Pembangunan Manusia\",\"tahun_dari\":2024,\"tahun_sampai\":2024}]', NULL, NULL, NULL, '085737650619', 'Universitas Pendidikan Ganesha', 'whatsapp', 1, '2026-03-30', 'menunggu', 0, '4fb2268a488d7e8dea0b84ee244c446d', 'cdac009afeeea021da3b57646ca9aecd', NULL, '2026-04-19 22:22:13'),
(84, 'sintya rosalina', 'sintya.rosalina@gmail.com', 'P', NULL, NULL, NULL, 'S2', '26 - 34 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Proyeksi jumlah penduduk kab buleleng\",\"tahun_dari\":2024,\"tahun_sampai\":2024}]', NULL, NULL, NULL, '085732761747', 'Dinas PMDPPKB', 'whatsapp', 1, '2026-04-16', 'menunggu', 1, '54ea39b1d25d627f2ed5532e469b90e8', '05fc2225581001477874880bf41debd3', NULL, '2026-04-19 22:22:13'),
(85, 'Ngurah Eka Utama Putra', 'eka.utama@student.undiksha.ac.id', 'L', NULL, NULL, NULL, 'SLTA/Sederajat', '17 - 25 tahun', NULL, 'Pelajar/Mahasiswa', 'Tugas Sekolah/Tugas Kuliah', '[{\"data\":\"PDRB Menurut Lapanga Usaha\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', NULL, NULL, NULL, '081236113073', 'Universitas Pendidikan Ganesha', 'whatsapp', 2, '2026-03-30', 'menunggu', 0, '891c9689a9248cc13bbac798fafb4967', '472febb415cf6b7a09a65f3f4bfda0d1', NULL, '2026-04-19 22:22:13'),
(86, 'I Putu Yoga Arta Dana', 'iputuyogaartadana@gmail.com', 'L', NULL, NULL, NULL, 'SLTA/Sederajat', '17 - 25 tahun', NULL, 'Pelajar/Mahasiswa', 'Tugas Sekolah/Tugas Kuliah', '[{\"data\":\"Jumlah UMKM Kabupaten Buleleng\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', NULL, NULL, NULL, '087752384983', 'Universitas Pendidikan Ganesha', 'whatsapp', 2, '2026-03-13', 'menunggu', 0, '548dfc75dd67fca1983738c2bc7f9247', 'e9aea122d663187e2d16c3092730c9e3', NULL, '2026-04-19 22:22:13'),
(87, 'Sucipto', 'dinkes@bulelengkab.go.id', 'L', NULL, NULL, NULL, 'S2', '55 - 65 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Jumlah Penduduk Menurut Kecamatan di Kabupaten Buleleng\",\"tahun_dari\":2025,\"tahun_sampai\":2025},{\"data\":\"Jumlah Penduduk Menurut Jenis Kelamin di Kabupaten Buleleng\",\"tahun_dari\":2025,\"tahun_sampai\":2025},{\"data\":\"Jumlah Rumah Tangga Menurut Kecamatan di Kabupaten Buleleng\",\"tahun_dari\":2025,\"tahun_sampai\":2025},{\"data\":\"Persentase Penduduk berdasarkan Tingkat Pendidikan Tertinggi yang Berhasil  Ditamatkan di Kabupaten Buleleng\",\"tahun_dari\":2025,\"tahun_sampai\":2025},{\"data\":\"Persentase Penduduk Usia 15 Tahun ke Atas yang Melek Huruf di Kabupaten Buleleng\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', NULL, NULL, NULL, '+623622178900', 'Dinas Kesehatan Kabupaten Buleleng', 'surat', 1, '2026-02-19', 'menunggu', 0, '103166b1638fe52c28bad0b235697476', '97ee81712b641b6a47d5ba44df0f0eb8', 'https://drive.google.com/file/d/1HZB6AgxrhupMN9p4yv4hBxyf7Mg2POVZ/view?usp=sharing', '2026-04-19 22:22:13'),
(88, 'I Nyoman Wisandika', 'disbudpar@bulelengkab.go.id', 'L', NULL, NULL, NULL, 'S2', '55 - 65 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Nilai Ekonomi Kreatif Kabupaten Buleleng Per KBLI\",\"tahun_dari\":2026,\"tahun_sampai\":2026}]', NULL, NULL, NULL, '036221342', 'Dinas Kebudayaan dan Pariwisata Kabupaten Buleleng', 'surat', 1, '2026-03-13', 'menunggu', 0, '19ce3098b8c2d41c9b8c4c5a62e9503a', '1b127b25d3f6f7072b20e0f89f619803', 'https://drive.google.com/file/d/1dTTTnOfkwiO8XKEFF2aZqk5RsB6rJ1yD/view?usp=drive_link', '2026-04-19 22:22:13'),
(92, 'Luh Putu Anggayanti', 'anggayanti04@gmail.com', 'P', NULL, NULL, NULL, 'SLTA/Sederajat', '17 - 25 tahun', NULL, 'Pelajar/Mahasiswa', 'Tugas Sekolah/Tugas Kuliah', '[{\"data\":\"Inflasi Bulanan Kota Singaraja\",\"tahun_dari\":2014,\"tahun_sampai\":2026}]', 'Permintaan Data', NULL, NULL, '085738244643', 'Undiksha', 'whatsapp', 1, '2026-04-20', 'menunggu', 0, '9fe7334634a2be675994a6b2ec614669', 'f3b442271be6383b9fb94b000b00b854', NULL, '2026-04-20 09:27:38'),
(93, 'I Wayan Wipra Satya Pradana', 'wiprasatya@gmail.com', 'L', 1, 'Permohonan Informasi Uji Kompetensi Pranata Komputer', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '081999552701', 'Dinas Tenaga Kerja, Transmigrasi dan ESDM Kab Buleleng', 'umum', 1, '2026-04-21', 'menunggu', 0, 'a13fd1f58e32c61f28e2b9895ac7469f', NULL, NULL, '2026-04-21 11:34:40'),
(94, 'Erny', '', 'P', 1, 'Promosi kredit BRI ke pegawai', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '087703138038', 'BRI', 'umum', 1, '2026-04-22', 'dipanggil', 0, '426c8cac56e1976df4a830ccbd566b67', NULL, NULL, '2026-04-22 11:48:21'),
(95, 'Basyaruddin', '', 'L', 2, 'Mengunjungi Ruang Tata Usaha', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '081233900058', 'Bank Syariah Indonesia', 'umum', 2, '2026-04-22', 'dipanggil', 0, '580a25b29aaf14bbfbe67e465b87c32b', NULL, NULL, '2026-04-22 11:51:15'),
(96, 'Made Widiada', '', 'L', 2, 'Penawaran Produk Asuransi Khusus Tenaga Sensus', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '085205105282', 'AJB Bumiputera 1912 KC Singaraja ', 'umum', 1, '2026-04-23', 'menunggu', 0, '31825c7cbdfe06e74f152d5d5ac6a1fe', NULL, NULL, '2026-04-23 10:07:06'),
(97, 'Luh Erny hermawati', '', 'P', 2, 'Kunjungan kerja', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '087703138038', 'BRI', 'umum', 2, '2026-04-23', 'menunggu', 0, '7c833667603614f0e365753ab7a05589', NULL, NULL, '2026-04-23 14:07:03'),
(98, 'Revalina Ramadhani', 'revalinarmdhni07@gmail.com', 'P', NULL, NULL, NULL, 'D4/S1', '17 - 25 tahun', NULL, 'Pelajar/Mahasiswa', 'Tugas Sekolah/Tugas Kuliah', '[{\"data\":\"data isu sosial\",\"tahun_dari\":2020,\"tahun_sampai\":2026}]', 'Permintaan Data', NULL, NULL, '088291751645', 'Universitas Udayana', 'whatsapp', 1, '2026-04-29', 'menunggu', 1, '8244caf08d558beebe8dfb84368b321e', 'cb377535ab2eb64424a8373aca973f3a', NULL, '2026-04-29 14:06:59'),
(99, 'Komang Devi Suartami', '', 'P', 1, 'Bri', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '08133717817', 'BRI', 'umum', 1, '2026-04-29', 'menunggu', 0, 'dabc57bafb53e959f4ad4884a7026986', NULL, NULL, '2026-04-29 16:21:07'),
(101, 'Kadek Ayu Dwipa', '11@gmail.com', 'P', 2, 'Pelayanan Statistik Terpadu', NULL, 'SLTA/Sederajat', '17 - 25 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"PDRB Kabupaten\\/Kota\",\"tahun_dari\":2025,\"tahun_sampai\":2025},{\"data\":\"Kepadatan Penduduk\",\"tahun_dari\":2025,\"tahun_sampai\":2025},{\"data\":\"Pendapatan Perkapita\",\"tahun_dari\":2025,\"tahun_sampai\":2025},{\"data\":\"Kepadatan Penduduk\",\"tahun_dari\":2025,\"tahun_sampai\":2025},{\"data\":\"Mata Pencaharian Utama\",\"tahun_dari\":2025,\"tahun_sampai\":2025},{\"data\":\"Jumlah Penduduk\",\"tahun_dari\":2025,\"tahun_sampai\":2025},{\"data\":\"Rasio dan Kategori Kapasitas Fiskal\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', 'Permintaan Data', NULL, NULL, 'Dinas kesehatan ', 'Dinas kesehatan ', 'umum', 1, '2026-04-30', 'dipanggil', 1, '261b952372acb30fb062b40bd91bef80', '151484ac926cb7320ca3cc7302152cfa', NULL, '2026-04-30 09:54:36'),
(102, 'Revalina Ramadhani', '', 'P', 5, 'permintaan data untuk tugas mata kuliah statistika sosial', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '088291751645', 'Universitas Udayana', 'umum', 2, '2026-04-30', 'dipanggil', 0, '61e5ee4fab44d734654338876b5d6bb1', NULL, NULL, '2026-04-30 11:03:32'),
(103, 'Ketut Widiasa Sangku SH ', '', 'L', 1, 'Untuk mendapat bantuan PKH saat ini ,setelah koordinasi dengan pihak Lurah yg menentukan dari BPS ,TKS', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '081236068165', 'lowyer /pengacara ', 'umum', 3, '2026-04-30', 'dipanggil', 0, '473aab8a4016943bb4fc1b2c7eb1f5f6', NULL, NULL, '2026-04-30 11:16:07'),
(106, 'Nyoman Doddy Darmawan', 'doddydarmawan08306@gmail.com', 'L', NULL, NULL, NULL, 'S2', '55 - 65 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Data PDRB harga konstan 2025 untuk pertanian, perkebunan dan perikanan dengan angkanya\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', 'Permintaan Data', NULL, NULL, '087769280987', 'Bappeda Kabupaten Buleleng', 'whatsapp', 1, '2026-05-04', 'menunggu', 1, '9e76d4e09b9d89f78e752051c6ff92ee', '614266e2fdd24c7660b638565eb8b0ac', NULL, '2026-05-04 11:52:23'),
(107, 'Yayan Sutrisna ', 'sutrisnayayan070@gmail.com', 'L', 1, 'Pelayanan Statistik Terpadu', NULL, 'D4/S1', '55 - 65 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Data kemiskinan\",\"tahun_dari\":2024,\"tahun_sampai\":2026}]', 'Permintaan Data', NULL, NULL, '081337586386', 'Dinas sosial ', 'umum', 1, '2026-05-06', 'dipanggil', 1, 'f38b18a4e5deb6f1d1510a21ab6231f1', NULL, NULL, '2026-05-06 10:13:43'),
(109, 'Nurrahmi ika aminy putri', '', 'P', 3, 'Penawaran kerjasama Pos', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '081280848073', 'Kantorpos', 'umum', 1, '2026-05-07', 'menunggu', 0, 'a3b8d9e300365b0c0e12321769367a63', NULL, NULL, '2026-05-07 09:51:36'),
(110, 'Ni Kadek Emi Antari', 'emiantari2018@gmail.com', 'P', NULL, NULL, NULL, 'D4/S1', '17 - 25 tahun', NULL, 'Pelajar/Mahasiswa', 'Tugas Sekolah/Tugas Kuliah', '[{\"data\":\"Data Pariwisata (tourists, tempat wisata, pendapatan dari wisata, dll.) beserta grafiknya\",\"tahun_dari\":2020,\"tahun_sampai\":2025}]', 'Permintaan Data', NULL, NULL, '089677425771', 'Universitas Udayana', 'whatsapp', 1, '2026-05-08', 'menunggu', 1, 'cd57b2f77e807938c6c3da5b9a0b5fd8', NULL, NULL, '2026-05-08 06:04:58'),
(112, 'Ketu suarnadi', 'ketutsuarnadi41@gmail.com', 'P', NULL, NULL, NULL, 'D4/S1', '17 - 25 tahun', NULL, 'Ternak babi', 'Lainnya', '[{\"data\":\"Data ekonomi masyarakat\",\"tahun_dari\":2016,\"tahun_sampai\":2026}]', 'Permintaan Data', NULL, NULL, '081330653463', '-', 'whatsapp', 2, '2026-05-11', 'menunggu', 1, '028c92983e9ecbe09bfa9d24d3060214', NULL, NULL, '2026-05-11 10:33:51'),
(113, 'Muzayyinul Ghufron', 'yiyin.guf@gmail.com', 'L', NULL, NULL, NULL, 'D4/S1', '26 - 34 tahun', NULL, 'ASN/TNI/Polri', 'Lainnya', '[{\"data\":\"Luas Tanaman Palawija\",\"tahun_dari\":2026,\"tahun_sampai\":2026}]', 'Permintaan Data', NULL, NULL, '085746126882', 'BPSBTPHBUN', 'whatsapp', 3, '2026-05-11', 'menunggu', 1, 'e1a2d2cd9da6eae39b1f709d1e90d658', NULL, NULL, '2026-05-11 13:35:54'),
(114, 'Grecya Indah Hutagalung', 'gresiahutagalung98@gmail.com', 'P', 1, 'Pelayanan Statistik Terpadu', NULL, 'D4/S1', '17 - 25 tahun', NULL, 'Pelajar/Mahasiswa', 'Penelitian', '[{\"data\":\"data jumlah penduduk Kabupaten Buleleng berdasarkan kelompok umur, khususnya kelompok usia 17–29 tahun yang merepresentasikan Generasi Z.\",\"tahun_dari\":2023,\"tahun_sampai\":2025}]', 'Permintaan Data', NULL, NULL, '08137060937', 'Universitas Pendidikan Ganesha (undiksha)', 'whatsapp', 1, '2026-05-20', 'menunggu', 0, 'ac01198f65385e6965f2926d29eac510', '392beb8d1f790c538143a672b1ea0ad9', NULL, '2026-05-15 14:11:31'),
(115, 'Luh Inda Dewi', 'gede.bayusuta@gmail.com', 'P', NULL, NULL, NULL, 'D1/D2/D3', '45 - 54 tahun', NULL, 'Wiraswasta', 'Lainnya', '[{\"data\":\"Selamat sore,\\n\\nPerkenalkan saya Luh Inda Dewi pemilik dari usaha penginapan Gede House Tejakula.  \\n\\nBeberapa waktu lalu kami mendapatkan pesan via WA menanyakan apakah benar kami Gede House Tejakula tanpa memperkenalkan diri dengan jelas dan mengirimkan formulir pengisian data kami melalui nomor WA pribadi.  Dan karena kami pernah mengalami penipuan serupa jadi kami blokir pesan tersebut segera.\\n\\nSetelah itu, dengan kontak yang sama orang yang mengaku petugas BPS tersebut memberikan review di laman google map kami dengan bintang 1 dan mengatakan bahwa owner kami tidak ramah dan memblokir kontaknya via WA, dan sudah kami jelaskan maksud kami memblokir dengan sengaja karena kami memang sengaja berhati-hati dalam memberikan apapun jenis data pribadi kami maupun usaha kami kepada pihak-pihak yang tidak bertanggung jawab.\\n\\nSetelah itu, karena keterbatasan kami sebagai pengelola penginapan, setelah kami baru sempat mengecek laman media sosial kami di Instagram, ternyata ada pesan masuk yang belum sempat kami baca sebelumnya yang terkirimkan di tanggal 23 April 2026, kami menerima pesan dengan tidak sopan dan seperti mengancam kami jika tidak memberikan data kami akan dilaporkan sebagai penolakan dari owner.  Sudah dengan jelas kami jelaskan bahwa pemblokiran kami lakukan dengan prinsip kehati-hatian kami. Jikalaupun pihak BPS ingin meminta data kami bisa melalui pihak desa ataupun kelian banjar yang ada di tempat kami, bukan dengan cara yang tidak sopan mengirimkan pesan seperti ini.\\n\\nAda beberapa hal yang ingin kami tanyakan kepada BPS Buleleng atas kejadian ini.\\n\\n1.  Apakah benar yang bersangkutan ini adalah staf atau petugas BPS Buleleng?\\n2. Jika memang benar, apakah prosedur meminta data usaha kami ataupun data pribadi kami dengan cara seperti ini? Menghubungi melalui WA tanpa perkenalan diri lengkap dengan surat tugas maupun surat ijin dari desa dan tanpa memberikan tanda pengenal dengan jelas?\\n3. ⁠Apakah dibenarkan, staf/petugas BPS Buleleng bisa memberikan review/ulasan di laman goodle usaha penduduk setempat seolah olah memberi kesan tidak bagus kepada usaha kecil seperti kami?\\n4. ⁠Apakah cara-cara seperti ini dijalankan juga kepada pihak-pihak lain yang memiliki usaha lain? karena sangat tidak nyaman sekali cara meminta data seperti ini. Apakah BPS Buleleng akan bertanggung jawab atas ketidaknyamanan ini?\\n5. ⁠Sedikit banyak kami mengerti tentang perlindungan data pribadi dan usaha kami jadi kami berhak menjaga data pribadi kami sebelum ada pihak pihak yang menghendaki permintaan data seperti ini.  Jika dengan cara seperti ini bisa termasuk ke dalam pencemaran nama baik karena telah memberikan review yang tidak sesuai dan menggangu kenyamanan kami karena terkesan mengancam pihak kami jika orang yang mengaku staf petugas BPS ini memang benar dari BPS Bulelang\\n\\nBerikut saya kirimkan foto screenshoot pesan yang disampaikan oleh orang yang mengaku staf atau petugas BPN Buleleng ini.\\n\\nTolong ditinjau kembali apakah ini memang benar-benar prosedur dari BPS Buleleng atau ini memang oknum penipuan?\\n\\nAtas perhatian nya kami ucapkan terimakasih.\",\"tahun_dari\":0,\"tahun_sampai\":0}]', 'Pengaduan', NULL, NULL, '08113892016', 'Gede house tejakula', 'whatsapp', 2, '2026-05-15', 'menunggu', 0, '22f13662d7b1563db666c7fa5929ed44', NULL, NULL, '2026-05-15 18:05:10'),
(117, 'Adirini loly', '', 'P', 2, 'Silahturahmi', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '081 22 9999 315', 'Azana Style Hotel Lovina Bali', 'umum', 1, '2026-05-18', 'menunggu', 0, 'f09ff4944878671e3faca5f44d6d7bf4', NULL, NULL, '2026-05-18 10:56:32'),
(120, 'Ida Bagus Ketut Wira Udiatmika', 'wiraudiatmika@gmail.com', 'L', NULL, NULL, NULL, 'D4/S1', '35 - 44 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Publikasi Sensus Pertanian Tahun 2020\",\"tahun_dari\":2020,\"tahun_sampai\":2024}]', 'Permintaan Data', NULL, NULL, '085338281987', 'Dinas Pertanian, Ketahanan Pangan dan Perikanan', 'whatsapp', 1, '2026-05-21', 'menunggu', 0, '15f457fab9556400b4cb4a88a428e1b7', '0540108b9a7fcaecea2f2522f121d603', NULL, '2026-05-21 13:35:39'),
(123, 'Ni Ketut Santi Dewi', '', 'P', 2, 'Izin penelitian tugas akhir', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '081288833966', 'Universitas Pendidikan Ganesha', 'umum', 1, '2026-05-25', 'menunggu', 0, 'a1b6e4f91de6f19c00d0dce381b7b806', NULL, NULL, '2026-05-25 13:25:02'),
(124, 'Ketut Oky Intan Purnama Sari', 'okyintann@gmail.com', 'P', NULL, NULL, NULL, 'SLTA/Sederajat', '17 - 25 tahun', NULL, 'Pelajar/Mahasiswa', 'Tugas Sekolah/Tugas Kuliah', '[{\"data\":\"Hasil Panen Jagung di Kabupaten Buleleng (Bulanan)\",\"tahun_dari\":2000,\"tahun_sampai\":2026},{\"data\":\"Luas Lahan Pertanian Jagung (bulan)\",\"tahun_dari\":2020,\"tahun_sampai\":2026},{\"data\":\"Produktivitas Jagung (bulan)\",\"tahun_dari\":2020,\"tahun_sampai\":2026}]', 'Permintaan Data', NULL, NULL, '083117386482', 'Universitas Pendidikan Ganesha', 'whatsapp', 1, '2026-06-05', 'menunggu', 0, '89571527c0547f061a4d4fcbd1ade56e', 'fef0a0ea1cdc0cca2cc80b77ed21f572', NULL, '2026-06-05 11:25:09');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pegawai`
--

CREATE TABLE `pegawai` (
  `id` int NOT NULL,
  `nama` varchar(255) COLLATE utf8mb4_general_ci NOT NULL,
  `username` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `password` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nip` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `nip_lama` varchar(30) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jabatan` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `status` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `pangkat` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `golongan` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kelas_jabatan` varchar(20) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pegawai`
--

INSERT INTO `pegawai` (`id`, `nama`, `username`, `password`, `nip`, `nip_lama`, `jabatan`, `status`, `pangkat`, `golongan`, `kelas_jabatan`) VALUES
(1, 'Gede Iwan Santika SST, M.M.', 'iwansantika', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '197807072002121002', '340016548', 'Kepala BPS Kabupaten Buleleng', 'PNS', 'Pembina', 'IV/a', '12'),
(2, 'I Ketut Ariasa, SE', 'ariasa', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '197306041994011001', '340014384', 'Kepala Subbagian Umum', 'PNS', 'Penata Tk. I', 'III/d', '9'),
(3, 'I Komang Ari Wijaya, SST, M.Agb', 'ariwijaya', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '198611152009021002', '340050119', 'Statistisi Ahli Madya', 'PNS', 'Pembina', 'IV/a', '11'),
(4, 'Ketut Ksama Putra SST., M.Si.', 'ksama', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '199004042013111001', '340056334', 'Statistisi Ahli Muda', 'PNS', 'Penata Tk. I', 'III/d', '9'),
(5, 'Alit Mahendra, SST', 'alit.mahendra', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '199204222014121001', '340056987', 'Statistisi Ahli Muda', 'PNS', 'Penata', 'III/c', '9'),
(6, 'Nyoman Subaktiyasa, SE', 'subaktiyasa', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '197106261994011001', '340014279', 'Statistisi Ahli Muda', 'PNS', 'Penata Tk. I', 'III/d', '9'),
(7, 'Ni Made Egy Wira Astuti, SST', 'nimade.astuti', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '199203302014122001', '340057158', 'Statistisi Ahli Muda', 'PNS', 'Penata Tk. I', 'III/d', '9'),
(8, 'Ni Made Pratiwi Pendit, S.Si., M.Si', 'madepratiwi', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '198609142009022007', '340051240', 'Statistisi Ahli Muda', 'PNS', 'Penata Tk. I', 'III/d', '9'),
(9, 'Raden Agus Setiyo Purnawan, SE', 'raden.agus', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '197008251994011001', '340014282', 'Statistisi Ahli Muda', 'PNS', 'Penata Tk. I', 'III/d', '9'),
(10, 'I Made Oka Suarjaya, SST, M.SE.', 'imadeoka', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '198809162010121001', '340054089', 'Statistisi Ahli Muda', 'PNS', 'Penata', 'III/c', '9'),
(11, 'Garinca Firgiana Santoso, S.Tr.Stat.', 'garinca.santoso', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '199902162022012001', '340060657', 'Statistisi Ahli Pertama', 'PNS', 'Penata Muda Tk. I', 'III/b', '8'),
(12, 'I Made Kariasa SST, M.SE.', 'imade.kariasa', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '199103292014121001', '340057091', 'Pranata Komputer Ahli Muda', 'PNS', 'Penata Tk. I', 'III/d', '9'),
(13, 'Rizq Taufiq Bahtiar Razendrya, S.Tr.Stat.', 'rizq.taufiq', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '200104132023021003', '340062033', 'Statistisi Ahli Pertama', 'PNS', 'Penata Muda', 'III/a', '8'),
(14, 'Kadek Suradnyana Wisnawa, SST', 'kadek.wisnawa', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '199405202017011001', '340057895', 'Statistisi Ahli Pertama', 'PNS', 'Penata Muda Tk. I', 'III/b', '8'),
(15, 'Amalia Susanti, S.Tr.Stat.', 'amalia.susanti', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '199901112022012001', '340060511', 'Statistisi Ahli Pertama', 'PNS', 'Penata Muda Tk. I', 'III/b', '8'),
(16, 'Ni Luh Putu Yayang Septia Ningsih, S.Tr.Stat.', 'yayangseptia', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '199812302022012001', '340060813', 'Statistisi Ahli Pertama', 'PNS', 'Penata Muda Tk. I', 'III/b', '8'),
(17, 'Novia Putri Lestari, S.Tr.Stat.', 'novia.putri', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '199811242022012002', '340060825', 'Statistisi Ahli Pertama', 'PNS', 'Penata Muda', 'III/a', '8'),
(18, 'Kharisma Pandu Utama, S.Tr.Stat.', 'pandu.utama', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '200110172023101002', '340062518', 'Statistisi Ahli Pertama', 'PNS', 'Penata Muda', 'III/a', '8'),
(19, 'Sita Dian Maretna, S.Tr.Stat.', 'sitadian', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '200003302023022001', '340062058', 'Pranata Komputer Ahli Pertama', 'PNS', 'Penata Muda', 'III/a', '8'),
(20, 'Erik Rihendri Candra Adifa, S.Tr.Stat.', 'erik.rihendri', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '199905202023021001', '340061762', 'Pranata Komputer Ahli Pertama', 'PNS', 'Penata Muda', 'III/a', '8'),
(21, 'Nyoman Pasek Susena, SE', 'paseksusena', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '198612232011011006', '340055165', 'Pranata Komputer Ahli Pertama', 'PNS', 'Penata', 'III/c', '8'),
(22, 'I Made Resdana', 'im.resdana', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '196807172006041017', '340018847', 'Statistisi Mahir', 'PNS', 'Penata Muda Tk. I', 'III/b', '7'),
(23, 'Made Sunika', 'made.sunika', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '197204012007101001', '340020560', 'Statistisi Mahir', 'PNS', 'Penata Muda Tk. I', 'III/b', '7'),
(24, 'I Gede Setya Budhi', 'gedesetyabudhi', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '197907052006041005', '340018381', 'Pranata Keuangan APBN Terampil', 'PNS', 'Pengatur Tk. I', 'II/d', '7'),
(25, 'I Nyoman Samiada', 'in.samiada', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '196902011989021001', '340012178', 'Fungsional Umum', 'PNS', 'Penata Muda Tk. I', 'III/b', '6'),
(26, 'Nyoman Redita', 'nyoman.redita', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '197009302006041007', '340018940', 'Statistisi Mahir', 'PNS', 'Penata Muda', 'III/a', '6'),
(27, 'I Putu Wardana Gelgel', 'gel.gel', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '197104072009011004', '340052241', 'Fungsional Umum', 'PNS', 'Penata Muda', 'III/a', '6'),
(28, 'Fadly Muhamad Akbar, S.Tr.Stat.', 'fadly.akbar', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '200103202024121004', '340063146', 'Fungsional Umum / Statistisi Ahli Pertama', 'PNS', 'Penata Muda', 'III/a', '8 (80%)'),
(29, 'Kasah Aisyah, A.Md.Stat.', 'kasah.aisyah', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '200105212024122001', '340063255', 'Fungsional Umum / Statistisi Pelaksana', 'PNS', 'Pengatur', 'II/c', '6 (80%)'),
(30, 'I Ketut Edi Mudarta', 'iketutedi-pppk', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '198307302025211036', '340064766', 'PPPK - Operator Layanan Operasional', 'PPPK', NULL, 'V', '5'),
(31, 'I Made Wiradana', 'imadewiradana-pppk', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '198507072025211071', '340064776', 'PPPK - Operator Layanan Operasional', 'PPPK', NULL, 'V', '5'),
(32, 'Ketut Swadana', 'ketutswadana-pppk', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '196911272025211007', '340064968', 'PPPK - Operator Layanan Operasional', 'PPPK', NULL, 'V', '5'),
(33, 'Putri Octaviana', 'putrioctavia-pppk', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '199510142025212054', '340065394', 'PPPK - Operator Layanan Operasional', 'PPPK', NULL, 'V', '5'),
(34, 'Ni Nengah Sekar', 'ninengahsekar-pppk', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '198507172025212050', '340065291', 'PPPK - Pengelola Umum Operasional', 'PPPK', NULL, 'III', '1'),
(35, 'Muhammad Zulkarnain, S.Tr.Stat', 'm.zulkarnain', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '199901152023021001', '340061945', 'Pranata Komputer Ahli Pertama', 'PNS', 'Penata Muda', 'III/a', '8'),
(36, 'Ardian Putra Wardana, S.Tr.Stat.', 'ardianputra', '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu', '200305222026031001', '340066129', 'Statistisi Ahli Pertama', 'CPNS', 'Penata Muda', 'III/a', '8');

-- --------------------------------------------------------

--
-- Struktur dari tabel `penghargaan_penilaian`
--

CREATE TABLE `penghargaan_penilaian` (
  `id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `bulan` tinyint UNSIGNED NOT NULL,
  `tahun` smallint UNSIGNED NOT NULL,
  `nilai_kinerja` tinyint UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `penghargaan_penilaian`
--

INSERT INTO `penghargaan_penilaian` (`id`, `pegawai_id`, `bulan`, `tahun`, `nilai_kinerja`, `updated_at`) VALUES
(1, 35, 1, 2026, 100, '2026-05-18 08:51:35'),
(6, 20, 5, 2026, 99, '2026-05-20 06:57:32'),
(7, 29, 5, 2026, 98, '2026-05-19 13:43:41'),
(8, 18, 5, 2026, 98, '2026-05-19 13:43:41'),
(9, 19, 5, 2026, 98, '2026-05-19 13:43:41');

-- --------------------------------------------------------

--
-- Struktur dari tabel `penghargaan_tim_penilai`
--

CREATE TABLE `penghargaan_tim_penilai` (
  `id` int NOT NULL,
  `pegawai_id` int NOT NULL,
  `bulan` tinyint UNSIGNED NOT NULL,
  `tahun` smallint UNSIGNED NOT NULL,
  `nama_penilai` varchar(50) COLLATE utf8mb4_general_ci NOT NULL,
  `nilai_kerja_sama` tinyint UNSIGNED DEFAULT NULL,
  `nilai_inovatif` tinyint UNSIGNED DEFAULT NULL,
  `nilai_penampilan` tinyint UNSIGNED DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `penghargaan_tim_penilai`
--

INSERT INTO `penghargaan_tim_penilai` (`id`, `pegawai_id`, `bulan`, `tahun`, `nama_penilai`, `nilai_kerja_sama`, `nilai_inovatif`, `nilai_penampilan`, `updated_at`) VALUES
(1, 35, 1, 2026, 'iwansantika', 95, 95, 95, '2026-05-18 08:51:53'),
(2, 20, 5, 2026, 'madekariasa', 98, 98, 95, '2026-05-19 13:00:31'),
(3, 29, 5, 2026, 'madekariasa', 98, 95, 96, '2026-05-19 13:00:31'),
(4, 18, 5, 2026, 'madekariasa', 96, 95, 95, '2026-05-19 13:00:32'),
(5, 19, 5, 2026, 'madekariasa', 97, 95, 96, '2026-05-19 13:00:32'),
(6, 29, 5, 2026, 'paseksusena', 97, 95, 97, '2026-05-19 13:46:47'),
(7, 19, 5, 2026, 'paseksusena', 97, 96, 97, '2026-05-19 13:46:47'),
(8, 20, 5, 2026, 'paseksusena', 97, 97, 95, '2026-05-19 13:46:47'),
(9, 18, 5, 2026, 'paseksusena', 96, 95, 96, '2026-05-19 13:46:47');

-- --------------------------------------------------------

--
-- Struktur dari tabel `penilaian`
--

CREATE TABLE `penilaian` (
  `id` int NOT NULL,
  `antrian_id` int DEFAULT NULL,
  `tanggal` date NOT NULL,
  `q1` tinyint UNSIGNED DEFAULT NULL,
  `q2` tinyint UNSIGNED DEFAULT NULL,
  `q3` tinyint UNSIGNED DEFAULT NULL,
  `q4` tinyint UNSIGNED DEFAULT NULL,
  `q5` tinyint UNSIGNED DEFAULT NULL,
  `q6` tinyint UNSIGNED DEFAULT NULL,
  `q7` tinyint UNSIGNED DEFAULT NULL,
  `q8` tinyint UNSIGNED DEFAULT NULL,
  `q9` tinyint UNSIGNED DEFAULT NULL,
  `q10` tinyint UNSIGNED DEFAULT NULL,
  `q11` tinyint UNSIGNED DEFAULT NULL,
  `q12` tinyint UNSIGNED DEFAULT NULL,
  `q13` tinyint UNSIGNED DEFAULT NULL,
  `q14` tinyint UNSIGNED DEFAULT NULL,
  `q15` tinyint UNSIGNED DEFAULT NULL,
  `q16` tinyint UNSIGNED DEFAULT NULL,
  `catatan` mediumtext COLLATE utf8mb4_unicode_ci,
  `submitted_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `penilaian`
--

INSERT INTO `penilaian` (`id`, `antrian_id`, `tanggal`, `q1`, `q2`, `q3`, `q4`, `q5`, `q6`, `q7`, `q8`, `q9`, `q10`, `q11`, `q12`, `q13`, `q14`, `q15`, `q16`, `catatan`, `submitted_at`) VALUES
(1, 68, '2026-04-02', 9, 9, 9, 9, 8, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 'Petugas ramah, respon cepat', '2026-04-01 20:43:52'),
(2, 69, '2026-04-02', 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, '', '2026-04-01 23:52:11'),
(3, 71, '2026-04-06', 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 'Petugas pelayanan BPS sangat profesional dan ramah terhadap melayani tamu, informasi yang diberikan sangat jelas dan mudah dipahami, dan untuk fasilitas sangat memadai', '2026-04-05 21:26:31'),
(4, 70, '2026-04-07', 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 'Pelayanan BPS secara online sudah sangat baik, informatif, dan mudah diakses. Informasi yang tersedia jelas serta membantu dalam memenuhi kebutuhan data.', NULL),
(5, 76, '2026-04-14', 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 8, '', NULL),
(6, 77, '2026-04-14', 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 'petugas sangat ramah, responsif\r\ntidak perlu menunggu lama\r\nsangat memuaskan', NULL),
(7, 78, '2026-04-14', 9, 9, 10, 8, 10, 9, 9, 9, 9, 10, 8, 10, 10, 10, 10, 10, 'Petugas ramah dan responsif', NULL),
(8, 72, '2026-04-15', 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 'Pelayanan responsif dan komunikatif', NULL),
(9, 46, '2026-04-16', 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, '', NULL),
(10, 47, '2026-04-16', 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, '', NULL),
(11, 82, '2026-04-16', 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 'Terimakasi kak, semoga kedepanya update terus', NULL),
(12, 83, '2026-04-16', 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 'Server bps sering down, mohon perbaikannya agar lebih efisien lagi', NULL),
(13, 85, '2026-04-16', 9, 9, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, '', NULL),
(14, 43, '2026-04-16', 9, 9, 9, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, '', NULL),
(15, 64, '2026-04-16', 9, 9, 9, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, '', NULL),
(16, 59, '2026-04-16', 10, 10, 9, 9, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, '', NULL),
(17, 60, '2026-04-16', 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, '', NULL),
(18, 48, '2026-04-16', 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, '', NULL),
(19, 49, '2026-04-16', 9, 10, 9, 9, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, '', NULL),
(20, 86, '2026-04-16', 9, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 'Pelayanannya sangat baik dan ramah', NULL),
(21, 62, '2026-04-16', 9, 10, 9, 9, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, '', NULL),
(22, 52, '2026-04-16', 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 'Tidak ada kritik dan saran karena pelayanan bagus', NULL),
(23, 51, '2026-04-16', 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, '', NULL),
(24, 61, '2026-04-16', 9, 10, 9, 9, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, '', NULL),
(25, 84, '2026-04-16', 9, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 'Sangat baik', NULL),
(26, 101, '2026-04-30', 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 'Petugas ramah, baik dan responsif dan data yg diminta sudah diberikan dan dijelaskan dengan baik juga.', '2026-04-30 11:31:27'),
(27, 98, '2026-04-30', 10, 9, 10, 9, 9, 10, 9, 10, 10, 10, 9, 10, 10, 10, 10, 10, 'sangat asik sekali dan sangat membantu dalam proses pengumpulan data', NULL),
(28, 105, '2026-05-04', 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, '', NULL),
(29, 92, '2026-05-25', 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, '', NULL),
(30, 124, '2026-06-19', 9, 9, 9, 8, 9, 9, 9, 8, 8, 8, 8, 9, 9, 9, 9, 8, '', NULL),
(31, 120, '2026-06-24', 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 'Petugas ramah dan responsif, data yang disajikan cepat dan sesuai. Terima kasih Tim BPS Kabupaten Buleleng', NULL),
(32, 106, '2026-06-24', 7, 7, 7, 7, 7, 7, 7, 8, 7, 7, 7, 7, 7, 7, 7, 7, 'Pelayanan baik secara online maupun tidak sudah memuaskan,', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `penilaian_data_item`
--

CREATE TABLE `penilaian_data_item` (
  `id` int NOT NULL,
  `penilaian_id` int NOT NULL,
  `nama_data` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tahun_dari` int DEFAULT NULL,
  `tahun_sampai` int DEFAULT NULL,
  `nilai` tinyint UNSIGNED DEFAULT NULL,
  `status_perolehan` enum('Ya, sesuai','Ya, tidak sesuai','Tidak diperoleh','Belum diperoleh') COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `untuk_perencanaan` enum('ya','tidak') COLLATE utf8mb4_unicode_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data untuk tabel `penilaian_data_item`
--

INSERT INTO `penilaian_data_item` (`id`, `penilaian_id`, `nama_data`, `tahun_dari`, `tahun_sampai`, `nilai`, `status_perolehan`, `untuk_perencanaan`) VALUES
(1, 1, 'Jumlah penduduk', 2025, 2025, 9, 'Ya, sesuai', 'ya'),
(2, 1, 'Nilai Ekspor Kabupaten Buleleng', 2025, 2025, 9, 'Ya, sesuai', 'ya'),
(3, 1, 'Nilai Impor Kabupaten Buleleng', 2025, 2025, 7, 'Ya, sesuai', 'ya'),
(4, 1, 'Persentase Kemiskinan', 2025, 2025, 9, 'Ya, sesuai', 'ya'),
(5, 1, 'Tingkat Pengangguran Terbuka', 2025, 2025, 9, 'Ya, sesuai', 'ya'),
(6, 2, 'aasasasasa', 2020, 2021, 10, 'Ya, sesuai', 'ya'),
(8, 3, 'Konsultasi metode survei penyusunan rata-rata lama menginap', 2025, 2025, 10, 'Ya, sesuai', 'ya'),
(9, 4, 'Analisis Hasil Survei Kebutuhan Data BPS Tahun 2025.', 2025, 2025, 10, 'Ya, sesuai', 'ya'),
(10, 5, 'Data Hasil Panen Jagung', 2010, 2025, 8, 'Ya, sesuai', 'ya'),
(11, 6, 'Nilai Tukar Petani', 2024, 2025, 10, 'Ya, sesuai', 'ya'),
(12, 6, 'Laju Pertumbuhan Lapangan Usaha Pertanian', 2024, 2025, 10, 'Ya, sesuai', 'ya'),
(13, 7, 'Luas Tanam Talas', 2020, 2025, 9, 'Ya, sesuai', 'ya'),
(14, 7, 'Produksi Talas', 2020, 2025, 9, 'Ya, sesuai', 'ya'),
(15, 8, 'Data Luas Tanaman Palawija', 2026, 2026, 9, 'Ya, sesuai', 'tidak'),
(16, 9, 'Jumlah Penduduk Usia Produktif Desa Tigawasa', 2025, 2025, 10, 'Ya, sesuai', 'ya'),
(17, 10, 'Jumlah Bayi Berat Badan Lahir Rendah', 2025, 2025, 10, 'Ya, sesuai', 'tidak'),
(18, 11, 'Jumlah Industri Mikro dan Kecil (UMKM)', 2025, 2025, 10, 'Ya, sesuai', 'tidak'),
(19, 12, 'Indeks Pembangunan Manusia', 2024, 2024, 10, 'Ya, sesuai', 'tidak'),
(20, 13, 'PDRB Menurut Lapanga Usaha', 2025, 2025, 10, 'Ya, sesuai', 'tidak'),
(21, 14, 'Kemiskinan', 1994, 2002, 10, 'Ya, sesuai', 'tidak'),
(22, 14, 'PDBR ADHK Menurut Lapangan Usaha', 1994, 2008, 10, 'Ya, sesuai', 'tidak'),
(23, 14, 'Tingkat Pengangguran Terbuka', 1994, 2006, 10, 'Ya, sesuai', 'tidak'),
(24, 14, 'Tingkat Partisipasi Angkatan Kerja', 1994, 2006, 10, 'Ya, sesuai', 'tidak'),
(25, 15, 'Distribusi Penduduk Berdasarkan Kelompok Umur', 1994, 2006, 10, 'Ya, sesuai', 'tidak'),
(26, 16, 'Tingkat Partisipasi Angkatan Kerja', 2023, 2025, 10, 'Ya, sesuai', 'ya'),
(27, 17, 'Jumlah Penduduk Per Kecamatan di Kabupaten Buleleng', 2025, 2025, 10, 'Ya, sesuai', 'ya'),
(28, 18, 'Jumlah Koperasi di Kabupaten Buleleng', 2025, 2025, 10, 'Ya, sesuai', 'ya'),
(29, 19, 'Konsumsi Beras per Kapita', 2025, 2025, 10, 'Ya, sesuai', 'ya'),
(30, 20, 'Jumlah UMKM Kabupaten Buleleng', 2025, 2025, 10, 'Ya, sesuai', 'tidak'),
(31, 21, 'Jumlah UMKM di Kabupaten Buleleng', 2025, 2025, 10, 'Ya, sesuai', 'ya'),
(32, 22, 'PDRB per kapita (ADHK dan ADHB)', 2020, 2024, 10, 'Ya, sesuai', 'tidak'),
(33, 22, 'Pertumbuhan ekonomi per tahun', 2020, 2024, 10, 'Ya, sesuai', 'tidak'),
(34, 22, 'Indeks Pembangunan Manusia', 2020, 2025, 10, 'Ya, sesuai', 'tidak'),
(35, 22, 'Data jumlah desa dan aksesibilitas jalan', 2024, 2025, 10, 'Ya, sesuai', 'tidak'),
(36, 22, 'Produk Domestik Regional Bruto', 2020, 2024, 10, 'Ya, sesuai', 'tidak'),
(37, 22, 'Tingkat Pengangguran Terbuka', 2020, 2025, 10, 'Ya, sesuai', 'tidak'),
(38, 22, 'Persentase Penduduk Miskin', 2020, 2025, 10, 'Ya, sesuai', 'tidak'),
(39, 22, 'Data Kependudukan Kabupaten Buleleng', 2020, 2025, 10, 'Ya, sesuai', 'tidak'),
(40, 23, 'Tingkat Pengangguran Terbuka', 2025, 2025, 10, 'Ya, sesuai', 'tidak'),
(41, 24, 'Luas Tanam Pertanian Kabupaten Buleleng (Maret)', 2026, 2026, 10, 'Ya, sesuai', 'ya'),
(42, 25, 'Proyeksi jumlah penduduk kab buleleng', 2024, 2024, 10, 'Ya, sesuai', 'ya'),
(57, 26, 'PDRB Kabupaten/Kota', 2025, 2025, 10, 'Ya, sesuai', 'ya'),
(58, 26, 'Kepadatan Penduduk', 2025, 2025, 10, 'Ya, sesuai', 'ya'),
(59, 26, 'Pendapatan Perkapita', 2025, 2025, 8, 'Belum diperoleh', 'ya'),
(60, 26, 'Kepadatan Penduduk', 2025, 2025, 10, 'Ya, sesuai', 'ya'),
(61, 26, 'Mata Pencaharian Utama', 2025, 2025, 10, 'Ya, sesuai', 'ya'),
(62, 26, 'Jumlah Penduduk', 2025, 2025, 10, 'Ya, sesuai', 'ya'),
(63, 26, 'Rasio dan Kategori Kapasitas Fiskal', 2025, 2025, 10, 'Ya, sesuai', 'ya'),
(64, 27, 'data isu sosial', 2020, 2026, 10, 'Ya, sesuai', 'tidak'),
(65, 28, 'Kependuduk', 2020, 2026, 9, 'Ya, sesuai', 'ya'),
(66, 29, 'Inflasi Bulanan Kota Singaraja', 2014, 2026, 10, 'Ya, sesuai', 'tidak'),
(67, 30, 'Hasil Panen Jagung di Kabupaten Buleleng (Bulanan)', 2000, 2026, 9, 'Ya, sesuai', 'ya'),
(68, 30, 'Luas Lahan Pertanian Jagung (bulan)', 2020, 2026, 9, 'Ya, sesuai', 'ya'),
(69, 30, 'Produktivitas Jagung (bulan)', 2020, 2026, 9, 'Ya, sesuai', 'ya'),
(70, 31, 'Publikasi Sensus Pertanian Tahun 2020', 2020, 2024, 10, 'Ya, sesuai', 'ya'),
(71, 32, 'Data PDRB harga konstan 2025 untuk pertanian, perkebunan dan perikanan dengan angkanya', 2025, 2025, 7, 'Ya, sesuai', 'ya');

-- --------------------------------------------------------

--
-- Struktur dari tabel `pes`
--

CREATE TABLE `pes` (
  `id` int NOT NULL,
  `antrian_id` int NOT NULL,
  `petugas_utama_id` int DEFAULT NULL,
  `kategori_instansi` varchar(100) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `kategori_instansi_lainnya` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `jenis_layanan` text COLLATE utf8mb4_general_ci,
  `sarana` text COLLATE utf8mb4_general_ci,
  `sarana_lainnya` varchar(255) COLLATE utf8mb4_general_ci DEFAULT NULL,
  `sentimen_kritik_saran` enum('negatif','normal','positif') COLLATE utf8mb4_general_ci DEFAULT NULL COMMENT '-1=negatif,0=normal,1=positif',
  `submitted_at` datetime DEFAULT NULL,
  `link_surat_balasan` varchar(500) COLLATE utf8mb4_general_ci DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pes`
--

INSERT INTO `pes` (`id`, `antrian_id`, `petugas_utama_id`, `kategori_instansi`, `kategori_instansi_lainnya`, `jenis_layanan`, `sarana`, `sarana_lainnya`, `sentimen_kritik_saran`, `submitted_at`, `link_surat_balasan`) VALUES
(1, 68, 35, 'TNI/Polri/BIN/Kejaksaan', '', '[\"Akses produk statistik pada Website BPS\"]', '[\"Pelayanan Statistik Terpadu (PST) datang langsung\",\"Website BPS \\/ AllStats BPS\"]', '', 'positif', '2026-04-12 18:56:35', NULL),
(2, 76, 35, 'Lembaga Penelitian & Pendidikan', '', '[\"Akses produk statistik pada Website BPS\",\"Konsultasi Statistik\"]', '[\"Website BPS \\/ AllStats BPS\",\"Aplikasi chat (WhatsApp, Telegram, ChatUs, dll.)\"]', '', 'normal', '2026-04-14 11:18:20', NULL),
(3, 77, 35, 'Pemerintah Daerah', '', '[\"Perpustakaan\",\"Akses produk statistik pada Website BPS\"]', '[\"Website BPS \\/ AllStats BPS\",\"Aplikasi chat (WhatsApp, Telegram, ChatUs, dll.)\"]', '', 'positif', '2026-04-14 14:46:21', NULL),
(4, 78, 35, 'Pemerintah Daerah', '', '[\"Akses produk statistik pada Website BPS\"]', '[\"Website BPS \\/ AllStats BPS\",\"Aplikasi chat (WhatsApp, Telegram, ChatUs, dll.)\"]', '', 'positif', '2026-04-14 15:55:05', NULL),
(5, 70, 15, 'Lembaga Penelitian & Pendidikan', '', '[\"Akses produk statistik pada Website BPS\"]', '[\"Aplikasi chat (WhatsApp, Telegram, ChatUs, dll.)\"]', '', 'positif', '2026-04-15 07:39:28', NULL),
(6, 71, 11, 'Pemerintah Daerah', '', '[\"Konsultasi Statistik\",\"Rekomendasi Kegiatan Statistik\"]', '[\"Pelayanan Statistik Terpadu (PST) datang langsung\"]', '', 'positif', '2026-04-15 07:42:38', NULL),
(7, 72, 35, 'Pemerintah Daerah', '', '[\"Konsultasi Statistik\"]', '[\"Pelayanan Statistik Terpadu (PST) datang langsung\",\"Aplikasi chat (WhatsApp, Telegram, ChatUs, dll.)\"]', '', 'positif', '2026-04-15 09:39:51', NULL),
(8, 46, 35, 'Pemerintah Daerah', '', '[\"Akses produk statistik pada Website BPS\",\"Konsultasi Statistik\"]', '[\"Pelayanan Statistik Terpadu (PST) datang langsung\",\"Website BPS \\/ AllStats BPS\"]', '', 'normal', '2026-04-16 10:55:01', NULL),
(9, 47, 35, 'TNI/Polri/BIN/Kejaksaan', '', '[\"Akses produk statistik pada Website BPS\"]', '[\"Pelayanan Statistik Terpadu (PST) datang langsung\",\"Website BPS \\/ AllStats BPS\"]', '', 'normal', '2026-04-16 11:00:53', NULL),
(10, 82, 35, 'Lembaga Penelitian & Pendidikan', '', '[\"Akses produk statistik pada Website BPS\"]', '[\"Pelayanan Statistik Terpadu (PST) datang langsung\",\"Website BPS \\/ AllStats BPS\"]', '', 'positif', '2026-04-16 11:08:30', NULL),
(11, 83, 35, 'Lembaga Penelitian & Pendidikan', '', '[\"Akses produk statistik pada Website BPS\"]', '[\"Pelayanan Statistik Terpadu (PST) datang langsung\",\"Website BPS \\/ AllStats BPS\"]', '', 'normal', '2026-04-16 11:24:43', NULL),
(12, 85, 35, 'Lembaga Penelitian & Pendidikan', '', '[\"Akses produk statistik pada Website BPS\",\"Konsultasi Statistik\"]', '[\"Pelayanan Statistik Terpadu (PST) datang langsung\",\"Website BPS \\/ AllStats BPS\"]', '', 'normal', '2026-04-16 11:39:12', NULL),
(13, 43, 35, 'Lembaga Penelitian & Pendidikan', '', '[\"Perpustakaan\",\"Konsultasi Statistik\"]', '[\"Aplikasi chat (WhatsApp, Telegram, ChatUs, dll.)\"]', '', 'normal', '2026-04-16 11:53:40', NULL),
(14, 64, 35, 'Lembaga Penelitian & Pendidikan', '', '[\"Konsultasi Statistik\"]', '[\"Website BPS \\/ AllStats BPS\",\"Aplikasi chat (WhatsApp, Telegram, ChatUs, dll.)\"]', '', 'normal', '2026-04-16 11:55:24', NULL),
(15, 59, 35, 'Pemerintah Daerah', '', '[\"Akses produk statistik pada Website BPS\"]', '[\"Pelayanan Statistik Terpadu (PST) datang langsung\",\"Website BPS \\/ AllStats BPS\"]', '', 'normal', '2026-04-16 12:00:04', NULL),
(16, 60, 35, 'Pemerintah Daerah', '', '[\"Akses produk statistik pada Website BPS\"]', '[\"Website BPS \\/ AllStats BPS\",\"Aplikasi chat (WhatsApp, Telegram, ChatUs, dll.)\"]', '', 'normal', '2026-04-16 12:06:49', NULL),
(17, 48, 35, 'Pemerintah Daerah', '', '[\"Akses produk statistik pada Website BPS\"]', '[\"Pelayanan Statistik Terpadu (PST) datang langsung\",\"Website BPS \\/ AllStats BPS\"]', '', 'normal', '2026-04-16 12:13:58', NULL),
(18, 49, 35, 'Pemerintah Daerah', '', '[\"Akses produk statistik pada Website BPS\"]', '[\"Website BPS \\/ AllStats BPS\",\"Aplikasi chat (WhatsApp, Telegram, ChatUs, dll.)\"]', '', 'normal', '2026-04-16 12:20:46', NULL),
(19, 86, 35, 'Pemerintah Daerah', '', '[\"Akses produk statistik pada Website BPS\"]', '[\"Pelayanan Statistik Terpadu (PST) datang langsung\",\"Website BPS \\/ AllStats BPS\"]', '', 'positif', '2026-04-16 13:39:03', NULL),
(20, 62, 35, 'Lembaga Penelitian & Pendidikan', '', '[\"Akses produk statistik pada Website BPS\"]', '[\"Pelayanan Statistik Terpadu (PST) datang langsung\",\"Website BPS \\/ AllStats BPS\"]', '', 'normal', '2026-04-16 13:48:23', NULL),
(21, 52, 35, 'Lembaga Penelitian & Pendidikan', '', '[\"Akses produk statistik pada Website BPS\",\"Konsultasi Statistik\"]', '[\"Website BPS \\/ AllStats BPS\",\"Aplikasi chat (WhatsApp, Telegram, ChatUs, dll.)\"]', '', 'positif', '2026-04-16 14:03:19', NULL),
(22, 51, 35, 'Lembaga Penelitian & Pendidikan', '', '[\"Akses produk statistik pada Website BPS\"]', '[\"Website BPS \\/ AllStats BPS\",\"Aplikasi chat (WhatsApp, Telegram, ChatUs, dll.)\"]', '', 'normal', '2026-04-16 14:13:59', NULL),
(23, 61, 16, 'Pemerintah Daerah', '', '[\"Konsultasi Statistik\"]', '[\"Pelayanan Statistik Terpadu (PST) datang langsung\"]', '', 'normal', '2026-04-16 14:19:50', NULL),
(24, 84, 13, 'Pemerintah Daerah', '', '[\"Akses produk statistik pada Website BPS\",\"Konsultasi Statistik\"]', '[\"Aplikasi chat (WhatsApp, Telegram, ChatUs, dll.)\"]', '', 'positif', '2026-04-17 09:44:46', NULL),
(25, 87, 35, 'Pemerintah Daerah', '', '[\"Akses produk statistik pada Website BPS\",\"Konsultasi Statistik\"]', '[\"Surat\\/E-mail\"]', '', 'normal', '2026-04-18 19:34:09', 'https://drive.google.com/file/d/1CMvDYsWl6EysspeOUsC4QZzBB5Gae6vz/view?usp=sharing'),
(26, 88, 35, 'Pemerintah Daerah', '', '[\"Konsultasi Statistik\"]', '[\"Surat\\/E-mail\"]', '', 'normal', '2026-04-18 19:38:03', 'https://drive.google.com/file/d/1zL4HQcU5CVYq14eSaB7wlgxxJyA6jC9A/view?usp=drive_link'),
(28, 114, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(29, 120, 20, 'Pemerintah Daerah', '', '[\"Akses produk statistik pada Website BPS\"]', '[\"Aplikasi chat (WhatsApp, Telegram, ChatUs, dll.)\"]', '', 'positif', '2026-06-24 15:06:11', NULL),
(30, 92, 14, 'Lembaga Penelitian & Pendidikan', '', '[\"Akses produk statistik pada Website BPS\"]', '[\"Aplikasi chat (WhatsApp, Telegram, ChatUs, dll.)\"]', '', 'normal', '2026-06-19 14:40:45', NULL),
(31, 98, 28, 'Lembaga Penelitian & Pendidikan', '', '[\"Perpustakaan\",\"Konsultasi Statistik\"]', '[\"Pelayanan Statistik Terpadu (PST) datang langsung\"]', '', 'positif', '2026-05-25 10:56:41', NULL),
(32, 101, 13, 'Pemerintah Daerah', '', '[\"Perpustakaan\",\"Konsultasi Statistik\"]', '[\"Pelayanan Statistik Terpadu (PST) datang langsung\"]', '', 'positif', '2026-05-25 11:05:39', NULL),
(33, 115, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL),
(34, 124, 35, 'Lembaga Penelitian & Pendidikan', '', '[\"Perpustakaan\"]', '[\"Aplikasi chat (WhatsApp, Telegram, ChatUs, dll.)\"]', '', 'normal', '2026-06-19 14:57:55', NULL),
(35, 106, 29, 'Pemerintah Daerah', '', '[\"Akses produk statistik pada Website BPS\"]', '[\"Website BPS \\/ AllStats BPS\",\"Aplikasi chat (WhatsApp, Telegram, ChatUs, dll.)\"]', '', 'positif', '2026-06-24 15:01:56', NULL);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pes_kebutuhan_data`
--

CREATE TABLE `pes_kebutuhan_data` (
  `id` int NOT NULL,
  `pes_id` int NOT NULL,
  `butir_kebutuhan` varchar(255) DEFAULT NULL,
  `jenis_sumber_data` varchar(100) DEFAULT NULL,
  `judul_sumber_data` varchar(255) DEFAULT NULL,
  `tahun_sumber_data` smallint DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pes_kebutuhan_data`
--

INSERT INTO `pes_kebutuhan_data` (`id`, `pes_id`, `butir_kebutuhan`, `jenis_sumber_data`, `judul_sumber_data`, `tahun_sumber_data`) VALUES
(1, 1, 'Jumlah penduduk', 'Tabel di Website', 'Jumlah Penduduk, Laju Pertumbuhan Penduduk, Distribusi Persentase Penduduk, Kepadatan Penduduk, Rasio Jenis Kelamin Penduduk Menurut Kecamatan di Kabupaten Buleleng', 2025),
(2, 1, 'Nilai Ekspor Kabupaten Buleleng', 'Tabel di Website', 'PDRB Seri 2010 Atas Dasar Harga Konstan Menurut Pengeluaran Kabupaten Buleleng (Milyar Rupiah)', 2025),
(3, 1, 'Nilai Impor Kabupaten Buleleng', 'Tabel di Website', 'PDRB Seri 2010 Atas Dasar Harga Berlaku Menurut Pengeluaran Kabupaten Buleleng (Milyar Rupiah)', 2025),
(4, 1, 'Persentase Kemiskinan', 'Tabel di Website', 'Persentase Penduduk Miskin (Persen)', 2025),
(5, 1, 'Tingkat Pengangguran Terbuka', 'Tabel di Website', 'Tingkat Pengangguran Terbuka (TPT) dan Tingkat Partisipasi Angkatan Kerja (TPAK) Menurut Kabupaten/Kota di Provinsi Bali', 2025),
(6, 2, 'Data Hasil Panen Jagung', 'Tabulasi Data', 'Produksi Jagung (Ton)', 2025),
(7, 3, 'Nilai Tukar Petani', 'Tabel di Website', 'Nilai Tukar Petani Provinsi Bali', 2025),
(8, 3, 'Laju Pertumbuhan Lapangan Usaha Pertanian', 'Publikasi', 'Produk Domestik Regional Bruto Kabupaten Buleleng Menurut Lapangan Usaha 2021-2025', 2026),
(9, 4, 'Luas Tanam Talas', 'Tabulasi Data', 'Luas Tanam Palawija', 2025),
(10, 4, 'Produksi Talas', 'Tabulasi Data', 'Produksi Palawija', 2025),
(11, 5, 'Analisis Hasil Survei Kebutuhan Data BPS Tahun 2025.', 'Publikasi', 'Analisis Hasil Survei Kebutuhan Data BPS Kabupaten Buleleng 2025', 2025),
(12, 6, 'Konsultasi metode survei penyusunan rata-rata lama menginap', 'Tabel di Website', 'Rata-Rata Lama Menginap Tamu Asing dan Domestik pada Hotel Non Bintang dan Akomodasi Lainnya Menurut Kabupaten/Kota dan Bulan di Provinsi Bali (Malam)', 2019),
(13, 7, 'Data Luas Tanaman Palawija', 'Tabulasi Data', 'Luas Tanam Tanaman Padi dan Palawija', 2026),
(14, 8, 'Jumlah Penduduk Usia Produktif Desa Tigawasa', 'Publikasi', 'Kecamatan Banjar Dalam Angka 2025', 2025),
(15, 9, 'Jumlah Bayi Berat Badan Lahir Rendah', 'Publikasi', 'Statistik Kesejahteraan Rakyat Provinsi Bali 2025', 2025),
(16, 10, 'Jumlah Industri Mikro dan Kecil (UMKM)', 'Publikasi', 'Kabupaten Buleleng Dalam Angka 2026', 2025),
(17, 11, 'Indeks Pembangunan Manusia', 'Tabel di Website', 'Indeks Pembangunan Manusia Kabupaten Buleleng', 2024),
(18, 12, 'PDRB Menurut Lapanga Usaha', 'Tabel di Website', 'Produk Domestik Regional Bruto (PDRB) Triwulanan Menurut Lapangan Usaha Atas Dasar Harga Konstan Kabupaten Buleleng (Milyar Rupiah)', 2025),
(19, 13, 'Kemiskinan', 'Tabulasi Data', 'Persentase Kemiskinan Kabupaten Buleleng', 2002),
(20, 13, 'PDBR ADHK Menurut Lapangan Usaha', 'Tabulasi Data', 'Produk Domestik Regional Bruto (PDRB) Triwulanan Menurut Lapangan Usaha Atas Dasar Harga Konstan Kabupaten Buleleng (Milyar Rupiah)', 2008),
(21, 13, 'Tingkat Pengangguran Terbuka', 'Tabulasi Data', 'Tingkat Pengangguran Terbuka Kabupaten Buleleng', 2006),
(22, 13, 'Tingkat Partisipasi Angkatan Kerja', 'Tabulasi Data', 'Tingkat Partisipasi Angkatan Kerja Kabupaten Buleleng', 2006),
(23, 14, 'Distribusi Penduduk Berdasarkan Kelompok Umur', 'Tabulasi Data', 'Distribusi Penduduk Berdasarkan Kelompok Umur Kabupaten Buleleng', 2006),
(24, 15, 'Tingkat Partisipasi Angkatan Kerja', 'Tabel di Website', 'Tingkat Partisipasi Angkatan Kerja', 2025),
(25, 16, 'Jumlah Penduduk Per Kecamatan di Kabupaten Buleleng', 'Publikasi', 'Jumlah Penduduk Per Kecamatan di Kabupaten Buleleng', 2026),
(26, 17, 'Jumlah Koperasi di Kabupaten Buleleng', 'Publikasi', 'Kabupaten Buleleng Dalam Angka 2026', 2025),
(27, 18, 'Konsumsi Beras per Kapita', 'Publikasi', 'Statistik Kesejahteraan Rakyat Kabupaten Buleleng', 2025),
(28, 19, 'Jumlah UMKM Kabupaten Buleleng', 'Publikasi', 'Kabupaten Buleleng Dalam Angka 2026', 2025),
(29, 20, 'Jumlah UMKM di Kabupaten Buleleng', 'Publikasi', 'Kabupaten Buleleng Dalam Angka 2026', 2025),
(30, 21, 'PDRB per kapita (ADHK dan ADHB)', 'Tabel di Website', 'Produk Domestik Regional Bruto dan PDRB Per Kapita  Kabupaten Buleleng', 2024),
(31, 21, 'Pertumbuhan ekonomi per tahun', 'Tabel di Website', 'Laju Pertumbuhan Y-on-Y PDRB Seri 2010 Atas Dasar Harga Konstan Menurut Pengeluaran Kabupaten Buleleng (Persen)', 2024),
(32, 21, 'Indeks Pembangunan Manusia', 'Publikasi', 'Indeks Pembangunan Manusia Kabupaten Buleleng', 2025),
(33, 21, 'Data jumlah desa dan aksesibilitas jalan', 'Publikasi', 'Statistik Potensi Desa Kabupaten Buleleng', 2025),
(34, 21, 'Produk Domestik Regional Bruto', 'Tabel di Website', 'Produk Domestik Regional Bruto Atas Dasar Harga Konstan 2010 Menurut Lapangan Usaha di Kabupaten Buleleng (miliar rupiah)', 2024),
(35, 21, 'Tingkat Pengangguran Terbuka', 'Tabel di Website', 'Tingkat Pengangguran Terbuka', 2024),
(36, 21, 'Persentase Penduduk Miskin', 'Tabel di Website', 'Persentase Penduduk Miskin (Persen)', 2025),
(37, 21, 'Data Kependudukan Kabupaten Buleleng', 'Tabel di Website', 'Jumlah Penduduk, Laju Pertumbuhan Penduduk, Distribusi Persentase Penduduk, Kepadatan Penduduk, Rasio Jenis Kelamin Penduduk Menurut Kecamatan di Kabupaten Buleleng', 2025),
(38, 22, 'Tingkat Pengangguran Terbuka', 'Tabel di Website', 'Tingkat Pengangguran Terbuka Kabupaten Buleleng', 2020),
(39, 23, 'Luas Tanam Pertanian Kabupaten Buleleng (Maret)', 'Tabulasi Data', 'Luas Tanamn Pertanian Palawija Kabupaten Buleleng Bulan Maret', 2026),
(40, 24, 'Proyeksi jumlah penduduk kab buleleng', 'Publikasi', 'Proyeksi Penduduk Hasil Sensus Penduduk 2020', 2024),
(41, 25, 'Jumlah Penduduk Menurut Kecamatan di Kabupaten Buleleng', 'Tabulasi Data', 'Jumlah Penduduk, Laju Pertumbuhan Penduduk, Distribusi Persentase Penduduk, Kepadatan Penduduk, Rasio Jenis Kelamin Penduduk Menurut Kecamatan di Kabupaten Buleleng', 2025),
(42, 25, 'Jumlah Penduduk Menurut Jenis Kelamin di Kabupaten Buleleng', 'Tabulasi Data', 'Jumlah Penduduk Menurut Kelompok Umur dan Jenis Kelamin di Kabupaten Buleleng (jiwa)', 2025),
(43, 25, 'Jumlah Rumah Tangga Menurut Kecamatan di Kabupaten Buleleng', 'Tabulasi Data', '-', 2025),
(44, 25, 'Persentase Penduduk berdasarkan Tingkat Pendidikan Tertinggi yang Berhasil  Ditamatkan di Kabupaten Buleleng', 'Tabulasi Data', 'Persentase Penduduk Berumur 15 Tahun ke Atas Menurut Karakteristik dan Tingkat Pendidikan Tertinggi yang Ditamatkan (Ijazah/STTB Tertinggi yang Dimiliki) di Kabupaten Buleleng', 2025),
(45, 25, 'Persentase Penduduk Usia 15 Tahun ke Atas yang Melek Huruf di Kabupaten Buleleng', 'Tabulasi Data', 'Angka Melek Huruf Provinsi Bali Menurut Kabupaten/Kota dan Jenis Kelamin (Persen)', 2025),
(46, 26, 'Nilai Ekonomi Kreatif Kabupaten Buleleng Per KBLI', 'Tabulasi Data', '-', 2026),
(48, 31, 'data isu sosial', 'Publikasi', 'kabupaten buleleng dalam Angka', 2025),
(49, 32, 'PDRB Kabupaten/Kota', 'Publikasi', 'Produk Domestik Regional Bruto Kabupaten Buleleng', 2025),
(50, 32, 'Kepadatan Penduduk', 'Publikasi', 'Kabupaten Buleleng Dalam Angka', 2025),
(51, 32, 'Pendapatan Perkapita', 'Tabulasi Data', 'Belum Diperoleh', 2025),
(52, 32, 'Kepadatan Penduduk', '', 'Kabupaten Buleleng Dalam Angka', 2023),
(53, 32, 'Mata Pencaharian Utama', 'Publikasi', 'Kabupaten Buleleng Dalam Angka', 2025),
(54, 32, 'Jumlah Penduduk', 'Publikasi', 'Kabupaten Buleleng Dalam Angka', 2025),
(55, 32, 'Rasio dan Kategori Kapasitas Fiskal', 'Publikasi', 'Kabupaten Buleleng Dalam Angka', 2025),
(59, 30, 'Inflasi Bulanan Kota Singaraja', 'Tabel di Website', 'Inflasi Bulanan Kota Singaraja', 2026),
(60, 34, 'Hasil Panen Jagung di Kabupaten Buleleng (Bulanan)', 'Tabulasi Data', 'Produksi Jagung Bulanan Kabupaten Buleleng (Ton)', 2025),
(61, 34, 'Luas Lahan Pertanian Jagung (bulan)', 'Tabulasi Data', 'Luas Tanam Bulanan Kabupaten Buleleng (Hektar)', 2026),
(62, 34, 'Produktivitas Jagung (bulan)', 'Tabulasi Data', 'Prodktivitas Jagung Bulanan Kabuaten Buleleng (Ton/Hektar)', 2026),
(64, 35, 'Data PDRB harga konstan 2025 untuk pertanian, perkebunan dan perikanan dengan angkanya', 'Publikasi', 'Produk Domestik Regional Bruto Kabupaten Buleleng Menurut Lapangan Usaha', 2025),
(65, 29, 'Publikasi Sensus Pertanian Tahun 2020', 'Publikasi', 'Hasil Pencacahan Lengkap Sensus Pertanian 2023 - Tahap II Kabupaten Buleleng', 2023);

-- --------------------------------------------------------

--
-- Struktur dari tabel `pes_pembantu`
--

CREATE TABLE `pes_pembantu` (
  `id` int NOT NULL,
  `pes_id` int NOT NULL,
  `pegawai_id` int NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data untuk tabel `pes_pembantu`
--

INSERT INTO `pes_pembantu` (`id`, `pes_id`, `pegawai_id`) VALUES
(1, 2, 15),
(2, 2, 6),
(3, 5, 11),
(4, 6, 15),
(5, 7, 15),
(6, 7, 11),
(7, 7, 6),
(8, 13, 16),
(9, 18, 5),
(10, 18, 16),
(11, 21, 16),
(12, 23, 6),
(13, 25, 4),
(14, 25, 16),
(15, 26, 11),
(16, 26, 3),
(17, 26, 12),
(20, 31, 13),
(21, 32, 28),
(22, 30, 11),
(24, 35, 19),
(25, 29, 18);

--
-- Indeks untuk tabel yang dibuang
--

--
-- Indeks untuk tabel `absensi_piket`
--
ALTER TABLE `absensi_piket`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_pegawai_tanggal` (`pegawai_id`,`tanggal`);

--
-- Indeks untuk tabel `antrian`
--
ALTER TABLE `antrian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD UNIQUE KEY `token_pes` (`token_pes`),
  ADD KEY `idx_tanggal_jenis_status` (`tanggal`,`jenis`,`status`);

--
-- Indeks untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `penghargaan_penilaian`
--
ALTER TABLE `penghargaan_penilaian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_pk` (`pegawai_id`,`bulan`,`tahun`);

--
-- Indeks untuk tabel `penghargaan_tim_penilai`
--
ALTER TABLE `penghargaan_tim_penilai`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uniq_tp` (`pegawai_id`,`bulan`,`tahun`,`nama_penilai`);

--
-- Indeks untuk tabel `penilaian`
--
ALTER TABLE `penilaian`
  ADD PRIMARY KEY (`id`);

--
-- Indeks untuk tabel `penilaian_data_item`
--
ALTER TABLE `penilaian_data_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pdi_penilaian` (`penilaian_id`);

--
-- Indeks untuk tabel `pes`
--
ALTER TABLE `pes`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pes_antrian` (`antrian_id`),
  ADD KEY `fk_pes_petugas` (`petugas_utama_id`);

--
-- Indeks untuk tabel `pes_kebutuhan_data`
--
ALTER TABLE `pes_kebutuhan_data`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_peskd_pes` (`pes_id`);

--
-- Indeks untuk tabel `pes_pembantu`
--
ALTER TABLE `pes_pembantu`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pespmb_pes` (`pes_id`),
  ADD KEY `fk_pespmb_pegawai` (`pegawai_id`);

--
-- AUTO_INCREMENT untuk tabel yang dibuang
--

--
-- AUTO_INCREMENT untuk tabel `absensi_piket`
--
ALTER TABLE `absensi_piket`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=97;

--
-- AUTO_INCREMENT untuk tabel `antrian`
--
ALTER TABLE `antrian`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=126;

--
-- AUTO_INCREMENT untuk tabel `pegawai`
--
ALTER TABLE `pegawai`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=37;

--
-- AUTO_INCREMENT untuk tabel `penghargaan_penilaian`
--
ALTER TABLE `penghargaan_penilaian`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- AUTO_INCREMENT untuk tabel `penghargaan_tim_penilai`
--
ALTER TABLE `penghargaan_tim_penilai`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=10;

--
-- AUTO_INCREMENT untuk tabel `penilaian`
--
ALTER TABLE `penilaian`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=33;

--
-- AUTO_INCREMENT untuk tabel `penilaian_data_item`
--
ALTER TABLE `penilaian_data_item`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT untuk tabel `pes`
--
ALTER TABLE `pes`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=36;

--
-- AUTO_INCREMENT untuk tabel `pes_kebutuhan_data`
--
ALTER TABLE `pes_kebutuhan_data`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT untuk tabel `pes_pembantu`
--
ALTER TABLE `pes_pembantu`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=26;

--
-- Ketidakleluasaan untuk tabel pelimpahan (Dumped Tables)
--

--
-- Ketidakleluasaan untuk tabel `absensi_piket`
--
ALTER TABLE `absensi_piket`
  ADD CONSTRAINT `fk_absensi_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `penilaian_data_item`
--
ALTER TABLE `penilaian_data_item`
  ADD CONSTRAINT `fk_pdi_penilaian` FOREIGN KEY (`penilaian_id`) REFERENCES `penilaian` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pes`
--
ALTER TABLE `pes`
  ADD CONSTRAINT `fk_pes_antrian` FOREIGN KEY (`antrian_id`) REFERENCES `antrian` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pes_petugas` FOREIGN KEY (`petugas_utama_id`) REFERENCES `pegawai` (`id`) ON DELETE SET NULL;

--
-- Ketidakleluasaan untuk tabel `pes_kebutuhan_data`
--
ALTER TABLE `pes_kebutuhan_data`
  ADD CONSTRAINT `fk_peskd_pes` FOREIGN KEY (`pes_id`) REFERENCES `pes` (`id`) ON DELETE CASCADE;

--
-- Ketidakleluasaan untuk tabel `pes_pembantu`
--
ALTER TABLE `pes_pembantu`
  ADD CONSTRAINT `fk_pespmb_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_pespmb_pes` FOREIGN KEY (`pes_id`) REFERENCES `pes` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
