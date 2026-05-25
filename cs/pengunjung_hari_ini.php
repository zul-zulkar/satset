<?php
header('Content-Type: application/json');
include '../db.php';

$tanggal = date('Y-m-d');
$stmt = $mysqli->prepare(
    "SELECT id, nomor, jenis, nama, telepon, instansi, status
     FROM antrian
     WHERE tanggal = ?
     ORDER BY jenis ASC, nomor ASC"
);
$stmt->bind_param("s", $tanggal);
$stmt->execute();
$result = $stmt->get_result();

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}
$stmt->close();

echo json_encode($rows);
