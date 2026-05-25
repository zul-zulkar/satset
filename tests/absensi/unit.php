<?php
/**
 * Unit Tests — logika absensi (password hashing, jadwal WFH/libur,
 * validasi tanggal bulan, format jam, validasi koordinat & radius).
 * Tidak butuh DB / HTTP.
 */

// ─── Helpers yang menyalin logika di absensi/ ────────────────────────────

/**
 * Tentukan apakah hari ini WFH berdasarkan config jadwal.
 * Sumber: absensi/index.php baris 32–40.
 *   - WFH rutin = day-of-week (0=Min .. 6=Sab) ada di $wfhHari
 *   - WFH custom = tanggal Y-m-d ada di $wfhCustom
 *   - Libur (di $libur) mengalahkan WFH
 */
function isHariWFH(string $tanggal, array $wfhHari, array $wfhCustom, array $libur): bool {
    if (in_array($tanggal, $libur, true)) return false; // libur > WFH
    $dow = (int) date('w', strtotime($tanggal));
    return in_array($dow, $wfhHari, true) || in_array($tanggal, $wfhCustom, true);
}

function isHariLibur(string $tanggal, array $libur): bool {
    return in_array($tanggal, $libur, true);
}

/**
 * Validasi koordinat & radius untuk save_config.php
 * Sumber: absensi/action/save_config.php baris 23–30
 */
function isKoordinatValid(float $lat, float $lng): bool {
    return $lat !== 0.0 && $lng !== 0.0;
}
function isRadiusValid(int $radius): bool {
    return $radius >= 10 && $radius <= 1000;
}

/**
 * Validasi format bulan Y-m untuk rekap.
 * Sumber: absensi/action/rekap_pribadi.php baris 18
 */
function isFormatBulanValid(string $bulan): bool {
    return (bool) preg_match('/^\d{4}-\d{2}$/', $bulan);
}

/**
 * Validasi format tanggal Y-m-d untuk save_jadwal.
 * Sumber: absensi/action/save_jadwal.php baris 36
 */
function isFormatTanggalValid(string $tanggal): bool {
    return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $tanggal);
}

// ─────────────────────────────────────────────────────────────────────────

T::header('Password Hashing (bcrypt round-trip)');

$plain = 'Rahasia2026!';
$hash  = password_hash($plain, PASSWORD_BCRYPT);

T::ok('Hash bukan plaintext',                $hash !== $plain);
T::ok('Hash bcrypt prefix $2y$',             str_starts_with($hash, '$2y$'));
T::ok('password_verify cocok',               password_verify($plain, $hash));
T::ok('password_verify gagal utk pass salah', !password_verify('SalahPassword', $hash));

// Hash sama untuk 2 invocations tetap berbeda (random salt)
$hash2 = password_hash($plain, PASSWORD_BCRYPT);
T::ok('Dua hash untuk password sama tetap berbeda (salt random)', $hash !== $hash2);
T::ok('Kedua hash sama-sama verify benar',   password_verify($plain, $hash) && password_verify($plain, $hash2));

// Hash admin default di absensi/admin.php
$adminHash = '$2y$10$lKUwHqZcuYMOKKxNKyQ3.ejJ1Tzl5.NL.2mrbdAIB/Du4nNgoMWGy';
T::ok('Hash admin default verify dgn "admin2024"',
    password_verify('admin2024', $adminHash));
T::ok('Hash admin default tidak verify dgn password lain',
    !password_verify('admin2025', $adminHash));

T::header('Jadwal WFH & Libur (logic dari absensi/index.php)');

// Jumat = 5 (default rutin)
$wfhHari   = [5];
$wfhCustom = ['2026-06-10']; // misal hari Rabu di-set WFH custom
$libur     = ['2026-05-25']; // hari Senin sebagai libur

// Senin 2026-05-25 → libur (mengalahkan WFH check)
T::ok('Tanggal libur → isHariLibur true',  isHariLibur('2026-05-25', $libur));
T::ok('Tanggal libur → isHariWFH false (libur > WFH)',
    !isHariWFH('2026-05-25', $wfhHari, $wfhCustom, $libur));

// Jumat 2026-05-29 → WFH rutin
T::ok('Jumat → isHariWFH true (DoW=5 ada di wfhHari)',
    isHariWFH('2026-05-29', $wfhHari, $wfhCustom, $libur));

// Rabu 2026-06-10 → WFH custom
T::ok('Tanggal custom → isHariWFH true',
    isHariWFH('2026-06-10', $wfhHari, $wfhCustom, $libur));

// Selasa biasa 2026-05-26 → bukan WFH, bukan libur
T::ok('Hari kerja biasa → bukan WFH',
    !isHariWFH('2026-05-26', $wfhHari, $wfhCustom, $libur));
T::ok('Hari kerja biasa → bukan libur',
    !isHariLibur('2026-05-26', $libur));

// Config kosong → tidak ada WFH/libur
T::ok('Config kosong → bukan WFH',  !isHariWFH('2026-05-26', [], [], []));
T::ok('Config kosong → bukan libur', !isHariLibur('2026-05-26', []));

T::header('Validasi Koordinat & Radius (save_config.php)');

