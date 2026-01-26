<?php
include 'db.php';

$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

// Pagination
$perPage = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $perPage;

// Hitung total data
$countStmt = $mysqli->prepare("SELECT COUNT(*) as total FROM antrian WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
$countStmt->bind_param("ii", $bulan, $tahun);
$countStmt->execute();
$countResult = $countStmt->get_result()->fetch_assoc();
$totalData = $countResult['total'];
$totalPages = ceil($totalData / $perPage);

// Ambil data untuk halaman saat ini
$stmt = $mysqli->prepare("SELECT * FROM antrian WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ? ORDER BY tanggal DESC, nomor DESC LIMIT ?, ?");
$stmt->bind_param("iiii", $bulan, $tahun, $offset, $perPage);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Daftar Pengguna</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Include Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    

</head>
<body class="bg-gray-100 p-8">
<div class="max-w-6xl mx-auto bg-white p-6 rounded shadow">
    <h1 class="text-2xl font-bold mb-4">Daftar Pengguna</h1>
    <form method="GET" class="flex items-center gap-4 mb-4">
        <select name="bulan" class="border p-2">
            <?php
            for ($i = 1; $i <= 12; $i++) {
                $val = str_pad($i, 2, '0', STR_PAD_LEFT);
                $selected = ($val == $bulan) ? 'selected' : '';
                echo "<option value='$val' $selected>" . date('F', mktime(0,0,0,$i,1)) . "</option>";
            }
            ?>
        </select>
        <select name="tahun" class="border p-2">
            <?php
            $currentYear = date('Y');
            for ($i = $currentYear; $i >= $currentYear - 5; $i--) {
                $selected = ($i == $tahun) ? 'selected' : '';
                echo "<option value='$i' $selected>$i</option>";
            }
            ?>
        </select>
        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded">Tampilkan</button>
        <a href="download_pengguna.php?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>" class="bg-green-500 text-white px-4 py-2 rounded">Download Excel</a>
        
    </form>
    <div class="overflow-x-auto">
    <table class="w-full border-collapse border text-sm">
        <thead>
            <tr class="bg-gray-200">
                <th class="border p-2">No</th>
                <th class="border p-2">Jenis</th>
                <th class="border p-2">Nomor Urut</th>
                <th class="border p-2">Tanggal Kunjungan</th>
                <th class="border p-2">Nama Lengkap</th>
                <th class="border p-2">Jenis Kelamin</th>
                <th class="border p-2">Pendidikan Terakhir</th>
                <th class="border p-2">Pekerjaan</th>
                <th class="border p-2">Telepon</th>
                <th class="border p-2">Instansi</th>
                <th class="border p-2">Data yang Diperlukan</th>
                <th class="border p-2">Metode Pelayanan</th>
                <th class="border p-2">Aksi</th> <!-- New Action Column -->
            </tr>
        </thead>
        <tbody>
  <?php $no = $offset + 1; while ($row = $result->fetch_assoc()) : ?>
    <tr>
      <td class='border p-2 text-center'>
        <input type="checkbox" class="select-checkbox" data-id="<?= $row['id'] ?>">
      </td>
      <td class='border p-2 text-center col-jenis' data-id='<?= $row['id'] ?>' data-column='jenis'><?= $row['jenis'] ?></td>
      <td class='border p-2 col-nomor' data-id='<?= $row['id'] ?>' data-column='nomor'><?= $row['nomor'] ?></td>
      <td class='border p-2 col-tanggal' contenteditable='true' data-id='<?= $row['id'] ?>' data-column='tanggal'><?= $row['tanggal'] ?></td>
      <td class='border p-2 col-nama' contenteditable='true' data-id='<?= $row['id'] ?>' data-column='nama'><?= $row['nama'] ?></td>
      <td class='border p-2 col-jk' contenteditable='true' data-id='<?= $row['id'] ?>' data-column='jk'><?= $row['jk'] ?></td>
      <td class='border p-2 col-pendidikan' contenteditable='true' data-id='<?= $row['id'] ?>' data-column='pendidikan'><?= $row['pendidikan'] ?></td>
      <td class='border p-2 col-pekerjaan' contenteditable='true' data-id='<?= $row['id'] ?>' data-column='pekerjaan'><?= $row['pekerjaan'] ?></td>
      <td class='border p-2 col-telepon' contenteditable='true' data-id='<?= $row['id'] ?>' data-column='telepon'><?= $row['telepon'] ?></td>
      <td class='border p-2 col-instansi' contenteditable='true' data-id='<?= $row['id'] ?>' data-column='instansi'><?= $row['instansi'] ?></td>
      <td class='border p-2 col-data-yang-diperlukan' contenteditable='true' data-id='<?= $row['id'] ?>' data-column='data_yang_diperlukan'><?= $row['data_yang_diperlukan'] ?></td>
      <td class='border p-2 col-metode' contenteditable='true' data-id='<?= $row['id'] ?>' data-column='metode'><?= $row['metode'] ?></td>
      <td class="border p-2 text-center col-aksi">
        <button class="bg-red-500 text-white p-1 rounded" onclick="deleteUser(<?= $row['id'] ?>)">
          <i class="fas fa-trash-alt"></i> <!-- Trash icon -->
        </button>
      </td>
    </tr>
  <?php endwhile ?>
</tbody>

    </table>
    <div class="mt-4 flex justify-between items-center">
  <button id="deleteSelected" class="bg-red-500 text-white px-4 py-2 rounded">
    Hapus yang Dipilih
  </button>
  <span id="selectedCount">0 Terpilih</span>
</div>

    </div>
    <!-- Pagination -->
    <div class="mt-4 flex justify-center space-x-2">
        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
            <a href="?bulan=<?= $bulan ?>&tahun=<?= $tahun ?>&page=<?= $i ?>"
               class="px-3 py-1 border rounded <?= ($i == $page) ? 'bg-blue-500 text-white' : 'bg-white' ?>">
                <?= $i ?>
            </a>
        <?php endfor; ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
  $(document).ready(function() {
    // Mengupdate jumlah yang dipilih
    $('.select-checkbox').on('change', function() {
      let selectedCount = $('.select-checkbox:checked').length;
      $('#selectedCount').text(selectedCount + ' Terpilih');
    });

    // Menghapus pengguna yang dipilih
    $('#deleteSelected').on('click', function() {
      let selectedIds = [];
      $('.select-checkbox:checked').each(function() {
        selectedIds.push($(this).data('id'));
      });

      if (selectedIds.length === 0) {
        alert('Pilih pengguna yang ingin dihapus');
        return;
      }

      if (confirm('Apakah Anda yakin ingin menghapus data yang dipilih?')) {
        $.ajax({
          url: 'delete_selected_pengguna.php',
          method: 'POST',
          data: {
            ids: selectedIds
          },
          success: function(response) {
            alert('Data berhasil dihapus');
            location.reload(); // Reload page untuk melihat perubahan
          },
          error: function() {
            alert('Gagal menghapus data');
          }
        });
      }
    });
  });

  
  $(document).ready(function () {
  $('[contenteditable=true]').on('blur', function () {
    var id = $(this).data('id');
    var column = $(this).data('column');
    var value = $(this).text();

    $.ajax({
      url: 'update_pengguna.php',
      method: 'POST',
      data: {
        id: id,
        column: column,
        value: value
      },
      success: function (response) {
        console.log('Data berhasil diperbarui');
      },
      error: function () {
        alert('Gagal memperbarui data');
      }
    });
  });
});


  // Fungsi untuk menghapus satu pengguna
  function deleteUser(id) {
    if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
      $.ajax({
        url: 'delete_pengguna.php',
        method: 'POST',
        data: { id: id },
        success: function(response) {
          alert('Data berhasil dihapus');
          location.reload(); // Reload page untuk melihat perubahan
        },
        error: function() {
          alert('Gagal menghapus data');
        }
      });
    }
  }
</script>

</body>
</html>
