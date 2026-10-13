<?php
// ==========================================
//  API_PRODUK.PHP
//  GET  -> ambil daftar produk (semua boleh lihat)
//  POST -> tambah / edit produk (khusus admin)
// ==========================================

header("Content-Type: application/json");

include "config.php";
include "koneksi.php";
include "auth.php";
include "toko.php";

// ---------------- BACA ----------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    $keyword = trim($_GET['cari'] ?? '');
    $bahasa  = trim($_GET['bahasa'] ?? '');
    $kategori = trim($_GET['kategori'] ?? '');

    $kunci      = mysqli_real_escape_string($conn, $keyword);
    $bahasaAman = mysqli_real_escape_string($conn, $bahasa);
    $kategoriAman = mysqli_real_escape_string($conn, $kategori);

    $sql    = "SELECT * FROM produk";
    $where  = [];

    if ($keyword !== '') {
        $where[] = "(NAMA LIKE '%$kunci%' OR DESKRIPSI LIKE '%$kunci%')";
    }
    if ($bahasa !== '') {
        $where[] = "BAHASA LIKE '%$bahasaAman%'";
    }
    if ($kategori !== '') {
        $where[] = "KATEGORI = '$kategoriAman'";
    }
    if ($where) {
        $sql .= " WHERE " . implode(" AND ", $where);
    }

    // Urutkan
    $urut = trim($_GET['urut'] ?? '');
    switch ($urut) {
        case 'harga_asc':  $sql .= " ORDER BY HARGA ASC";  break;
        case 'harga_desc': $sql .= " ORDER BY HARGA DESC"; break;
        case 'terbaru':    $sql .= " ORDER BY ID DESC";    break;
        default:           $sql .= " ORDER BY ID DESC";    break;
    }

    $hasil  = mysqli_query($conn, $sql);
    $produk = [];

    while ($p = mysqli_fetch_assoc($hasil)) {
        $produk[] = [
            "id"        => (int)$p['ID'],
            "nama"      => $p['NAMA'],
            "bahasa"    => $p['BAHASA'],
            "kategori"  => $p['KATEGORI'],
            "deskripsi" => $p['DESKRIPSI'],
            "harga"     => (int)$p['HARGA'],
            "tingkat"   => $p['TINGKAT'],
            "gambar"    => $p['GAMBAR'],
            "stok"      => (int)$p['STOK']
        ];
    }

    // Daftar kategori untuk filter
    $daftarKategori = [];
    $q = mysqli_query($conn, "SELECT DISTINCT KATEGORI FROM produk ORDER BY KATEGORI");
    while ($b = mysqli_fetch_assoc($q)) {
        $daftarKategori[] = $b['KATEGORI'];
    }

    echo json_encode([
        "status"   => "success",
        "produk"   => $produk,
        "kategori" => $daftarKategori
    ]);
    exit;
}

// ---------------- TAMBAH / EDIT (admin) ----------------
wajib_login_api('admin');

$data = json_decode(file_get_contents("php://input"), true);

$id        = (int)($data['id'] ?? 0);
$nama      = trim($data['nama'] ?? '');
$bahasa    = trim($data['bahasa'] ?? '');
$kategori  = trim($data['kategori'] ?? 'Lainnya');
$deskripsi = trim($data['deskripsi'] ?? '');
$harga     = (int)($data['harga'] ?? 0);
$tingkat   = trim($data['tingkat'] ?? 'Mudah');
$gambar    = trim($data['gambar'] ?? '🐍');
$stok      = (int)($data['stok'] ?? 1);

if ($nama === '' || $bahasa === '' || $harga <= 0) {
    echo json_encode([
        "status"  => "error",
        "message" => "Nama, bahasa, dan harga wajib diisi!"
    ]);
    exit;
}

if (!in_array($tingkat, ['Mudah', 'Menengah', 'Sulit'], true)) {
    $tingkat = 'Mudah';
}
if ($gambar === '') {
    $gambar = '🐍';
}
if ($kategori === '') {
    $kategori = 'Lainnya';
}

if ($id > 0) {
    $sql = "UPDATE produk SET NAMA=?, BAHASA=?, KATEGORI=?, DESKRIPSI=?, HARGA=?,
            TINGKAT=?, GAMBAR=?, STOK=? WHERE ID=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssissiii", $nama, $bahasa, $kategori,
                           $deskripsi, $harga, $tingkat, $gambar, $stok, $id);
    $pesan = "Produk berhasil diupdate!";
} else {
    $sql = "INSERT INTO produk (NAMA, BAHASA, KATEGORI, DESKRIPSI, HARGA, TINGKAT, GAMBAR, STOK)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "sssissis", $nama, $bahasa, $kategori,
                           $deskripsi, $harga, $tingkat, $gambar, $stok);
    $pesan = "Produk baru berhasil ditambahkan!";
}

if ($stmt && mysqli_stmt_execute($stmt)) {
    echo json_encode(["status" => "success", "message" => $pesan]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Gagal menyimpan produk: " . mysqli_error($conn)
    ]);
}
