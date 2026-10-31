<?php
// ── Session & Auth ────────────────────────────────────────────────
require_once __DIR__ . '/../frontend/includes/config.php';

session_name('admin_session');
session_start(); // ini gua ubah

if (empty($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id     = $_SESSION['user_id'];
$nama_user   = $_SESSION['nama'];
$role        = $_SESSION['role'];
$nama_kantin = $_SESSION['nama_kantin'] ?? '';

$words   = explode(' ', trim($nama_user));
$inisial = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : substr($words[0], 1, 1)));

// ── Fungsi upload foto ────────────────────────────────────────────
function uploadFoto($key) {
    if (empty($_FILES[$key]['name'])) return null;
    $file    = $_FILES[$key];
    $allowed = ['image/jpeg','image/jpg','image/png','image/webp'];
    if (!in_array($file['type'], $allowed)) return null;
    if ($file['size'] > 2 * 1024 * 1024) return null;
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'menu_' . time() . '_' . rand(100,999) . '.' . $ext;
    $dir      = 'uploads/menu/';
    if (!is_dir($dir)) mkdir($dir, 0777, true);
    if (move_uploaded_file($file['tmp_name'], $dir . $filename)) return $dir . $filename;
    return null;
}

// ── Handler Aksi ──────────────────────────────────────────────────
$aksi = $_POST['aksi'] ?? $_GET['aksi'] ?? '';

// TAMBAH
if ($aksi === 'tambah' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_menu       = mysqli_real_escape_string($conn, trim($_POST['nama_menu']));
    $harga           = (float)$_POST['harga'];
    $stok            = (int)$_POST['stok'];
    $kategori        = mysqli_real_escape_string($conn, trim($_POST['kategori']));
    $nama_kantin_esc = mysqli_real_escape_string($conn, $nama_kantin);
    $foto            = uploadFoto('foto');
    $foto_val        = $foto ? "'".mysqli_real_escape_string($conn,$foto)."'" : "NULL";

     $status = ($stok <= 0) ? 'habis' : 'tersedia';

$sql = "INSERT INTO menu (id_kantin, nama_kantin, nama_menu, harga, stok, kategori, status, foto)
        VALUES ('$user_id', '$nama_kantin_esc', '$nama_menu', '$harga', '$stok', '$kategori', '$status', $foto_val)";

    if (mysqli_query($conn, $sql)) {
        header("Location: products.php?msg=tambah_ok");
    } else {
        header("Location: products.php?msg=tambah_gagal&err=" . urlencode(mysqli_error($conn)));
    }
    exit;
}

// EDIT
if ($aksi === 'edit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id_menu   = (int)$_POST['id_menu'];
    $nama_menu = mysqli_real_escape_string($conn, trim($_POST['nama_menu']));
    $harga     = (float)$_POST['harga'];
    $stok      = (int)$_POST['stok'];
    $kategori  = mysqli_real_escape_string($conn, trim($_POST['kategori']));
    $status = ($stok <= 0) ? 'habis' : 'tersedia';
    $foto_sql  = '';
    $foto_baru = uploadFoto('foto');
    if ($foto_baru) {
        $foto_sql = ", foto='".mysqli_real_escape_string($conn,$foto_baru)."'";
    }

    $sql = "UPDATE menu SET nama_menu='$nama_menu', harga='$harga', stok='$stok', kategori='$kategori', status='$status' $foto_sql
            WHERE id_menu=$id_menu AND id_kantin='$user_id'";

    if (mysqli_query($conn, $sql)) {
        header("Location: products.php?msg=edit_ok");
    } else {
        header("Location: products.php?msg=edit_gagal&err=" . urlencode(mysqli_error($conn)));
    }
    exit;
}

// HAPUS
if ($aksi === 'hapus' && isset($_GET['id'])) {
    $id_menu = (int)$_GET['id'];
    mysqli_query($conn, "DELETE FROM menu WHERE id_menu=$id_menu AND id_kantin='$user_id'");
    header("Location: products.php?msg=hapus_ok");
    exit;
}

