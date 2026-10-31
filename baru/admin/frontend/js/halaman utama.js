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


const searchInput = document.querySelector('#search-form input');

searchInput.addEventListener('keyup', function() {
    let kataKunci = this.value;
    
    // Mengirim data ke backend secara real-time tanpa reload page
    fetch('proses_cari.php?keyword=' + kataKunci)
        .then(response => response.text())
        .then(data => {
            // Tampilkan hasil pencarian di dalam wadah menu makanan Anda
            document.querySelector('#konten-makanan').innerHTML = data;
        });
});

const warna = {
  'di terima':    'warning',
  'di proses':    'primary',
  'siap diambil': 'success'
};
const label = {
  'di terima':    'Di Terima 📋',
  'di proses':    'Sedang Diproses 🍳',
  'siap diambil': 'Siap Diambil ✅'
};

// FILTER PRODUK BERDASARKAN KATEGORI
function filterProduk(kategori){

    document.querySelectorAll('.product-item').forEach(item => {

        const kategoriProduk =
            item.dataset.kategori?.toLowerCase();

        const card =
            item.closest('.col');

        if(
            kategori === 'all' ||
            kategoriProduk === kategori
        ){
            if(card) card.style.display = '';
        }else{
            if(card) card.style.display = 'none';
        }

    });

}

