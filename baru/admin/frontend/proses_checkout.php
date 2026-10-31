<?php
session_name('user_session');
session_start();
include 'includes/config.php';

$data = json_decode(
    file_get_contents("php://input"),
    true
);

$cart  = $data['cart'];
$total = $data['total'];

$id_user = $_SESSION['user_id'];

/* =========================
   SIMPAN ORDER
========================= */
mysqli_query($conn,"
INSERT INTO orders
(
    id_user,
    total_harga,
    status,
    prioritas
)
VALUES
(
    '$id_user',
    '$total',
    'pending',
    'normal'
)
");

$id_order = mysqli_insert_id($conn);

/* =========================
   SIMPAN PAYMENT
========================= */
mysqli_query($conn,"
INSERT INTO payment
(
    id_order,
    total_pembayaran,
    metode,
    prioritas,
    status
)
VALUES
(
    '$id_order',
    '$total',
    'QR',
    'normal',
    'pending'
)
");

$id_payment = mysqli_insert_id($conn);

/* =========================
   SIMPAN INVOICE
========================= */
mysqli_query($conn,"
INSERT INTO invoice
(
    id_payment,
    id_user
)
VALUES
(
    '$id_payment',
    '$id_user'
)
");

/* =========================
   SIMPAN DETAIL ORDER
========================= */
foreach($cart as $item){

    $nama_menu = $item['nama'];
    $jumlah    = $item['jumlah'];
    $subtotal  = $item['harga'] * $item['jumlah'];

    $cari_menu = mysqli_query(
        $conn,
        "SELECT id_menu
         FROM menu
         WHERE nama_menu='$nama_menu'"
    );

    $menu = mysqli_fetch_assoc($cari_menu);

    $id_menu = $menu['id_menu'];

    mysqli_query($conn,"
    INSERT INTO detail_order
    (
        id_order,
        id_menu,
        jumlah,
        total
    )
    VALUES
    (
        '$id_order',
        '$id_menu',
        '$jumlah',
        '$subtotal'
    )
    ");

    mysqli_query($conn,"
    UPDATE menu
    SET stok = stok - $jumlah
    WHERE id_menu = '$id_menu'
    ");
}

/* =========================
   KIRIM DATA KE JAVASCRIPT
========================= */
echo json_encode([
    'id_order' => $id_order
]);