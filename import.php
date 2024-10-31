<?php
require 'vendor/autoload.php'; // Pastikan jalur ini sesuai dengan lokasi autoload.php Anda

use PhpOffice\PhpSpreadsheet\IOFactory;

// Konfigurasi database
$host = 'localhost';
$user = 'root'; // Ganti dengan username database Anda
$password = ''; // Ganti dengan password database Anda
$database = 'transport';

// Buat koneksi ke database
$conn = new mysqli($host, $user, $password, $database);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Memproses pengiriman formulir
if (isset($_POST['submit'])) {
    // Mengambil file Excel
    $fileName = $_FILES['file']['tmp_name'];

    // Membaca file Excel
    $spreadsheet = IOFactory::load($fileName);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow(); // Mengambil jumlah baris tertinggi

    // Mengimpor data ke dalam database
    for ($row = 2; $row <= $highestRow; $row++) { // Mulai dari baris ke-2 (baris pertama adalah header)
        $trainNo = $worksheet->getCell('A' . $row)->getValue();
        $from = $worksheet->getCell('B' . $row)->getValue();
        $to = $worksheet->getCell('C' . $row)->getValue();
        $departure = $worksheet->getCell('D' . $row)->getFormattedValue(); // Menggunakan getFormattedValue untuk membaca waktu
        $platform = $worksheet->getCell('E' . $row)->getValue();
        $status = $worksheet->getCell('F' . $row)->getValue();

        // Jika $departure dalam format yang salah, coba konversi
        if (is_numeric($departure)) {
            $departure = \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($departure)->format('Y-m-d H:i:s');
        }

        // Debugging: tampilkan nilai yang diambil
        echo "Mencoba mengimpor data: $trainNo, $from, $to, $departure, $platform, $status<br>";

        // Cek apakah data sudah ada
        $checkSql = "SELECT * FROM pids WHERE `Train No.` = ? AND `From` = ? AND `To` = ? AND `Departure` = ? AND `Platform` = ? AND `Status` = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("ssssss", $trainNo, $from, $to, $departure, $platform, $status);
        $checkStmt->execute();
        $result = $checkStmt->get_result();

        if ($result->num_rows > 0) {
            // Jika data sudah ada, minta konfirmasi
            echo "Data dari baris $row sudah ada. Apakah Anda ingin mengganti data ini? <br>";
            echo "<form action='' method='post'>";
            echo "<input type='hidden' name='trainNo' value='$trainNo'>";
            echo "<input type='hidden' name='from' value='$from'>";
            echo "<input type='hidden' name='to' value='$to'>";
            echo "<input type='hidden' name='departure' value='$departure'>";
            echo "<input type='hidden' name='platform' value='$platform'>";
            echo "<input type='hidden' name='status' value='$status'>";
            echo "<input type='submit' name='confirm' value='Ya, Ganti'>";
            echo "<input type='submit' name='cancel' value='Tidak'>";
            echo "</form>";
            continue; // Lanjutkan ke baris berikutnya
        }

        // Persiapkan query untuk memasukkan data
        $sql = "INSERT INTO pids (`Train No.`, `From`, `To`, `Departure`, `Platform`, `Status`) VALUES (?, ?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        if (!$stmt) {
            die("Query gagal: " . $conn->error);
        }
        $stmt->bind_param("ssssss", $trainNo, $from, $to, $departure, $platform, $status);

        // Eksekusi query
        if ($stmt->execute()) {
            echo "Data dari baris $row berhasil diimpor.<br>";
        } else {
            echo "Error pada baris $row: " . $stmt->error . "<br>";
        }
    }

    // Tutup koneksi
    $stmt->close();
    $checkStmt->close();
    $conn->close();
}

// Jika pengguna mengonfirmasi penggantian data
if (isset($_POST['confirm'])) {
    // Ambil data dari form
    $trainNo = $_POST['trainNo'];
    $from = $_POST['from'];
    $to = $_POST['to'];
    $departure = $_POST['departure'];
    $platform = $_POST['platform'];
    $status = $_POST['status'];

    // Persiapkan query untuk memperbarui data
    $updateSql = "UPDATE pids SET `From` = ?, `To` = ?, `Departure` = ?, `Platform` = ?, `Status` = ? WHERE `Train No.` = ?";
    $updateStmt = $conn->prepare($updateSql);
    if (!$updateStmt) {
        die("Query gagal: " . $conn->error);
    }
    $updateStmt->bind_param("ssssis", $from, $to, $departure, $platform, $status, $trainNo);
    
    if ($updateStmt->execute()) {
        echo "Data untuk Train No. $trainNo berhasil diperbarui.<br>";
    } else {
        echo "Error saat memperbarui data: " . $updateStmt->error . "<br>";
    }

    // Tutup koneksi
    $updateStmt->close();
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impor Data Excel</title>
</head>
<body>
    <h1>Impor Data Excel</h1>
    <form action="import.php" method="post" enctype="multipart/form-data">
        <input type="file" name="file" accept=".xlsx, .xls" required>
        <input type="submit" name="submit" value="Impor Data">
    </form>
</body>
</html>
