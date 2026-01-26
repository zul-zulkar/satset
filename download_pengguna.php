<?php
require 'vendor/autoload.php';
include 'db.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$bulan = $_GET['bulan'] ?? date('m');
$tahun = $_GET['tahun'] ?? date('Y');

$stmt = $mysqli->prepare("SELECT * FROM antrian WHERE MONTH(tanggal) = ? AND YEAR(tanggal) = ?");
$stmt->bind_param("ii", $bulan, $tahun);
$stmt->execute();
$result = $stmt->get_result();

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->fromArray(['id', 'nama', 'email', 'Jenis Kelamin', 'Tanggal Lahir', 'Pendidikan', 'Alamat', 'Pekerjaan', 'Data yang Diperlukan', 'Metode', 'Telepon', 'Instansi', 'Jenis', 'Nomor', 'Tanggal' , 'Status'], NULL, 'A1');

$rowNum = 2;
while ($row = $result->fetch_assoc()) {
    $sheet->fromArray(array_values($row), NULL, "A$rowNum");
    $rowNum++;
}

$filename = "daftar_pengguna_{$bulan}_{$tahun}.xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
$writer = new Xlsx($spreadsheet);
$writer->save("php://output");
exit;