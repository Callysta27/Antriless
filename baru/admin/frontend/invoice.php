<?php
session_start();
require_once '../frontend/includes/config.php';

if (!isset($_GET['id_order'])) {
    die("ID Order tidak ditemukan.");
}

$id_order = $_GET['id_order'];

/*
=====================
AMBIL DATA ORDER
=====================
*/

$queryOrder = mysqli_query($conn, "
    SELECT *
    FROM orders
    WHERE id_order = '$id_order'
");

$order = mysqli_fetch_assoc($queryOrder);

if (!$order) {
    die("Pesanan tidak ditemukan.");
}

/*
=====================
DETAIL PESANAN
=====================
*/

$queryDetail = mysqli_query($conn, "
    SELECT
        d.jumlah,
        d.total,
        m.nama_menu,
        m.harga
    FROM detail_order d
    JOIN menu m
        ON d.id_menu = m.id_menu
    WHERE d.id_order = '$id_order'
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #<?= $id_order ?></title>

    <!-- CSS -->
    <link rel="stylesheet" href="css/invoice.css">

    <!-- Font Awesome -->
    <link rel="stylesheet"
          href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
</head>

<body>

<div class="invoice-container">

    <div class="invoice-card">

        <!-- Header -->
        <div class="logo">
            <h1>ANTRILESS</h1>
            <p>Your Online Canteen</p>
            <h2>Invoice #<?= $id_order ?></h2>
        </div>

        <!-- Informasi -->
        <div class="info">

            <p>
                <strong>Tanggal :</strong>
                <?= date('d F Y'); ?>
            </p>

            <p>
                <strong>Status :</strong>
                <?= ucfirst($order['status']); ?>
            </p>

        </div>

        <!-- Tabel -->
        <table>

            <tr>
                <th>No</th>
                <th>Menu</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Subtotal</th>
            </tr>

            <?php
            $no = 1;
            $grand_total = 0;

            while ($row = mysqli_fetch_assoc($queryDetail)) :

                $grand_total += $row['total'];
            ?>

                <tr>

                    <td><?= $no++ ?></td>

                    <td>
                        <?= $row['nama_menu']; ?>
                    </td>

                    <td>
                        <?= $row['jumlah']; ?>x
                    </td>

                    <td>
                        Rp <?= number_format($row['harga'], 0, ',', '.'); ?>
                    </td>

                    <td>
                        Rp <?= number_format($row['total'], 0, ',', '.'); ?>
                    </td>

                </tr>

            <?php endwhile; ?>

        </table>

        <!-- Total -->
        <div class="total">
            Total :
            Rp <?= number_format($grand_total, 0, ',', '.'); ?>
        </div>

        <!-- Tombol -->
        <div class="btn-group">

            <button
                onclick="window.print()"
                class="btn btn-print">

                <i class="fa-solid fa-file-pdf"></i>
                Download PDF
            </button>

            <a
                href="status.php?id_order=<?= $id_order ?>"
                class="btn btn-back">

                <i class="fa-solid fa-arrow-left"></i>
                Kembali
            </a>

        </div>

    </div>

</div>

</body>
</html>