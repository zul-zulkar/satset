<?php
include '../db.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$id = intval($_POST['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

$nama          = trim($_POST['nama']             ?? '');
$email         = trim($_POST['email']            ?? '');
$telepon       = trim($_POST['telepon']          ?? '');
$instansi      = trim($_POST['instansi']         ?? '');
$jk            = $_POST['jk']                    ?? '';
$tanggal       = $_POST['tanggal']               ?? '';
$jumlah_orang  = isset($_POST['jumlah_orang']) && $_POST['jumlah_orang'] !== ''
                 ? intval($_POST['jumlah_orang']) : null;
$kunjungan_pst = intval($_POST['kunjungan_pst']  ?? 0);
$keperluan     = trim($_POST['keperluan']        ?? '') ?: null;
$pendidikan    = trim($_POST['pendidikan']       ?? '') ?: null;
$kelompok_umur = trim($_POST['kelompok_umur']    ?? '') ?: null;
$pekerjaan     = trim($_POST['pekerjaan']        ?? '') ?: null;
$pemanfaatan   = trim($_POST['pemanfaatan_data'] ?? '') ?: null;
$data_dibutuhkan = trim($_POST['data_dibutuhkan'] ?? '') ?: null;

if (empty($nama)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nama wajib diisi']);
    exit;
}
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Format tanggal tidak valid']);
    exit;
}

$stmt = $mysqli->prepare(
    "UPDATE antrian SET
        nama=?, email=?, telepon=?, instansi=?, jk=?, tanggal=?,
        jumlah_orang=?, keperluan=?, kunjungan_pst=?,
        pendidikan=?, kelompok_umur=?, pekerjaan=?,
        pemanfaatan_data=?, data_dibutuhkan=?
     WHERE id=?"
);
// s×6, i, s, i, s×5, i  → 15 params
$stmt->bind_param(
    "ssssssisisssssi",
    $nama, $email, $telepon, $instansi, $jk, $tanggal,
    $jumlah_orang, $keperluan, $kunjungan_pst,
    $pendidikan, $kelompok_umur, $pekerjaan,
    $pemanfaatan, $data_dibutuhkan,
    $id
);

if ($stmt->execute()) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan: ' . $stmt->error]);
}
$stmt->close();
