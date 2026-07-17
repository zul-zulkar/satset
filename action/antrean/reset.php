

<?php
// === reset.php ===
include __DIR__ . '/../../app/db.php';
$mysqli->query("DELETE FROM antrian WHERE tanggal != CURDATE()");
echo "Nomor antrean berhasil direset.";
?>
