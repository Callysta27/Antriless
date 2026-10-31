<?php
// Session harus dimulai sebelum ada output HTML apa pun
session_name('user_session');
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit;
}

require_once 'includes/config.php';

function e($teks)
{
  return htmlspecialchars((string) $teks, ENT_QUOTES, 'UTF-8');
}

function rupiah($angka)
{
  return 'Rp ' . number_format($angka, 0, ',', '.');
}

function fotoMenu($menu)
{
  return empty($menu['foto']) ? '' : '../dashboard_temp/' . $menu['foto'];
}

// Semua menu yang tersedia, paling baru di depan
$daftarMenu = mysqli_fetch_all(
  mysqli_query($conn, "SELECT * FROM menu WHERE status='tersedia' ORDER BY created_at DESC, id_menu DESC"),
  MYSQLI_ASSOC
);

$menuBaru   = $daftarMenu[0] ?? null;           // banner "New Product!"
$menuTerbaru = array_slice($daftarMenu, 0, 8);  // carousel "Newly Fresh Bake"

// Grid "Foods And Drinks" tetap urut sesuai waktu ditambahkan (lama → baru)
$menuGrid = array_reverse($daftarMenu);

$kategori = [
  ['kode' => 'makanan', 'nama' => 'Foods',   'gambar' => 'img/burger.png'],
  ['kode' => 'minuman', 'nama' => 'Drinks',  'gambar' => 'img/drink.png'],
  ['kode' => 'camilan', 'nama' => 'Snacks',  'gambar' => 'img/snack.jpg'],
  ['kode' => 'dessert', 'nama' => 'Dessert', 'gambar' => 'img/dessert.jpg'],
];

$tabKategori = [
  'all'     => 'ALL',
  'makanan' => 'FOOD',
  'minuman' => 'DRINK',
  'camilan' => 'SNACK',
  'dessert' => 'DESSERT',
];

$ulasan = [
  'UI nya bagus banget, sung aja IPK 4',
  'Tambahin snacknya dong min',
  'Membantu bangett, 5 star!!',
];
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="format-detection" content="telephone=no">
  <meta name="description" content="Antriless — pesan makanan kantin tanpa antre">
  <title>Antriless - Your Online Canteen</title>

  <!-- Bootstrap & Swiper (gaya carousel) -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@9/swiper-bundle.min.css">
  <link rel="stylesheet" href="css/halaman%20utama.css?v=<?= filemtime(__DIR__ . '/css/halaman utama.css') ?>">

  <!-- QR code untuk struk pembayaran -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
</head>

