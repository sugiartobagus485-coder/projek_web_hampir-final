<?php
// ==========================================================
//  API_NOTIFIKASI.PHP - Notifikasi untuk admin
//  GET  -> ambil daftar notifikasi + jumlah belum dibaca
//  POST -> tandai semua sudah dibaca
// ==========================================================

header("Content-Type: application/json");

include "config.php";
include "koneksi.php";
include "auth.php";
include "toko.php";

wajib_login_api('admin');

// Tandai semua sudah dibaca
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mysqli_query($conn, "UPDATE notifikasi SET STATUS = 'Dibaca'");
    echo json_encode([
        "status"  => "success",
        "message" => "Semua notifikasi ditandai sudah dibaca."
    ]);
    exit;
}

// Ambil notifikasi terbaru
$hasil  = mysqli_query($conn, "SELECT * FROM notifikasi ORDER BY ID DESC LIMIT 20");
$daftar = [];

while ($n = mysqli_fetch_assoc($hasil)) {
    $daftar[] = [
        "id"              => (int)$n['ID'],
        "pesanan_no"      => $n['PESANAN_NO'],
        "nama"            => $n['NAMA_PEMBELI'],
        "total"           => (int)$n['PESANAN_TOTAL'],
        "status_pesanan"  => $n['PESANAN_STATUS'],
        "isi"             => $n['ISI'],
        "status"          => $n['STATUS'],
        "tanggal"         => $n['DIBUAT']
    ];
}

// Jumlah yang belum dibaca
$belum = (int)mysqli_fetch_row(mysqli_query($conn,
    "SELECT COUNT(*) FROM notifikasi WHERE STATUS = 'Baru'"))[0];

echo json_encode([
    "status"        => "success",
    "belum_dibaca"  => $belum,
    "notifikasi"    => $daftar
]);
