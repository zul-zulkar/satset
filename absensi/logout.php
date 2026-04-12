<?php
ini_set('session.gc_maxlifetime', 60 * 60 * 24 * 30);
session_set_cookie_params(['lifetime' => 60 * 60 * 24 * 30, 'samesite' => 'Lax']);
session_start();
session_destroy();

include_once __DIR__ . '/../config.php';

// Hapus cookie session dari browser
setcookie(session_name(), '', time() - 3600, '/');

header('Location: ' . APP_URL . '/absensi/login');
exit;
