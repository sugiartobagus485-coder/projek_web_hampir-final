<?php

header("Content-Type: application/json");

include "koneksi.php";

// Ambil data dari JavaScript
$data = json_decode(file_get_contents("php://input"), true);

// Cek data
if (
    !$data ||
    !isset($data["nama"]) ||
    !isset($data["email"]) ||
    !isset($data["password"])
) {
    echo json_encode([
        "status" => "error",
        "message" => "Data tidak lengkap!"
    ]);
    exit;
}

$nama     = $data["nama"];
$email    = $data["email"];
$password = $data["password"];

// Hash password (sebelumnya salah: pakai password_verify, jadi password tidak pernah bisa dipakai login)
$passwordHash = password_hash($password, PASSWORD_DEFAULT);

// Query database
$sql = "INSERT INTO regis (USERNAME, EMAIL, PASSWORD) VALUES (?, ?, ?)";
$stmt = mysqli_prepare($conn, $sql);

if (!$stmt) {
    echo json_encode([
        "status" => "error",
        "message" => "Query database gagal: " . mysqli_error($conn)
    ]);
    exit;
}

mysqli_stmt_bind_param($stmt, "sss", $nama, $email, $passwordHash);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "status" => "success",
        "message" => "Registrasi Berhasil!"
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Gagal menyimpan data: " . mysqli_stmt_error($stmt)
    ]);
}

mysqli_stmt_close($stmt);
mysqli_close($conn);

?>