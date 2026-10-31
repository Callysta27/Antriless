<?php
// ── Session & Auth ────────────────────────────────────────────────
require_once __DIR__ . '/../frontend/includes/config.php';

session_name('admin_session');
session_start();

if (empty($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (trim($_SESSION['role'] ?? '') !== 'pemilik_kantin') {
    header("Location: ../login.php");
    exit;
}

$id_kantin   = (int) $_SESSION['user_id'];   // id pemilik kantin = menu.id_kantin
$nama_user   = $_SESSION['nama'];
$role        = $_SESSION['role'];
$nama_kantin = $_SESSION['nama_kantin'] ?? '';

$daftarKategori = ['Makanan', 'Minuman', 'Camilan', 'Dessert'];
$daftarStatus   = ['tersedia', 'habis', 'nonaktif'];

// ── Fungsi upload foto ────────────────────────────────────────────
// Jenis file dicek dari ISI file (bukan dari nama/tipe kiriman browser),
// supaya file .php yang menyamar sebagai gambar tidak bisa di-upload.
function uploadFoto($key)
{
    if (empty($_FILES[$key]['name']) || $_FILES[$key]['error'] !== UPLOAD_ERR_OK) return null;

    $file = $_FILES[$key];
    if ($file['size'] > 2 * 1024 * 1024) return null;

    $ekstensi = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];

    $tipe = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    if (!isset($ekstensi[$tipe])) return null;

    $dir = 'uploads/menu/';
    if (!is_dir($dir)) mkdir($dir, 0755, true);

    $filename = 'menu_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ekstensi[$tipe];
    return move_uploaded_file($file['tmp_name'], $dir . $filename) ? $dir . $filename : null;
}

function hapusFoto($path)
{
    // hanya hapus file di dalam uploads/menu/
    if ($path && str_starts_with($path, 'uploads/menu/') && is_file(__DIR__ . '/' . $path)) {
        @unlink(__DIR__ . '/' . $path);
    }
}

// Status otomatis "habis" kalau stok 0, kecuali sengaja dinonaktifkan
function tentukanStatus($stok, $statusDipilih)
{
    if ($statusDipilih === 'nonaktif') return 'nonaktif';
    return $stok <= 0 ? 'habis' : 'tersedia';
}

// ── Handler Aksi ──────────────────────────────────────────────────
$aksi = $_POST['aksi'] ?? $_GET['aksi'] ?? '';

// TAMBAH
if ($aksi === 'tambah' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_menu = trim($_POST['nama_menu'] ?? '');
    $harga     = max(0, (int) ($_POST['harga'] ?? 0));
    $stok      = max(0, (int) ($_POST['stok'] ?? 0));
    $kategori  = in_array($_POST['kategori'] ?? '', $daftarKategori, true) ? $_POST['kategori'] : 'Makanan';
    $status    = tentukanStatus($stok, 'tersedia');

    if (!empty($_FILES['foto']['name'])) {
        $foto = uploadFoto('foto');
        if (!$foto) {
            header("Location: products.php?msg=foto_gagal");
            exit;
        }
    } else {
        $foto = null;
    }

    $stmt = mysqli_prepare($conn, "
        INSERT INTO menu (id_kantin, nama_kantin, nama_menu, harga, stok, kategori, status, foto)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    mysqli_stmt_bind_param($stmt, 'issiisss', $id_kantin, $nama_kantin, $nama_menu, $harga, $stok, $kategori, $status, $foto);

    if (mysqli_stmt_execute($stmt)) {
        header("Location: products.php?msg=tambah_ok");
    } else {
        hapusFoto($foto);
        header("Location: products.php?msg=tambah_gagal");
    }
    exit;
}

// EDIT
if ($aksi === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_menu   = (int) ($_POST['id_menu'] ?? 0);
    $nama_menu = trim($_POST['nama_menu'] ?? '');
    $harga     = max(0, (int) ($_POST['harga'] ?? 0));
    $stok      = max(0, (int) ($_POST['stok'] ?? 0));
    $kategori  = in_array($_POST['kategori'] ?? '', $daftarKategori, true) ? $_POST['kategori'] : 'Makanan';
    $status    = tentukanStatus($stok, $_POST['status'] ?? '');

    // ambil foto lama (sekaligus memastikan menu ini milik kantin yang login)
    $stmt = mysqli_prepare($conn, "SELECT foto FROM menu WHERE id_menu = ? AND id_kantin = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $id_menu, $id_kantin);
    mysqli_stmt_execute($stmt);
    $lama = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$lama) {
        header("Location: products.php?msg=edit_gagal");
        exit;
    }

    $foto = $lama['foto'];
    if (!empty($_FILES['foto']['name'])) {
        $fotoBaru = uploadFoto('foto');
        if (!$fotoBaru) {
            header("Location: products.php?msg=foto_gagal");
            exit;
        }
        $foto = $fotoBaru;
    }

    $stmt = mysqli_prepare($conn, "
        UPDATE menu
        SET nama_menu = ?, harga = ?, stok = ?, kategori = ?, status = ?, foto = ?
        WHERE id_menu = ? AND id_kantin = ?
    ");
    mysqli_stmt_bind_param($stmt, 'siisssii', $nama_menu, $harga, $stok, $kategori, $status, $foto, $id_menu, $id_kantin);

    if (mysqli_stmt_execute($stmt)) {
        if ($foto !== $lama['foto']) hapusFoto($lama['foto']);   // buang foto lama yang sudah diganti
        header("Location: products.php?msg=edit_ok");
    } else {
        if ($foto !== $lama['foto']) hapusFoto($foto);
        header("Location: products.php?msg=edit_gagal");
    }
    exit;
}

// HAPUS (lewat POST supaya tidak bisa terpicu hanya dengan membuka link)
if ($aksi === 'hapus' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_menu = (int) ($_POST['id_menu'] ?? 0);

    $stmt = mysqli_prepare($conn, "SELECT foto FROM menu WHERE id_menu = ? AND id_kantin = ?");
    mysqli_stmt_bind_param($stmt, 'ii', $id_menu, $id_kantin);
    mysqli_stmt_execute($stmt);
    $menu = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if (!$menu) {
        header("Location: products.php");
        exit;
    }

    try {
        $stmt = mysqli_prepare($conn, "DELETE FROM menu WHERE id_menu = ? AND id_kantin = ?");
        mysqli_stmt_bind_param($stmt, 'ii', $id_menu, $id_kantin);
        $berhasil = mysqli_stmt_execute($stmt);
    } catch (mysqli_sql_exception $e) {
        $berhasil = false;
    }

    if ($berhasil) {
        hapusFoto($menu['foto']);
        header("Location: products.php?msg=hapus_ok");
    } else {
        // menu sudah pernah dipesan (terhubung ke detail_order) → tidak bisa dihapus
        header("Location: products.php?msg=hapus_gagal");
    }
    exit;
}

// ── Ambil data ────────────────────────────────────────────────────
$filter_status = $_GET['status'] ?? 'all';
if ($filter_status !== 'all' && !in_array($filter_status, $daftarStatus, true)) {
    $filter_status = 'all';
}

$stmt = mysqli_prepare($conn, "SELECT * FROM menu WHERE id_kantin = ? ORDER BY created_at DESC");
mysqli_stmt_bind_param($stmt, 'i', $id_kantin);
mysqli_stmt_execute($stmt);
$semuaMenu = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

$count_map = ['all' => 0, 'tersedia' => 0, 'habis' => 0, 'nonaktif' => 0];
foreach ($semuaMenu as $m) {
    $count_map['all']++;
    if (isset($count_map[$m['status']])) $count_map[$m['status']]++;
}

$daftarMenu = $filter_status === 'all'
    ? $semuaMenu
    : array_values(array_filter($semuaMenu, fn($m) => $m['status'] === $filter_status));

$thumb_map   = ['makanan' => '🍛', 'minuman' => '🧋', 'camilan' => '🍟', 'snack' => '🍟', 'dessert' => '🍰'];
$modal_style = "display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:1000;align-items:center;justify-content:center;";

$pesan = [
    'tambah_ok'    => ['green', '✅ Menu berhasil ditambahkan!'],
    'tambah_gagal' => ['red',   '❌ Gagal menambahkan menu. Coba lagi.'],
    'edit_ok'      => ['green', '✅ Menu berhasil diperbarui!'],
    'edit_gagal'   => ['red',   '❌ Gagal memperbarui menu.'],
    'hapus_ok'     => ['red',   '🗑️ Menu berhasil dihapus.'],
    'hapus_gagal'  => ['red',   '❌ Menu ini sudah pernah dipesan, jadi tidak bisa dihapus. Ubah statusnya menjadi Nonaktif saja.'],
    'foto_gagal'   => ['red',   '❌ Foto tidak valid. Gunakan JPG, PNG, atau WEBP maksimal 2 MB.'],
];
$msg   = $_GET['msg'] ?? '';
$notif = isset($pesan[$msg]) ? ['type' => $pesan[$msg][0], 'text' => $pesan[$msg][1]] : null;
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>AdminPanel — Menu <?= htmlspecialchars($nama_kantin) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com"/>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet"/>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css"/>
  <link rel="stylesheet" href="style/produk.css?v=<?= filemtime(__DIR__ . '/style/produk.css') ?>"/>
</head>
<body>
<div class="layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div class="sidebar-brand">
      <div class="brand-icon"><i class="ti ti-layout-dashboard"></i></div>
      AdminPanel
    </div>

    <div class="nav-section">Main</div>
    <a href="dashboard.php"  class="nav-item"><i class="ti ti-home"></i> Dashboard</a>
    <a href="orderPage.php"  class="nav-item"><i class="ti ti-shopping-cart"></i> Orders</a>
    <a href="products.php"   class="nav-item active"><i class="ti ti-box"></i> Products</a>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
      <div class="user-avatar"><?= htmlspecialchars(strtoupper(substr($nama_user, 0, 2))) ?></div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($nama_user) ?></div>
        <div class="user-role"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $role))) ?></div>
      </div>
      <div style="position:relative">
        <i class="ti ti-dots-vertical" id="menuToggle"
           style="color:var(--text3);cursor:pointer;font-size:16px"></i>
        <div id="userDropdown" style="display:none;position:absolute;bottom:40px;right:-8px;
          background:var(--surface);border:1px solid var(--border2);border-radius:var(--radius);
          box-shadow:0 8px 24px rgba(0,0,0,0.12);min-width:160px;z-index:99;overflow:hidden;padding:4px;">
          <div style="padding:8px 12px 6px;border-bottom:1px solid var(--border);margin-bottom:4px">
            <div style="font-size:12px;font-weight:600;color:var(--text)"><?= htmlspecialchars($nama_user) ?></div>
            <div style="font-size:11px;color:var(--text3)"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $role))) ?></div>
          </div>
          <a href="profil.php" style="display:flex;align-items:center;gap:8px;padding:8px 12px;font-size:13px;
            color:var(--text);text-decoration:none;border-radius:8px;transition:background 0.15s;"
            onmouseover="this.style.background='var(--surface2)'" onmouseout="this.style.background='transparent'">
            <i class="ti ti-user" style="font-size:15px;color:var(--text2)"></i> Profil Saya
          </a>
          <a href="../login.php" style="display:flex;align-items:center;gap:8px;padding:8px 12px;font-size:13px;
            color:var(--red);text-decoration:none;border-radius:8px;transition:background 0.15s;"
            onmouseover="this.style.background='var(--red-light)'" onmouseout="this.style.background='transparent'">
            <i class="ti ti-logout" style="font-size:15px"></i> Logout
          </a>
        </div>
      </div>
    </div>
  </aside>

  <!-- Main -->
  <div class="main">
    <header class="topbar">
      <div class="topbar-title">
        Dashboard <span class="breadcrumb">/ Produk</span>
      </div>
      <div class="topbar-spacer"></div>
      <div class="search">
        <i class="ti ti-search"></i>
        <!-- FIX: id ditambahkan, sebelumnya script pencarian error karena elemen ini tidak ditemukan -->
        <input type="text" id="searchInput" placeholder="Cari menu..." />
      </div>
      <div class="icon-btn">
        <i class="ti ti-bell"></i>
        <div class="notif-dot"></div>
      </div>

      <!-- Avatar Topbar -->
      <div style="position:relative">
        <div class="user-avatar" id="avatarToggle" style="width:34px;height:34px;cursor:pointer">
          <?= htmlspecialchars(strtoupper(substr($nama_user, 0, 2))) ?>
        </div>
        <div id="avatarDropdown" style="display:none;position:absolute;top:42px;right:0;
          background:var(--surface);border:1px solid var(--border2);border-radius:var(--radius);
          box-shadow:0 8px 24px rgba(0,0,0,0.12);min-width:180px;z-index:99;overflow:hidden;padding:4px;">
          <div style="padding:8px 12px;border-bottom:1px solid var(--border);margin-bottom:4px">
            <div style="font-size:12px;font-weight:600;color:var(--text)"><?= htmlspecialchars($nama_user) ?></div>
            <div style="font-size:11px;color:var(--text3)"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $role))) ?></div>
          </div>
          <a href="profil.php" style="display:flex;align-items:center;gap:8px;padding:8px 12px;font-size:13px;
            color:var(--text);text-decoration:none;border-radius:8px;transition:background 0.15s;"
            onmouseover="this.style.background='var(--surface2)'" onmouseout="this.style.background='transparent'">
            <i class="ti ti-user" style="font-size:15px;color:var(--text2)"></i> Profil Saya
          </a>
          <a href="../login.php" style="display:flex;align-items:center;gap:8px;padding:8px 12px;font-size:13px;
            color:var(--red);text-decoration:none;border-radius:8px;transition:background 0.15s;"
            onmouseover="this.style.background='var(--red-light)'" onmouseout="this.style.background='transparent'">
            <i class="ti ti-logout" style="font-size:15px"></i> Logout
          </a>
        </div>
      </div>
    </header>

    <div class="content">

      <?php if ($notif): ?>
      <div id="notifBar" style="
        background:<?= $notif['type'] === 'green' ? 'var(--teal-light)' : 'var(--red-light)' ?>;
        color:<?= $notif['type'] === 'green' ? '#0F6E56' : 'var(--red)' ?>;
        padding:10px 16px;border-radius:var(--radius);
        font-size:13px;font-weight:500;margin-bottom:16px;">
        <?= htmlspecialchars($notif['text']) ?>
      </div>
      <?php endif; ?>

      <div class="page-header">
        <div>
          <h1>Menu — <?= htmlspecialchars($nama_kantin ?: 'Kantin') ?></h1>
          <p>Kelola daftar menu kantin Anda.</p>
        </div>
        <button class="btn-primary" onclick="bukaModal('modalTambah')">
          <i class="ti ti-plus"></i> Tambah Menu
        </button>
      </div>

      <div class="filter-bar">
        <?php foreach (['all' => 'Semua', 'tersedia' => 'Tersedia', 'habis' => 'Habis', 'nonaktif' => 'Nonaktif'] as $key => $label): ?>
          <a href="?status=<?= $key ?>" class="filter-btn <?= $filter_status === $key ? 'active' : '' ?>">
            <?= $label ?> <span class="filter-count"><?= $count_map[$key] ?></span>
          </a>
        <?php endforeach; ?>
      </div>

      <div class="menu-grid" id="menuGrid">
        <?php if (!$daftarMenu): ?>
          <div class="empty-state">
            <i class="ti ti-mood-empty"></i>
            <p>Tidak ada menu. Klik <strong>Tambah Menu</strong> untuk memulai.</p>
          </div>
        <?php else: foreach ($daftarMenu as $row):
          $st       = $row['status'];
          $hargaFmt = 'Rp ' . number_format($row['harga'], 0, ',', '.');
          $emoji    = $thumb_map[strtolower($row['kategori'])] ?? '🍽️';
          $fotoUrl  = !empty($row['foto']) ? htmlspecialchars($row['foto']) : '';
          $stok     = (int) $row['stok'];

          // data untuk modal edit, dikirim sebagai JSON supaya aman dari tanda kutip di nama menu
          $dataEdit = htmlspecialchars(json_encode([
            'id'       => (int) $row['id_menu'],
            'nama'     => $row['nama_menu'],
            'harga'    => (int) $row['harga'],
            'stok'     => $stok,
            'kategori' => ucfirst(strtolower($row['kategori'])),
            'status'   => $st,
          ]), ENT_QUOTES, 'UTF-8');
        ?>
          <div class="menu-card" data-nama="<?= htmlspecialchars(strtolower($row['nama_menu'])) ?>">
            <div class="menu-thumb">
              <?php if ($fotoUrl): ?>
                <img src="<?= $fotoUrl ?>" alt="foto menu" style="width:100%;height:110px;object-fit:cover;display:block"/>
              <?php else: ?>
                <?= $emoji ?>
              <?php endif; ?>
              <div class="menu-status">
                <span class="badge badge-<?= htmlspecialchars($st) ?>"><?= htmlspecialchars(ucfirst($st)) ?></span>
              </div>
            </div>
            <div class="menu-body">
              <div class="menu-nama"><?= htmlspecialchars($row['nama_menu']) ?></div>
              <div class="menu-kategori"><?= htmlspecialchars($row['kategori']) ?></div>
              <div class="menu-harga-stok">
                <span class="menu-harga"><?= $hargaFmt ?></span>
                <span class="menu-stok <?= $stok === 0 ? 'stok-habis' : ($stok <= 5 ? 'stok-sedikit' : '') ?>">
                  <i class="ti ti-stack-2"></i> <?= $stok ?>
                </span>
              </div>
            </div>
            <div class="menu-actions">
              <button class="menu-act-btn menu-act-edit" data-menu="<?= $dataEdit ?>" onclick="bukaModalEdit(this)">
                <i class="ti ti-pencil" style="font-size:13px"></i> Edit
              </button>
              <button class="menu-act-btn menu-act-delete" onclick="bukaModalHapus(<?= (int) $row['id_menu'] ?>)">
                <i class="ti ti-trash" style="font-size:13px"></i> Hapus
              </button>
            </div>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<!-- ══════════════════════════════════
     Modal TAMBAH
     ══════════════════════════════════ -->
