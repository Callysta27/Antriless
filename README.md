# 🍽️ Antriless - Your Online Canteen

**Antriless** adalah website pemesanan makanan dan minuman di kantin untuk mahasiswa dan dosen yang tidak punya waktu luang. Tidak perlu lagi mengantre panjang: pilih menu, pesan lewat website, lalu ambil pesananmu saat sudah siap.

## ✨ Fitur Utama

- Melihat menu makanan, minuman, camilan, dan dessert dari berbagai kantin
- Filter menu berdasarkan kategori dan urutkan dari harga termurah
- Keranjang belanja dengan pengaturan jumlah pesanan
- Struk pesanan dengan total harga dan QR code
- Pelacakan status pesanan (Diterima, Diproses, Siap Diambil)
- Login pengguna dan manajemen profil
- Dashboard admin kantin untuk mengelola menu, stok, dan foto

## 🛠️ Teknologi yang Digunakan

- PHP (native) dan MySQL
- HTML, CSS, JavaScript
- Bootstrap 5 dan Swiper.js
- QRCode.js
- XAMPP (server lokal)

## 🚀 Cara Menjalankan Proyek

1. **Clone repository**
   ```bash
   git clone https://github.com/SyahwaAliah/SOFTWARE-DEVELOPMENT---Antriless.git
   ```
2. **Pindahkan folder proyek** ke `C:\xampp\htdocs\`
3. **Jalankan XAMPP**, lalu aktifkan **Apache** dan **MySQL**
4. **Import database**
   - Buka `http://localhost/phpmyadmin`
   - Buat database baru, lalu import file `.sql` dari folder proyek
5. **Atur koneksi database**
   - Salin `config.example.php` menjadi `config.php`
   - Sesuaikan nama database, username, dan password
6. **Buka di browser**
   ```
   http://localhost/nama-folder-proyek/
   ```

## 📁 Struktur Folder (Ringkas)

```
├── admin/
│   └── dashboard_temp/   # Dashboard admin kantin
├── css/                  # Stylesheet
├── js/                   # Script JavaScript
├── img/                  # Gambar aset
└── includes/             # Konfigurasi database
```

## 👥 Anggota Kelompok

- Callysta Elysia Theona Dethan
- Suellen Abelvia
- Tracy Prycillia Kusmana
- Syahwa Aliah Wandi

## 📄 Lisensi

Proyek ini dibuat untuk keperluan tugas mata kuliah Software Development.

---
© 2026 Antriless. All rights reserved.
