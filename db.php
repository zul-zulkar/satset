<?php
// Struktur file PHP dasar untuk sistem antrian pengguna data
// Direktori project:
// - index.php (halaman pengguna scan barcode)
// - cs.php (halaman petugas CS)
// - monitor.php (tampilan monitor antrean)
// - reset.php (reset antrean harian)
// - db.php (koneksi dan struktur database)
// - style: TailwindCSS CDN
date_default_timezone_set('Asia/Makassar');

// === db.php ===
$mysqli = new mysqli("kang.statsbali.id", "bps5108", "db@bps5108", "db_5108_satset");
if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

// Struktur tabel:
// CREATE TABLE `antrian` (
//   `id` int AUTO_INCREMENT PRIMARY KEY,
//   `nama` varchar(255),
//   `telepon` varchar(20),
//   `instansi` varchar(255),
//   `jenis` enum('umum', 'disabilitas'),
//   `nomor` int,
//   `tanggal` date,
//   `status` enum('menunggu', 'dipanggil')
// );

// === index.php ===
?>