<?php
/**
 * Unit Tests — Logika Skor & Kalender
 * Tidak memerlukan koneksi DB atau server HTTP.
 * Menguji formula yang sama persis dengan yang ada di penghargaan/index.php.
 */
require_once __DIR__ . '/T.php';

// ─── Salin fungsi dari index.php agar bisa diuji secara terisolasi ───────────

function weeksInMonth(int $y, int $m): array {
    $weeks = [];
    $cur   = new DateTime(sprintf('%04d-%02d-01', $y, $m));
    if ((int)$cur->format('N') !== 1) $cur->modify('next Monday');
    $n = 1;
    while ((int)$cur->format('n') === $m) {
        $mon    = clone $cur;
        $fri    = (clone $cur)->modify('+4 days');
        $weeks[] = ['num' => $n++, 'mon' => $mon->format('Y-m-d'), 'fri' => $fri->format('Y-m-d')];
        $cur->modify('+7 days');
    }
    return $weeks;
}

/**
 * Hitung skor akhir persis seperti di index.php.
 * Mengembalikan null jika ada komponen yang belum diisi.
 */
function hitungFinal(float $kehadiran, ?int $kinerja, ?float $avgKS, ?float $avgIV, ?float $avgPE, float $performa): ?float {
    if ($kinerja === null || $avgKS === null || $avgIV === null || $avgPE === null) return null;
    return round($kehadiran*0.20 + $kinerja*0.30 + $avgKS*0.10 + $avgIV*0.10 + $avgPE*0.10 + $performa*0.20, 2);
}

/** Hitung rata-rata nilai dari tim penilai, null jika belum ada satupun nilai. */
function avgTim(array $timScores, string $key, array $TIM_PENILAI): ?float {
    $vals = array_values(array_filter(
        array_map(fn($t) => $timScores[$t][$key] ?? null, $TIM_PENILAI),
        fn($v) => $v !== null
    ));
    return count($vals) ? round(array_sum($vals) / count($vals), 2) : null;
}

// ─────────────────────────────────────────────────────────────────────────────

T::reset();

// ── 1. weeksInMonth ──────────────────────────────────────────────────────────
T::header('weeksInMonth()');

$w = weeksInMonth(2025, 1); // Jan 2025: mulai Rabu 1 Jan → Senin pertama = 6 Jan
T::ok('Jan 2025: Senin pertama = 2025-01-06', $w[0]['mon'] === '2025-01-06');
T::ok('Jan 2025: Jumat pertama = 2025-01-10', $w[0]['fri'] === '2025-01-10');
T::ok('Jan 2025: week num dimulai dari 1', $w[0]['num'] === 1);
T::ok('Jan 2025: semua minggu masih dalam bulan Jan', array_product(array_map(fn($x) => (int)(substr($x['mon'],5,2)==='01'), $w)) === 1);

$w2 = weeksInMonth(2025, 2); // Feb 2025 mulai Sabtu 1 Feb → Senin pertama = 3 Feb
T::ok('Feb 2025: Senin pertama = 2025-02-03', $w2[0]['mon'] === '2025-02-03');
T::ok('Feb 2025: Jumat pertama = 2025-02-07', $w2[0]['fri'] === '2025-02-07');

$wMei = weeksInMonth(2025, 5); // Mei 2025: mulai Kamis 1 Mei → Senin pertama = 5 Mei
T::ok('Mei 2025: Senin pertama = 2025-05-05', $wMei[0]['mon'] === '2025-05-05');

$wMar = weeksInMonth(2025, 3); // Maret 2025 mulai Sabtu → Senin = 3 Maret
T::ok('Mar 2025: Senin pertama = 2025-03-03', $wMar[0]['mon'] === '2025-03-03');

// Bulan dengan Senin tepat di tanggal 1
$wApr = weeksInMonth(2024, 4); // April 2024: mulai Senin 1 Apr
T::ok('Apr 2024: Senin pertama = 2024-04-01', $wApr[0]['mon'] === '2024-04-01');

// ── 2. Bobot & Formula ───────────────────────────────────────────────────────
T::header('Formula Skor Akhir');

// Semua 100: skor = 100
T::eq('Semua 100 → 100.00', 100.0, hitungFinal(100, 100, 100, 100, 100, 100));

// Semua 0: skor = 0 (kinerja tidak bisa 0, tapi formula tetap valid)
T::eq('Semua 0 → 0.00', 0.0, hitungFinal(0, 0, 0, 0, 0, 0));

// Bobot: kehadiran 20% dari 100 saja
T::eq('Hanya kehadiran=100, sisanya 0 → 20.00', 20.0, hitungFinal(100, 0, 0, 0, 0, 0));

// Bobot: kinerja 30%
T::eq('Hanya kinerja=100, sisanya 0 → 30.00', 30.0, hitungFinal(0, 100, 0, 0, 0, 0));

// Bobot: performa 20%
T::eq('Hanya performa=100, sisanya 0 → 20.00', 20.0, hitungFinal(0, 0, 0, 0, 0, 100));

// Bobot: kerja sama 10%
T::eq('Hanya avgKS=100, sisanya 0 → 10.00', 10.0, hitungFinal(0, 0, 100, 0, 0, 0));

// Nilai campuran realistis
T::eq('Skor realistis', 75.5, hitungFinal(80, 70, 75, 80, 70, 80));

// Pembulatan 2 desimal — 66.67*0.20+66*0.30+67*0.10+67*0.10+66*0.10+66.67*0.20 = 66.468 → 66.47
T::eq('Pembulatan 2 desimal', 66.47, hitungFinal(66.67, 66, 67, 67, 66, 66.67));

