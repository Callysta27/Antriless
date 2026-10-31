<?php
// ob_start HARUS paling pertama sebelum output apapun
ob_start();

require_once __DIR__ . '/frontend/includes/config.php';

$error_login    = '';
$error_signup   = '';
$success_signup = '';
$activePanel    = '';

// ── AKUN DEMO ─────────────────────────────────────────────────────────────────
// Email akun demo (sesuai data contoh di database/init.sql).
// Password tidak ditulis di HTML: server langsung login-kan akun ini.
$akunDemo = [
    'mahasiswa'      => 'budi@untar.ac.id',
    'dosen'          => 'dosen@untar.ac.id',
    'pemilik_kantin' => 'kantin@antriless.com',
];

// Simpan data user ke session lalu arahkan sesuai role
function masukkanUser($row)
{
    $adminKantin = trim($row['role']) === 'pemilik_kantin';

    // Session name ditentukan SEBELUM session_start
    session_name($adminKantin ? 'admin_session' : 'user_session');
    session_start();
    session_regenerate_id(true);

    $_SESSION['user_id']     = $row['id_user'];
    $_SESSION['nama']        = $row['nama'];
    $_SESSION['role']        = $row['role'];
    $_SESSION['nama_kantin'] = $row['nama_kantin'] ?? '';

    header('Location: ' . ($adminKantin ? 'dashboard_temp/dashboard.php' : 'frontend/halaman utama.php'));
    ob_end_flush();
    exit;
}

function cariUserByEmail($conn, $email)
{
    $stmt = mysqli_prepare($conn, "SELECT * FROM user WHERE email = ? LIMIT 1");
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    $row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);
    return $row;
}

// ── LOGIN DEMO ────────────────────────────────────────────────────────────────
if (isset($_POST['demo'])) {
    $roleDemo = $_POST['demo'];

    if (isset($akunDemo[$roleDemo])) {
        $row = cariUserByEmail($conn, $akunDemo[$roleDemo]);
        if ($row) masukkanUser($row);
    }
    $error_login = "Akun demo belum tersedia. Pastikan data contoh di database sudah di-import.";
}

// ── LOGIN ─────────────────────────────────────────────────────────────────────
if (isset($_POST['login'])) {
    $email    = trim($_POST['email_login'] ?? '');
    $password = $_POST['pass_login'] ?? '';

    $row = cariUserByEmail($conn, $email);

    if ($row && $password === $row['password']) {
        masukkanUser($row);
    }
    $error_login = "Email atau password salah.";
}

// ── SIGNUP ────────────────────────────────────────────────────────────────────
if (isset($_POST['signup'])) {
    $nama        = trim($_POST['nama'] ?? '');
    $email       = trim($_POST['email_signup'] ?? '');
    $password    = $_POST['pass_signup'] ?? '';
    $no_telp     = trim($_POST['no_telp'] ?? '');
    $role        = $_POST['role'] ?? '';
    $nim         = trim($_POST['nim']         ?? '');
    $nip         = trim($_POST['nip']         ?? '');
    $nama_kantin = trim($_POST['nama_kantin'] ?? '');

    $activePanel = 'right-panel-active';

    if (empty($nama) || empty($email) || empty($password) || empty($role)) {
        $error_signup = "Semua field wajib diisi.";
    } elseif (!in_array($role, ['mahasiswa', 'dosen', 'pemilik_kantin'], true)) {
        $error_signup = "Role tidak valid.";
    } elseif (strlen($password) < 6) {
        $error_signup = "Password minimal 6 karakter.";
    } elseif ($role === 'mahasiswa' && empty($nim)) {
        $error_signup = "NIM wajib diisi untuk mahasiswa.";
    } elseif ($role === 'dosen' && empty($nip)) {
        $error_signup = "NIP wajib diisi untuk dosen.";
    } elseif ($role === 'pemilik_kantin' && empty($nama_kantin)) {
        $error_signup = "Nama kantin wajib diisi.";
    } elseif (cariUserByEmail($conn, $email)) {
        $error_signup = "Email sudah terdaftar. Silakan login.";
    } else {
        $nim         = $nim         ?: null;
        $nip         = $nip         ?: null;
        $nama_kantin = $nama_kantin ?: null;

        $stmt = mysqli_prepare($conn, "
            INSERT INTO user (nama, email, password, role, no_telp, nim, nip, nama_kantin)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, 'ssssssss', $nama, $email, $password, $role, $no_telp, $nim, $nip, $nama_kantin);

        if (mysqli_stmt_execute($stmt)) {
            // Set session untuk tampilkan pesan sukses
            session_name('user_session');
            session_start();
            $_SESSION['success_signup'] = "Akun berhasil dibuat! Silakan login.";
            header("Location: login.php");
            ob_end_flush();
            exit;
        }
        $error_signup = "Gagal menyimpan data. Coba lagi.";
    }
}

// ── Cek pesan sukses dari redirect signup ────────────────────────────────────
if (empty($activePanel)) {
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
  <title>Login — Antriless</title>
  <style>
    .msg-error   { color: #e74c3c; font-size: 13px; margin: 6px 0; text-align: center; }
    .msg-success { color: #27ae60; font-size: 13px; margin: 6px 0; text-align: center; }
    .input-kondisional { display: none; }

    /* ── Tombol akun demo ── */
    .demo-box {
      width: 100%;
      margin-top: 18px;
      padding-top: 14px;
      border-top: 1px dashed #ddd;
      text-align: center;
    }

    .demo-judul {
      margin: 0 0 8px;
      font-size: 12px;
      color: #888;
    }

    .demo-list {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 6px;
    }

    .demo-list button.demo-btn {
      margin: 0;
      padding: 7px 12px;
      border: 1.5px solid #534AB7;
      border-radius: 999px;
      background: #fff;
      color: #534AB7;
      font-size: 11px;
      font-weight: 600;
      letter-spacing: 0;
      text-transform: none;
      cursor: pointer;
      transition: background .15s, color .15s;
    }

    .demo-list button.demo-btn:hover {
      background: #534AB7;
      color: #fff;
    }
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

        <a href="forgotpassword.php">Forgot your password?</a>
        <button type="submit" name="login">Sign In</button>

        <!-- Akun demo: langsung masuk tanpa mengetik email & password -->
        <div class="demo-box">
          <p class="demo-judul">Coba tanpa daftar, masuk sebagai:</p>
          <div class="demo-list">
            <button type="submit" name="demo" value="mahasiswa"      class="demo-btn" formnovalidate>🎓 Mahasiswa</button>
            <button type="submit" name="demo" value="dosen"          class="demo-btn" formnovalidate>👨‍🏫 Dosen</button>
            <button type="submit" name="demo" value="pemilik_kantin" class="demo-btn" formnovalidate>🧑‍🍳 Ibu Kantin</button>
          </div>
        </div>
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