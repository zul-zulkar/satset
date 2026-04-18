<?php
include '../db.php';

$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

// Tab 1: Semua tamu (umum + disabilitas + whatsapp + surat)
$stmt1 = $mysqli->prepare(
    "SELECT a.*,
            (SELECT link_surat_balasan FROM pes WHERE antrian_id = a.id LIMIT 1) AS link_surat_balasan
     FROM antrian a
     WHERE MONTH(a.tanggal) = ? AND YEAR(a.tanggal) = ?
     ORDER BY a.tanggal DESC, a.id DESC"
);
$stmt1->bind_param("ii", $bulan, $tahun);
$stmt1->execute();
$resTamu = $stmt1->get_result();

// Tab 2: Pengunjung PST (whatsapp otomatis + umum/disabilitas jika kunjungan_pst=1)
$stmt2 = $mysqli->prepare(
    "SELECT a.*,
            (SELECT id FROM penilaian WHERE antrian_id = a.id LIMIT 1) AS penilaian_id,
            (SELECT id FROM pes       WHERE antrian_id = a.id LIMIT 1) AS pes_id,
            (SELECT link_surat_balasan FROM pes WHERE antrian_id = a.id LIMIT 1) AS link_surat_balasan
     FROM antrian a
     WHERE (a.jenis IN ('whatsapp','surat') OR (a.jenis IN ('umum','disabilitas') AND a.kunjungan_pst = 1))
       AND MONTH(a.tanggal) = ? AND YEAR(a.tanggal) = ?
     ORDER BY a.tanggal DESC, a.id DESC"
);
$stmt2->bind_param("ii", $bulan, $tahun);
$stmt2->execute();
$resPST = $stmt2->get_result();

