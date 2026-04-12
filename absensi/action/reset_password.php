<?php
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);
session_set_cookie_params(['lifetime' => 60 * 60 * 24 * 30, 'samesite' => 'Lax']);
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['absensi_admin'])) {
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak valid.']); exit;
}

include_once __DIR__ . '/../../db.php';

$pegawaiId    = (int) ($_POST['pegawai_id'] ?? 0);
$passwordBaru = $_POST['password_baru'] ?? '';

if ($pegawaiId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Pegawai tidak valid.']); exit;
}
if (mb_strlen($passwordBaru) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password minimal 6 karakter.']); exit;
}

// Pastikan pegawai ada
$chk = $mysqli->prepare("SELECT id, nama FROM pegawai WHERE id = ? LIMIT 1");
$chk->bind_param("i", $pegawaiId);
$chk->execute();
$pegawai = $chk->get_result()->fetch_assoc();
$chk->close();

if (!$pegawai) {
    echo json_encode(['success' => false, 'message' => 'Pegawai tidak ditemukan.']); exit;
}

$hash = password_hash($passwordBaru, PASSWORD_BCRYPT);
$upd  = $mysqli->prepare("UPDATE pegawai SET password = ? WHERE id = ?");
$upd->bind_param("si", $hash, $pegawaiId);
$upd->execute();
$upd->close();

echo json_encode(['success' => true, 'message' => 'Password ' . $pegawai['nama'] . ' berhasil direset.']);
