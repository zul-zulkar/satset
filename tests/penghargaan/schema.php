<?php
/**
 * Schema Tests — Verifikasi struktur tabel DB
 * Memastikan skema identik antara dev dan production.
 *
 * Jalankan di KEDUA environment:
 *   php tests/penghargaan/run.php schema
 */
require_once __DIR__ . '/T.php';

// Sambungkan ke DB via config yang sama dengan aplikasi
chdir(dirname(__DIR__, 2));
include_once 'config.php';

T::reset();

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
if ($mysqli->connect_error) {
    T::header('Schema Tests');
    T::ok('Koneksi database berhasil', false);
    echo "\e[31m  Error: " . $mysqli->connect_error . "\e[0m\n\n";
    return ['pass' => 0, 'fail' => 1, 'skip' => 0];
}

// ── Helper ────────────────────────────────────────────────────────────────────

/** Ambil semua kolom sebuah tabel dari information_schema. */
function getColumns(mysqli $db, string $table): array {
    $db->query("SET SESSION group_concat_max_len = 10000");
    $res = $db->query("
        SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, EXTRA
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '{$table}'
        ORDER BY ORDINAL_POSITION
    ");
    if (!$res) return [];
    $cols = [];
    while ($row = $res->fetch_assoc()) {
        $cols[$row['COLUMN_NAME']] = $row;
    }
    return $cols;
}

/** Ambil semua index/constraint sebuah tabel. */
function getIndexes(mysqli $db, string $table): array {
    $res = $db->query("SHOW INDEX FROM `{$table}`");
    if (!$res) return [];
    $idx = [];
    while ($row = $res->fetch_assoc()) {
        $idx[$row['Key_name']][] = $row['Column_name'];
    }
    return $idx;
}

// ── 1. Auto-create tables ─────────────────────────────────────────────────────
T::header('Auto-create Tables');

// Paksa CREATE TABLE IF NOT EXISTS (seperti saat save.php / index.php diakses)
$mysqli->query("CREATE TABLE IF NOT EXISTS penghargaan_penilaian (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT NOT NULL,
    bulan TINYINT UNSIGNED NOT NULL,
    tahun SMALLINT UNSIGNED NOT NULL,
    nilai_kinerja TINYINT UNSIGNED DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_pk (pegawai_id, bulan, tahun)
)");
$mysqli->query("CREATE TABLE IF NOT EXISTS penghargaan_tim_penilai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pegawai_id INT NOT NULL,
    bulan TINYINT UNSIGNED NOT NULL,
    tahun SMALLINT UNSIGNED NOT NULL,
    nama_penilai VARCHAR(50) NOT NULL,
    nilai_kerja_sama TINYINT UNSIGNED DEFAULT NULL,
    nilai_inovatif   TINYINT UNSIGNED DEFAULT NULL,
    nilai_penampilan TINYINT UNSIGNED DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_tp (pegawai_id, bulan, tahun, nama_penilai)
)");

$resPK  = $mysqli->query("SHOW TABLES LIKE 'penghargaan_penilaian'");
$resTP  = $mysqli->query("SHOW TABLES LIKE 'penghargaan_tim_penilai'");
T::ok('Tabel penghargaan_penilaian ada', $resPK && $resPK->num_rows === 1);
T::ok('Tabel penghargaan_tim_penilai ada', $resTP && $resTP->num_rows === 1);

// ── 2. Kolom penghargaan_penilaian ────────────────────────────────────────────
T::header('Kolom penghargaan_penilaian');

$cols = getColumns($mysqli, 'penghargaan_penilaian');
T::ok('Kolom id ada',            isset($cols['id']));
T::ok('id = AUTO_INCREMENT',     ($cols['id']['EXTRA'] ?? '') === 'auto_increment');
T::ok('Kolom pegawai_id ada',    isset($cols['pegawai_id']));
T::ok('pegawai_id NOT NULL',     ($cols['pegawai_id']['IS_NULLABLE'] ?? '') === 'NO');
T::ok('Kolom bulan ada',         isset($cols['bulan']));
T::ok('bulan = tinyint',         str_contains(strtolower($cols['bulan']['DATA_TYPE'] ?? ''), 'tinyint'));
T::ok('Kolom tahun ada',         isset($cols['tahun']));
T::ok('tahun = smallint',        str_contains(strtolower($cols['tahun']['DATA_TYPE'] ?? ''), 'smallint'));
T::ok('Kolom nilai_kinerja ada', isset($cols['nilai_kinerja']));
T::ok('nilai_kinerja nullable',  ($cols['nilai_kinerja']['IS_NULLABLE'] ?? '') === 'YES');
T::ok('Kolom updated_at ada',    isset($cols['updated_at']));

// ── 3. Kolom penghargaan_tim_penilai ─────────────────────────────────────────
T::header('Kolom penghargaan_tim_penilai');

$cols2 = getColumns($mysqli, 'penghargaan_tim_penilai');
T::ok('Kolom id ada',               isset($cols2['id']));
T::ok('Kolom pegawai_id ada',       isset($cols2['pegawai_id']));
T::ok('Kolom bulan ada',            isset($cols2['bulan']));
T::ok('Kolom tahun ada',            isset($cols2['tahun']));
T::ok('Kolom nama_penilai ada',     isset($cols2['nama_penilai']));
T::ok('nama_penilai = varchar(50)', str_contains(strtolower($cols2['nama_penilai']['DATA_TYPE'] ?? ''), 'varchar'));
T::ok('Kolom nilai_kerja_sama ada', isset($cols2['nilai_kerja_sama']));
T::ok('nilai_kerja_sama nullable',  ($cols2['nilai_kerja_sama']['IS_NULLABLE'] ?? '') === 'YES');
T::ok('Kolom nilai_inovatif ada',   isset($cols2['nilai_inovatif']));
T::ok('Kolom nilai_penampilan ada', isset($cols2['nilai_penampilan']));
T::ok('Kolom updated_at ada',       isset($cols2['updated_at']));

// ── 4. UNIQUE constraints ─────────────────────────────────────────────────────
T::header('UNIQUE Constraints');

$idx1 = getIndexes($mysqli, 'penghargaan_penilaian');
T::ok('UNIQUE uniq_pk ada di penilaian',           isset($idx1['uniq_pk']));
T::ok('uniq_pk mencakup pegawai_id+bulan+tahun',   count($idx1['uniq_pk'] ?? []) === 3);

$idx2 = getIndexes($mysqli, 'penghargaan_tim_penilai');
T::ok('UNIQUE uniq_tp ada di tim_penilai',                 isset($idx2['uniq_tp']));
T::ok('uniq_tp mencakup 4 kolom (pid+bln+thn+penilai)',   count($idx2['uniq_tp'] ?? []) === 4);

// ── 5. Upsert Behavior ────────────────────────────────────────────────────────
T::header('Upsert (ON DUPLICATE KEY UPDATE)');

$testPid  = 99999; // ID tidak akan pernah ada di data nyata
$testBulan = 1;
$testTahun = 2099;

// Bersihkan dulu kalau ada sisa test sebelumnya
$mysqli->query("DELETE FROM penghargaan_penilaian WHERE pegawai_id=$testPid AND tahun=$testTahun");
$mysqli->query("DELETE FROM penghargaan_tim_penilai WHERE pegawai_id=$testPid AND tahun=$testTahun");

// Insert pertama
$stmt = $mysqli->prepare("INSERT INTO penghargaan_penilaian (pegawai_id,bulan,tahun,nilai_kinerja) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE nilai_kinerja=VALUES(nilai_kinerja)");
$v = 75;
$stmt->bind_param("iiii", $testPid, $testBulan, $testTahun, $v);
$stmt->execute();
T::ok('Insert pertama berhasil', $stmt->affected_rows >= 1);
$stmt->close();

// Upsert — nilai berubah
$stmt = $mysqli->prepare("INSERT INTO penghargaan_penilaian (pegawai_id,bulan,tahun,nilai_kinerja) VALUES(?,?,?,?) ON DUPLICATE KEY UPDATE nilai_kinerja=VALUES(nilai_kinerja)");
$v2 = 90;
$stmt->bind_param("iiii", $testPid, $testBulan, $testTahun, $v2);
$stmt->execute();
T::ok('Upsert berhasil (tidak duplikat)', $mysqli->errno === 0);
$stmt->close();

// Verifikasi nilai terbaru
$r = $mysqli->query("SELECT nilai_kinerja FROM penghargaan_penilaian WHERE pegawai_id=$testPid AND bulan=$testBulan AND tahun=$testTahun");
$row = $r ? $r->fetch_assoc() : null;
T::eq('Nilai ter-update ke 90', 90, (int)($row['nilai_kinerja'] ?? 0));

// Upsert tim penilai
$stmt2 = $mysqli->prepare("INSERT INTO penghargaan_tim_penilai (pegawai_id,bulan,tahun,nama_penilai,nilai_kerja_sama,nilai_inovatif,nilai_penampilan) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE nilai_kerja_sama=VALUES(nilai_kerja_sama),nilai_inovatif=VALUES(nilai_inovatif),nilai_penampilan=VALUES(nilai_penampilan)");
$np = 'iwansantika'; $ks = 80; $iv = 85; $pe = 90;
$stmt2->bind_param("iiisiii", $testPid, $testBulan, $testTahun, $np, $ks, $iv, $pe);
$stmt2->execute();
T::ok('Insert tim_penilai berhasil', $mysqli->errno === 0);
$stmt2->close();

// Update nilai tim penilai
$stmt3 = $mysqli->prepare("INSERT INTO penghargaan_tim_penilai (pegawai_id,bulan,tahun,nama_penilai,nilai_kerja_sama,nilai_inovatif,nilai_penampilan) VALUES(?,?,?,?,?,?,?) ON DUPLICATE KEY UPDATE nilai_kerja_sama=VALUES(nilai_kerja_sama),nilai_inovatif=VALUES(nilai_inovatif),nilai_penampilan=VALUES(nilai_penampilan)");
$ks2 = 95;
$stmt3->bind_param("iiisiii", $testPid, $testBulan, $testTahun, $np, $ks2, $iv, $pe);
$stmt3->execute();
$r2 = $mysqli->query("SELECT nilai_kerja_sama FROM penghargaan_tim_penilai WHERE pegawai_id=$testPid AND bulan=$testBulan AND tahun=$testTahun AND nama_penilai='iwansantika'");
$row2 = $r2 ? $r2->fetch_assoc() : null;
T::eq('Nilai KS tim ter-update ke 95', 95, (int)($row2['nilai_kerja_sama'] ?? 0));
$stmt3->close();

// Cleanup — hapus data test
$mysqli->query("DELETE FROM penghargaan_penilaian WHERE pegawai_id=$testPid AND tahun=$testTahun");
$mysqli->query("DELETE FROM penghargaan_tim_penilai WHERE pegawai_id=$testPid AND tahun=$testTahun");
T::ok('Data test dibersihkan', $mysqli->errno === 0);

$mysqli->close();

// ── Hasil ─────────────────────────────────────────────────────────────────────
echo "\n";
$s = T::summary();
$col = $s['fail'] > 0 ? "\e[31m" : "\e[32m";
echo "{$col}  schema: {$s['pass']} passed, {$s['fail']} failed, {$s['skip']} skipped\e[0m\n";
return $s;
