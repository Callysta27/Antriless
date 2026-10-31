// Tempat menyimpan data keranjang belanja
let keranjang = [];

const cartTotalHeader = document.querySelector('.cart-total'); 
const cartList = document.getElementById('cart-list');         
const cartCount = document.getElementById('cart-count');       
const cartTotalOffcanvas = document.getElementById('cart-total');

// LOGIKA TOMBOL TAMBAH (+) DAN MINUS (-) PRODUK DI HALAMAN
document.querySelectorAll('.product-item').forEach(item => {
  const btnMinus = item.querySelector('.quantity-left-minus');
  const btnPlus = item.querySelector('.quantity-right-plus');
  const inputQty = item.querySelector('.input-number');

  if (btnPlus && inputQty) {
    btnPlus.addEventListener('click', () => {
      let nilai = parseInt(inputQty.value) || 1;
      inputQty.value = nilai + 1;
    });
  }

  if (btnMinus && inputQty) {
    btnMinus.addEventListener('click', () => {
      let nilai = parseInt(inputQty.value) || 1;
      if (nilai > 1) {
        inputQty.value = nilai - 1;
      }
    });
  }
});

// FUNGSI UTAMA UNTUK UPDATE TAMPILAN KERANJANG (HEADER & OFFCANVAS)
function updateTampilanKeranjang() {
  // Kosongkan isi list Offcanvas lama agar tidak menumpuk saat di-update
  if (cartList) {
    cartList.innerHTML = '';
  }
  
  let totalHarga = 0;
  let totalItem = 0;

  // Loop data keranjang untuk memunculkan item satu per satu ke Offcanvas
  keranjang.forEach((item) => {
    const subTotalItem = item.harga * item.jumlah;
    totalHarga += subTotalItem;
    totalItem += item.jumlah;

    if (cartList) {
      const listItem = document.createElement('li');
      // Menggunakan kelas Bootstrap agar tampilannya rapi dan serasi
      listItem.className = "list-group-item d-flex justify-content-between lh-sm text-dark";
      listItem.innerHTML = `
        <div>
          <h6 class="my-0 fw-bold">${item.nama}</h6>
          <small class="text-muted">Jumlah: ${item.jumlah}x</small>
        </div>
        <span class="text-muted">Rp ${subTotalItem.toLocaleString('id-ID')}</span>
      `;
      cartList.appendChild(listItem);
    }
  });

  // A. Update total harga di menu kanan atas Header
  if (cartTotalHeader) {
    cartTotalHeader.textContent = `Rp ${totalHarga.toLocaleString('id-ID')},-`;
  }
  
  // B. Update badge jumlah barang di dalam Offcanvas
  if (cartCount) {
    cartCount.textContent = totalItem;
  }

  // C. Update total harga paling bawah di dalam Offcanvas
  if (cartTotalOffcanvas) {
    cartTotalOffcanvas.textContent = `Rp ${totalHarga.toLocaleString('id-ID')}`;
  }
}

// EVENT LISTENER SAAT TOMBOL "Add to Cart" DIKLIK
document.querySelectorAll('.product-item .nav-link').forEach((tombol) => {
  tombol.addEventListener('click', function(e) {
    e.preventDefault(); // Mencegah halaman refresh
    
    const produkItem = this.closest('.product-item');
    
    // Membaca nama produk dari tag h3 dan harga dari class .price
    const namaProduk = produkItem.querySelector('h3').textContent.trim();
    const teksHarga = produkItem.querySelector('.price').textContent;
    const hargaProduk = parseInt(teksHarga.replace(/[^0-9]/g, '')); // Mengambil angka saja

    // Mengambil jumlah kuantitas dari kotak input
    const inputQty = produkItem.querySelector('.input-number');
    const jumlahDibeli = parseInt(inputQty.value) || 1;

    // Cek apakah makanan tersebut sudah ada di keranjang
    const produkSudahAda = keranjang.find(item => item.nama === namaProduk);

    if (produkSudahAda) {
      produkSudahAda.jumlah += jumlahDibeli;
    } else {
      keranjang.push({
        nama: namaProduk,
        harga: hargaProduk,
        jumlah: jumlahDibeli
      });
    }

    // Reset angka input produk kembali ke 1 setelah sukses ditambah
    inputQty.value = 1;

    // Jalankan pembaruan data visual
    updateTampilanKeranjang();
  });
});


// FILTER PRODUK: kata kunci + kategori + urutan termurah
const filterMenu = {
  kategori: 'all',
  keyword: '',
  termurah: false
};

const productGrid = document.querySelector('.product-grid');

// Simpan urutan asli supaya bisa kembali setelah "Termurah" dimatikan
if (productGrid) {
  productGrid.querySelectorAll(':scope > .col').forEach((col, i) => {
    col.dataset.urutan = i;
  });
}

function terapkanFilter() {
  if (!productGrid) return;

  const cols = Array.from(productGrid.querySelectorAll(':scope > .col'));
  let jumlahTampil = 0;

  cols.forEach(col => {
    const item = col.querySelector('.product-item');
    const kategoriProduk = item.dataset.kategori?.toLowerCase();
    const namaProduk = item.querySelector('h3').textContent.toLowerCase();

    const cocokKategori = filterMenu.kategori === 'all' || kategoriProduk === filterMenu.kategori;
    const cocokKeyword = namaProduk.includes(filterMenu.keyword);

    col.style.display = cocokKategori && cocokKeyword ? '' : 'none';
    if (cocokKategori && cocokKeyword) jumlahTampil++;
  });

  cols.sort((a, b) => {
    if (filterMenu.termurah) {
      const hargaA = parseInt(a.querySelector('.product-item').dataset.harga);
      const hargaB = parseInt(b.querySelector('.product-item').dataset.harga);
      return hargaA - hargaB;
    }
    return a.dataset.urutan - b.dataset.urutan;
  });
  cols.forEach(col => productGrid.appendChild(col));

  // Tab aktif
  document.querySelectorAll('#nav-tab [data-kategori]').forEach(tab => {
    tab.classList.toggle('active', tab.dataset.kategori === filterMenu.kategori);
  });
  document.getElementById('tab-termurah')?.classList.toggle('active', filterMenu.termurah);

  const pesanKosong = document.getElementById('menu-kosong');
  if (pesanKosong) pesanKosong.style.display = jumlahTampil ? 'none' : 'block';
}

