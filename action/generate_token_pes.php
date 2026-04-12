<?php
/**
 * Generates a unique PES token for an antrian record (if not already set).
 * POST: id (int)
 * Response: JSON { success, token }
 */
include '../db.php';

header('Content-Type: application/json');

$id = intval($_POST['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'message' => 'ID tidak valid']);
    exit;
}

// Return existing token_pes if present
$stmt = $mysqli->prepare("SELECT token_pes FROM antrian WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
    exit;
}

if (!empty($row['token_pes'])) {
    echo json_encode(['success' => true, 'token' => $row['token_pes']]);
    exit;
}

// Generate and save new token_pes
$token  = bin2hex(random_bytes(16));
$stmtUp = $mysqli->prepare("UPDATE antrian SET token_pes = ? WHERE id = ? AND token_pes IS NULL");
$stmtUp->bind_param("si", $token, $id);
$stmtUp->execute();

if ($stmtUp->affected_rows > 0) {
    echo json_encode(['success' => true, 'token' => $token]);
} else {
    // Race condition: re-fetch
    $stmtRe = $mysqli->prepare("SELECT token_pes FROM antrian WHERE id = ? LIMIT 1");
    $stmtRe->bind_param("i", $id);
    $stmtRe->execute();
    $row2 = $stmtRe->get_result()->fetch_assoc();
    $stmtRe->close();
    echo json_encode(['success' => true, 'token' => $row2['token_pes']]);
}
