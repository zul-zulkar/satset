<?php
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);
session_set_cookie_params(['lifetime' => 60 * 60 * 24 * 30, 'samesite' => 'Lax']);
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['absensi_auth'])) {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

include_once __DIR__ . '/../../db.php';

$pegawaiId = (int) $_SESSION['pegawai_id'];
$bulan     = $_GET['bulan'] ?? date('Y-m');

// Validasi format Y-m
if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) {
    echo json_encode(['error' => 'Format bulan tidak valid']); exit;
}

$stmt = $mysqli->prepare(
    "SELECT tanggal, jam_masuk, jam_keluar
     FROM absensi_piket
     WHERE pegawai_id = ? AND DATE_FORMAT(tanggal, '%Y-%m') = ?
     ORDER BY tanggal ASC"
);
$stmt->bind_param("is", $pegawaiId, $bulan);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Format jam
foreach ($rows as &$r) {
    $r['jam_masuk']  = $r['jam_masuk']  ? date('H:i:s', strtotime($r['jam_masuk']))  : null;
    $r['jam_keluar'] = $r['jam_keluar'] ? date('H:i:s', strtotime($r['jam_keluar'])) : null;
}
unset($r);

echo json_encode(['data' => $rows]);
