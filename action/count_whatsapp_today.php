<?php
/**
 * Returns count of WhatsApp visitors registered today.
 * Response: JSON { count: N }
 */
ob_start();
include '../db.php';
ob_clean();

header('Content-Type: application/json');

$tanggal = date('Y-m-d');
$stmt = $mysqli->prepare(
    "SELECT COUNT(*) AS total FROM antrian WHERE tanggal = ? AND jenis = 'whatsapp'"
);
$stmt->bind_param("s", $tanggal);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode(['count' => (int)($row['total'] ?? 0)]);
