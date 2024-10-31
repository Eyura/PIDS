<?php
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

// Update status menjadi "Close" jika waktu keberangkatan tinggal 2 menit
$updateStatusSql = "UPDATE pids SET Status = 'Close' WHERE Departure BETWEEN NOW() AND NOW() + INTERVAL 5 MINUTE";
$conn->query($updateStatusSql);

// Ambil data keberangkatan, hanya 10 baris pertama
$sql = "SELECT * FROM pids WHERE Departure > NOW() ORDER BY Departure LIMIT 10";
$result = $conn->query($sql);

// Memeriksa jumlah data saat ini
session_start();
$currentCount = $result->num_rows;

// Simpan jumlah data saat ini ke dalam sesi untuk perbandingan
if (!isset($_SESSION['lastCount'])) {
    $_SESSION['lastCount'] = $currentCount;
}

// Cek apakah ada data baru
if ($currentCount > $_SESSION['lastCount']) {
    $_SESSION['lastCount'] = $currentCount; // Update jumlah data terakhir
    echo "<script>
        location.reload(); // Muat ulang halaman jika ada data baru
    </script>";
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Keberangkatan</title>
    <style>
        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            display: flex;
            flex-direction: column;
            background-color: #0827F5;
            color: white;
            font-family: Arial, sans-serif;
        }

        .table-container {
            flex: 1;
            overflow: auto;
            padding: 0;
        }

        table {
            width: 100%;
            height: auto;
            margin: 10px auto;
            border-collapse: collapse;
            font-size: 40px;
        }

        ::-webkit-scrollbar {
            display: none;
        }

        th, td {
            border: 1px solid #333;
            padding: 10px;
            text-align: center;
        }
        
        th {
            background-color: #0723DB;
            color: white;
        }
        
        td {
            color: white;
        }

        tr:nth-child(odd) {
            background-color: #0723DB;
        }
        
    

        table, th, td {
            border: none;
        }


        .running-text {
            position: absolute;
            overflow: hidden;
            white-space: nowrap;
            box-sizing: border-box;
            animation: marquee 50s linear infinite;
            font-size: 24px;
            bottom: 0;
            left: 0;
            width: 100%; 
            color: yellow;
        }
        
        @keyframes marquee {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }

        .status-text {
            max-width: 100%;
            overflow: hidden;
            white-space: nowrap;
            animation: marquee-status 10s linear infinite;
            
        }

        @keyframes marquee-status {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-40%); }
        }

        td.status-column {
            overflow: hidden;
        }

        .current-time {
            text-align: center;
            font-size: 50px;
            color: #FFFFFF;
            margin-top: 10px;
            margin-bottom: 0;
        }

        .running-box {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 10px;
            background-color: #0827F5;
            text-align: center;
            padding: 10px;
        }

        .footer-text {
            font-size: 24px;
            color: white;
        }
    </style>
</head>
<body>
    <h1 class="current-time" id="current-time"></h1>
    
    <div class="table-container">
        <table>
            <tr>
                <td id="train-no" style="width: 13%;">No. Kereta</td>
                <td id="start-station" style="width: 27%;">Stasiun Awal</td>
                <td id="end-station" style="width: 27%;">Stasiun Akhir</td>
                <td id="departure-time" style="width: 10%;">Keberangkatan</td>
                <td id="platform" style="width: 5%;">Peron</td>
                <td id="status" style="width: 16%;">Status</td>
            </tr>
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $row['Train No.']; ?></td>
                        <td><?php echo $row['From']; ?></td>
                        <td><?php echo $row['To']; ?></td>
                        <td><?php echo date('H:i:s', strtotime($row['Departure'])); ?></td>
                        <td><?php echo $row['Platform']; ?></td>
                        <td class="status-column"><div class="status-text"><?php echo $row['Status']; ?></div></td>
                    </tr>
                <?php endwhile; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6">Tidak ada data keberangkatan.</td>
                    <td rowspan="10"></td>
                </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <div class="running-box">
        <footer class="running-text">Jadwal Keberangkatan----indopride----WARLOK</footer>
    </div>
    
    <?php $conn->close(); ?>
    
    <script>
        

        const texts = {
            id: {
                trainNo: "No. Kereta",
                startStation: "Stasiun Awal",
                endStation: "Stasiun Akhir",
                departureTime: "Keberangkatan",
                platform: "Peron",
                status: "Status",
                footer: "Jadwal Keberangkatan ---- WARLOK---- Jadwal Keberangkatan"
            },
            en: {
                trainNo: "Train No.",
                startStation: "From",
                endStation: "To",
                departureTime: "Departure",
                platform: "Platform",
                status: "Status",
                footer: "Departure Schedule ---- LOCAL PRIDE ---- Departure Schedule"
            }
        };

        let currentLang = "id";

        function updateLanguage() {
            const langTexts = texts[currentLang];
            
            document.getElementById("train-no").textContent = langTexts.trainNo;
            document.getElementById("start-station").textContent = langTexts.startStation;
            document.getElementById("end-station").textContent = langTexts.endStation;
            document.getElementById("departure-time").textContent = langTexts.departureTime;
            document.getElementById("platform").textContent = langTexts.platform;
            document.getElementById("status").textContent = langTexts.status;
            document.querySelector(".running-text").textContent = langTexts.footer;

            currentLang = currentLang === "id" ? "en" : "id";
        }

        setInterval(updateLanguage, 10000);

        function updateTime() {
            const now = new Date();
            const options = { hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: false };
            document.getElementById('current-time').textContent = now.toLocaleTimeString('id-ID', options);
        }

        setInterval(updateTime, 1000);
        updateTime();


        // Refresh halaman setiap 60 detik
        setTimeout(() => {
            location.reload();
        }, 60000); // 60 detik (1 menit)
    </script>
</body>
</html>
