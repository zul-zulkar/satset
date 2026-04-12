<?php
/**
 * Catat absensi masuk / keluar petugas piket PST.
 * POST: tipe (masuk|keluar), lat, lng
 * pegawai_id diambil dari session — tidak bisa di-spoof via POST
 * Response: JSON { success, message, waktu, waktu_lengkap }
 */
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);
session_set_cookie_params(['lifetime' => 60 * 60 * 24 * 30, 'samesite' => 'Lax']);
session_start();

if (empty($_SESSION['absensi_auth'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sesi habis. Silakan login ulang.']);
    exit;
}

include '../../db.php';
header('Content-Type: application/json');

// pegawai_id wajib dari session, bukan dari POST
$pegawaiId = (int) ($_SESSION['pegawai_id'] ?? 0);
$tipe      = $_POST['tipe'] ?? '';
$lat       = floatval($_POST['lat'] ?? 0);
$lng       = floatval($_POST['lng'] ?? 0);

if (!$pegawaiId) {
    echo json_encode(['success' => false, 'message' => 'Sesi tidak valid. Silakan login ulang.']);
    exit;
}
if (!in_array($tipe, ['masuk', 'keluar'])) {
    echo json_encode(['success' => false, 'message' => 'Tipe absen tidak valid.']);
    exit;
}

$today = date('Y-m-d');
$now   = date('Y-m-d H:i:s');

if ($tipe === 'masuk') {
    // Cek apakah sudah absen masuk hari ini
    $stmtEx = $mysqli->prepare(
        "SELECT id, jam_masuk FROM absensi_piket WHERE pegawai_id = ? AND tanggal = ? LIMIT 1"
    );
    $stmtEx->bind_param("is", $pegawaiId, $today);
    $stmtEx->execute();
    $existing = $stmtEx->get_result()->fetch_assoc();
    $stmtEx->close();

    if ($existing && $existing['jam_masuk']) {
        echo json_encode([
            'success' => false,
            'message' => 'Sudah absen masuk hari ini pukul ' . date('H:i:s', strtotime($existing['jam_masuk'])) . '.'
        ]);
        exit;
    }

    if ($existing) {
        $stmt = $mysqli->prepare(
            "UPDATE absensi_piket SET jam_masuk = ?, lat_masuk = ?, lng_masuk = ? WHERE id = ?"
        );
        $stmt->bind_param("sddi", $now, $lat, $lng, $existing['id']);
    } else {
        $stmt = $mysqli->prepare(
            "INSERT INTO absensi_piket (pegawai_id, tanggal, jam_masuk, lat_masuk, lng_masuk)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->bind_param("issdd", $pegawaiId, $today, $now, $lat, $lng);
    }
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success'       => true,
        'message'       => 'Absen masuk berhasil.',
        'waktu'         => date('H:i',   strtotime($now)),
        'waktu_lengkap' => date('H:i:s', strtotime($now)),
    ]);

} else {
    // keluar
    $stmtEx = $mysqli->prepare(
        "SELECT id, jam_masuk, jam_keluar FROM absensi_piket WHERE pegawai_id = ? AND tanggal = ? LIMIT 1"
    );
    $stmtEx->bind_param("is", $pegawaiId, $today);
    $stmtEx->execute();
    $existing = $stmtEx->get_result()->fetch_assoc();
    $stmtEx->close();

    if (!$existing || !$existing['jam_masuk']) {
        echo json_encode(['success' => false, 'message' => 'Belum absen masuk hari ini.']);
        exit;
    }
    $stmt = $mysqli->prepare(
        "UPDATE absensi_piket SET jam_keluar = ?, lat_keluar = ?, lng_keluar = ? WHERE id = ?"
    );
    $stmt->bind_param("sddi", $now, $lat, $lng, $existing['id']);
    $stmt->execute();
    $stmt->close();

    echo json_encode([
        'success'       => true,
        'message'       => $existing['jam_keluar'] ? 'Absen keluar diperbarui.' : 'Absen keluar berhasil.',
        'waktu'         => date('H:i',   strtotime($now)),
        'waktu_lengkap' => date('H:i:s', strtotime($now)),
    ]);
}
