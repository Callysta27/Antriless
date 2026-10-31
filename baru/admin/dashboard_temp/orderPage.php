<?php
require_once __DIR__ . '/../frontend/includes/config.php';

session_name('admin_session');
session_start();

if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit;
}

if (trim($_SESSION['role'] ?? '') !== 'pemilik_kantin') {
  header("Location: ../login.php");
  exit;
}

$id_kantin   = (int) $_SESSION['user_id'];   // id pemilik kantin = menu.id_kantin
$nama_user   = $_SESSION['nama']        ?? 'Admin';
$role        = $_SESSION['role']        ?? '';
$nama_kantin = $_SESSION['nama_kantin'] ?? '';

$words   = explode(' ', trim($nama_user));
$inisial = strtoupper(substr($words[0], 0, 1) . (isset($words[1]) ? substr($words[1], 0, 1) : substr($words[0], 1, 1)));

$daftarStatus = ['Diterima', 'Diproses', 'Siap Diambil', 'Selesai'];

// Samakan penulisan status lama ("di proses", "siap diambil") dengan yang baru
function normalStatus($s)
{
  $kunci = strtolower(str_replace(' ', '', trim((string) $s)));
  return match ($kunci) {
    'diterima'    => 'Diterima',
    'diproses'    => 'Diproses',
    'siapdiambil' => 'Siap Diambil',
    'selesai'     => 'Selesai',
    default       => trim((string) $s),
  };
}

