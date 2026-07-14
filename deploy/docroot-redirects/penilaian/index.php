<?php
// Redirect kompatibilitas: rute lama (dulu di root domain) → lokasi baru /pst.
// Dipakai karena hosting memakai nginx (.htaccess diabaikan), jadi redirect
// harus dilakukan lewat PHP. Hapus folder ini bila link lama sudah tidak ada.
$qs = isset($_SERVER['QUERY_STRING']) && $_SERVER['QUERY_STRING'] !== ''
     ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: /pst/penilaian/' . $qs, true, 301);
exit;