// ── Ambil data ────────────────────────────────────────────────────
$filter_status = $_GET['status'] ?? 'all';
$uid_esc       = mysqli_real_escape_string($conn, $user_id);
$where         = "WHERE id_kantin='$uid_esc'";
if ($filter_status !== 'all') {
    $fs     = mysqli_real_escape_string($conn, $filter_status);
    $where .= " AND status='$fs'";
}

$result    = mysqli_query($conn, "SELECT * FROM menu $where ORDER BY created_at DESC");
$count_res = mysqli_query($conn, "SELECT status, COUNT(*) as total FROM menu WHERE id_kantin='$uid_esc' GROUP BY status");
$count_map = ['all'=>0,'tersedia'=>0,'habis'=>0,'nonaktif'=>0];
while ($c = mysqli_fetch_assoc($count_res)) {
    $count_map[$c['status']] = (int)$c['total'];
    $count_map['all']       += (int)$c['total'];
}

$thumb_map   = ['makanan'=>'🍛','minuman'=>'🧋','camilan'=>'🍟','snack'=>'🍟','dessert'=>'🍰'];
$modal_style = "display:none;position:fixed;inset:0;background:rgba(0,0,0,0.45);z-index:1000;align-items:center;justify-content:center;";

$msg   = $_GET['msg'] ?? '';
$notif = null;
if ($msg === 'tambah_ok')    $notif = ['type'=>'green','text'=>'✅ Menu berhasil ditambahkan!'];
if ($msg === 'tambah_gagal') $notif = ['type'=>'red',  'text'=>'❌ Gagal tambah: '.($_GET['err']??'')];
if ($msg === 'edit_ok')      $notif = ['type'=>'green','text'=>'✅ Menu berhasil diperbarui!'];
if ($msg === 'edit_gagal')   $notif = ['type'=>'red',  'text'=>'❌ Gagal edit: '.($_GET['err']??'')];
if ($msg === 'hapus_ok')     $notif = ['type'=>'red',  'text'=>'🗑️ Menu berhasil dihapus.'];
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>AdminPanel — Menu</title>
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
    <a href="orderPage.php" class="nav-item"><i class="ti ti-shopping-cart"></i> Orders</a>
    <a href="products.php"   class="nav-item active"><i class="ti ti-box"></i> Products</a>

    <!-- Sidebar Footer -->
    <div class="sidebar-footer">
      <div class="user-avatar">
        <?= strtoupper(substr($nama_user, 0, 2)) ?>
      </div>
      <div class="user-info">
        <div class="user-name"><?= htmlspecialchars($nama_user) ?></div>
        <div class="user-role"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $role))) ?></div>
      </div>
      <div style="position:relative">
        <i class="ti ti-dots-vertical" id="menuToggle"
           style="color:var(--text3);cursor:pointer;font-size:16px"></i>
        <div id="userDropdown" style="
          display:none;
          position:absolute;
          bottom:40px;
          right:-8px;
          background:var(--surface);
          border:1px solid var(--border2);
          border-radius:var(--radius);
          box-shadow:0 8px 24px rgba(0,0,0,0.12);
          min-width:160px;
          z-index:99;
          overflow:hidden;
          padding:4px;
        ">
          <div style="padding:8px 12px 6px;border-bottom:1px solid var(--border);margin-bottom:4px">
            <div style="font-size:12px;font-weight:600;color:var(--text)"><?= htmlspecialchars($nama_user) ?></div>
            <div style="font-size:11px;color:var(--text3)"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $role))) ?></div>
          </div>
          <a href="profil.php" style="
            display:flex;align-items:center;gap:8px;
            padding:8px 12px;font-size:13px;color:var(--text);
            text-decoration:none;border-radius:8px;transition:background 0.15s;
          " onmouseover="this.style.background='var(--surface2)'"
             onmouseout="this.style.background='transparent'">
            <i class="ti ti-user" style="font-size:15px;color:var(--text2)"></i> Profil Saya
          </a>
          <a href="../login.php" style="
            display:flex;align-items:center;gap:8px;
            padding:8px 12px;font-size:13px;color:var(--red);
            text-decoration:none;border-radius:8px;transition:background 0.15s;
          " onmouseover="this.style.background='var(--red-light)'"
             onmouseout="this.style.background='transparent'">
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
            <input type="text" placeholder="Search anything..." />
          </div>
          <div class="icon-btn">
            <i class="ti ti-bell"></i>
            <div class="notif-dot"></div>
          </div>

          <!-- Avatar Topbar -->
          <div style="position:relative">
            <div class="user-avatar" id="avatarToggle"
                 style="width:34px;height:34px;cursor:pointer">
              <?= strtoupper(substr($nama_user, 0, 2)) ?>
            </div>
            <div id="avatarDropdown" style="
              display:none;
              position:absolute;
              top:42px;
              right:0;
              background:var(--surface);
              border:1px solid var(--border2);
              border-radius:var(--radius);
              box-shadow:0 8px 24px rgba(0,0,0,0.12);
              min-width:180px;
              z-index:99;
              overflow:hidden;
              padding:4px;
            ">
              <div style="padding:8px 12px;border-bottom:1px solid var(--border);margin-bottom:4px">
                <div style="font-size:12px;font-weight:600;color:var(--text)"><?= htmlspecialchars($nama_user) ?></div>
                <div style="font-size:11px;color:var(--text3)"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $role))) ?></div>
              </div>
              <a href="profil.php" style="
                display:flex;align-items:center;gap:8px;
                padding:8px 12px;font-size:13px;color:var(--text);
                text-decoration:none;border-radius:8px;transition:background 0.15s;
              " onmouseover="this.style.background='var(--surface2)'"
                 onmouseout="this.style.background='transparent'">
                <i class="ti ti-user" style="font-size:15px;color:var(--text2)"></i> Profil Saya
              </a>
              <a href="../login.php" style="
                display:flex;align-items:center;gap:8px;
                padding:8px 12px;font-size:13px;color:var(--red);
                text-decoration:none;border-radius:8px;transition:background 0.15s;
              " onmouseover="this.style.background='var(--red-light)'"
                 onmouseout="this.style.background='transparent'">
                <i class="ti ti-logout" style="font-size:15px"></i> Logout
              </a>
            </div>
          </div>
        </header>

    <div class="content">

      <?php if ($notif): ?>
      <div id="notifBar" style="
        background:<?= $notif['type']==='green'?'var(--teal-light)':'var(--red-light)' ?>;
        color:<?= $notif['type']==='green'?'#0F6E56':'var(--red)' ?>;
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
        <a href="?status=all"      class="filter-btn <?= $filter_status==='all'      ?'active':'' ?>">Semua <span class="filter-count"><?= $count_map['all'] ?></span></a>
        <a href="?status=tersedia" class="filter-btn <?= $filter_status==='tersedia' ?'active':'' ?>">Tersedia <span class="filter-count"><?= $count_map['tersedia'] ?></span></a>
        <a href="?status=habis"    class="filter-btn <?= $filter_status==='habis'    ?'active':'' ?>">Habis <span class="filter-count"><?= $count_map['habis'] ?></span></a>
        <a href="?status=nonaktif" class="filter-btn <?= $filter_status==='nonaktif' ?'active':'' ?>">Nonaktif <span class="filter-count"><?= $count_map['nonaktif'] ?></span></a>
      </div>

      <div class="menu-grid" id="menuGrid">
        <?php if (mysqli_num_rows($result) === 0): ?>
          <div class="empty-state">
            <i class="ti ti-mood-empty"></i>
            <p>Tidak ada menu. Klik <strong>Tambah Menu</strong> untuk memulai.</p>
          </div>
        <?php else: while ($row = mysqli_fetch_assoc($result)):
          $st      = $row['status'];
          $hargaFmt = 'Rp '.number_format($row['harga'],0,',','.');
          $emoji   = $thumb_map[strtolower($row['kategori'])] ?? '🍽️';
          $namaJs  = htmlspecialchars(addslashes($row['nama_menu']));
          $katJs   = htmlspecialchars(addslashes($row['kategori']));
         $fotoUrl = !empty($row['foto']) ? htmlspecialchars($row['foto']) : '';
