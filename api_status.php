<?php
// ==========================================
//  API_STATUS.PHP - Siapa yang sedang login?
//  Dipanggil setiap halaman HTML lewat app.js
// ==========================================

header("Content-Type: application/json");

include "config.php";
include "koneksi.php";
include "auth.php";
include "toko.php";

echo json_encode([
    "login"  => isset($_SESSION['login']),
    "role"   => $_SESSION['role']  ?? '',
    "nama"   => $_SESSION['nama']  ?? '',
    "email"  => $_SESSION['email'] ?? '',
    "no_wa"  => no_wa($conn)
]);
