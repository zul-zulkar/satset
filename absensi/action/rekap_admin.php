<?php
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);
session_set_cookie_params(['lifetime' => 60 * 60 * 24 * 30, 'samesite' => 'Lax']);
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['absensi_admin'])) {
    echo json_encode(['error' => 'Unauthorized']); exit;
}

include_once __DIR__ . '/../../db.php';

$bulan = $_GET['bulan'] ?? date('Y-m');

if (!preg_match('/^\d{4}-\d{2}$/', $bulan)) {
    echo json_encode(['error' => 'Format bulan tidak valid']); exit;
}

$stmt = $mysqli->prepare(
    "SELECT ap.tanggal, ap.jam_masuk, ap.jam_keluar, p.nama, p.jabatan
     FROM absensi_piket ap
     JOIN pegawai p ON p.id = ap.pegawai_id
     WHERE DATE_FORMAT(ap.tanggal, '%Y-%m') = ?
     ORDER BY ap.tanggal ASC, p.nama ASC"
);
$stmt->bind_param("s", $bulan);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

foreach ($rows as &$r) {
    $r['jam_masuk']  = $r['jam_masuk']  ? date('H:i', strtotime($r['jam_masuk']))  : null;
    $r['jam_keluar'] = $r['jam_keluar'] ? date('H:i', strtotime($r['jam_keluar'])) : null;
}
unset($r);

echo json_encode(['data' => $rows]);
