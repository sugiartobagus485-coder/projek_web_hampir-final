<?php
// ==========================================
//  API_PRODUK_HAPUS.PHP - Hapus produk
//  Cara pakai: api_produk_hapus.php?id=3
// ==========================================

header("Content-Type: application/json");

include "config.php";
include "koneksi.php";
include "auth.php";

wajib_login_api('admin');

$id = (int)($_GET['id'] ?? 0);

$cek = mysqli_prepare($conn, "SELECT ID FROM produk WHERE ID = ?");
mysqli_stmt_bind_param($cek, "i", $id);
mysqli_stmt_execute($cek);
$ada = mysqli_stmt_get_result($cek);

if (!$ada || mysqli_num_rows($ada) === 0) {
    echo json_encode([
        "status"  => "error",
        "message" => "Produk tidak ditemukan."
    ]);
    exit;
}

$hapus = mysqli_prepare($conn, "DELETE FROM produk WHERE ID = ?");
mysqli_stmt_bind_param($hapus, "i", $id);

if (mysqli_stmt_execute($hapus)) {
    echo json_encode([
        "status"  => "success",
        "message" => "Produk berhasil dihapus!"
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Gagal menghapus produk."
    ]);
}
