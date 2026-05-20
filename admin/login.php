<?php
session_start();

// If already logged in, redirect to index
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header('Location: index.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi!';
    } else {
        $settings_file = __DIR__ . '/../data/settings.json';
        if (file_exists($settings_file)) {
            $data = json_decode(file_get_contents($settings_file), true);
            $admin_user = $data['admin']['username'] ?? 'admin';
            $admin_hash = $data['admin']['password_hash'] ?? '';

            if ($username === $admin_user && password_verify($password, $admin_hash)) {
                // Login successful
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                header('Location: index.php');
                exit;
            } else {
                $error = 'Username atau password salah!';
            }
        } else {
            $error = 'File data pengaturan tidak ditemukan!';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login Admin - Konservatif. Cikupa</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    :root {
      --primary-coffee: #4A3B32;
      --primary-coffee-dark: #2F241D;
      --cream-light: #FAF6F0;
      --cream-medium: #EADBC8;
      --charcoal: #121212;
      --charcoal-light: #1E1E1E;
      --accent-orange: #D35400;
      --text-light: #FAF9F6;
      --text-muted: #A89284;
    }

    * {
      box-sizing: border-box;
      margin: 0;
      padding: 0;
    }

    body {
      font-family: 'Outfit', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
      background: linear-gradient(135deg, var(--charcoal) 0%, var(--primary-coffee-dark) 100%);
      color: var(--text-light);
      display: flex;
      justify-content: center;
      align-items: center;
      min-height: 100vh;
      padding: 24px;
      overflow: hidden;
    }

    .login-container {
      width: 100%;
      max-width: 420px;
      background: rgba(30, 30, 30, 0.75);
      border: 1px solid rgba(234, 219, 200, 0.1);
      backdrop-filter: blur(16px);
      -webkit-backdrop-filter: blur(16px);
      padding: 40px;
      border-radius: 16px;
      box-shadow: 0 20px 50px rgba(0, 0, 0, 0.3);
      position: relative;
    }

    .login-header {
      text-align: center;
      margin-bottom: 30px;
    }

    .login-header i {
      font-size: 2.5rem;
      color: var(--accent-orange);
      margin-bottom: 12px;
    }

    .login-header h1 {
      font-family: 'Playfair Display', serif;
      font-size: 1.8rem;
      font-weight: 700;
      color: var(--text-light);
      margin-bottom: 6px;
    }

    .login-header p {
      font-size: 0.9rem;
      color: var(--text-muted);
    }

    .form-group {
      margin-bottom: 20px;
      position: relative;
    }

    .form-label {
      display: block;
      font-size: 0.85rem;
      font-weight: 600;
      text-transform: uppercase;
      letter-spacing: 0.5px;
      margin-bottom: 8px;
      color: var(--cream-medium);
    }

    .input-wrapper {
      position: relative;
    }

    .input-wrapper i {
      position: absolute;
      left: 16px;
      top: 50%;
      transform: translateY(-50%);
      color: var(--text-muted);
      font-size: 1rem;
    }

    .form-control {
      width: 100%;
      padding: 14px 16px 14px 44px;
      border-radius: 8px;
      border: 1.5px solid rgba(234, 219, 200, 0.2);
      background-color: rgba(18, 18, 18, 0.5);
      color: var(--text-light);
      font-size: 0.95rem;
      transition: all 0.3s ease;
    }

    .form-control:focus {
      outline: none;
      border-color: var(--accent-orange);
      background-color: rgba(18, 18, 18, 0.8);
      box-shadow: 0 0 0 4px rgba(211, 84, 0, 0.15);
    }

    .btn-submit {
      width: 100%;
      padding: 14px;
      border-radius: 8px;
      background-color: var(--accent-orange);
      color: var(--text-light);
      border: none;
      font-weight: 600;
      font-size: 1rem;
      cursor: pointer;
      transition: all 0.3s ease;
      box-shadow: 0 4px 10px rgba(211, 84, 0, 0.3);
      margin-top: 10px;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 10px;
    }

    .btn-submit:hover {
      background-color: #E67E22;
      transform: translateY(-2px);
      box-shadow: 0 6px 15px rgba(211, 84, 0, 0.4);
    }

    .error-msg {
      background-color: rgba(231, 76, 60, 0.15);
      border: 1px solid rgba(231, 76, 60, 0.3);
      color: #e74c3c;
      padding: 12px 16px;
      border-radius: 8px;
      font-size: 0.85rem;
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .back-link {
      display: block;
      text-align: center;
      margin-top: 24px;
      font-size: 0.9rem;
      color: var(--text-muted);
      text-decoration: none;
      transition: color 0.2s ease;
    }

    .back-link:hover {
      color: var(--accent-orange);
    }

    .back-link i {
      margin-right: 4px;
    }
  </style>
</head>
<body>

  <div class="login-container">
    <div class="login-header">
      <i class="fa-solid fa-mug-hot"></i>
      <h1>Konservatif</h1>
      <p>Panel Kontrol Administrator</p>
    </div>

    <?php if (!empty($error)): ?>
      <div class="error-msg">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= htmlspecialchars($error) ?></span>
      </div>
    <?php endif; ?>

    <form action="" method="POST">
      <div class="form-group">
        <label for="username" class="form-label">Username</label>
        <div class="input-wrapper">
          <i class="fa-solid fa-user"></i>
          <input type="text" id="username" name="username" class="form-control" placeholder="Masukkan username" required autofocus>
        </div>
      </div>

      <div class="form-group">
        <label for="password" class="form-label">Password</label>
        <div class="input-wrapper">
          <i class="fa-solid fa-key"></i>
          <input type="password" id="password" name="password" class="form-control" placeholder="Masukkan password" required>
        </div>
      </div>

      <button type="submit" class="btn-submit">
        Masuk <i class="fa-solid fa-arrow-right-to-bracket"></i>
      </button>
    </form>

    <a href="../index.php" class="back-link">
      <i class="fa-solid fa-arrow-left"></i> Kembali ke Website
    </a>
  </div>

</body>
</html>
