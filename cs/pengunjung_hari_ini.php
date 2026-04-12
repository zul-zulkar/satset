<?php
header('Content-Type: application/json');
include '../db.php';

$tanggal = date('Y-m-d');
$result = $mysqli->query(
    "SELECT id, nomor, jenis, nama, telepon, instansi, status
     FROM antrian
     WHERE tanggal = '$tanggal'
     ORDER BY jenis ASC, nomor ASC"
);

$rows = [];
while ($row = $result->fetch_assoc()) {
    $rows[] = $row;
}

echo json_encode($rows);
