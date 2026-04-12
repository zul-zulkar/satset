-- phpMyAdmin SQL Dump
-- version 4.9.0.1
-- https://www.phpmyadmin.net/
--
-- Host: sql213.infinityfree.com
-- Generation Time: Apr 06, 2026 at 07:59 AM
-- Server version: 11.4.10-MariaDB
-- PHP Version: 7.2.22

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `if0_41029675_db_5108_satset`
--

-- --------------------------------------------------------

--
-- Table structure for table `antrian`
--

CREATE TABLE `antrian` (
  `id` int(11) NOT NULL,
  `nama` varchar(100) NOT NULL,
  `email` text DEFAULT NULL,
  `jk` enum('L','P') DEFAULT NULL,
  `jumlah_orang` int(11) DEFAULT NULL,
  `keperluan` text DEFAULT NULL,
  `lahir` varchar(30) DEFAULT NULL,
  `pendidikan` varchar(20) DEFAULT NULL,
  `kelompok_umur` varchar(25) DEFAULT NULL,
  `alamat` text DEFAULT NULL,
  `pekerjaan` varchar(100) DEFAULT NULL,
  `pemanfaatan_data` varchar(50) DEFAULT NULL,
  `data_dibutuhkan` text DEFAULT NULL,
  `data_yang_diperlukan` text DEFAULT NULL,
  `metode` varchar(100) DEFAULT NULL,
  `telepon` varchar(20) NOT NULL,
  `instansi` varchar(100) NOT NULL,
  `jenis` enum('umum','disabilitas','whatsapp') DEFAULT NULL,
  `nomor` int(11) NOT NULL,
  `tanggal` date NOT NULL,
  `status` enum('menunggu','dipanggil') DEFAULT 'menunggu',
  `kunjungan_pst` tinyint(1) NOT NULL DEFAULT 0,
  `token` varchar(64) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `antrian`
--

INSERT INTO `antrian` (`id`, `nama`, `email`, `jk`, `jumlah_orang`, `keperluan`, `lahir`, `pendidikan`, `kelompok_umur`, `alamat`, `pekerjaan`, `pemanfaatan_data`, `data_dibutuhkan`, `data_yang_diperlukan`, `metode`, `telepon`, `instansi`, `jenis`, `nomor`, `tanggal`, `status`, `kunjungan_pst`, `token`) VALUES
(41, 'Komang indah Wulandari wismaya', 'Wismayaewc@gmail.com', 'P', NULL, NULL, NULL, 'S1/DIV', NULL, NULL, 'Mahasiswa', NULL, NULL, 'Mencari lokasi untuk penanaman pohon', 'kunjungan langsung', '081936662555', 'perorangan', 'umum', 2, '2025-04-14', 'menunggu', 0, NULL),
(42, 'Nazzala Qinthara Nafi', 'nzalthara@gmail.com', 'P', NULL, NULL, NULL, 'S1/DIV', NULL, NULL, 'ASN', NULL, NULL, 'vyig', 'kunjungan langsung', '09', 'ygyug', 'umum', 1, '2025-04-15', 'dipanggil', 0, NULL),
(36, 'Komang Indah Wulandari Wismaya', 'komangindah@gmail.com', 'P', NULL, NULL, '1999-06-01', 'S1/DIV', NULL, 'Panji asri T06', 'Mahasiswa', NULL, NULL, 'Menari lokasi untuk penanaman pohon', 'kunjungan langsung', '081936662555', 'perorangan', 'umum', 1, '2025-04-14', 'dipanggil', 0, NULL),
(43, 'Ni Komang Ayu Mirah Senja Paramita', 'anonim@gmail.com', 'P', NULL, NULL, NULL, 'SMA', NULL, NULL, 'Mahasiswa', NULL, NULL, '1. Persentase penduduk miskin (tahun 1994-2002); 2. PDRB menurut lapangan usaha harga konstan (1994-2008); 3. Tingkat Pengangguran Terbuka/TPT (1994-2006); 4. Tingkat Partisipasi Angkatan Kerja/TPAK (1994-2006); 5. Distribusi penduduk menurut kelompok umur (1994-2006)', 'whatsapp', '08970834220', 'Universitas Udayana', 'whatsapp', 1, '2026-01-27', 'dipanggil', 1, NULL),
(44, 'Gusti doni', 'donigungalit@gmail.com', 'L', 1, 'Melakukan Klarifikasi Desil dari DTSEN', NULL, 'D4/S1', '35 - 44 tahun', NULL, 'Pegawai Swasta', 'Pemerintahan', NULL, 'Desil', 'kunjungan langsung', '081999838039', 'Perorangan', 'umum', 1, '2026-02-03', 'dipanggil', 0, NULL),
(46, 'I Komang edi jayantika', 'edijayantikaikomang@gmail.com', 'L', 1, NULL, NULL, 'D4/S1', '35 - 44 tahun', NULL, 'Pegawai Swasta', 'Pemerintahan', '[{\"data\":\"Jumlah Penduduk Usia Produktif Desa Tigawasa\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', 'Data usia produktif penduduk desa tigawasa', 'kunjungan langsung', '087856301752', 'pemerintah desa tigawasa', 'umum', 1, '2026-02-04', 'dipanggil', 1, NULL),
(47, 'I Gede Agus Supriawan', 'gede23agus@gmail.com', 'L', 1, NULL, NULL, 'D4/S1', '45 - 54 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Jumlah Bayi Berat Badan Lahir Rendah\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', 'data BBLR tahun 2024 dan tahun 2025', 'kunjungan langsung', '081915697344', 'Perorangan', 'umum', 2, '2026-02-04', 'dipanggil', 1, NULL),
(48, 'Hadi Setiadi', 'hadiadihadi2020@gmail.com', 'L', 1, NULL, NULL, 'D4/S1', '45 - 54 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Jumlah Koperasi di Kabupaten Buleleng\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', 'Data Angkatan Kerja', 'kunjungan langsung', '082338681355', 'DisdsgperinkopUKM Kab. Buleleng', 'umum', 1, '2026-02-10', 'dipanggil', 1, NULL),
(49, 'Ni Made Atmadewi ', 'atmadewi.made@gmail.com', 'P', NULL, NULL, NULL, 'S2', NULL, NULL, 'PNS', NULL, NULL, 'Konsumsi beras per kapita ', 'whatsapp', '08179732608', 'Dinas Pertanian, Ketahanan Pangan dan Perikanan ', 'whatsapp', 1, '2026-02-23', 'menunggu', 1, NULL),
(50, 'Luh Putu Anggayanti', 'anggayanti04@gmail.com', 'P', NULL, NULL, NULL, 'SMA', NULL, NULL, 'Mahasiswa', NULL, NULL, 'Tingkat Pengangguran Terbuka (TPT)', 'whatsapp', '085738244643', 'Universitas Pendidikan Ganesha', 'whatsapp', 2, '2026-02-23', 'menunggu', 1, NULL),
(51, 'Luh Putu Anggayanti', 'anggayanti04@gmail.com', 'P', NULL, NULL, NULL, 'SMA', NULL, NULL, 'Mahasiswa', NULL, NULL, 'Tingkat Pengangguran Terbuka (TPT)', 'whatsapp', '085738244643', 'Universitas Pendidikan Ganesha', 'whatsapp', 3, '2026-02-23', 'menunggu', 1, NULL),
(52, 'Made Ayodhia Sari Widhi Nurjaya ', 'ayodhiasariii@gmail.com', 'P', NULL, NULL, NULL, 'S1/DIV', NULL, NULL, 'Mahasiswa ', NULL, NULL, 'Sehubungan dengan penelitian tersebut, saya memohon bantuan data berikut untuk periode 2020-2025:  1. PDRB per kapita (ADHK dan ADHB)  2. Pertumbuhan ekonomi per tahun  3. Indeks Pembangunan Manusia (IPM)  4. Data ketimpangan pembangunan (Indeks Williamson jika tersedia).  5. Data jumlah desa dan aksesibilitas jalan  6. Data sosial ekonomi per kecamatan', 'whatsapp', '08873356569', 'UPN Veteran Jawa Timur ', 'whatsapp', 1, '2026-02-25', 'menunggu', 1, NULL),
(53, 'Ni Luh Meliyani ', 'luhmeliyani889@gmail.com', 'P', NULL, NULL, NULL, 'S1/DIV', NULL, NULL, 'Pegawai Pemerintah ', NULL, NULL, 'Jadwal pendampingan Survei Komoditas Strategis Perkebunan Tahun 2026 (VKOMSTRAT 2026) di kecamatan Kubutambahan ', 'whatsapp', '087864477963', 'Kantor Camat Kubutambahan ', 'whatsapp', 1, '2026-03-02', 'menunggu', 1, NULL),
(54, 'I Made Suwantika', 'madesuantika268@gmail.com', 'L', NULL, NULL, NULL, 'SMA', NULL, NULL, 'Perangkat Desa', NULL, NULL, 'Jadwal Survey', 'whatsapp', '081915707849', 'Kantor Perbekel Bontihing', 'whatsapp', 2, '2026-03-02', 'menunggu', 1, NULL),
(55, 'Ketut Mudiarta', 'ketutmudiartacrb@gmail.com', 'L', NULL, NULL, NULL, 'SMA', NULL, NULL, 'Perangkat Desa', NULL, NULL, 'Jadwal sesus perkebunan 2026 untuk desa pakisan', 'whatsapp', '085933062289', 'Pemerintah Desa Pakisan', 'whatsapp', 3, '2026-03-02', 'menunggu', 1, NULL),
(60, 'Kristiani Widya Karo', 'kristianiwiwidya@gmail.com', 'P', 1, NULL, NULL, 'D4/S1', '26 - 34 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Jumlah Penduduk Per Kecamatan di Kabupaten Buleleng\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', 'Data kependudukan', 'kunjungan langsung', '082284352265', 'Dinas Lingkungan Hidup Provinsi Bali', 'umum', 1, '2026-03-05', 'menunggu', 1, NULL),
(59, 'Wibawa mahardika', 'komangtri50@gmail.com', 'L', NULL, NULL, NULL, 'S1/DIV', NULL, NULL, 'Asn', NULL, NULL, 'TPAK Kabupaten buleleng 2023-2025', 'whatsapp', '089686309413', 'Bappeda', 'whatsapp', 1, '2026-03-04', 'menunggu', 1, NULL),
(61, 'Muzayyinul Ghufron', 'yiyin.guf@gmail.com', 'L', 1, NULL, NULL, 'D4/S1', '26 - 34 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Luas Tanam Pertanian Kabupaten Buleleng\",\"tahun_dari\":2026,\"tahun_sampai\":2026}]', 'Data SP Palawija', 'kunjungan langsung', '085746126882', 'BPSBTPHBUN', 'umum', 1, '2026-03-10', 'menunggu', 1, NULL),
(62, 'Putu Dhio Agustina', 'dhioagst27@gmail.com', 'L', 1, NULL, NULL, 'SLTA/Sederajat', '17 - 25 tahun', NULL, 'Pelajar/Mahasiswa', 'Tugas Sekolah/Tugas Kuliah', '[{\"data\":\"Jumlah UMKM\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', 'Produksi Anggur Kabupaten Buleleng', 'kunjungan langsung', '085737279334', 'Universitas Pendidikan Ganesha', 'umum', 1, '2026-03-13', 'dipanggil', 1, NULL),
(63, 'Ngurah putu widiyasa', 'ngurahputuwidiasa@gmail.com', 'L', NULL, NULL, NULL, 'SMA', NULL, NULL, 'Swasta', NULL, NULL, 'Konseling listrik/travo terbakar', 'whatsapp', '081529616544', 'Perorangan', 'whatsapp', 1, '2026-03-24', 'menunggu', 1, NULL),
(64, 'Ni Komang Ayu Mirah Senja Paramita', 'ayumirahayumirah@gmail.com', 'P', NULL, NULL, NULL, 'SMA', NULL, NULL, 'mahasiswa', NULL, NULL, '1. Rata rata lama sekolah Buleleng periode tahun 2000â€“2010 2. Data jumlah penduduk miskin buleleng tahun 2000â€“2002. ', 'whatsapp', '08970834220', 'Universitas udayana', 'whatsapp', 1, '2026-03-27', 'menunggu', 1, NULL),
(66, 'Debby', 'debbyroundra@gmail.com', 'P', 3, 'Penawaran Kredit Syariah', NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, NULL, '082216936021', 'Bank Syariah Indonesia', 'umum', 2, '2026-03-30', 'menunggu', 0, '7cb548fcc2bb7cc488013d0a990fd550'),
(68, 'I Ketut Sudarma Yasa', 'sudarmayasa041981@gmail.com', 'L', 2, NULL, NULL, 'SLTA/Sederajat', '45 - 54 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Jumlah penduduk\",\"tahun_dari\":2025,\"tahun_sampai\":2025},{\"data\":\"Nilai Ekspor Kabupaten Buleleng\",\"tahun_dari\":2025,\"tahun_sampai\":2025},{\"data\":\"Nilai Impor Kabupaten Buleleng\",\"tahun_dari\":2025,\"tahun_sampai\":2025},{\"data\":\"Persentase Kemiskinan\",\"tahun_dari\":2025,\"tahun_sampai\":2025},{\"data\":\"Tingkat Pengangguran Terbuka\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', NULL, NULL, '081353535711', 'Kodim 1609/Buleleng', 'umum', 1, '2026-04-02', 'dipanggil', 1, 'ff1bb514f130f6a189533304179f42f3'),
(70, 'Rossyana', 'anarossy695@gmail.com', 'P', NULL, NULL, NULL, 'D4/S1', '17 - 25 tahun', NULL, 'Pelajar/Mahasiswa', 'Tugas Sekolah/Tugas Kuliah', '[{\"data\":\"Analisis Hasil Survei Kebutuhan Data BPS Tahun 2025.\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', NULL, NULL, '088217404207', 'Universitas Brawijaya', 'whatsapp', 0, '2026-04-06', 'menunggu', 0, 'a30d002b173e510775eb7a14a8f708d4'),
(71, 'I NYOMANAGUS SWADARMA ADNYANA', 'agus.swadarma.as@gmail.com', 'L', 1, NULL, NULL, 'D4/S1', '45 - 54 tahun', NULL, 'ASN/TNI/Polri', 'Pemerintahan', '[{\"data\":\"Konsultasi metode survei penyusunan rata-rata lama menginap\",\"tahun_dari\":2025,\"tahun_sampai\":2025}]', NULL, NULL, '081999808644', 'Disbudpar', 'umum', 1, '2026-04-06', 'menunggu', 1, '2993dcd1262255ca005907829e8be97d');

-- --------------------------------------------------------

--
-- Table structure for table `penilaian`
--

CREATE TABLE `penilaian` (
  `id` int(11) NOT NULL,
  `antrian_id` int(11) DEFAULT NULL,
  `tanggal` date NOT NULL,
  `q1` tinyint(3) UNSIGNED DEFAULT NULL,
  `q2` tinyint(3) UNSIGNED DEFAULT NULL,
  `q3` tinyint(3) UNSIGNED DEFAULT NULL,
  `q4` tinyint(3) UNSIGNED DEFAULT NULL,
  `q5` tinyint(3) UNSIGNED DEFAULT NULL,
  `q6` tinyint(3) UNSIGNED DEFAULT NULL,
  `q7` tinyint(3) UNSIGNED DEFAULT NULL,
  `q8` tinyint(3) UNSIGNED DEFAULT NULL,
  `q9` tinyint(3) UNSIGNED DEFAULT NULL,
  `q10` tinyint(3) UNSIGNED DEFAULT NULL,
  `q11` tinyint(3) UNSIGNED DEFAULT NULL,
  `q12` tinyint(3) UNSIGNED DEFAULT NULL,
  `q13` tinyint(3) UNSIGNED DEFAULT NULL,
  `q14` tinyint(3) UNSIGNED DEFAULT NULL,
  `q15` tinyint(3) UNSIGNED DEFAULT NULL,
  `q16` tinyint(3) UNSIGNED DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `submitted_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `penilaian`
--

INSERT INTO `penilaian` (`id`, `antrian_id`, `tanggal`, `q1`, `q2`, `q3`, `q4`, `q5`, `q6`, `q7`, `q8`, `q9`, `q10`, `q11`, `q12`, `q13`, `q14`, `q15`, `q16`, `catatan`, `submitted_at`) VALUES
(1, 68, '2026-04-02', 9, 9, 9, 9, 8, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 'Petugas ramah, respon cepat', '2026-04-02 03:43:52'),
(2, 69, '2026-04-02', 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, '', '2026-04-02 06:52:11'),
(3, 71, '2026-04-06', 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 'Petugas pelayanan BPS sangat profesional dan ramah terhadap melayani tamu, informasi yang diberikan sangat jelas dan mudah dipahami, dan untuk fasilitas sangat memadai', '2026-04-06 04:26:31');

-- --------------------------------------------------------

--
-- Table structure for table `penilaian_data_item`
--

CREATE TABLE `penilaian_data_item` (
  `id` int(11) NOT NULL,
  `penilaian_id` int(11) NOT NULL,
  `nama_data` varchar(255) DEFAULT NULL,
  `tahun_dari` int(11) DEFAULT NULL,
  `tahun_sampai` int(11) DEFAULT NULL,
  `nilai` tinyint(3) UNSIGNED DEFAULT NULL,
  `status_perolehan` enum('Ya, sesuai','Ya, tidak sesuai','Tidak diperoleh','Belum diperoleh') DEFAULT NULL,
  `untuk_perencanaan` enum('ya','tidak') DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci;

--
-- Dumping data for table `penilaian_data_item`
--

INSERT INTO `penilaian_data_item` (`id`, `penilaian_id`, `nama_data`, `tahun_dari`, `tahun_sampai`, `nilai`, `status_perolehan`, `untuk_perencanaan`) VALUES
(1, 1, 'Jumlah penduduk', 2025, 2025, 9, 'Ya, sesuai', 'ya'),
(2, 1, 'Nilai Ekspor Kabupaten Buleleng', 2025, 2025, 9, 'Ya, sesuai', 'ya'),
(3, 1, 'Nilai Impor Kabupaten Buleleng', 2025, 2025, 7, 'Ya, sesuai', 'ya'),
(4, 1, 'Persentase Kemiskinan', 2025, 2025, 9, 'Ya, sesuai', 'ya'),
(5, 1, 'Tingkat Pengangguran Terbuka', 2025, 2025, 9, 'Ya, sesuai', 'ya'),
(6, 2, 'aasasasasa', 2020, 2021, 10, 'Ya, sesuai', 'ya'),
(8, 3, 'Konsultasi metode survei penyusunan rata-rata lama menginap', 2025, 2025, 10, 'Ya, sesuai', 'ya');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `antrian`
--
ALTER TABLE `antrian`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_tanggal_jenis_status` (`tanggal`,`jenis`,`status`);

--
-- Indexes for table `penilaian`
--
ALTER TABLE `penilaian`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `penilaian_data_item`
--
ALTER TABLE `penilaian_data_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pdi_penilaian` (`penilaian_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `antrian`
--
ALTER TABLE `antrian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=72;

--
-- AUTO_INCREMENT for table `penilaian`
--
ALTER TABLE `penilaian`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `penilaian_data_item`
--
ALTER TABLE `penilaian_data_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `penilaian_data_item`
--
ALTER TABLE `penilaian_data_item`
  ADD CONSTRAINT `fk_pdi_penilaian` FOREIGN KEY (`penilaian_id`) REFERENCES `penilaian` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
