<?php
/**
 * Returns PST visitors within a date range who may need a survey code.
 * GET: dari  (Y-m-d, default 3 months ago)
 *      sampai (Y-m-d, default today)
 * Response: JSON { data: [ { id, nama, jenis, nomor, tanggal, token, sudah_penilaian } ] }
 */
include '../db.php';

header('Content-Type: application/json');

$dari   = $_GET['dari']   ?? date('Y-m-d', strtotime('-3 months'));
$sampai = $_GET['sampai'] ?? date('Y-m-d');

// Basic validation
$re = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($re, $dari))   $dari   = date('Y-m-d', strtotime('-3 months'));
if (!preg_match($re, $sampai)) $sampai = date('Y-m-d');
// Ensure dari <= sampai
if ($dari > $sampai) [$dari, $sampai] = [$sampai, $dari];

$stmt = $mysqli->prepare(
    "SELECT a.id, a.nama, a.jenis, a.nomor, a.tanggal, a.token,
            EXISTS(SELECT 1 FROM penilaian WHERE antrian_id = a.id) AS sudah_penilaian
     FROM antrian a
     WHERE a.tanggal BETWEEN ? AND ?
       AND (a.jenis = 'whatsapp' OR (a.jenis IN ('umum','disabilitas') AND a.kunjungan_pst = 1))
     ORDER BY a.tanggal DESC, a.id DESC"
);
$stmt->bind_param("ss", $dari, $sampai);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

echo json_encode(['data' => $rows]);
