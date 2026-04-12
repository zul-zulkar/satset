<?php
include '../db.php';


$tanggal = date('Y-m-d');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jenis = $_POST['jenis'];
    $aksi = $_POST['aksi'];

    if ($aksi === 'next') {
        // Panggil antrean berikutnya
        $result = $mysqli->query("SELECT * FROM antrian WHERE tanggal = '$tanggal' AND jenis = '$jenis' AND status = 'menunggu' ORDER BY id ASC LIMIT 1");
        if ($row = $result->fetch_assoc()) {
            $mysqli->query("UPDATE antrian SET status = 'dipanggil' WHERE id = " . $row['id']);
        }
    } elseif ($aksi === 'undo') {
        // Kembalikan antrean terakhir ke status 'menunggu'
        $last = $mysqli->query("SELECT * FROM antrian WHERE tanggal = '$tanggal' AND jenis = '$jenis' AND status = 'dipanggil' ORDER BY id DESC LIMIT 1");
        if ($row = $last->fetch_assoc()) {
            $mysqli->query("UPDATE antrian SET status = 'menunggu' WHERE id = " . $row['id']);
        }
    }
}

$terakhir_disabilitas = $mysqli->query("SELECT * FROM antrian WHERE tanggal = '$tanggal' AND jenis = 'disabilitas' AND status = 'dipanggil' ORDER BY id DESC LIMIT 1")->fetch_assoc();
$terakhir_umum = $mysqli->query("SELECT * FROM antrian WHERE tanggal = '$tanggal' AND jenis = 'umum' AND status = 'dipanggil' ORDER BY id DESC LIMIT 1")->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Halaman CS - Antrian</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-8">

    <h1 class="text-2xl font-bold mb-6 text-center">Halaman Petugas CS</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Kolom Disabilitas -->
        <div class="bg-white p-6 rounded shadow">
            <h2 class="text-xl font-semibold text-blue-600 mb-4 text-center">Antrean Disabilitas</h2>
            <div class="text-5xl font-bold text-blue-800 mb-4 text-center">
                <?= $terakhir_disabilitas ? '#' . $terakhir_disabilitas['nomor'] : '-' ?>
            </div>
            <form method="post" class="flex justify-center gap-4">
                <input type="hidden" name="jenis" value="disabilitas">
                <button type="submit" name="aksi" value="next" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                    Panggil Berikutnya
                </button>
                <button type="submit" name="aksi" value="undo" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                    Kembali Sebelumnya
                </button>
            </form>
        </div>

        <!-- Kolom Umum -->
        <div class="bg-white p-6 rounded shadow">
            <h2 class="text-xl font-semibold text-green-600 mb-4 text-center">Antrean Umum</h2>
            <div class="text-5xl font-bold text-green-800 mb-4 text-center">
                <?= $terakhir_umum ? '#' . $terakhir_umum['nomor'] : '-' ?>
            </div>
            <form method="post" class="flex justify-center gap-4">
                <input type="hidden" name="jenis" value="umum">
                <button type="submit" name="aksi" value="next" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Panggil Berikutnya
                </button>
                <button type="submit" name="aksi" value="undo" class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                    Kembali Sebelumnya
                </button>
            </form>
        </div>
    </div>
    <?php
$panggilan = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($row)) {
    // Ambil ulang antrean terakhir yang sudah dipanggil setelah update
    if ($jenis === 'disabilitas') {
        $terakhir = $mysqli->query("SELECT * FROM antrian WHERE tanggal = '$tanggal' AND jenis = 'disabilitas' AND status = 'dipanggil' ORDER BY id DESC LIMIT 1")->fetch_assoc();
    } else {
        $terakhir = $mysqli->query("SELECT * FROM antrian WHERE tanggal = '$tanggal' AND jenis = 'umum' AND status = 'dipanggil' ORDER BY id DESC LIMIT 1")->fetch_assoc();
    }

    $panggilan = [
        'jenis' => $jenis,
        'nomor' => $terakhir ? $terakhir['nomor'] : null,
        'aksi' => $aksi
    ];
}

?>