<div id="modalTambah" style="<?= $modal_style ?>">
  <div class="modal-box">

    <div class="modal-header">
      <h3><i class="ti ti-plus" style="font-size:15px;margin-right:6px"></i>Tambah Menu</h3>
      <button class="modal-close-btn" onclick="tutupModal('modalTambah')">✕</button>
    </div>

    <form method="POST" action="products.php" enctype="multipart/form-data">
      <input type="hidden" name="aksi" value="tambah"/>

      <div class="modal-body">

        <div class="modal-field">
          <label>Foto Menu <span style="color:var(--text3)">(opsional · maks 2 MB · JPG / PNG / WEBP)</span></label>
          <div class="foto-upload-area" onclick="document.getElementById('fotoTambah').click()">
            <img id="previewTambah" class="foto-preview" src="" alt="preview"/>
            <div id="placeholderTambah">
              <i class="ti ti-photo-plus"></i>
              <p>Klik untuk pilih foto</p>
            </div>
          </div>
          <input type="file" id="fotoTambah" name="foto" accept="image/jpeg,image/png,image/webp" style="display:none"
                 onchange="previewFoto(this,'previewTambah','placeholderTambah')"/>
        </div>

        <div class="modal-field">
          <label>Nama Menu <span style="color:var(--red)">*</span></label>
          <input type="text" name="nama_menu" placeholder="cth: Nasi Goreng Spesial" maxlength="100" required/>
        </div>

        <div class="modal-row">
          <div class="modal-field">
            <label>Harga (Rp) <span style="color:var(--red)">*</span></label>
            <input type="number" name="harga" placeholder="15000" min="0" required/>
          </div>
          <div class="modal-field">
            <label>Stok <span style="color:var(--red)">*</span></label>
            <input type="number" name="stok" placeholder="0" min="0" value="0" required/>
          </div>
        </div>

        <div class="modal-field">
          <label>Kategori</label>
          <select name="kategori">
            <option value="Makanan">🍛 Makanan</option>
            <option value="Minuman">🧋 Minuman</option>
            <option value="Camilan">🍟 Camilan</option>
            <option value="Dessert">🍰 Dessert</option>
          </select>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="tutupModal('modalTambah')">Batal</button>
        <button type="submit" class="btn-primary">
          <i class="ti ti-check" style="font-size:14px"></i> Simpan Menu
        </button>
      </div>
    </form>
  </div>
