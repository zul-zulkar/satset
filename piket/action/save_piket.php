<?php
/**
 * Tambah / hapus penugasan piket mingguan.
 *
 * POST action     : add | remove
 * POST senin      : YYYY-MM-DD — HARUS hari Senin (kunci minggu piket)
 * POST pegawai_id : int
 *
 * Response: JSON { success, message }
 */
ob_start();
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);
session_set_cookie_params(['lifetime' => 60 * 60 * 24 * 30, 'samesite' => 'Lax']);
session_start();
ob_clean();

header('Content-Type: application/json');

if (empty($_SESSION['absensi_auth'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesi habis. Silakan login kembali.']);
    exit;
}

include __DIR__ . '/../../app/db.php';
require_once __DIR__ . '/../../app/wa.php';
wa_ensure_tables($mysqli);

$action    = trim($_POST['action'] ?? '');
$senin     = trim($_POST['senin']  ?? '');
$pegawaiId = (int)($_POST['pegawai_id'] ?? 0);

if (!in_array($action, ['add', 'remove'], true)) {
    echo json_encode(['success' => false, 'message' => 'Aksi tidak valid.']);
    exit;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $senin) || date('N', strtotime($senin)) !== '1') {
    echo json_encode(['success' => false, 'message' => 'Tanggal minggu tidak valid (harus hari Senin).']);
    exit;
}
if ($pegawaiId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Pegawai tidak valid.']);
    exit;
}

if ($action === 'add') {
    $st = $mysqli->prepare("SELECT id FROM pegawai WHERE id = ? LIMIT 1");
    $st->bind_param("i", $pegawaiId);
    $st->execute();
    $ada = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$ada) {
        echo json_encode(['success' => false, 'message' => 'Pegawai tidak ditemukan.']);
        exit;
    }

    $st = $mysqli->prepare("INSERT IGNORE INTO jadwal_piket (senin, pegawai_id) VALUES (?, ?)");
    $st->bind_param("si", $senin, $pegawaiId);
    $st->execute();
    $inserted = $st->affected_rows > 0;
    $st->close();

    echo json_encode($inserted
        ? ['success' => true,  'message' => 'Petugas ditambahkan ke jadwal.']
        : ['success' => false, 'message' => 'Petugas sudah terjadwal di minggu tersebut.']);
    exit;
}

// remove
$st = $mysqli->prepare("DELETE FROM jadwal_piket WHERE senin = ? AND pegawai_id = ?");
$st->bind_param("si", $senin, $pegawaiId);
$st->execute();
$st->close();

echo json_encode(['success' => true, 'message' => 'Petugas dihapus dari jadwal.']);
