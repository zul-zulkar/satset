<?php
include '../../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']); exit;
}

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
    nilai_inovatif TINYINT UNSIGNED DEFAULT NULL,
    nilai_penampilan TINYINT UNSIGNED DEFAULT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_tp (pegawai_id, bulan, tahun, nama_penilai)
)");

$tipe       = $_POST['tipe']       ?? '';
$pegawai_id = intval($_POST['pegawai_id'] ?? 0);
$bulan      = intval($_POST['bulan']      ?? 0);
$tahun      = intval($_POST['tahun']      ?? 0);

if (!$pegawai_id || !$bulan || !$tahun) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']); exit;
}

if ($tipe === 'kinerja') {
    $nilai = intval($_POST['nilai'] ?? 0);
    if ($nilai < 1 || $nilai > 100) {
        echo json_encode(['success' => false, 'message' => 'Nilai harus antara 1–100']); exit;
    }
    $stmt = $mysqli->prepare("
        INSERT INTO penghargaan_penilaian (pegawai_id, bulan, tahun, nilai_kinerja)
        VALUES (?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE nilai_kinerja = VALUES(nilai_kinerja)
    ");
    $stmt->bind_param("iiii", $pegawai_id, $bulan, $tahun, $nilai);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => $stmt->error]); exit;
    }
    $stmt->close();
    echo json_encode(['success' => true]);

} elseif ($tipe === 'tim_penilai') {
    $VALID = ['iwansantika', 'madepratiwi', 'ariwijaya'];
    $nama_penilai = trim($_POST['nama_penilai'] ?? '');
    if (!in_array($nama_penilai, $VALID)) {
        echo json_encode(['success' => false, 'message' => 'Nama penilai tidak valid']); exit;
    }
    $ks  = intval($_POST['nilai_kerja_sama'] ?? 0);
    $inv = intval($_POST['nilai_inovatif']   ?? 0);
    $pen = intval($_POST['nilai_penampilan'] ?? 0);
    foreach ([$ks, $inv, $pen] as $v) {
        if ($v < 1 || $v > 100) {
            echo json_encode(['success' => false, 'message' => 'Semua nilai harus antara 1–100']); exit;
        }
    }
    $stmt = $mysqli->prepare("
        INSERT INTO penghargaan_tim_penilai
            (pegawai_id, bulan, tahun, nama_penilai, nilai_kerja_sama, nilai_inovatif, nilai_penampilan)
        VALUES (?, ?, ?, ?, ?, ?, ?)
        ON DUPLICATE KEY UPDATE
            nilai_kerja_sama = VALUES(nilai_kerja_sama),
            nilai_inovatif   = VALUES(nilai_inovatif),
            nilai_penampilan = VALUES(nilai_penampilan)
    ");
    $stmt->bind_param("iiisiii", $pegawai_id, $bulan, $tahun, $nama_penilai, $ks, $inv, $pen);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => $stmt->error]); exit;
    }
    $stmt->close();
    echo json_encode(['success' => true]);

} else {
    echo json_encode(['success' => false, 'message' => 'Tipe tidak dikenal']);
}
