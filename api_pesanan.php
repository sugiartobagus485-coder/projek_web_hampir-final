<?php
// ==========================================
//  API_PESANAN.PHP
//  GET  -> lihat pesanan (admin: semua, user: miliknya)
//  POST -> buat pesanan baru (user / tamu)
// ==========================================

header("Content-Type: application/json");

include "config.php";
include "koneksi.php";
include "auth.php";
include "toko.php";

/** Susun rincian produk di dalam pesanan */
function rincianPesanan($conn, $idPesanan)
{
    $id = (int)$idPesanan;
    $q  = mysqli_query($conn, "SELECT * FROM detail_pesanan WHERE PESANAN_ID = $id");
    $isi = [];
    while ($d = mysqli_fetch_assoc($q)) {
        $isi[] = [
            "id"     => (int)$d['PRODUK_ID'],
            "nama"   => $d['NAMA'],
            "harga"  => (int)$d['HARGA'],
            "qty"    => (int)$d['QTY']
        ];
    }
    return $isi;
}

// ---------------- BACA ----------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $role = $_SESSION['role'] ?? '';

    if ($role === 'admin') {
        $sql = "SELECT * FROM pesanan ORDER BY DIBUAT DESC";
    } elseif ($role === 'user') {
        $nama = mysqli_real_escape_string($conn, $_SESSION['nama']);
        $sql  = "SELECT * FROM pesanan
                 WHERE USER_ID = '$nama' OR NAMA_PEMBELI = '$nama'
                 ORDER BY DIBUAT DESC";
    } else {
        echo json_encode([
            "status"  => "error",
            "message" => "Silakan login dulu untuk melihat pesanan."
        ]);
        exit;
    }

    $hasil    = mysqli_query($conn, $sql);
    $pesanan  = [];

    while ($p = mysqli_fetch_assoc($hasil)) {
        $pesanan[] = [
            "id"      => (int)$p['ID'],
            "nama"    => $p['NAMA_PEMBELI'],
            "wa"      => $p['NO_WA'],
            "catatan" => $p['CATATAN'],
            "total"   => (int)$p['TOTAL'],
            "status"  => $p['STATUS'],
            "tanggal" => $p['DIBUAT'],
            "isi"     => rincianPesanan($conn, $p['ID'])
        ];
    }

    echo json_encode([
        "status"  => "success",
        "pesanan" => $pesanan
    ]);
    exit;
}

// ---------------- BUAT PESANAN BARU ----------------
$data = json_decode(file_get_contents("php://input"), true);

$nama    = trim($data['nama'] ?? '');
$wa      = trim($data['wa'] ?? '');
$catatan = trim($data['catatan'] ?? '');
$userId  = trim($data['user_id'] ?? '');
$items   = $data['items'] ?? [];

// Rapikan & validasi nomor WhatsApp
$wa = preg_replace('/[^0-9]/', '', $wa);
// Ganti prefix 0 dengan 62 (contoh: 0812... -> 62812...)
if (substr($wa, 0, 1) === '0') {
    $wa = '62' . substr($wa, 1);
}
if (substr($wa, 0, 3) !== '628' || strlen($wa) < 10 || strlen($wa) > 13) {
    echo json_encode([
        "status"  => "error",
        "message" => "Nomor WhatsApp tidak valid. Gunakan format 08xx."
    ]);
    exit;
}

if ($nama === '' || $wa === '' || empty($items)) {
    echo json_encode([
        "status"  => "error",
        "message" => "Nama, nomor WhatsApp, dan isi pesanan wajib diisi!"
    ]);
    exit;
}

// Kalau yang login user, pesanan otomatis atas nama akunnya
if (isset($_SESSION['login']) && $_SESSION['role'] === 'user') {
    $userId = $_SESSION['nama'];
}

// Hitung total di server (jangan percaya harga dari browser)
$total = 0;
foreach ($items as $item) {
    $total += (int)($item['harga'] ?? 0) * (int)($item['qty'] ?? 1);
}

mysqli_begin_transaction($conn);

try {
    $sql = "INSERT INTO pesanan (USER_ID, NAMA_PEMBELI, NO_WA, CATATAN, TOTAL, STATUS)
            VALUES (?, ?, ?, ?, ?, 'Menunggu')";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ssssi", $userId, $nama, $wa, $catatan, $total);
    mysqli_stmt_execute($stmt);

    $idPesanan = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);

    foreach ($items as $item) {
        $idProduk = (int)($item['id'] ?? 0);
        $namaProd = substr($item['nama'] ?? '', 0, 150);
        $harga    = (int)($item['harga'] ?? 0);
        $qty      = max(1, (int)($item['qty'] ?? 1));

        $sqlDetail = "INSERT INTO detail_pesanan (PESANAN_ID, PRODUK_ID, NAMA, HARGA, QTY)
                      VALUES (?, ?, ?, ?, ?)";
        $stmtDetail = mysqli_prepare($conn, $sqlDetail);
        mysqli_stmt_bind_param($stmtDetail, "iisii", $idPesanan, $idProduk,
                               $namaProd, $harga, $qty);
        mysqli_stmt_execute($stmtDetail);
        mysqli_stmt_close($stmtDetail);
    }

    mysqli_commit($conn);

    // Buat notifikasi untuk admin
    $noPesanan   = '#' . $idPesanan;
    $isiNotif    = "Pesanan baru " . $noPesanan . " dari " . $nama .
                   " (" . rupiah($total) . ")";
    $statusNotif = 'Menunggu';
    $sqlNotif    = "INSERT INTO notifikasi
                    (PESANAN_ID, PESANAN_NO, NAMA_PEMBELI, PESANAN_TOTAL,
                     PESANAN_STATUS, ISI)
                    VALUES (?, ?, ?, ?, ?, ?)";
    $stmtNotif   = mysqli_prepare($conn, $sqlNotif);
    mysqli_stmt_bind_param($stmtNotif, "isisis", $idPesanan, $noPesanan,
                           $nama, $total, $statusNotif, $isiNotif);
    mysqli_stmt_execute($stmtNotif);
    mysqli_stmt_close($stmtNotif);

    // Susun pesan WhatsApp yang rapi
    $isi  = "Halo Admin GameDev, saya mau pesan:%0A";
    $isi .= "No. Pesanan: " . $idPesanan . "%0A";
    $isi .= "Nama: " . rawurlencode($nama) . "%0A";
    $isi .= "No. WA: " . rawurlencode($wa) . "%0A%0A";
    $isi .= "Daftar pesanan:%0A";
    foreach ($items as $item) {
        $sub = (int)($item['harga'] ?? 0) * (int)($item['qty'] ?? 1);
        $isi .= "- " . rawurlencode($item['nama'] ?? '') . " x"
              . (int)($item['qty'] ?? 1) . " (" . rupiah($sub) . ")%0A";
    }
    $isi .= "%0ATotal: " . rawurlencode(rupiah($total));
    if ($catatan !== '') {
        $isi .= "%0ACatatan: " . rawurlencode($catatan);
    }

    echo json_encode([
        "status"     => "success",
        "message"    => "Pesanan berhasil dibuat!",
        "id_pesanan" => $idPesanan,
        "total"      => rupiah($total),
        "link_wa"    => "https://wa.me/" . no_wa($conn) . "?text=" . $isi
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    echo json_encode([
        "status"  => "error",
        "message" => "Gagal menyimpan pesanan: " . $e->getMessage()
    ]);
}
