<?php
include 'includes/config.php';

if(isset($_GET['id_order'])){

    $id_order = (int)$_GET['id_order'];

    mysqli_query($conn,"
        UPDATE orders
        SET status = 'selesai'
        WHERE id_order = $id_order
    ");

    header("Location: halaman utama.php");
    exit;
}
?>