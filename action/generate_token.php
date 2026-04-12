<?php
/**
 * Generates a unique survey token for an antrian record (if not already set).
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

// Return existing token if present
$stmt = $mysqli->prepare("SELECT token FROM antrian WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $id);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Data tidak ditemukan']);
    exit;
}

if (!empty($row['token'])) {
    echo json_encode(['success' => true, 'token' => $row['token']]);
    exit;
}

// Generate and save new token
$token  = bin2hex(random_bytes(16));
$stmtUp = $mysqli->prepare("UPDATE antrian SET token = ? WHERE id = ? AND token IS NULL");
$stmtUp->bind_param("si", $token, $id);
$stmtUp->execute();

if ($stmtUp->affected_rows > 0) {
    echo json_encode(['success' => true, 'token' => $token]);
} else {
    // Race condition: another request set it first — re-fetch
    $stmtRe = $mysqli->prepare("SELECT token FROM antrian WHERE id = ? LIMIT 1");
    $stmtRe->bind_param("i", $id);
    $stmtRe->execute();
    $row2 = $stmtRe->get_result()->fetch_assoc();
    $stmtRe->close();
    echo json_encode(['success' => true, 'token' => $row2['token']]);
}