function filterProduk(kategori) {
  filterMenu.kategori = kategori.toLowerCase();
  terapkanFilter();
}

// Klik "Termurah" sekali = urutkan dari yang termurah, klik lagi = urutan semula
function sortMurah() {
  filterMenu.termurah = !filterMenu.termurah;
  terapkanFilter();
}

// Dipakai kotak promo Dessert / Food di atas
function pilihKategori(kategori) {
  filterProduk(kategori);
  scrollMenu();
}

// PENCARIAN MENU
const searchInput = document.getElementById('search-input');

function cariMenu() {
  filterMenu.keyword = searchInput ? searchInput.value.trim().toLowerCase() : '';
  terapkanFilter();
}

if (searchInput) {
  searchInput.addEventListener('input', cariMenu);

  document.getElementById('search-form').addEventListener('submit', function (e) {
    e.preventDefault();             // jangan reload halaman
    cariMenu();
    scrollMenu();
  });
}

// Tombol "Order Now" di banner New Product
function lihatMenu(idMenu) {
  filterMenu.kategori = 'all';
  filterMenu.keyword = '';
  if (searchInput) searchInput.value = '';
  terapkanFilter();

  const kartu = document.getElementById('menu-' + idMenu);
  if (!kartu) return scrollMenu();

  kartu.scrollIntoView({ behavior: 'smooth', block: 'center' });
  kartu.classList.add('menu-sorot');
  setTimeout(() => kartu.classList.remove('menu-sorot'), 2000);
}

// SCROLL & CAROUSEL
function scrollMenu() {
  document.querySelector('#foods-menu').scrollIntoView({ behavior: 'smooth' });
}

// Tombol ❮ ❯ di Category & Newly Fresh Bake (arah: -1 kiri, 1 kanan)
function geser(carousel, arah) {
  document.querySelector(carousel + ' .swiper-wrapper')
    .scrollBy({ left: 350 * arah, behavior: 'smooth' });
}

// KOTAK PROMO (Dessert / Food) — bisa diklik atau ditekan Enter
document.querySelectorAll('[data-kategori-promo]').forEach(kotak => {
  const buka = () => pilihKategori(kotak.dataset.kategoriPromo);
  kotak.addEventListener('click', buka);
  kotak.addEventListener('keydown', e => {
    if (e.key === 'Enter') buka();
  });
});

// DROPDOWN PROFIL
const profileToggle = document.getElementById('profileToggle');
const profileDropdown = document.getElementById('profileDropdown');

profileToggle.addEventListener('click', function (e) {
  e.preventDefault();
  e.stopPropagation();
  profileDropdown.classList.toggle('tampil');
});

document.addEventListener('click', function (e) {
  if (!profileDropdown.contains(e.target)) profileDropdown.classList.remove('tampil');
});

// MODAL INFO PESANAN
// Kunci tanpa spasi & huruf kecil: "Diterima", "di terima", "DITERIMA" dianggap sama
const warnaStatus = {
  diterima:    'warning',
  diproses:    'primary',
  siapdiambil: 'success'
};
const labelStatus = {
  diterima:    'Di Terima 📋',
  diproses:    'Sedang Diproses 🍳',
  siapdiambil: 'Siap Diambil ✅'
};

// Teks dari database dimasukkan sebagai teks biasa, bukan HTML
function amankan(teks) {
  const div = document.createElement('div');
  div.textContent = teks ?? '';
  return div.innerHTML;
}

document.getElementById('modalInfoPesanan').addEventListener('show.bs.modal', function () {
  const isi = document.getElementById('infoPesananBody');
  isi.innerHTML = `
    <div class="spinner-border text-warning mb-3" role="status">
      <span class="visually-hidden">Loading...</span>
    </div>
    <p class="text-muted">Mengambil data pesanan...</p>
  `;

  fetch('status_pesanan.php')
    .then(res => res.json())
    .then(data => {
      if (data.status === 'ok') {
        const kunci = String(data.status_pesanan || '').toLowerCase().replace(/\s+/g, '');
        const wc = warnaStatus[kunci] || 'secondary';
        const lb = labelStatus[kunci] || amankan(data.status_pesanan);
        isi.innerHTML = `
          <span class="badge bg-${wc} fs-6 px-4 py-2 mb-3">${lb}</span>
          <p class="mb-1 fw-semibold">${amankan(data.nama_menu)}</p>
          <p class="text-muted small">Total: Rp ${Number(data.total).toLocaleString('id-ID')}</p>
        `;
      } else if (data.msg === 'not_logged_in') {
        isi.innerHTML = '<p class="text-muted">Silakan <a href="../login.php">login</a> terlebih dahulu.</p>';
      } else {
        isi.innerHTML = '<p class="text-muted">Belum ada pesanan aktif.</p>';
      }
    })
    .catch(() => {
      isi.innerHTML = '<p class="text-danger">Gagal mengambil data.</p>';
    });
});
