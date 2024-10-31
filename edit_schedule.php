<?php
// Konfigurasi database
$host = 'localhost';
$user = 'root'; // Sesuaikan dengan username database Anda
$password = ''; // Sesuaikan dengan password database Anda
$database = 'transport';

$conn = new mysqli($host, $user, $password, $database);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$message = ""; // Variabel untuk menyimpan pesan

// Tangani permintaan POST
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $trainNo = $_POST['train_no'];
    $from = $_POST['from'];
    $to = $_POST['to'];
    $departure = $_POST['departure'];
    $platform = $_POST['platform'];
    $status = $_POST['status'];

    // Cek apakah ID (train_no) sudah ada di database
    $checkId = $conn->prepare("SELECT COUNT(*) FROM pids WHERE `Train No.` = ?");
    $checkId->bind_param("s", $trainNo);
    $checkId->execute();
    $checkId->bind_result($idCount);
    $checkId->fetch();
    $checkId->close();

    if ($idCount > 0) {
        $message = "Error: No. Kereta sudah ada dalam database.";
    } else {
        // Jika ID belum ada, lakukan INSERT
        $stmt = $conn->prepare("INSERT INTO pids (`Train No.`, `From`, `To`, `Departure`, `Platform`, `Status`) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $trainNo, $from, $to, $departure, $platform, $status);

        if ($stmt->execute()) {
            $message = "Data berhasil ditambahkan!";
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Schedule</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            width: 400px;
        }

        h2 {
            text-align: center;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            display: block;
            margin-bottom: 5px;
        }

        input[type="text"], input[type="datetime-local"] {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .btn {
            width: 100%;
            padding: 10px;
            background-color: #007bff;
            border: none;
            color: white;
            font-size: 16px;
            cursor: pointer;
            border-radius: 4px;
            margin-top: 10px;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        .message {
            color: red;
            text-align: center;
            margin-top: 15px;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Tambah / Edit Jadwal Keberangkatan</h2>
    <form method="POST" action="">
        <div class="form-group">
            <label for="train_no">No. Kereta</label>
            <input type="text" id="train_no" name="train_no" required>
        </div>
        <div class="form-group">
            <label for="from">Stasiun Awal</label>
            <input type="text" id="from" name="from" required>
        </div>
        <div class="form-group">
            <label for="to">Stasiun Akhir</label>
            <input type="text" id="to" name="to" required>
        </div>
        <div class="form-group">
            <label for="departure">Waktu Keberangkatan</label>
            <input type="datetime-local" id="departure" name="departure" required>
        </div>
        <div class="form-group">
            <label for="platform">Peron</label>
            <input type="text" id="platform" name="platform" required>
        </div>
        <div class="form-group">
            <label for="status">Status</label>
            <input type="text" id="status" name="status" required>
        </div>
        <button type="submit" class="btn">Simpan</button>
    </form>
    <?php if ($message): ?>
        <div class="message"><?php echo $message; ?></div>
    <?php endif; ?>
</div>

</body>
</html>
