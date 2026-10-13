<?php
// ==========================================
//  API_LOGOUT.PHP - Hapus session
// ==========================================

header("Content-Type: application/json");

include "auth.php";

$_SESSION = [];
session_destroy();

echo json_encode(["status" => "success"]);
