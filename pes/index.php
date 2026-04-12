<?php
// Ekstrak token dari URL: /pes/{token}
$uri   = strtok($_SERVER['REQUEST_URI'], '?');
$parts = explode('/', trim($uri, '/'));
// $parts[0] = 'pes', $parts[1] = token (jika ada)
$token = isset($parts[1]) ? trim($parts[1]) : '';
if ($token !== '') {
    $_GET['token'] = $token;
}
include __DIR__ . '/../form/pes.php';
