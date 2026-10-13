<?php
// ==========================================
//  AUTH.PHP - Session & fungsi bantu login
//  Di-include paling atas oleh semua halaman
//  yang butuh tahu "siapa yang sedang login".
// ==========================================

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah pengguna sudah login sebagai $role ('admin' atau 'user').
 * Kalau belum, dilempar ke halaman login.
 */
function wajib_login($role)
{
    if (!isset($_SESSION['login']) || $_SESSION['role'] !== $role) {
        header("Location: login.html?pesan=belum_login");
        exit;
    }
}

/**
 * Versi khusus API: bukan me-redirect, tapi kirim JSON 403.
 */
function wajib_login_api($role)
{
    if (!isset($_SESSION['login']) || $_SESSION['role'] !== $role) {
        header("Content-Type: application/json");
        http_response_code(403);
        echo json_encode([
            "status"  => "error",
            "message" => "Akses ditolak. Login sebagai " . $role . " dulu."
        ]);
        exit;
    }
}

/** Aman-in teks sebelum di-echo ke HTML */
function h($teks)
{
    return htmlspecialchars($teks ?? '', ENT_QUOTES, 'UTF-8');
}

/** Ubah angka jadi format Rupiah: 50000 -> Rp 50.000 */
function rupiah($angka)
{
    return 'Rp ' . number_format((int)$angka, 0, ',', '.');
}

/** Rapikan nomor HP jadi format 62xxxx */
function rapikan_no_hp($no)
{
    $no = preg_replace('/[^0-9]/', '', $no ?? '');
    if ($no === '') {
        return NO_WA_DEFAULT;
    }
    if (substr($no, 0, 1) === '0') {
        $no = '62' . substr($no, 1);
    }
    return $no;
}
