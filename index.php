<?php
session_start();
include 'db_pemilu.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['nik'])) {
    $nik = $_POST['nik'];
    $nama = $_POST['nama'];
    $alamat = $_POST['alamat'];

    // Simpan ke database (jika belum ada)
    $cek = $conn->prepare("SELECT * FROM pemilih WHERE nik = ?");
    $cek->bind_param("s", $nik);
    $cek->execute();
    $result = $cek->get_result();

    if ($result->num_rows == 0) {
        $stmt = $conn->prepare("INSERT INTO pemilih (nik, nama, alamat) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $nik, $nama, $alamat);
        $stmt->execute();
    }

    $_SESSION['pemilih_id'] = $nik;
}

$pemilih_id = $_SESSION['pemilih_id'] ?? null;

// Cek apakah sudah memilih
$hasVoted = false;
if ($pemilih_id) {
    $stmt = $conn->prepare("SELECT * FROM suara WHERE pemilih_id = ?");
    $stmt->bind_param("s", $pemilih_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $hasVoted = $result->num_rows > 0;
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>PEMILU</title>
    <link rel="stylesheet" href="style.css">
    <style>
        .btn-kembali {
            display: inline-block;
            margin-top: 10px;
            padding: 6px 14px;
            background-color: #2ecc71;
            color: white;
            font-size: 14px;
            text-decoration: none;
            border-radius: 4px;
            font-weight: 500;
            transition: background 0.3s ease;
        }
        .btn-kembali:hover {
            background-color: #27ae60;
        }
    </style>
</head>
<body>
<div class="container">
    <h2>PEMILIHAN UMUM</h2>

    <?php if (!$pemilih_id): ?>
        <!-- FORM LOGIN -->
        <form method="POST">
            <input type="text" name="nama" placeholder="Masukkan nama Anda" required><br>
            <input type="text" name="alamat" placeholder="Masukkan alamat Anda" required><br>
            <input type="text" name="nik" placeholder="Masukkan NIK Anda" required><br>
            <button type="submit">Login</button>
        </form>

    <?php elseif ($hasVoted): ?>
        <!-- SUDAH MEMILIH -->
        <p class="error" style="color: red; font-weight: bold;">Anda sudah memilih. Terima kasih!</p>

        <div id="chart-container">
            <h3>Grafik Hasil PEMILU</h3>
            <canvas id="chartPemilu"></canvas>
            <a href="logout.php" class="btn-kembali">Kembali ke Halaman Awal</a>
        </div>

    <?php else: ?>
        <!-- BELUM MEMILIH -->
        <p><strong>ID Anda:</strong> <?= htmlspecialchars($pemilih_id) ?></p>
        <form id="voteForm" onsubmit="submitVote(event)">
            <?php
            $result = $conn->query("SELECT * FROM calon");
            while ($row = $result->fetch_assoc()) {
                echo "<label><input type='radio' name='vote' value='{$row['id']}' required> {$row['nama']}</label><br>";
            }
            ?>
            <button type="submit">Submit Pilihan</button>
        </form>
        <div id="result"></div>
    <?php endif; ?>

    <?php if ($hasVoted): ?>
        <!-- TAMPILKAN GRAFIK HANYA SETELAH MEMILIH -->
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        function loadChart() {
            fetch("hasil.php")
                .then(res => res.json())
                .then(data => {
                    const ctx = document.getElementById('chartPemilu').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.label,
                            datasets: [{
                                label: 'Jumlah Suara',
                                data: data.suara,
                                backgroundColor: [
                                    '#3498db',
                                    '#e74c3c',
                                    '#2ecc71',
                                    '#f1c40f',
                                    '#9b59b6',
                                    '#e67e22'
                                ]
                            }]
                        },
                        options: {
                            responsive: true,
                            scales: {
                                y: { beginAtZero: true }
                            }
                        }
                    });
                });
        }
        loadChart();
        </script>
    <?php endif; ?>
</div>

<script>
function submitVote(e) {
    e.preventDefault();
    const selected = document.querySelector('input[name="vote"]:checked');
    if (!selected) return;

    var id = selected.value;

    fetch("vote.php?id=" + id)
        .then(response => response.text())
        .then(data => {
            document.getElementById("result").innerHTML = data;
            location.reload(); // Reload agar user dianggap sudah voting
        });
}
</script>
</body>
</html>