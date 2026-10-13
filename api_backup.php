<?php
// ==========================================================
//  API_BACKUP.PHP - Backup seluruh database jadi file SQL
//  Cara pakai: buka lewat browser (harus login admin)
//  http://localhost/ProjekHtmlRevisiiii/api_backup.php
// ==========================================================

include "config.php";
include "koneksi.php";
include "auth.php";

wajib_login_api('admin');

$namaFile = "backup_gamedev_" . date('Y-m-d_His') . ".sql";

header("Content-Type: application/sql");
header("Content-Disposition: attachment; filename=\"" . $namaFile . "\"");

$semuaTabel = ['produk', 'pesanan', 'detail_pesanan', 'setelan',
               'regis', 'notifikasi', 'login_attempts'];

echo "-- ============================================\n";
echo "--  BACKUP DATABASE GAMEDEV\n";
echo "--  Tanggal : " . date('Y-m-d H:i:s') . "\n";
echo "-- ============================================\n\n";

foreach ($semuaTabel as $tabel) {

    // Struktur tabel
    $hasil = mysqli_query($conn, "SHOW CREATE TABLE `$tabel`");
    if ($hasil) {
        $baris = mysqli_fetch_row($hasil);
        echo "DROP TABLE IF EXISTS `$tabel`;\n";
        echo $baris[1] . ";\n\n";
    }

    // Isi tabel
    $data = mysqli_query($conn, "SELECT * FROM `$tabel`");
    if ($data && mysqli_num_rows($data) > 0) {
        while ($d = mysqli_fetch_assoc($data)) {
            $kolom = implode('`, `', array_keys($d));
            $nilai = implode("', '", array_map(function ($v) use ($conn) {
                return mysqli_real_escape_string($conn, $v ?? '');
            }, array_values($d)));
            echo "INSERT INTO `$tabel` (`$kolom`) VALUES ('$nilai');\n";
        }
        echo "\n";
    }
}

echo "-- SELESAI --\n";
