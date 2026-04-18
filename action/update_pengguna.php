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
$link_surat         = trim($_POST['link_surat']         ?? '') ?: null;
$link_surat_balasan = trim($_POST['link_surat_balasan'] ?? '') ?: null;

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
        pemanfaatan_data=?, data_dibutuhkan=?, link_surat=?
     WHERE id=?"
);
// s×6, i, s, i, s×6, i  → 16 params
$stmt->bind_param(
    "ssssssisissssssi",
    $nama, $email, $telepon, $instansi, $jk, $tanggal,
    $jumlah_orang, $keperluan, $kunjungan_pst,
    $pendidikan, $kelompok_umur, $pekerjaan,
    $pemanfaatan, $data_dibutuhkan, $link_surat,
    $id
);

if (!$stmt->execute()) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Gagal menyimpan: ' . $stmt->error]);
    $stmt->close();
    exit;
}
$stmt->close();

// Simpan link_surat_balasan ke tabel pes — INSERT jika belum ada, UPDATE jika sudah ada
$chk = $mysqli->prepare("SELECT id FROM pes WHERE antrian_id=? LIMIT 1");
$chk->bind_param("i", $id);
$chk->execute();
$chk->store_result();
if ($chk->num_rows > 0) {
    $chk->close();
    $stmt2 = $mysqli->prepare("UPDATE pes SET link_surat_balasan=? WHERE antrian_id=?");
    $stmt2->bind_param("si", $link_surat_balasan, $id);
} else {
    $chk->close();
    $stmt2 = $mysqli->prepare("INSERT INTO pes (antrian_id, link_surat_balasan) VALUES (?,?)");
    $stmt2->bind_param("is", $id, $link_surat_balasan);
}
$stmt2->execute();
$stmt2->close();

echo json_encode(['success' => true]);
