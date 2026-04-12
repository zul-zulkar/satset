<?php
/**
 * Returns full penilaian data for a given antrian_id.
 * GET: antrian_id (int)
 * Response: JSON { found, penilaian, data_items }
 */
include '../db.php';
header('Content-Type: application/json');

$antrian_id = intval($_GET['antrian_id'] ?? 0);
if (!$antrian_id) {
    echo json_encode(['found' => false]);
    exit;
}

$stmt = $mysqli->prepare("SELECT * FROM penilaian WHERE antrian_id = ? LIMIT 1");
$stmt->bind_param("i", $antrian_id);
$stmt->execute();
$penilaian = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$penilaian) {
    echo json_encode(['found' => false]);
    exit;
}

$stmtD = $mysqli->prepare("SELECT * FROM penilaian_data_item WHERE penilaian_id = ? ORDER BY id");
$stmtD->bind_param("i", $penilaian['id']);
$stmtD->execute();
$items = $stmtD->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtD->close();

echo json_encode(['found' => true, 'penilaian' => $penilaian, 'data_items' => $items]);
