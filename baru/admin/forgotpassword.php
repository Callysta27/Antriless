<?php
// ob_start HARUS paling pertama sebelum output apapun
ob_start();

require_once __DIR__ . '/frontend/includes/config.php';

session_name('user_session');
session_start();

$error   = '';
$success = '';

// Batas waktu untuk mengganti password setelah verifikasi (detik)
const RESET_TIMEOUT = 600;

// Verifikasi kedaluwarsa → kembali ke langkah 1
if (!empty($_SESSION['reset_user_id']) && time() - $_SESSION['reset_time'] > RESET_TIMEOUT) {
    unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_time']);
    $error = "Waktu verifikasi habis. Silakan ulangi.";
}

// ── BATAL ─────────────────────────────────────────────────────────────────────
if (isset($_POST['batal'])) {
    unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_time']);
    header("Location: forgotpassword.php");
    ob_end_flush();
    exit;
}

// ── LANGKAH 1: verifikasi email + nomor telepon ──────────────────────────────
if (isset($_POST['verifikasi'])) {
    $email   = trim($_POST['email'] ?? '');
    $no_telp = trim($_POST['no_telp'] ?? '');

    if (empty($email) || empty($no_telp)) {
        $error = "Email dan nomor telepon wajib diisi.";
    } else {
        $stmt = mysqli_prepare($conn, "SELECT id_user FROM user WHERE email = ? AND no_telp = ?");
        mysqli_stmt_bind_param($stmt, 'ss', $email, $no_telp);
        mysqli_stmt_execute($stmt);
        $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
        mysqli_stmt_close($stmt);

        if ($row) {
            session_regenerate_id(true);
            $_SESSION['reset_user_id'] = $row['id_user'];
            $_SESSION['reset_email']   = $email;
            $_SESSION['reset_time']    = time();
        } else {
            $error = "Email atau nomor telepon tidak cocok.";
        }
    }
}

// ── LANGKAH 2: simpan password baru ──────────────────────────────────────────
if (isset($_POST['reset']) && !empty($_SESSION['reset_user_id'])) {
    $password   = $_POST['pass_baru'] ?? '';
    $konfirmasi = $_POST['pass_konfirmasi'] ?? '';

    if (strlen($password) < 6) {
        $error = "Password minimal 6 karakter.";
    } elseif ($password !== $konfirmasi) {
        $error = "Konfirmasi password tidak sama.";
    } else {
        // Disimpan dengan format yang sama seperti signup di login.php
        $stmt = mysqli_prepare($conn, "UPDATE user SET password = ? WHERE id_user = ?");
        mysqli_stmt_bind_param($stmt, 'si', $password, $_SESSION['reset_user_id']);

        if (mysqli_stmt_execute($stmt)) {
            unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['reset_time']);
            // Ditampilkan di halaman login (pakai pesan yang sama dengan signup)
            $_SESSION['success_signup'] = "Password berhasil diubah! Silakan login.";
            mysqli_stmt_close($stmt);
            header("Location: login.php");
            ob_end_flush();
            exit;
        }
        $error = "Gagal mengubah password: " . mysqli_error($conn);
        mysqli_stmt_close($stmt);
    }
}

$terverifikasi = !empty($_SESSION['reset_user_id']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="dashboard_temp/style/login.css?v=<?= filemtime(__DIR__ . '/dashboard_temp/style/login.css') ?>" />
  <title>Lupa Password — Kantin</title>
  <style>
    .msg-error   { color: #e74c3c; font-size: 13px; margin: 6px 0; text-align: center; }
    .msg-success { color: #27ae60; font-size: 13px; margin: 6px 0; text-align: center; }
    .subjudul    { margin: 8px 0 14px; font-size: 14px; font-weight: 500; color: var(--muted); line-height: 1.5; }
    .tombol-batal {
      background: transparent !important; color: var(--brand) !important;
      box-shadow: none !important; border: 1.5px solid var(--line) !important;
      margin-top: 10px !important;
    }
    form a.kembali { align-self: center; margin-top: 18px; }
  </style>
</head>
<body>
  <div class="container" id="container">

    <!-- ── FORM ── -->
    <div class="form-container">
      <?php if (!$terverifikasi): ?>
      <!-- Langkah 1: verifikasi akun -->
      <form action="forgotpassword.php" method="POST">
        <h1>Lupa Password</h1>
        <p class="subjudul">Masukkan email dan nomor telepon yang kamu daftarkan untuk memverifikasi akun.</p>

        <?php if ($error): ?><p class="msg-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

        <input type="email" name="email"   placeholder="Email"         required
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
        <input type="text"  name="no_telp" placeholder="Nomor Telepon" required
               value="<?= htmlspecialchars($_POST['no_telp'] ?? '') ?>" />

        <button type="submit" name="verifikasi">Verifikasi</button>
        <a href="login.php" class="kembali">← Kembali ke Sign In</a>
      </form>

      <?php else: ?>
      <!-- Langkah 2: buat password baru -->
      <form action="forgotpassword.php" method="POST">
        <h1>Password Baru</h1>
        <p class="subjudul">Akun <strong><?= htmlspecialchars($_SESSION['reset_email']) ?></strong> terverifikasi. Buat password baru kamu.</p>

        <?php if ($error): ?><p class="msg-error"><?= htmlspecialchars($error) ?></p><?php endif; ?>

        <input type="password" name="pass_baru"       placeholder="Password baru"            required minlength="6" />
        <input type="password" name="pass_konfirmasi" placeholder="Konfirmasi password baru" required minlength="6" />

        <button type="submit" name="reset">Simpan Password</button>
        <button type="submit" name="batal" class="tombol-batal" formnovalidate>Batal</button>
      </form>
      <?php endif; ?>
    </div>

    <!-- ── OVERLAY ── -->
    <div class="overlay-container">
      <div class="overlay">
        <div class="overlay-panel overlay-right">
          <h1>Tenang, Untarian!</h1>
          <p>Lupa password itu biasa. Verifikasi akunmu, lalu buat password baru dalam hitungan detik.</p>
        </div>
      </div>
    </div>

  </div>
</body>
</html>
<?php
mysqli_close($conn);
ob_end_flush();
?>