// ── Update status: hanya order yang berisi menu kantin ini ────────
if (isset($_POST['update_status'])) {
  $id_order = (int) $_POST['id_order'];
  $status   = $_POST['status'] ?? '';

  if (in_array($status, $daftarStatus, true)) {
    $stmt = mysqli_prepare($conn, "
      UPDATE orders o
      SET o.status = ?
      WHERE o.id_order = ?
        AND EXISTS (
          SELECT 1
          FROM detail_order d
          JOIN menu m ON m.id_menu = d.id_menu
          WHERE d.id_order = o.id_order
            AND m.id_kantin = ?
        )
    ");
    mysqli_stmt_bind_param($stmt, 'sii', $status, $id_order, $id_kantin);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
  }

  // kembali ke filter yang sedang dibuka
  $kembali = isset($_GET['filter']) ? '?filter=' . urlencode($_GET['filter']) : '';
  header("Location: orderPage.php" . $kembali);
  exit;
}

// ── Ambil semua order milik kantin ini ────────────────────────────
// Total = bagian kantin ini saja (satu order bisa berisi menu beberapa kantin)
$stmt = mysqli_prepare($conn, "
  SELECT
    o.id_order,
    o.id_user,
    o.waktu_pesan,
    o.status,
    o.prioritas,
    u.nama,
    u.role       AS user_role,
    SUM(d.total) AS total_kantin
  FROM orders o
  JOIN user u         ON u.id_user  = o.id_user
  JOIN detail_order d ON d.id_order = o.id_order
  JOIN menu m         ON m.id_menu  = d.id_menu
  WHERE m.id_kantin = ?
  GROUP BY o.id_order, o.id_user, o.waktu_pesan, o.status, o.prioritas, u.nama, u.role
  ORDER BY
    CASE WHEN u.role = 'dosen' THEN 0 ELSE 1 END,
    o.waktu_pesan DESC
");
mysqli_stmt_bind_param($stmt, 'i', $id_kantin);
mysqli_stmt_execute($stmt);
$semuaOrder = mysqli_fetch_all(mysqli_stmt_get_result($stmt), MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// ── Hitung per status (untuk angka di tombol filter) ──────────────
$counts = ['all' => 0, 'Diterima' => 0, 'Diproses' => 0, 'Siap Diambil' => 0, 'Selesai' => 0];
foreach ($semuaOrder as &$o) {
  $o['status'] = normalStatus($o['status']);
  $counts['all']++;
  if (isset($counts[$o['status']])) $counts[$o['status']]++;
}
unset($o);

// ── Filter status ─────────────────────────────────────────────────
$filter = $_GET['filter'] ?? 'all';
if ($filter !== 'all' && !in_array($filter, $daftarStatus, true)) {
  $filter = 'all';
}

$orders = $filter === 'all'
  ? $semuaOrder
  : array_values(array_filter($semuaOrder, fn($o) => $o['status'] === $filter));
?>
<!doctype html>
<html lang="id">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AdminPanel — Orders <?= htmlspecialchars($nama_kantin) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet" />
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" />
  <link rel="stylesheet" href="style/style.css?v=<?= filemtime(__DIR__ . '/style/style.css') ?>" />
  <style>
    /* ── Status select styling ── */
    .status-select {
      border: 1.5px solid var(--border2);
      border-radius: 8px;
      padding: 5px 10px;
      font-family: inherit;
      font-size: 12px;
      font-weight: 500;
      background: var(--surface);
      color: var(--text);
      cursor: pointer;
      outline: none;
      transition: border-color 0.15s;
    }

    .status-select:focus {
      border-color: var(--purple);
    }

    /* ── Priority badge ── */
    .badge-priority-dosen {
      background: #fef3c7;
      color: #92400e;
      border: 1px solid #fcd34d;
    }

    tbody tr {
      transition: background 0.12s;
    }

    /* ── Filter bar pills ── */
    .filter-bar {
      display: flex;
      gap: 6px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }

    .filter-btn {
      display: inline-flex;
      align-items: center;
      gap: 6px;
      padding: 6px 14px;
      border-radius: 99px;
      font-size: 13px;
      font-weight: 500;
      border: 1.5px solid var(--border2);
      background: var(--surface);
      color: var(--text2);
      cursor: pointer;
      text-decoration: none;
      font-family: inherit;
      transition: all 0.15s;
      white-space: nowrap;
    }

    .filter-btn:hover {
      background: var(--surface2);
      color: var(--text);
    }

    .filter-btn.active {
      background: var(--purple);
      color: #fff;
      border-color: var(--purple);
    }

    .filter-btn .n {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      min-width: 18px;
      height: 18px;
      padding: 0 4px;
      border-radius: 99px;
      font-size: 10px;
      font-weight: 700;
      background: rgba(255, 255, 255, 0.28);
    }

    .filter-btn:not(.active) .n {
      background: var(--surface2);
      color: var(--text3);
    }

    /* ── Nama user di tabel ── */
    .user-chip {
      display: inline-flex;
      align-items: center;
      gap: 8px;
    }

    .user-chip .av {
      width: 28px;
      height: 28px;
      border-radius: 50%;
      background: var(--purple-light);
      color: var(--purple);
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 11px;
      font-weight: 700;
      flex-shrink: 0;
    }

    .user-chip .av.dosen {
      background: #fef3c7;
      color: #92400e;
    }
  </style>
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
      <a href="dashboard.php" class="nav-item"><i class="ti ti-home"></i> Dashboard</a>
      <a href="orderPage.php" class="nav-item active"><i class="ti ti-shopping-cart"></i> Orders</a>
      <a href="products.php" class="nav-item"><i class="ti ti-box"></i> Products</a>

      <div class="sidebar-footer">
        <div class="user-avatar"><?= htmlspecialchars($inisial) ?></div>
        <div class="user-info">
          <div class="user-name"><?= htmlspecialchars($nama_user) ?></div>
          <div class="user-role"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $role))) ?></div>
        </div>
        <i class="ti ti-dots-vertical" style="color:var(--text3);font-size:16px"></i>
      </div>
    </aside>

    <!-- Main -->
    <div class="main">
      <header class="topbar">
        <div class="topbar-title">Dashboard <span class="breadcrumb">/ Orders</span></div>
        <div class="topbar-spacer"></div>
        <div class="icon-btn"><i class="ti ti-bell"></i>
          <div class="notif-dot"></div>
        </div>
        <div class="icon-btn"><i class="ti ti-settings"></i></div>
        <div class="user-avatar" style="width:34px;height:34px;cursor:pointer"><?= htmlspecialchars($inisial) ?></div>
      </header>

      <div class="content">

        <!-- Page Header -->
        <div class="page-header">
          <div>
            <h1>Order Management</h1>
            <p>Kelola dan update status pesanan masuk untuk <strong><?= htmlspecialchars($nama_kantin ?: 'kantin kamu') ?></strong>.</p>
          </div>
        </div>

        <!-- Filter Pills -->
        <div class="filter-bar">
          <?php
          $filters = ['all' => 'Semua'] + array_combine($daftarStatus, $daftarStatus);
          foreach ($filters as $key => $label):
            $active = ($filter === $key) ? 'active' : '';
          ?>
            <a href="?filter=<?= urlencode($key) ?>" class="filter-btn <?= $active ?>">
              <?= $label ?>
              <span class="n"><?= $counts[$key] ?? 0 ?></span>
            </a>
          <?php endforeach; ?>
        </div>

        <!-- Tabel -->
        <div class="chart-card" style="padding:0;overflow:hidden">
          <div class="table-wrap">
            <table>
              <thead>
                <tr>
                  <th>Order ID</th>
                  <th>Customer</th>
                  <th>Total</th>
                  <th>Waktu Pesan</th>
                  <th>Prioritas</th>
                  <th>Status</th>
                </tr>
              </thead>
              <tbody>
                <?php if ($orders): ?>
                  <?php foreach ($orders as $o): ?>
                    <tr>
                      <!-- Order ID -->
                      <td>
                        <span class="order-id">#<?= str_pad($o['id_order'], 4, '0', STR_PAD_LEFT) ?></span>
                      </td>

                      <!-- Customer -->
                      <td>
                        <div class="user-chip">
                          <div class="av <?= $o['user_role'] === 'dosen' ? 'dosen' : '' ?>">
                            <?= htmlspecialchars(strtoupper(substr($o['nama'], 0, 2))) ?>
                          </div>
                          <div>
                            <div style="font-weight:500;font-size:13px">
                              <?= htmlspecialchars($o['nama']) ?>
                            </div>
                            <div style="font-size:11px;color:var(--text3)">
                              <?= htmlspecialchars(ucfirst($o['user_role'])) ?>
                            </div>
                          </div>
                        </div>
                      </td>

                      <!-- Total (bagian kantin ini) -->
                      <td>
                        <strong style="font-family:'DM Mono',monospace;font-size:13px">
                          Rp <?= number_format($o['total_kantin'], 0, ',', '.') ?>
                        </strong>
                      </td>

                      <!-- Waktu -->
                      <td style="color:var(--text2);font-size:13px">
                        <?= date('d M Y', strtotime($o['waktu_pesan'])) ?><br>
                        <span style="font-size:11px;color:var(--text3)">
                          <?= date('H:i', strtotime($o['waktu_pesan'])) ?>
                        </span>
                      </td>

                      <!-- Prioritas -->
                      <td>
                        <?php if ($o['user_role'] === 'dosen'): ?>
                          <span class="badge badge-priority-dosen">
                            <i class="ti ti-star-filled" style="font-size:10px"></i> Dosen
                          </span>
                        <?php else: ?>
                          <span class="badge badge-gray">Normal</span>
                        <?php endif; ?>
                      </td>

                      <!-- Status — dropdown langsung update -->
                      <td>
                        <form method="POST" action="orderPage.php?filter=<?= urlencode($filter) ?>" style="margin:0">
                          <input type="hidden" name="id_order" value="<?= (int) $o['id_order'] ?>">
                          <input type="hidden" name="update_status" value="1">
                          <select name="status" class="status-select" onchange="this.form.submit()">
                            <?php
                            $ikon = ['Diterima' => '⏳', 'Diproses' => '🔄', 'Siap Diambil' => '✅', 'Selesai' => '🏁'];
                            foreach ($daftarStatus as $opsi):
                            ?>
                              <option value="<?= $opsi ?>" <?= $o['status'] === $opsi ? 'selected' : '' ?>>
                                <?= $ikon[$opsi] ?> <?= $opsi ?>
                              </option>
                            <?php endforeach; ?>
                          </select>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="6" style="text-align:center;color:var(--text2);padding:40px">
                      <i class="ti ti-inbox" style="font-size:32px;opacity:.3;display:block;margin-bottom:8px"></i>
                      Tidak ada order
                      <?= $filter !== 'all' ? 'dengan status <strong>' . htmlspecialchars($filter) . '</strong>' : '' ?>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>

      </div><!-- /content -->
    </div><!-- /main -->
  </div><!-- /layout -->
</body>

</html>
<?php mysqli_close($conn); ?>