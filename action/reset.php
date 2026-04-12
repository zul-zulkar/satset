

<?php
// === reset.php ===
include '../db.php';
$mysqli->query("DELETE FROM antrian WHERE tanggal != CURDATE()");
echo "Nomor antrean berhasil direset.";
?>
