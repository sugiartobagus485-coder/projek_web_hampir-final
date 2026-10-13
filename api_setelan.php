<?php
// ==========================================
//  API_SETELAN.PHP
//  GET  -> baca pengaturan toko (admin)
//  POST -> simpan pengaturan toko (admin)
// ==========================================

header("Content-Type: application/json");

include "config.php";
include "koneksi.php";
include "auth.php";
include "toko.php";

wajib_login_api('admin');

// ---------------- BACA ----------------
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $s = get_setelan($conn);

    echo json_encode([
        "status"     => "success",
        "no_wa"      => $s['no_wa']      ?? NO_WA_DEFAULT,
        "admin_user" => $s['admin_user'] ?? ADMIN_USER
    ]);
    exit;
}

// ---------------- SIMPAN ----------------
$data = json_decode(file_get_contents("php://input"), true);

$noWa       = trim($data['no_wa'] ?? '');
$adminUser  = trim($data['admin_user'] ?? '');
$passLama   = $data['pass_lama'] ?? '';
$passBaru   = $data['pass_baru'] ?? '';
$passUlangi = $data['pass_ulangi'] ?? '';

if ($noWa === '' || $adminUser === '') {
    echo json_encode([
        "status"  => "error",
        "message" => "Nomor WA dan username admin tidak boleh kosong!"
    ]);
    exit;
}

$setelan      = get_setelan($conn);
$hashSekarang = $setelan['admin_pass'] ?? '';
$hashBaru     = $hashSekarang;

$mauGantiPass = ($passLama !== '' || $passBaru !== '' || $passUlangi !== '');

if ($mauGantiPass) {
    $cocok = ($hashSekarang !== '') ? password_verify($passLama, $hashSekarang)
                                    : ($passLama === ADMIN_PASS);

    if (!$cocok) {
        echo json_encode([
            "status"  => "error",
            "message" => "Password lama salah! Password tidak diganti."
        ]);
        exit;
    }
    if (strlen($passBaru) < 6) {
        echo json_encode([
            "status"  => "error",
            "message" => "Password baru minimal 6 karakter!"
        ]);
        exit;
    }
    if ($passBaru !== $passUlangi) {
        echo json_encode([
            "status"  => "error",
            "message" => "Konfirmasi password baru tidak cocok!"
        ]);
        exit;
    }
    $hashBaru = password_hash($passBaru, PASSWORD_DEFAULT);
}

$sql = "UPDATE setelan SET NO_WA = ?, ADMIN_USER = ?, ADMIN_PASS = ? WHERE ID = 1";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sss", $noWa, $adminUser, $hashBaru);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "status"  => "success",
        "message" => "Pengaturan berhasil disimpan!"
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Gagal menyimpan pengaturan."
    ]);
}
