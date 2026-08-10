<?php
/**
 * Simpan / kosongkan nomor HP pegawai (dipakai notifikasi WhatsApp piket).
 *
 * POST pegawai_id : int
 * POST no_hp      : string — 08xx / +62xx / 62xx, atau kosong untuk menghapus.
 *                   Disimpan apa adanya; normalisasi ke 628xx hanya saat kirim
 *                   (konsisten dengan perlakuan antrian.telepon).
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

$pegawaiId = (int)($_POST['pegawai_id'] ?? 0);
$noHp      = trim($_POST['no_hp'] ?? '');

if ($pegawaiId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Pegawai tidak valid.']);
    exit;
}
if ($noHp !== '' && !preg_match('/^(\+62|0)\d{6,13}$/', $noHp) && !preg_match('/^62\d{8,13}$/', $noHp)) {
    echo json_encode(['success' => false, 'message' => 'Format nomor tidak valid. Gunakan 08xx, +62xx, atau 62xx.']);
    exit;
}

$val = $noHp === '' ? null : $noHp;
$st  = $mysqli->prepare("UPDATE pegawai SET no_hp = ? WHERE id = ?");
$st->bind_param("si", $val, $pegawaiId);
$st->execute();
$st->close();

echo json_encode(['success' => true, 'message' => $noHp === '' ? 'Nomor HP dikosongkan.' : 'Nomor HP disimpan.']);
