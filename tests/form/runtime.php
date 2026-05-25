<?php
/**
 * Runtime smoke tests — form endpoints reachable + render kosong tanpa POST.
 * Skip jika server tidak bisa dijangkau.
 */
T::header('Runtime — Form Endpoints');

$base = 'http://localhost/satset';

$ch = curl_init($base . '/');
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_NOBODY         => true,
    CURLOPT_TIMEOUT        => 3,
    CURLOPT_SSL_VERIFYPEER => false,
]);
curl_exec($ch);
$err = curl_error($ch);
curl_close($ch);

if ($err) {
    T::skip('Runtime form tests (Server tidak bisa dijangkau: ' . $base . ')');
    return;
}

function formGet(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => (int)$code, 'body' => (string)$body];
}

function formPost(string $url, array $fields): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => http_build_query($fields),
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_FOLLOWLOCATION => false,
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => (int)$code, 'body' => (string)$body];
}

// ── 1. Form langsung (disabilitas / umum) ─────────────────────────────────
$r = formGet($base . '/form/disabilitas.php');
T::ok('GET form/disabilitas.php → 200', $r['code'] === 200);
T::ok('disabilitas.php render judul "Form Antrean Disabilitas"',
    str_contains($r['body'], 'Form Antrean Disabilitas'));
T::ok('disabilitas.php punya form field "nama"',
    str_contains($r['body'], 'name="nama"'));
T::ok('disabilitas.php punya field "keperluan_pst" (PST toggle)',
    str_contains($r['body'], 'name="keperluan_pst"'));

$r = formGet($base . '/form/umum.php');
T::ok('GET form/umum.php → 200', $r['code'] === 200);
T::ok('umum.php render judul "Form Antrean Umum"',
    str_contains($r['body'], 'Form Antrean Umum'));

// ── 2. Form WhatsApp ──────────────────────────────────────────────────────
$r = formGet($base . '/form/whatsapp.php');
T::ok('GET form/whatsapp.php → 200', $r['code'] === 200);
T::ok('whatsapp.php punya field "pengaduan_text" (jenis_pelayanan baru)',
    str_contains($r['body'], 'name="pengaduan_text"'));
T::ok('whatsapp.php punya opsi "Pengaduan" di jenis_pelayanan',
    str_contains($r['body'], 'value="Pengaduan"'));

// Token revisi tidak ada → form revisi memunculkan pesan kedaluwarsa
$r = formGet($base . '/form/whatsapp.php?token=' . str_repeat('x', 32) . '&revisi=1');
T::ok('whatsapp.php?revisi=1&token=invalid → 200 + pesan kedaluwarsa',
    $r['code'] === 200 && str_contains($r['body'], 'Link revisi tidak valid'));

// ── 3. Form Surat ─────────────────────────────────────────────────────────
$r = formGet($base . '/form/buku_tamu_surat.php');
T::ok('GET form/buku_tamu_surat.php → 200', $r['code'] === 200);

// ── 4. Form Penilaian (token-based) ───────────────────────────────────────
$r = formGet($base . '/penilaian/?token=tidak-ada-token-valid-1234567890');
T::ok('penilaian/ tanpa token valid → 200 + pesan error',
    $r['code'] === 200 && (
        str_contains($r['body'], 'Link Tidak Valid') ||
        str_contains($r['body'], 'kedaluwarsa')
    ));

// ── 5. POST invalid ke whatsapp.php (no nama) ─────────────────────────────
$r = formPost($base . '/form/whatsapp.php', [
    'nama'             => '',
    'email'            => 'x',
    'telepon'          => '0812',
    'jk'               => '',
    'pendidikan'       => '',
    'kelompok_umur'    => '',
    'pekerjaan'        => '',
    'instansi'         => '',
    'pemanfaatan_data' => '',
    'jenis_pelayanan'  => '',
]);
T::ok('POST whatsapp.php data kosong → 200 + error message (tidak crash)',
    $r['code'] === 200);
T::ok('POST whatsapp.php data kosong → ada pesan error',
    str_contains($r['body'], 'Nama hanya boleh berisi') ||
    str_contains($r['body'], 'tidak valid') ||
    str_contains($r['body'], 'wajib diisi'));

// ── 6. POST invalid ke disabilitas.php (nama mengandung angka) ────────────
$r = formPost($base . '/form/disabilitas.php', [
    'nama'           => 'Budi 123',
    'telepon'        => '0812345678',
    'instansi'       => 'Test',
    'jk'             => 'L',
    'keperluan_pst'  => '0',
    'keperluan'      => 'test',
]);
T::ok('POST disabilitas.php nama dgn angka → 200 + pesan error', $r['code'] === 200);
T::ok('POST nama invalid → error message muncul',
    str_contains($r['body'], 'Nama hanya boleh berisi'));
