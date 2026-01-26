<?php
include 'db.php';


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
<script>

    const panggilan = <?= json_encode($panggilan) ?>;

    
    if (panggilan) {
        if(panggilan.nomor !== null) {
            const jenis = panggilan.jenis === 'disabilitas' ? 'antrean disabilitas' : 'antrean umum';
            const nomor = panggilan.nomor;
            const aksi = panggilan.aksi;

            const teks = aksi === 'next'
                ? `Nomor antrean berikutnya, ${jenis}, ${nomor}`
                : `Nomor antrean berikutnya, ${jenis}, ${nomor}`;

            const synth = window.speechSynthesis;

            const speak = () => {
                const utter = new SpeechSynthesisUtterance(teks);
                utter.lang = 'id-ID';

                // Cari suara Indonesia jika ada
                const voices = synth.getVoices();
                const indonesianVoice = voices.find(voice => voice.lang === 'id-ID');
                if (indonesianVoice) {
                    utter.voice = indonesianVoice;
                }

                synth.speak(utter);
            };

            // Ulangi 3 kali dengan jeda
            let count = 0;
            const interval = setInterval(() => {
                if (count >= 2) {
                    clearInterval(interval);
                } else {
                    speak();
                    count++;
                }
            }, 1500);
        }
        
    }
</script>

</body>
</html>
