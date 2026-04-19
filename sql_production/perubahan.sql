-- =============================================================================
--  MIGRASI DATABASE — Sistem Antrean BPS Kabupaten Buleleng
--  Jalankan seluruh file ini di phpMyAdmin production (satu kali).
--
--  Aman dijalankan berulang untuk tabel yang BELUM ada.
--  Untuk kolom di tabel yang SUDAH ada, lihat catatan di masing-masing bagian.
-- =============================================================================


-- ─────────────────────────────────────────────────────────────────────────────
--  BAGIAN 0: TABEL PEGAWAI PST (baru — selalu aman dijalankan)
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `pegawai` (
  `id`             INT          AUTO_INCREMENT PRIMARY KEY,
  `nama`           VARCHAR(255) NOT NULL,
  `username`       VARCHAR(100) NULL,
  `nip`            VARCHAR(30)  NULL,
  `nip_lama`       VARCHAR(30)  NULL,
  `jabatan`        VARCHAR(255) NULL,
  `status`         VARCHAR(100) NULL,
  `pangkat`        VARCHAR(100) NULL,
  `golongan`       VARCHAR(20)  NULL,
  `kelas_jabatan`  VARCHAR(20)  NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Jika tabel pegawai sudah ada, jalankan ALTER berikut:
-- ALTER TABLE `pegawai`
--   ADD COLUMN IF NOT EXISTS `username`      VARCHAR(100) NULL AFTER `nama`,
--   ADD COLUMN IF NOT EXISTS `nip_lama`      VARCHAR(30)  NULL AFTER `nip`,
--   ADD COLUMN IF NOT EXISTS `jabatan`       VARCHAR(255) NULL AFTER `nip_lama`,
--   ADD COLUMN IF NOT EXISTS `status`        VARCHAR(100) NULL AFTER `jabatan`,
--   ADD COLUMN IF NOT EXISTS `pangkat`       VARCHAR(100) NULL AFTER `status`,
--   ADD COLUMN IF NOT EXISTS `golongan`      VARCHAR(20)  NULL AFTER `pangkat`,
--   ADD COLUMN IF NOT EXISTS `kelas_jabatan` VARCHAR(20)  NULL AFTER `golongan`;


-- ─────────────────────────────────────────────────────────────────────────────
--  BAGIAN 1: BUAT TABEL (skip otomatis jika sudah ada)
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `antrian` (
  `id`                INT          AUTO_INCREMENT PRIMARY KEY,
  `nama`              VARCHAR(255),
  `email`             VARCHAR(255),
  `telepon`           VARCHAR(20),
  `instansi`          VARCHAR(255),
  `jk`                ENUM('L','P'),
  `jenis`             ENUM('umum','disabilitas','whatsapp'),
  `nomor`             INT,
  `tanggal`           DATE,
  `status`            ENUM('menunggu','dipanggil') DEFAULT 'menunggu',
  `jumlah_orang`      INT,
  `keperluan`         TEXT,
  `kunjungan_pst`     TINYINT(1) NOT NULL DEFAULT 0,
  `pendidikan`        VARCHAR(20),
  `kelompok_umur`     VARCHAR(25),
  `pekerjaan`         VARCHAR(255),
  `pemanfaatan_data`  VARCHAR(50),
  `data_dibutuhkan`   TEXT,
  `token`             VARCHAR(64) UNIQUE,
  `token_pes`         VARCHAR(64) UNIQUE,
  `created_at`        DATETIME NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `penilaian` (
  `id`           INT AUTO_INCREMENT PRIMARY KEY,
  `antrian_id`   INT,
  `tanggal`      DATE NOT NULL,
  `q1`           TINYINT UNSIGNED,
  `q2`           TINYINT UNSIGNED,
  `q3`           TINYINT UNSIGNED,
  `q4`           TINYINT UNSIGNED,
  `q5`           TINYINT UNSIGNED,
  `q6`           TINYINT UNSIGNED,
  `q7`           TINYINT UNSIGNED,
  `q8`           TINYINT UNSIGNED,
  `q9`           TINYINT UNSIGNED,
  `q10`          TINYINT UNSIGNED,
  `q11`          TINYINT UNSIGNED,
  `q12`          TINYINT UNSIGNED,
  `q13`          TINYINT UNSIGNED,
  `q14`          TINYINT UNSIGNED,
  `q15`          TINYINT UNSIGNED,
  `q16`          TINYINT UNSIGNED,
  `catatan`      TEXT NULL,
  `submitted_at` DATETIME NULL DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pes` (
  `id`                        INT          AUTO_INCREMENT PRIMARY KEY,
  `antrian_id`                INT          NOT NULL,
  `petugas_utama_id`          INT          NULL,
  `kategori_instansi`         VARCHAR(100) NULL,
  `kategori_instansi_lainnya` VARCHAR(255) NULL,
  `jenis_layanan`             TEXT         NULL,  -- JSON array of strings
  `sarana`                    TEXT         NULL,  -- JSON array of strings
  `sarana_lainnya`            VARCHAR(255) NULL,
  `submitted_at`              DATETIME     NULL DEFAULT NULL,
  CONSTRAINT fk_pes_antrian FOREIGN KEY (antrian_id)
    REFERENCES antrian(id) ON DELETE CASCADE,
  CONSTRAINT fk_pes_petugas FOREIGN KEY (petugas_utama_id)
    REFERENCES pegawai(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pes_pembantu` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `pes_id`     INT NOT NULL,
  `pegawai_id` INT NOT NULL,
  CONSTRAINT fk_pespmb_pes     FOREIGN KEY (pes_id)     REFERENCES pes(id)     ON DELETE CASCADE,
  CONSTRAINT fk_pespmb_pegawai FOREIGN KEY (pegawai_id) REFERENCES pegawai(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `penilaian_data_item` (
  `id`                INT AUTO_INCREMENT PRIMARY KEY,
  `penilaian_id`      INT NOT NULL,
  `nama_data`         VARCHAR(255),
  `tahun_dari`        INT,
  `tahun_sampai`      INT,
  `nilai`             TINYINT UNSIGNED,
  `status_perolehan`  ENUM('Ya, sesuai','Ya, tidak sesuai','Tidak diperoleh','Belum diperoleh'),
  `untuk_perencanaan` ENUM('ya','tidak'),
  CONSTRAINT fk_pdi_penilaian FOREIGN KEY (penilaian_id)
    REFERENCES penilaian(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ─────────────────────────────────────────────────────────────────────────────
--  BAGIAN 2: MIGRASI KOLOM — jalankan hanya jika tabel SUDAH ADA sebelumnya
--
--  Jika tabel baru dibuat dari BAGIAN 1 di atas, lewati bagian ini.
--  Jika tabel sudah ada sejak sebelumnya, jalankan ALTER berikut.
--  Error "Duplicate column name" berarti kolom sudah ada — aman diabaikan.
-- ─────────────────────────────────────────────────────────────────────────────

-- Tambah kolom-kolom baru ke antrian
ALTER TABLE `antrian`
  ADD COLUMN `email`             VARCHAR(255)         AFTER `nama`,
  ADD COLUMN `jumlah_orang`      INT                  AFTER `jk`,
  ADD COLUMN `keperluan`         TEXT                 AFTER `jumlah_orang`,
  ADD COLUMN `kunjungan_pst`     TINYINT(1) DEFAULT 0 AFTER `keperluan`,
  ADD COLUMN `kelompok_umur`     VARCHAR(25)          AFTER `pendidikan`,
  ADD COLUMN `pemanfaatan_data`  VARCHAR(50)          AFTER `pekerjaan`,
  ADD COLUMN `data_dibutuhkan`   TEXT                 AFTER `pemanfaatan_data`,
  ADD COLUMN `token`             VARCHAR(64) UNIQUE,
  ADD COLUMN `token_pes`         VARCHAR(64) UNIQUE,
  MODIFY COLUMN `jenis`          ENUM('umum','disabilitas','whatsapp'),
  MODIFY COLUMN `pendidikan`     VARCHAR(20);

-- Tandai semua entri whatsapp lama sebagai kunjungan PST
UPDATE `antrian` SET `kunjungan_pst` = 1 WHERE `jenis` = 'whatsapp' AND `kunjungan_pst` = 0;

-- Tambah kolom-kolom baru ke penilaian
ALTER TABLE `penilaian`
  ADD COLUMN `antrian_id`   INT          AFTER `id`,
  ADD COLUMN `catatan`      TEXT NULL,
  ADD COLUMN `submitted_at` DATETIME NULL DEFAULT NULL;

-- Tambah kolom-kolom baru ke penilaian_data_item
ALTER TABLE `penilaian_data_item`
  ADD COLUMN `status_perolehan`  ENUM('Ya, sesuai','Ya, tidak sesuai','Tidak diperoleh','Belum diperoleh'),
  ADD COLUMN `untuk_perencanaan` ENUM('ya','tidak');

-- Tambah jenis 'surat' ke ENUM antrian
ALTER TABLE `antrian`
  MODIFY COLUMN `jenis` ENUM('umum','disabilitas','whatsapp','surat');

-- Tambah kolom created_at ke antrian (untuk jendela revisi 2 jam form whatsapp)
-- (lewati jika sudah ada — error "Duplicate column name" aman diabaikan)
ALTER TABLE `antrian`
  ADD COLUMN `created_at` DATETIME NULL DEFAULT CURRENT_TIMESTAMP;

-- Tambah kolom link surat masuk
-- (lewati jika sudah ada kolom link_surat — error "Duplicate column name" aman diabaikan)
ALTER TABLE `antrian`
  ADD COLUMN `link_surat` VARCHAR(500) NULL;

-- Tambah kolom link surat balasan ke tabel pes
-- (lewati jika sudah ada — error "Duplicate column name" aman diabaikan)
ALTER TABLE `pes`
  ADD COLUMN `link_surat_balasan` VARCHAR(500) NULL;


-- ─────────────────────────────────────────────────────────────────────────────
--  BAGIAN 2b: TABEL PES_KEBUTUHAN_DATA (baru — selalu aman dijalankan)
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `pes_kebutuhan_data` (
  `id`               INT          AUTO_INCREMENT PRIMARY KEY,
  `pes_id`           INT          NOT NULL,
  `butir_kebutuhan`  TEXT         NULL,
  `jenis_sumber_data` VARCHAR(100) NULL,
  `judul_sumber_data` TEXT         NULL,
  `tahun_sumber_data` INT          NULL,
  CONSTRAINT `fk_pkd_pes` FOREIGN KEY (`pes_id`) REFERENCES `pes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ─────────────────────────────────────────────────────────────────────────────
--  BAGIAN 3: TABEL ABSENSI PIKET PST (baru — selalu aman dijalankan)
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `absensi_piket` (
  `id`          INT            AUTO_INCREMENT PRIMARY KEY,
  `pegawai_id`  INT            NOT NULL,
  `tanggal`     DATE           NOT NULL,
  `jam_masuk`   DATETIME       NULL,
  `jam_keluar`  DATETIME       NULL,
  `lat_masuk`   DECIMAL(10,8)  NULL,
  `lng_masuk`   DECIMAL(11,8)  NULL,
  `lat_keluar`  DECIMAL(10,8)  NULL,
  `lng_keluar`  DECIMAL(11,8)  NULL,
  UNIQUE KEY `uq_pegawai_tanggal` (`pegawai_id`, `tanggal`),
  CONSTRAINT `fk_absensi_pegawai`
    FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
