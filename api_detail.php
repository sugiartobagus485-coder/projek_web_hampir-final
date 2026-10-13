<?php
// ==========================================================
//  API_DETAIL.PHP - Detail satu produk + produk terkait
// ==========================================================

header("Content-Type: application/json");

include "config.php";
include "koneksi.php";
include "auth.php";
include "toko.php";

$id = (int)($_GET['id'] ?? 0);

$stmt = mysqli_prepare($conn, "SELECT * FROM produk WHERE ID = ?");
mysqli_stmt_bind_param($stmt, "i", $id);
mysqli_stmt_execute($stmt);
$hasil = mysqli_stmt_get_result($stmt);
$p = mysqli_fetch_assoc($hasil);
mysqli_stmt_close($stmt);

if (!$p) {
    http_response_code(404);
    echo json_encode([
        "status"  => "error",
        "message" => "Produk tidak ditemukan."
    ]);
    exit;
}

// Produk terkait (kategori sama, bukan produk ini)
$stmt2 = mysqli_prepare($conn, "SELECT ID, NAMA, BAHASA, HARGA, TINGKAT, GAMBAR
                                 FROM produk
                                 WHERE KATEGORI = ? AND ID != ?
                                 ORDER BY ID DESC LIMIT 4");
mysqli_stmt_bind_param($stmt2, "si", $p['KATEGORI'], $id);
mysqli_stmt_execute($stmt2);
$hasil2  = mysqli_stmt_get_result($stmt2);
$terkait = [];

while ($r = mysqli_fetch_assoc($hasil2)) {
    $terkait[] = [
        "id"      => (int)$r['ID'],
        "nama"    => $r['NAMA'],
        "bahasa"  => $r['BAHASA'],
        "harga"   => (int)$r['HARGA'],
        "tingkat" => $r['TINGKAT'],
        "gambar"  => $r['GAMBAR']
    ];
}
mysqli_stmt_close($stmt2);

echo json_encode([
    "status"  => "success",
    "produk"  => [
        "id"        => (int)$p['ID'],
        "nama"      => $p['NAMA'],
        "bahasa"    => $p['BAHASA'],
        "kategori"  => $p['KATEGORI'],
        "deskripsi" => $p['DESKRIPSI'],
        "harga"     => (int)$p['HARGA'],
        "tingkat"   => $p['TINGKAT'],
        "gambar"    => $p['GAMBAR'],
        "stok"      => (int)$p['STOK'],
        "dibuat"    => $p['DIBUAT']
    ],
    "terkait" => $terkait
]);
