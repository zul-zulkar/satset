<?php
include __DIR__ . '/buku_tamu_penilaian.php';
renderFormPenilaian(trim($_GET['token'] ?? ''));
?>
