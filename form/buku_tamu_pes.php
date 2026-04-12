<?php
/**
 * Form Post Enumeration Survey (PES) — diisi oleh petugas PST setelah kunjungan.
 * Token-based, terhubung ke antrian via antrian.token_pes.
 * Menyimpan ke tabel `pes` + `pes_pembantu` + `pes_kebutuhan_data`.
 */
function renderFormPes($token) {
    include '../db.php';

    $judul = 'Form PES — Post Enumeration Survey';

    // ── Validasi token ──────────────────────────────────────────────────
    if (empty($token)) {
        renderPesMessage('error', 'Link Tidak Valid', 'Pastikan Anda menggunakan link yang diberikan.');
        return;
    }

    $stmt = $mysqli->prepare("SELECT * FROM antrian WHERE token_pes = ? LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $antrian = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$antrian) {
        renderPesMessage('error', 'Link Tidak Valid', 'Link PES tidak ditemukan atau sudah kedaluwarsa.');
        return;
    }

    // ── Cek apakah sudah diisi ──────────────────────────────────────────
    $stmtChk = $mysqli->prepare("SELECT * FROM pes WHERE antrian_id = ? LIMIT 1");
    $stmtChk->bind_param("i", $antrian['id']);
    $stmtChk->execute();
    $existingPes = $stmtChk->get_result()->fetch_assoc();
    $stmtChk->close();

    $isRevision = (bool) $existingPes;

    // ── Daftar pegawai ──────────────────────────────────────────────────
    $pegawaiList = $mysqli->query("SELECT id, nama, nip FROM pegawai ORDER BY nama ASC")->fetch_all(MYSQLI_ASSOC);

    // ── Daftar instansi dari antrian (untuk autocomplete) ───────────────
    $instansiList = $mysqli->query(
        "SELECT DISTINCT instansi FROM antrian WHERE instansi IS NOT NULL AND instansi != '' ORDER BY instansi ASC"
    )->fetch_all(MYSQLI_ASSOC);

    // ── Data kebutuhan dari antrian ─────────────────────────────────────
    $dataItems = [];
    if (!empty($antrian['data_dibutuhkan'])) {
        $parsed = json_decode($antrian['data_dibutuhkan'], true);
        if (is_array($parsed)) $dataItems = $parsed;
    }
    $totalDataItems = count($dataItems);

    // ── Catatan pengunjung dari penilaian ───────────────────────────────
    $catatanPengunjung = null;
    $stmtCat = $mysqli->prepare("SELECT catatan FROM penilaian WHERE antrian_id = ? LIMIT 1");
    $stmtCat->bind_param("i", $antrian['id']);
    $stmtCat->execute();
    $rowCat = $stmtCat->get_result()->fetch_assoc();
    $stmtCat->close();
    if ($rowCat !== null) $catatanPengunjung = $rowCat['catatan'] ?? null;

    // ── Pembantu & kebutuhan data yang sudah tersimpan (pre-fill) ───────
    $existingPembantu      = [];
    $existingKebutuhanData = [];
    if ($existingPes) {
        $stmtPmb = $mysqli->prepare("SELECT pegawai_id FROM pes_pembantu WHERE pes_id = ?");
        $stmtPmb->bind_param("i", $existingPes['id']);
        $stmtPmb->execute();
        $existingPembantu = array_column($stmtPmb->get_result()->fetch_all(MYSQLI_ASSOC), 'pegawai_id');
        $stmtPmb->close();

        $stmtKd = $mysqli->prepare("SELECT * FROM pes_kebutuhan_data WHERE pes_id = ? ORDER BY id");
        $stmtKd->bind_param("i", $existingPes['id']);
        $stmtKd->execute();
        $existingKebutuhanData = $stmtKd->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmtKd->close();
    }

    // ── Konstanta pilihan ───────────────────────────────────────────────
    $kategoriList = [
        'Lembaga Negara',
        'Kementerian & Lembaga Pemerintah',
        'TNI/Polri/BIN/Kejaksaan',
        'Pemerintah Daerah',
        'Lembaga Internasional',
        'Lembaga Penelitian & Pendidikan',
        'BUMN/BUMD',
        'Swasta',
        'Lainnya',
    ];
    $kategoriDesc = [
        'Lembaga Negara'                   => 'DPR, MPR, DPD, MA, MK, BPK, KPK, dsb.',
        'Kementerian & Lembaga Pemerintah' => 'Kemensos, Kemendikbud, BAPPENAS, BPJS, BNN, dsb.',
        'TNI/Polri/BIN/Kejaksaan'          => 'Kodam, Polres, Polsek, Kejari, Brimob, dsb.',
        'Pemerintah Daerah'                => 'Pemkab, Pemkot, Pemprov, Dinas, Badan, Kecamatan, dsb.',
        'Lembaga Internasional'            => 'UNICEF, ILO, World Bank, UNDP, ADB, dsb.',
        'Lembaga Penelitian & Pendidikan'  => 'Universitas, BRIN, sekolah, lembaga riset, dsb.',
        'BUMN/BUMD'                        => 'PLN, Pertamina, Bank BRI/BNI/Mandiri, PDAM, dsb.',
        'Swasta'                           => 'PT swasta, CV, UD, yayasan swasta, perorangan, mahasiswa, dsb.',
        'Lainnya'                          => 'Kategori yang tidak termasuk di atas.',
    ];
    $jenisLayananList = [
        'Perpustakaan',
        'Pembelian Produk Statistik Berbayar (Publikasi BPS/Data Mikro/Peta Wilayah Kerja Statistik)',
        'Akses produk statistik pada Website BPS',
        'Konsultasi Statistik',
        'Rekomendasi Kegiatan Statistik',
    ];
    $saranaList = [
        'Pelayanan Statistik Terpadu (PST) datang langsung',
        'Pelayanan Statistik Terpadu online (pst.bps.go.id)',
        'Website BPS / AllStats BPS',
        'Surat/E-mail',
        'Aplikasi chat (WhatsApp, Telegram, ChatUs, dll.)',
        'Lainnya',
    ];
    $jenisSumberDataList = [
        'Publikasi',
        'Data Mikro',
        'Peta Wilkerstat',
        'Tabulasi Data',
        'Tabel di Website',
    ];

    // ── Label petugas utama untuk pre-fill search input ─────────────────
    $petugasUtamaLabel = '';
    if (!empty($existingPes['petugas_utama_id'])) {
        foreach ($pegawaiList as $pgw) {
            if ($pgw['id'] == $existingPes['petugas_utama_id']) {
                $petugasUtamaLabel = $pgw['nama'];
                break;
            }
        }
    }

    // ── Data pre-fill ───────────────────────────────────────────────────
    $old = [];
    if ($isRevision && $_SERVER['REQUEST_METHOD'] !== 'POST') {
        $old['petugas_utama_id']          = $existingPes['petugas_utama_id'] ?? '';
        $old['kategori_instansi']         = $existingPes['kategori_instansi'] ?? '';
        $old['kategori_instansi_lainnya'] = $existingPes['kategori_instansi_lainnya'] ?? '';
        $old['jenis_layanan']             = json_decode($existingPes['jenis_layanan'] ?? '[]', true) ?: [];
        $old['sarana']                    = json_decode($existingPes['sarana'] ?? '[]', true) ?: [];
        $old['sarana_lainnya']            = $existingPes['sarana_lainnya'] ?? '';
        $old['pembantu']                  = $existingPembantu;
        $old['sentimen_kritik_saran']     = $existingPes['sentimen_kritik_saran'] ?? '';
        $old['jenis_sumber_data']         = array_column($existingKebutuhanData, 'jenis_sumber_data');
        $old['judul_sumber_data']         = array_column($existingKebutuhanData, 'judul_sumber_data');
        $old['tahun_sumber_data']         = array_column($existingKebutuhanData, 'tahun_sumber_data');
    }

    $errorMsg = '';
    $nama     = $antrian['nama']     ?? '-';
    $instansi = $antrian['instansi'] ?? '-';
    $tanggal  = $antrian['tanggal']  ?? '';

    // ── Handle POST ─────────────────────────────────────────────────────
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $old = $_POST;
        $old['jenis_layanan']     = $_POST['jenis_layanan']     ?? [];
        $old['sarana']            = $_POST['sarana']            ?? [];
        $old['pembantu']          = array_map('intval', $_POST['pembantu'] ?? []);
        $old['jenis_sumber_data'] = $_POST['jenis_sumber_data'] ?? [];
        $old['judul_sumber_data'] = $_POST['judul_sumber_data'] ?? [];
        $old['tahun_sumber_data'] = $_POST['tahun_sumber_data'] ?? [];

        $petugasUtamaId       = intval($_POST['petugas_utama_id'] ?? 0) ?: null;
        $kategoriInstansi     = trim($_POST['kategori_instansi'] ?? '');
        $kategoriInstansiLain = trim($_POST['kategori_instansi_lainnya'] ?? '');
        $jenisLayanan         = array_values(array_filter($old['jenis_layanan']));
        $sarana               = array_values(array_filter($old['sarana']));
        $saranaLain           = trim($_POST['sarana_lainnya'] ?? '');
        $pembantuIds          = $old['pembantu'];
        $sentimenKritikSaran  = trim($_POST['sentimen_kritik_saran'] ?? '') ?: null;
        $jenisSumberData      = $old['jenis_sumber_data'];
        $judulSumberData      = $old['judul_sumber_data'];
        $tahunSumberData      = $old['tahun_sumber_data'];

        // Pre-fill label
        foreach ($pegawaiList as $pgw) {
            if ($pgw['id'] == $petugasUtamaId) { $petugasUtamaLabel = $pgw['nama']; break; }
        }

        // Validasi
        if (!$petugasUtamaId) {
            $errorMsg = 'Petugas utama wajib dipilih.';
        } elseif (empty($kategoriInstansi)) {
            $errorMsg = 'Kategori instansi wajib dipilih.';
        } elseif ($kategoriInstansi === 'Lainnya' && $kategoriInstansiLain === '') {
            $errorMsg = 'Keterangan kategori instansi lainnya wajib diisi.';
        } elseif (empty($jenisLayanan)) {
            $errorMsg = 'Jenis layanan wajib dipilih minimal satu.';
        } elseif (empty($sarana)) {
            $errorMsg = 'Sarana wajib dipilih minimal satu.';
        } elseif (in_array('Lainnya', $sarana, true) && $saranaLain === '') {
            $errorMsg = 'Keterangan sarana lainnya wajib diisi.';
        }

        if ($errorMsg === '') {
            $jenisLayananJson = json_encode($jenisLayanan, JSON_UNESCAPED_UNICODE);
            $saranaJson       = json_encode($sarana,       JSON_UNESCAPED_UNICODE);

            $mysqli->begin_transaction();
            try {
                if ($isRevision) {
                    $stmtSave = $mysqli->prepare(
                        "UPDATE pes SET
                            petugas_utama_id=?, kategori_instansi=?, kategori_instansi_lainnya=?,
                            jenis_layanan=?, sarana=?, sarana_lainnya=?,
                            sentimen_kritik_saran=?, submitted_at=NOW()
                         WHERE id=?"
                    );
                    $stmtSave->bind_param(
                        "issssssi",
                        $petugasUtamaId, $kategoriInstansi, $kategoriInstansiLain,
                        $jenisLayananJson, $saranaJson, $saranaLain,
                        $sentimenKritikSaran, $existingPes['id']
                    );
                    $stmtSave->execute();
                    $pesId = $existingPes['id'];
                    $stmtSave->close();
                    $stmtDel = $mysqli->prepare("DELETE FROM pes_pembantu WHERE pes_id = ?");
                    $stmtDel->bind_param("i", $pesId);
                    $stmtDel->execute();
                    $stmtDel->close();
                } else {
                    $antrianId = $antrian['id'];
                    $stmtSave  = $mysqli->prepare(
                        "INSERT INTO pes
                            (antrian_id, petugas_utama_id, kategori_instansi, kategori_instansi_lainnya,
                             jenis_layanan, sarana, sarana_lainnya, sentimen_kritik_saran, submitted_at)
                         VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())"
                    );
                    $stmtSave->bind_param(
                        "iissssss",
                        $antrianId, $petugasUtamaId, $kategoriInstansi, $kategoriInstansiLain,
                        $jenisLayananJson, $saranaJson, $saranaLain, $sentimenKritikSaran
                    );
                    $stmtSave->execute();
                    $pesId = $mysqli->insert_id;
                    $stmtSave->close();
                }

                // Simpan pembantu
                if (!empty($pembantuIds)) {
                    $stmtPmb = $mysqli->prepare("INSERT INTO pes_pembantu (pes_id, pegawai_id) VALUES (?, ?)");
                    foreach ($pembantuIds as $pgwId) {
                        if ($pgwId && $pgwId !== $petugasUtamaId) {
                            $stmtPmb->bind_param("ii", $pesId, $pgwId);
                            $stmtPmb->execute();
                        }
                    }
                    $stmtPmb->close();
                }

                // Simpan kebutuhan data
                $stmtDelKd = $mysqli->prepare("DELETE FROM pes_kebutuhan_data WHERE pes_id = ?");
                $stmtDelKd->bind_param("i", $pesId);
                $stmtDelKd->execute();
                $stmtDelKd->close();
                if ($totalDataItems > 0) {
                    $stmtKd = $mysqli->prepare(
                        "INSERT INTO pes_kebutuhan_data (pes_id, butir_kebutuhan, jenis_sumber_data, judul_sumber_data, tahun_sumber_data)
                         VALUES (?, ?, ?, ?, ?)"
                    );
                    foreach ($dataItems as $i => $item) {
                        $butir      = $item['data'] ?? '';
                        $jenisSumber = $jenisSumberData[$i] ?? '';
                        $judul      = trim($judulSumberData[$i] ?? '');
                        $tahun      = intval($tahunSumberData[$i] ?? 0);
                        $stmtKd->bind_param("isssi", $pesId, $butir, $jenisSumber, $judul, $tahun);
                        $stmtKd->execute();
                    }
                    $stmtKd->close();
                }

                $mysqli->commit();
                renderPesMessage('done', 'PES Berhasil Disimpan',
                    'Data Post Enumeration Survey untuk pengunjung <strong>' . htmlspecialchars($nama) . '</strong> telah berhasil disimpan.' .
                    ($isRevision ? ' (Revisi)' : ''));
                return;

            } catch (Exception $e) {
                $mysqli->rollback();
                $errorMsg = 'Gagal menyimpan: ' . $e->getMessage();
            }
        }
    }

    // ── Render form ─────────────────────────────────────────────────────
    $jkLabel      = ($antrian['jk'] ?? '') === 'L' ? 'Laki-laki' : (($antrian['jk'] ?? '') === 'P' ? 'Perempuan' : '-');
    $jenisLabel   = ['umum' => 'Umum', 'disabilitas' => 'Disabilitas', 'whatsapp' => 'WhatsApp'][$antrian['jenis'] ?? ''] ?? ($antrian['jenis'] ?? '-');
    $tanggalLabel = $tanggal ? date('d F Y', strtotime($tanggal)) : '-';

    // Hitung label sentimen untuk seksi terakhir (d.e berurutan dari jenis layanan)
    $seksiSumber  = $totalDataItems > 0 ? 'd' : null;
    $seksiSentimen = $totalDataItems > 0 ? 'e' : 'd';

    ?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($judul) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        .dropdown-open { border-color: #0d9488 !important; box-shadow: 0 0 0 2px rgba(13,148,136,.25); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen py-8 px-4">
<div class="max-w-2xl mx-auto">

    <!-- Header -->
    <div class="text-center mb-6">
        <div class="inline-flex items-center justify-center w-14 h-14 rounded-2xl bg-teal-600 text-white text-3xl mb-3">📋</div>
        <h1 class="text-2xl font-bold text-slate-800"><?= htmlspecialchars($judul) ?></h1>
        <p class="text-slate-500 text-sm mt-1">BPS Kabupaten Buleleng · Diisi oleh Petugas PST</p>
        <?php if ($isRevision): ?>
        <div class="mt-2 inline-block bg-amber-100 text-amber-800 text-xs font-semibold px-3 py-1 rounded-full">Mode Revisi</div>
        <?php endif; ?>
    </div>

    <!-- Info Pengunjung -->
    <div class="bg-white border border-slate-200 rounded-2xl p-5 mb-5 shadow-sm">
        <h2 class="font-semibold text-slate-700 mb-3 text-sm uppercase tracking-wide">Data Pengunjung</h2>
        <dl class="grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
            <dt class="text-slate-500">Nama</dt>
            <dd class="font-medium text-slate-800"><?= htmlspecialchars($nama) ?></dd>
            <dt class="text-slate-500">Instansi</dt>
            <dd class="font-medium text-slate-800"><?= htmlspecialchars($instansi ?: '-') ?></dd>
            <dt class="text-slate-500">Jenis</dt>
            <dd><span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-teal-100 text-teal-800"><?= htmlspecialchars($jenisLabel) ?></span></dd>
            <dt class="text-slate-500">Tanggal Kunjungan</dt>
            <dd class="font-medium text-slate-800"><?= htmlspecialchars($tanggalLabel) ?></dd>
        </dl>
    </div>

    <?php if ($errorMsg): ?>
    <div class="bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 mb-5 text-sm flex items-start gap-2">
        <i class="fas fa-circle-exclamation mt-0.5 flex-shrink-0"></i>
        <span><?= htmlspecialchars($errorMsg) ?></span>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" id="formPes" class="space-y-5" onsubmit="return validasiForm()">

        <!-- ══ Petugas Pelayanan ══════════════════════════════════════════ -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h2 class="font-semibold text-slate-700 mb-4 flex items-center gap-2">
                <i class="fas fa-user-tie text-teal-600 text-sm"></i>
                Petugas Pelayanan
            </h2>

            <!-- Petugas Utama — searchable -->
            <div class="mb-5">
                <label class="block text-sm font-medium text-slate-700 mb-1.5">
                    Petugas Utama <span class="text-red-500">*</span>
                </label>
                <input type="hidden" name="petugas_utama_id" id="petugasUtamaId"
                       value="<?= htmlspecialchars($old['petugas_utama_id'] ?? '') ?>">
                <div class="relative" id="petugasUtamaWrap">
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                            <i class="fas fa-user text-xs"></i>
                        </span>
                        <input type="text" id="petugasUtamaSearch" autocomplete="off"
                               value="<?= htmlspecialchars($petugasUtamaLabel) ?>"
                               placeholder="Cari nama petugas…"
                               class="w-full pl-8 pr-8 py-2.5 border border-slate-300 rounded-xl text-sm focus:outline-none transition"
                               onfocus="bukaDropdownUtama()" oninput="filterDropdownUtama()">
                        <span class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 pointer-events-none">
                            <i class="fas fa-chevron-down text-xs transition-transform" id="utamaChevron"></i>
                        </span>
                    </div>
                    <ul id="petugasUtamaList"
                        class="absolute z-50 w-full mt-1 bg-white border border-slate-200 rounded-xl shadow-lg max-h-52 overflow-y-auto hidden text-sm">
                        <?php foreach ($pegawaiList as $pgw):
                            $lbl   = htmlspecialchars($pgw['nama']) . ($pgw['nip'] ? ' · ' . htmlspecialchars($pgw['nip']) : '');
                            $srch  = strtolower($pgw['nama'] . ' ' . ($pgw['nip'] ?? ''));
                        ?>
                        <li class="px-3 py-2.5 hover:bg-teal-50 cursor-pointer utama-option"
                            data-id="<?= $pgw['id'] ?>"
                            data-label="<?= htmlspecialchars($pgw['nama']) ?>"
                            data-search="<?= htmlspecialchars($srch) ?>"
                            onclick="pilihPetugasUtama(this)">
                            <p class="font-medium text-slate-800"><?= htmlspecialchars($pgw['nama']) ?></p>
                            <?php if ($pgw['nip']): ?>
                            <p class="text-xs text-slate-400"><?= htmlspecialchars($pgw['nip']) ?></p>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <p id="petugasUtamaError" class="mt-1 text-xs text-red-500 hidden">Petugas utama wajib dipilih.</p>
            </div>

            <!-- Petugas Pembantu — searchable + preview -->
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">
                    Petugas Pembantu
                    <span class="text-slate-400 font-normal">(bisa lebih dari satu)</span>
                </label>
                <?php if (empty($pegawaiList)): ?>
                <p class="text-slate-400 text-sm">Belum ada data pegawai.</p>
                <?php else: ?>
                <div class="relative mb-2">
                    <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-slate-400 pointer-events-none">
                        <i class="fas fa-magnifying-glass text-xs"></i>
                    </span>
                    <input type="text" id="cariPembantu" autocomplete="off"
                           placeholder="Cari nama petugas pembantu…"
                           class="w-full pl-8 pr-4 py-2 border border-slate-200 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-teal-400 bg-slate-50"
                           oninput="filterPembantu()">
                </div>
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <div class="max-h-40 overflow-y-auto divide-y divide-slate-50 py-1" id="pembantuCheckList">
                        <?php foreach ($pegawaiList as $pgw): ?>
                        <label class="flex items-center gap-3 px-3 py-2.5 hover:bg-slate-50 cursor-pointer pembantu-item"
                               data-id="<?= $pgw['id'] ?>"
                               data-nama="<?= htmlspecialchars($pgw['nama']) ?>">
                            <input type="checkbox" name="pembantu[]" value="<?= $pgw['id'] ?>"
                                <?= in_array($pgw['id'], $old['pembantu'] ?? [], false) ? 'checked' : '' ?>
                                class="accent-teal-600 w-4 h-4 flex-shrink-0"
                                onchange="updatePembantuPreview()">
                            <span class="text-sm text-slate-700"><?= htmlspecialchars($pgw['nama']) ?></span>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <!-- Preview yang dipilih -->
                <div class="mt-2.5 bg-teal-50 border border-teal-100 rounded-xl px-3 py-2.5 min-h-[40px] flex items-start flex-wrap gap-1.5"
                     id="pembantuPreview">
                    <p class="text-slate-400 text-xs italic self-center">Belum ada yang dipilih</p>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ══ a. Kategori Instansi ═══════════════════════════════════════ -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h2 class="font-semibold text-slate-700 mb-3">
                a. Kategori Instansi <span class="text-red-500">*</span>
            </h2>
            <?php if (!empty($instansi) && $instansi !== '-'): ?>
            <div class="mb-3 flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 text-sm">
                <i class="fas fa-building text-slate-400 text-xs flex-shrink-0"></i>
                <span class="text-slate-500 text-xs">Instansi pengunjung:</span>
                <span class="font-semibold text-slate-700"><?= htmlspecialchars($instansi) ?></span>
            </div>
            <?php endif; ?>
            <div class="space-y-2">
                <?php foreach ($kategoriList as $kat): ?>
                <label class="flex items-start gap-2.5 text-sm cursor-pointer">
                    <input type="radio" name="kategori_instansi" value="<?= htmlspecialchars($kat) ?>"
                        <?= (($old['kategori_instansi'] ?? '') === $kat) ? 'checked' : '' ?>
                        class="accent-teal-600 mt-0.5 w-4 h-4 flex-shrink-0"
                        onchange="updateKategoriPreview()">
                    <span class="text-slate-700"><?= htmlspecialchars($kat) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <!-- Preview deskripsi kategori -->
            <div id="kategoriPreview"
                 class="mt-3 hidden bg-teal-50 border border-teal-100 rounded-xl px-3 py-2.5 text-xs text-teal-800 flex items-start gap-2">
                <i class="fas fa-circle-info flex-shrink-0 mt-0.5"></i>
                <span id="kategoriPreviewText"></span>
            </div>
            <div id="kategori-lain-wrap" class="mt-3 <?= (($old['kategori_instansi'] ?? '') === 'Lainnya') ? '' : 'hidden' ?>">
                <input type="text" name="kategori_instansi_lainnya"
                    list="instansiDatalist"
                    value="<?= htmlspecialchars($old['kategori_instansi_lainnya'] ?? '') ?>"
                    placeholder="Sebutkan atau pilih nama instansi…"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
                <datalist id="instansiDatalist">
                    <?php foreach ($instansiList as $row): ?>
                    <option value="<?= htmlspecialchars($row['instansi']) ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
        </div>

        <!-- ══ b. Jenis Layanan ═══════════════════════════════════════════ -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h2 class="font-semibold text-slate-700 mb-4">
                b. Jenis Layanan <span class="text-red-500">*</span>
                <span class="text-slate-400 font-normal text-xs">(bisa lebih dari satu)</span>
            </h2>
            <div class="space-y-2">
                <?php foreach ($jenisLayananList as $jl): ?>
                <label class="flex items-start gap-2.5 text-sm cursor-pointer">
                    <input type="checkbox" name="jenis_layanan[]" value="<?= htmlspecialchars($jl) ?>"
                        <?= in_array($jl, $old['jenis_layanan'] ?? [], true) ? 'checked' : '' ?>
                        class="accent-teal-600 mt-0.5 w-4 h-4 flex-shrink-0">
                    <span class="text-slate-700"><?= htmlspecialchars($jl) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- ══ c. Sarana ══════════════════════════════════════════════════ -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h2 class="font-semibold text-slate-700 mb-4">
                c. Sarana <span class="text-red-500">*</span>
                <span class="text-slate-400 font-normal text-xs">(bisa lebih dari satu)</span>
            </h2>
            <div class="space-y-2">
                <?php foreach ($saranaList as $sar): ?>
                <label class="flex items-start gap-2.5 text-sm cursor-pointer">
                    <input type="checkbox" name="sarana[]" value="<?= htmlspecialchars($sar) ?>"
                        <?= in_array($sar, $old['sarana'] ?? [], true) ? 'checked' : '' ?>
                        class="accent-teal-600 mt-0.5 w-4 h-4 flex-shrink-0"
                        <?= $sar === 'Lainnya' ? 'onchange="toggleSaranaLain()"' : '' ?>>
                    <span class="text-slate-700"><?= htmlspecialchars($sar) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
            <div id="sarana-lain-wrap" class="mt-3 <?= in_array('Lainnya', $old['sarana'] ?? [], true) ? '' : 'hidden' ?>">
                <input type="text" name="sarana_lainnya"
                    value="<?= htmlspecialchars($old['sarana_lainnya'] ?? '') ?>"
                    placeholder="Sebutkan sarana lainnya…"
                    class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-500">
            </div>
        </div>

        <?php if ($totalDataItems > 0): ?>
        <!-- ══ d. Sumber Data per Butir Kebutuhan ═════════════════════════ -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h2 class="font-semibold text-slate-700 mb-1 flex items-center gap-2">
                <span>d. Sumber Data yang Diberikan</span>
            </h2>
            <p class="text-xs text-slate-400 mb-4">Isi sumber data yang diberikan untuk setiap butir kebutuhan data pengunjung.</p>
            <div class="space-y-4">
                <?php foreach ($dataItems as $i => $item):
                    $namaData  = $item['data'] ?? 'Data ke-' . ($i + 1);
                    $tdari     = $item['tahun_dari']   ?? '';
                    $tsampai   = $item['tahun_sampai'] ?? '';
                    $rangeStr  = ($tdari || $tsampai) ? ' (' . $tdari . ($tsampai && $tsampai != $tdari ? '–' . $tsampai : '') . ')' : '';
                    $oldJenis  = $old['jenis_sumber_data'][$i] ?? '';
                    $oldJudul  = $old['judul_sumber_data'][$i] ?? '';
                    $oldTahun  = $old['tahun_sumber_data'][$i] ?? '';
                ?>
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <div class="bg-slate-50 px-4 py-2.5 border-b border-slate-200 flex items-start gap-2">
                        <span class="flex-shrink-0 w-5 h-5 rounded-full bg-teal-600 text-white text-[10px] font-bold flex items-center justify-center mt-0.5"><?= $i + 1 ?></span>
                        <div>
                            <p class="text-sm font-medium text-slate-800"><?= htmlspecialchars($namaData) ?></p>
                            <?php if ($rangeStr): ?>
                            <p class="text-xs text-slate-400"><?= htmlspecialchars($rangeStr) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="p-4 space-y-3">
                        <!-- Jenis sumber data -->
                        <div>
                            <p class="text-xs font-semibold text-slate-600 mb-2">Jenis Sumber Data</p>
                            <div class="flex flex-wrap gap-2">
                                <?php foreach ($jenisSumberDataList as $jsdk): ?>
                                <label class="flex items-center gap-1.5 text-xs cursor-pointer">
                                    <input type="radio" name="jenis_sumber_data[<?= $i ?>]"
                                           value="<?= htmlspecialchars($jsdk) ?>"
                                           <?= $oldJenis === $jsdk ? 'checked' : '' ?>
                                           class="accent-teal-600 w-3.5 h-3.5">
                                    <span class="text-slate-700"><?= htmlspecialchars($jsdk) ?></span>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <!-- Judul sumber data -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Judul Sumber Data</label>
                            <input type="text" name="judul_sumber_data[<?= $i ?>]"
                                   value="<?= htmlspecialchars($oldJudul) ?>"
                                   placeholder="Contoh: Statistik Kesejahteraan Rakyat 2024…"
                                   class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                        </div>
                        <!-- Tahun sumber data -->
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1.5">Tahun Sumber Data</label>
                            <input type="number" name="tahun_sumber_data[<?= $i ?>]"
                                   value="<?= htmlspecialchars($oldTahun ?: '') ?>"
                                   min="1990" max="2099" placeholder="mis. 2024"
                                   class="w-32 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-teal-400">
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ══ d/e. Sentimen Kritik dan Saran ════════════════════════════ -->
        <div class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm">
            <h2 class="font-semibold text-slate-700 mb-1 flex items-center gap-2">
                <span><?= htmlspecialchars($seksiSentimen) ?>. Sentimen Kritik dan Saran</span>
            </h2>
            <p class="text-xs text-slate-400 mb-4">Penilaian sentimen berdasarkan catatan yang ditulis pengunjung.</p>

            <!-- Catatan pengunjung — preview -->
            <div class="mb-4">
                <p class="text-xs font-semibold text-slate-600 mb-2 flex items-center gap-1.5">
                    <i class="fas fa-comment-dots text-slate-400"></i>
                    Catatan dari Pengunjung
                </p>
                <?php if ($catatanPengunjung !== null && trim($catatanPengunjung) !== ''): ?>
                <div class="bg-slate-50 border border-slate-200 rounded-xl px-4 py-3 text-sm text-slate-700 leading-relaxed italic">
                    "<?= nl2br(htmlspecialchars($catatanPengunjung)) ?>"
                </div>
                <?php elseif ($catatanPengunjung !== null): ?>
                <div class="bg-slate-50 border border-dashed border-slate-300 rounded-xl px-4 py-5 text-center">
                    <i class="fas fa-comment-slash text-2xl text-slate-300 mb-2 block"></i>
                    <p class="text-xs text-slate-400">Pengunjung tidak menulis catatan.</p>
                </div>
                <?php else: ?>
                <div class="bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 flex items-start gap-2 text-xs text-amber-700">
                    <i class="fas fa-triangle-exclamation flex-shrink-0 mt-0.5"></i>
                    <span>Pengunjung belum mengisi survei kepuasan. Catatan belum tersedia.</span>
                </div>
                <?php endif; ?>
            </div>

            <!-- Pilihan sentimen -->
            <p class="text-xs font-semibold text-slate-600 mb-2">Sentimen</p>
            <div class="grid grid-cols-3 gap-3">
                <?php
                $sentimenOpts = [
                    'negatif' => ['label' => 'Negatif', 'icon' => '😞', 'color' => 'red',    'ring' => 'ring-red-400',    'bg' => 'bg-red-50',    'text' => 'text-red-700',    'border' => 'border-red-300'],
                    'normal'  => ['label' => 'Normal',  'icon' => '😐', 'color' => 'slate',  'ring' => 'ring-slate-400',  'bg' => 'bg-slate-100', 'text' => 'text-slate-700',  'border' => 'border-slate-300'],
                    'positif' => ['label' => 'Positif', 'icon' => '😊', 'color' => 'green',  'ring' => 'ring-green-400',  'bg' => 'bg-green-50',  'text' => 'text-green-700',  'border' => 'border-green-300'],
                ];
                $curSentimen = $old['sentimen_kritik_saran'] ?? '';
                foreach ($sentimenOpts as $val => $opt):
                ?>
                <label class="cursor-pointer">
                    <input type="radio" name="sentimen_kritik_saran" value="<?= $val ?>"
                           <?= $curSentimen === $val ? 'checked' : '' ?>
                           class="sr-only peer" onchange="updateSentimenUI()">
                    <div class="sentimen-card border-2 rounded-xl px-3 py-4 text-center transition select-none
                                peer-checked:<?= $opt['ring'] ?> peer-checked:ring-2 peer-checked:<?= $opt['bg'] ?>
                                border-slate-200 hover:border-slate-300 <?= $curSentimen === $val ? $opt['border'] . ' ' . $opt['bg'] : '' ?>"
                         data-val="<?= $val ?>" data-border="<?= $opt['border'] ?>" data-bg="<?= $opt['bg'] ?>">
                        <span class="text-2xl block mb-1"><?= $opt['icon'] ?></span>
                        <span class="text-xs font-semibold <?= $opt['text'] ?>"><?= $opt['label'] ?></span>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- Submit -->
        <button type="submit"
            class="w-full bg-teal-600 hover:bg-teal-700 active:bg-teal-800 text-white font-semibold py-3.5 rounded-2xl transition text-sm flex items-center justify-center gap-2 shadow-md shadow-teal-200">
            <i class="fas fa-paper-plane"></i>
            <?= $isRevision ? 'Simpan Revisi PES' : 'Kirim PES' ?>
        </button>

    </form>
</div>

<script>
// ─────────────────────────────────────────────────────────────────────────────
//  Utilitas
// ─────────────────────────────────────────────────────────────────────────────
function escH(s) {
    return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ─────────────────────────────────────────────────────────────────────────────
//  Petugas Utama — Searchable Dropdown
// ─────────────────────────────────────────────────────────────────────────────
function bukaDropdownUtama() {
    const list = document.getElementById('petugasUtamaList');
    const inp  = document.getElementById('petugasUtamaSearch');
    list.classList.remove('hidden');
    inp.classList.add('dropdown-open');
    document.getElementById('utamaChevron').style.transform = 'rotate(180deg)';
}
function tutupDropdownUtama() {
    const list = document.getElementById('petugasUtamaList');
    const inp  = document.getElementById('petugasUtamaSearch');
    list.classList.add('hidden');
    inp.classList.remove('dropdown-open');
    document.getElementById('utamaChevron').style.transform = '';
}
function filterDropdownUtama() {
    const q = document.getElementById('petugasUtamaSearch').value.toLowerCase();
    document.querySelectorAll('.utama-option').forEach(li => {
        li.style.display = li.dataset.search.includes(q) ? '' : 'none';
    });
    document.getElementById('petugasUtamaList').classList.remove('hidden');
    document.getElementById('utamaChevron').style.transform = 'rotate(180deg)';
}
function pilihPetugasUtama(li) {
    document.getElementById('petugasUtamaId').value     = li.dataset.id;
    document.getElementById('petugasUtamaSearch').value = li.dataset.label;
    document.getElementById('petugasUtamaError').classList.add('hidden');
    tutupDropdownUtama();
    syncPetugasUtamaToPembantu();
}
function syncPetugasUtamaToPembantu() {
    const utamaId = document.getElementById('petugasUtamaId').value;
    document.querySelectorAll('.pembantu-item').forEach(item => {
        const cb = item.querySelector('input[type=checkbox]');
        if (item.dataset.id === utamaId) {
            cb.checked = false;
            item.style.opacity = '0.35';
            item.style.pointerEvents = 'none';
        } else {
            item.style.opacity = '';
            item.style.pointerEvents = '';
        }
    });
    updatePembantuPreview();
}

// Tutup dropdown saat klik di luar
document.addEventListener('click', function(e) {
    const wrap = document.getElementById('petugasUtamaWrap');
    if (wrap && !wrap.contains(e.target)) {
        tutupDropdownUtama();
        // Jika tidak ada pilihan valid, kosongkan
        if (!document.getElementById('petugasUtamaId').value) {
            document.getElementById('petugasUtamaSearch').value = '';
        }
    }
});

// ─────────────────────────────────────────────────────────────────────────────
//  Petugas Pembantu — Search + Preview
// ─────────────────────────────────────────────────────────────────────────────
function filterPembantu() {
    const q = document.getElementById('cariPembantu').value.toLowerCase();
    document.querySelectorAll('.pembantu-item').forEach(item => {
        item.style.display = item.dataset.nama.toLowerCase().includes(q) ? '' : 'none';
    });
}
function updatePembantuPreview() {
    const checked = [...document.querySelectorAll('input[name="pembantu[]"]:checked')];
    const preview = document.getElementById('pembantuPreview');
    if (!preview) return;
    if (checked.length === 0) {
        preview.innerHTML = '<p class="text-slate-400 text-xs italic self-center">Belum ada yang dipilih</p>';
    } else {
        preview.innerHTML = checked.map(cb => {
            const nama = cb.closest('.pembantu-item').dataset.nama;
            return `<span class="inline-flex items-center gap-1.5 bg-teal-100 text-teal-800 text-xs font-semibold px-2.5 py-1 rounded-full">
                <i class="fas fa-user text-[10px]"></i>${escH(nama)}</span>`;
        }).join('');
    }
}

// ─────────────────────────────────────────────────────────────────────────────
//  Kategori Instansi — Preview Deskripsi
// ─────────────────────────────────────────────────────────────────────────────
const KATEGORI_DESC = <?= json_encode($kategoriDesc, JSON_UNESCAPED_UNICODE) ?>;

function updateKategoriPreview() {
    const sel  = document.querySelector('input[name="kategori_instansi"]:checked');
    const box  = document.getElementById('kategoriPreview');
    const txt  = document.getElementById('kategoriPreviewText');
    if (sel && KATEGORI_DESC[sel.value]) {
        txt.innerHTML = '<strong>' + escH(sel.value) + ':</strong> ' + escH(KATEGORI_DESC[sel.value]);
        box.classList.remove('hidden');
    } else {
        box.classList.add('hidden');
    }
    toggleKategoriLain();
}
function toggleKategoriLain() {
    const isLain = document.querySelector('input[name="kategori_instansi"]:checked')?.value === 'Lainnya';
    document.getElementById('kategori-lain-wrap').classList.toggle('hidden', !isLain);
}

// ─────────────────────────────────────────────────────────────────────────────
//  Sarana Lainnya
// ─────────────────────────────────────────────────────────────────────────────
function toggleSaranaLain() {
    const isLain = [...document.querySelectorAll('input[name="sarana[]"]:checked')]
        .some(cb => cb.value === 'Lainnya');
    document.getElementById('sarana-lain-wrap').classList.toggle('hidden', !isLain);
}

// ─────────────────────────────────────────────────────────────────────────────
//  Sentimen UI
// ─────────────────────────────────────────────────────────────────────────────
function updateSentimenUI() {
    // Visual feedback sudah handled oleh peer-checked Tailwind, tapi kita
    // juga update border supaya langsung terlihat tanpa toggle class
    document.querySelectorAll('.sentimen-card').forEach(card => {
        const radio = card.closest('label').querySelector('input[type=radio]');
        card.classList.toggle('ring-2', radio.checked);
    });
}

// ─────────────────────────────────────────────────────────────────────────────
//  Validasi sebelum submit
// ─────────────────────────────────────────────────────────────────────────────
function validasiForm() {
    const utamaId = document.getElementById('petugasUtamaId').value;
    if (!utamaId) {
        document.getElementById('petugasUtamaError').classList.remove('hidden');
        document.getElementById('petugasUtamaSearch').focus();
        return false;
    }
    return true;
}

// ─────────────────────────────────────────────────────────────────────────────
//  Init saat halaman pertama kali dimuat
// ─────────────────────────────────────────────────────────────────────────────
syncPetugasUtamaToPembantu();
updatePembantuPreview();
updateKategoriPreview();
</script>
</body>
</html>
<?php
}

function renderPesMessage($type, $judul, $pesan) {
    $icon = $type === 'done' ? '✅' : ($type === 'error' ? '❌' : 'ℹ️');
    ?><!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($judul) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center px-4">
    <div class="text-center max-w-md">
        <div class="text-6xl mb-4"><?= $icon ?></div>
        <h1 class="text-2xl font-bold text-slate-800 mb-2"><?= htmlspecialchars($judul) ?></h1>
        <p class="text-slate-600 text-sm"><?= $pesan ?></p>
    </div>
</body>
</html><?php
}
