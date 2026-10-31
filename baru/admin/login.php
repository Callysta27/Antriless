<?php
// ob_start HARUS paling pertama sebelum output apapun
ob_start();

require_once __DIR__ . '/frontend/includes/config.php';

$error_login    = '';
$error_signup   = '';
$success_signup = '';
$activePanel    = '';

// ── LOGIN ─────────────────────────────────────────────────────────────────────
if (isset($_POST['login'])) {
    $email    = trim($_POST['email_login']);
    $password = $_POST['pass_login'];

    $sql = mysqli_query($conn, "SELECT * FROM user WHERE email = '$email'");

    if (mysqli_num_rows($sql) > 0) {
        $row = mysqli_fetch_array($sql);

        if ($password === $row['password']) {
            // Tentukan session name berdasarkan role dari DB — SEBELUM session_start
            if (trim($row['role']) === 'pemilik_kantin') {
                session_name('admin_session');
            } else {
                session_name('user_session');
            }
            session_start();

            $_SESSION['user_id']     = $row['id_user'];
            $_SESSION['nama']        = $row['nama'];
            $_SESSION['role']        = $row['role'];
            $_SESSION['nama_kantin'] = $row['nama_kantin'] ?? '';

            if (trim($row['role']) === 'pemilik_kantin') {
                header("Location: dashboard_temp/dashboard.php");
            } else {
                header("Location: frontend/halaman utama.php");
            }
            ob_end_flush();
            exit;
        } else {
            $error_login = "Email atau password salah.";
        }
    } else {
        $error_login = "Email atau password salah.";
    }
}

// ── SIGNUP ────────────────────────────────────────────────────────────────────
if (isset($_POST['signup'])) {
    $nama        = trim($_POST['nama']);
    $email       = trim($_POST['email_signup']);
    $password    = $_POST['pass_signup'];
    $no_telp     = trim($_POST['no_telp'] ?? '');
    $role        = $_POST['role'] ?? '';
    $nim         = trim($_POST['nim']         ?? '');
    $nip         = trim($_POST['nip']         ?? '');
    $nama_kantin = trim($_POST['nama_kantin'] ?? '');

    $activePanel = 'right-panel-active';

    if (empty($nama) || empty($email) || empty($password) || empty($role)) {
        $error_signup = "Semua field wajib diisi.";
    } elseif (strlen($password) < 6) {
        $error_signup = "Password minimal 6 karakter.";
    } elseif ($role === 'mahasiswa' && empty($nim)) {
        $error_signup = "NIM wajib diisi untuk mahasiswa.";
    } elseif ($role === 'dosen' && empty($nip)) {
        $error_signup = "NIP wajib diisi untuk dosen.";
    } elseif ($role === 'pemilik_kantin' && empty($nama_kantin)) {
        $error_signup = "Nama kantin wajib diisi.";
    } else {
        $cek = mysqli_query($conn, "SELECT id_user FROM user WHERE email = '$email'");
        if (mysqli_num_rows($cek) > 0) {
            $error_signup = "Email sudah terdaftar. Silakan login.";
        } else {
            $sql_insert = "INSERT INTO user (nama, email, password, role, no_telp, nim, nip, nama_kantin)
                           VALUES (
                             '$nama', '$email', '$password', '$role', '$no_telp',
                             " . (empty($nim)         ? 'NULL' : "'$nim'")         . ",
                             " . (empty($nip)         ? 'NULL' : "'$nip'")         . ",
                             " . (empty($nama_kantin) ? 'NULL' : "'$nama_kantin'") . "
                           )";

            if (mysqli_query($conn, $sql_insert)) {
                // Set session untuk tampilkan pesan sukses
                session_name('user_session');
                session_start();
                $_SESSION['success_signup'] = "Akun berhasil dibuat! Silakan login.";
                header("Location: login.php");
                ob_end_flush();
                exit;
            } else {
                $error_signup = "Gagal menyimpan data: " . mysqli_error($conn);
            }
        }
    }
}

