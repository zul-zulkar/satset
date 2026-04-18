<?php
/**
 * Form Buku Tamu WhatsApp — dipakai oleh whatsapp.php
 * Fields: nama, email, telepon, jk, pendidikan, kelompok_umur,
 *         pekerjaan, instansi, pemanfaatan_data, data_dibutuhkan (JSON)
 */
function renderFormWhatsapp($judul) {
    include '../db.php';

    $tampilkanForm = true;
    $tanggal       = date('Y-m-d');
    $errorMsg      = '';
    $old           = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $old = $_POST;

        $nama           = trim($_POST['nama']           ?? '');
        $email          = trim($_POST['email']          ?? '');
        $telepon        = trim($_POST['telepon']        ?? '');
        $jk             = $_POST['jk']                 ?? '';
        $pendidikan     = $_POST['pendidikan']          ?? '';
        $kelompok_umur  = $_POST['kelompok_umur']       ?? '';
        $pekerjaan      = trim($_POST['pekerjaan']      ?? '');
        $instansi       = trim($_POST['instansi']       ?? '');
        $pemanfaatan    = $_POST['pemanfaatan_data']    ?? '';
        $dataNama       = $_POST['data_nama']           ?? [];
        $tahunDari      = $_POST['tahun_dari']          ?? [];
        $tahunSampai    = $_POST['tahun_sampai']        ?? [];

        $validJk          = ['L', 'P'];
        $validPendidikan  = ['SLTA/Sederajat', 'D1/D2/D3', 'D4/S1', 'S2', 'S3'];
        $validUmur        = ['di bawah 17 tahun', '17 - 25 tahun', '26 - 34 tahun', '35 - 44 tahun', '45 - 54 tahun', '55 - 65 tahun', 'di atas 65 tahun'];
        $validPemanfaatan = ['Tugas Sekolah/Tugas Kuliah', 'Pemerintahan', 'Komersial', 'Penelitian', 'Lainnya'];

        if (!preg_match("/^[a-zA-Z'\s]+$/u", $nama)) {
            $errorMsg = "Nama hanya boleh berisi huruf, spasi, dan tanda petik satu (').";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Format email tidak valid.';
        } elseif (!preg_match('/^(\+62|0)\d{6,13}$/', $telepon)) {
            $errorMsg = 'Nomor telepon tidak valid. Awali dengan 08, 0362 (kantor), atau +62.';
        } elseif (!in_array($jk, $validJk)) {
            $errorMsg = 'Pilih jenis kelamin.';
        } elseif (!in_array($pendidikan, $validPendidikan)) {
            $errorMsg = 'Pilih pendidikan tertinggi.';
        } elseif (!in_array($kelompok_umur, $validUmur)) {
            $errorMsg = 'Pilih kelompok umur.';
        } elseif (empty($pekerjaan)) {
            $errorMsg = 'Pekerjaan wajib diisi.';
        } elseif (empty($instansi)) {
            $errorMsg = 'Instansi / organisasi wajib diisi.';
        } elseif (!in_array($pemanfaatan, $validPemanfaatan)) {
            $errorMsg = 'Pilih pemanfaatan hasil data.';
        } elseif (empty($dataNama) || count(array_filter(array_map('trim', $dataNama))) === 0) {
            $errorMsg = 'Tambahkan minimal 1 data yang dibutuhkan.';
        } else {
            // Bangun JSON data_dibutuhkan
            $records = [];
            for ($i = 0; $i < count($dataNama); $i++) {
                $dn = trim($dataNama[$i] ?? '');
                if ($dn === '') continue;
                $td = intval($tahunDari[$i]   ?? 0);
                $ts = intval($tahunSampai[$i] ?? 0);
                $records[] = ['data' => $dn, 'tahun_dari' => $td, 'tahun_sampai' => $ts];
            }
            $dataJson = json_encode($records, JSON_UNESCAPED_UNICODE);
            $token    = bin2hex(random_bytes(16));

            // Generate nomor antrean (nomor NOT NULL di DB)
            $stmtN = $mysqli->prepare("SELECT COALESCE(MAX(nomor), 0) AS maxn FROM antrian WHERE tanggal = ? AND jenis = 'whatsapp'");
            $stmtN->bind_param("s", $tanggal);
            $stmtN->execute();
            $nomor_baru = (int)$stmtN->get_result()->fetch_assoc()['maxn'] + 1;
            $stmtN->close();

            $stmt = $mysqli->prepare(
                "INSERT INTO antrian
                    (nama, email, telepon, jk, pendidikan, kelompok_umur,
                     pekerjaan, instansi, pemanfaatan_data, data_dibutuhkan,
                     kunjungan_pst, jenis, nomor, tanggal, token)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'whatsapp', ?, ?, ?)"
            );
            $stmt->bind_param(
                "ssssssssssiss",
                $nama, $email, $telepon,
                $jk, $pendidikan, $kelompok_umur,
                $pekerjaan, $instansi,
                $pemanfaatan, $dataJson,
                $nomor_baru, $tanggal, $token
            );
            if ($stmt->execute()) {
                $tampilkanForm = false;
            } else {
                $errorMsg = 'Gagal menyimpan data. Silakan coba lagi.';
            }
            $stmt->close();
        }
    }

    // Siapkan nilai lama untuk pekerjaan
    $oldPekerjaanPilihan    = $old['pekerjaan_pilihan']     ?? '';
    $oldPekerjaanLainnyaTxt = $old['pekerjaan_lainnya_text'] ?? '';
    $oldDataNama            = $old['data_nama']              ?? [];
    $oldTahunDari           = $old['tahun_dari']             ?? [];
    $oldTahunSampai         = $old['tahun_sampai']           ?? [];
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title><?= htmlspecialchars($judul) ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            /* custom radio style */
            .radio-label { display:flex; align-items:center; gap:0.5rem; cursor:pointer; padding:0.25rem 0; }
            .radio-label input[type=radio] { accent-color:#16a34a; width:1rem; height:1rem; flex-shrink:0; }
        </style>
    </head>
    <body class="bg-gray-100 min-h-screen py-8 px-4">
    <div class="w-full max-w-xl mx-auto bg-white p-6 sm:p-8 rounded-lg shadow">
        <h1 class="text-xl sm:text-2xl font-bold mb-6 text-center leading-tight"><?= htmlspecialchars($judul) ?></h1>

        <?php if ($tampilkanForm): ?>

            <?php if (!empty($errorMsg)): ?>
                <div class="bg-red-50 border border-red-400 text-red-700 px-4 py-3 rounded mb-5 text-sm">
                    <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-6 text-sm" id="formWa" autocomplete="off" novalidate>

                <!-- 1. Nama -->
                <div>
                    <label class="block mb-1 font-semibold">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input name="nama" required placeholder="Nama lengkap Anda"
                           value="<?= htmlspecialchars($old['nama'] ?? '') ?>"
                           class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-1 focus:ring-green-500">
                </div>

                <!-- 2. Email -->
                <div>
                    <label class="block mb-1 font-semibold">Email <span class="text-red-500">*</span></label>
                    <input name="email" type="email" required placeholder="contoh@email.com"
                           value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                           class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-1 focus:ring-green-500">
                </div>

                <!-- 3. Nomor HP -->
                <div>
                    <label class="block mb-1 font-semibold">Nomor HP <span class="text-red-500">*</span></label>
                    <input name="telepon" required placeholder="08xx / 0362xxxxxx"
                           value="<?= htmlspecialchars($old['telepon'] ?? '') ?>"
                           class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-1 focus:ring-green-500">
                </div>

                <!-- 4. Jenis Kelamin -->
                <div>
                    <label class="block mb-2 font-semibold">Jenis Kelamin <span class="text-red-500">*</span></label>
                    <div class="flex gap-6">
                        <?php foreach (['L' => 'Laki-laki', 'P' => 'Perempuan'] as $val => $label): ?>
                            <label class="radio-label">
                                <input type="radio" name="jk" value="<?= $val ?>"
                                       <?= ($old['jk'] ?? '') === $val ? 'checked' : '' ?>>
                                <?= $label ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 5. Pendidikan -->
                <div>
                    <label class="block mb-2 font-semibold">Pendidikan Tertinggi <span class="text-red-500">*</span></label>
                    <div>
                        <?php foreach (['SLTA/Sederajat', 'D1/D2/D3', 'D4/S1', 'S2', 'S3'] as $p): ?>
                            <label class="radio-label">
                                <input type="radio" name="pendidikan" value="<?= $p ?>"
                                       <?= ($old['pendidikan'] ?? '') === $p ? 'checked' : '' ?>>
                                <?= $p ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 6. Umur -->
                <div>
                    <label class="block mb-2 font-semibold">Umur <span class="text-red-500">*</span></label>
                    <div>
                        <?php foreach ([
                            'di bawah 17 tahun', '17 - 25 tahun', '26 - 34 tahun',
                            '35 - 44 tahun', '45 - 54 tahun', '55 - 65 tahun', 'di atas 65 tahun'
                        ] as $u): ?>
                            <label class="radio-label">
                                <input type="radio" name="kelompok_umur" value="<?= $u ?>"
                                       <?= ($old['kelompok_umur'] ?? '') === $u ? 'checked' : '' ?>>
                                <?= $u ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 7. Pekerjaan -->
                <div>
                    <label class="block mb-2 font-semibold">Pekerjaan <span class="text-red-500">*</span></label>
                    <div>
                        <?php
                        $pekerjaanOptions = [
                            'Pelajar/Mahasiswa', 'Peneliti/Dosen', 'ASN/TNI/Polri',
                            'Pegawai BUMN/BUMD', 'Pegawai Swasta', 'Wiraswasta', 'Lainnya'
                        ];
                        foreach ($pekerjaanOptions as $pj):
                        ?>
                            <label class="radio-label">
                                <input type="radio" name="pekerjaan_pilihan" value="<?= $pj ?>"
                                       <?= $oldPekerjaanPilihan === $pj ? 'checked' : '' ?>
                                       <?= $pj === 'Lainnya' ? 'id="pekerjaan_lainnya_radio"' : '' ?>>
                                <?= $pj ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <input type="text" id="pekerjaan_lainnya_input" name="pekerjaan_lainnya_text"
                           placeholder="Sebutkan pekerjaan Anda"
                           value="<?= htmlspecialchars($oldPekerjaanLainnyaTxt) ?>"
                           class="mt-2 w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-1 focus:ring-green-500 <?= $oldPekerjaanPilihan === 'Lainnya' ? '' : 'hidden' ?>">
                    <input type="hidden" name="pekerjaan" id="pekerjaan_hidden"
                           value="<?= htmlspecialchars($old['pekerjaan'] ?? '') ?>">
                </div>

                <!-- 8. Instansi -->
                <div>
                    <label class="block mb-1 font-semibold">Asal Instansi / Organisasi <span class="text-red-500">*</span></label>
                    <input name="instansi" required placeholder="Nama instansi atau organisasi Anda"
                           value="<?= htmlspecialchars($old['instansi'] ?? '') ?>"
                           class="w-full border border-gray-300 p-2 rounded focus:outline-none focus:ring-1 focus:ring-green-500">
                </div>

                <!-- 9. Pemanfaatan Hasil -->
                <div>
                    <label class="block mb-2 font-semibold">Pemanfaatan Hasil <span class="text-red-500">*</span></label>
                    <div>
                        <?php foreach ([
                            'Tugas Sekolah/Tugas Kuliah', 'Pemerintahan',
                            'Komersial', 'Penelitian', 'Lainnya'
                        ] as $pf): ?>
                            <label class="radio-label">
                                <input type="radio" name="pemanfaatan_data" value="<?= $pf ?>"
                                       <?= ($old['pemanfaatan_data'] ?? '') === $pf ? 'checked' : '' ?>>
                                <?= $pf ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 10. Data yang Dibutuhkan -->
                <div>
                    <label class="block mb-1 font-semibold">Data yang Dibutuhkan <span class="text-red-500">*</span></label>
                    <p class="text-gray-500 text-xs mb-3">Tambahkan satu atau lebih data yang Anda butuhkan beserta rentang tahunnya.</p>
                    <div id="data-container" class="space-y-3"></div>
                    <button type="button" id="btn-tambah-data"
                            class="mt-3 text-green-700 border border-green-600 px-4 py-1.5 rounded text-sm hover:bg-green-50 transition-colors">
                        + Tambah Data
                    </button>
                </div>

                <button type="submit"
                        class="bg-green-600 hover:bg-green-700 text-white w-full py-2.5 rounded font-semibold transition-colors">
                    Kirim Data
                </button>
            </form>

            <script>
            // =========================================================
            // Pekerjaan — toggle "Lainnya" input
            // =========================================================
            const pekerjaanRadios  = document.querySelectorAll('input[name="pekerjaan_pilihan"]');
            const lainnyaRadio     = document.getElementById('pekerjaan_lainnya_radio');
            const lainnyaInput     = document.getElementById('pekerjaan_lainnya_input');
            const pekerjaanHidden  = document.getElementById('pekerjaan_hidden');

            pekerjaanRadios.forEach(function(r) {
                r.addEventListener('change', function() {
                    if (lainnyaRadio.checked) {
                        lainnyaInput.classList.remove('hidden');
                        lainnyaInput.focus();
                    } else {
                        lainnyaInput.classList.add('hidden');
                        lainnyaInput.value = '';
                    }
                });
            });

            // =========================================================
            // Data yang Dibutuhkan — dynamic records
            // =========================================================
            const container = document.getElementById('data-container');

            function escHtml(str) {
                return String(str)
                    .replace(/&/g, '&amp;')
                    .replace(/</g, '&lt;')
                    .replace(/>/g, '&gt;')
                    .replace(/"/g, '&quot;');
            }

            function createRecord(data, dari, sampai) {
                data   = data   || '';
                dari   = dari   || '';
                sampai = sampai || '';

                var div = document.createElement('div');
                div.className = 'border border-gray-200 rounded-lg p-4 bg-gray-50 relative';
                div.innerHTML =
                    '<button type="button" onclick="removeRecord(this)" ' +
                    '        class="absolute top-2 right-3 text-gray-400 hover:text-red-500 text-xl leading-none" ' +
                    '        title="Hapus baris ini">&times;</button>' +

                    '<div class="mb-2">' +
                    '  <label class="block text-xs text-gray-600 mb-1 font-medium">Data yang dibutuhkan</label>' +
                    '  <input type="text" name="data_nama[]" required ' +
                    '         placeholder="Contoh: Data jumlah penduduk" ' +
                    '         value="' + escHtml(data) + '" ' +
                    '         class="w-full border border-gray-300 p-2 rounded text-sm focus:outline-none focus:ring-1 focus:ring-green-500">' +
                    '</div>' +

                    '<div class="flex gap-3">' +
                    '  <div class="flex-1">' +
                    '    <label class="block text-xs text-gray-600 mb-1 font-medium">Tahun dari</label>' +
                    '    <input type="number" name="tahun_dari[]" min="1900" max="2100" ' +
                    '           placeholder="2020" value="' + escHtml(dari) + '" ' +
                    '           class="w-full border border-gray-300 p-2 rounded text-sm focus:outline-none focus:ring-1 focus:ring-green-500">' +
                    '  </div>' +
                    '  <div class="flex-1">' +
                    '    <label class="block text-xs text-gray-600 mb-1 font-medium">Tahun sampai</label>' +
                    '    <input type="number" name="tahun_sampai[]" min="1900" max="2100" ' +
                    '           placeholder="2024" value="' + escHtml(sampai) + '" ' +
                    '           class="w-full border border-gray-300 p-2 rounded text-sm focus:outline-none focus:ring-1 focus:ring-green-500">' +
                    '  </div>' +
                    '</div>';
                return div;
            }

            function removeRecord(btn) {
                if (container.children.length > 1) {
                    btn.closest('.border').remove();
                } else {
                    alert('Minimal harus ada 1 data yang dibutuhkan.');
                }
            }

            document.getElementById('btn-tambah-data').addEventListener('click', function() {
                container.appendChild(createRecord());
            });

            // Restore records (initial or after error)
            <?php
            if (empty($oldDataNama)) {
                echo "container.appendChild(createRecord());";
            } else {
                for ($i = 0; $i < count($oldDataNama); $i++):
                    $dn = json_encode($oldDataNama[$i] ?? '');
                    $td = json_encode($oldTahunDari[$i]   ?? '');
                    $ts = json_encode($oldTahunSampai[$i] ?? '');
                    echo "container.appendChild(createRecord($dn, $td, $ts));";
                endfor;
            }
            ?>

            // =========================================================
            // Form Validation
            // =========================================================
            document.getElementById('formWa').addEventListener('submit', function(e) {

                // Nama
                var nama = document.querySelector('input[name="nama"]').value.trim();
                if (!nama) {
                    alert('Nama lengkap wajib diisi.');
                    e.preventDefault(); return;
                }
                if (!/^[a-zA-Z'\s]+$/.test(nama)) {
                    alert("Nama hanya boleh berisi huruf, spasi, dan tanda petik satu (').");
                    e.preventDefault(); return;
                }

                // Email
                var email = document.querySelector('input[name="email"]').value.trim();
                if (!email) {
                    alert('Email wajib diisi.');
                    e.preventDefault(); return;
                }
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                    alert('Format email tidak valid.');
                    e.preventDefault(); return;
                }

                // Telepon
                var telepon = document.querySelector('input[name="telepon"]').value.trim();
                if (!/^(\+62|0)\d{6,13}$/.test(telepon)) {
                    alert('Nomor telepon tidak valid. Awali dengan 08, 0362 (kantor), atau +62.');
                    e.preventDefault(); return;
                }

                // Jenis Kelamin
                if (!document.querySelector('input[name="jk"]:checked')) {
                    alert('Pilih jenis kelamin.');
                    e.preventDefault(); return;
                }

                // Pendidikan
                if (!document.querySelector('input[name="pendidikan"]:checked')) {
                    alert('Pilih pendidikan tertinggi.');
                    e.preventDefault(); return;
                }

                // Umur
                if (!document.querySelector('input[name="kelompok_umur"]:checked')) {
                    alert('Pilih kelompok umur.');
                    e.preventDefault(); return;
                }

                // Pekerjaan
                var pekerjaanPilihan = document.querySelector('input[name="pekerjaan_pilihan"]:checked');
                if (!pekerjaanPilihan) {
                    alert('Pilih pekerjaan.');
                    e.preventDefault(); return;
                }
                if (pekerjaanPilihan.value === 'Lainnya') {
                    var lainnyaVal = lainnyaInput.value.trim();
                    if (!lainnyaVal) {
                        alert('Sebutkan pekerjaan Anda pada kolom "Lainnya".');
                        lainnyaInput.focus();
                        e.preventDefault(); return;
                    }
                    pekerjaanHidden.value = lainnyaVal;
                } else {
                    pekerjaanHidden.value = pekerjaanPilihan.value;
                }

                // Instansi
                var instansi = document.querySelector('input[name="instansi"]').value.trim();
                if (!instansi) {
                    alert('Asal instansi / organisasi wajib diisi.');
                    e.preventDefault(); return;
                }

                // Pemanfaatan Hasil
                if (!document.querySelector('input[name="pemanfaatan_data"]:checked')) {
                    alert('Pilih pemanfaatan hasil data.');
                    e.preventDefault(); return;
                }

                // Data yang Dibutuhkan
                var dataNamas = document.querySelectorAll('input[name="data_nama[]"]');
                if (dataNamas.length === 0) {
                    alert('Tambahkan minimal 1 data yang dibutuhkan.');
                    e.preventDefault(); return;
                }
                var hasData = false;
                for (var i = 0; i < dataNamas.length; i++) {
                    if (dataNamas[i].value.trim() !== '') { hasData = true; break; }
                }
                if (!hasData) {
                    alert('Isi nama data pada minimal 1 baris data yang dibutuhkan.');
                    e.preventDefault(); return;
                }

                var tahunDariArr   = document.querySelectorAll('input[name="tahun_dari[]"]');
                var tahunSampaiArr = document.querySelectorAll('input[name="tahun_sampai[]"]');
                for (var j = 0; j < dataNamas.length; j++) {
                    if (!dataNamas[j].value.trim()) continue;

                    var td = parseInt(tahunDariArr[j].value);
                    var ts = parseInt(tahunSampaiArr[j].value);

                    if (isNaN(td) || td < 1900 || td > 2100) {
                        alert('Tahun dari tidak valid pada data ke-' + (j + 1) + '. Masukkan tahun antara 1900–2100.');
                        tahunDariArr[j].focus();
                        e.preventDefault(); return;
                    }
                    if (isNaN(ts) || ts < 1900 || ts > 2100) {
                        alert('Tahun sampai tidak valid pada data ke-' + (j + 1) + '. Masukkan tahun antara 1900–2100.');
                        tahunSampaiArr[j].focus();
                        e.preventDefault(); return;
                    }
                    if (ts < td) {
                        alert('Tahun sampai tidak boleh lebih kecil dari tahun dari pada data ke-' + (j + 1) + '.');
                        tahunSampaiArr[j].focus();
                        e.preventDefault(); return;
                    }
                }
            });
            </script>

        <?php else: ?>
            <div class="mt-6 p-8 bg-green-50 border border-green-400 rounded-lg text-center">
                <p class="text-4xl font-bold text-green-700 mb-2">&#10003;</p>
                <p class="text-2xl font-bold text-green-700">Terima kasih!</p>
                <p class="text-gray-600 mt-2">Data Anda telah berhasil dicatat.</p>
            </div>
        <?php endif; ?>
    </div>
    </body>
    </html>
    <?php
}
?>
