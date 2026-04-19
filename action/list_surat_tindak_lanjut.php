<?php
/**
 * Surat visitors with penilaian + PES status and links.
 * GET: dari (Y-m-d), sampai (Y-m-d)
 * Response: JSON { data: [ { id, nama, tanggal, token, token_pes, sudah_penilaian, sudah_pes } ] }
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
        "SELECT a.id, a.nama, a.tanggal, a.token, a.token_pes,
                EXISTS(SELECT 1 FROM penilaian WHERE antrian_id = a.id) AS sudah_penilaian,
                EXISTS(SELECT 1 FROM pes        WHERE antrian_id = a.id
                         AND (petugas_utama_id IS NOT NULL OR jenis_layanan IS NOT NULL)) AS sudah_pes
         FROM antrian a
         WHERE a.jenis = 'surat'
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
