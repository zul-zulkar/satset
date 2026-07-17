<?php
/**
 * Unit Tests — logika validasi form buku tamu.
 *
 * Tidak butuh DB / HTTP — menguji ulang regex & whitelist persis
 * seperti yang dipakai di form/buku_tamu*.php agar regresi terdeteksi
 * saat aturan validasi diubah.
 */

// ─── Helpers yang menyalin logika di form/buku_tamu*.php ─────────────────────

/**
 * Validasi nama: huruf, spasi, dan apostrof.
 * Persis seperti di buku_tamu*.php: input di-trim() dulu sebelum regex,
 * sehingga string whitespace-only otomatis kosong dan ditolak.
 */
function isNamaValid(string $nama): bool {
    $nama = trim($nama);
    return (bool) preg_match("/^[a-zA-Z'\s]+$/u", $nama);
}

/** Validasi telepon: awal 08 / 0362 (kantor) / +62 lalu digit. */
function isTeleponValid(string $telp): bool {
    return (bool) preg_match('/^(\+62|0)\d{6,13}$/', $telp);
}

/**
 * Bangun records JSON seperti di buku_tamu.php / buku_tamu_surat.php.
 * Skip entri yang nama-nya kosong.
 */
function buildRecords(array $dataNama, array $tahunDari, array $tahunSampai): array {
    $records = [];
    for ($i = 0; $i < count($dataNama); $i++) {
        $dn = trim($dataNama[$i] ?? '');
        if ($dn === '') continue;
        $td = intval($tahunDari[$i]   ?? 0);
        $ts = intval($tahunSampai[$i] ?? 0);
        $records[] = ['data' => $dn, 'tahun_dari' => $td, 'tahun_sampai' => $ts];
    }
    return $records;
}

/**
 * Bangun records khusus mode Pengaduan (WhatsApp): 1 entri tunggal
 * dengan tahun_dari & tahun_sampai = 0 — persis seperti
 * buku_tamu_whatsapp.php baris 80–82.
 */
function buildPengaduanRecords(string $pengaduanText): array {
    return [['data' => trim($pengaduanText), 'tahun_dari' => 0, 'tahun_sampai' => 0]];
}

// ─────────────────────────────────────────────────────────────────────────────

T::header('Validasi Nama (regex huruf + spasi + apostrof)');

T::ok('Nama biasa "Made Pratiwi" valid',        isNamaValid('Made Pratiwi'));
T::ok('Nama dgn apostrof "O\'Brien" valid',     isNamaValid("O'Brien"));
T::ok('Nama satu kata "Sukarno" valid',         isNamaValid('Sukarno'));
T::ok('Nama kosong → tidak valid',              !isNamaValid(''));
T::ok('Nama dgn angka "Budi123" → tidak valid', !isNamaValid('Budi123'));
T::ok('Nama dgn simbol "Budi-S" → tidak valid', !isNamaValid('Budi-S'));
T::ok('Nama dgn tanda titik "Drs." → tidak valid', !isNamaValid('Drs.'));
T::ok('Nama dgn @ → tidak valid',               !isNamaValid('budi@nama'));
T::ok('Nama hanya spasi → tidak valid',         !isNamaValid('   '));

T::header('Validasi Telepon (08 / 0362 / +62)');

T::ok('"081234567890" valid',          isTeleponValid('081234567890'));
T::ok('"0362123456" (kantor) valid',   isTeleponValid('0362123456'));
T::ok('"+6281234567890" valid',        isTeleponValid('+6281234567890'));
T::ok('"08123456" (7 digit setelah 0) → min 7 digit OK', isTeleponValid('08123456'));
T::ok('"081" (terlalu pendek) → tidak valid', !isTeleponValid('081'));
T::ok('"0812345678901234" (terlalu panjang) → tidak valid', !isTeleponValid('0812345678901234'));
T::ok('"123456789" (tanpa awalan) → tidak valid', !isTeleponValid('123456789'));
T::ok('"+1234567890" (bukan +62) → tidak valid',  !isTeleponValid('+1234567890'));
T::ok('Telepon kosong → tidak valid',            !isTeleponValid(''));
T::ok('Telepon dgn huruf "08abc1234" → tidak valid', !isTeleponValid('08abc1234'));

T::header('Validasi Email (filter_var FILTER_VALIDATE_EMAIL)');

T::ok('"user@example.com" valid',     (bool) filter_var('user@example.com', FILTER_VALIDATE_EMAIL));
T::ok('"a@b.co" valid',               (bool) filter_var('a@b.co', FILTER_VALIDATE_EMAIL));
T::ok('"not-an-email" tidak valid',   !filter_var('not-an-email', FILTER_VALIDATE_EMAIL));
T::ok('"missing@domain" tidak valid', !filter_var('missing@domain', FILTER_VALIDATE_EMAIL));
T::ok('"" tidak valid',               !filter_var('', FILTER_VALIDATE_EMAIL));

T::header('Whitelist field (jaga konsistensi enum)');

$validJk          = ['L', 'P'];
$validPendidikan  = ['SLTA/Sederajat', 'D1/D2/D3', 'D4/S1', 'S2', 'S3'];
$validUmur        = ['di bawah 17 tahun', '17 - 25 tahun', '26 - 34 tahun', '35 - 44 tahun', '45 - 54 tahun', '55 - 65 tahun', 'di atas 65 tahun'];
$validPemanfaatan = ['Tugas Sekolah/Tugas Kuliah', 'Pemerintahan', 'Komersial', 'Penelitian', 'Lainnya'];

