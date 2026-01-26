<?php
$jenis = "whatsapp"; 
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Hadir</title>
    <meta name="viewport" content="width=device-width, initial-scale=1"> <!-- Responsive -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-4 sm:p-10">
<div class="w-full max-w-xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-xl sm:text-2xl font-bold mb-4 text-center">Daftar Hadir</h1>

    <?php
    include 'db.php';

    $tampilkanForm = true;
    $nomorSaya = null;

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $tanggal = date('Y-m-d');

        $stmt = $mysqli->prepare("SELECT MAX(nomor) as maxn FROM antrian WHERE tanggal = ? AND jenis = ?");
        $stmt->bind_param("ss", $tanggal, $jenis);
        $stmt->execute();
        $res = $stmt->get_result()->fetch_assoc();
        $nomor_baru = (int)$res['maxn'] + 1;
        $metode = 'whatsapp';

        $stmt = $mysqli->prepare("INSERT INTO antrian (
        nama, telepon, instansi, jenis, nomor, 
        tanggal, jk, pendidikan, 
        pekerjaan, data_yang_diperlukan, metode, email, status
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'menunggu')");
        $stmt->bind_param(
            "ssssisssssss",
            $_POST['nama'], $_POST['telepon'], $_POST['instansi'], $jenis, $nomor_baru,
            $tanggal, $_POST['jk'], $_POST['pendidikan'],
            $_POST['pekerjaan'], $_POST['data_yang_diperlukan'], $metode, $_POST['email']
        );
        
        $stmt->execute();

        $nomorSaya = $nomor_baru;
        $tampilkanForm = false;
    }

    if ($tampilkanForm):
    ?>
    <form method="POST" class="space-y-3 text-sm">
    <div>
        <label for="nama">Nama:</label>
        <input id="nama" name="nama" required placeholder="Nama" class="w-full border p-2 rounded">
    </div>

    <div>
        <label for="email">Email:</label>
        <input id="email" name="email" required placeholder="Email" class="w-full border p-2 rounded">
    </div>

    <div>
        <label for="telepon">Nomor Telepon:</label>
        <input id="telepon" name="telepon" required placeholder="Nomor Telepon" class="w-full border p-2 rounded">
    </div>

    <div>
        <label for="pekerjaan">Pekerjaan:</label>
        <input id="pekerjaan" name="pekerjaan" required placeholder="Pekerjaan" class="w-full border p-2 rounded">
    </div>

    <div>
        <label for="data_yang_diperlukan">Data yang Diperlukan:</label>
        <input id="data_yang_diperlukan" name="data_yang_diperlukan" required placeholder="Data Yang Diperlukan" class="w-full border p-2 rounded">
    </div>

    <div>
        <label for="pendidikan">Pendidikan Terakhir:</label>
        <select id="pendidikan" name="pendidikan" required class="w-full border p-2 rounded">
            <option value="" disabled selected>Pilih Pendidikan</option>
            <option value="SD">SD</option>
            <option value="SMP">SMP</option>
            <option value="SMA">SMA</option>
            <option value="D1">D1</option>
            <option value="D2">D2</option>
            <option value="D3">D3</option>
            <option value="S1/DIV">S1/DIV</option>
            <option value="S2">S2</option>
            <option value="S3">S3</option>
            <option value="Tidak Sekolah">Tidak Sekolah</option>
        </select>
    </div>

    <div>
        <label for="jk">Jenis Kelamin:</label>
        <select id="jk" name="jk" required class="w-full border p-2 rounded">
            <option value="" disabled selected>Pilih Jenis Kelamin</option>
            <option value="L">Laki-Laki</option>
            <option value="P">Perempuan</option>
        </select>
    </div>

    <div>
        <label for="instansi">Asal Instansi:</label>
        <input id="instansi" name="instansi" required placeholder="Perorangan / Dinas Pendidikan / BRI" class="w-full border p-2 rounded">
    </div>

    <button type="submit" class="bg-blue-500 hover:bg-blue-600 text-white w-full py-2 rounded font-semibold">Ambil Nomor Antrean</button>
</form>


    <?php else: ?>
        <div class="mt-6 p-4 bg-green-100 border border-green-400 rounded text-center">
            <p class="text-3xl font-bold text-green-700 uppercase">Terimakasih telah mengisi daftar hadir</p>
        </div>

    <?php endif; ?>
</div>


</body>
</html>
