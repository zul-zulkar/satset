<?php
// Muat konfigurasi terpusat (URL + DB) — lihat config.php
include_once __DIR__ . '/config.php';

date_default_timezone_set('Asia/Makassar');

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    die("Koneksi gagal: " . $mysqli->connect_error);
}

// Struktur tabel (jalankan di production jika belum ada):
// CREATE TABLE `antrian` (
//   `id`               INT AUTO_INCREMENT PRIMARY KEY,
//   `nama`             VARCHAR(255),
//   `email`            VARCHAR(255),
//   `telepon`          VARCHAR(20),
//   `instansi`         VARCHAR(255),
//   `jk`               ENUM('L','P'),
//   `jenis`            ENUM('umum','disabilitas','whatsapp'),
//   `nomor`            INT,                          -- NULL untuk whatsapp
//   `tanggal`          DATE,
//   `status`           ENUM('menunggu','dipanggil') DEFAULT 'menunggu',
//   -- Kunjungan langsung (umum / disabilitas):
//   `jumlah_orang`     INT,
//   `keperluan`        TEXT,
//   `kunjungan_pst`    TINYINT(1) NOT NULL DEFAULT 0, -- 1 = Permintaan Data
//   -- WhatsApp + PST (umum/disabilitas Permintaan Data):
//   `pendidikan`       VARCHAR(20),
//     -- Nilai: 'SLTA/Sederajat' | 'D1/D2/D3' | 'D4/S1' | 'S2' | 'S3'
//   `kelompok_umur`    VARCHAR(25),
//     -- Nilai: 'di bawah 17 tahun' | '17 - 25 tahun' | '26 - 34 tahun'
//     --      | '35 - 44 tahun' | '45 - 54 tahun' | '55 - 65 tahun' | 'di atas 65 tahun'
//   `pekerjaan`        VARCHAR(255),
//     -- Nilai: 'Pelajar/Mahasiswa' | 'Peneliti/Dosen' | 'ASN/TNI/Polri'
//     --      | 'Pegawai BUMN/BUMD' | 'Pegawai Swasta' | 'Wiraswasta'
//     --      | <teks bebas jika memilih "Lainnya">
//   `pemanfaatan_data` VARCHAR(50),
//     -- Nilai: 'Tugas Sekolah/Tugas Kuliah' | 'Pemerintahan' | 'Komersial'
//     --      | 'Penelitian' | 'Lainnya'
//   `data_dibutuhkan`  TEXT,
//     -- JSON: [{"data":"...","tahun_dari":2020,"tahun_sampai":2024}, ...]
//   `token`            VARCHAR(64) UNIQUE
//     -- Link unik survei kepuasan: /penilaian/{token}
// );
//
// Jika tabel sudah ada dengan schema lama, jalankan ALTER berikut (lewati kolom yang sudah ada):
// ALTER TABLE `antrian`
//   ADD COLUMN IF NOT EXISTS `email`          VARCHAR(255)          AFTER `nama`,
//   ADD COLUMN IF NOT EXISTS `jumlah_orang`   INT                   AFTER `jk`,
//   ADD COLUMN IF NOT EXISTS `keperluan`      TEXT                  AFTER `jumlah_orang`,
//   ADD COLUMN IF NOT EXISTS `kunjungan_pst`  TINYINT(1) DEFAULT 0  AFTER `keperluan`,
//   ADD COLUMN IF NOT EXISTS `kelompok_umur`  VARCHAR(25)           AFTER `pendidikan`,
//   ADD COLUMN IF NOT EXISTS `pemanfaatan_data` VARCHAR(50)         AFTER `pekerjaan`,
//   ADD COLUMN IF NOT EXISTS `data_dibutuhkan` TEXT                 AFTER `pemanfaatan_data`,
//   ADD COLUMN IF NOT EXISTS `token`          VARCHAR(64) UNIQUE,
//   MODIFY COLUMN `jenis`      ENUM('umum','disabilitas','whatsapp'),
//   MODIFY COLUMN `pendidikan` VARCHAR(20);
//
// ── Tabel survei kepuasan pelayanan ──────────────────────────────────
// CREATE TABLE `penilaian` (
//   `id`           INT AUTO_INCREMENT PRIMARY KEY,
//   `antrian_id`   INT,                              -- FK → antrian.id
//   `tanggal`      DATE NOT NULL,
//   `q1`           TINYINT UNSIGNED,                 -- skala 1–10
//   `q2`           TINYINT UNSIGNED,
//   `q3`           TINYINT UNSIGNED,
//   `q4`           TINYINT UNSIGNED,
//   `q5`           TINYINT UNSIGNED,
//   `q6`           TINYINT UNSIGNED,
//   `q7`           TINYINT UNSIGNED,
//   `q8`           TINYINT UNSIGNED,
//   `q9`           TINYINT UNSIGNED,
//   `q10`          TINYINT UNSIGNED,
//   `q11`          TINYINT UNSIGNED,
//   `q12`          TINYINT UNSIGNED,
//   `q13`          TINYINT UNSIGNED,
//   `q14`          TINYINT UNSIGNED,
//   `q15`          TINYINT UNSIGNED,
//   `q16`          TINYINT UNSIGNED,
//   `catatan`      TEXT NULL,
//   `submitted_at` DATETIME NULL DEFAULT NULL,
//   CONSTRAINT fk_penilaian_antrian FOREIGN KEY (antrian_id)
//     REFERENCES antrian(id) ON DELETE SET NULL
// );
//
// ── Tabel penilaian per item data yang dibutuhkan ─────────────────────
// CREATE TABLE `penilaian_data_item` (
//   `id`                INT AUTO_INCREMENT PRIMARY KEY,
//   `penilaian_id`      INT NOT NULL,
//   `nama_data`         VARCHAR(255),
//   `tahun_dari`        INT,
//   `tahun_sampai`      INT,
//   `nilai`             TINYINT UNSIGNED,            -- skala 1–10
//   `status_perolehan`  VARCHAR(50) NULL,
//   `untuk_perencanaan` VARCHAR(10) NULL,
//   CONSTRAINT fk_pdi_penilaian FOREIGN KEY (penilaian_id)
//     REFERENCES penilaian(id) ON DELETE CASCADE
// );
//
// ── Migrasi jika tabel sudah ada ─────────────────────────────────────
// ALTER TABLE `antrian`
//   ADD COLUMN IF NOT EXISTS `kunjungan_pst` TINYINT(1) DEFAULT 0 AFTER `keperluan`,
//   ADD COLUMN IF NOT EXISTS `token`         VARCHAR(64) UNIQUE;
// ALTER TABLE `penilaian`
//   ADD COLUMN IF NOT EXISTS `antrian_id`   INT AFTER `id`,
//   ADD COLUMN IF NOT EXISTS `catatan`      TEXT NULL,
//   ADD COLUMN IF NOT EXISTS `submitted_at` DATETIME NULL DEFAULT NULL;
// -- Tambahkan FK jika belum ada:
// ALTER TABLE `penilaian`
//   ADD CONSTRAINT fk_penilaian_antrian
//     FOREIGN KEY (antrian_id) REFERENCES antrian(id) ON DELETE SET NULL;
// ALTER TABLE `penilaian_data_item`
//   ADD COLUMN IF NOT EXISTS `status_perolehan`  VARCHAR(50) NULL,
//   ADD COLUMN IF NOT EXISTS `untuk_perencanaan` VARCHAR(10) NULL;
?>
