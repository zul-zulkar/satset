-- =============================================================================
--  MIGRASI PRODUCTION — Sistem Antrean BPS Kabupaten Buleleng
--  Dijalankan di: phpMyAdmin production (if0_41029675_db_5108_satset)
--  Tanggal dibuat: 2026-04-06
--
--  Aman dijalankan satu kali. Tidak menghapus data yang sudah ada.
--  Jalankan seluruh file ini sekaligus.
-- =============================================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;


-- ─────────────────────────────────────────────────────────────────────────────
--  BAGIAN 1: TABEL PEGAWAI (baru)
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

-- Data pegawai (36 orang) — INSERT IGNORE agar aman dijalankan ulang
INSERT IGNORE INTO `pegawai` (`id`,`nama`,`username`,`nip`,`nip_lama`,`jabatan`,`status`,`pangkat`,`golongan`,`kelas_jabatan`) VALUES
(1,'Gede Iwan Santika SST, M.M.','iwansantika','197807072002121002','340016548','Kepala BPS Kabupaten Buleleng','PNS','Pembina','IV/a','12'),
(2,'I Ketut Ariasa, SE','ariasa','197306041994011001','340014384','Kepala Subbagian Umum','PNS','Penata Tk. I','III/d','9'),
(3,'I Komang Ari Wijaya, SST, M.Agb','ariwijaya','198611152009021002','340050119','Statistisi Ahli Madya','PNS','Pembina','IV/a','11'),
(4,'Ketut Ksama Putra SST., M.Si.','ksama','199004042013111001','340056334','Statistisi Ahli Muda','PNS','Penata Tk. I','III/d','9'),
(5,'Alit Mahendra, SST','alit.mahendra','199204222014121001','340056987','Statistisi Ahli Muda','PNS','Penata','III/c','9'),
(6,'Nyoman Subaktiyasa, SE','subaktiyasa','197106261994011001','340014279','Statistisi Ahli Muda','PNS','Penata Tk. I','III/d','9'),
(7,'Ni Made Egy Wira Astuti, SST','nimade.astuti','199203302014122001','340057158','Statistisi Ahli Muda','PNS','Penata Tk. I','III/d','9'),
(8,'Ni Made Pratiwi Pendit, S.Si., M.Si','madepratiwi','198609142009022007','340051240','Statistisi Ahli Muda','PNS','Penata Tk. I','III/d','9'),
(9,'Raden Agus Setiyo Purnawan, SE','raden.agus','197008251994011001','340014282','Statistisi Ahli Muda','PNS','Penata Tk. I','III/d','9'),
(10,'I Made Oka Suarjaya, SST, M.SE.','imadeoka','198809162010121001','340054089','Statistisi Ahli Muda','PNS','Penata','III/c','9'),
(11,'Garinca Firgiana Santoso, S.Tr.Stat.','garinca.santoso','199902162022012001','340060657','Statistisi Ahli Pertama','PNS','Penata Muda Tk. I','III/b','8'),
(12,'I Made Kariasa SST, M.SE.','imade.kariasa','199103292014121001','340057091','Pranata Komputer Ahli Muda','PNS','Penata Tk. I','III/d','9'),
(13,'Rizq Taufiq Bahtiar Razendrya, S.Tr.Stat.','rizq.taufiq','200104132023021003','340062033','Statistisi Ahli Pertama','PNS','Penata Muda','III/a','8'),
(14,'Kadek Suradnyana Wisnawa, SST','kadek.wisnawa','199405202017011001','340057895','Statistisi Ahli Pertama','PNS','Penata Muda Tk. I','III/b','8'),
(15,'Amalia Susanti, S.Tr.Stat.','amalia.susanti','199901112022012001','340060511','Statistisi Ahli Pertama','PNS','Penata Muda Tk. I','III/b','8'),
(16,'Ni Luh Putu Yayang Septia Ningsih, S.Tr.Stat.','yayangseptia','199812302022012001','340060813','Statistisi Ahli Pertama','PNS','Penata Muda Tk. I','III/b','8'),
(17,'Novia Putri Lestari, S.Tr.Stat.','novia.putri','199811242022012002','340060825','Statistisi Ahli Pertama','PNS','Penata Muda','III/a','8'),
(18,'Kharisma Pandu Utama, S.Tr.Stat.','pandu.utama','200110172023101002','340062518','Statistisi Ahli Pertama','PNS','Penata Muda','III/a','8'),
(19,'Sita Dian Maretna, S.Tr.Stat.','sitadian','200003302023022001','340062058','Pranata Komputer Ahli Pertama','PNS','Penata Muda','III/a','8'),
(20,'Erik Rihendri Candra Adifa, S.Tr.Stat.','erik.rihendri','199905202023021001','340061762','Pranata Komputer Ahli Pertama','PNS','Penata Muda','III/a','8'),
(21,'Nyoman Pasek Susena, SE','paseksusena','198612232011011006','340055165','Pranata Komputer Ahli Pertama','PNS','Penata','III/c','8'),
(22,'I Made Resdana','im.resdana','196807172006041017','340018847','Statistisi Mahir','PNS','Penata Muda Tk. I','III/b','7'),
(23,'Made Sunika','made.sunika','197204012007101001','340020560','Statistisi Mahir','PNS','Penata Muda Tk. I','III/b','7'),
(24,'I Gede Setya Budhi','gedesetyabudhi','197907052006041005','340018381','Pranata Keuangan APBN Terampil','PNS','Pengatur Tk. I','II/d','7'),
(25,'I Nyoman Samiada','in.samiada','196902011989021001','340012178','Fungsional Umum','PNS','Penata Muda Tk. I','III/b','6'),
(26,'Nyoman Redita','nyoman.redita','197009302006041007','340018940','Statistisi Mahir','PNS','Penata Muda','III/a','6'),
(27,'I Putu Wardana Gelgel','gel.gel','197104072009011004','340052241','Fungsional Umum','PNS','Penata Muda','III/a','6'),
(28,'Fadly Muhamad Akbar, S.Tr.Stat.','fadly.akbar','200103202024121004','340063146','Fungsional Umum / Statistisi Ahli Pertama','PNS','Penata Muda','III/a','8 (80%)'),
(29,'Kasah Aisyah, A.Md.Stat.','kasah.aisyah','200105212024122001','340063255','Fungsional Umum / Statistisi Pelaksana','PNS','Pengatur','II/c','6 (80%)'),
(30,'I Ketut Edi Mudarta','iketutedi-pppk','198307302025211036','340064766','PPPK - Operator Layanan Operasional','PPPK',NULL,'V','5'),
(31,'I Made Wiradana','imadewiradana-pppk','198507072025211071','340064776','PPPK - Operator Layanan Operasional','PPPK',NULL,'V','5'),
(32,'Ketut Swadana','ketutswadana-pppk','196911272025211007','340064968','PPPK - Operator Layanan Operasional','PPPK',NULL,'V','5'),
(33,'Putri Octaviana','putrioctavia-pppk','199510142025212054','340065394','PPPK - Operator Layanan Operasional','PPPK',NULL,'V','5'),
(34,'Ni Nengah Sekar','ninengahsekar-pppk','198507172025212050','340065291','PPPK - Pengelola Umum Operasional','PPPK',NULL,'III','1'),
(35,'Muhammad Zulkarnain, S.Tr.Stat','m.zulkarnain','199901152023021001','340061945','Pranata Komputer Ahli Pertama','PNS','Penata Muda','III/a','8'),
(36,'Ardian Putra Wardana, S.Tr.Stat.','ardianputra','200305222026031001','340066129','Statistisi Ahli Pertama','CPNS','Penata Muda','III/a','8');


