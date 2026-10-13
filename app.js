// ==========================================================
//  app.js - Script bersama semua halaman HTML
//  Berisi: helper, navbar, footer, dan keranjang belanja.
// ==========================================================

// ----------------------------------------------------------
// 0. ANTI-CACHE (PENTING!)
//    Browser suka menyimpan style1.css versi lama. Akibatnya
//    halaman terlihat "tanpa desain" padahal CSS-nya benar.
//    Kita ubah URL CSS dengan penanda waktu, jadi browser
//    selalu memuat CSS yang terbaru.
// ----------------------------------------------------------
document.querySelectorAll('link[rel="stylesheet"]').forEach(function (tautan) {
    var dasar = tautan.href.split("?")[0];
    tautan.href = dasar + "?v=" + Date.now();
});

// ----------------------------------------------------------
// 1. HELPER UMUM
// ----------------------------------------------------------

/** Panggil API PHP, kembalikan JSON */
async function api(url, opsi) {
  const res = await fetch(url, opsi);
  return res.json();
}

/** Kirim data JSON ke API */
async function apiKirim(url, data, method) {
  const res = await fetch(url, {
    method: method || "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(data)
  });
  return res.json();
}

/** Format angka jadi Rupiah */
function rupiah(angka) {
  return "Rp " + Number(angka || 0).toLocaleString("id-ID");
}

/** Aman-in teks sebelum dimasukkan ke HTML */
function aman(teks) {
  return String(teks ?? "").replace(/[&<>"']/g, function (c) {
    return {
      "&": "&amp;",
      "<": "&lt;",
      ">": "&gt;",
      '"': "&quot;",
      "'": "&#39;"
    }[c];
  });
}

/** Warna lencana sesuai tingkat kesulitan */
function lencanaTingkat(tingkat) {
  if (tingkat === "Mudah") return "mudah";
  if (tingkat === "Menengah") return "menengah";
  return "advanced";
}

/** Warna lencana sesuai status pesanan */
function lencanaStatus(status) {
  if (status === "Selesai") return "mudah";
  if (status === "Dibatalkan") return "advanced";
  return "menengah";
}

/** Tampilkan pesan di elemen #notif */
function tampilkanNotif(teks, jenis) {
  const kotak = document.getElementById("notif");
  if (!kotak) return;
  kotak.className = jenis === "error" ? "notif error" : "notif";
  kotak.innerText = teks;
}

// ----------------------------------------------------------
// 2. STATUS LOGIN
// ----------------------------------------------------------

let infoAkun = { login: false, role: "", nama: "" };

async function muatStatus() {
  try {
    infoAkun = await api("api_status.php");
  } catch (e) {
    infoAkun = { login: false, role: "", nama: "" };
  }
  return infoAkun;
}

/** Halaman khusus user: kalau bukan user, tendang ke login */
function kunciUser() {
  if (!infoAkun.login || infoAkun.role !== "user") {
    window.location.href = "login.html?pesan=belum_login";
  }
}

/** Halaman khusus admin: kalau bukan admin, tendang ke login */
function kunciAdmin() {
  if (!infoAkun.login || infoAkun.role !== "admin") {
    window.location.href = "login.html?pesan=belum_login";
  }
}

// ----------------------------------------------------------
// 3. NAVBAR (ada 2 macam: user & admin)
// ----------------------------------------------------------

function markupNavbarUser() {
  const s = infoAkun;
  let menuKanan;

  if (s.login && s.role === "admin") {
    menuKanan =
      '<li><a href="admin.html">Admin</a></li>' +
      '<li><a href="#" id="tombolKeluar">Logout</a></li>';
  } else if (s.login && s.role === "user") {
    menuKanan =
      '<li><a href="dashboard.html">Profil</a></li>' +
      '<li><a href="#" id="tombolKeluar">Logout</a></li>';
  } else {
    menuKanan = '<li><a href="login.html">Login</a></li>';
  }

  return (
    '<nav>' +
      '<div class="Pepek">' +
        '<div class="logo">Game<span>Dev</span></div>' +
        '<ul class="PepekNav">' +
          '<li><a href="home.html">Home</a></li>' +
          '<li><a href="Contact.html">ContactUs</a></li>' +
          '<li><a href="OurProject.html">OurProject</a></li>' +
          '<li><a href="katalog.html">Katalog</a></li>' +
          '<li><a href="cara_beli.html">Cara Beli</a></li>' +
          '<li><a href="demo_pesan.html">Demo</a></li>' +
        "</ul>" +
        '<ul class="Login">' + menuKanan + "</ul>" +
      "</div>" +
    "</nav>"
  );
}

function markupNavbarAdmin() {
  return (
    '<nav class="nav-admin">' +
      '<div class="Pepek">' +
        '<div class="logo">Game<span>Dev</span> Admin</div>' +
        '<ul class="PepekNav">' +
          '<li><a href="admin.html">Ringkasan <span id="lencanaNotif" class="lencana-notif" style="display:none;"></span></a></li>' +
          '<li><a href="admin_produk.html">Kelola Produk</a></li>' +
          '<li><a href="admin_pesanan.html">Pesanan</a></li>' +
          '<li><a href="admin_setelan.html">Pengaturan</a></li>' +
        "</ul>" +
        '<ul class="Login">' +
          '<li><a href="katalog.html">Lihat Toko</a></li>' +
          '<li><a href="#" id="tombolKeluar">Logout</a></li>' +
        "</ul>" +
      "</div>" +
    "</nav>"
  );
}

