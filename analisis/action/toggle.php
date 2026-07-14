<?php
/**
 * AJAX: masukkan / keluarkan responden dari analisis kepuasan.
 * Exclude disimpan per (tahun, triwulan, antrian_id). Tahun & triwulan
 * diturunkan dari tanggal kunjungan antrian agar konsisten antara
 * tampilan tahunan maupun triwulanan.
 *
 * POST: antrian_id (int), include (1 = masuk / 0 = dikeluarkan)
 * Respons: { success: bool, message: string }
 */
header('Content-Type: application/json');
include __DIR__ . '/../../app/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode tidak diizinkan.']); exit;
}

$antrianId = (int)($_POST['antrian_id'] ?? 0);
$include   = ($_POST['include'] ?? '1') === '1';
if ($antrianId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID responden tidak valid.']); exit;
}

// Ambil tanggal kunjungan → tentukan tahun & triwulan
$st = $mysqli->prepare("SELECT tanggal FROM antrian WHERE id = ?");
$st->bind_param("i", $antrianId); $st->execute();
$row = $st->get_result()->fetch_assoc(); $st->close();
if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Responden tidak ditemukan.']); exit;
}
$tahun = (int)date('Y', strtotime($row['tanggal']));
$tw    = intdiv((int)date('n', strtotime($row['tanggal'])) - 1, 3) + 1;

if ($include) {
    // Masuk analisis = hapus dari daftar exclude
    $st = $mysqli->prepare("DELETE FROM analisis_exclude WHERE tahun = ? AND triwulan = ? AND antrian_id = ?");
    $st->bind_param("iii", $tahun, $tw, $antrianId);
} else {
    // Dikeluarkan = tambahkan ke daftar exclude (idempoten)
    $st = $mysqli->prepare(
        "INSERT INTO analisis_exclude (tahun, triwulan, antrian_id) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE excluded_at = CURRENT_TIMESTAMP");
    $st->bind_param("iii", $tahun, $tw, $antrianId);
}
$ok = $st->execute();
$st->close();

echo json_encode($ok
    ? ['success' => true, 'message' => $include ? 'Responden dimasukkan.' : 'Responden dikeluarkan.']
    : ['success' => false, 'message' => 'Gagal menyimpan ke database.']);
