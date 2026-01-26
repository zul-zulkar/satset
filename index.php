<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="refresh" content="3"> <!-- auto refresh -->
    <title>Layar Antrean</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-black text-white p-10">
    <h1 class="text-3xl text-center font-bold mb-10">LAYAR ANTREAN</h1>

    <!-- KOTAK ANTREAN -->
    <div class="grid grid-cols-2 gap-10 text-center text-6xl font-bold mb-10">
        <div class="bg-blue-700 p-6 rounded">DISABILITAS:<br>
            <?php
            include 'db.php';
            $tanggal = date('Y-m-d');
            $res = $mysqli->query("SELECT nomor FROM antrian WHERE tanggal = '$tanggal' AND jenis = 'disabilitas' AND status = 'dipanggil' ORDER BY id DESC LIMIT 1");
            echo ($row = $res->fetch_assoc()) ? $row['nomor'] : '-';
            ?>
        </div>
        <div class="bg-green-700 p-6 rounded">UMUM:<br>
            <?php
            $res = $mysqli->query("SELECT nomor FROM antrian WHERE tanggal = '$tanggal' AND jenis = 'umum' AND status = 'dipanggil' ORDER BY id DESC LIMIT 1");
            echo ($row = $res->fetch_assoc()) ? $row['nomor'] : '-';
            ?>
        </div>
    </div>

    <!-- KOTAK BARCODE TERPISAH DENGAN LATAR BELAKANG PUTIH -->
    <div class="grid grid-cols-2 gap-10 text-center">
        <div class="bg-white text-black p-6 rounded shadow">
            <p class="font-semibold mb-2">SCAN UNTUK DISABILITAS</p>
            <img src="qr_disabilitas.png" alt="Barcode Disabilitas" class="mx-auto w-70">
            <p>https://satset.statsbali.id/disabilitas.php</p>
        </div>
        <div class="bg-white text-black p-6 rounded shadow">
            <p class="font-semibold mb-2">SCAN UNTUK UMUM</p>
            <img src="qr_umum.png" alt="Barcode Umum" class="mx-auto w-70">
            <p>https://satset.statsbali.id/umum.php</p>
        </div>
    </div>
</body>
</html>
