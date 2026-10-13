<?php
// ==========================================
//  INSTALL.PHP - Pembuat tabel database otomatis
//  Buka satu kali lewat browser:
//  http://localhost/ProjekHtmlRevisiiii/install.php
//  Setelah sukses, halaman ini boleh dihapus.
// ==========================================

$host = "localhost";
$user = "root";
$pass = "";
$db   = "registrasi";

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die("<h2>Gagal konek MySQL</h2><p>" . mysqli_connect_error() .
        "</p><p>Pastikan MySQL sudah <b>Start</b> di XAMPP Control Panel.</p>");
}

// Usahakan database & tabel user lama tetap ada (kalau belum ada, dibuatkan)
$tabel = [];

$tabel[] = "CREATE TABLE IF NOT EXISTS regis (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    USERNAME VARCHAR(100) NOT NULL,
    EMAIL VARCHAR(100) NOT NULL,
    PASSWORD VARCHAR(255) NOT NULL
)";

// Produk / kode bahasa pemrograman yang dijual
$tabel[] = "CREATE TABLE IF NOT EXISTS produk (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    NAMA VARCHAR(150) NOT NULL,
    BAHASA VARCHAR(60) NOT NULL,
    KATEGORI VARCHAR(60) DEFAULT 'Lainnya',
    DESKRIPSI TEXT,
    HARGA INT NOT NULL DEFAULT 0,
    TINGKAT ENUM('Mudah','Menengah','Sulit') NOT NULL DEFAULT 'Mudah',
    GAMBAR VARCHAR(255) DEFAULT '🐍',
    STOK INT NOT NULL DEFAULT 1,
    DIBUAT TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Pesanan dari user
$tabel[] = "CREATE TABLE IF NOT EXISTS pesanan (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    USER_ID VARCHAR(100) DEFAULT NULL,
    NAMA_PEMBELI VARCHAR(150) NOT NULL,
    NO_WA VARCHAR(30) NOT NULL,
    CATATAN TEXT,
    TOTAL INT NOT NULL DEFAULT 0,
    STATUS ENUM('Menunggu','Diproses','Selesai','Dibatalkan') NOT NULL DEFAULT 'Menunggu',
    DIBUAT TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Rincian isi pesanan (satu pesanan bisa banyak produk)
$tabel[] = "CREATE TABLE IF NOT EXISTS detail_pesanan (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    PESANAN_ID INT NOT NULL,
    PRODUK_ID INT NOT NULL,
    NAMA VARCHAR(150) NOT NULL,
    HARGA INT NOT NULL,
    QTY INT NOT NULL DEFAULT 1
)";

// Pengaturan toko: nomor WA, sandi admin
$tabel[] = "CREATE TABLE IF NOT EXISTS setelan (
    ID INT PRIMARY KEY,
    NO_WA VARCHAR(30) NOT NULL DEFAULT '6281234567890',
    ADMIN_USER VARCHAR(60) NOT NULL DEFAULT 'admin',
    ADMIN_PASS VARCHAR(255) NOT NULL
)";

// Notifikasi untuk admin (pesanan baru, dll)
$tabel[] = "CREATE TABLE IF NOT EXISTS notifikasi (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    PESANAN_ID INT DEFAULT NULL,
    PESANAN_NO VARCHAR(50),
    NAMA_PEMBELI VARCHAR(150),
    PESANAN_TOTAL INT DEFAULT 0,
    PESANAN_STATUS VARCHAR(20) DEFAULT 'Menunggu',
    ISI TEXT,
    STATUS ENUM('Baru','Dibaca') NOT NULL DEFAULT 'Baru',
    DIBUAT TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Percobaan login (rate-limit anti brute-force)
$tabel[] = "CREATE TABLE IF NOT EXISTS login_attempts (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    USERNAME VARCHAR(100),
    SUKSES TINYINT(1) DEFAULT 0,
    WAKTU TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

// Isi pengaturan bawaan kalau belum ada (password: admin123)
$hashBawaan = password_hash('admin123', PASSWORD_DEFAULT);
$tabel[] = "INSERT IGNORE INTO setelan (ID, NO_WA, ADMIN_USER, ADMIN_PASS)
            VALUES (1, '6281234567890', 'admin', '$hashBawaan')";

// Contoh produk biar katalog tidak kosong
$tabel[] = "INSERT INTO produk (NAMA, BAHASA, DESKRIPSI, HARGA, TINGKAT, GAMBAR, STOK)
            SELECT * FROM (
                SELECT 'Source Code Game Ular 2D' AS a, 'Python' AS b,
                       'Kode lengkap game ular pakai Pygame. Ada skor, level, dan suara. Cocok buat tugas sekolah.' AS c,
                       75000 AS d, 'Mudah' AS e, '🐍' AS f, 10 AS g
            ) AS tmp
            WHERE NOT EXISTS (SELECT NAMA FROM produk WHERE NAMA = 'Source Code Game Ular 2D')";