// ── Cek pesan sukses dari redirect signup ────────────────────────────────────
if (empty($activePanel)) {
    // Belum ada POST — cek apakah ada pesan signup dari session
    session_name('user_session');
    session_start();
    if (!empty($_SESSION['success_signup'])) {
        $success_signup = $_SESSION['success_signup'];
        unset($_SESSION['success_signup']);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <link rel="stylesheet" href="dashboard_temp/style/login.css?v=<?= filemtime(__DIR__ . '/dashboard_temp/style/login.css') ?>" />
  <title>Login — Kantin</title>
  <style>
    .msg-error   { color: #e74c3c; font-size: 13px; margin: 6px 0; text-align: center; }
    .msg-success { color: #27ae60; font-size: 13px; margin: 6px 0; text-align: center; }
    .input-kondisional { display: none; }
  </style>
</head>
<body>
  <div class="container" id="container">

    <!-- ── SIGN UP ── -->
    <div class="form-container sign-up-container">
      <form action="login.php" method="POST">
        <h1>Create Account</h1>

        <?php if ($error_signup):  ?><p class="msg-error"><?= htmlspecialchars($error_signup) ?></p><?php endif; ?>
        <?php if ($success_signup): ?><p class="msg-success"><?= htmlspecialchars($success_signup) ?></p><?php endif; ?>

        <input type="text"     name="nama"        placeholder="Name"          required
               value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" />

        <input type="email"    name="email_signup" placeholder="Email"         required
               value="<?= htmlspecialchars($_POST['email_signup'] ?? '') ?>" />

        <input type="password" name="pass_signup"  placeholder="Password"      required minlength="6" />

        <input type="text"     name="no_telp"      placeholder="Nomor Telepon"
               value="<?= htmlspecialchars($_POST['no_telp'] ?? '') ?>" />

        <label for="role">Pilih role Anda:</label>
        <select id="role" name="role" required onchange="tampilkanInputrole()">
          <option value="" disabled <?= empty($_POST['role']) ? 'selected' : '' ?>>-- Pilih role --</option>
          <option value="mahasiswa"      <?= (($_POST['role'] ?? '') === 'mahasiswa')      ? 'selected' : '' ?>>Mahasiswa</option>
          <option value="pemilik_kantin" <?= (($_POST['role'] ?? '') === 'pemilik_kantin') ? 'selected' : '' ?>>Pemilik Kantin</option>
          <option value="dosen"          <?= (($_POST['role'] ?? '') === 'dosen')          ? 'selected' : '' ?>>Dosen</option>
        </select>

        <input type="text" id="input-nim"    name="nim"         placeholder="NIM"
               class="input-kondisional"
               value="<?= htmlspecialchars($_POST['nim'] ?? '') ?>" />

        <input type="text" id="input-nip"    name="nip"         placeholder="NIP"
               class="input-kondisional"
               value="<?= htmlspecialchars($_POST['nip'] ?? '') ?>" />

        <input type="text" id="input-kantin" name="nama_kantin" placeholder="Nama Kantin"
               class="input-kondisional"
               value="<?= htmlspecialchars($_POST['nama_kantin'] ?? '') ?>" />

        <button type="submit" name="signup" style="margin-top:15px">Sign Up</button>
      </form>
    </div>

    <!-- ── SIGN IN ── -->
    <div class="form-container sign-in-container">
      <form action="login.php" method="POST">
        <h1>Sign in</h1>

        <?php if ($error_login): ?><p class="msg-error"><?= htmlspecialchars($error_login) ?></p><?php endif; ?>
        <?php if ($success_signup): ?><p class="msg-success"><?= htmlspecialchars($success_signup) ?></p><?php endif; ?>

        <input type="email"    name="email_login" placeholder="Email"    required
               value="<?= htmlspecialchars($_POST['email_login'] ?? '') ?>" />
        <input type="password" name="pass_login"  placeholder="Password" required />

        <a href="baru\admin\forgotpassword.php">Forgot your password?</a>
        <button type="submit" name="login">Sign In</button>
      </form>
    </div>

    <!-- ── OVERLAY ── -->
    <div class="overlay-container">
      <div class="overlay">
        <div class="overlay-panel overlay-left">
          <h1>Welcome Back!</h1>
          <p>To keep connected with us please login with your personal info</p>
          <button class="ghost" id="signIn">Sign In</button>
        </div>
        <div class="overlay-panel overlay-right">
          <h1>Hello, Untarian!</h1>
          <p>Enter your personal details and start journey with us</p>
          <button class="ghost" id="signUp">Sign Up</button>
        </div>
      </div>
    </div>

  </div>

  <script>
    const container = document.getElementById('container');

    <?php if ($activePanel): ?>
    container.classList.add('right-panel-active');
    <?php endif; ?>

    document.getElementById('signUp').addEventListener('click', () => {
      container.classList.add('right-panel-active');
    });
    document.getElementById('signIn').addEventListener('click', () => {
      container.classList.remove('right-panel-active');
    });

    function tampilkanInputrole() {
      const role = document.getElementById('role').value;
      document.getElementById('input-nim').style.display    = 'none';
      document.getElementById('input-nip').style.display    = 'none';
      document.getElementById('input-kantin').style.display = 'none';

      if (role === 'mahasiswa')           document.getElementById('input-nim').style.display    = 'block';
      else if (role === 'dosen')          document.getElementById('input-nip').style.display    = 'block';
      else if (role === 'pemilik_kantin') document.getElementById('input-kantin').style.display = 'block';
    }

    window.addEventListener('DOMContentLoaded', tampilkanInputrole);
  </script>

</body>
</html>
<?php
mysqli_close($conn);
ob_end_flush();
?>