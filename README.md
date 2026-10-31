# 🍽️ Antriless — Your Online Canteen

**Antriless** adalah website pemesanan makanan dan minuman kantin untuk mahasiswa dan dosen yang tidak punya banyak waktu luang. Tidak perlu lagi mengantre panjang: pilih menu, pesan lewat website, lalu ambil pesananmu saat sudah siap.

![PHP](https://img.shields.io/badge/PHP-8.2-777BB4?logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-8-4479A1?logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?logo=bootstrap&logoColor=white)
![Docker](https://img.shields.io/badge/Docker-Compose-2496ED?logo=docker&logoColor=white)

---

## ✨ Fitur Utama

### 👤 Pengguna (Mahasiswa & Dosen)
- Registrasi dan login akun
- Melihat menu makanan, minuman, camilan, dan dessert dari berbagai kantin
- Filter menu berdasarkan kategori dan urutkan dari harga termurah
- Keranjang belanja dengan pengaturan jumlah pesanan
- Checkout dan invoice/struk pesanan lengkap dengan QR code
- Pelacakan status pesanan: **Diterima → Diproses → Siap Diambil**
- Manajemen profil

### 🧑‍🍳 Pemilik Kantin (Admin)
- Dashboard ringkasan pesanan
- Kelola menu: tambah, ubah, hapus, atur stok, dan unggah foto
- Kelola dan perbarui status pesanan masuk
- Laporan penjualan
- Manajemen profil kantin

---

## 🛠️ Teknologi

| Bagian | Teknologi |
|---|---|
| Backend | PHP 8.2 (native) + MySQLi |
| Database | MySQL 8 |
| Frontend | HTML, CSS, JavaScript, Bootstrap 5, Swiper.js, QRCode.js |
| Infrastruktur | Docker, Docker Compose, Apache, phpMyAdmin |

---

## 🚀 Cara Menjalankan

### Opsi 1 — Docker (direkomendasikan)

Pastikan **Docker** dan **Docker Compose** sudah terpasang.

```bash
git clone https://github.com/Callysta27/Antriless.git
cd Antriless
docker compose up -d --build
```

Database akan dibuat dan diisi otomatis dari `database/init.sql`. Setelah semua container berjalan:

| Layanan | URL |
|---|---|
| Website Antriless | http://localhost:8000 |
| phpMyAdmin | http://localhost:8081 |
| MySQL (dari host) | `localhost:3307` |

Untuk menghentikan:

```bash
docker compose down        # hentikan container
docker compose down -v     # hentikan + hapus data database (reset)
```

### Opsi 2 — XAMPP (manual)

1. Clone repository, lalu salin isi folder `baru/admin/` ke `C:\xampp\htdocs\antriless\`.
2. Jalankan XAMPP dan aktifkan **Apache** serta **MySQL**.
3. Buka `http://localhost/phpmyadmin`, lalu import file `database/init.sql` (database `db_kantin` akan dibuat otomatis).
4. Buat folder `dashboard_temp/uploads/menu/` agar upload foto menu berfungsi.
5. Buka `http://localhost/antriless/login.php`.

> Koneksi database diatur di `frontend/includes/config.php`. File ini membaca environment variable `DB_HOST`, `DB_USER`, `DB_PASS`, dan `DB_NAME`, dan jika tidak ada akan memakai default XAMPP (`localhost`, `root`, tanpa password, `db_kantin`).

---

## 🔑 Akun Demo

| Role | Email | Password |
|---|---|---|
| Pemilik Kantin | `kantin@antriless.com` | `kantin123` |
| Mahasiswa | `budi@untar.ac.id` | `budi123` |
| Dosen | `dosen@untar.ac.id` | `dosen123` |

Login sebagai pemilik kantin akan diarahkan ke dashboard admin, sedangkan mahasiswa/dosen ke halaman utama pemesanan.

---

## 📁 Struktur Folder

```
Antriless/
├── baru/
│   └── admin/                    # Root aplikasi (di-mount ke /var/www/html)
│       ├── login.php             # Halaman login & registrasi
│       ├── forgotpassword.php
│       ├── assets/               # Script login & produk
│       ├── dashboard_temp/       # Dashboard pemilik kantin
│       │   ├── dashboard.php
│       │   ├── products.php      # Kelola menu & stok
│       │   ├── orderPage.php     # Kelola pesanan
│       │   ├── reports.php       # Laporan
│       │   └── profil.php
│       └── frontend/             # Halaman pengguna
│           ├── halaman utama.php # Daftar menu & keranjang
│           ├── proses_checkout.php
│           ├── invoice.php       # Struk + QR code
│           ├── status_pesanan.php
│           ├── profil.php
│           └── includes/config.php
├── database/
│   └── init.sql                  # Skema & data awal
├── dockerfile
└── docker-compose.yml
```

### Skema Database
`user` · `menu` · `orders` · `detail_order` · `payment` · `invoice`

---

## 👥 Anggota Kelompok

- Callysta Elysia Theona Dethan
- Suellen Abelvia
- Tracy Prycillia Kusmana
- Syahwa Aliah Wandi

---

## 📄 Lisensi

Proyek ini dibuat untuk keperluan tugas mata kuliah **Software Development**.

© 2026 Antriless. All rights reserved.
