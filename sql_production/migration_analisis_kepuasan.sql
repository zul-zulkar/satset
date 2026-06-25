-- ============================================================================
-- Migrasi: Modul Analisis Kepuasan Pengguna Data
-- Jalankan SEKALI di database produksi (dbsatset) sebelum deploy modul analisis/
-- ============================================================================

-- 1. Ragam disabilitas pada data kunjungan (untuk visualisasi "jenis disabilitas")
ALTER TABLE antrian
    ADD COLUMN jenis_disabilitas VARCHAR(50) NULL AFTER jenis;

-- 2. Daftar responden yang DIKELUARKAN dari analisis, per (tahun, triwulan).
--    Default: semua responden masuk; baris di sini = responden yang di-exclude.
CREATE TABLE IF NOT EXISTS analisis_exclude (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    tahun       SMALLINT NOT NULL,
    triwulan    TINYINT  NOT NULL,            -- 1..4 (diturunkan dari tanggal kunjungan)
    antrian_id  INT      NOT NULL,
    excluded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_periode_resp (tahun, triwulan, antrian_id),
    KEY idx_periode (tahun, triwulan)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
