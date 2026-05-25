<?php
/**
 * Shared HTML head — boilerplate untuk halaman baru.
 *
 * Cara pakai (set variabel SEBELUM include):
 *   $page_title = 'Judul Halaman';                 // wajib
 *   $head_extras = ['fontawesome', 'qrcodejs'];    // opsional, CDN tambahan
 *   include __DIR__ . '/../partials/_head.php';
 *   // setelah include, tag <html> dan <head> sudah terbuka.
 *   // tambahkan <style> page-specific atau <script> di sini.
 *   // tutup </head> dan buka <body> sendiri.
 *
 * Available $head_extras: 'fontawesome' | 'qrcodejs' | 'jquery' | 'datatables'
 * 'datatables' otomatis menambahkan jQuery + DataTables CSS + JS.
 */

$page_title  = $page_title  ?? 'Sistem Antrean BPS Buleleng';
$head_extras = $head_extras ?? [];

$cdn = [
    'fontawesome' => '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">',
    'qrcodejs'    => '<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>',
    'jquery'      => '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>',
    'datatables'  => '<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">' . "\n"
                   . '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>' . "\n"
                   . '<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>',
];
?><!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($page_title) ?></title>
<script src="https://cdn.tailwindcss.com"></script>
<?php foreach ($head_extras as $ex): ?>
<?= $cdn[$ex] ?? '' ?>
<?php endforeach; ?>
