<?php
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);
session_set_cookie_params(['lifetime' => 60 * 60 * 24 * 30, 'samesite' => 'Lax']);
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['absensi_auth'])) {
    echo json_encode(['success' => false, 'message' => 'Sesi tidak valid.']); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method tidak valid.']); exit;
}

include_once __DIR__ . '/../../db.php';

$pegawaiId   = (int) $_SESSION['pegawai_id'];
$passwordLama = $_POST['password_lama'] ?? '';
$passwordBaru = $_POST['password_baru'] ?? '';

if ($passwordLama === '' || $passwordBaru === '') {
    echo json_encode(['success' => false, 'message' => 'Semua field wajib diisi.']); exit;
}
if (mb_strlen($passwordBaru) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password baru minimal 6 karakter.']); exit;
}

// Ambil password saat ini
$stmt = $mysqli->prepare("SELECT password FROM pegawai WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $pegawaiId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row || !password_verify($passwordLama, $row['password'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'Password lama tidak sesuai.']); exit;
}

$hash = password_hash($passwordBaru, PASSWORD_BCRYPT);
$upd  = $mysqli->prepare("UPDATE pegawai SET password = ? WHERE id = ?");
$upd->bind_param("si", $hash, $pegawaiId);
$upd->execute();
$upd->close();

echo json_encode(['success' => true, 'message' => 'Password berhasil diubah.']);