</div>


<!-- ══════════════════════════════════
     Modal EDIT
     ══════════════════════════════════ -->
<div id="modalEdit" style="<?= $modal_style ?>">
  <div class="modal-box">

    <div class="modal-header">
      <h3><i class="ti ti-pencil" style="font-size:15px;margin-right:6px"></i>Edit Menu</h3>
      <button class="modal-close-btn" onclick="tutupModal('modalEdit')">✕</button>
    </div>

    <form method="POST" action="products.php" enctype="multipart/form-data">
      <input type="hidden" name="aksi"    value="edit"/>
      <input type="hidden" name="id_menu" id="edit_id"/>

      <div class="modal-body">

        <div class="modal-field">
          <label>Ganti Foto <span style="color:var(--text3)">(biarkan kosong jika tidak ingin mengganti)</span></label>
          <div class="foto-upload-area" onclick="document.getElementById('fotoEdit').click()">
            <img id="previewEdit" class="foto-preview" src="" alt="preview"/>
            <div id="placeholderEdit">
              <i class="ti ti-photo-edit"></i>
              <p>Klik untuk ganti foto</p>
            </div>
          </div>
          <input type="file" id="fotoEdit" name="foto" accept="image/jpeg,image/png,image/webp" style="display:none"
                 onchange="previewFoto(this,'previewEdit','placeholderEdit')"/>
        </div>

        <div class="modal-field">
          <label>Nama Menu <span style="color:var(--red)">*</span></label>
          <input type="text" name="nama_menu" id="edit_nama" maxlength="100" required/>
        </div>

        <div class="modal-row">
          <div class="modal-field">
            <label>Harga (Rp) <span style="color:var(--red)">*</span></label>
            <input type="number" name="harga" id="edit_harga" min="0" required/>
          </div>
          <div class="modal-field">
            <label>Stok <span style="color:var(--red)">*</span></label>
            <input type="number" name="stok" id="edit_stok" min="0" required/>
          </div>
        </div>

        <div class="modal-row">
          <div class="modal-field">
            <label>Kategori</label>
            <select name="kategori" id="edit_kategori">
              <option value="Makanan">🍛 Makanan</option>
              <option value="Minuman">🧋 Minuman</option>
              <option value="Camilan">🍟 Camilan</option>
              <option value="Dessert">🍰 Dessert</option>
            </select>
          </div>
          <div class="modal-field">
            <label>Status</label>
            <select name="status" id="edit_status">
              <option value="tersedia">✅ Tersedia</option>
              <option value="habis">⏳ Habis</option>
              <option value="nonaktif">🚫 Nonaktif</option>
            </select>
            <small style="color:var(--text3);font-size:11px">Stok 0 otomatis menjadi Habis.</small>
          </div>
        </div>

      </div>

      <div class="modal-footer">
        <button type="button" class="btn-secondary" onclick="tutupModal('modalEdit')">Batal</button>
        <button type="submit" class="btn-primary">
          <i class="ti ti-check" style="font-size:14px"></i> Simpan Perubahan
        </button>
      </div>
    </form>
  </div>
