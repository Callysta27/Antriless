<?php
session_name('user_session');
session_start();

require_once '../frontend/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$id_user = $_SESSION['user_id'];

$query = mysqli_query($conn, "
    SELECT
        id_user,
        nama,
        email,
        role
    FROM user
    WHERE id_user = '$id_user'
");

$user = mysqli_fetch_assoc($query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profil Saya</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="css/profil.css">
</head>

<body>

    <div class="profile-card">

        <div class="profile-header">

            <div class="avatar">
                <?= strtoupper(substr($user['nama'], 0, 1)); ?>
            </div>

            <div class="profile-title">
                <h2><?= htmlspecialchars($user['nama']); ?></h2>

                <p><?= htmlspecialchars($user['email']); ?></p>

                <div class="role-badge">
                    👤 <?= ucfirst($user['role']); ?>
                </div>
            </div>

        </div>

        <div class="profile-grid">

            <div class="info-card">
                <div class="info-label">
                    ID User
                </div>

                <div class="info-value">
                    <?= $user['id_user']; ?>
                </div>
            </div>

            <div class="info-card">
                <div class="info-label">
                    Role
                </div>

                <div class="info-value">
                    <?= htmlspecialchars($user['role']); ?>
                </div>
            </div>

        </div>

        <a href="halaman utama.php" class="back-btn">
            ← Kembali ke Halaman Utama
        </a>

    </div>

</body>

</html>