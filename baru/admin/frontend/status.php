<!--bagian atas gua ubah-->
<?php
session_name('user_session');
if (session_status() === PHP_SESSION_NONE) session_start();

include __DIR__ . '/../frontend/includes/config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../login.php");
  exit;
}

$id_user = $_SESSION['user_id'];

$order = mysqli_query($conn, "
    SELECT * FROM orders
    WHERE id_user = '$id_user'
    ORDER BY id_order DESC
    LIMIT 1
");
$data_order = mysqli_fetch_assoc($order);
if (!$data_order) {
  die("Belum ada pesanan.");
}

$id_order    = $data_order['id_order'];
$total       = $data_order['total_harga'];
$status      = $data_order['status'] ?? '';
$statusClass = str_replace(' ', '-', $status);

$urutan = ['di terima', 'di proses', 'siap diambil'];

function stepClass($step, $currentStatus, $urutan)
{
  $ci = array_search($currentStatus, $urutan);
  $si = array_search($step, $urutan);
  if ($ci === false || $si === false) return '';
  if ($si < $ci)   return 'done';
  if ($si === $ci) return 'active';
  return '';
}

$label = [
  'di terima'    => 'DITERIMA',
  'di proses'    => 'DIPROSES',
  'siap diambil' => 'SIAP DIAMBIL',
  'dibatalkan'   => 'DIBATALKAN',
];
$desc = [
  'di terima'    => 'Pesanan sudah diterima, menunggu dimasak 📋',
  'di proses'    => 'Makanan kamu sedang dimasak 🍳',
  'siap diambil' => 'Pesanan siap diambil di konter ✅',
  'dibatalkan'   => 'Pesanan ini telah dibatalkan ❌',
];

$detail = mysqli_query($conn, "
    SELECT d.*, m.nama_menu, m.harga
    FROM detail_order d
    JOIN menu m ON d.id_menu = m.id_menu
    WHERE d.id_order = '$id_order'
");
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Status Pesanan — Antriless</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:wght@700;900&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="css/status.css" />
</head>

<body>

  <!-- NAVBAR -->
  <div class="navbar">
    <div class="logo">
      <h2>ANTRILESS</h2>
      <p>Your Online Canteen</p>
    </div>
    <div class="spacer"></div>
    <a href="halaman utama.php" class="btn-back-nav">
      <i class="fa-solid fa-arrow-left"></i> Kembali
    </a>
  </div>

  <div class="page">

    <!-- STATUS CARD -->
    <div class="status-card <?= htmlspecialchars($statusClass) ?>">
      <i class="fa-solid fa-bowl-food icon-bg left"></i>
      <h1 class="status-title"><?= $label[$status] ?? strtoupper($status) ?></h1>
      <p class="status-desc"><?= $desc[$status] ?? '' ?></p>
      <i class="fa-solid fa-bowl-food icon-bg right"></i>
    </div>

    <!-- PROGRESS TRACKER -->
    <?php if ($status !== 'dibatalkan'): ?>
      <div class="tracker">
        <div class="steps">
          <div class="fill <?= htmlspecialchars($statusClass) ?>"></div>

          <div class="step <?= stepClass('di terima', $status, $urutan) ?>">
            <div class="step-circle">
              <?php if (stepClass('di terima', $status, $urutan) === 'done'): ?>
                <i class="fa-solid fa-check"></i>
              <?php elseif (stepClass('di terima', $status, $urutan) === 'active'): ?>
                <i class="fa-solid fa-clock"></i>
              <?php else: ?>
                <i class="fa-solid fa-clock"></i>
              <?php endif; ?>
            </div>
            <span class="step-label">Diterima</span>
          </div>

          <div class="step <?= stepClass('di proses', $status, $urutan) ?>">
            <div class="step-circle">
              <?php
              $c = stepClass('di proses', $status, $urutan);
              if ($c === 'done')       echo '<i class="fa-solid fa-check"></i>';
              elseif ($c === 'active') echo '<i class="fa-solid fa-fire-burner"></i>';
              else                     echo '<i class="fa-solid fa-utensils"></i>';
              ?>
            </div>
            <span class="step-label">Diproses</span>
          </div>

          <div class="step <?= stepClass('siap diambil', $status, $urutan) ?>">
            <div class="step-circle">
              <?php
              $c = stepClass('siap diambil', $status, $urutan);
              if ($c === 'done' || $c === 'active') echo '<i class="fa-solid fa-check"></i>';
              else                                  echo '<i class="fa-solid fa-bag-shopping"></i>';
              ?>
            </div>
            <span class="step-label">Siap Diambil</span>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- ORDER TABLE -->
    <div class="order-card">
      <div class="order-card-header">
        <i class="fa-solid fa-receipt" style="color:var(--orange)"></i>
        <h2>Detail Pesanan</h2>
        <span class="order-id-badge">#<?= $id_order ?></span>
      </div>
      <table>
        <thead>
          <tr>
            <th>Nama Produk</th>
            <th>Harga</th>
            <th>Qty</th>
            <th>Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php $no = 1;
          while ($row = mysqli_fetch_assoc($detail)): ?>
            <tr>
              <td><?= $no++ ?>. <?= htmlspecialchars($row['nama_menu']) ?></td>
              <td>Rp <?= number_format($row['harga'], 0, ',', '.') ?></td>
              <td><?= $row['jumlah'] ?>×</td>
              <td>Rp <?= number_format($row['total'], 0, ',', '.') ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="3">Total Pembayaran</td>
            <td>Rp <?= number_format($total, 0, ',', '.') ?></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <!-- BUTTONS -->
    <div class="btn-group">

      <a href="halaman utama.php" class="btn btn-home">
        <i class="fa-solid fa-house"></i>
        Halaman Utama
      </a>

      <?php if ($status === 'Siap Diambil'): ?>

        <a href="invoice.php?id_order=<?= $id_order ?>"
          class="btn btn-invoice">
          <i class="fa-solid fa-file-arrow-down"></i>
          Download Invoice
        </a>

        <a href="javascript:void(0)"
          class="btn btn-done"
          onclick="openConfirmModal(<?= $id_order ?>)">
          <i class="fa-solid fa-circle-check"></i>
          Sudah Diambil
        </a>

      <?php endif; ?>

    </div>

  </div>

  <!-- Auto-refresh setiap 10 detik jika masih menunggu -->
  <?php if ($status !== 'siap diambil' && $status !== 'dibatalkan'): ?>
    <script>
      setTimeout(function() {
        location.reload();
      }, 10000);
    </script>
  <?php endif; ?>

  <div id="confirmModal" class="modal">

    <div class="modal-content">

        <div class="modal-icon">
            <i class="fa-solid fa-circle-check"></i>
        </div>

        <h3>Konfirmasi Pesanan</h3>

        <p>
            Apakah pesanan sudah diambil?
        </p>

        <div class="modal-buttons">

            <button
                class="modal-btn cancel"
                onclick="closeConfirmModal()">

                Batal
            </button>

            <a
                id="confirmLink"
                class="modal-btn confirm">

                Ya, Sudah
            </a>

        </div>

    </div>

</div>
</body>

<script src="js/nunggu.js"></script>
</html>