$jenisMeta = [
    'umum'        => ['border' => 'border-l-4 border-l-blue-500',   'badge' => 'bg-blue-100 text-blue-800 border-blue-200',     'icon' => 'fa-solid fa-user',         'label' => 'Umum'],
    'disabilitas' => ['border' => 'border-l-4 border-l-purple-500', 'badge' => 'bg-purple-100 text-purple-800 border-purple-200','icon' => 'fa-solid fa-wheelchair',   'label' => 'Disabilitas'],
    'whatsapp'    => ['border' => 'border-l-4 border-l-green-500',  'badge' => 'bg-green-100 text-green-800 border-green-200',   'icon' => 'fa-brands fa-whatsapp',    'label' => 'WhatsApp'],
    'surat'       => ['border' => 'border-l-4 border-l-amber-500',  'badge' => 'bg-amber-100 text-amber-800 border-amber-200',   'icon' => 'fa-solid fa-envelope',     'label' => 'Surat'],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Daftar Pengguna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <!-- safelist amber classes agar Tailwind CDN tidak prune -->
    <div class="hidden bg-amber-100 text-amber-800 border-amber-200 border-l-4 border-l-amber-500"></div>
    <style>
        .score-1,.score-2  { background:#fee2e2;color:#dc2626; }
        .score-3,.score-4  { background:#ffedd5;color:#ea580c; }
        .score-5,.score-6  { background:#fef9c3;color:#ca8a04; }
        .score-7,.score-8  { background:#dcfce7;color:#16a34a; }
        .score-9,.score-10 { background:#bbf7d0;color:#15803d; }
        .score-badge-inline { font-size:0.7rem; font-weight:700; padding:0.15rem 0.45rem; border-radius:999px; margin-left:0.25rem; }
    </style>
</head>
<body class="bg-gray-100 p-2 sm:p-6">
<div class="max-w-6xl mx-auto bg-white p-3 sm:p-6 rounded shadow">
    <h1 class="text-xl sm:text-2xl font-bold mb-4">Daftar Pengguna</h1>

    <!-- Filter & Export -->
    <form method="GET" class="flex flex-wrap items-center gap-2 mb-5">
        <select name="bulan" class="border p-2 text-sm rounded flex-1 min-w-[110px]">
            <?php for ($i = 1; $i <= 12; $i++):
                $val = str_pad($i, 2, '0', STR_PAD_LEFT);
                $selected = ($val == $bulan) ? 'selected' : '';
                echo "<option value='$val' $selected>" . date('F', mktime(0,0,0,$i,1)) . "</option>";
            endfor; ?>
        </select>
        <select name="tahun" class="border p-2 text-sm rounded flex-1 min-w-[80px]">
            <?php $currentYear = date('Y');
            for ($i = $currentYear; $i >= $currentYear - 5; $i--):
                $selected = ($i == $tahun) ? 'selected' : '';
                echo "<option value='$i' $selected>$i</option>";
            endfor; ?>
        </select>
        <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 text-sm rounded whitespace-nowrap">
            <i class="fas fa-search mr-1"></i>Tampilkan
        </button>

        <div class="relative">
            <button type="button" id="exportDropdown" class="bg-green-500 hover:bg-green-600 text-white px-3 py-2 text-sm rounded flex items-center gap-1.5 whitespace-nowrap">
                <i class="fas fa-download"></i><span class="hidden sm:inline"> Ekspor Data</span><span class="sm:hidden">Ekspor</span> <i class="fas fa-chevron-down text-xs"></i>
            </button>
            <div id="exportMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-10 border">
                <a href="<?= APP_BASE ?>/action/download_pengguna.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&format=excel"
                   class="block px-4 py-2 text-gray-800 hover:bg-gray-100 rounded-t-md text-sm">
                    <i class="fas fa-file-excel text-green-600"></i> Ekspor ke Excel
                </a>
                <a href="<?= APP_BASE ?>/action/download_pengguna.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&format=csv"
                   class="block px-4 py-2 text-gray-800 hover:bg-gray-100 rounded-b-md text-sm">
                    <i class="fas fa-file-csv text-blue-600"></i> Ekspor ke CSV
                </a>
            </div>
        </div>
    </form>

    <!-- Tabs -->
    <div class="flex gap-1 mb-0 border-b border-gray-200 overflow-x-auto items-end">
        <button id="tab-tamu" onclick="switchTab('tamu')"
                class="px-4 py-2.5 text-sm font-semibold rounded-t-lg border-b-2 border-blue-600 text-blue-700 bg-blue-50 -mb-px whitespace-nowrap">
            <i class="fas fa-book-open mr-1.5"></i>Daftar Tamu
        </button>
        <button id="tab-pst" onclick="switchTab('pst')"
                class="px-4 py-2.5 text-sm font-semibold rounded-t-lg border-b-2 border-transparent text-gray-500 hover:text-gray-700 -mb-px whitespace-nowrap">
            <i class="fas fa-building mr-1.5"></i>Pengunjung PST
        </button>
        <button id="btn-refresh" onclick="refreshPage()"
                class="ml-auto px-3 py-2 text-sm font-semibold text-gray-400 hover:text-blue-600 hover:bg-blue-50 rounded-t-lg -mb-px flex items-center gap-1 whitespace-nowrap transition-colors">
            <i class="fas fa-rotate-right"></i><span class="hidden sm:inline ml-1">Refresh</span>
        </button>
    </div>

    <!-- ── Tab 1: Daftar Tamu ─────────────────────────────────────────────── -->
    <div id="section-tamu" class="pt-4">
    <p class="text-xs text-gray-400 mb-2 sm:hidden"><i class="fas fa-arrows-left-right mr-1"></i>Geser tabel ke kiri/kanan jika perlu</p>
    <div class="overflow-x-auto">
        <table id="tamuTable" class="w-full border-collapse border text-sm display" style="width:100%">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border p-2" style="width:40px"></th>
                    <th class="border p-2">Jenis</th>
                    <th class="border p-2">Tanggal</th>
                    <th class="border p-2">Nama</th>
                    <th class="border p-2">Jml. Pengunjung</th>
                    <th class="border p-2">Keperluan / PST</th>
                </tr>
                <tr>
                    <th></th>
                    <th><input type="text" placeholder="Cari Jenis" class="w-full p-1 border text-xs"></th>
                    <th><input type="text" placeholder="Cari Tanggal" class="w-full p-1 border text-xs"></th>
                    <th><input type="text" placeholder="Cari Nama" class="w-full p-1 border text-xs"></th>
                    <th></th>
                    <th><input type="text" placeholder="Cari Keperluan / PST" class="w-full p-1 border text-xs"></th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $resTamu->fetch_assoc()):
                $jenis        = $row['jenis'] ?? '';
                $isPstJenis   = in_array($jenis, ['whatsapp', 'surat']);
                $meta         = $jenisMeta[$jenis] ?? ['border' => '', 'badge' => 'bg-gray-100 text-gray-700 border-gray-200', 'icon' => '', 'label' => htmlspecialchars($jenis)];
                $jumlah       = $isPstJenis ? 1 : ($row['jumlah_orang'] ?? 1);
                $keperluan    = $isPstJenis ? 'Permintaan Data' : ($row['keperluan'] ?? '-');
                $isPST        = $isPstJenis || !empty($row['kunjungan_pst']);
            ?>
            <tr class="expandable-row <?= $meta['border'] ?>"
                data-id="<?= $row['id'] ?>"
                data-jenis="<?= htmlspecialchars($jenis) ?>"
                data-nama="<?= htmlspecialchars($row['nama'] ?? '') ?>"
                data-tanggal="<?= htmlspecialchars($row['tanggal'] ?? '') ?>"
                data-jk="<?= htmlspecialchars($row['jk'] ?? '') ?>"
                data-email="<?= htmlspecialchars($row['email'] ?? '') ?>"
                data-telepon="<?= htmlspecialchars($row['telepon'] ?? '') ?>"
                data-instansi="<?= htmlspecialchars($row['instansi'] ?? '') ?>"
                data-jumlah-orang="<?= htmlspecialchars($row['jumlah_orang'] ?? '') ?>"
                data-keperluan="<?= htmlspecialchars($row['keperluan'] ?? '') ?>"
                data-kunjungan-pst="<?= intval($row['kunjungan_pst'] ?? 0) ?>"
                data-pendidikan="<?= htmlspecialchars($row['pendidikan'] ?? '') ?>"
                data-kelompok-umur="<?= htmlspecialchars($row['kelompok_umur'] ?? '') ?>"
                data-pekerjaan="<?= htmlspecialchars($row['pekerjaan'] ?? '') ?>"
                data-pemanfaatan-data="<?= htmlspecialchars($row['pemanfaatan_data'] ?? '') ?>"
                data-data-dibutuhkan="<?= htmlspecialchars($row['data_dibutuhkan'] ?? '') ?>"
                data-link-surat="<?= htmlspecialchars($row['link_surat'] ?? '') ?>"
                data-link-surat-balasan="<?= htmlspecialchars($row['link_surat_balasan'] ?? '') ?>">
                <td class="border p-2 text-center expand-toggle" style="cursor:pointer;width:40px"><span class="expand-icon">▶</span></td>
                <td class="border p-2 text-center">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold border <?= $meta['badge'] ?>">
                        <?php if ($meta['icon']): ?><i class="<?= $meta['icon'] ?> text-[10px]"></i><?php endif; ?>
                        <?= $meta['label'] ?>
                    </span>
                </td>
                <td class="border p-2"><?= htmlspecialchars($row['tanggal']) ?></td>
                <td class="border p-2"><?= htmlspecialchars($row['nama']) ?></td>
                <td class="border p-2 text-center"><?= intval($jumlah) ?></td>
                <td class="border p-2">
                    <?php if ($isPST): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700 border border-indigo-200">
                            <i class="fas fa-building text-[10px]"></i> PST
                        </span>
                        <span class="hidden">PST</span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-700 border border-amber-200">
                            <i class="fas fa-file-lines text-[10px]"></i>
                            <?= htmlspecialchars($keperluan) ?>
                        </span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div><!-- /overflow-x-auto -->
    </div><!-- /section-tamu -->

    <!-- ── Tab 2: Pengunjung PST ──────────────────────────────────────────── -->
    <div id="section-pst" class="hidden pt-4">
    <p class="text-xs text-gray-400 mb-2 sm:hidden"><i class="fas fa-arrows-left-right mr-1"></i>Geser tabel ke kiri/kanan jika perlu</p>
    <div class="overflow-x-auto">
        <table id="pstTable" class="w-full border-collapse border text-sm display" style="width:100%">
            <thead>
                <tr class="bg-gray-200">
                    <th class="border p-2" style="width:40px"></th>
                    <th class="border p-2"><input type="checkbox" id="selectAllPST"><span class="ml-1">All</span></th>
                    <th class="border p-2">Jenis</th>
                    <th class="border p-2">Tanggal</th>
                    <th class="border p-2">Nama</th>
                    <th class="border p-2">Email</th>
                    <th class="border p-2 text-center">Penilaian</th>
                    <th class="border p-2 text-center">PES</th>
                </tr>
                <tr>
                    <th></th><th></th>
                    <th><input type="text" placeholder="Cari Jenis" class="w-full p-1 border text-xs"></th>
                    <th><input type="text" placeholder="Cari Tanggal" class="w-full p-1 border text-xs"></th>
                    <th><input type="text" placeholder="Cari Nama" class="w-full p-1 border text-xs"></th>
                    <th><input type="text" placeholder="Cari Email" class="w-full p-1 border text-xs"></th>
                    <th><input type="text" placeholder="Sudah / Belum" class="w-full p-1 border text-xs"></th>
                    <th><input type="text" placeholder="Sudah / Belum" class="w-full p-1 border text-xs"></th>
                </tr>
            </thead>
            <tbody>
            <?php while ($row = $resPST->fetch_assoc()):
                $jenis        = $row['jenis'] ?? '';
                $meta         = $jenisMeta[$jenis] ?? ['border' => '', 'badge' => 'bg-gray-100 text-gray-700 border-gray-200', 'icon' => '', 'label' => htmlspecialchars($jenis)];
                $hasPenilaian = !empty($row['penilaian_id']);
                $hasPes       = !empty($row['pes_id']);
            ?>
            <tr class="expandable-row <?= $meta['border'] ?>"
                data-id="<?= $row['id'] ?>"
                data-token="<?= htmlspecialchars($row['token'] ?? '') ?>"
                data-token-pes="<?= htmlspecialchars($row['token_pes'] ?? '') ?>"
                data-penilaian-id="<?= intval($row['penilaian_id'] ?? 0) ?>"
                data-pes-id="<?= intval($row['pes_id'] ?? 0) ?>"
                data-jenis="<?= htmlspecialchars($jenis) ?>"
                data-nama="<?= htmlspecialchars($row['nama'] ?? '') ?>"
                data-tanggal="<?= htmlspecialchars($row['tanggal'] ?? '') ?>"
                data-jk="<?= htmlspecialchars($row['jk'] ?? '') ?>"
                data-email="<?= htmlspecialchars($row['email'] ?? '') ?>"
                data-telepon="<?= htmlspecialchars($row['telepon'] ?? '') ?>"
                data-instansi="<?= htmlspecialchars($row['instansi'] ?? '') ?>"
                data-jumlah-orang="<?= htmlspecialchars($row['jumlah_orang'] ?? '') ?>"
                data-keperluan="<?= htmlspecialchars($row['keperluan'] ?? '') ?>"
                data-kunjungan-pst="<?= intval($row['kunjungan_pst'] ?? 0) ?>"
                data-pendidikan="<?= htmlspecialchars($row['pendidikan'] ?? '') ?>"
                data-kelompok-umur="<?= htmlspecialchars($row['kelompok_umur'] ?? '') ?>"
                data-pekerjaan="<?= htmlspecialchars($row['pekerjaan'] ?? '') ?>"
                data-pemanfaatan-data="<?= htmlspecialchars($row['pemanfaatan_data'] ?? '') ?>"
                data-data-dibutuhkan="<?= htmlspecialchars($row['data_dibutuhkan'] ?? '') ?>"
                data-link-surat="<?= htmlspecialchars($row['link_surat'] ?? '') ?>"
                data-link-surat-balasan="<?= htmlspecialchars($row['link_surat_balasan'] ?? '') ?>">
                <td class="border p-2 text-center expand-toggle" style="cursor:pointer;width:40px"><span class="expand-icon">▶</span></td>
                <td class="border p-2 text-center"><input type="checkbox" class="select-pst" data-id="<?= $row['id'] ?>"></td>
                <td class="border p-2 text-center">
                    <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-semibold border <?= $meta['badge'] ?>">
                        <?php if ($meta['icon']): ?><i class="<?= $meta['icon'] ?> text-[10px]"></i><?php endif; ?>
                        <?= $meta['label'] ?>
                    </span>
                </td>
                <td class="border p-2"><?= htmlspecialchars($row['tanggal']) ?></td>
                <td class="border p-2"><?= htmlspecialchars($row['nama']) ?></td>
                <td class="border p-2"><?= htmlspecialchars($row['email'] ?? '') ?></td>
                <td class="border p-2 text-center">
                    <?php if ($hasPenilaian): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 border border-green-200">
                            <i class="fas fa-check text-[10px]"></i> Sudah
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500 border border-gray-200">
                            <i class="fas fa-minus text-[10px]"></i> Belum
                        </span>
                    <?php endif; ?>
                    <span class="hidden"><?= $hasPenilaian ? 'Sudah' : 'Belum' ?></span>
                </td>
                <td class="border p-2 text-center">
                    <?php if ($hasPes): ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-teal-100 text-teal-800 border border-teal-200">
                            <i class="fas fa-check text-[10px]"></i> Sudah
                        </span>
                    <?php else: ?>
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-gray-100 text-gray-500 border border-gray-200">
                            <i class="fas fa-minus text-[10px]"></i> Belum
                        </span>
                    <?php endif; ?>
                    <span class="hidden"><?= $hasPes ? 'Sudah' : 'Belum' ?></span>
                </td>
            </tr>
            <?php endwhile; ?>
            </tbody>
        </table>
    </div><!-- /overflow-x-auto -->
        <div class="mt-4 flex justify-between items-center">
            <button id="deletePST" class="bg-red-500 text-white px-4 py-2 rounded text-sm">Hapus yang Dipilih</button>
            <span id="countPST" class="text-sm text-gray-600">0 Terpilih</span>
        </div>
    </div><!-- /section-pst -->
</div>

<!-- ── Edit Modal ───────────────────────────────────────────────────────── -->
<div id="editModal" class="fixed inset-0 hidden z-50 flex items-start justify-center bg-black/60 py-6 px-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[92vh] flex flex-col">
        <div class="flex justify-between items-center px-5 py-4 border-b shrink-0">
            <h3 class="font-bold text-gray-800 text-base"><i class="fas fa-edit text-blue-500 mr-2"></i>Edit Data Pengguna</h3>
            <button onclick="closeEditModal()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>
        <div class="overflow-y-auto p-5 text-sm space-y-4" id="editModalBody">

            <input type="hidden" id="edit-id">

            <!-- Data Dasar -->
            <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Data Dasar</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block mb-1 font-medium text-gray-700">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input id="edit-nama" type="text" class="w-full border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block mb-1 font-medium text-gray-700">Email</label>
                    <input id="edit-email" type="email" class="w-full border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block mb-1 font-medium text-gray-700">Nomor HP</label>
                    <input id="edit-telepon" type="text" class="w-full border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block mb-1 font-medium text-gray-700">Instansi / Organisasi</label>
                    <input id="edit-instansi" type="text" class="w-full border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block mb-1 font-medium text-gray-700">Jenis Kelamin</label>
                    <select id="edit-jk" class="w-full border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">— Pilih —</option>
                        <option value="L">Laki-laki</option>
                        <option value="P">Perempuan</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-1 font-medium text-gray-700">Tanggal Kunjungan <span class="text-red-500">*</span></label>
                    <input id="edit-tanggal" type="date" class="w-full border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
            </div>

            <!-- Surat (jenis=surat only) -->
            <div id="edit-section-surat" class="hidden space-y-3 border-t border-amber-100 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-amber-500">Data Surat</p>
                <div>
                    <label class="block mb-1 font-medium text-gray-700">Link Surat Masuk</label>
                    <input id="edit-link-surat" type="url" placeholder="https://drive.google.com/..."
                           class="w-full border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
                <div>
                    <label class="block mb-1 font-medium text-gray-700">Link Surat Balasan</label>
                    <input id="edit-link-surat-balasan" type="url" placeholder="https://drive.google.com/..."
                           class="w-full border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>
            </div>

            <!-- Kunjungan (umum/disabilitas only) -->
            <div id="edit-section-kunjungan" class="hidden space-y-3 border-t border-gray-100 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-gray-400">Kunjungan Langsung</p>
                <div>
                    <label class="block mb-1 font-medium text-gray-700">Jumlah Pengunjung</label>
                    <input id="edit-jumlah-orang" type="number" min="1" class="w-full border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block mb-1 font-medium text-gray-700">Keperluan Kunjungan</label>
                    <div class="flex gap-4 mb-2">
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="edit-keperluan-pst" id="edit-kep-ya" value="1" class="accent-blue-500">
                            <span>Permintaan Data (PST)</span>
                        </label>
                        <label class="flex items-center gap-1.5 cursor-pointer">
                            <input type="radio" name="edit-keperluan-pst" id="edit-kep-tidak" value="0" class="accent-blue-500">
                            <span>Lainnya</span>
                        </label>
                    </div>
                    <textarea id="edit-keperluan" rows="2" placeholder="Jelaskan keperluan kunjungan"
                              class="w-full border border-gray-300 rounded px-3 py-1.5 resize-none focus:outline-none focus:ring-2 focus:ring-blue-400 hidden"></textarea>
                </div>
            </div>

            <!-- PST Fields -->
            <div id="edit-section-pst" class="hidden space-y-3 border-t border-blue-100 pt-4">
                <p class="text-xs font-semibold uppercase tracking-wide text-blue-500">Data Kunjungan PST</p>

                <div>
                    <label class="block mb-1 font-medium text-gray-700">Pendidikan Tertinggi</label>
                    <select id="edit-pendidikan" class="w-full border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">— Pilih —</option>
                        <option>SLTA/Sederajat</option><option>D1/D2/D3</option>
                        <option>D4/S1</option><option>S2</option><option>S3</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-1 font-medium text-gray-700">Kelompok Umur</label>
                    <select id="edit-kelompok-umur" class="w-full border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">— Pilih —</option>
                        <option>di bawah 17 tahun</option><option>17 - 25 tahun</option>
                        <option>26 - 34 tahun</option><option>35 - 44 tahun</option>
                        <option>45 - 54 tahun</option><option>55 - 65 tahun</option>
                        <option>di atas 65 tahun</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-1 font-medium text-gray-700">Pekerjaan</label>
                    <select id="edit-pekerjaan-select" class="w-full border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">— Pilih —</option>
                        <option>Pelajar/Mahasiswa</option><option>Peneliti/Dosen</option>
                        <option>ASN/TNI/Polri</option><option>Pegawai BUMN/BUMD</option>
                        <option>Pegawai Swasta</option><option>Wiraswasta</option>
                        <option value="_lainnya">Lainnya…</option>
                    </select>
                    <input id="edit-pekerjaan-lainnya" type="text" placeholder="Sebutkan pekerjaan"
                           class="mt-1 w-full border border-gray-300 rounded px-3 py-1.5 hidden focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block mb-1 font-medium text-gray-700">Pemanfaatan Hasil Data</label>
                    <select id="edit-pemanfaatan" class="w-full border border-gray-300 rounded px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-blue-400">
                        <option value="">— Pilih —</option>
                        <option>Tugas Sekolah/Tugas Kuliah</option><option>Pemerintahan</option>
                        <option>Komersial</option><option>Penelitian</option><option>Lainnya</option>
                    </select>
                </div>
                <div>
                    <label class="block mb-1 font-medium text-gray-700">Data yang Dibutuhkan</label>
                    <div id="edit-data-container" class="space-y-2 mb-2"></div>
                    <button type="button" id="edit-btn-tambah"
                            class="text-blue-600 border border-blue-400 px-3 py-1 rounded text-xs hover:bg-blue-50">
                        + Tambah Data
                    </button>
                </div>
            </div>

        </div>
        <div class="px-5 py-4 border-t shrink-0 flex justify-end gap-3">
            <button onclick="closeEditModal()" class="px-4 py-2 text-sm rounded border border-gray-300 hover:bg-gray-100">Batal</button>
            <button onclick="submitEditModal()" id="edit-save-btn"
                    class="px-4 py-2 text-sm rounded bg-blue-600 hover:bg-blue-700 text-white font-semibold">
                <i class="fas fa-save mr-1"></i>Simpan
            </button>
        </div>
    </div>
</div>

<!-- ── QR Code Modal ─────────────────────────────────────────────────────── -->
<div id="qrModal" class="fixed inset-0 hidden z-[60] flex items-center justify-center bg-black/70 px-4">
    <div class="bg-white rounded-2xl shadow-2xl p-6 w-full max-w-xs text-center">
        <div id="qr-nama" class="font-semibold text-gray-800 text-sm mb-1 truncate"></div>
        <div id="qr-link" class="text-gray-400 text-xs mb-4 break-all"></div>
        <div id="qr-canvas" class="flex justify-center mb-4"></div>
        <div class="flex gap-2 justify-center">
            <button onclick="tutupQR()"
                class="text-sm bg-gray-200 hover:bg-gray-300 text-gray-700 font-semibold px-4 py-2 rounded-lg transition">
                Tutup</button>
            <button id="qr-salin-btn"
                class="text-sm bg-blue-600 hover:bg-blue-500 text-white font-semibold px-4 py-2 rounded-lg transition">
                Salin Link</button>
        </div>
    </div>
</div>

<!-- ── Penilaian Modal ──────────────────────────────────────────────────── -->
<div id="penilaianModal" class="fixed inset-0 hidden z-50 flex items-start justify-center bg-black/60 py-8 px-4">
    <div class="bg-white rounded-xl shadow-2xl w-full max-w-2xl max-h-[90vh] flex flex-col">
        <div class="flex justify-between items-center px-5 py-4 border-b">
            <h3 class="font-bold text-gray-800 text-base"><i class="fas fa-star text-yellow-400 mr-2"></i>Detail Penilaian</h3>
            <button onclick="closePenilaianModal()" class="text-gray-400 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>
        <div id="penilaianModalBody" class="overflow-y-auto p-5 text-sm"></div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
<script>
const APP_BASE = '<?= APP_BASE ?>';
const APP_URL  = '<?= APP_URL ?>';

var tableTamu, tablePST;
var cachedPenilaian = {};

var QUESTIONS = ['',
    'Informasi pelayanan pada unit layanan ini tersedia melalui media elektronik maupun non elektronik.',
    'Persyaratan pelayanan yang ditetapkan mudah dipenuhi/disiapkan oleh konsumen.',
    'Prosedur/alur pelayanan yang ditetapkan mudah diikuti/dilakukan.',
    'Jangka waktu penyelesaian pelayanan yang diterima sesuai dengan yang ditetapkan.',
    'Biaya pelayanan yang dibayarkan sesuai dengan biaya yang ditetapkan.',
    'Produk pelayanan yang diterima sesuai dengan yang dijanjikan.',
    'Sarana dan prasarana pendukung pelayanan memberikan kenyamanan.',
    'Data BPS mudah diakses.',
    'Petugas pelayanan dan/atau aplikasi pelayanan online merespon dengan baik.',
    'Petugas pelayanan dan/atau aplikasi pelayanan online mampu memberikan informasi yang jelas.',
    'Fasilitas pengaduan PST mudah diakses.',
    'Tidak ada diskriminasi dalam pelayanan.',
    'Tidak ada pelayanan di luar prosedur/kecurangan pelayanan.',
    'Tidak ada penerimaan gratifikasi.',
    'Tidak ada pungutan liar (pungli) dalam pelayanan.',
    'Tidak ada praktik percaloan dalam pelayanan.'
];

// ── Helpers ─────────────────────────────────────────────────────────────────
function escHtml(str) {
    return String(str || '')
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// ── QR Code ─────────────────────────────────────────────────────────────────
var _qrInstance = null;
function tampilkanQR(url, nama) {
    document.getElementById('qr-nama').textContent = nama;
    document.getElementById('qr-link').textContent = url;
    var canvas = document.getElementById('qr-canvas');
    canvas.innerHTML = '';
    _qrInstance = new QRCode(canvas, {
        text: url, width: 200, height: 200,
        colorDark: '#1e293b', colorLight: '#ffffff',
        correctLevel: QRCode.CorrectLevel.M
    });
    document.getElementById('qr-salin-btn').onclick = function() {
        navigator.clipboard.writeText(url).then(function() {
            var btn = document.getElementById('qr-salin-btn');
            btn.textContent = 'Tersalin!';
            setTimeout(function(){ btn.textContent = 'Salin Link'; }, 1500);
        });
    };
    document.getElementById('qrModal').classList.remove('hidden');
}
function tutupQR() {
    document.getElementById('qrModal').classList.add('hidden');
}
document.getElementById('qrModal').addEventListener('click', function(e) {
    if (e.target === this) tutupQR();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') tutupQR();
});

function field(label, val, extraClass) {
    return "<div" + (extraClass ? " class='" + extraClass + "'" : "") + ">" +
        "<label class='font-semibold text-gray-500 text-xs uppercase tracking-wide'>" + label + "</label>" +
        "<p class='mt-0.5'>" + escHtml(val) + "</p></div>";
}

function renderStars(val) {
    if (!val || val < 1) return '<span class="text-gray-400 text-xs">—</span>';
    var stars = '';
    for (var i = 1; i <= 10; i++) {
        stars += '<span style="color:' + (i <= val ? '#f59e0b' : '#d1d5db') + ';font-size:0.95em">★</span>';
    }
    return '<span>' + stars + '</span><span class="score-badge-inline score-' + val + '">' + val + '/10</span>';
}

function renderScoreCompact(val) {
    if (!val || val < 1) return '<span class="text-gray-400 text-xs">—</span>';
    var cls = val <= 2 ? 'text-red-600' : val <= 4 ? 'text-orange-500' : val <= 6 ? 'text-yellow-600' : val <= 8 ? 'text-green-600' : 'text-emerald-700';
    return '<span class="text-amber-400">★</span><span class="font-bold text-sm ' + cls + ' ml-0.5">' + val + '</span><span class="text-gray-400 text-xs">/10</span>';
}

function renderStarsMini(val) {
    if (!val || val < 1) return '<span class="text-gray-400">—</span>';
    var stars = '';
    for (var i = 1; i <= 10; i++) {
        stars += '<span style="color:' + (i <= val ? '#f59e0b' : '#d1d5db') + ';font-size:0.75em">★</span>';
    }
    return stars + '<span class="text-gray-700 font-semibold text-xs ml-0.5">' + val + '</span>';
}

function renderDataItems(raw, pDataItems) {
    if (!raw || raw === '-' || raw === '') return '<span class="text-gray-400 italic">—</span>';
    try {
        var items = JSON.parse(raw);
        if (!Array.isArray(items) || items.length === 0) return escHtml(raw);
        var hasPData = pDataItems && pDataItems.length > 0;

        var html = '<div class="mt-1 rounded border border-gray-200 overflow-hidden text-xs">' +
            '<table class="w-full border-collapse"><thead><tr class="bg-gray-100 text-gray-600">' +
            '<th class="px-3 py-1.5 text-left font-semibold w-6">#</th>' +
            '<th class="px-3 py-1.5 text-left font-semibold">Data yang Dibutuhkan</th>' +
            '<th class="px-3 py-1.5 text-center font-semibold whitespace-nowrap">Tahun</th>';
        if (hasPData) {
            html += '<th class="px-3 py-1.5 text-center font-semibold">Kepuasan</th>' +
                    '<th class="px-3 py-1.5 text-center font-semibold whitespace-nowrap">Status Perolehan</th>';
        }
        html += '</tr></thead><tbody>';
        items.forEach(function(item, i) {
            var bg    = i % 2 === 0 ? '' : 'style="background:#f9fafb"';
            var pItem = hasPData ? (pDataItems[i] || null) : null;
            html += '<tr ' + bg + '>' +
                '<td class="px-3 py-1.5 text-gray-400">' + (i + 1) + '</td>' +
                '<td class="px-3 py-1.5 font-medium">' + escHtml(item.data || '') + '</td>' +
                '<td class="px-3 py-1.5 text-center text-gray-500 whitespace-nowrap">' +
                    (item.tahun_dari || '—') + '–' + (item.tahun_sampai || '—') + '</td>';
            if (hasPData) {
                if (pItem) {
                    html += '<td class="px-3 py-1.5 text-center">' + renderStarsMini(parseInt(pItem.nilai) || 0) + '</td>' +
                            '<td class="px-3 py-1.5 text-center text-gray-600">' + escHtml(pItem.status_perolehan || '—') + '</td>';
                } else {
                    html += '<td class="px-3 py-1.5 text-center text-gray-400">—</td>' +
                            '<td class="px-3 py-1.5 text-center text-gray-400">—</td>';
                }
            }
            html += '</tr>';
        });
        return html + '</tbody></table></div>';
    } catch(e) { return escHtml(raw); }
}

// ── Build expand detail ──────────────────────────────────────────────────────
// isPST=false → Tab 1 simple detail; isPST=true → Tab 2 full detail
function buildExpandDetail(tr, pData, isPST) {
    var id       = tr.data('id');
    var nama     = tr.data('nama')     || '';
    var jk       = tr.data('jk')      || '-';
    var telepon  = tr.data('telepon')  || '-';
    var instansi = tr.data('instansi') || '-';
    var email    = tr.data('email')    || '-';

    var jenis = tr.data('jenis') || '';

    if (!isPST) {
        // Tab 1: minimal detail
        var linkSurat1   = tr.attr('data-link-surat') || '';
        var suratLinksHtml1 = '';
        if (jenis === 'surat') {
            suratLinksHtml1 = "<div class='flex flex-col gap-2 mt-3 pt-3 border-t border-gray-200'>" +
                "<div class='flex items-center justify-between gap-3 py-2.5 px-3 rounded-lg border bg-amber-50 border-amber-200'>" +
                  "<div class='flex items-center gap-2 min-w-0'>" +
                    "<i class='fas fa-envelope text-amber-600 flex-shrink-0'></i>" +
                    "<span class='font-semibold text-xs text-amber-800'>Surat Masuk</span>" +
                  "</div>" +
                  (linkSurat1
                    ? "<a href='" + escHtml(linkSurat1) + "' target='_blank' class='inline-flex items-center gap-1 px-2.5 py-1.5 rounded-md text-xs font-medium bg-amber-500 hover:bg-amber-600 text-white transition-colors'><i class='fas fa-external-link-alt'></i><span class='ml-1'>Buka</span></a>"
                    : "<span class='text-xs text-amber-600 italic'>belum diisi</span>") +
                "</div></div>";
        }
        return "<div class='bg-gray-50 border-t border-gray-200 p-3 sm:p-4 expand-detail text-sm' style='display:none;'>" +
            "<div class='grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-2'>" +
            field('Jenis Kelamin', jk) +
            field('Telepon', telepon) +
            field('Instansi / Organisasi', instansi) +
            field('Email', email) +
            "</div>" +
            suratLinksHtml1 +
            "<div class='mt-3 pt-3 border-t border-gray-200 flex gap-2 flex-wrap'>" +
            "<button class='bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded text-xs' " +
            "onclick='openEditModal($(\"tr.expandable-row[data-id=" + id + "]\"))'>" +
            "<i class='fas fa-edit mr-1'></i>Edit</button>" +
            "<button class='bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded text-xs' " +
            "onclick='deleteUser(" + id + ")'><i class='fas fa-trash-alt mr-1'></i>Hapus</button>" +
            "</div></div>";
    }

    // Tab 2: full detail (same fields for all jenis)
    var token        = tr.attr('data-token')     || '';
    var tokenPes     = tr.attr('data-token-pes') || '';
    var hasPenilaian = pData && pData.found;
    var jumlah       = tr.data('jumlah-orang') || '1';
    var keperluan    = tr.data('keperluan')    || '-';

    // Cek jendela revisi 24 jam dari submitted_at
    var canRevise = false;
    if (hasPenilaian && pData.penilaian && pData.penilaian.submitted_at) {
        var submittedAt = new Date(pData.penilaian.submitted_at.replace(' ', 'T'));
        var secsLeft = 86400 - Math.floor((Date.now() - submittedAt.getTime()) / 1000);
        canRevise = secsLeft > 0;
    }

    var nameEsc = escHtml(nama).replace(/'/g, '&#39;');

    // ── Row 1: link & action items ────────────────────────────────────────────
    var row1 = '';

    // Surat links (hanya jenis surat)
    if (jenis === 'surat') {
        var linkSurat   = tr.attr('data-link-surat') || '';
        var linkBalasan = tr.attr('data-link-surat-balasan') || '';
        if (linkSurat) {
            row1 += "<a href='" + escHtml(linkSurat) + "' target='_blank' " +
                    "class='inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-600 text-white px-3 py-1.5 rounded-md text-xs font-medium transition-colors'>" +
                    "<i class='fas fa-envelope'></i> Surat Masuk</a>";
        }
        if (linkBalasan) {
            row1 += "<a href='" + escHtml(linkBalasan) + "' target='_blank' " +
                    "class='inline-flex items-center gap-1.5 bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-md text-xs font-medium transition-colors'>" +
                    "<i class='fas fa-reply'></i> Surat Balasan</a>";
        }
    }

    // Survey item
    if (!hasPenilaian) {
        if (token) {
            var surveyUrl    = APP_URL + '/penilaian/?token=' + token;
            var surveyUrlEsc = escHtml(surveyUrl);
            row1 += "<span class='inline-flex rounded-md overflow-hidden text-xs border border-yellow-200 items-stretch'>" +
                      "<span class='bg-yellow-50 text-yellow-800 px-2.5 py-1.5 flex items-center gap-1.5 font-semibold whitespace-nowrap'>" +
                        "<i class='fas fa-star text-yellow-500 text-[10px]'></i>Survei Kepuasan" +
                      "</span>" +
                      "<button onclick='tampilkanQR(\"" + surveyUrlEsc + "\",\"" + nameEsc + "\")' " +
                        "class='bg-yellow-400 hover:bg-yellow-500 text-white px-2 py-1.5 border-l border-yellow-200 transition-colors' title='QR Code'>" +
                        "<i class='fas fa-qrcode text-[10px]'></i></button>" +
                      "<button onclick='salinLink(\"" + surveyUrlEsc + "\",this)' " +
                        "class='bg-yellow-400 hover:bg-yellow-500 text-white px-2 py-1.5 border-l border-yellow-200 transition-colors' title='Salin link'>" +
                        "<i class='fas fa-copy text-[10px]'></i></button>" +
                      "<a href='" + surveyUrlEsc + "' target='_blank' " +
                        "class='bg-yellow-400 hover:bg-yellow-500 text-white px-2 py-1.5 border-l border-yellow-200 transition-colors' title='Buka'>" +
                        "<i class='fas fa-external-link-alt text-[10px]'></i></a>" +
                    "</span>";
        } else {
            row1 += "<button id='survey-link-container-" + id + "' onclick='generateToken(" + id + ",this)' " +
                    "class='inline-flex items-center gap-1.5 bg-yellow-50 hover:bg-yellow-100 text-yellow-800 border border-yellow-200 px-3 py-1.5 rounded-md text-xs font-medium transition-colors'>" +
                    "<i class='fas fa-link text-yellow-500'></i> Buat Link Survei</button>";
        }
    } else if (canRevise && token) {
        var surveyUrlRevisi    = APP_URL + '/penilaian/?token=' + token;
        var surveyUrlRevisiEsc = escHtml(surveyUrlRevisi);
        row1 += "<span class='inline-flex rounded-md overflow-hidden text-xs border border-amber-200 items-stretch'>" +
                  "<span class='bg-amber-50 text-amber-800 px-2.5 py-1.5 flex items-center gap-1.5 font-semibold whitespace-nowrap'>" +
                    "<i class='fas fa-edit text-amber-500 text-[10px]'></i>Revisi Penilaian" +
                  "</span>" +
                  "<button onclick='tampilkanQR(\"" + surveyUrlRevisiEsc + "\",\"" + nameEsc + "\")' " +
                    "class='bg-amber-400 hover:bg-amber-500 text-white px-2 py-1.5 border-l border-amber-200 transition-colors' title='QR Code'>" +
                    "<i class='fas fa-qrcode text-[10px]'></i></button>" +
                  "<button onclick='salinLink(\"" + surveyUrlRevisiEsc + "\",this)' " +
                    "class='bg-amber-400 hover:bg-amber-500 text-white px-2 py-1.5 border-l border-amber-200 transition-colors' title='Salin link'>" +
                    "<i class='fas fa-copy text-[10px]'></i></button>" +
                  "<a href='" + surveyUrlRevisiEsc + "' target='_blank' " +
                    "class='bg-amber-400 hover:bg-amber-500 text-white px-2 py-1.5 border-l border-amber-200 transition-colors' title='Buka'>" +
                    "<i class='fas fa-external-link-alt text-[10px]'></i></a>" +
                "</span>";
    }

    var hasPes = parseInt(tr.attr('data-pes-id') || 0) > 0;

    // PES item
    if (!hasPes) {
        if (tokenPes) {
            var pesUrl    = APP_URL + '/pes/?token=' + tokenPes;
            var pesUrlEsc = escHtml(pesUrl);
            row1 += "<span class='inline-flex rounded-md overflow-hidden text-xs border border-teal-200 items-stretch'>" +
                      "<span class='bg-teal-50 text-teal-800 px-2.5 py-1.5 flex items-center gap-1.5 font-semibold whitespace-nowrap'>" +
                        "<i class='fas fa-clipboard-list text-teal-600 text-[10px]'></i>Form PES" +
                      "</span>" +
                      "<button onclick='tampilkanQR(\"" + pesUrlEsc + "\",\"" + nameEsc + "\")' " +
                        "class='bg-teal-500 hover:bg-teal-600 text-white px-2 py-1.5 border-l border-teal-200 transition-colors' title='QR Code'>" +
                        "<i class='fas fa-qrcode text-[10px]'></i></button>" +
                      "<button onclick='salinLink(\"" + pesUrlEsc + "\",this)' " +
                        "class='bg-teal-500 hover:bg-teal-600 text-white px-2 py-1.5 border-l border-teal-200 transition-colors' title='Salin link'>" +
                        "<i class='fas fa-copy text-[10px]'></i></button>" +
                      "<a href='" + pesUrlEsc + "' target='_blank' " +
                        "class='bg-teal-500 hover:bg-teal-600 text-white px-2 py-1.5 border-l border-teal-200 transition-colors' title='Buka'>" +
                        "<i class='fas fa-external-link-alt text-[10px]'></i></a>" +
                    "</span>";
        } else {
            row1 += "<button id='pes-link-container-" + id + "' onclick='generateTokenPes(" + id + ",this)' " +
                    "class='inline-flex items-center gap-1.5 bg-teal-50 hover:bg-teal-100 text-teal-700 border border-teal-200 px-3 py-1.5 rounded-md text-xs font-medium transition-colors'>" +
                    "<i class='fas fa-link text-teal-500'></i> Buat Link PES</button>";
        }
    }

    // ── Row 2: admin buttons ──────────────────────────────────────────────────
    var row2 = "<button class='inline-flex items-center gap-1.5 bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-md text-xs font-medium transition-colors' " +
        "onclick='openEditModal($(\"tr.expandable-row[data-id=" + id + "]\"))'>" +
        "<i class='fas fa-edit'></i>Edit</button>" +
        "<button class='inline-flex items-center gap-1.5 bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-md text-xs font-medium transition-colors' onclick='deleteUser(" + id + ")'>" +
        "<i class='fas fa-trash-alt'></i>Hapus</button>";

    if (hasPenilaian) {
        row2 += "<button onclick='openPenilaianModal(" + id + ")' " +
                "class='inline-flex items-center gap-1.5 bg-indigo-500 hover:bg-indigo-600 text-white px-3 py-1.5 rounded-md text-xs font-medium transition-colors'>" +
                "<i class='fas fa-star'></i> Lihat Penilaian</button>";
    }
    if (hasPes && token) {
        row2 += "<a href='" + APP_BASE + "/cs/detail_pengunjung.php?token=" + encodeURIComponent(token) + "#section-pes' target='_blank' " +
                "class='inline-flex items-center gap-1.5 bg-teal-500 hover:bg-teal-600 text-white px-3 py-1.5 rounded-md text-xs font-medium transition-colors'>" +
                "<i class='fas fa-clipboard-check'></i> Lihat PES</a>";
    }
    row2 += token
        ? "<a href='" + APP_BASE + "/cs/detail_pengunjung.php?token=" + encodeURIComponent(token) + "' target='_blank' " +
          "class='inline-flex items-center gap-1.5 bg-slate-500 hover:bg-slate-600 text-white px-3 py-1.5 rounded-md text-xs font-medium transition-colors'>" +
          "<i class='fas fa-file-lines'></i> Detail Lengkap</a>"
        : "<span class='inline-flex items-center gap-1.5 bg-slate-300 text-slate-500 px-3 py-1.5 rounded-md text-xs font-medium cursor-not-allowed' title='Token belum dibuat'>" +
          "<i class='fas fa-file-lines'></i> Detail Lengkap</span>";

    return "<div class='bg-gray-50 border-t border-gray-200 p-3 sm:p-4 expand-detail text-sm' style='display:none;'>" +
        "<div class='grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-x-6 gap-y-2 mb-3'>" +
        field('Jenis Kelamin', jk) + field('Telepon', telepon) +
        field('Instansi / Organisasi', instansi) + field('Email', email) +
        field('Jumlah Pengunjung', jumlah) +
        field('Keperluan', keperluan, 'sm:col-span-2 md:col-span-3') +
        "</div>" +
        "<div class='flex flex-wrap items-center gap-2 mt-3 pt-3 border-t border-gray-200'>" + row1 + "</div>" +
        "<div class='flex flex-wrap gap-2 mt-2 pt-2 border-t border-gray-100'>" + row2 + "</div>" +
        "</div>";
}

// ── Expand row ───────────────────────────────────────────────────────────────
function expandRow(tr, tableInst, isPST) {
    var row  = tableInst.row(tr);
    var icon = tr.find('.expand-icon');

    if (row.child.isShown()) {
        row.child().find('.expand-detail').fadeOut(200, function() {
            row.child.hide(); icon.text('▶');
        });
        return;
    }

    icon.text('▼');
    var id           = tr.data('id');
    var penilaianId  = tr.data('penilaian-id');
    var hasPenilaian = isPST && penilaianId && penilaianId > 0;

    if (hasPenilaian && !cachedPenilaian[id]) {
        row.child(
            "<div class='bg-gray-50 p-4 expand-detail text-center text-gray-400 text-sm'>" +
            "<i class='fas fa-spinner fa-spin mr-1'></i>Memuat data penilaian...</div>"
        ).show();
        $.getJSON(APP_BASE + '/action/get_penilaian.php?antrian_id=' + id)
            .done(function(pData) {
                cachedPenilaian[id] = pData;
                var html = buildExpandDetail(tr, pData, isPST);
                row.child(html).show();
                row.child().find('.expand-detail').fadeIn(250);
            })
            .fail(function() {
                var html = buildExpandDetail(tr, null, isPST);
                row.child(html).show();
                row.child().find('.expand-detail').fadeIn(250);
            });
    } else {
        var pData = hasPenilaian ? (cachedPenilaian[id] || null) : null;
        var html  = buildExpandDetail(tr, pData, isPST);
        row.child(html).show();
        row.child().find('.expand-detail').fadeIn(250);
    }
}

// ── Penilaian modal ──────────────────────────────────────────────────────────
function openPenilaianModal(antrianId) {
    var modal = document.getElementById('penilaianModal');
    var body  = document.getElementById('penilaianModalBody');
    modal.classList.remove('hidden');
    body.innerHTML = '<div class="text-center text-gray-400 py-10"><i class="fas fa-spinner fa-spin text-2xl"></i></div>';

    if (cachedPenilaian[antrianId] && cachedPenilaian[antrianId].found) {
        renderPenilaianModal(cachedPenilaian[antrianId]);
    } else {
        $.getJSON(APP_BASE + '/action/get_penilaian.php?antrian_id=' + antrianId)
            .done(function(data) { cachedPenilaian[antrianId] = data; renderPenilaianModal(data); })
            .fail(function() { body.innerHTML = '<p class="text-red-500 text-center py-4">Gagal memuat data penilaian.</p>'; });
    }
}

function closePenilaianModal() {
    document.getElementById('penilaianModal').classList.add('hidden');
}
document.getElementById('penilaianModal').addEventListener('click', function(e) {
    if (e.target === this) closePenilaianModal();
});

function renderPenilaianModal(pData) {
    var body = document.getElementById('penilaianModalBody');
    if (!pData || !pData.found) {
        body.innerHTML = '<p class="text-gray-500 text-center py-4">Data penilaian tidak ditemukan.</p>';
        return;
    }
    var p = pData.penilaian, items = pData.data_items || [];

    var html = '<p class="text-gray-400 text-xs mb-3">Tanggal: <strong>' + escHtml(p.tanggal) + '</strong>';
    if (p.submitted_at) html += ' &nbsp;·&nbsp; Submit: <strong>' + escHtml(p.submitted_at) + '</strong>';
    html += '</p>';

    // Kualitas pelayanan — compact list
    html += '<div class="mb-4">' +
        '<h4 class="font-bold text-gray-600 text-xs uppercase tracking-wide mb-2">Kualitas Pelayanan</h4>' +
        '<div class="rounded-lg border border-gray-200 overflow-hidden divide-y divide-gray-100">';
    for (var q = 1; q <= 16; q++) {
        var val = parseInt(p['q' + q]) || 0;
        html += '<div class="flex items-center gap-2 px-3 py-2 ' + (q % 2 === 0 ? 'bg-gray-50' : 'bg-white') + '">' +
            '<span class="flex-shrink-0 w-5 h-5 rounded-full bg-blue-100 text-blue-600 text-xs font-bold flex items-center justify-center">' + q + '</span>' +
            '<p class="flex-1 text-xs text-gray-600 leading-snug">' + escHtml(QUESTIONS[q]) + '</p>' +
            '<span class="flex-shrink-0 pl-2 whitespace-nowrap">' + renderScoreCompact(val) + '</span>' +
            '</div>';
    }
    html += '</div></div>';

    // Kebutuhan data — compact
    if (items.length > 0) {
        html += '<div class="mb-4">' +
            '<h4 class="font-bold text-gray-600 text-xs uppercase tracking-wide mb-2">Kebutuhan Data</h4>' +
            '<div class="rounded-lg border border-gray-200 overflow-hidden divide-y divide-gray-100">';
        items.forEach(function(item, i) {
            var val = parseInt(item.nilai) || 0;
            html += '<div class="px-3 py-2 ' + (i % 2 === 0 ? 'bg-white' : 'bg-gray-50') + '">' +
                '<div class="flex items-start justify-between gap-2">' +
                  '<div class="flex items-start gap-1.5 flex-1 min-w-0">' +
                    '<span class="flex-shrink-0 w-5 h-5 rounded-full bg-emerald-100 text-emerald-700 text-xs font-bold flex items-center justify-center mt-0.5">' + (i+1) + '</span>' +
                    '<div class="min-w-0">' +
                      '<p class="text-xs font-semibold text-gray-800 leading-snug">' + escHtml(item.nama_data || '') + '</p>' +
                      '<p class="text-xs text-gray-400">' + (item.tahun_dari||'—') + '–' + (item.tahun_sampai||'—') + '</p>' +
                    '</div>' +
                  '</div>' +
                  '<span class="flex-shrink-0 pl-2 whitespace-nowrap">' + renderScoreCompact(val) + '</span>' +
                '</div>' +
                '<div class="mt-1 pl-7 flex flex-wrap gap-x-4 text-xs text-gray-500">' +
                  '<span>Perolehan: <span class="font-medium text-gray-700">' + escHtml(item.status_perolehan||'—') + '</span></span>' +
                  '<span>Perencanaan: <span class="font-medium text-gray-700">' + escHtml(item.untuk_perencanaan||'—') + '</span></span>' +
                '</div>' +
                '</div>';
        });
        html += '</div></div>';
    }

    if (p.catatan && p.catatan.trim()) {
        html += '<div>' +
            '<h4 class="font-bold text-gray-600 text-xs uppercase tracking-wide mb-2">Catatan &amp; Saran</h4>' +
            '<div class="bg-purple-50 border border-purple-100 rounded-lg px-3 py-2 text-xs text-gray-700 leading-relaxed">' + escHtml(p.catatan) + '</div>' +
            '</div>';
    }
    body.innerHTML = html;
}

// ── Tab switching ────────────────────────────────────────────────────────────
function switchTab(tab) {
    var isTamu = tab === 'tamu';
    document.getElementById('section-tamu').classList.toggle('hidden', !isTamu);
    document.getElementById('section-pst').classList.toggle('hidden', isTamu);

    var btnTamu = document.getElementById('tab-tamu');
    var btnPST  = document.getElementById('tab-pst');
    var activeClass = function(color) {
        return 'px-5 py-2.5 text-sm font-semibold rounded-t-lg border-b-2 border-' + color + '-600 text-' + color + '-700 bg-' + color + '-50 -mb-px whitespace-nowrap';
    };
    var inactiveClass = 'px-5 py-2.5 text-sm font-semibold rounded-t-lg border-b-2 border-transparent text-gray-500 hover:text-gray-700 -mb-px whitespace-nowrap';

    btnTamu.className = isTamu  ? activeClass('blue') : inactiveClass;
    btnPST.className  = !isTamu ? activeClass('indigo') : inactiveClass;

    if (isTamu) tableTamu.columns.adjust().draw(false);
    else        tablePST.columns.adjust().draw(false);

    history.replaceState(null, '', location.pathname + location.search + '#' + tab);
}

function refreshPage() {
    var icon = document.querySelector('#btn-refresh i');
    if (icon) icon.style.animation = 'spin 0.6s linear infinite';
    location.reload();
}

// ── Init ─────────────────────────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', function() {
    var exportDropdown = document.getElementById('exportDropdown');
    var exportMenu     = document.getElementById('exportMenu');
    exportDropdown.addEventListener('click', function(e) { e.stopPropagation(); exportMenu.classList.toggle('hidden'); });
    document.addEventListener('click', function() { exportMenu.classList.add('hidden'); });
});

$(document).ready(function() {
    // Tab 1: 6 cols (expand, jenis, tanggal, nama, jumlah, keperluan/pst)
    tableTamu = $('#tamuTable').DataTable({
        pageLength: 25,
        order: [[2, 'desc']],
        autoWidth: false,
        columnDefs: [{ orderable: false, targets: [0] }]
    });

    // Tab 2: 8 cols (expand, checkbox, jenis, tanggal, nama, email, penilaian, pes)
    tablePST = $('#pstTable').DataTable({
        pageLength: 25,
        order: [[3, 'desc']],
        autoWidth: false,
        columnDefs: [{ orderable: false, targets: [0, 1, 6, 7] }]
    });

    function bindFilters(tableInst) {
        $(tableInst.table().node()).find('thead tr:eq(1) th').each(function(i) {
            var input = $(this).find('input');
            if (input.length) {
                input.on('keyup change clear', function() {
                    if (tableInst.column(i).search() !== this.value)
                        tableInst.column(i).search(this.value).draw();
                });
            }
        });
    }
    bindFilters(tableTamu);
    bindFilters(tablePST);

    // Restore active tab from URL hash
    var hashTab = location.hash.replace('#', '');
    if (hashTab === 'pst') switchTab('pst');

    // Expand toggle (shared for both tables)
    $(document).on('click', '.expand-toggle', function() {
        var tr      = $(this).closest('tr.expandable-row');
        var tableId = tr.closest('table').attr('id');
        var isPST   = tableId === 'pstTable';
        expandRow(tr, isPST ? tablePST : tableTamu, isPST);
    });

    // Checkboxes — PST
    $(document).on('change', '.select-pst', function() {
        $('#countPST').text($('.select-pst:checked').length + ' Terpilih');
    });
    $('#selectAllPST').on('change', function() {
        $('.select-pst').prop('checked', $(this).is(':checked')).trigger('change');
    });
    $('#deletePST').on('click', function() {
        var ids = [];
        $('.select-pst:checked').each(function() { ids.push($(this).data('id')); });
        if (!ids.length) { alert('Pilih pengguna yang ingin dihapus'); return; }
        if (confirm('Hapus ' + ids.length + ' data terpilih?'))
            ajaxDelete(APP_BASE + '/action/delete_selected_pengguna.php', { ids: ids });
    });
});

// ── Actions ──────────────────────────────────────────────────────────────────
function ajaxDelete(url, data) {
    $.ajax({ url: url, method: 'POST', data: data,
        success: function() { alert('Data berhasil dihapus'); location.reload(); },
        error:   function() { alert('Gagal menghapus data'); }
    });
}

function deleteUser(id) {
    if (confirm('Hapus data ini?'))
        ajaxDelete(APP_BASE + '/action/delete_pengguna.php', { id: id });
}

function copySurveyLink(url) {
    salinLink(url, null);
}

function salinLink(url, btn) {
    var orig = btn ? btn.innerHTML : null;
    function feedback() {
        if (btn) { btn.innerHTML = '<i class="fas fa-check"></i><span class="hidden sm:inline ml-1">Tersalin</span>'; }
        setTimeout(function() { if (btn) btn.innerHTML = orig; }, 1500);
    }
    if (navigator.clipboard) {
        navigator.clipboard.writeText(url).then(feedback);
    } else {
        var el = document.createElement('textarea');
        el.value = url; document.body.appendChild(el); el.select();
        document.execCommand('copy'); document.body.removeChild(el);
        feedback();
    }
}

// ── Edit Modal ───────────────────────────────────────────────────────────────
var PREDEF_JOBS = ['Pelajar/Mahasiswa','Peneliti/Dosen','ASN/TNI/Polri',
                   'Pegawai BUMN/BUMD','Pegawai Swasta','Wiraswasta'];

function createEditRecord(data, dari, sampai) {
    var div = document.createElement('div');
    div.className = 'edit-record-row border border-gray-200 rounded p-3 bg-gray-50 relative text-xs';
    div.innerHTML =
        '<button type="button" onclick="removeEditRecord(this)" ' +
        'class="absolute top-1.5 right-2 text-gray-400 hover:text-red-500 text-base leading-none">&times;</button>' +
        '<div class="mb-1.5"><label class="block text-gray-500 mb-0.5">Data</label>' +
        '<input type="text" class="edit-data-nama w-full border border-gray-300 rounded px-2 py-1" ' +
        'placeholder="Contoh: Jumlah penduduk" value="' + escHtml(data || '') + '"></div>' +
        '<div class="flex gap-2">' +
        '<div class="flex-1"><label class="block text-gray-500 mb-0.5">Tahun dari</label>' +
        '<input type="number" class="edit-tahun-dari w-full border border-gray-300 rounded px-2 py-1" ' +
        'min="1900" max="2100" placeholder="2020" value="' + escHtml(dari || '') + '"></div>' +
        '<div class="flex-1"><label class="block text-gray-500 mb-0.5">Tahun sampai</label>' +
        '<input type="number" class="edit-tahun-sampai w-full border border-gray-300 rounded px-2 py-1" ' +
        'min="1900" max="2100" placeholder="2024" value="' + escHtml(sampai || '') + '"></div>' +
        '</div>';
    return div;
}

function removeEditRecord(btn) {
    var c = document.getElementById('edit-data-container');
    if (c.children.length > 1) btn.closest('.edit-record-row').remove();
    else alert('Minimal harus ada 1 data yang dibutuhkan.');
}

function openEditModal(tr) {
    var jenis    = tr.data('jenis') || '';
    var isWa     = jenis === 'whatsapp' || jenis === 'surat';
    var isPST    = isWa || parseInt(tr.data('kunjungan-pst') || 0) === 1;
    var isLangsung = jenis === 'umum' || jenis === 'disabilitas';

    // Isi field dasar
    document.getElementById('edit-id').value      = tr.data('id');
    document.getElementById('edit-nama').value    = tr.data('nama')    || '';
    document.getElementById('edit-email').value   = tr.data('email')   || '';
    document.getElementById('edit-telepon').value = tr.data('telepon') || '';
    document.getElementById('edit-instansi').value= tr.data('instansi')|| '';
    document.getElementById('edit-jk').value      = tr.data('jk')      || '';
    document.getElementById('edit-tanggal').value = tr.data('tanggal') || '';

    // Section surat
    var sSurat = document.getElementById('edit-section-surat');
    sSurat.classList.toggle('hidden', jenis !== 'surat');
    if (jenis === 'surat') {
        document.getElementById('edit-link-surat').value         = tr.data('link-surat')         || '';
        document.getElementById('edit-link-surat-balasan').value = tr.attr('data-link-surat-balasan') || '';
    }

    // Section kunjungan (umum/disabilitas)
    var sKunjungan = document.getElementById('edit-section-kunjungan');
    sKunjungan.classList.toggle('hidden', !isLangsung);
    if (isLangsung) {
        document.getElementById('edit-jumlah-orang').value = tr.data('jumlah-orang') || '1';
        var kpVal = parseInt(tr.data('kunjungan-pst') || 0);
        document.getElementById('edit-kep-ya').checked    = kpVal === 1;
        document.getElementById('edit-kep-tidak').checked = kpVal === 0;
        var keperluan = tr.data('keperluan') || '';
        document.getElementById('edit-keperluan').value = keperluan;
        document.getElementById('edit-keperluan').classList.toggle('hidden', kpVal !== 0);
    }

    // Section PST
    var sPST = document.getElementById('edit-section-pst');
    sPST.classList.toggle('hidden', !isPST);
    if (isPST) {
        document.getElementById('edit-pendidikan').value     = tr.data('pendidikan')     || '';
        document.getElementById('edit-kelompok-umur').value  = tr.data('kelompok-umur')  || '';
        document.getElementById('edit-pemanfaatan').value    = tr.data('pemanfaatan-data')|| '';

        // Pekerjaan
        var pekerjaan = tr.data('pekerjaan') || '';
        var selPek = document.getElementById('edit-pekerjaan-select');
        var inpPek = document.getElementById('edit-pekerjaan-lainnya');
        if (PREDEF_JOBS.indexOf(pekerjaan) !== -1) {
            selPek.value = pekerjaan;
            inpPek.value = '';
            inpPek.classList.add('hidden');
        } else {
            selPek.value = '_lainnya';
            inpPek.value = pekerjaan;
            inpPek.classList.remove('hidden');
        }

        // Data yang dibutuhkan
        var container = document.getElementById('edit-data-container');
        container.innerHTML = '';
        var rawData = tr.attr('data-data-dibutuhkan') || '';
        try {
            var items = rawData ? JSON.parse(rawData) : [];
            if (!Array.isArray(items) || items.length === 0) {
                container.appendChild(createEditRecord());
            } else {
                items.forEach(function(it) {
                    container.appendChild(createEditRecord(it.data, it.tahun_dari, it.tahun_sampai));
                });
            }
        } catch(e) { container.appendChild(createEditRecord()); }
    }

    document.getElementById('editModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeEditModal() {
    document.getElementById('editModal').classList.add('hidden');
    document.body.style.overflow = '';
}

function submitEditModal() {
    var id    = document.getElementById('edit-id').value;
    var nama  = document.getElementById('edit-nama').value.trim();
    var tanggal = document.getElementById('edit-tanggal').value;
    if (!nama)    { alert('Nama wajib diisi.');    document.getElementById('edit-nama').focus();    return; }
    if (!tanggal) { alert('Tanggal wajib diisi.'); document.getElementById('edit-tanggal').focus(); return; }

    var kpRadio = document.querySelector('input[name="edit-keperluan-pst"]:checked');
    var kunjunganPst = 0;
    var kunjunganSection = document.getElementById('edit-section-kunjungan');
    if (!kunjunganSection.classList.contains('hidden')) {
        if (!kpRadio) { alert('Pilih keperluan kunjungan.'); return; }
        kunjunganPst = parseInt(kpRadio.value);
    }

    // PST fields
    var dataDibutuhkan = null;
    var pstSection = document.getElementById('edit-section-pst');
    if (!pstSection.classList.contains('hidden')) {
        var selPek = document.getElementById('edit-pekerjaan-select');
        var inpPek = document.getElementById('edit-pekerjaan-lainnya');
        var pekerjaan = selPek.value === '_lainnya' ? inpPek.value.trim() : selPek.value;
        if (!pekerjaan) { alert('Pekerjaan wajib diisi.'); selPek.focus(); return; }

        var records = [];
        document.querySelectorAll('#edit-data-container .edit-record-row').forEach(function(row) {
            var nm = row.querySelector('.edit-data-nama').value.trim();
            if (!nm) return;
            var td = parseInt(row.querySelector('.edit-tahun-dari').value)  || 0;
            var ts = parseInt(row.querySelector('.edit-tahun-sampai').value) || 0;
            records.push({ data: nm, tahun_dari: td, tahun_sampai: ts });
        });
        dataDibutuhkan = JSON.stringify(records);
    } else {
        var selPek2  = document.getElementById('edit-pekerjaan-select');
        var inpPek2  = document.getElementById('edit-pekerjaan-lainnya');
        var pekerjaan = '';
    }

    var btn = document.getElementById('edit-save-btn');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Menyimpan…';

    var fd = {
        id:             id,
        nama:           nama,
        email:          document.getElementById('edit-email').value.trim(),
        telepon:        document.getElementById('edit-telepon').value.trim(),
        instansi:       document.getElementById('edit-instansi').value.trim(),
        jk:             document.getElementById('edit-jk').value,
        tanggal:        tanggal,
        jumlah_orang:   document.getElementById('edit-jumlah-orang').value || '',
        keperluan:      kunjunganPst === 0 ? document.getElementById('edit-keperluan').value.trim() : '',
        kunjungan_pst:  kunjunganPst,
        pendidikan:     document.getElementById('edit-pendidikan').value,
        kelompok_umur:  document.getElementById('edit-kelompok-umur').value,
        pekerjaan:      (function(){
                            var s = document.getElementById('edit-pekerjaan-select');
                            var i = document.getElementById('edit-pekerjaan-lainnya');
                            return s.value === '_lainnya' ? i.value.trim() : s.value;
                        })(),
        pemanfaatan_data: document.getElementById('edit-pemanfaatan').value,
        data_dibutuhkan:  dataDibutuhkan || '',
        link_surat:         document.getElementById('edit-link-surat').value.trim(),
        link_surat_balasan: document.getElementById('edit-link-surat-balasan').value.trim(),
    };

    $.ajax({
        url: APP_BASE + '/action/update_pengguna.php',
        method: 'POST', data: fd, dataType: 'json',
        success: function(res) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save mr-1"></i>Simpan';
            if (res.success) {
                closeEditModal();
                location.reload();
            } else {
                alert('Gagal: ' + (res.message || 'Error tidak diketahui'));
            }
        },
        error: function() {
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save mr-1"></i>Simpan';
            alert('Gagal menghubungi server.');
        }
    });
}

// Close edit modal on backdrop click
document.getElementById('editModal').addEventListener('click', function(e) {
    if (e.target === this) closeEditModal();
});
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeEditModal();
});

// Toggle pekerjaan lainnya
document.getElementById('edit-pekerjaan-select').addEventListener('change', function() {
    var inp = document.getElementById('edit-pekerjaan-lainnya');
    inp.classList.toggle('hidden', this.value !== '_lainnya');
    if (this.value === '_lainnya') inp.focus();
});

// Toggle keperluan textarea
document.querySelectorAll('input[name="edit-keperluan-pst"]').forEach(function(r) {
    r.addEventListener('change', function() {
        var kep = document.getElementById('edit-keperluan');
        kep.classList.toggle('hidden', this.value !== '0');
        // Update PST section visibility
        var sPST = document.getElementById('edit-section-pst');
        sPST.classList.toggle('hidden', this.value !== '1');
    });
});

// Tambah data row
document.getElementById('edit-btn-tambah').addEventListener('click', function() {
    document.getElementById('edit-data-container').appendChild(createEditRecord());
});

function generateToken(id, btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span class="ml-1">Membuat...</span>';
    $.ajax({
        url: APP_BASE + '/action/generate_token.php',
        method: 'POST', data: { id: id }, dataType: 'json',
        success: function(res) {
            if (res.success) {
                var tr = $('tr[data-id="' + id + '"]');
                tr.attr('data-token', res.token);
                var surveyUrl = APP_URL + '/penilaian/?token=' + res.token;
                var nama = tr.data('nama') || '';
                var nameEsc = escHtml(nama).replace(/'/g, '&#39;');
                var surveyUrlEsc = escHtml(surveyUrl);
                $('#survey-link-container-' + id).replaceWith(
                    "<span class='inline-flex rounded-md overflow-hidden text-xs border border-yellow-200 items-stretch'>" +
                      "<span class='bg-yellow-50 text-yellow-800 px-2.5 py-1.5 flex items-center gap-1.5 font-semibold whitespace-nowrap'>" +
                        "<i class='fas fa-star text-yellow-500 text-[10px]'></i>Survei Kepuasan" +
                      "</span>" +
                      "<button onclick='tampilkanQR(\"" + surveyUrlEsc + "\",\"" + nameEsc + "\")' " +
                        "class='bg-yellow-400 hover:bg-yellow-500 text-white px-2 py-1.5 border-l border-yellow-200 transition-colors' title='QR Code'>" +
                        "<i class='fas fa-qrcode text-[10px]'></i></button>" +
                      "<button onclick='salinLink(\"" + surveyUrlEsc + "\",this)' " +
                        "class='bg-yellow-400 hover:bg-yellow-500 text-white px-2 py-1.5 border-l border-yellow-200 transition-colors' title='Salin link'>" +
                        "<i class='fas fa-copy text-[10px]'></i></button>" +
                      "<a href='" + surveyUrlEsc + "' target='_blank' " +
                        "class='bg-yellow-400 hover:bg-yellow-500 text-white px-2 py-1.5 border-l border-yellow-200 transition-colors' title='Buka'>" +
                        "<i class='fas fa-external-link-alt text-[10px]'></i></a>" +
                    "</span>"
                );
            } else {
                alert('Gagal membuat link survei.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-link"></i><span class="ml-1">Buat Link</span>';
            }
        },
        error: function() {
            alert('Gagal membuat link survei.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-link"></i><span class="ml-1">Buat Link</span>';
        }
    });
}

function generateTokenPes(id, btn) {
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i><span class="ml-1">Membuat...</span>';
    $.ajax({
        url: APP_BASE + '/action/generate_token_pes.php',
        method: 'POST', data: { id: id }, dataType: 'json',
        success: function(res) {
            if (res.success) {
                var tr = $('tr[data-id="' + id + '"]');
                tr.attr('data-token-pes', res.token);
                var pesUrl = APP_URL + '/pes/?token=' + res.token;
                var nama = tr.data('nama') || '';
                var nameEsc = escHtml(nama).replace(/'/g, '&#39;');
                var pesUrlEsc = escHtml(pesUrl);
                $('#pes-link-container-' + id).replaceWith(
                    "<span class='inline-flex rounded-md overflow-hidden text-xs border border-teal-200 items-stretch'>" +
                      "<span class='bg-teal-50 text-teal-800 px-2.5 py-1.5 flex items-center gap-1.5 font-semibold whitespace-nowrap'>" +
                        "<i class='fas fa-clipboard-list text-teal-600 text-[10px]'></i>Form PES" +
                      "</span>" +
                      "<button onclick='tampilkanQR(\"" + pesUrlEsc + "\",\"" + nameEsc + "\")' " +
                        "class='bg-teal-500 hover:bg-teal-600 text-white px-2 py-1.5 border-l border-teal-200 transition-colors' title='QR Code'>" +
                        "<i class='fas fa-qrcode text-[10px]'></i></button>" +
                      "<button onclick='salinLink(\"" + pesUrlEsc + "\",this)' " +
                        "class='bg-teal-500 hover:bg-teal-600 text-white px-2 py-1.5 border-l border-teal-200 transition-colors' title='Salin link'>" +
                        "<i class='fas fa-copy text-[10px]'></i></button>" +
                      "<a href='" + pesUrlEsc + "' target='_blank' " +
                        "class='bg-teal-500 hover:bg-teal-600 text-white px-2 py-1.5 border-l border-teal-200 transition-colors' title='Buka'>" +
                        "<i class='fas fa-external-link-alt text-[10px]'></i></a>" +
                    "</span>"
                );
            } else {
                alert('Gagal membuat link PES.');
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-link"></i><span class="ml-1">Buat Link</span>';
            }
        },
        error: function() {
            alert('Gagal membuat link PES.');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-link"></i><span class="ml-1">Buat Link</span>';
        }
    });
}
</script>
</body>
</html>
