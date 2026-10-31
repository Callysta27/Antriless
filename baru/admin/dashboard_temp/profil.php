<?php
session_name('admin_session');
session_start();

require_once '../frontend/includes/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../../login.php");
    exit;
}

$id_user = $_SESSION['user_id'];

$query = mysqli_query($conn, "
    SELECT
        id_user,
        nama,
        email,
        nama_kantin
    FROM user
    WHERE id_user = '$id_user'
");

$user = mysqli_fetch_assoc($query);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Profil Pemilik Kantin</title>

    <link rel="stylesheet" href="../dashboard_temp/style/profil.css">
</head>

<body>

<div class="profile-wrapper">

    <div class="profile-card">

        <div class="profile-header">

            <div class="profile-avatar">
                <?= strtoupper(substr($user['nama'],0,1)); ?>
            </div>

            <div class="profile-title">
                <h2><?= htmlspecialchars($user['nama']); ?></h2>

                <p><?= htmlspecialchars($user['email']); ?></p>

                <div class="role-badge">
                    🏪 Pemilik Kantin
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
                    Nama Kantin
                </div>

                <div class="info-value">
                    <?= htmlspecialchars($user['nama_kantin']); ?>
                </div>
            </div>

        </div>

        <a href="dashboard.php" class="back-btn">
            ← Kembali ke Dashboard
        </a>

    </div>

</div>

</body>
</html>