// Pastikan folder uploads/ ada di dalam dashboard_temp/
          $stok    = (int)$row['stok'];
        ?>
          <div class="menu-card" data-nama="<?= strtolower(htmlspecialchars($row['nama_menu'])) ?>">
            <div class="menu-thumb">
              <?php if ($fotoUrl): ?>
                <img src="<?= $fotoUrl ?>" alt="foto menu" style="width:100%;height:110px;object-fit:cover;display:block"/>
              <?php else: ?>
                <?= $emoji ?>
              <?php endif; ?>
              <div class="menu-status">
                <span class="badge badge-<?= $st ?>"><?= ucfirst($st) ?></span>
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
              <button class="menu-act-btn menu-act-edit"
                onclick="bukaModalEdit(<?= $row['id_menu'] ?>,'<?= $namaJs ?>',<?= $row['harga'] ?>,<?= $stok ?>,'<?= $katJs ?>','<?= $st ?>')">
                <i class="ti ti-pencil" style="font-size:13px"></i> Edit
              </button>
              <button class="menu-act-btn menu-act-delete"
                onclick="bukaModalHapus(<?= $row['id_menu'] ?>)">
                <i class="ti ti-trash" style="font-size:13px"></i> Hapus
              </button>
            </div>
          </div>
        <?php endwhile; endif; ?>
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

        <!-- Foto -->
        <div class="modal-field">
          <label>Foto Menu <span style="color:var(--text3)">(opsional · maks 2 MB · JPG / PNG / WEBP)</span></label>
          <div class="foto-upload-area" onclick="document.getElementById('fotoTambah').click()">
            <img id="previewTambah" class="foto-preview" src="" alt="preview"/>
            <div id="placeholderTambah">
              <i class="ti ti-photo-plus"></i>
              <p>Klik untuk pilih foto</p>
            </div>
          </div>
          <input type="file" id="fotoTambah" name="foto" accept="image/*" style="display:none"
                 onchange="previewFoto(this,'previewTambah','placeholderTambah')"/>
        </div>

        <!-- Nama -->
        <div class="modal-field">
          <label>Nama Menu <span style="color:var(--red)">*</span></label>
          <input type="text" name="nama_menu" placeholder="cth: Nasi Goreng Spesial" required/>
        </div>

        <!-- Harga + Stok berdampingan -->
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

        <!-- Kategori -->
        <div class="modal-field">
          <label>Kategori</label>
          <select name="kategori">
            <option value="Makanan">🍛 Makanan</option>
            <option value="Minuman">🧋 Minuman</option>
            <option value="Camilan">🍟 Camilan</option>
            <option value="Dessert">🍰 Dessert</option>
          </select>
        </div>

      </div><!-- /modal-body -->

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

        <!-- Ganti Foto -->
        <div class="modal-field">
          <label>Ganti Foto <span style="color:var(--text3)">(biarkan kosong jika tidak ingin mengganti)</span></label>
          <div class="foto-upload-area" onclick="document.getElementById('fotoEdit').click()">
            <img id="previewEdit" class="foto-preview" src="" alt="preview"/>
            <div id="placeholderEdit">
              <i class="ti ti-photo-edit"></i>
              <p>Klik untuk ganti foto</p>
            </div>
          </div>
          <input type="file" id="fotoEdit" name="foto" accept="image/*" style="display:none"
                 onchange="previewFoto(this,'previewEdit','placeholderEdit')"/>
        </div>

        <!-- Nama -->
        <div class="modal-field">
          <label>Nama Menu <span style="color:var(--red)">*</span></label>
          <input type="text" name="nama_menu" id="edit_nama" required/>
        </div>

        <!-- Harga + Stok -->
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

        <!-- Kategori + Status -->
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
          </div>
        </div>

      </div><!-- /modal-body -->

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

    <div class="modal-delete-footer">
      <button class="btn-secondary" onclick="tutupModal('modalHapus')">Batal</button>
      <a id="hapusLink" href="#" class="btn-primary" style="background:var(--red);text-decoration:none">
        <i class="ti ti-trash" style="font-size:14px"></i> Ya, Hapus
      </a>
    </div>

  </div>
