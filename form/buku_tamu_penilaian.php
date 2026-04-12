<?php
/**
 * Form Survei Kepuasan Pelayanan — token-based, terhubung ke antrian
 * Bagian 1 (opsional): penilaian kepuasan per item data_dibutuhkan (WhatsApp)
 * Bagian 2: 16 pertanyaan kualitas pelayanan (skala Likert bintang 1–10)
 * Disimpan ke tabel `penilaian` + `penilaian_data_item`
 */
function renderFormPenilaian($token) {
    include '../db.php';

    $judul = 'Survei Kepuasan Pelayanan Data BPS Kabupaten Buleleng';

    // ── Validasi token ──────────────────────────────────────────────────
    if (empty($token)) {
        renderPenilaianMessage('error', 'Link Tidak Valid', 'Pastikan Anda menggunakan link yang diberikan oleh petugas.');
        return;
    }

    $stmt = $mysqli->prepare("SELECT * FROM antrian WHERE token = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $antrian = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$antrian) {
        renderPenilaianMessage('error', 'Link Tidak Valid', 'Link survei tidak ditemukan atau sudah kedaluwarsa.');
        return;
    }

    // ── Cek duplikasi / jendela revisi ──────────────────────────────────
    $stmtChk = $mysqli->prepare(
        "SELECT *, TIMESTAMPDIFF(SECOND, submitted_at, NOW()) AS seconds_since
         FROM penilaian WHERE antrian_id = ? LIMIT 1"
    );
    $stmtChk->bind_param("i", $antrian['id']);
    $stmtChk->execute();
    $existingPenilaian = $stmtChk->get_result()->fetch_assoc();
    $stmtChk->close();

    $isRevision     = false;
    $reviseDeadline = '';
    if ($existingPenilaian) {
        $secsLeft = 86400 - intval($existingPenilaian['seconds_since']);
        if ($secsLeft <= 0) {
            renderPenilaianMessage('done', 'Sudah Dinilai', 'Penilaian untuk kunjungan ini sudah pernah diisi. Masa revisi 24 jam telah berakhir. Terima kasih atas partisipasi Anda.');
            return;
        }
        $isRevision = true;
        $h = floor($secsLeft / 3600);
        $m = floor(($secsLeft % 3600) / 60);
        $reviseDeadline = $h > 0 ? "{$h} jam {$m} menit" : "{$m} menit";
    }

    // Ambil item penilaian yang ada untuk pre-fill saat revisi
    $existingItems = [];
    if ($existingPenilaian) {
        $stmtEI = $mysqli->prepare("SELECT * FROM penilaian_data_item WHERE penilaian_id = ? ORDER BY id");
        $stmtEI->bind_param("i", $existingPenilaian['id']);
        $stmtEI->execute();
        $existingItems = $stmtEI->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtEI->close();
    }

    // ── Data pengunjung ─────────────────────────────────────────────────
    $nama     = $antrian['nama']     ?? '';
    $instansi = $antrian['instansi'] ?? '';
    $jk       = $antrian['jk']       ?? '';
    $jkLabel  = $jk === 'L' ? 'Laki-laki' : ($jk === 'P' ? 'Perempuan' : '-');

    // ── Item data yang dibutuhkan (WhatsApp) ────────────────────────────
    $dataItems = [];
    if (!empty($antrian['data_dibutuhkan'])) {
        $parsed = json_decode($antrian['data_dibutuhkan'], true);
        if (is_array($parsed)) $dataItems = $parsed;
    }
    $totalD = count($dataItems);

    // ── Pertanyaan standar ──────────────────────────────────────────────
    $questions = [
        1  => 'Informasi pelayanan pada unit layanan ini tersedia melalui media elektronik maupun non elektronik.',
        2  => 'Persyaratan pelayanan yang ditetapkan mudah dipenuhi/disiapkan oleh konsumen.',
        3  => 'Prosedur/alur pelayanan yang ditetapkan mudah diikuti/dilakukan.',
        4  => 'Jangka waktu penyelesaian pelayanan yang diterima sesuai dengan yang ditetapkan.',
        5  => 'Biaya pelayanan yang dibayarkan sesuai dengan biaya yang ditetapkan.',
        6  => 'Produk pelayanan yang diterima sesuai dengan yang dijanjikan.',
        7  => 'Sarana dan prasarana pendukung pelayanan memberikan kenyamanan.',
        8  => 'Data BPS mudah diakses.',
        9  => 'Petugas pelayanan dan/atau aplikasi pelayanan online merespon dengan baik.',
        10 => 'Petugas pelayanan dan/atau aplikasi pelayanan online mampu memberikan informasi yang jelas.',
        11 => 'Fasilitas pengaduan PST mudah diakses.',
        12 => 'Tidak ada diskriminasi dalam pelayanan.',
        13 => 'Tidak ada pelayanan di luar prosedur/kecurangan pelayanan.',
        14 => 'Tidak ada penerimaan gratifikasi.',
        15 => 'Tidak ada pungutan liar (pungli) dalam pelayanan.',
        16 => 'Tidak ada praktik percaloan dalam pelayanan.',
    ];
    $notes = [
        11 => 'Contoh: Kotak saran dan pengaduan, website https://webapps.bps.go.id/pengaduan, e-mail bpshq@bps.go.id',
    ];
    $totalQ = count($questions);

    $tampilkanForm = true;
    $tanggal       = date('Y-m-d');
    $errorMsg      = '';
    $old           = [];

    // Pre-fill form dari data penilaian yang ada (mode revisi, hanya pada GET)
    if ($isRevision && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        for ($i = 1; $i <= 16; $i++) {
            $old["q$i"] = intval($existingPenilaian["q$i"]);
        }
        $old['catatan'] = $existingPenilaian['catatan'] ?? '';
        foreach ($existingItems as $di => $eItem) {
            $old["dval$di"]      = intval($eItem['nilai']);
            $old["dstatus$di"]   = $eItem['status_perolehan'] ?? '';
            $old["dplanning$di"] = $eItem['untuk_perencanaan'] ?? '';
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $old     = $_POST;
        $catatan = trim($_POST['catatan'] ?? '');

        // Validasi pertanyaan standar
        $values = [];
        for ($i = 1; $i <= $totalQ; $i++) {
            $val = isset($_POST["q$i"]) ? intval($_POST["q$i"]) : 0;
            if ($val < 1 || $val > 10) {
                $errorMsg = "Pertanyaan nomor $i belum dijawab.";
                break;
            }
            $values[$i] = $val;
        }

        // Validasi penilaian item data
        $dValues   = [];
        $dStatus   = [];
        $dPlanning = [];
        $validStatus = ['Ya, sesuai', 'Ya, tidak sesuai', 'Tidak diperoleh', 'Belum diperoleh'];
        if ($errorMsg === '' && $totalD > 0) {
            for ($i = 0; $i < $totalD; $i++) {
                $label = $dataItems[$i]['data'] ?? "Data ke-" . ($i + 1);

                $val = isset($_POST["dval$i"]) ? intval($_POST["dval$i"]) : 0;
                if ($val < 1 || $val > 10) {
                    $errorMsg = "Penilaian kepuasan untuk \"" . htmlspecialchars($label) . "\" belum diisi.";
                    break;
                }
                $dValues[$i] = $val;

                $status = $_POST["dstatus$i"] ?? '';
                if (!in_array($status, $validStatus, true)) {
                    $errorMsg = "Status perolehan untuk \"" . htmlspecialchars($label) . "\" belum dipilih.";
                    break;
                }
                $dStatus[$i] = $status;

                $planning = $_POST["dplanning$i"] ?? '';
                if (!in_array($planning, ['ya', 'tidak'], true)) {
                    $errorMsg = "Penggunaan untuk perencanaan pada \"" . htmlspecialchars($label) . "\" belum dipilih.";
                    break;
                }
                $dPlanning[$i] = $planning;
            }
        }

        if ($errorMsg === '') {
            $mysqli->begin_transaction();
            try {
                if ($isRevision) {
                    $stmtSave = $mysqli->prepare(
                        "UPDATE penilaian SET
                            tanggal=?, q1=?,q2=?,q3=?,q4=?,q5=?,q6=?,q7=?,q8=?,
                            q9=?,q10=?,q11=?,q12=?,q13=?,q14=?,q15=?,q16=?,
                            catatan=?, submitted_at=NOW()
                         WHERE id=?"
                    );
                    $stmtSave->bind_param(
                        "siiiiiiiiiiiiiiiisi",
                        $tanggal,
                        $values[1],  $values[2],  $values[3],  $values[4],
                        $values[5],  $values[6],  $values[7],  $values[8],
                        $values[9],  $values[10], $values[11], $values[12],
                        $values[13], $values[14], $values[15], $values[16],
                        $catatan, $existingPenilaian['id']
                    );
                    $stmtSave->execute();
                    $penilaianId = $existingPenilaian['id'];
                    $stmtSave->close();
                    // Hapus item lama sebelum re-insert
                    $stmtDel = $mysqli->prepare("DELETE FROM penilaian_data_item WHERE penilaian_id = ?");
                    $stmtDel->bind_param("i", $penilaianId);
                    $stmtDel->execute();
                    $stmtDel->close();
                } else {
                    $stmtSave = $mysqli->prepare(
                        "INSERT INTO penilaian
                            (antrian_id, tanggal, q1, q2, q3, q4, q5, q6, q7, q8,
                             q9, q10, q11, q12, q13, q14, q15, q16, catatan)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmtSave->bind_param(
                        "isiiiiiiiiiiiiiiiis",
                        $antrian['id'], $tanggal,
                        $values[1],  $values[2],  $values[3],  $values[4],
                        $values[5],  $values[6],  $values[7],  $values[8],
                        $values[9],  $values[10], $values[11], $values[12],
                        $values[13], $values[14], $values[15], $values[16],
                        $catatan
                    );
                    $stmtSave->execute();
                    $penilaianId = $mysqli->insert_id;
                    $stmtSave->close();
                }

                if ($totalD > 0) {
                    $stmtD = $mysqli->prepare(
                        "INSERT INTO penilaian_data_item
                            (penilaian_id, nama_data, tahun_dari, tahun_sampai, nilai,
                             status_perolehan, untuk_perencanaan)
                         VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );
                    for ($i = 0; $i < $totalD; $i++) {
                        $nd = $dataItems[$i]['data']              ?? '';
                        $td = intval($dataItems[$i]['tahun_dari']   ?? 0);
                        $ts = intval($dataItems[$i]['tahun_sampai'] ?? 0);
                        $nv = $dValues[$i];
                        $sp = $dStatus[$i];
                        $up = $dPlanning[$i];
                        $stmtD->bind_param("isiiiss", $penilaianId, $nd, $td, $ts, $nv, $sp, $up);
                        $stmtD->execute();
                    }
                    $stmtD->close();
                }

                $mysqli->commit();
                $tampilkanForm = false;
            } catch (Exception $e) {
                $mysqli->rollback();
                $errorMsg = 'Terjadi kesalahan saat menyimpan data. Silakan coba lagi.';
            }
        }
    }
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title><?= htmlspecialchars($judul) ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            .star {
                font-size: 1.85rem;
                color: #d1d5db;
                cursor: pointer;
                transition: color 0.08s, transform 0.08s;
                line-height: 1;
                display: inline-block;
                user-select: none;
            }
            .star:hover, .star.preview { color: #fde68a; transform: scale(1.15); }
            .star.lit                  { color: #f59e0b; }
            .star-group                { display: flex; gap: 0.1rem; align-items: center; }
            .score-badge {
                font-size: 0.75rem;
                font-weight: 600;
                padding: 0.2rem 0.6rem;
                border-radius: 999px;
                display: none;
                margin-left: 0.5rem;
            }
            .score-badge.show   { display: inline-block; }
            .score-1,.score-2   { background:#fee2e2; color:#dc2626; }
            .score-3,.score-4   { background:#ffedd5; color:#ea580c; }
            .score-5,.score-6   { background:#fef9c3; color:#ca8a04; }
            .score-7,.score-8   { background:#dcfce7; color:#16a34a; }
            .score-9,.score-10  { background:#bbf7d0; color:#15803d; }
            .question-num {
                display: flex; align-items: center; justify-content: center;
                width: 1.75rem; height: 1.75rem; border-radius: 9999px;
                font-size: 0.75rem; font-weight: 700; flex-shrink: 0;
            }
            .question-row { border-bottom: 1px solid #e5e7eb; }
            .question-row.unanswered               { background: #fef2f2 !important; }
            .question-row.unanswered .question-num { background: #fee2e2 !important; color: #dc2626 !important; }
            .question-row.unanswered .star         { animation: shake 0.35s ease; }
            .section-header {
                display: flex; align-items: center; gap: 0.75rem;
                padding: 0.875rem 1.25rem;
                border-bottom: 1px solid rgba(0,0,0,0.08);
            }
            .section-num {
                display: flex; align-items: center; justify-content: center;
                width: 2rem; height: 2rem; border-radius: 9999px;
                font-size: 0.875rem; font-weight: 700; flex-shrink: 0; color: #fff;
            }
            .scale-hint {
                display: flex; justify-content: space-between; align-items: center;
                padding: 0.5rem 1.25rem;
                font-size: 0.7rem; font-weight: 500;
                border-bottom: 1px solid rgba(0,0,0,0.06);
            }
            @keyframes shake {
                0%,100% { transform: translateX(0); }
                25%      { transform: translateX(-3px); }
                75%      { transform: translateX(3px); }
            }
        </style>
    </head>
    <body class="bg-gray-100 min-h-screen py-8 px-3 sm:px-6">
    <div class="w-full max-w-2xl mx-auto">

        <!-- Header -->
        <div class="bg-white rounded-xl shadow p-6 mb-4">
            <h1 class="text-xl sm:text-2xl font-bold text-center text-gray-800 leading-snug">
                <?= htmlspecialchars($judul) ?>
            </h1>
            <p class="text-center text-gray-500 text-sm mt-1">BPS Kabupaten Buleleng</p>
        </div>

        <?php if ($tampilkanForm): ?>

        <!-- Info pengunjung (read-only) -->
        <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
            <div class="section-header bg-gray-700">
                <div class="section-num bg-gray-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                    <h2 class="text-sm font-bold text-white">Identitas Pengunjung</h2>
                    <p class="text-xs text-gray-300 mt-0.5">Data terisi otomatis dari formulir pendaftaran Anda.</p>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-3 divide-y sm:divide-y-0 sm:divide-x divide-gray-100">
                <div class="px-5 py-4">
                    <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold mb-1">Nama</p>
                    <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($nama) ?></p>
                </div>
                <div class="px-5 py-4">
                    <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold mb-1">Jenis Kelamin</p>
                    <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($jkLabel) ?></p>
                </div>
                <div class="px-5 py-4">
                    <p class="text-xs text-gray-400 uppercase tracking-wide font-semibold mb-1">Instansi / Organisasi</p>
                    <p class="font-semibold text-gray-800 text-sm"><?= htmlspecialchars($instansi ?: '-') ?></p>
                </div>
            </div>
        </div>

        <?php if ($isRevision): ?>
        <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 mb-4 flex items-start gap-3 text-sm">
            <span class="text-amber-500 text-base mt-0.5">⏰</span>
            <div>
                <p class="font-semibold text-amber-800">Mode Revisi</p>
                <p class="text-amber-700 text-xs mt-0.5">Anda dapat merevisi penilaian sebelumnya. Masa revisi berakhir dalam <strong><?= $reviseDeadline ?></strong>.</p>
            </div>
        </div>
        <?php endif; ?>
        <?php if (!empty($errorMsg)): ?>
        <div class="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded-lg mb-4 text-sm">
            <?= htmlspecialchars($errorMsg) ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="formPenilaian" novalidate>

        <!-- ── SESI 1: Penilaian Kualitas Pelayanan ──────────────────── -->
        <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
            <div class="section-header bg-blue-700">
                <div class="section-num bg-blue-500">1</div>
                <div>
                    <h2 class="text-sm font-bold text-white">Penilaian Kualitas Pelayanan</h2>
                    <p class="text-xs text-blue-200 mt-0.5">Berikan penilaian terhadap 16 aspek pelayanan berikut (skala 1–10).</p>
                </div>
            </div>
            <div class="scale-hint bg-blue-50 text-blue-600">
                <span>&#9733; Sangat Tidak Puas</span>
                <span class="text-blue-400">Skala Bintang 1 – 10</span>
                <span>&#9733;&#9733;&#9733;&#9733;&#9733;&#9733;&#9733;&#9733;&#9733;&#9733; Sangat Puas</span>
            </div>
            <?php foreach ($questions as $num => $text):
                $oldVal = intval($old["q$num"] ?? 0);
                $qId    = "q$num";
            ?>
            <div class="question-row px-5 py-5 <?= $num % 2 === 0 ? 'bg-gray-50' : 'bg-white' ?> <?= (!empty($errorMsg) && $oldVal < 1) ? 'unanswered' : '' ?>"
                 id="row-<?= $qId ?>">
                <div class="flex gap-3 mb-3">
                    <span class="question-num bg-blue-100 text-blue-700 mt-0.5"><?= $num ?></span>
                    <div class="flex-1">
                        <p class="text-sm font-semibold text-gray-800 leading-relaxed"><?= htmlspecialchars($text) ?></p>
                        <?php if (isset($notes[$num])): ?>
                        <p class="text-xs text-gray-400 mt-1 italic"><?= htmlspecialchars($notes[$num]) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="pl-9 flex items-center flex-wrap gap-y-1">
                    <div class="star-group" id="stars-<?= $qId ?>">
                        <?php for ($v = 1; $v <= 10; $v++): ?>
                        <span class="star <?= $oldVal >= $v ? 'lit' : '' ?>" data-val="<?= $v ?>">&#9733;</span>
                        <?php endfor; ?>
                    </div>
                    <span class="score-badge <?= $oldVal > 0 ? "show score-$oldVal" : '' ?>"
                          id="badge-<?= $qId ?>"><?= $oldVal > 0 ? "$oldVal / 10" : '' ?></span>
                    <input type="hidden" name="<?= $qId ?>" id="inp-<?= $qId ?>"
                           value="<?= $oldVal > 0 ? $oldVal : '' ?>">
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if ($totalD > 0): ?>
        <!-- ── SESI 2: Penilaian Pemenuhan Kebutuhan Data ────────────── -->
        <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
            <div class="section-header bg-emerald-700">
                <div class="section-num bg-emerald-500">2</div>
                <div>
                    <h2 class="text-sm font-bold text-white">Penilaian Pemenuhan Kebutuhan Data</h2>
                    <p class="text-xs text-emerald-200 mt-0.5">Berikan penilaian kepuasan atas setiap data yang Anda butuhkan.</p>
                </div>
            </div>
            <div class="scale-hint bg-emerald-50 text-emerald-700">
                <span>&#9733; Sangat Tidak Puas</span>
                <span class="text-emerald-500">Skala 1 – 10</span>
                <span>&#9733;&#9733;&#9733;&#9733;&#9733;&#9733;&#9733;&#9733;&#9733;&#9733; Sangat Puas</span>
            </div>
            <?php foreach ($dataItems as $di => $item):
                $oldVal = intval($old["dval$di"] ?? 0);
                $itemId = "dval$di";
            ?>
            <div class="question-row px-5 py-5 <?= $di % 2 === 0 ? 'bg-white' : 'bg-gray-50' ?> <?= (!empty($errorMsg) && $oldVal < 1) ? 'unanswered' : '' ?>"
                 id="row-<?= $itemId ?>">
                <!-- Judul data -->
                <div class="flex gap-3 mb-4">
                    <span class="question-num bg-emerald-100 text-emerald-700 mt-0.5"><?= $di + 1 ?></span>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($item['data'] ?? '') ?></p>
                        <p class="text-xs text-gray-400 mt-0.5">Tahun <?= intval($item['tahun_dari'] ?? 0) ?> – <?= intval($item['tahun_sampai'] ?? 0) ?></p>
                    </div>
                </div>
                <!-- Tingkat kepuasan (bintang) -->
                <div class="pl-9 mb-4">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Tingkat Kepuasan</p>
                    <div class="flex items-center flex-wrap gap-y-1">
                        <div class="star-group" id="stars-<?= $itemId ?>">
                            <?php for ($v = 1; $v <= 10; $v++): ?>
                            <span class="star <?= $oldVal >= $v ? 'lit' : '' ?>" data-val="<?= $v ?>">&#9733;</span>
                            <?php endfor; ?>
                        </div>
                        <span class="score-badge <?= $oldVal > 0 ? "show score-$oldVal" : '' ?>"
                              id="badge-<?= $itemId ?>"><?= $oldVal > 0 ? "$oldVal / 10" : '' ?></span>
                        <input type="hidden" name="<?= $itemId ?>" id="inp-<?= $itemId ?>"
                               value="<?= $oldVal > 0 ? $oldVal : '' ?>">
                    </div>
                </div>
                <!-- Status perolehan -->
                <div class="pl-9 mb-3">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Apakah data sudah diperoleh?</p>
                    <div class="flex flex-wrap gap-x-5 gap-y-2">
                        <?php foreach (['Ya, sesuai', 'Ya, tidak sesuai', 'Tidak diperoleh', 'Belum diperoleh'] as $opt):
                            $checked = ($old["dstatus$di"] ?? '') === $opt ? 'checked' : ''; ?>
                        <label class="flex items-center gap-1.5 cursor-pointer text-sm text-gray-700">
                            <input type="radio" name="dstatus<?= $di ?>" value="<?= htmlspecialchars($opt) ?>"
                                   <?= $checked ?> class="accent-emerald-600 w-4 h-4">
                            <?= htmlspecialchars($opt) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Untuk perencanaan -->
                <div class="pl-9">
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-2">Digunakan untuk perencanaan &amp; evaluasi pembangunan nasional/daerah?</p>
                    <div class="flex gap-6">
                        <?php foreach (['ya' => 'Ya', 'tidak' => 'Tidak'] as $pVal => $pLabel):
                            $checked = ($old["dplanning$di"] ?? '') === $pVal ? 'checked' : ''; ?>
                        <label class="flex items-center gap-1.5 cursor-pointer text-sm text-gray-700">
                            <input type="radio" name="dplanning<?= $di ?>" value="<?= $pVal ?>"
                                   <?= $checked ?> class="accent-emerald-600 w-4 h-4">
                            <?= $pLabel ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- ── SESI 3: Catatan & Penilaian Kualitatif ────────────────── -->
        <div class="bg-white rounded-xl shadow overflow-hidden mb-6">
            <div class="section-header bg-purple-700">
                <div class="section-num bg-purple-500"><?= $totalD > 0 ? 3 : 2 ?></div>
                <div>
                    <h2 class="text-sm font-bold text-white">Catatan &amp; Saran</h2>
                    <p class="text-xs text-purple-200 mt-0.5">Penilaian kualitatif mengenai pelayanan secara keseluruhan.</p>
                </div>
            </div>
            <div class="p-5">
                <p class="text-sm text-gray-600 mb-3">Tuliskan kesan, saran, atau masukan Anda mengenai pelayanan yang telah diterima secara keseluruhan.</p>
                <textarea name="catatan" id="catatan" rows="5"
                          class="w-full border border-gray-300 rounded-lg px-4 py-3 text-sm text-gray-800 focus:outline-none focus:ring-2 focus:ring-purple-400 resize-y"
                          placeholder="Contoh: Petugas ramah dan responsif, namun proses pengambilan data memerlukan waktu lebih lama dari yang diharapkan..."><?= htmlspecialchars($old['catatan'] ?? '') ?></textarea>
                <p class="text-xs text-gray-400 mt-1.5">Opsional — boleh dikosongkan.</p>
            </div>
        </div>

        <button type="submit"
                class="w-full bg-green-600 hover:bg-green-700 text-white font-bold py-3.5 rounded-xl shadow-md transition-colors text-base tracking-wide">
            <?= $isRevision ? 'Simpan Revisi' : 'Kirim Penilaian' ?>
        </button>
        <p class="text-center text-xs text-gray-400 mt-3 mb-6">
            Pastikan semua pertanyaan wajib telah diisi sebelum mengirim.
        </p>
        </form>

        <script>
        var TOTAL_Q = <?= $totalQ ?>;
        var TOTAL_D = <?= $totalD ?>;

        var scoreLabels = {
            1:'Sangat Tidak Puas', 2:'Sangat Tidak Puas',
            3:'Tidak Puas',        4:'Tidak Puas',
            5:'Cukup Puas',        6:'Cukup Puas',
            7:'Puas',              8:'Puas',
            9:'Sangat Puas',       10:'Sangat Puas'
        };

        function setupStars(id) {
            var group = document.getElementById('stars-' + id);
            var stars = Array.from(group.querySelectorAll('.star'));
            var input = document.getElementById('inp-' + id);
            var badge = document.getElementById('badge-' + id);

            function applyPreview(upTo) {
                stars.forEach(function(s, i) { s.classList.toggle('preview', i < upTo); });
            }
            function updateBadge(val) {
                if (val < 1) { badge.className = 'score-badge'; badge.textContent = ''; return; }
                badge.textContent = val + ' / 10  ' + scoreLabels[val];
                badge.className   = 'score-badge show score-' + val;
            }

            group.addEventListener('mouseover', function(e) {
                var star = e.target.closest('.star');
                if (!star) return;
                applyPreview(stars.indexOf(star) + 1);
            });
            group.addEventListener('mouseleave', function() {
                var current = parseInt(input.value) || 0;
                stars.forEach(function(s, i) {
                    s.classList.toggle('lit', i < current);
                    s.classList.remove('preview');
                });
            });
            group.addEventListener('click', function(e) {
                var star = e.target.closest('.star');
                if (!star) return;
                var val = stars.indexOf(star) + 1;
                input.value = val;
                stars.forEach(function(s, i) {
                    s.classList.toggle('lit', i < val);
                    s.classList.remove('preview');
                });
                updateBadge(val);
                var row = document.getElementById('row-' + id);
                if (row) row.classList.remove('unanswered');
            });
        }

        for (var q = 1; q <= TOTAL_Q; q++) { setupStars('q' + q); }
        for (var d = 0; d < TOTAL_D; d++) { setupStars('dval' + d); }

        document.getElementById('formPenilaian').addEventListener('submit', function(e) {
            var anyMissing = false;
            var firstMissing = null;

            for (var i = 1; i <= TOTAL_Q; i++) {
                var id  = 'q' + i;
                var val = document.getElementById('inp-' + id).value;
                if (!val || parseInt(val) < 1) {
                    var row = document.getElementById('row-' + id);
                    if (row) { row.classList.add('unanswered'); if (!firstMissing) firstMissing = row; }
                    anyMissing = true;
                }
            }
            for (var d = 0; d < TOTAL_D; d++) {
                var id  = 'dval' + d;
                var val = document.getElementById('inp-' + id).value;
                var statusOk   = !!document.querySelector('input[name="dstatus'   + d + '"]:checked');
                var planningOk = !!document.querySelector('input[name="dplanning' + d + '"]:checked');
                if (!val || parseInt(val) < 1 || !statusOk || !planningOk) {
                    var row = document.getElementById('row-' + id);
                    if (row) { row.classList.add('unanswered'); if (!firstMissing) firstMissing = row; }
                    anyMissing = true;
                }
            }
            if (anyMissing) {
                e.preventDefault();
                if (firstMissing) firstMissing.scrollIntoView({ behavior: 'smooth', block: 'center' });
                alert('Semua pertanyaan wajib harus diisi sebelum mengirim.');
            }
        });
        </script>

        <?php else: ?>

        <!-- Halaman sukses -->
        <div class="bg-white rounded-xl shadow p-10 text-center">
            <div class="text-6xl mb-4">🙏</div>
            <?php if ($isRevision): ?>
            <p class="text-2xl font-bold text-green-700 mb-2">Revisi Tersimpan!</p>
            <p class="text-gray-600 leading-relaxed">
                Revisi penilaian Anda telah berhasil disimpan.<br>
                Terima kasih atas partisipasi Anda.
            </p>
            <?php else: ?>
            <p class="text-2xl font-bold text-green-700 mb-2">Terima Kasih!</p>
            <p class="text-gray-600 leading-relaxed">
                Penilaian Anda telah berhasil kami terima.<br>
                Masukan Anda sangat berarti untuk peningkatan kualitas pelayanan<br>
                BPS Kabupaten Buleleng.
            </p>
            <?php endif; ?>
            <div class="mt-6 pt-5 border-t border-gray-100">
                <p class="text-xs text-gray-400 mb-3">Ingin mengubah jawaban? Anda dapat merevisi dalam 24 jam sejak pengiriman.</p>
                <a href="<?= htmlspecialchars($_SERVER['REQUEST_URI']) ?>"
                   class="inline-flex items-center gap-2 bg-amber-500 hover:bg-amber-600 text-white font-semibold px-5 py-2.5 rounded-lg transition-colors text-sm">
                    ✏️ Revisi Jawaban
                </a>
            </div>
        </div>

        <?php endif; ?>
    </div>
    </body>
    </html>
    <?php
}

function renderPenilaianMessage($type, $title, $body) {
    $icons  = ['error' => '⚠️', 'done' => '✅'];
    $colors = ['error' => 'text-red-700',  'done' => 'text-blue-700'];
    $icon   = $icons[$type]  ?? '❔';
    $color  = $colors[$type] ?? 'text-gray-700';
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title>Survei Kepuasan Pelayanan</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-gray-100 min-h-screen flex items-center justify-center px-4">
    <div class="bg-white rounded-xl shadow p-10 text-center max-w-md w-full">
        <div class="text-5xl mb-4"><?= $icon ?></div>
        <p class="text-xl font-bold <?= $color ?> mb-2"><?= htmlspecialchars($title) ?></p>
        <p class="text-gray-600 text-sm leading-relaxed"><?= htmlspecialchars($body) ?></p>
    </div>
    </body>
    </html>
    <?php
}
?>