<body>

  <!-- ================= KERANJANG (offcanvas) ================= -->
  <div class="offcanvas offcanvas-end" data-bs-scroll="true" tabindex="-1" id="offcanvasCart" aria-label="Keranjang">
    <div class="offcanvas-header justify-content-center">
      <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Tutup"></button>
    </div>
    <div class="offcanvas-body">
      <h4 class="d-flex justify-content-between align-items-center mb-3">
        <span class="text-primary">Your cart</span>
        <span class="badge bg-primary rounded-pill" id="cart-count">0</span>
      </h4>

      <ul class="list-group mb-3" id="cart-list"></ul>

      <ul class="list-group mb-3">
        <li class="list-group-item d-flex justify-content-between">
          <span>Total</span>
          <strong id="cart-total">Rp 0</strong>
        </li>
      </ul>

      <button type="button" class="w-100 btn btn-primary btn-lg" onclick="checkout()">Continue to checkout</button>
    </div>
  </div>

  <!-- ================= HEADER ================= -->
  <header>
    <div class="container-fluid">
      <div class="row py-3 border-bottom align-items-center">

        <!-- Logo -->
        <div class="col-sm-3 col-lg-2 text-center text-sm-start">
          <div class="main-logo">
            <a href="halaman%20utama.php" class="text-decoration-none text-dark">
              <h1 class="m-0">Antriless</h1>
              <h6 class="m-0 text-muted">Your Online Canteen</h6>
            </a>
          </div>
        </div>

        <!-- Pencarian -->
        <div class="col-8 col-sm-6 col-md-5">
          <form id="search-form" class="text-center" role="search">
            <input type="search" name="keyword" id="search-input" class="form-control border-0 bg-light"
              placeholder="Search for foods and drinks" autocomplete="off">
          </form>
        </div>
        <div class="col-2 col-sm-2 col-md-1 text-start">
          <svg role="button" aria-label="Cari" onclick="cariMenu()" width="24" height="24" viewBox="0 0 24 24">
            <path fill="currentColor" d="M21.71 20.29L18 16.61A9 9 0 1 0 16.61 18l3.68 3.68a1 1 0 0 0 1.42 0a1 1 0 0 0 0-1.39ZM11 18a7 7 0 1 1 7-7a7 7 0 0 1-7 7Z" />
          </svg>
        </div>

        <!-- Support, ikon, keranjang -->
        <div class="col-12 col-sm-12 col-lg-3 offset-md-1 offset-lg-1 d-flex justify-content-end gap-3 align-items-center mt-3 mt-lg-0">
          <div class="support-box text-end d-none d-xl-block">
            <span class="fs-6 text-muted d-block">For Support?</span>
            <h5 class="mb-0 text-nowrap">+62 8123456789</h5>
          </div>

          <ul class="d-flex justify-content-end list-unstyled m-0 p-0">
            <!-- Profil -->
            <li class="position-relative">
              <a href="#" class="rounded-circle bg-light p-2 mx-1 d-inline-block" id="profileToggle" title="Profil">
                <svg width="24" height="24" viewBox="0 0 24 24">
                  <path fill="currentColor" d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-5.33 0-8 2.67-8 4v1h16v-1c0-1.33-2.67-4-8-4Z" />
                </svg>
              </a>

              <div id="profileDropdown" class="profil-dropdown">
                <div class="profil-info">
                  <div class="profil-nama"><?= e($_SESSION['nama'] ?? 'User') ?></div>
                  <div class="profil-role"><?= e($_SESSION['role'] ?? '') ?></div>
                </div>
                <a href="profil.php" class="profil-link">
                  <svg width="16" height="16" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M12 12a5 5 0 1 0 0-10 5 5 0 0 0 0 10Zm0 2c-5.33 0-8 2.67-8 4v1h16v-1c0-1.33-2.67-4-8-4Z" />
                  </svg>
                  Profil Saya
                </a>
                <a href="../login.php" class="profil-link logout">
                  <svg width="16" height="16" viewBox="0 0 24 24">
                    <path fill="currentColor" d="M17 8l-1.41 1.41L17.17 11H9v2h8.17l-1.58 1.58L17 16l4-4-4-4zM5 5h7V3H5C3.9 3 3 3.9 3 5v14c0 1.1.9 2 2 2h7v-2H5V5z" />
                  </svg>
                  Logout
                </a>
              </div>
            </li>

           <!-- Notifikasi pesanan -->
          <li class="position-relative">
            <a href="#" class="rounded-circle bg-light p-2 mx-1 d-inline-block position-relative" id="notifToggle" title="Notifikasi Pesanan">
              <svg width="24" height="24" viewBox="0 0 24 24">
                <path fill="currentColor" d="M12 22a2 2 0 0 0 2-2h-4a2 2 0 0 0 2 2Zm6-6V11c0-3.07-1.64-5.64-4.5-6.32V4a1.5 1.5 0 0 0-3 0v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2Z" />
              </svg>
              <span class="notif-dot" id="notifDot"></span>
            </a>

            <div id="notifDropdown" class="profil-dropdown notif-dropdown">
              <div class="profil-info">
                <div class="profil-nama">Notifikasi Pesanan</div>
              </div>
              <div class="notif-isi" id="notifIsi">
                <p class="notif-kosong">Memuat...</p>
              </div>
              <a href="status.php" class="profil-link">Lihat detail pesanan →</a>
            </div>
          </li>

            <!-- Info pesanan -->
            <li>
              <a href="#" class="rounded-circle bg-warning p-2 mx-1 d-inline-block"
                data-bs-toggle="modal" data-bs-target="#modalInfoPesanan" title="Info Pesanan">
                <svg width="24" height="24" viewBox="0 0 24 24">
                  <path fill="currentColor" d="M9 5H7a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2h-2M9 5a2 2 0 0 0 2 2h2a2 2 0 0 0 2-2M9 5a2 2 0 0 1 2-2h2a2 2 0 0 1 2 2m-6 9l2 2l4-4" />
                </svg>
              </a>
            </li>

            <!-- Keranjang (HP & tablet) -->
            <li class="d-lg-none">
              <a href="#" class="rounded-circle bg-light p-2 mx-1 d-inline-block" title="Keranjang"
                data-bs-toggle="offcanvas" data-bs-target="#offcanvasCart" aria-controls="offcanvasCart">
                <svg width="24" height="24" viewBox="0 0 24 24">
                  <path fill="currentColor" d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2ZM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42a.25.25 0 0 1-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1 1 0 0 0 20 4H5.21l-.94-2H1Zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2Z" />
                </svg>
              </a>
            </li>
          </ul>

          <!-- Keranjang (laptop & desktop) -->
          <div class="cart text-end d-none d-lg-block">
            <button type="button" class="border-0 bg-transparent d-flex flex-column align-items-end lh-1"
              data-bs-toggle="offcanvas" data-bs-target="#offcanvasCart" aria-controls="offcanvasCart">
              <span class="fs-6 text-muted dropdown-toggle mb-1">Your Cart</span>
              <span class="cart-total fs-5 fw-bold text-nowrap">Rp 0</span>
            </button>
          </div>
        </div>

      </div>
    </div>
  </header>

  <!-- ================= BANNER & PROMO ================= -->
  <section class="py-3">
    <div class="container-fluid">
      <div class="row">

        <!-- Banner kiri: menu paling baru -->
        <div class="col-12 col-md-6 col-lg-7">
          <div class="p-3 bg-primary text-white" style="min-height: 420px;">
            <div class="kotak">
              <h3>New Product!</h3>
              <?php if ($menuBaru): ?>
                <?php if (fotoMenu($menuBaru)): ?>
                  <img src="<?= e(fotoMenu($menuBaru)) ?>" class="new-product-img" alt="<?= e($menuBaru['nama_menu']) ?>">
                <?php endif; ?>
                <h1><?= e($menuBaru['nama_menu']) ?></h1>
                <p>
                  Menu terbaru dari <strong><?= e($menuBaru['nama_kantin'] ?: 'kantin') ?></strong>.
                  Cobain sekarang, cuma <strong><?= rupiah($menuBaru['harga']) ?></strong>!
                </p>
                <div class="kotak_py-3">
                  <button type="button" class="btn btn-light" onclick="lihatMenu(<?= (int) $menuBaru['id_menu'] ?>)">
                    Order Now
                  </button>
                </div>
              <?php else: ?>
                <h1>Belum ada menu</h1>
                <p>Menu terbaru dari kantin akan tampil di sini.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Kotak promo kanan -->
        <div class="col-12 col-md-6 col-lg-5">
          <div class="row g-3">
            <div class="col-12">
              <div class="kotakAtas bg-secondary text-white" style="min-height: 200px;"
                role="button" tabindex="0" data-kategori-promo="dessert">
                <h4>15% Off</h4>
                <h2>Dessert</h2>
                <h6>shop collection ➔</h6>
              </div>
            </div>
            <div class="col-12">
              <div class="kotakBawah bg-dark text-white" style="min-height: 200px;"
                role="button" tabindex="0" data-kategori-promo="makanan">
                <h4>5% Off</h4>
                <h2>Food</h2>
                <h6>shop collection ➔</h6>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ================= NEWLY FRESH BAKE (menu terbaru) ================= -->
  <section class="py-5 overflow-hidden">
    <div class="container-fluid">
      <div class="section-header d-flex flex-wrap justify-content-between">
        <h2 class="section-title">Newly Fresh Bake</h2>
        <div class="d-flex align-items-center">
          <a href="#foods-menu" class="btn-link text-decoration-none" onclick="pilihKategori('all'); return false;">View All Menu →</a>
          <div class="swiper-buttons">
            <button type="button" class="swiper-prev btn btn-yellow" aria-label="Sebelumnya" onclick="geser('.brand-carousel', -1)">❮</button>
            <button type="button" class="swiper-next btn btn-yellow" aria-label="Berikutnya" onclick="geser('.brand-carousel', 1)">❯</button>
          </div>
        </div>
      </div>

      <div class="brand-carousel swiper">
        <div class="swiper-wrapper">
          <?php foreach ($menuTerbaru as $menu): ?>
            <div class="swiper-slide" role="button" tabindex="0" onclick="lihatMenu(<?= (int) $menu['id_menu'] ?>)">
              <div class="card mb-3 p-3 rounded-4 shadow border-0">
                <div class="row g-0">
                  <div class="col-md-4">
                    <?php if (fotoMenu($menu)): ?>
                      <img src="<?= e(fotoMenu($menu)) ?>" class="img-fluid rounded" alt="<?= e($menu['nama_menu']) ?>">
                    <?php else: ?>
                      <img class="img-fluid rounded" alt="">
                    <?php endif; ?>
                  </div>
                  <div class="col-md-8">
                    <div class="card-body py-0">
                      <p class="text-muted mb-0"><?= e($menu['nama_menu']) ?></p>
                      <h5 class="card-title"><?= rupiah($menu['harga']) ?></h5>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- ================= FOODS AND DRINKS ================= -->
  <section class="py-5" id="foods-menu">
    <div class="container-fluid">
      <div class="bootstrap-tabs product-tabs">

        <div class="tabs-header d-flex justify-content-between border-bottom">
          <h3>Foods And Drinks</h3>
          <nav>
            <div class="nav nav-tabs" id="nav-tab">
              <?php foreach ($tabKategori as $kode => $label): ?>
                <a class="nav-link<?= $kode === 'all' ? ' active' : '' ?>" href="#" data-kategori="<?= $kode ?>"
                  onclick="filterProduk('<?= $kode ?>'); return false;"><?= $label ?></a>
              <?php endforeach; ?>
              <a class="nav-link" href="#" id="tab-termurah" onclick="sortMurah(); return false;">TERMURAH</a>
            </div>
          </nav>
        </div>

        <div class="product-grid row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 row-cols-xl-5">
          <?php foreach ($menuGrid as $menu): ?>
            <div class="col" id="menu-<?= (int) $menu['id_menu'] ?>">
              <div class="product-item" data-kategori="<?= e(strtolower($menu['kategori'])) ?>" data-harga="<?= (int) $menu['harga'] ?>">

                <img src="<?= e(fotoMenu($menu)) ?>" class="tab-image" alt="<?= e($menu['nama_menu']) ?>">

                <h3><?= e($menu['nama_menu']) ?></h3>
                <span class="qty">Stok : <?= (int) $menu['stok'] ?></span>
                <span class="price"><?= rupiah($menu['harga']) ?></span>

                <div class="d-flex align-items-center justify-content-start">
                  <div class="input-group product-qty">
                    <span class="input-group-btn">
                      <button type="button" class="quantity-left-minus btn btn-danger btn-number">-</button>
                    </span>
                    <input type="text" id="qty-<?= (int) $menu['id_menu'] ?>" class="form-control input-number" value="1" inputmode="numeric" aria-label="Jumlah">
                    <span class="input-group-btn">
                      <button type="button" class="quantity-right-plus btn btn-success btn-number">+</button>
                    </span>
                  </div>
                  <a href="#" class="nav-link">Add to Cart</a>
                </div>

              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <p id="menu-kosong" class="text-center text-muted py-5" style="display:none;">
          Menu yang kamu cari tidak ditemukan 😕
        </p>

      </div>
    </div>
  </section>

  <!-- ================= ULASAN ================= -->
  <section class="pt-5 mb-2">
    <div class="container-fluid">
      <h2 class="my-4">Rating so far..</h2>
      <?php foreach ($ulasan as $teks): ?>
        <span class="btn btn-warning me-2 mb-2"><?= e($teks) ?></span>
      <?php endforeach; ?>
    </div>
  </section>

  <!-- ================= KEUNGGULAN ================= -->
  <section class="pt-5 pb-2 mb-2">
    <div class="container-fluid">
      <div class="row row-cols-1 row-cols-sm-3 row-cols-lg-5 justify-content-center g-4">

        <div class="col" style="max-width: 300px;">
          <div class="card mb-3 border-0">
            <div class="row align-items-start g-2">
              <div class="col-auto text-dark pt-1">
                <svg width="28" height="28" viewBox="0 0 24 24">
                  <path fill="currentColor" d="M21.5 15a3 3 0 0 0-1.9-2.78l1.87-7a1 1 0 0 0-.18-.87A1 1 0 0 0 20.5 4H6.8l-.33-1.26A1 1 0 0 0 5.5 2h-2v2h1.23l2.48 9.26a1 1 0 0 0 1 .74H18.5a1 1 0 0 1 0 2h-13a1 1 0 0 0 0 2h1.18a3 3 0 1 0 5.64 0h2.36a3 3 0 1 0 5.82 1a2.94 2.94 0 0 0-.4-1.47A3 3 0 0 0 21.5 15Zm-3.91-3H9L7.34 6H19.2ZM9.5 20a1 1 0 1 1 1-1a1 1 0 0 1-1 1Zm8 0a1 1 0 1 1 1-1a1 1 0 0 1-1 1Z" />
                </svg>
              </div>
              <div class="col">
                <div class="card-body p-0">
                  <h5 class="mb-0">Free delivery</h5>
                  <p class="card-text small mb-0">Lorem ipsum dolor sit amet</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col" style="max-width: 300px;">
          <div class="card mb-3 border-0">
            <div class="row align-items-start g-2">
              <div class="col-auto text-dark pt-1">
                <svg width="34" height="34" fill="currentColor" viewBox="0 0 24 24">
                  <path d="M5.338 1.59a61 61 0 0 0-2.837.856.48.48 0 0 0-.328.39c-.554 4.157.726 7.19 2.253 9.188a10.7 10.7 0 0 0 2.287 2.233c.346.244.652.42.893.533q.18.085.293.118a1 1 0 0 0 .101.025 1 1 0 0 0 .1-.025q.114-.034.294-.118c.24-.113.547-.29.893-.533a10.7 10.7 0 0 0 2.287-2.233c1.527-1.997 2.807-5.031 2.253-9.188a.48.48 0 0 0-.328-.39c-.651-.213-1.75-.56-2.837-.855C9.552 1.29 8.531 1.067 8 1.067c-.53 0-1.552.223-2.662.524zM5.072.56C6.157.265 7.31 0 8 0s1.843.265 2.928.56c1.11.3 2.229.655 2.887.87a1.54 1.54 0 0 1 1.044 1.262c.596 4.477-.787 7.795-2.465 9.99a11.8 11.8 0 0 1-2.517 2.453 7 7 0 0 1-1.048.625c-.28.132-.581.24-.829.24s-.548-.108-.829-.24a7 7 0 0 1-1.048-.625 11.8 11.8 0 0 1-2.517-2.453C1.928 10.487.545 7.169 1.141 2.692A1.54 1.54 0 0 1 2.185 1.43 63 63 0 0 1 5.072.56" />
                </svg>
              </div>
              <div class="col">
                <div class="card-body p-0">
                  <h5 class="mb-0">Food Quality</h5>
                  <p class="card-text small mb-0">Lorem ipsum dolor sit amet</p>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col" style="max-width: 300px;">
          <div class="card mb-3 border-0">
            <div class="row align-items-start g-2">
              <div class="col-auto text-dark">
                <img src="img/halal.png" width="35" height="35" alt="Halal">
              </div>
              <div class="col">
                <div class="card-body p-0">
                  <h5 class="mb-0">Halal Choice</h5>
                  <p class="card-text small mb-0">Lorem ipsum dolor sit amet</p>
                </div>
              </div>
            </div>
          </div>
        </div>

      </div>
    </div>
  </section>

  <!-- ================= FOOTER ================= -->
  <footer class="py-5">
    <div class="container-fluid">
      <div class="row g-2 g-md-3">

        <div class="col-lg-3 col-md-6 col-sm-6">
          <div class="footer-menu">
            <a href="halaman%20utama.php" class="text-decoration-none text-dark">
              <h1 class="m-0" style="font-size: 38pt;">Antriless</h1>
              <h6 class="m-0 text-muted">Your Online Canteen</h6>
            </a>
            <div class="social-links mt-3">
              <ul class="d-flex list-unstyled">
                <li>
                  <a href="#" class="btn btn-outline-light" aria-label="Facebook">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                      <path d="M16 8.049c0-4.446-3.582-8.05-8-8.05C3.58 0-.002 3.603-.002 8.05c0 4.017 2.926 7.347 6.75 7.951v-5.625h-2.03V8.05H6.75V6.275c0-2.017 1.195-3.131 3.022-3.131.876 0 1.791.157 1.791.157v1.98h-1.009c-.993 0-1.303.621-1.303 1.258v1.51h2.218l-.354 2.326H9.25V16c3.824-.604 6.75-3.934 6.75-7.951" />
                    </svg>
                  </a>
                </li>
                <li>
                  <a href="#" class="btn btn-outline-light" aria-label="Instagram">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                      <path d="M8 0C5.829 0 5.556.01 4.703.048 3.85.088 3.269.222 2.76.42a3.9 3.9 0 0 0-1.417.923A3.9 3.9 0 0 0 .42 2.76C.222 3.268.087 3.85.048 4.7.01 5.555 0 5.827 0 8.001c0 2.172.01 2.444.048 3.297.04.852.174 1.433.372 1.942.205.526.478.972.923 1.417.444.445.89.719 1.416.923.51.198 1.09.333 1.942.372C5.555 15.99 5.827 16 8 16s2.444-.01 3.298-.048c.851-.04 1.434-.174 1.943-.372a3.9 3.9 0 0 0 1.416-.923c.445-.445.718-.891.923-1.417.197-.509.332-1.09.372-1.942C15.99 10.445 16 10.173 16 8s-.01-2.445-.048-3.299c-.04-.851-.175-1.433-.372-1.941a3.9 3.9 0 0 0-.923-1.417A3.9 3.9 0 0 0 13.24.42c-.51-.198-1.092-.333-1.943-.372C10.443.01 10.172 0 7.998 0zm-.717 1.442h.718c2.136 0 2.389.007 3.232.046.78.035 1.204.166 1.486.275.373.145.64.319.92.599s.453.546.598.92c.11.281.24.705.275 1.485.039.843.047 1.096.047 3.231s-.008 2.389-.047 3.232c-.035.78-.166 1.203-.275 1.485a2.5 2.5 0 0 1-.599.919c-.28.28-.546.453-.92.598-.28.11-.704.24-1.485.276-.843.038-1.096.047-3.232.047s-2.39-.009-3.233-.047c-.78-.036-1.203-.166-1.485-.276a2.5 2.5 0 0 1-.92-.598 2.5 2.5 0 0 1-.6-.92c-.109-.281-.24-.705-.275-1.485-.038-.843-.046-1.096-.046-3.233s.008-2.388.046-3.231c.036-.78.166-1.204.276-1.486.145-.373.319-.64.599-.92s.546-.453.92-.598c.282-.11.705-.24 1.485-.276.738-.034 1.024-.044 2.515-.045zm4.988 1.328a.96.96 0 1 0 0 1.92.96.96 0 0 0 0-1.92m-4.27 1.122a4.109 4.109 0 1 0 0 8.217 4.109 4.109 0 0 0 0-8.217m0 1.441a2.667 2.667 0 1 1 0 5.334 2.667 2.667 0 0 1 0-5.334" />
                    </svg>
                  </a>
                </li>
                <li>
                  <a href="#" class="btn btn-outline-light" aria-label="WhatsApp">
                    <svg width="24" height="24" fill="currentColor" viewBox="0 0 16 16">
                      <path d="M13.601 2.326A7.85 7.85 0 0 0 7.994 0C3.627 0 .068 3.558.064 7.926c0 1.399.366 2.76 1.057 3.965L0 16l4.204-1.102a7.9 7.9 0 0 0 3.79.965h.004c4.368 0 7.926-3.558 7.93-7.93A7.9 7.9 0 0 0 13.6 2.326zM7.994 14.521a6.6 6.6 0 0 1-3.356-.92l-.24-.144-2.494.654.666-2.433-.156-.251a6.56 6.56 0 0 1-1.007-3.505c0-3.626 2.957-6.584 6.591-6.584a6.56 6.56 0 0 1 4.66 1.931 6.56 6.56 0 0 1 1.928 4.66c-.004 3.639-2.961 6.592-6.592 6.592m3.615-4.934c-.197-.099-1.17-.578-1.353-.646-.182-.065-.315-.099-.445.099-.133.197-.513.646-.627.775-.114.133-.232.148-.43.05-.197-.1-.836-.308-1.592-.985-.59-.525-.985-1.175-1.103-1.372-.114-.198-.011-.304.088-.403.087-.088.197-.232.296-.346.1-.114.133-.198.198-.33.065-.134.034-.248-.015-.347-.05-.099-.445-1.076-.612-1.47-.16-.389-.323-.335-.445-.34-.114-.007-.247-.007-.38-.007a.73.73 0 0 0-.529.247c-.182.198-.691.677-.691 1.654s.71 1.916.81 2.049c.098.133 1.394 2.132 3.383 2.992.47.205.84.326 1.129.418.475.152.904.129 1.246.08.38-.058 1.171-.48 1.338-.943.164-.464.164-.86.114-.943-.049-.084-.182-.133-.38-.232" />
                    </svg>
                  </a>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-5 col-sm-6">
          <div class="footer-menu">
            <h3 class="widget-title">About Us?</h3>
              <p style="text-align: justify;">
      Antriless adalah platform pemesanan kantin online yang dibuat untuk mahasiswa dan dosen
      dengan jadwal padat. Lewat Antriless, kamu bisa melihat menu dari berbagai kantin,
      memesan makanan dan minuman langsung dari website, lalu memantau status pesanan sampai
      siap diambil, tanpa perlu antre panjang di jam istirahat.
    </p>
    <p style="text-align: justify;" class="mb-0">
      Proyek ini dikembangkan oleh mahasiswa sebagai tugas mata kuliah Software Development,
      dengan tujuan membuat waktu makan di kampus jadi lebih cepat, praktis, dan nyaman.
    </p>
          </div>
        </div>

        <div class="col-12 col-lg-3 col-md-4 col-sm-6 ms-md-5">
          <div class="footer-menu">
            <h3 class="widget-title">Contact Support</h3>
            <p style="font-size: 14px;">
              Callysta Elysia Theona Dethan {+62 825240012} <br>
              Suellen Abelvia {+62 825240025} <br>
              Tracy Prycillia Kusmana {+62 825240099} <br>
              Syahwa Aliah Wandi {+62 825240139}
            </p>
          </div>
        </div>

      </div>
    </div>
  </footer>

  <div id="footer-bottom">
    <div class="container-fluid">
      <div class="row">
        <div class="col-md-6 copyright">
          <p>© 2026 Antriless. All rights reserved.</p>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= MODAL INFO PESANAN ================= -->
  <div class="modal fade" id="modalInfoPesanan" tabindex="-1" aria-labelledby="labelInfoPesanan" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold" id="labelInfoPesanan">🧾 Status Pesanan Kamu</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
        </div>
        <div class="modal-body text-center py-4" id="infoPesananBody"></div>
        <div class="modal-footer border-0">
          <a href="status.php" class="btn btn-warning w-100">Lihat Detail Lengkap →</a>
        </div>
      </div>
    </div>
  </div>

  <!-- ================= STRUK PEMBAYARAN ================= -->
  <div class="modal" id="receiptModal">
    <div class="receipt">
      <button type="button" class="receipt-close" aria-label="Tutup" onclick="closeReceipt()">✕</button>

      <div class="receipt-title">TOTAL HARGA</div>
      <div class="receipt-list" id="receiptItems"></div>

      <div class="receipt-total">
        <span>Total</span>
        <span>Rp <span id="receiptTotal">0</span></span>
      </div>

      <div class="receipt-divider"></div>
      <div id="qrcode"></div>

      <button type="button" class="close-btn" onclick="closeReceipt()">Selesai</button>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js" integrity="sha384-ENjdO4Dr2bkBIFxQpeoTz1HIcje39Wm4jDKdf19U8gI4ddQ3GYNS7NTKfAdVQSZe" crossorigin="anonymous"></script>
  <script src="js/halaman%20utama.js?v=<?= filemtime(__DIR__ . '/js/halaman utama.js') ?>"></script>
  <script src="js/script.js?v=<?= filemtime(__DIR__ . '/js/script.js') ?>"></script>
</body>

</html>
<?php mysqli_close($conn); ?>