</div>


<script>
  function bukaModal(id)  { document.getElementById(id).style.display = 'flex'; }
  function tutupModal(id) { document.getElementById(id).style.display = 'none'; }

  ['modalTambah','modalEdit','modalHapus'].forEach(function(id) {
    document.getElementById(id).addEventListener('click', function(e) {
      if (e.target === this) tutupModal(id);
    });
  });

  function previewFoto(input, previewId, placeholderId) {
    if (!input.files || !input.files[0]) return;
    const reader = new FileReader();
    reader.onload = function(e) {
      const img = document.getElementById(previewId);
      img.src = e.target.result;
      img.style.display = 'block';
      document.getElementById(placeholderId).style.display = 'none';
    };
    reader.readAsDataURL(input.files[0]);
  }

  // ← stok ditambahkan sebagai parameter ke-4
  function bukaModalEdit(id, nama, harga, stok, kategori, status) {
    document.getElementById('edit_id').value       = id;
    document.getElementById('edit_nama').value     = nama;
    document.getElementById('edit_harga').value    = harga;
    document.getElementById('edit_stok').value     = stok;
    document.getElementById('edit_kategori').value = kategori;
    document.getElementById('edit_status').value   = status;
    // reset preview foto
    document.getElementById('previewEdit').style.display     = 'none';
    document.getElementById('placeholderEdit').style.display = '';
    document.getElementById('fotoEdit').value = '';
    bukaModal('modalEdit');
  }

  function bukaModalHapus(id) {
    document.getElementById('hapusLink').href = 'products.php?aksi=hapus&id=' + id;
    bukaModal('modalHapus');
  }

  document.getElementById('searchInput').addEventListener('input', function() {
    const q = this.value.toLowerCase();
    document.querySelectorAll('.menu-card').forEach(function(c) {
      c.style.display = c.dataset.nama.includes(q) ? '' : 'none';
    });
  });

  // Dropdown user
  document.getElementById('menuToggle').addEventListener('click', function(e) {
    e.stopPropagation();
    const dd = document.getElementById('userDropdown');
    dd.style.display = dd.style.display === 'none' ? 'block' : 'none';
  });
  document.addEventListener('click', function() {
    document.getElementById('userDropdown').style.display = 'none';
  });

  // Auto hilang notifikasi
  setTimeout(function() {
    const n = document.getElementById('notifBar');
    if (n) n.style.display = 'none';
  }, 3000);

</script>
 <script>
      // Sidebar dropdown (titik 3)
      const menuToggle   = document.getElementById('menuToggle');
      const userDropdown = document.getElementById('userDropdown');
      menuToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        userDropdown.style.display = userDropdown.style.display === 'none' ? 'block' : 'none';
      });

      // Topbar avatar dropdown
      const avatarToggle   = document.getElementById('avatarToggle');
      const avatarDropdown = document.getElementById('avatarDropdown');
      avatarToggle.addEventListener('click', (e) => {
        e.stopPropagation();
        avatarDropdown.style.display = avatarDropdown.style.display === 'none' ? 'block' : 'none';
      });

      // Klik di luar tutup semua dropdown
      document.addEventListener('click', () => {
        userDropdown.style.display   = 'none';
        avatarDropdown.style.display = 'none';
      });

      // Animate bars
      window.addEventListener('load', () => {
        document.querySelectorAll('.bar-fill').forEach(bar => {
          const w = bar.style.width;
          bar.style.width = '0';
          setTimeout(() => { bar.style.width = w; }, 100);
        });
      });
    </script>
</body>
</html>
<?php mysqli_close($conn); ?>