<?php
include '../db.php';

if (isset($_POST['id'])) {
    $id = $_POST['id'];
    
    $stmt = $mysqli->prepare("DELETE FROM antrian WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    
    if ($stmt->affected_rows > 0) {
        echo "Data berhasil dihapus";
    } else {
        echo "Data gagal dihapus";
    }
}
?>