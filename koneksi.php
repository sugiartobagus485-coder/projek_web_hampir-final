<?php
// ==========================================
//  KONEKSI DATABASE
//  Belum pernah jalan? Buka dulu:
//  http://localhost/ProjekHtmlRevisiiii/install.php
//  nanti tabel-tabelnya dibuat otomatis.
// ==========================================

$host = "localhost";
$user = "root";
$pass = "";
$db   = "registrasi";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    header("Content-Type: application/json");
    echo json_encode([
        "status"  => "error",
        "message" => "Koneksi database gagal: " . mysqli_connect_error() .
                     " | Nyalakan MySQL di XAMPP, lalu buka install.php"
    ]);
    exit;
}
