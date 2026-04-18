<?php
include '../db.php';
header('Content-Type: application/json');

$id   = intval($_POST['id'] ?? 0);
$link = trim($_POST['link'] ?? '');

if (!$id) { echo json_encode(['ok' => false, 'msg' => 'ID tidak valid']); exit; }
if ($link !== '' && !filter_var($link, FILTER_VALIDATE_URL)) {
    echo json_encode(['ok' => false, 'msg' => 'Format URL tidak valid']); exit;
}

if ($link === '') {
    $stmt = $mysqli->prepare("UPDATE antrian SET link_surat = NULL WHERE id = ? AND jenis = 'surat'");
    $stmt->bind_param("i", $id);
} else {
    $stmt = $mysqli->prepare("UPDATE antrian SET link_surat = ? WHERE id = ? AND jenis = 'surat'");
    $stmt->bind_param("si", $link, $id);
}
$stmt->execute();
$ok = ($stmt->affected_rows >= 0 && $stmt->errno === 0);
$stmt->close();

echo json_encode(['ok' => $ok]);