function pasangAksiNavbar() {
  const tombol = document.getElementById("tombolKeluar");
  if (tombol) {
    tombol.addEventListener("click", async function (e) {
      e.preventDefault();
      try {
        await apiKirim("api_logout.php", {});
      } catch (err) {
        console.error(err);
      }
      window.location.href = "login.html?pesan=keluar";
    });
  }
}

// ----------------------------------------------------------
// 4. FOOTER
// ----------------------------------------------------------

function markupFooter(noWa) {
  const wa = String(noWa || "6281234567890").replace(/[^0-9]/g, "");
  const tampil =
    "08" + (wa.slice(2, 5) || "812") + "-" +
    (wa.slice(5, 9) || "3456") + "-" + (wa.slice(9, 13) || "7890");

  return (
    '<footer class="footer">' +
      '<div class="footer-isi">' +
        '<div class="footer-kolom">' +
          '<div class="logo">Game<span>Dev</span></div>' +
          "<p>Tempat belajar dan jual beli kode bahasa pemrograman " +
          "untuk bikin game. Dari yang ramah pemula sampai level advance.</p>" +
        "</div>" +
        '<div class="footer-kolom">' +
          "<h4>Menu</h4>" +
          '<a href="home.html">Home</a>' +
          '<a href="Contact.html">ContactUs</a>' +
          '<a href="OurProject.html">OurProject</a>' +
          '<a href="katalog.html">Katalog</a>' +
        "</div>" +
        '<div class="footer-kolom">' +
          "<h4>Alamat</h4>" +
          "<p>Jl. Teknologi No. 99<br>Jakarta, Indonesia</p>" +
        "</div>" +
        '<div class="footer-kolom">' +
          "<h4>Kontak &amp; Jam Buka</h4>" +
          '<a href="https://wa.me/' + aman(wa) +
            '" target="_blank" rel="noopener">WhatsApp: ' + tampil + "</a>" +
          '<a href="mailto:hello@ourproject.com">hello@ourproject.com</a>' +
          "<p>Senin - Sabtu<br>08.00 - 20.00 WIB</p>" +
        "</div>" +
      "</div>" +
      '<div class="footer-bawah">By.@BogasSugiartoX_Rpl-1</div>' +
    "</footer>"
  );
}

// ----------------------------------------------------------
// 5. KERANJANG (localStorage)
// ----------------------------------------------------------

const KERANJANG_KEY = "keranjang_gamedev";

function ambilKeranjang() {
  try {
    const isi = JSON.parse(localStorage.getItem(KERANJANG_KEY));
    return Array.isArray(isi) ? isi : [];
  } catch (e) {
    return [];
  }
}

function simpanKeranjang(isi) {
  localStorage.setItem(KERANJANG_KEY, JSON.stringify(isi));
  perbaruiLencanaKeranjang();
}

function tambahKeKeranjang(id, nama, harga) {
  const isi = ambilKeranjang();
  isi.push({ id: id, nama: nama, harga: harga, qty: 1 });
  simpanKeranjang(isi);
  return isi.length;
}

function jumlahKeranjang() {
  return ambilKeranjang().length;
}

function perbaruiLencanaKeranjang() {
  const lencana = document.getElementById("badgeKeranjang");
  if (lencana) {
    lencana.innerText = jumlahKeranjang();
  }
}

// ----------------------------------------------------------
// 6. JALANKAN SAAT HALAMAN SIAP
// ----------------------------------------------------------

/** Update menu Login/Profil/Admin di navbar sesuai status login */
function updateMenuAuth() {
  const menu = document.getElementById("menuAuth");
  if (!menu) return;

  if (infoAkun.login && infoAkun.role === "admin") {
    menu.innerHTML =
      '<a href="admin.html">Admin</a> ' +
      '<a href="#" id="tombolKeluar">Logout</a>';
  } else if (infoAkun.login && infoAkun.role === "user") {
    menu.innerHTML =
      '<a href="dashboard.html">Profil</a> ' +
      '<a href="#" id="tombolKeluar">Logout</a>';
  } else {
    menu.innerHTML = '<a href="login.html">Login</a>';
  }

  const tombol = document.getElementById("tombolKeluar");
  if (tombol) {
    tombol.addEventListener("click", async function (e) {
      e.preventDefault();
      try {
        await apiKirim("api_logout.php", {});
      } catch (err) {
        console.error(err);
      }
      window.location.href = "login.html?pesan=keluar";
    });
  }
}

document.addEventListener("DOMContentLoaded", async function () {
  // Baca status login dulu
  await muatStatus();

  // Update menu Login/Profil/Admin di navbar
  updateMenuAuth();

  // Badge notifikasi untuk admin
  if (infoAkun.role === "admin") {
    try {
      const notif = await api("api_notifikasi.php");
      const lencana = document.getElementById("lencanaNotif");
      if (lencana && notif.belum_dibaca > 0) {
        lencana.style.display = "inline-block";
        lencana.innerText = notif.belum_dibaca;
      }
    } catch (e) {
      console.error(e);
    }
  }

  // Pasang footer
  const tempatFooter = document.getElementById("footer");
  if (tempatFooter) {
    tempatFooter.innerHTML = markupFooter(infoAkun.no_wa);
  }

  // Perbarui jumlah keranjang
  perbaruiLencanaKeranjang();
});
