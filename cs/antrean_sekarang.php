<?php
include '../db.php';

$tanggal = date('Y-m-d');
$jenis = $_GET['jenis'];

$stmt = $mysqli->prepare("SELECT jenis, nomor FROM antrian WHERE tanggal = ? AND status = 'dipanggil' AND jenis = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("ss", $tanggal, $jenis);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

if ($result) {
    echo strtoupper($result['jenis']) . '-' . $result['nomor'];
} else {
    echo "Belum ada antrean dipanggil";
}