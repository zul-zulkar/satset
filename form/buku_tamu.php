<?php
/**
 * Form Kunjungan Langsung — dipakai oleh disabilitas.php & umum.php
 * Fields dasar : nama, email, telepon, instansi, jk, jumlah_orang, keperluan
 * Fields PST   : pendidikan, kelompok_umur, pekerjaan, pemanfaatan_data,
 *                data_dibutuhkan  (muncul saat keperluan = Permintaan Data)
 */
function renderForm($jenis, $judul) {
    include '../db.php';

    $tampilkanForm = true;
    $nomorSaya     = null;
    $tanggal       = date('Y-m-d');
    $errorMsg      = '';
    $old           = [];

    $validPendidikan  = ['SLTA/Sederajat', 'D1/D2/D3', 'D4/S1', 'S2', 'S3'];
    $validUmur        = ['di bawah 17 tahun', '17 - 25 tahun', '26 - 34 tahun',
                         '35 - 44 tahun', '45 - 54 tahun', '55 - 65 tahun', 'di atas 65 tahun'];
    $validPemanfaatan = ['Tugas Sekolah/Tugas Kuliah', 'Pemerintahan',
                         'Komersial', 'Penelitian', 'Lainnya'];
    $validJenisPelayanan = ['Permintaan Data', 'Konsultasi Statistik', 'Rekomendasi Statistik'];
    $pekerjaanOptions = ['Pelajar/Mahasiswa', 'Peneliti/Dosen', 'ASN/TNI/Polri',
                         'Pegawai BUMN/BUMD', 'Pegawai Swasta', 'Wiraswasta', 'Lainnya'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $old  = $_POST;
        $nama  = trim($_POST['nama']  ?? '');
        $email = trim($_POST['email'] ?? '');
        $isPSTPost = (($_POST['keperluan_pst'] ?? '') === '1');

        $telepon = trim($_POST['telepon'] ?? '');

        if (!preg_match("/^[a-zA-Z'\s]+$/u", $nama)) {
            $errorMsg = "Nama hanya boleh berisi huruf, spasi, dan tanda petik satu (').";
        } elseif (empty($telepon)) {
            $errorMsg = 'Nomor HP wajib diisi.';
        } elseif ($isPSTPost && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errorMsg = 'Format email tidak valid.';
        } else {
            $kpVal = $_POST['keperluan_pst'] ?? '';
            if ($kpVal !== '1' && $kpVal !== '0') {
                $errorMsg = 'Pilih keperluan kunjungan Anda.';
            } else {
                $kunjungan_pst = intval($kpVal);
                $keperluan     = $kunjungan_pst ? 'Pelayanan Statistik Terpadu' : trim($_POST['keperluan'] ?? '');
                if (!$kunjungan_pst && $keperluan === '') {
                    $errorMsg = 'Jelaskan keperluan kunjungan Anda.';
                }
            }
        }

        // ── Validasi PST tambahan ──────────────────────────────────────
        $pendidikan      = null;
        $kelompok_umur   = null;
        $pekerjaan       = null;
        $pemanfaatan     = null;
        $dataJson        = null;
        $jenis_pelayanan = null;

        if ($errorMsg === '' && ($kunjungan_pst ?? 0) === 1) {
            $pendidikan      = $_POST['pendidikan']       ?? '';
            $kelompok_umur   = $_POST['kelompok_umur']    ?? '';
            $pemanfaatan     = $_POST['pemanfaatan_data'] ?? '';
            $jenis_pelayanan = $_POST['jenis_pelayanan']  ?? '';
            $pkPilihan     = $_POST['pekerjaan_pilihan'] ?? '';
            $pekerjaan     = $pkPilihan === 'Lainnya'
                           ? trim($_POST['pekerjaan_lainnya_text'] ?? '')
                           : $pkPilihan;
            $dataNama      = $_POST['data_nama']    ?? [];
            $tahunDari     = $_POST['tahun_dari']   ?? [];
            $tahunSampai   = $_POST['tahun_sampai'] ?? [];

            if (!in_array($jenis_pelayanan, $validJenisPelayanan)) {
                $errorMsg = 'Pilih jenis pelayanan.';
            } elseif (!in_array($pendidikan, $validPendidikan)) {
                $errorMsg = 'Pilih pendidikan tertinggi.';
            } elseif (!in_array($kelompok_umur, $validUmur)) {
                $errorMsg = 'Pilih kelompok umur.';
            } elseif (empty($pekerjaan)) {
                $errorMsg = $pkPilihan === 'Lainnya'
                    ? 'Sebutkan pekerjaan Anda pada kolom "Lainnya".'
                    : 'Pilih pekerjaan.';
            } elseif (!in_array($pemanfaatan, $validPemanfaatan)) {
                $errorMsg = 'Pilih pemanfaatan hasil data.';
            } elseif (empty($dataNama) || count(array_filter(array_map('trim', $dataNama))) === 0) {
                $errorMsg = 'Tambahkan minimal 1 data yang dibutuhkan.';
            } else {
                $records = [];
                for ($i = 0; $i < count($dataNama); $i++) {
                    $dn = trim($dataNama[$i] ?? '');
                    if ($dn === '') continue;
                    $td = intval($tahunDari[$i]   ?? 0);
                    $ts = intval($tahunSampai[$i] ?? 0);
                    $records[] = ['data' => $dn, 'tahun_dari' => $td, 'tahun_sampai' => $ts];
                }
                $dataJson = json_encode($records, JSON_UNESCAPED_UNICODE);
            }
        }

        // ── Simpan ────────────────────────────────────────────────────
        if ($errorMsg === '') {
            $stmt = $mysqli->prepare("SELECT MAX(nomor) AS maxn FROM antrian WHERE tanggal = ? AND jenis = ?");
            $stmt->bind_param("ss", $tanggal, $jenis);
            $stmt->execute();
            $res       = $stmt->get_result()->fetch_assoc();
            $nomor_baru = (int)$res['maxn'] + 1;

            $jumlah = max(1, (int)($_POST['jumlah_orang'] ?? 1));
            $token  = bin2hex(random_bytes(16));

            $stmt = $mysqli->prepare(
                "INSERT INTO antrian
                    (nama, email, telepon, instansi, jk, jumlah_orang, keperluan,
                     kunjungan_pst, pendidikan, kelompok_umur, pekerjaan,
                     pemanfaatan_data, data_dibutuhkan, jenis_pelayanan,
                     jenis, nomor, tanggal, status, token)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'menunggu', ?)"
            );
            $stmt->bind_param(
                "sssssisisssssssiss",
                $_POST['nama'], $_POST['email'], $_POST['telepon'], $_POST['instansi'],
                $_POST['jk'], $jumlah, $keperluan,
                $kunjungan_pst, $pendidikan, $kelompok_umur, $pekerjaan,
                $pemanfaatan, $dataJson, $jenis_pelayanan,
                $jenis, $nomor_baru, $tanggal, $token
            );
            $stmt->execute();
            $nomorSaya     = $nomor_baru;
            $tampilkanForm = false;
        }
    }

    // Nilai lama untuk pekerjaan & data
    $oldPekerjaanPilihan     = $old['pekerjaan_pilihan']      ?? '';
    $oldPekerjaanLainnyaTxt  = $old['pekerjaan_lainnya_text'] ?? '';
    $oldDataNama             = $old['data_nama']               ?? [];
    $oldTahunDari            = $old['tahun_dari']              ?? [];
    $oldTahunSampai          = $old['tahun_sampai']            ?? [];
    $isPST                   = ($old['keperluan_pst'] ?? '') === '1';
    $oldJenisPelayanan       = $old['jenis_pelayanan']         ?? '';
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <meta charset="UTF-8">
        <title><?= htmlspecialchars($judul) ?></title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <script src="https://cdn.tailwindcss.com"></script>
        <style>
            .radio-label { display:flex; align-items:center; gap:0.5rem; cursor:pointer; padding:0.25rem 0; }
            .radio-label input[type=radio] { accent-color:#2563eb; width:1rem; height:1rem; flex-shrink:0; }
        </style>
    </head>
    <body class="bg-gray-100 p-4 sm:p-10">
    <div class="w-full max-w-xl mx-auto bg-white p-6 rounded shadow">
        <h1 class="text-xl sm:text-2xl font-bold mb-4 text-center"><?= htmlspecialchars($judul) ?></h1>

        <?php if ($tampilkanForm): ?>
            <?php if (!empty($errorMsg)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded mb-4 text-sm">
                    <?= htmlspecialchars($errorMsg) ?>
                </div>
            <?php endif; ?>

            <form method="POST" class="space-y-4 text-sm" id="formLangsung" autocomplete="off" novalidate>

                <div>
                    <label class="block mb-1 font-medium">Nama Lengkap <span class="text-red-500">*</span></label>
                    <input id="nama" name="nama" required placeholder="Nama Lengkap"
                           value="<?= htmlspecialchars($old['nama'] ?? '') ?>"
                           class="w-full border p-2 rounded">
                </div>

                <div>
                    <label class="block mb-1 font-medium">Nama Organisasi / Instansi <span class="text-red-500">*</span></label>
                    <input id="instansi" name="instansi" required placeholder="Perorangan / Dinas Pendidikan / BRI"
                           value="<?= htmlspecialchars($old['instansi'] ?? '') ?>"
                           class="w-full border p-2 rounded">
                </div>

                <div>
                    <label class="block mb-1 font-medium">Nomor Telepon <span class="text-red-500">*</span></label>
                    <input id="telepon" name="telepon" required placeholder="08xx / 0362xxxxxx"
                           value="<?= htmlspecialchars($old['telepon'] ?? '') ?>"
                           class="w-full border p-2 rounded">
                </div>

                <div>
                    <label class="block mb-1 font-medium">Jenis Kelamin <span class="text-red-500">*</span></label>
                    <select id="jk" name="jk" required class="w-full border p-2 rounded">
                        <option value="" disabled <?= empty($old['jk']) ? 'selected' : '' ?>>Pilih Jenis Kelamin</option>
                        <option value="L" <?= ($old['jk'] ?? '') === 'L' ? 'selected' : '' ?>>Laki-Laki</option>
                        <option value="P" <?= ($old['jk'] ?? '') === 'P' ? 'selected' : '' ?>>Perempuan</option>
                    </select>
                </div>

                <div>
                    <label class="block mb-1 font-medium">Jumlah Orang <span class="text-red-500">*</span></label>
                    <input id="jumlah_orang" name="jumlah_orang" type="number" min="1" required
                           value="<?= htmlspecialchars($old['jumlah_orang'] ?? '1') ?>"
                           class="w-full border p-2 rounded">
                </div>

                <!-- Keperluan -->
                <div>
                    <label class="block mb-1 font-medium">Keperluan Kunjungan <span class="text-red-500">*</span></label>
                    <div class="space-y-2 mb-2">
                        <label class="radio-label">
                            <input type="radio" name="keperluan_pst" value="1" id="kep_ya"
                                   <?= $isPST ? 'checked' : '' ?>>
                            <span>Pelayanan Statistik Terpadu</span>
                        </label>
                        <label class="radio-label">
                            <input type="radio" name="keperluan_pst" value="0" id="kep_tidak"
                                   <?= ($old['keperluan_pst'] ?? '') === '0' ? 'checked' : '' ?>>
                            <span>Lainnya</span>
                        </label>
                    </div>
                    <div id="keperluan_lain_wrapper" class="<?= ($old['keperluan_pst'] ?? '') === '0' ? '' : 'hidden' ?>">
                        <textarea id="keperluan" name="keperluan" rows="2"
                                  placeholder="Jelaskan keperluan kunjungan Anda"
                                  class="w-full border p-2 rounded resize-none text-sm"><?= htmlspecialchars($old['keperluan'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- ── BAGIAN PST (tampil saat Permintaan Data dipilih) ── -->
                <div id="pst-fields" class="<?= $isPST ? '' : 'hidden' ?> space-y-4 border-t border-blue-100 pt-4">
                    <p class="text-xs text-blue-600 font-semibold uppercase tracking-wide">Data Kunjungan PST</p>

                    <!-- Jenis Pelayanan -->
                    <div>
                        <label class="block mb-1 font-medium">Jenis Pelayanan <span class="text-red-500">*</span></label>
                        <div>
                            <?php foreach ($validJenisPelayanan as $jp): ?>
                                <label class="radio-label">
                                    <input type="radio" name="jenis_pelayanan" value="<?= $jp ?>"
                                           <?= $oldJenisPelayanan === $jp ? 'checked' : '' ?>>
                                    <?= $jp ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Email (khusus PST) -->
                    <div>
                        <label class="block mb-1 font-medium">Email <span class="text-red-500">*</span></label>
                        <input id="email" name="email" type="email" placeholder="contoh@email.com"
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                               class="w-full border p-2 rounded">
                    </div>

                    <!-- Pendidikan -->
                    <div>
                        <label class="block mb-1 font-medium">Pendidikan Tertinggi <span class="text-red-500">*</span></label>
                        <div>
                            <?php foreach ($validPendidikan as $p): ?>
                                <label class="radio-label">
                                    <input type="radio" name="pendidikan" value="<?= $p ?>"
                                           <?= ($old['pendidikan'] ?? '') === $p ? 'checked' : '' ?>>
                                    <?= $p ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Kelompok Umur -->
                    <div>
                        <label class="block mb-1 font-medium">Umur <span class="text-red-500">*</span></label>
                        <div>
                            <?php foreach ($validUmur as $u): ?>
                                <label class="radio-label">
                                    <input type="radio" name="kelompok_umur" value="<?= $u ?>"
                                           <?= ($old['kelompok_umur'] ?? '') === $u ? 'checked' : '' ?>>
                                    <?= $u ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Pekerjaan -->
                    <div>
                        <label class="block mb-1 font-medium">Pekerjaan <span class="text-red-500">*</span></label>
                        <div>
                            <?php foreach ($pekerjaanOptions as $pj): ?>
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
                               class="mt-2 w-full border p-2 rounded text-sm <?= $oldPekerjaanPilihan === 'Lainnya' ? '' : 'hidden' ?>">
                        <input type="hidden" name="pekerjaan" id="pekerjaan_hidden"
                               value="<?= htmlspecialchars($old['pekerjaan'] ?? '') ?>">
                    </div>

                    <!-- Pemanfaatan Hasil -->
                    <div>
                        <label class="block mb-1 font-medium">Pemanfaatan Hasil <span class="text-red-500">*</span></label>
                        <div>
                            <?php foreach ($validPemanfaatan as $pf): ?>
                                <label class="radio-label">
                                    <input type="radio" name="pemanfaatan_data" value="<?= $pf ?>"
                                           <?= ($old['pemanfaatan_data'] ?? '') === $pf ? 'checked' : '' ?>>
                                    <?= $pf ?>
                                </label>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Data yang Dibutuhkan -->
                    <div>
                        <label class="block mb-1 font-medium"><span id="data-section-label">Data yang Dibutuhkan</span> <span class="text-red-500">*</span></label>
                        <p class="text-gray-500 text-xs mb-3">Tambahkan satu atau lebih data beserta rentang tahunnya.</p>
                        <div id="data-container" class="space-y-3"></div>
                        <button type="button" id="btn-tambah-data"
                                class="mt-3 text-blue-700 border border-blue-500 px-4 py-1.5 rounded text-sm hover:bg-blue-50 transition-colors">
                            + Tambah Data
                        </button>
                    </div>
                </div>

                <button type="submit"
                        class="bg-blue-500 hover:bg-blue-600 text-white w-full py-2 rounded font-semibold">
                    Ambil Nomor Antrean
                </button>
            </form>

            <script>
            // ── Toggle keperluan & PST section ───────────────────────────
            (function() {
                var kepYa    = document.getElementById('kep_ya');
                var kepTidak = document.getElementById('kep_tidak');
                var lainWrap = document.getElementById('keperluan_lain_wrapper');
                var pstWrap  = document.getElementById('pst-fields');

                function toggle() {
                    lainWrap.classList.toggle('hidden', !kepTidak.checked);
                    pstWrap.classList.toggle('hidden',  !kepYa.checked);
                }
                kepYa.addEventListener('change',    toggle);
                kepTidak.addEventListener('change', toggle);
            })();

            // ── Pekerjaan "Lainnya" toggle ────────────────────────────────
            var lainnyaRadio = document.getElementById('pekerjaan_lainnya_radio');
            var lainnyaInput = document.getElementById('pekerjaan_lainnya_input');
            var pekHidden    = document.getElementById('pekerjaan_hidden');

            document.querySelectorAll('input[name="pekerjaan_pilihan"]').forEach(function(r) {
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

            // ── Jenis Pelayanan — label dinamis ───────────────────────────
            var _jpLabels = {
                'Permintaan Data':       {big: 'Data yang Dibutuhkan',                          small: 'Data yang dibutuhkan'},
                'Konsultasi Statistik':  {big: 'Statistik yang Dikonsultasikan',                small: 'Statistik yang dikonsultasikan'},
                'Rekomendasi Statistik': {big: 'Kegiatan Statistik yang Akan Dilaksanakan',     small: 'Kegiatan Statistik yang akan dilaksanakan'}
            };

            function currentJpLabel() {
                var el = document.querySelector('input[name="jenis_pelayanan"]:checked');
                return _jpLabels[el ? el.value : ''] || _jpLabels['Permintaan Data'];
            }

            function refreshDataLabels() {
                var lbl = currentJpLabel();
                var sec = document.getElementById('data-section-label');
                if (sec) sec.textContent = lbl.big;
                document.querySelectorAll('.data-row-label').forEach(function(el) {
                    el.textContent = lbl.small;
                });
            }

            document.querySelectorAll('input[name="jenis_pelayanan"]').forEach(function(r) {
                r.addEventListener('change', refreshDataLabels);
            });

            // ── Data yang Dibutuhkan — baris dinamis ──────────────────────
            var container = document.getElementById('data-container');

            function escHtml(s) {
                return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;')
                                .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
            }

            function createRecord(data, dari, sampai) {
                var lbl = currentJpLabel().small;
                var div = document.createElement('div');
                div.className = 'record-row border border-gray-200 rounded-lg p-4 bg-gray-50 relative';
                div.innerHTML =
                    '<button type="button" onclick="removeRecord(this)"' +
                    ' class="absolute top-2 right-3 text-gray-400 hover:text-red-500 text-xl leading-none">&times;</button>' +
                    '<div class="mb-2">' +
                    '  <label class="block text-xs text-gray-600 mb-1 font-medium data-row-label">' + escHtml(lbl) + '</label>' +
                    '  <input type="text" name="data_nama[]" placeholder="Contoh: Jumlah penduduk"' +
                    '         value="' + escHtml(data||'') + '"' +
                    '         class="w-full border border-gray-300 p-2 rounded text-sm focus:outline-none focus:ring-1 focus:ring-blue-500">' +
                    '</div>' +
                    '<div class="flex gap-3">' +
                    '  <div class="flex-1"><label class="block text-xs text-gray-600 mb-1 font-medium">Tahun dari</label>' +
                    '    <input type="number" name="tahun_dari[]" min="1900" max="2100" placeholder="2020"' +
                    '           value="' + escHtml(dari||'') + '"' +
                    '           class="w-full border border-gray-300 p-2 rounded text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"></div>' +
                    '  <div class="flex-1"><label class="block text-xs text-gray-600 mb-1 font-medium">Tahun sampai</label>' +
                    '    <input type="number" name="tahun_sampai[]" min="1900" max="2100" placeholder="2024"' +
                    '           value="' + escHtml(sampai||'') + '"' +
                    '           class="w-full border border-gray-300 p-2 rounded text-sm focus:outline-none focus:ring-1 focus:ring-blue-500"></div>' +
                    '</div>';
                return div;
            }

            function removeRecord(btn) {
                if (container.children.length > 1) btn.closest('.record-row').remove();
                else alert('Minimal harus ada 1 data yang dibutuhkan.');
            }

            document.getElementById('btn-tambah-data').addEventListener('click', function() {
                container.appendChild(createRecord());
            });

            // Restore baris lama (setelah error)
            <?php
            if (empty($oldDataNama)) {
                echo "container.appendChild(createRecord());";
            } else {
                for ($i = 0; $i < count($oldDataNama); $i++) {
                    $dn = json_encode($oldDataNama[$i] ?? '');
                    $td = json_encode($oldTahunDari[$i]   ?? '');
                    $ts = json_encode($oldTahunSampai[$i] ?? '');
                    echo "container.appendChild(createRecord($dn,$td,$ts));";
                }
            }
            ?>
            refreshDataLabels();

            // ── Validasi submit ───────────────────────────────────────────
            document.getElementById('formLangsung').addEventListener('submit', function(e) {
                var nama = document.getElementById('nama').value.trim();
                if (!/^[a-zA-Z'\s]+$/.test(nama)) {
                    alert("Nama hanya boleh berisi huruf, spasi, dan tanda petik satu."); e.preventDefault(); return;
                }
                var telepon = document.getElementById('telepon').value.trim();
                if (!telepon) {
                    alert('Nomor HP wajib diisi.'); document.getElementById('telepon').focus(); e.preventDefault(); return;
                }
                var kepPST = document.querySelector('input[name="keperluan_pst"]:checked');
                if (!kepPST) {
                    alert('Pilih keperluan kunjungan Anda.'); e.preventDefault(); return;
                }
                if (kepPST.value === '0') {
                    var kep = (document.getElementById('keperluan') || {}).value || '';
                    if (!kep.trim()) {
                        alert('Jelaskan keperluan kunjungan Anda.');
                        document.getElementById('keperluan').focus(); e.preventDefault(); return;
                    }
                }
                if (kepPST.value === '1') {
                    if (!document.querySelector('input[name="jenis_pelayanan"]:checked')) {
                        alert('Pilih jenis pelayanan.'); e.preventDefault(); return;
                    }
                    var email = document.getElementById('email').value.trim();
                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        alert('Format email tidak valid.'); document.getElementById('email').focus(); e.preventDefault(); return;
                    }
                    if (!document.querySelector('input[name="pendidikan"]:checked')) {
                        alert('Pilih pendidikan tertinggi.'); e.preventDefault(); return;
                    }
                    if (!document.querySelector('input[name="kelompok_umur"]:checked')) {
                        alert('Pilih kelompok umur.'); e.preventDefault(); return;
                    }
                    var pkPilihan = document.querySelector('input[name="pekerjaan_pilihan"]:checked');
                    if (!pkPilihan) {
                        alert('Pilih pekerjaan.'); e.preventDefault(); return;
                    }
                    if (pkPilihan.value === 'Lainnya') {
                        var lainVal = lainnyaInput.value.trim();
                        if (!lainVal) {
                            alert('Sebutkan pekerjaan Anda pada kolom "Lainnya".');
                            lainnyaInput.focus(); e.preventDefault(); return;
                        }
                        pekHidden.value = lainVal;
                    } else {
                        pekHidden.value = pkPilihan.value;
                    }
                    if (!document.querySelector('input[name="pemanfaatan_data"]:checked')) {
                        alert('Pilih pemanfaatan hasil data.'); e.preventDefault(); return;
                    }
                    var dataNamas = document.querySelectorAll('input[name="data_nama[]"]');
                    var hasData = Array.from(dataNamas).some(function(i) { return i.value.trim() !== ''; });
                    if (!hasData) {
                        alert('Tambahkan minimal 1 data yang dibutuhkan.'); e.preventDefault(); return;
                    }
                    var tDari   = document.querySelectorAll('input[name="tahun_dari[]"]');
                    var tSampai = document.querySelectorAll('input[name="tahun_sampai[]"]');
                    for (var j = 0; j < dataNamas.length; j++) {
                        if (!dataNamas[j].value.trim()) continue;
                        var td = parseInt(tDari[j].value), ts = parseInt(tSampai[j].value);
                        if (isNaN(td) || td < 1900 || td > 2100) {
                            alert('Tahun dari tidak valid pada data ke-' + (j+1) + '.'); tDari[j].focus(); e.preventDefault(); return;
                        }
                        if (isNaN(ts) || ts < 1900 || ts > 2100) {
                            alert('Tahun sampai tidak valid pada data ke-' + (j+1) + '.'); tSampai[j].focus(); e.preventDefault(); return;
                        }
                        if (ts < td) {
                            alert('Tahun sampai tidak boleh lebih kecil dari tahun dari pada data ke-' + (j+1) + '.'); tSampai[j].focus(); e.preventDefault(); return;
                        }
                    }
                }
            });
            </script>

        <?php else: ?>
            <div class="mt-2 p-4 bg-green-100 border border-green-400 rounded text-center">
                <p class="text-gray-700 mb-1">Nomor Antrean Anda:</p>
                <p class="text-4xl font-bold text-green-700 uppercase">
                    <?= htmlspecialchars($jenis) ?>-<?= htmlspecialchars($nomorSaya) ?>
                </p>
            </div>

            <?php
            $stmt = $mysqli->prepare(
                "SELECT jenis, nomor FROM antrian
                 WHERE tanggal = ? AND status = 'dipanggil' AND jenis = ?
                 ORDER BY id DESC LIMIT 1"
            );
            $stmt->bind_param("ss", $tanggal, $jenis);
            $stmt->execute();
            $current = $stmt->get_result()->fetch_assoc();
            ?>
            <div class="mt-4 p-4 bg-yellow-100 border border-yellow-400 rounded text-center">
                <p class="text-gray-700 mb-1">Antrean Saat Ini:</p>
                <p id="nomor-antrean" class="text-2xl font-bold text-yellow-700 uppercase">
                    <?php if ($current): ?>
                        <?= htmlspecialchars($current['jenis']) ?>-<?= htmlspecialchars($current['nomor']) ?>
                    <?php else: ?>
                        Belum ada antrean dipanggil
                    <?php endif; ?>
                </p>
            </div>

            <script>
                const APP_BASE = '<?= APP_BASE ?>';
                setInterval(() => {
                    fetch(APP_BASE + '/cs/antrean_sekarang.php?jenis=<?= urlencode($jenis) ?>')
                        .then(r => r.text())
                        .then(t => { var el = document.getElementById('nomor-antrean'); if (el) el.innerText = t; });
                }, 3000);
            </script>
        <?php endif; ?>
    </div>
    </body>
    </html>
    <?php
}
?>
