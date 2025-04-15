<?php
include 'db_pemilu.php';

$data = [];
$label = [];
$jumlah = [];

$res = $conn->query("SELECT calon.nama, COUNT(suara.calon_id) AS jumlah 
                     FROM calon 
                     LEFT JOIN suara ON calon.id = suara.calon_id 
                     GROUP BY calon.id");

while ($row = $res->fetch_assoc()) {
    $label[] = $row['nama'];
    $jumlah[] = $row['jumlah'];
}

echo json_encode(['label' => $label, 'suara' => $jumlah]);