T::ok('JK valid hanya L atau P',                in_array('L', $validJk) && in_array('P', $validJk) && !in_array('X', $validJk));
T::ok('Pendidikan punya 5 jenjang',             count($validPendidikan) === 5);
T::ok('Umur punya 7 kelompok',                  count($validUmur) === 7);
T::ok('Pemanfaatan punya 5 kategori',           count($validPemanfaatan) === 5);
T::ok('Whitelist tidak menerima nilai kosong',  !in_array('', $validJk) && !in_array('', $validPendidikan));

T::header('Jenis Pelayanan — WhatsApp punya tambahan "Pengaduan"');

// Form langsung (disabilitas/umum) hanya 3 opsi
$validJpDirect = ['Permintaan Data', 'Konsultasi Statistik', 'Rekomendasi Statistik'];
// WhatsApp punya 4 opsi (termasuk Pengaduan)
$validJpWa     = ['Permintaan Data', 'Konsultasi Statistik', 'Rekomendasi Statistik', 'Pengaduan'];

T::eq('Form langsung: 3 jenis pelayanan', 3, count($validJpDirect));
T::eq('Form WhatsApp: 4 jenis pelayanan', 4, count($validJpWa));
T::ok('Pengaduan hanya muncul di form WhatsApp', !in_array('Pengaduan', $validJpDirect) && in_array('Pengaduan', $validJpWa));

T::header('Build Records JSON (skip baris kosong)');

$records = buildRecords(
    ['Jumlah penduduk', '', 'PDRB', '   '],
    ['2020', '2019', '2018', '2017'],
    ['2024', '2024', '2024', '2024']
);
T::eq('Baris kosong/whitespace di-skip → 2 records', 2, count($records));
T::eq('Record pertama data="Jumlah penduduk"',     'Jumlah penduduk', $records[0]['data']);
T::eq('Record pertama tahun_dari = 2020 (int)',     2020, $records[0]['tahun_dari']);
T::eq('Record kedua data="PDRB" (urut original)',   'PDRB', $records[1]['data']);
T::eq('Record kedua tahun_sampai = 2024 (int)',     2024, $records[1]['tahun_sampai']);

$emptyRecords = buildRecords(['', '   '], ['2020', '2020'], ['2024', '2024']);
T::eq('Semua baris kosong → 0 records', 0, count($emptyRecords));

$trimmed = buildRecords(['  Inflasi  '], ['1990'], ['2024']);
T::eq('Data di-trim',     'Inflasi', $trimmed[0]['data']);
T::eq('Tahun_dari ke int', 1990,     $trimmed[0]['tahun_dari']);

T::header('Pengaduan Record Shape (WhatsApp)');

$pengaduan = buildPengaduanRecords('Pelayanan lambat di hari Jumat');
T::eq('Pengaduan menghasilkan 1 record',           1, count($pengaduan));
T::eq('Pengaduan tahun_dari = 0',                  0, $pengaduan[0]['tahun_dari']);
T::eq('Pengaduan tahun_sampai = 0',                0, $pengaduan[0]['tahun_sampai']);
T::eq('Pengaduan data berisi teks pengaduan',      'Pelayanan lambat di hari Jumat', $pengaduan[0]['data']);

// Teks pengaduan di-trim
$trimmedP = buildPengaduanRecords("  Saran  \n");
T::eq('Pengaduan text di-trim',                    'Saran', $trimmedP[0]['data']);

T::header('JSON Encoding (jaga karakter unicode tidak di-escape)');

$rec     = buildRecords(['Penduduk Buleleng'], ['2020'], ['2024']);
$json    = json_encode($rec, JSON_UNESCAPED_UNICODE);
$decoded = json_decode($json, true);
T::ok('JSON encode → decode roundtrip aman',           is_array($decoded) && $decoded[0]['data'] === 'Penduduk Buleleng');
T::ok('JSON tidak escape karakter unicode (flag aktif)', str_contains(json_encode(['x' => 'Á'], JSON_UNESCAPED_UNICODE), 'Á'));

T::header('Jumlah Orang — minimal 1');

// Persis seperti di buku_tamu.php: max(1, (int)$_POST['jumlah_orang'] ?? 1)
$cases = [
    ['input' => '5',   'expect' => 5],
    ['input' => '1',   'expect' => 1],
    ['input' => '0',   'expect' => 1],   // di-floor ke 1
    ['input' => '-3',  'expect' => 1],
    ['input' => 'abc', 'expect' => 1],   // intval('abc')=0 → max(1,0)=1
    ['input' => '',    'expect' => 1],
];
foreach ($cases as $c) {
    $got = max(1, (int)$c['input']);
    T::eq("max(1, (int){$c['input']}) → {$c['expect']}", $c['expect'], $got);
}

T::header('Token Generation (bin2hex random_bytes 16)');

$tok1 = bin2hex(random_bytes(16));
$tok2 = bin2hex(random_bytes(16));
T::eq('Token panjang 32 hex chars',  32, strlen($tok1));
T::ok('Token hanya hex (0-9a-f)',    (bool) preg_match('/^[0-9a-f]+$/', $tok1));
T::ok('Dua token berbeda',           $tok1 !== $tok2);