-- ─────────────────────────────────────────────────────────────────────────────
--  BAGIAN 2: MIGRASI TABEL ANTRIAN
--  - Konversi engine ke InnoDB
--  - Konversi charset ke utf8mb4
--  - Tambah kolom token_pes (jika belum ada)
--  Catatan: kolom lama (lahir, alamat, data_yang_diperlukan, metode) dibiarkan,
--           tidak perlu dihapus karena tidak mengganggu fungsionalitas.
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE `antrian`
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `antrian` ENGINE=InnoDB;

-- Tambah token_pes jika belum ada
-- (Error "Duplicate column" aman diabaikan)
ALTER TABLE `antrian`
  ADD COLUMN `token_pes` VARCHAR(64) NULL DEFAULT NULL AFTER `token`;

ALTER TABLE `antrian`
  ADD UNIQUE KEY `token_pes` (`token_pes`);


-- ─────────────────────────────────────────────────────────────────────────────
--  BAGIAN 3: MIGRASI TABEL PENILAIAN
--  - Konversi charset ke utf8mb4
--  - Ubah submitted_at: timestamp NOT NULL → DATETIME NULL DEFAULT NULL
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE `penilaian`
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

ALTER TABLE `penilaian`
  MODIFY COLUMN `submitted_at` DATETIME NULL DEFAULT NULL;


