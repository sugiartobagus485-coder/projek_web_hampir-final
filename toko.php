<?php
// ==========================================
//  TOKO.PHP - Pengaturan toko & helper WhatsApp
// ==========================================

/** Ambil satu baris pengaturan toko (nomor WA, sandi admin, dll) */
function get_setelan($conn)
{
    $hasil = mysqli_query($conn, "SELECT * FROM setelan WHERE id = 1");
    if ($hasil && mysqli_num_rows($hasil) > 0) {
        return mysqli_fetch_assoc($hasil);
    }
    return [];
}

/** Nomor WhatsApp admin yang sudah rapi (62xxx) */
function no_wa($conn)
{
    $setelan = get_setelan($conn);
    $no = $setelan['no_wa'] ?? '';
    if ($no === '') {
        $no = NO_WA_DEFAULT;
    }
    return rapikan_no_hp($no);
}

/** Bikin link klik-ke-chat WhatsApp dengan pesan yang sudah terisi */
function link_wa($conn, $pesan)
{
    return 'https://wa.me/' . no_wa($conn) . '?text=' . urlencode($pesan);
}

/** Kredensial admin aktif (dari database, kalau kosong pakai bawaan config) */
function kredensial_admin($conn)
{
    $setelan = get_setelan($conn);
    return [
        'user' => $setelan['admin_user'] ?? ADMIN_USER,
        'hash' => $setelan['admin_pass'] ?? ''
    ];
}
