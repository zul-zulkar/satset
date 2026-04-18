<?php
include '../db.php';
header('Content-Type: application/json');

$id   = intval($_POST['id'] ?? 0);
$link = trim($_POST['link'] ?? '');

if (!$id) { echo json_encode(['ok' => false, 'msg' => 'ID tidak valid']); exit; }
if ($link !== '' && !filter_var($link, FILTER_VALIDATE_URL)) {
    echo json_encode(['ok' => false, 'msg' => 'Format URL tidak valid']); exit;
}

$linkVal = $link === '' ? null : $link;

$stmt = $mysqli->prepare("UPDATE pes SET link_surat_balasan = ? WHERE antrian_id = ?");
$stmt->bind_param("si", $linkVal, $id);
$stmt->execute();
$ok = ($stmt->errno === 0);
$stmt->close();

echo json_encode(['ok' => $ok]);