<!-- ── TABEL PENGUNJUNG HARI INI ── -->
<div class="mt-8 bg-white rounded shadow overflow-hidden">
    <div class="flex flex-wrap items-center justify-between gap-3 px-4 py-3 border-b bg-gray-50">
        <h2 class="font-bold text-gray-700 text-base">Pengunjung Hari Ini</h2>
        <div class="flex items-center gap-3 text-sm">
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-yellow-100 text-yellow-800 font-semibold">
                <span id="badge-menunggu">–</span> Menunggu
            </span>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-green-100 text-green-800 font-semibold">
                <span id="badge-dipanggil">–</span> Dipanggil
            </span>
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full bg-blue-100 text-blue-800 font-semibold">
                <span id="badge-total">–</span> Total
            </span>
            <span id="refresh-indicator" class="text-gray-400 text-xs hidden">↻ memperbarui…</span>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="w-full text-sm border-collapse">
            <thead>
                <tr class="bg-gray-100 text-gray-600 uppercase text-xs tracking-wide">
                    <th class="px-3 py-2 text-center border-b w-8">#</th>
                    <th class="px-3 py-2 text-center border-b">Jenis</th>
                    <th class="px-3 py-2 text-center border-b">No.</th>
                    <th class="px-3 py-2 text-left border-b">Nama</th>
                    <th class="px-3 py-2 text-left border-b">Telepon</th>
                    <th class="px-3 py-2 text-left border-b">Instansi</th>
                    <th class="px-3 py-2 text-center border-b">Metode</th>
                    <th class="px-3 py-2 text-center border-b">Status</th>
                </tr>
            </thead>
            <tbody id="tabel-pengunjung-body">
                <tr><td colspan="8" class="text-center text-gray-400 py-6">Memuat data…</td></tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    const STATUS_LABEL = {
        menunggu:  { label: 'Menunggu',  cls: 'bg-yellow-100 text-yellow-800' },
        dipanggil: { label: 'Dipanggil', cls: 'bg-green-100 text-green-800'  },
    };
    const JENIS_CLS = {
        disabilitas: 'bg-blue-100 text-blue-700',
        umum:        'bg-emerald-100 text-emerald-700',
        whatsapp:    'bg-purple-100 text-purple-700',
    };

    function escHtml(str) {
        return (str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
    }

    function renderTabel(data) {
        const tbody = document.getElementById('tabel-pengunjung-body');
        if (!data.length) {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-gray-400 py-6">Belum ada pengunjung hari ini.</td></tr>';
            document.getElementById('badge-menunggu').textContent  = 0;
            document.getElementById('badge-dipanggil').textContent = 0;
            document.getElementById('badge-total').textContent     = 0;
            return;
        }

        let menunggu = 0, dipanggil = 0;
        let html = '';
        data.forEach((row, i) => {
            const st   = STATUS_LABEL[row.status] || { label: row.status, cls: 'bg-gray-100 text-gray-700' };
            const jcls = JENIS_CLS[row.jenis]     || 'bg-gray-100 text-gray-700';
            const trCls = row.status === 'dipanggil' ? 'bg-green-50' : '';
            if (row.status === 'menunggu')  menunggu++;
            if (row.status === 'dipanggil') dipanggil++;
            html += `
            <tr class="${trCls} border-b hover:bg-gray-50 transition-colors">
                <td class="px-3 py-2 text-center text-gray-400">${i + 1}</td>
                <td class="px-3 py-2 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold ${jcls}">${escHtml(row.jenis)}</span>
                </td>
                <td class="px-3 py-2 text-center font-bold">${escHtml(row.nomor)}</td>
                <td class="px-3 py-2">${escHtml(row.nama)}</td>
                <td class="px-3 py-2 text-gray-500">${escHtml(row.telepon)}</td>
                <td class="px-3 py-2 text-gray-500">${escHtml(row.instansi)}</td>
                <td class="px-3 py-2 text-center text-xs text-gray-500">${escHtml(row.metode)}</td>
                <td class="px-3 py-2 text-center">
                    <span class="px-2 py-0.5 rounded-full text-xs font-semibold ${st.cls}">${st.label}</span>
                </td>
            </tr>`;
        });

        tbody.innerHTML = html;
        document.getElementById('badge-menunggu').textContent  = menunggu;
        document.getElementById('badge-dipanggil').textContent = dipanggil;
        document.getElementById('badge-total').textContent     = data.length;
    }

    const APP_BASE = '<?= APP_BASE ?>';

    function muatPengunjung() {
        const ind = document.getElementById('refresh-indicator');
        ind.classList.remove('hidden');
        fetch(APP_BASE + '/cs/pengunjung_hari_ini.php', {cache: 'no-store'})
            .then(r => r.json())
            .then(data => renderTabel(data))
            .catch(() => {})
            .finally(() => ind.classList.add('hidden'));
    }

    document.addEventListener('DOMContentLoaded', muatPengunjung);
    setInterval(muatPengunjung, 5000);
</script>

</body>
</html>
