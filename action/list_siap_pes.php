<?php
/**
 * Returns PST visitors only within a date range for PES.
 * PST = jenis 'whatsapp', OR jenis 'umum'/'disabilitas' with kunjungan_pst = 1.
 * GET: dari  (Y-m-d, default 3 months ago)
 *      sampai (Y-m-d, default today)
 * Response: JSON { data: [ { id, nama, jenis, nomor, tanggal, token_pes, sudah_pes } ] }
 */
ob_start();
include '../db.php';
ob_clean();

header('Content-Type: application/json');

$dari   = $_GET['dari']   ?? date('Y-m-d', strtotime('-3 months'));
$sampai = $_GET['sampai'] ?? date('Y-m-d');

$re = '/^\d{4}-\d{2}-\d{2}$/';
if (!preg_match($re, $dari))   $dari   = date('Y-m-d', strtotime('-3 months'));
if (!preg_match($re, $sampai)) $sampai = date('Y-m-d');
if ($dari > $sampai) [$dari, $sampai] = [$sampai, $dari];

try {
    $stmt = $mysqli->prepare(
        "SELECT a.id, a.nama, a.jenis, a.nomor, a.tanggal, a.token_pes,
                EXISTS(SELECT 1 FROM pes WHERE antrian_id = a.id) AS sudah_pes
         FROM antrian a
         WHERE (a.jenis = 'whatsapp' OR (a.jenis IN ('umum','disabilitas') AND a.kunjungan_pst = 1))
           AND a.tanggal BETWEEN ? AND ?
         ORDER BY a.tanggal DESC, a.id DESC"
    );
    $stmt->bind_param("ss", $dari, $sampai);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    echo json_encode(['data' => $rows]);
} catch (Exception $e) {
    echo json_encode(['data' => [], 'error' => $e->getMessage()]);
}
