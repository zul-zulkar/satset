<?php
include '../../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']); exit;
}

$tipe       = $_POST['tipe']       ?? '';
$pegawai_id = intval($_POST['pegawai_id'] ?? 0);
$bulan      = intval($_POST['bulan']      ?? 0);
$tahun      = intval($_POST['tahun']      ?? 0);

if (!$pegawai_id || !$bulan || !$tahun) {
    echo json_encode(['success' => false, 'message' => 'Data tidak lengkap']); exit;
}

if ($tipe === 'kinerja') {
    $stmt = $mysqli->prepare("DELETE FROM penghargaan_penilaian WHERE pegawai_id=? AND bulan=? AND tahun=?");
    $stmt->bind_param("iii", $pegawai_id, $bulan, $tahun);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => $stmt->error]); exit;
    }
    $stmt->close();
    echo json_encode(['success' => true]);

} elseif ($tipe === 'tim_penilai') {
    $VALID = ['iwansantika', 'madekariasa', 'paseksusena'];
    $nama_penilai = trim($_POST['nama_penilai'] ?? '');
    if (!in_array($nama_penilai, $VALID)) {
        echo json_encode(['success' => false, 'message' => 'Nama penilai tidak valid']); exit;
    }
    $stmt = $mysqli->prepare("DELETE FROM penghargaan_tim_penilai WHERE pegawai_id=? AND bulan=? AND tahun=? AND nama_penilai=?");
    $stmt->bind_param("iiis", $pegawai_id, $bulan, $tahun, $nama_penilai);
    if (!$stmt->execute()) {
        echo json_encode(['success' => false, 'message' => $stmt->error]); exit;
    }
    $stmt->close();
    echo json_encode(['success' => true]);

} else {
    echo json_encode(['success' => false, 'message' => 'Tipe tidak dikenal']);
}
