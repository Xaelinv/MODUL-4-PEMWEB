<?php
session_start();
include 'db_pemilu.php';

if (!isset($_SESSION['pemilih_id'])) {
    echo "Session tidak ditemukan.";
    exit;
}

$pemilih_id = $_SESSION['pemilih_id'];
$calon_id = $_GET['id'] ?? null;

if (!$calon_id) {
    echo "ID calon tidak valid.";
    exit;
}

// Cek apakah sudah memilih
$stmt = $conn->prepare("SELECT * FROM suara WHERE pemilih_id = ?");
$stmt->bind_param("s", $pemilih_id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows > 0) {
    echo "Anda sudah memilih!";
    exit;
}

// Simpan suara
$stmt = $conn->prepare("INSERT INTO suara (pemilih_id, calon_id) VALUES (?, ?)");
$stmt->bind_param("si", $pemilih_id, $calon_id);
if ($stmt->execute()) {
    echo "Terima kasih telah memilih!";
} else {
    echo "Gagal menyimpan suara.";
}