</div>


<!-- ══════════════════════════════════
     Modal HAPUS
     ══════════════════════════════════ -->
<div id="modalHapus" style="<?= $modal_style ?>">
  <div class="modal-box" style="max-width:340px">

    <div class="modal-delete-body">
      <div class="modal-delete-icon">🗑️</div>
      <h3>Hapus Menu Ini?</h3>
      <p>Menu ini akan dihapus secara permanen dan tidak dapat dikembalikan.</p>
    </div>

    <form method="POST" action="products.php" class="modal-delete-footer">
      <input type="hidden" name="aksi" value="hapus"/>
      <input type="hidden" name="id_menu" id="hapus_id"/>
      <button type="button" class="btn-secondary" onclick="tutupModal('modalHapus')">Batal</button>
      <button type="submit" class="btn-primary" style="background:var(--red)">
        <i class="ti ti-trash" style="font-size:14px"></i> Ya, Hapus
      </button>
    </form>

  </div>
</div>


<script>
  // ── Modal ──
  function bukaModal(id)  { document.getElementById(id).style.display = 'flex'; }
  function tutupModal(id) { document.getElementById(id).style.display = 'none'; }

  ['modalTambah', 'modalEdit', 'modalHapus'].forEach(function (id) {
    document.getElementById(id).addEventListener('click', function (e) {
      if (e.target === this) tutupModal(id);
    });
  });

  function previewFoto(input, previewId, placeholderId) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function (e) {
      const img = document.getElementById(previewId);
      img.src = e.target.result;
      img.style.display = 'block';
      document.getElementById(placeholderId).style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
  }

  function bukaModalEdit(tombol) {
    const m = JSON.parse(tombol.dataset.menu);
    document.getElementById('edit_id').value       = m.id;
    document.getElementById('edit_nama').value     = m.nama;
    document.getElementById('edit_harga').value    = m.harga;
    document.getElementById('edit_stok').value     = m.stok;
    document.getElementById('edit_kategori').value = m.kategori;
    document.getElementById('edit_status').value   = m.status;

    document.getElementById('previewEdit').style.display     = 'none';
    document.getElementById('placeholderEdit').style.display = '';
    document.getElementById('fotoEdit').value = '';
    bukaModal('modalEdit');
  }

  function bukaModalHapus(id) {
    document.getElementById('hapus_id').value = id;
    bukaModal('modalHapus');
  }

  // ── Pencarian menu ──
  const searchInput = document.getElementById('searchInput');
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      const q = this.value.toLowerCase();
      document.querySelectorAll('.menu-card').forEach(function (c) {
        c.style.display = c.dataset.nama.includes(q) ? '' : 'none';
      });
    });
  }

  // ── Dropdown user (sidebar & topbar) ──
  const menuToggle     = document.getElementById('menuToggle');
  const userDropdown   = document.getElementById('userDropdown');
  const avatarToggle   = document.getElementById('avatarToggle');
  const avatarDropdown = document.getElementById('avatarDropdown');

  function toggle(dropdown, lainnya) {
    return function (e) {
      e.stopPropagation();
      lainnya.style.display  = 'none';
      dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
    };
  }

  menuToggle.addEventListener('click', toggle(userDropdown, avatarDropdown));
  avatarToggle.addEventListener('click', toggle(avatarDropdown, userDropdown));

  document.addEventListener('click', function () {
    userDropdown.style.display   = 'none';
    avatarDropdown.style.display = 'none';
  });

  // ── Notifikasi hilang otomatis ──
  setTimeout(function () {
    const n = document.getElementById('notifBar');
    if (n) n.style.display = 'none';
  }, 3000);
</script>
</body>
</html>
<?php mysqli_close($conn); ?>