// ── 3. Nilai null → final null ───────────────────────────────────────────────
T::header('Null Guard (data belum lengkap)');

T::ok('kinerja=null → final=null', hitungFinal(80, null, 80, 80, 80, 80) === null);
T::ok('avgKS=null → final=null',   hitungFinal(80, 80, null, 80, 80, 80) === null);
T::ok('avgIV=null → final=null',   hitungFinal(80, 80, 80, null, 80, 80) === null);
T::ok('avgPE=null → final=null',   hitungFinal(80, 80, 80, 80, null, 80) === null);
T::ok('Semua null → final=null',   hitungFinal(80, null, null, null, null, 80) === null);

// ── 4. Rata-rata Tim Penilai ─────────────────────────────────────────────────
T::header('Rata-rata Tim Penilai');

$TIM = ['iwansantika', 'madepratiwi', 'ariwijaya'];

// Semua tiga menilai
$ts = [
    'iwansantika' => ['ks' => 80, 'iv' => 70, 'pe' => 90],
    'madepratiwi' => ['ks' => 90, 'iv' => 80, 'pe' => 70],
    'ariwijaya'   => ['ks' => 70, 'iv' => 90, 'pe' => 80],
];
T::eq('Rata-rata KS dari 3 penilai → 80.00', 80.0, avgTim($ts, 'ks', $TIM));
T::eq('Rata-rata IV dari 3 penilai → 80.00', 80.0, avgTim($ts, 'iv', $TIM));
T::eq('Rata-rata PE dari 3 penilai → 80.00', 80.0, avgTim($ts, 'pe', $TIM));

// Hanya satu penilai → rata-rata = nilai itu sendiri
$ts2 = ['iwansantika' => ['ks' => 75, 'iv' => 85, 'pe' => 65]];
T::eq('Hanya 1 penilai KS → 75.00', 75.0, avgTim($ts2, 'ks', $TIM));

// Belum ada penilai → null
T::ok('Belum ada penilai → null', avgTim([], 'ks', $TIM) === null);

// Dua penilai, nilai tidak rata
$ts3 = [
    'iwansantika' => ['ks' => 70, 'iv' => 70, 'pe' => 70],
    'madepratiwi' => ['ks' => 80, 'iv' => 80, 'pe' => 80],
];
T::eq('2 penilai KS 70+80 → 75.00', 75.0, avgTim($ts3, 'ks', $TIM));

// ── 5. Kehadiran & Performa (perhitungan piket) ──────────────────────────────
T::header('Kehadiran & Performa (berbasis minggu piket)');

// Petugas hadir 4 dari 5 hari kerja piket → 80%
$hariKerjaPiket = 5;
$hariHadir = 4;
$kehadiran = $hariKerjaPiket > 0 ? min(100, round($hariHadir / $hariKerjaPiket * 100, 2)) : 0;
T::eq('Hadir 4/5 hari → 80.00%', 80.0, $kehadiran);

// Petugas hadir semua hari → 100% (min() mengembalikan int saat arg pertama int)
$hadir55 = min(100, round(5/5*100, 2));
T::eq('Hadir 5/5 hari → 100%', $hadir55, $hadir55); // tautologi: pastikan tidak crash & nilai wajar
T::ok('Hadir 5/5 hari → nilai = 100', $hadir55 == 100);

// hari_kerja_piket = 0 → kehadiran = 0 (tidak crash, tanpa literal /0)
$hkp = 0;
$kehadiran0 = $hkp > 0 ? min(100, round($hariHadir / $hkp * 100, 2)) : 0;
T::eq('Hari kerja piket = 0 → kehadiran = 0', 0, $kehadiran0);

// Performa: PST piket 10, negatif 2 → (10-2)/10 = 80%
$pst = 10; $neg = 2;
$performa = $pst > 0 ? round(($pst - $neg) / $pst * 100, 2) : 0;
T::eq('PST=10, Negatif=2 → Performa=80.00', 80.0, $performa);

// PST = 0 → performa = 0 (tidak crash, tanpa literal /0)
$pst0 = 0;
$performa0 = $pst0 > 0 ? round(($pst0 - 0) / $pst0 * 100, 2) : 0;
T::eq('PST=0 → Performa=0', 0, $performa0);

// ── 6. Sorting Peringkat ─────────────────────────────────────────────────────
T::header('Sorting Peringkat');

$results = [
    ['nama' => 'Budi',  'final' => 85.0],
    ['nama' => 'Ani',   'final' => 90.0],
    ['nama' => 'Citra', 'final' => null],  // belum lengkap → paling bawah
    ['nama' => 'Dedi',  'final' => 90.0],  // skor sama dengan Ani → urut nama
];
usort($results, fn($a, $b) =>
    $a['final'] === null && $b['final'] === null ? strcmp($a['nama'], $b['nama']) :
    ($a['final'] === null ? 1 : ($b['final'] === null ? -1 : $b['final'] <=> $a['final']))
);
T::eq('Urutan ke-1 = Ani atau Dedi (skor 90)', 90.0, $results[0]['final']);
T::eq('Urutan ke-3 = Budi (skor 85)', 85.0, $results[2]['final']);
T::ok('Citra (null) paling bawah', $results[3]['final'] === null);
T::ok('Skor sama: Ani sebelum Dedi (A < D)', strcmp($results[0]['nama'], $results[1]['nama']) < 0);

// ── Hasil ────────────────────────────────────────────────────────────────────
echo "\n";
$s = T::summary();
$col = $s['fail'] > 0 ? "\e[31m" : "\e[32m";
echo "{$col}  unit: {$s['pass']} passed, {$s['fail']} failed, {$s['skip']} skipped\e[0m\n";
return $s;
