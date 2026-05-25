<?php
/**
 * Simpan konfigurasi koordinat PST ke config/pst.json
 * POST: lat, lng, radius
 * Hanya bisa diakses oleh admin yang sudah login
 */
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);
session_set_cookie_params(['lifetime' => 60 * 60 * 24 * 30, 'samesite' => 'Lax']);
session_start();

header('Content-Type: application/json');

if (empty($_SESSION['absensi_admin'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesi admin habis.']);
    exit;
}

$lat    = floatval($_POST['lat']    ?? 0);
$lng    = floatval($_POST['lng']    ?? 0);
$radius = intval($_POST['radius']   ?? 100);

if ($lat === 0.0 || $lng === 0.0) {
    echo json_encode(['success' => false, 'message' => 'Koordinat tidak valid.']);
    exit;
}
if ($radius < 10 || $radius > 1000) {
    echo json_encode(['success' => false, 'message' => 'Radius harus antara 10–1000 meter.']);
    exit;
}

$config = [
    'lat'    => $lat,
    'lng'    => $lng,
    'radius' => $radius,
];

$path = __DIR__ . '/../config/pst.json';
$ok   = file_put_contents($path, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

if ($ok === false) {
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan file konfigurasi.']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Konfigurasi berhasil disimpan.']);
