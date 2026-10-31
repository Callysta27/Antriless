<?php
require_once __DIR__ . '/../frontend/includes/config.php';
ob_start();
session_name('admin_session');
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

if (trim($_SESSION['role']) !== 'pemilik_kantin') {
    header("Location: ../login.php");
    exit;
}

$id_kantin   = (int) $_SESSION['user_id'];   // id pemilik kantin = menu.id_kantin
$nama_user   = $_SESSION['nama'];
$role_user   = $_SESSION['role'];
$nama_kantin = $_SESSION['nama_kantin'] ?? 'Kantin';

/* ---------------------------------------------------------------------
   UPDATE STATUS — hanya untuk order yang berisi menu milik kantin ini
   --------------------------------------------------------------------- */
if (isset($_POST['update_status'])) {
    $id_order    = (int) $_POST['id_order'];
    $status_baru = $_POST['status'] ?? '';

    $allowed = ['Diterima', 'Diproses', 'Siap Diambil', 'Selesai'];

    if (in_array($status_baru, $allowed, true)) {
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
        mysqli_stmt_bind_param($stmt, 'sii', $status_baru, $id_order, $id_kantin);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    header("Location: dashboard.php");
    exit;
}

/* ---------------------------------------------------------------------
   STATISTIK — hanya dari item menu milik kantin ini
   Revenue = jumlah subtotal item kantin ini (bukan total order penuh,
   karena satu order bisa berisi menu dari beberapa kantin)
   --------------------------------------------------------------------- */
$total_revenue = 0;
$total_orders  = 0;

$stmt = mysqli_prepare($conn, "
    SELECT
        COALESCE(SUM(d.total), 0)      AS total_revenue,
        COUNT(DISTINCT d.id_order)     AS total_orders
    FROM detail_order d
    JOIN menu m ON m.id_menu = d.id_menu
    WHERE m.id_kantin = ?
");
mysqli_stmt_bind_param($stmt, 'i', $id_kantin);
mysqli_stmt_execute($stmt);
$stats = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if ($stats) {
    $total_revenue = $stats['total_revenue'];
    $total_orders  = $stats['total_orders'];
}

/* ---------------------------------------------------------------------
   5 ORDER TERBARU — hanya order yang berisi menu kantin ini
   Dosen diprioritaskan di atas
   --------------------------------------------------------------------- */
$stmt = mysqli_prepare($conn, "
    SELECT
        o.id_order,
        o.id_user,
        o.status,
        o.waktu_pesan,
        u.nama  AS nama_pemesan,
        u.role,
        SUM(d.total) AS total_kantin
    FROM orders o
    JOIN user u          ON u.id_user  = o.id_user
    JOIN detail_order d  ON d.id_order = o.id_order
    JOIN menu m          ON m.id_menu  = d.id_menu
    WHERE m.id_kantin = ?
    GROUP BY o.id_order, o.id_user, o.status, o.waktu_pesan, u.nama, u.role
    ORDER BY
        CASE WHEN u.role = 'dosen' THEN 0 ELSE 1 END,
        o.waktu_pesan DESC
    LIMIT 5
");
mysqli_stmt_bind_param($stmt, 'i', $id_kantin);
mysqli_stmt_execute($stmt);
$queryOrders = mysqli_stmt_get_result($stmt);
mysqli_stmt_close($stmt);

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
?>
<!doctype html>
<html lang="id">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>AdminPanel — Dashboard <?= htmlspecialchars($nama_kantin) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/icons-webfont@latest/tabler-icons.min.css" />
    <link rel="stylesheet" href="style/style.css?v=<?= filemtime(__DIR__ . '/style/style.css') ?>" />
    <link rel="stylesheet" href="style/dashboard.css?v=<?= filemtime(__DIR__ . '/style/dashboard.css') ?>" />
  </head>
  <body>
    <div class="layout">

      <!-- ── Sidebar ── -->
      <aside class="sidebar">
        <div class="sidebar-brand">
          <div class="brand-icon"><i class="ti ti-layout-dashboard"></i></div>
          AdminPanel
        </div>

        <div class="nav-section">Main</div>
        <a href="dashboard.php" class="nav-item active"><i class="ti ti-home"></i> Dashboard</a>
        <a href="orderPage.php" class="nav-item"><i class="ti ti-shopping-cart"></i> Orders</a>
        <a href="products.php"  class="nav-item"><i class="ti ti-box"></i> Products</a>
        <div class="sidebar-footer">
          <div class="user-avatar"><?= strtoupper(substr($nama_user, 0, 2)) ?></div>
          <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($nama_user) ?></div>
            <div class="user-role"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $role_user))) ?></div>
          </div>
          <div style="position:relative">
            <i class="ti ti-dots-vertical" id="menuToggle"
               style="color:var(--text3);cursor:pointer;font-size:16px"></i>
            <div id="userDropdown" style="display:none;position:absolute;bottom:40px;right:-8px;
              background:var(--surface);border:1px solid var(--border2);border-radius:var(--radius);
              box-shadow:0 8px 24px rgba(0,0,0,0.12);min-width:160px;z-index:99;overflow:hidden;padding:4px;">
              <div style="padding:8px 12px 6px;border-bottom:1px solid var(--border);margin-bottom:4px">
                <div style="font-size:12px;font-weight:600;color:var(--text)"><?= htmlspecialchars($nama_user) ?></div>
                <div style="font-size:11px;color:var(--text3)"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $role_user))) ?></div>
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

      <!-- ── Main ── -->
      <div class="main">

        <header class="topbar">
          <div class="topbar-title">
            Dashboard <span class="breadcrumb">/ <?= htmlspecialchars($nama_kantin) ?></span>
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
          <div style="position:relative">
            <div class="user-avatar" id="avatarToggle" style="width:34px;height:34px;cursor:pointer">
              <?= strtoupper(substr($nama_user, 0, 2)) ?>
            </div>
            <div id="avatarDropdown" style="display:none;position:absolute;top:42px;right:0;
              background:var(--surface);border:1px solid var(--border2);border-radius:var(--radius);
              box-shadow:0 8px 24px rgba(0,0,0,0.12);min-width:180px;z-index:99;overflow:hidden;padding:4px;">
              <div style="padding:8px 12px;border-bottom:1px solid var(--border);margin-bottom:4px">
                <div style="font-size:12px;font-weight:600;color:var(--text)"><?= htmlspecialchars($nama_user) ?></div>
                <div style="font-size:11px;color:var(--text3)"><?= htmlspecialchars(ucfirst(str_replace('_', ' ', $role_user))) ?></div>
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
          <div class="page-header">
            <div style="display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:12px;">
              <div>
                <h1>
                  <?php
                  $jam = date('H');
                  if ($jam < 12)      echo "Good morning";
                  elseif ($jam < 18)  echo "Good afternoon";
                  else                echo "Good evening";
                  ?>, <?= htmlspecialchars(explode(' ', $nama_user)[0]) ?> 👋
                </h1>
                <p>Here's what's happening with <strong><?= htmlspecialchars($nama_kantin) ?></strong> today.</p>
              </div>
              <a href="reports.php"
                style="display:inline-flex;align-items:center;gap:8px;
                        padding:9px 18px;background:#16a34a;color:#fff;
                        border-radius:8px;font-size:13px;font-weight:600;
                        text-decoration:none;transition:background 0.2s;
                        box-shadow:0 1px 3px rgba(0,0,0,0.15);"
                onmouseover="this.style.background='#15803d'"
                onmouseout="this.style.background='#16a34a'">
                <i class="ti ti-file-spreadsheet" style="font-size:16px"></i>
                Download Laporan Excel
              </a>
            </div>
          </div>

          <!-- Stats -->
          <div class="stat-grid">
            <div class="stat-card">
              <div class="stat-icon" style="background:var(--purple-light);color:var(--purple)">
                <i class="ti ti-currency-dollar"></i>
              </div>
              <div class="stat-info">
                <div class="stat-label">Total Revenue</div>
                <div class="stat-value">Rp <?= number_format($total_revenue, 0, ',', '.') ?></div>
              </div>
            </div>
            <div class="stat-card">
              <div class="stat-icon" style="background:var(--teal-light);color:var(--teal)">
                <i class="ti ti-shopping-bag"></i>
              </div>
              <div class="stat-info">
                <div class="stat-label">Total Orders</div>
                <div class="stat-value"><?= number_format($total_orders, 0, ',', '.') ?></div>
              </div>
            </div>
          </div>

          <!-- Bottom -->
          <div class="bottom-row">
            <div class="chart-card">
              <div class="card-header">
                <div>
                  <div class="card-title">Orderan Masuk</div>
                  <div class="card-sub">5 latest transactions</div>
                </div>
                <a href="orderPage.php" class="card-action">See all →</a>
              </div>
              <div class="table-wrap">
                <table>
                  <thead>
                    <tr>
                      <th>Order ID</th>
                      <th>Customer</th>
                      <th>Amount</th>
                      <th>Date</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if ($queryOrders && mysqli_num_rows($queryOrders) > 0): ?>
                      <?php while ($order = mysqli_fetch_assoc($queryOrders)): ?>
                        <?php
                          $status = normalStatus($order['status']);
                          $badgeClass = match ($status) {
                              'Diproses'     => 'badge-warn',
                              'Siap Diambil',
                              'Selesai'      => 'badge-success',
                              default        => 'badge-default',
                          };
                          $inisial = strtoupper(substr($order['nama_pemesan'] ?: 'U', 0, 2));
                        ?>
                        <tr>
                          <td><span class="order-id">#<?= (int) $order['id_order'] ?></span></td>
                          <td>
                            <div class="customer">
                              <div class="cust-av" style="background:#eeedfe;color:#534ab7">
                                <?= htmlspecialchars($inisial) ?>
                              </div>
                              <?= htmlspecialchars($order['nama_pemesan']) ?>
                            </div>
                          </td>
                          <td><strong>Rp <?= number_format($order['total_kantin'], 0, ',', '.') ?></strong></td>
                          <td style="color:var(--text2)"><?= date('d M Y, H:i', strtotime($order['waktu_pesan'])) ?></td>
                          <td>
                            <form method="POST" style="margin:0">
                              <input type="hidden" name="id_order" value="<?= (int) $order['id_order'] ?>">
                              <input type="hidden" name="update_status" value="1">
                              <select name="status" class="status-select <?= $badgeClass ?>" onchange="this.form.submit()">
                                <?php foreach (['Diterima', 'Diproses', 'Siap Diambil', 'Selesai'] as $opsi): ?>
                                  <option value="<?= $opsi ?>" <?= $status === $opsi ? 'selected' : '' ?>><?= $opsi ?></option>
                                <?php endforeach; ?>
                              </select>
                            </form>
                          </td>
                        </tr>
                      <?php endwhile; ?>
                    <?php else: ?>
                      <tr>
                        <td colspan="5" style="text-align:center;color:var(--text2);padding:24px">
                          Belum ada order masuk
                        </td>
                      </tr>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <script>
      const menuToggle   = document.getElementById('menuToggle');
      const userDropdown = document.getElementById('userDropdown');
      menuToggle.addEventListener('click', e => {
        e.stopPropagation();
        userDropdown.style.display = userDropdown.style.display === 'none' ? 'block' : 'none';
      });

      const avatarToggle   = document.getElementById('avatarToggle');
      const avatarDropdown = document.getElementById('avatarDropdown');
      avatarToggle.addEventListener('click', e => {
        e.stopPropagation();
        avatarDropdown.style.display = avatarDropdown.style.display === 'none' ? 'block' : 'none';
      });

      document.addEventListener('click', () => {
        userDropdown.style.display   = 'none';
        avatarDropdown.style.display = 'none';
      });
    </script>
  </body>
</html>
<?php
mysqli_close($conn);
ob_end_flush();
?>