-- ─────────────────────────────────────────────────────────────────────────────
--  BAGIAN 4: MIGRASI TABEL PENILAIAN_DATA_ITEM
--  - Konversi charset ke utf8mb4
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE `penilaian_data_item`
  CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;


-- ─────────────────────────────────────────────────────────────────────────────
--  BAGIAN 5: TABEL PES DAN PES_PEMBANTU (baru)
-- ─────────────────────────────────────────────────────────────────────────────

CREATE TABLE IF NOT EXISTS `pes` (
  `id`                        INT          AUTO_INCREMENT PRIMARY KEY,
  `antrian_id`                INT          NOT NULL,
  `petugas_utama_id`          INT          NULL,
  `kategori_instansi`         VARCHAR(100) NULL,
  `kategori_instansi_lainnya` VARCHAR(255) NULL,
  `jenis_layanan`             TEXT         NULL,
  `sarana`                    TEXT         NULL,
  `sarana_lainnya`            VARCHAR(255) NULL,
  `submitted_at`              DATETIME     NULL DEFAULT NULL,
  CONSTRAINT `fk_pes_antrian`  FOREIGN KEY (`antrian_id`)        REFERENCES `antrian`(`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pes_petugas`  FOREIGN KEY (`petugas_utama_id`)  REFERENCES `pegawai`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `pes_pembantu` (
  `id`         INT AUTO_INCREMENT PRIMARY KEY,
  `pes_id`     INT NOT NULL,
  `pegawai_id` INT NOT NULL,
  CONSTRAINT `fk_pespmb_pes`     FOREIGN KEY (`pes_id`)     REFERENCES `pes`(`id`)     ON DELETE CASCADE,
  CONSTRAINT `fk_pespmb_pegawai` FOREIGN KEY (`pegawai_id`) REFERENCES `pegawai`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- ─────────────────────────────────────────────────────────────────────────────
--  BAGIAN 6: TABEL ABSENSI PIKET PST (baru)
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


SET foreign_key_checks = 1;


-- ─────────────────────────────────────────────────────────────────────────────
--  BAGIAN 7: KOLOM PASSWORD PEGAWAI (fitur ganti password per-akun)
-- ─────────────────────────────────────────────────────────────────────────────

ALTER TABLE `pegawai`
  ADD COLUMN IF NOT EXISTS `password` VARCHAR(255) NULL AFTER `username`;

-- Set default password untuk semua pegawai yang belum punya password
-- Hash ini adalah password default yang sama dengan sebelumnya
UPDATE `pegawai`
  SET `password` = '$2y$10$KSDLI2fyKlZjKbPeSH8PW.uFxsLmXqQcY6T3kA.rdZj3rXKd4kHzu'
  WHERE `password` IS NULL;


-- ─────────────────────────────────────────────────────────────────────────────
--  BAGIAN 8: FITUR PES — SENTIMEN, SUMBER DATA PER BUTIR KEBUTUHAN
-- ─────────────────────────────────────────────────────────────────────────────

-- Kolom sentimen kritik dan saran pada tabel pes
ALTER TABLE `pes`
  ADD COLUMN IF NOT EXISTS `sentimen_kritik_saran`
    ENUM('negatif','normal','positif') NULL
    AFTER `sarana_lainnya`;

-- Tabel detail sumber data per butir kebutuhan
CREATE TABLE IF NOT EXISTS `pes_kebutuhan_data` (
  `id`                INT           AUTO_INCREMENT PRIMARY KEY,
  `pes_id`            INT           NOT NULL,
  `butir_kebutuhan`   TEXT          NULL,
  `jenis_sumber_data` VARCHAR(100)  NULL,
  `judul_sumber_data` TEXT          NULL,
  `tahun_sumber_data` SMALLINT      NULL,
  CONSTRAINT `fk_peskd_pes` FOREIGN KEY (`pes_id`) REFERENCES `pes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
