<?php
include 'db.php';

// Pastikan ada data ID yang dikirimkan
if (isset($_POST['ids'])) {
    $ids = $_POST['ids']; // Array ID yang dipilih

    // Mempersiapkan query untuk menghapus pengguna yang dipilih
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $mysqli->prepare("DELETE FROM antrian WHERE id IN ($placeholders)");

    // Binding parameter untuk setiap ID yang dipilih
    $types = str_repeat('i', count($ids)); // Tipe data integer untuk setiap ID
    $stmt->bind_param($types, ...$ids);

    if ($stmt->execute()) {
        echo "Data berhasil dihapus";
    } else {
        echo "Gagal menghapus data";
    }

    $stmt->close();
} else {
    echo "Tidak ada data yang dipilih";
}

$mysqli->close();
?>
