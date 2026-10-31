<?php
if (session_status() === PHP_SESSION_NONE) session_start();
include 'includes/config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'msg' => 'not_logged_in']);
    exit;
}

$id_user = mysqli_real_escape_string($conn, $_SESSION['user_id']);

$query = mysqli_query($conn, "
    SELECT 
        o.id_order,
        o.status,
        o.total_harga,
        o.waktu_pesan,
        m.nama_menu
    FROM orders o
    LEFT JOIN detail_order d ON o.id_order = d.id_order
    LEFT JOIN menu m ON d.id_menu = m.id_menu
    WHERE o.id_user = '$id_user'
    ORDER BY o.id_order DESC
    LIMIT 1
");

$data = mysqli_fetch_assoc($query);

if ($data) {
    echo json_encode([
        'status'         => 'ok',
        'status_pesanan' => $data['status'],
        'total'          => $data['total_harga'],
        'nama_menu'      => $data['nama_menu'],
        'waktu'          => $data['waktu_pesan'],
        'id_order'       => $data['id_order'],
    ]);
} else {
    echo json_encode(['status' => 'error', 'msg' => 'no_order']);
}
?>

