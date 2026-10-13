<?php
// ==========================================
//  API_PESANAN_STATUS.PHP - Ubah status pesanan
// ==========================================

header("Content-Type: application/json");

include "config.php";
include "koneksi.php";
include "auth.php";

wajib_login_api('admin');

$data = json_decode(file_get_contents("php://input"), true);

$id     = (int)($data['id'] ?? 0);
$status = $data['status'] ?? '';

$boleh = ['Menunggu', 'Diproses', 'Selesai', 'Dibatalkan'];

if ($id <= 0 || !in_array($status, $boleh, true)) {
    echo json_encode([
        "status"  => "error",
        "message" => "Data status tidak valid."
    ]);
    exit;
}

$stmt = mysqli_prepare($conn, "UPDATE pesanan SET STATUS = ? WHERE ID = ?");
mysqli_stmt_bind_param($stmt, "si", $status, $id);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode([
        "status"  => "success",
        "message" => "Status pesanan diperbarui."
    ]);
} else {
    echo json_encode([
        "status"  => "error",
        "message" => "Gagal memperbarui status."
    ]);
}
