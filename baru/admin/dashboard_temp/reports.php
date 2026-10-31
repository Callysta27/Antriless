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

// Ambil semua data orders
$queryOrders = mysqli_query($conn, "
    SELECT 
        id_order,
        id_user,
        waktu_pesan,
        status,
        total_harga
    FROM orders
    ORDER BY waktu_pesan DESC
");

// Ambil statistik
$queryStats = mysqli_query($conn, "
    SELECT 
        COALESCE(SUM(total_harga), 0) AS total_revenue,
        COUNT(id_order) AS total_orders
    FROM orders
");
$stats = mysqli_fetch_assoc($queryStats);

$nama_kantin = $_SESSION['nama_kantin'] ?? 'Kantin';
$tanggal_export = date('d-m-Y H:i');
$filename = 'Laporan_Order_' . date('Ymd_His') . '.xls';

// Bersihkan buffer output agar tidak ada output sebelumnya
ob_end_clean();

// Set header untuk download Excel (format HTML-based XLS)
header("Content-Type: application/vnd.ms-excel; charset=utf-8");
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Pragma: no-cache");
header("Expires: 0");

// Keluarkan BOM untuk encoding UTF-8
echo "\xEF\xBB\xBF";
?>
<html xmlns:o="urn:schemas-microsoft-com:office:office"
      xmlns:x="urn:schemas-microsoft-com:office:excel"
      xmlns="http://www.w3.org/TR/REC-html40">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<!--[if gte mso 9]>
<xml>
  <x:ExcelWorkbook>
    <x:ExcelWorksheets>
      <x:ExcelWorksheet>
        <x:Name>Laporan Order</x:Name>
        <x:WorksheetOptions>
          <x:DisplayGridlines/>
        </x:WorksheetOptions>
      </x:ExcelWorksheet>
    </x:ExcelWorksheets>
  </x:ExcelWorkbook>
</xml>
<![endif]-->
<style>
  body  { font-family: Arial, sans-serif; font-size: 11pt; }
  table { border-collapse: collapse; width: 100%; }
  th    { background-color: #1e293b; color: #ffffff; font-weight: bold;
          padding: 8px 12px; border: 1px solid #334155; text-align: center; }
  td    { padding: 7px 12px; border: 1px solid #e2e8f0; vertical-align: middle; }
  tr:nth-child(even) td { background-color: #f8fafc; }

  .header-block  { margin-bottom: 18px; }
  .header-block h2 { font-size: 15pt; margin: 0 0 4px; color: #1e293b; }
  .header-block p  { font-size: 10pt; color: #64748b; margin: 0; }

  .stat-row td { background: #eff6ff; font-weight: bold; color: #1d4ed8;
                 border: 1px solid #bfdbfe; text-align: center; }

  .badge-default { background:#f1f5f9; color:#475569; padding:3px 8px;
                   border-radius:99px; font-size:10pt; }
  .badge-warn    { background:#fef9c3; color:#92400e; padding:3px 8px;
                   border-radius:99px; font-size:10pt; }
  .badge-success { background:#dcfce7; color:#166534; padding:3px 8px;
                   border-radius:99px; font-size:10pt; }

  .footer-note { font-size: 9pt; color: #94a3b8; margin-top: 12px; }
</style>
</head>
<body>

<!-- Header Laporan -->
<div class="header-block">
  <h2>📋 Laporan Order — <?= htmlspecialchars($nama_kantin) ?></h2>
  <p>Diekspor pada: <?= $tanggal_export ?> &nbsp;|&nbsp; Oleh: <?= htmlspecialchars($_SESSION['nama']) ?></p>
</div>

<!-- Ringkasan Statistik -->
<table style="width:auto;margin-bottom:16px;">
  <thead>
    <tr>
      <th>Total Pendapatan</th>
      <th>Total Order</th>
    </tr>
  </thead>
  <tbody>
    <tr class="stat-row">
      <td>Rp <?= number_format($stats['total_revenue'], 0, ',', '.') ?></td>
      <td><?= number_format($stats['total_orders'], 0, ',', '.') ?></td>
    </tr>
  </tbody>
</table>

<!-- Tabel Detail Order -->
<table>
  <thead>
    <tr>
      <th>No</th>
      <th>Order ID</th>
      <th>ID Customer</th>
      <th>Tanggal Pesan</th>
      <th>Total Harga</th>
      <th>Status</th>
    </tr>
  </thead>
  <tbody>
    <?php if ($queryOrders && mysqli_num_rows($queryOrders) > 0):
      $no = 1;
      while ($order = mysqli_fetch_assoc($queryOrders)):
        $badgeClass = match($order['status']) {
            'di terima'    => 'badge-default',
            'di proses'    => 'badge-warn',
            'siap diambil' => 'badge-success',
            default        => 'badge-default',
        };
        $statusLabel = match($order['status']) {
            'di terima'    => 'Di Terima',
            'di proses'    => 'Di Proses',
            'siap diambil' => 'Siap Diambil',
            default        => ucfirst($order['status']),
        };
    ?>
    <tr>
      <td style="text-align:center"><?= $no++ ?></td>
      <td style="text-align:center">#<?= htmlspecialchars($order['id_order']) ?></td>
      <td style="text-align:center">User <?= htmlspecialchars($order['id_user']) ?></td>
      <td><?= date('d M Y, H:i', strtotime($order['waktu_pesan'])) ?></td>
      <td style="text-align:right">Rp <?= number_format($order['total_harga'], 0, ',', '.') ?></td>
      <td style="text-align:center">
        <span class="<?= $badgeClass ?>"><?= $statusLabel ?></span>
      </td>
    </tr>
    <?php endwhile; ?>
    <!-- Baris Total -->
    <tr>
      <td colspan="4" style="text-align:right;font-weight:bold;background:#f1f5f9;color:#1e293b;">
        TOTAL
      </td>
      <td style="text-align:right;font-weight:bold;background:#dbeafe;color:#1d4ed8;">
        Rp <?= number_format($stats['total_revenue'], 0, ',', '.') ?>
      </td>
      <td style="background:#f1f5f9;"></td>
    </tr>
    <?php else: ?>
    <tr>
      <td colspan="6" style="text-align:center;color:#94a3b8;padding:20px;">
        Belum ada data order.
      </td>
    </tr>
    <?php endif; ?>
  </tbody>
</table>

<p class="footer-note">* Laporan ini digenerate otomatis oleh sistem AdminPanel.</p>

</body>
</html>
<?php
mysqli_close($conn);
?>