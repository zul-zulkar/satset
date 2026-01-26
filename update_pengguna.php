<?php
include 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $column = $_POST['column'];
    $value = $_POST['value'];

    // Validasi kolom agar tidak bisa input kolom sembarangan
    $allowedColumns = ['nama', 'tanggal', 'jk', 'lahir', 'pendidikan', 'alamat', 'pekerjaan', 'telepon', 'instansi', 'data_yang_diperlukan', 'metode', 'jenis'];
    if (!in_array($column, $allowedColumns)) {
        http_response_code(400);
        echo "Kolom tidak valid";
        exit;
    }

    // Persiapkan statement dinamis
    $stmt = $mysqli->prepare("UPDATE antrian SET $column = ? WHERE id = ?");
    $stmt->bind_param('si', $value, $id);

    if ($stmt->execute()) {
        echo "Berhasil";
    } else {
        http_response_code(500);
        echo "Gagal";
    }

    $stmt->close();
}
?>
