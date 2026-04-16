<?php
/**
 * Returns visitor totals for today (all types).
 * Response: JSON { disabilitas: N, umum: N, whatsapp: N }
 */
ob_start();
include '../db.php';
ob_clean();

header('Content-Type: application/json');

$tanggal = date('Y-m-d');
$stmt = $mysqli->prepare(
    "SELECT jenis, COUNT(*) AS total
     FROM antrian
     WHERE tanggal = ?
       AND jenis IN ('disabilitas','umum','whatsapp')
     GROUP BY jenis"
);
$stmt->bind_param("s", $tanggal);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$out = ['disabilitas' => 0, 'umum' => 0, 'whatsapp' => 0];
foreach ($rows as $r) {
    $out[$r['jenis']] = (int)$r['total'];
}
echo json_encode($out);