T::ok('Koordinat valid (-8.1134, 115.0940)', isKoordinatValid(-8.1134, 115.0940));
T::ok('Koordinat lat=0 tidak valid',         !isKoordinatValid(0.0, 115.0940));
T::ok('Koordinat lng=0 tidak valid',         !isKoordinatValid(-8.1134, 0.0));
T::ok('Koordinat dua-duanya 0 tidak valid',  !isKoordinatValid(0.0, 0.0));

T::ok('Radius 10 valid (batas bawah)',       isRadiusValid(10));
T::ok('Radius 100 valid',                    isRadiusValid(100));
T::ok('Radius 1000 valid (batas atas)',      isRadiusValid(1000));
T::ok('Radius 9 → tidak valid',              !isRadiusValid(9));
T::ok('Radius 1001 → tidak valid',           !isRadiusValid(1001));
T::ok('Radius 0 → tidak valid',              !isRadiusValid(0));
T::ok('Radius negatif → tidak valid',        !isRadiusValid(-5));

T::header('Format Tanggal & Bulan untuk Rekap');

// Y-m
T::ok('"2026-05" valid',         isFormatBulanValid('2026-05'));
T::ok('"2026-12" valid',         isFormatBulanValid('2026-12'));
T::ok('"2026-5" tidak valid (perlu 2 digit)', !isFormatBulanValid('2026-5'));
T::ok('"26-05" tidak valid (perlu 4 digit)',  !isFormatBulanValid('26-05'));
T::ok('"2026/05" tidak valid (separator salah)', !isFormatBulanValid('2026/05'));
T::ok('SQLi payload tidak lolos format check',
    !isFormatBulanValid("2026-05' OR 1=1--"));
T::ok('"" tidak valid', !isFormatBulanValid(''));

// Y-m-d
T::ok('"2026-05-25" valid',      isFormatTanggalValid('2026-05-25'));
T::ok('"2026-5-25" tidak valid', !isFormatTanggalValid('2026-5-25'));
T::ok('SQLi payload tidak lolos format tanggal',
    !isFormatTanggalValid("2026-05-25'; DROP TABLE--"));

T::header('Format Jam (H:i:s dari datetime string)');

// rekap_pribadi.php pakai date('H:i:s', strtotime($jam_masuk))
T::eq('"2026-05-25 08:30:15" → "08:30:15"', '08:30:15', date('H:i:s', strtotime('2026-05-25 08:30:15')));
T::eq('"2026-05-25 17:00:00" → "17:00:00"', '17:00:00', date('H:i:s', strtotime('2026-05-25 17:00:00')));

// rekap_admin.php pakai date('H:i') (tanpa detik)
T::eq('"2026-05-25 08:30:15" → "08:30" (H:i)', '08:30', date('H:i', strtotime('2026-05-25 08:30:15')));

T::header('JSON Jadwal — bentuk file config');

$contoh = [
    'wfh_hari'    => [5],
    'wfh_tanggal' => [
        ['tanggal' => '2026-06-10', 'keterangan' => 'WFH khusus'],
    ],
    'libur' => [
        ['tanggal' => '2026-05-25', 'keterangan' => 'Hari Waisak'],
    ],
];
$json = json_encode($contoh, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
$back = json_decode($json, true);

T::ok('Roundtrip JSON jadwal aman',
    is_array($back) && $back['wfh_hari'] === [5]
    && $back['libur'][0]['tanggal'] === '2026-05-25');
T::ok('wfh_hari default = [5] (Jumat)', $back['wfh_hari'][0] === 5);

// Sort wfh_tanggal by tanggal seperti di save_jadwal.php baris 60
$jadwal = [
    ['tanggal' => '2026-08-15', 'keterangan' => 'C'],
    ['tanggal' => '2026-06-10', 'keterangan' => 'A'],
    ['tanggal' => '2026-07-20', 'keterangan' => 'B'],
];
usort($jadwal, fn($a, $b) => $a['tanggal'] <=> $b['tanggal']);
T::eq('Sort by tanggal: yang pertama "2026-06-10"', '2026-06-10', $jadwal[0]['tanggal']);
T::eq('Sort by tanggal: terakhir "2026-08-15"',     '2026-08-15', $jadwal[2]['tanggal']);

T::header('Whitelist action di save_jadwal.php');

$validActions = ['add_wfh', 'remove_wfh', 'add_libur', 'remove_libur'];
T::ok('add_wfh valid',     in_array('add_wfh', $validActions, true));
T::ok('add_libur valid',   in_array('add_libur', $validActions, true));
T::ok('drop_table tidak diijinkan', !in_array('drop_table', $validActions, true));
T::ok('UPDATE arbitrary tidak diijinkan', !in_array('update_x', $validActions, true));
T::eq('Total ada 4 aksi yang valid', 4, count($validActions));

T::header('Validasi password — minimum 6 karakter (ganti_password.php)');

// Sumber: ganti_password.php baris 25
$validPasswords = ['rahasia', 'Abc123', 'longerpassword123'];
foreach ($validPasswords as $p) {
    T::ok("Password \"{$p}\" (>=6 char) → valid", mb_strlen($p) >= 6);
}
T::ok('Password "12345" (5 char) → tidak valid', mb_strlen('12345') < 6);
T::ok('Password kosong → tidak valid',             mb_strlen('') < 6);
T::ok('Password unicode "ñ¿abc" (5 multibyte char) → tidak valid', mb_strlen('ñ¿abc') < 6);