$tabel[] = "INSERT INTO produk (NAMA, BAHASA, DESKRIPSI, HARGA, TINGKAT, GAMBAR, STOK)
            SELECT * FROM (
                SELECT 'Template Platformer 2D' AS a, 'C++ / Raylib' AS b,
                       'Project platformer siap pakai: player, musuh, koin, dan 3 level.' AS c,
                       120000 AS d, 'Menengah' AS e, '⚡' AS f, 5 AS g
            ) AS tmp
            WHERE NOT EXISTS (SELECT NAMA FROM produk WHERE NAMA = 'Template Platformer 2D')";

$tabel[] = "INSERT INTO produk (NAMA, BAHASA, DESKRIPSI, HARGA, TINGKAT, GAMBAR, STOK)
            SELECT * FROM (
                SELECT 'Starter Kit Unity 3D' AS a, 'C# / Unity' AS b,
                       'Aset dan scene dasar untuk mulai game 3D: kontrol player, kamera, dan UI.' AS c,
                       150000 AS d, 'Sulit' AS e, '🎮' AS f, 5 AS g
            ) AS tmp
            WHERE NOT EXISTS (SELECT NAMA FROM produk WHERE NAMA = 'Starter Kit Unity 3D')";

$tabel[] = "INSERT INTO produk (NAMA, BAHASA, DESKRIPSI, HARGA, TINGKAT, GAMBAR, STOK)
            SELECT * FROM (
                SELECT 'Kumpulan Fungsi SDL2' AS a, 'C++ / SDL2' AS b,
                       'Snippet kode SDL2: render, input, audio, dan tilemap penjelasan Bahasa Indonesia.' AS c,
                       90000 AS d, 'Menengah' AS e, '🔷' AS f, 8 AS g
            ) AS tmp
            WHERE NOT EXISTS (SELECT NAMA FROM produk WHERE NAMA = 'Kumpulan Fungsi SDL2')";

$gagal = 0;
foreach ($tabel as $sql) {
    if (!mysqli_query($conn, $sql)) {
        $gagal++;
        echo "<p style='color:red'>Gagal: " . h(mysqli_error($conn)) . "</p>";
    }
}

// Untuk database lama: tambahkan kolom USER_ID kalau belum ada.
// Kalau sudah ada, errornya diabaikan (tidak dihitung gagal).
@mysqli_query($conn, "ALTER TABLE pesanan ADD COLUMN USER_ID VARCHAR(100) DEFAULT NULL");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Instalasi GameDev</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; padding: 40px; }
        .kotak { max-width: 620px; margin: auto; background: #fff; padding: 30px;
                 border-radius: 14px; box-shadow: 0 8px 24px rgba(0,0,0,.08); }
        h1 { color: #2d6a4f; }
        .ok { background: #d3ffea; color: #1b4332; padding: 12px 16px;
              border-radius: 10px; font-weight: 600; }
        a.tombol { display: inline-block; margin-top: 18px; background: #2d6a4f; color: #fff;
                   padding: 12px 20px; border-radius: 8px; text-decoration: none; font-weight: 600; }
        ul { line-height: 1.9; color: #555; }
    </style>
</head>
<body>
<div class="kotak">
    <h1>Instalasi Database GameDev</h1>

    <?php if ($gagal === 0): ?>
        <p class="ok">Semua tabel berhasil dibuat / sudah ada.</p>
        <ul>
            <li>Tabel <b>produk</b> &mdash; daftar kode yang dijual</li>
            <li>Tabel <b>pesanan</b> &amp; <b>detail_pesanan</b> &mdash; riwayat pembelian</li>
            <li>Tabel <b>setelan</b> &mdash; nomor WA &amp; sandi admin</li>
        </ul>
        <p><b>Login Admin:</b> username <b>admin</b> / password <b>admin123</b><br>
           (bisa diganti nanti di halaman Pengaturan Admin)</p>
        <a class="tombol" href="home.html">Buka Website</a>
        <a class="tombol" href="login.php">Ke Halaman Login</a>
    <?php else: ?>
        <p>Ada <?= $gagal ?> yang gagal. Coba refresh halaman ini, atau periksa
           apakah MySQL sudah aktif di XAMPP.</p>
    <?php endif; ?>
</div>
</body>
</html>
