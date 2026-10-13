<?php
// ==========================================
//  API_LOGIN.PHP - Endpoint login (JSON)
//  Dipanggil oleh login.html lewat fetch
// ==========================================

header("Content-Type: application/json");

include "config.php";
include "koneksi.php";
include "auth.php";
include "toko.php";

$data = json_decode(file_get_contents("php://input"), true);

$username = trim($data['username'] ?? '');
$password = $data['password'] ?? '';

if ($username === '' || $password === '') {
    echo json_encode([
        "status"  => "error",
        "message" => "Username dan password wajib diisi!"
    ]);
    exit;
}

// ---------- RATE-LIMIT: cegah brute-force ----------
// Hitung percobaan gagal 15 menit terakhir untuk username ini
$sejak   = date('Y-m-d H:i:s', strtotime('-15 minutes'));
$stmtCek = mysqli_prepare($conn, "SELECT COUNT(*) FROM login_attempts
                                    WHERE USERNAME = ? AND SUKSES = 0 AND WAKTU > ?");
mysqli_stmt_bind_param($stmtCek, "ss", $username, $sejak);
mysqli_stmt_execute($stmtCek);
$hasilCek = mysqli_stmt_get_result($stmtCek);
$gagalTerakhir = (int)mysqli_fetch_row($hasilCek)[0];
mysqli_stmt_close($stmtCek);

if ($gagalTerakhir >= 5) {
    echo json_encode([
        "status"  => "error",
        "message" => "Terlalu banyak percobaan gagal. Coba lagi 15 menit lagi."
    ]);
    exit;
}

// ---------- 1. Cek apakah ini ADMIN ----------
$kred = kredensial_admin($conn);
$hash = $kred['hash'];

$cocokAdmin = ($username === $kred['user']) &&
              ($hash !== '' ? password_verify($password, $hash)
                             : ($password === ADMIN_PASS));

if ($cocokAdmin) {
    $_SESSION['login'] = true;
    $_SESSION['role']  = 'admin';
    $_SESSION['nama']  = $kred['user'];

    echo json_encode([
        "status"  => "success",
        "message" => "Login admin berhasil!",
        "role"    => "admin",
        "nama"    => $kred['user'],
        "tujuan"  => "admin.html"
    ]);
    exit;
}

// ---------- 2. Cek apakah ini USER terdaftar ----------
$sql = "SELECT USERNAME, EMAIL, PASSWORD FROM regis WHERE USERNAME = ? OR EMAIL = ? LIMIT 1";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "ss", $username, $username);
    mysqli_stmt_execute($stmt);
    $akun = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($akun && password_verify($password, $akun['PASSWORD'])) {
        $_SESSION['login']    = true;
        $_SESSION['role']     = 'user';
        $_SESSION['user_id']  = $akun['USERNAME'];
        $_SESSION['nama']     = $akun['USERNAME'];
        $_SESSION['email']    = $akun['EMAIL'];

        echo json_encode([
            "status"  => "success",
            "message" => "Login berhasil!",
            "role"    => "user",
            "nama"    => $akun['USERNAME'],
            "tujuan"  => "dashboard.html"
        ]);
        exit;
    }
}

// Catat percobaan gagal (untuk rate-limit)
$stmtSimpan = mysqli_prepare($conn, "INSERT INTO login_attempts (USERNAME, SUKSES) VALUES (?, 0)");
mysqli_stmt_bind_param($stmtSimpan, "s", $username);
mysqli_stmt_execute($stmtSimpan);
mysqli_stmt_close($stmtSimpan);

echo json_encode([
    "status"  => "error",
    "message" => "Username atau password salah!"
]);
