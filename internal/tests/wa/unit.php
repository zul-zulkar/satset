<?php
/**
 * Unit tests — fungsi murni app/wa.php (tanpa DB, tanpa HTTP).
 */
require_once __DIR__ . '/../../../app/wa.php';

// ── wa_normalize_phone ─────────────────────────────────────────────────────
T::header('Unit — wa_normalize_phone');

T::eq("08xx → 628xx",            '6281234567890', wa_normalize_phone('081234567890'));
T::eq("+62 → 62",                '6281234567890', wa_normalize_phone('+6281234567890'));
T::eq("62xx tetap",              '6281234567890', wa_normalize_phone('6281234567890'));
T::eq("8xx → 628xx",             '628123456789',  wa_normalize_phone('8123456789'));
T::eq("telepon kantor 0362",     '62362123456',   wa_normalize_phone('0362123456'));
T::eq("spasi & strip dibuang",   '6281234567890', wa_normalize_phone('+62 812-3456-7890'));
T::eq("terlalu pendek → null",   null,            wa_normalize_phone('081'));
T::eq("kosong → null",           null,            wa_normalize_phone(''));
T::eq("null → null",             null,            wa_normalize_phone(null));
T::eq("huruf → null",            null,            wa_normalize_phone('abc'));

// ── wa_detect_intent ───────────────────────────────────────────────────────
T::header('Unit — wa_detect_intent');

T::eq("menu '1'",   'Permintaan Data',       wa_detect_intent('1'));
T::eq("menu '2.'",  'Konsultasi Statistik',  wa_detect_intent('2.'));
T::eq("menu '3)'",  'Rekomendasi Statistik', wa_detect_intent('3)'));
T::eq("menu '4'",   'Pengaduan',             wa_detect_intent('4'));
T::eq("kalimat minta data",      'Permintaan Data',       wa_detect_intent('Saya mau minta data penduduk Buleleng'));
T::eq("konsultasi > data",       'Konsultasi Statistik',  wa_detect_intent('mau konsultasi data sensus'));
T::eq("rekomendasi kegiatan",    'Rekomendasi Statistik', wa_detect_intent('rekomendasi kegiatan statistik'));
T::eq("romantik",                'Rekomendasi Statistik', wa_detect_intent('cara pengajuan Romantik gimana ya'));
T::eq("keluhan → Pengaduan",     'Pengaduan',             wa_detect_intent('saya punya keluhan pelayanan'));
T::eq("aduan > data",            'Pengaduan',             wa_detect_intent('mau lapor pengaduan soal data'));
T::eq("publikasi",               'Permintaan Data',       wa_detect_intent('apakah publikasi DDA sudah terbit?'));
T::eq("sapaan → null (menu)",    null,                    wa_detect_intent('halo selamat pagi'));
T::eq("kosong → null",           null,                    wa_detect_intent(''));
T::eq("angka di luar 1-4",       null,                    wa_detect_intent('5'));

// ── wa_monday_of ───────────────────────────────────────────────────────────
T::header('Unit — wa_monday_of');

$hasil = wa_monday_of('2026-07-17');
T::ok("hasil adalah hari Senin",           date('N', strtotime($hasil)) === '1');
T::ok("Senin ≤ tanggal input",             $hasil <= '2026-07-17');
T::ok("selisih < 7 hari",                  (strtotime('2026-07-17') - strtotime($hasil)) < 7 * 86400);
$seninTetap = wa_monday_of($hasil);
T::eq("Senin memetakan ke dirinya sendiri", $hasil, $seninTetap);

// ── Template pesan ─────────────────────────────────────────────────────────
T::header('Unit — Template pesan');

$menu = wa_tpl_menu('Budi');
T::ok("menu menyapa nama",                str_contains($menu, 'Bapak/Ibu Budi'));
T::ok("menu memuat tautan buku tamu",     str_contains($menu, APP_URL . '/whatsapp/'));
T::ok("menu memuat opsi 1-4",             str_contains($menu, '1. Permintaan Data') && str_contains($menu, '4. Pengaduan'));
T::ok("menu tanpa nama tetap rapi",       str_starts_with(wa_tpl_menu(''), 'Halo,'));

$intent = wa_tpl_intent('Ani', 'Konsultasi Statistik');
T::ok("intent memuat label layanan",      str_contains($intent, '*Konsultasi Statistik*'));
T::ok("intent memuat tautan buku tamu",   str_contains($intent, APP_URL . '/whatsapp/'));

$rowContoh = [
    'nama' => 'Budi Test', 'instansi' => 'Universitas Test',
    'jenis_pelayanan' => 'Permintaan Data', 'telepon' => '081234567890',
    'token' => 'abc123token',
    'data_dibutuhkan' => json_encode([['data' => 'PDRB', 'tahun_dari' => 2020, 'tahun_sampai' => 2024]]),
];
$notif = wa_tpl_notifikasi($rowContoh, true, null);
T::ok("notifikasi memuat nama & instansi", str_contains($notif, 'Budi Test') && str_contains($notif, 'Universitas Test'));
T::ok("notifikasi memuat item data+tahun", str_contains($notif, 'PDRB (2020-2024)'));
T::ok("notifikasi memuat nomor 628xx",     str_contains($notif, '6281234567890'));
T::ok("notifikasi 'terdaftar: Ya'",        str_contains($notif, 'terdaftar: Ya'));
T::ok("notifikasi memuat link detail",     str_contains($notif, '/cs/detail_pengunjung.php?token=abc123token'));

$rowAduan = $rowContoh;
$rowAduan['jenis_pelayanan'] = 'Pengaduan';
$rowAduan['data_dibutuhkan'] = json_encode([['data' => 'Petugas kurang ramah', 'tahun_dari' => 0, 'tahun_sampai' => 0]]);
$notifAduan = wa_tpl_notifikasi($rowAduan, null, '2026-07-17 09:00:00');
T::ok("pengaduan tampil sebagai teks",     str_contains($notifAduan, 'Petugas kurang ramah') && !str_contains($notifAduan, '(0-0)'));
T::ok("validate null → 'Tidak diketahui'", str_contains($notifAduan, 'terdaftar: Tidak diketahui'));
T::ok("jejak chat bot tampil",             str_contains($notifAduan, 'Chat bot 24 jam terakhir: Ya, terakhir'));
