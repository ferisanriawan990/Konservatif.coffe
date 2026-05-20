<?php
session_start();

$settings_file = __DIR__ . '/../data/settings.json';
$messages_file = __DIR__ . '/../data/messages.json';

// Detect if running on Vercel
function is_on_vercel() {
    return (
        isset($_SERVER['VERCEL']) ||
        getenv('VERCEL') !== false ||
        getenv('NOW_REGION') !== false ||
        isset($_ENV['VERCEL']) ||
        strpos(__FILE__, '/var/task') !== false ||
        strpos($_SERVER['DOCUMENT_ROOT'] ?? '', '/var/task') !== false
    );
}

// Helper function to read settings
function get_settings($file) {
    if (file_exists($file)) {
        $json = file_get_contents($file);
        return json_decode($json, true) ?? [];
    }
    return [];
}

// Helper function to save settings
function save_settings($file, $data) {
    if (is_on_vercel()) {
        return false;
    }
    return file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
}

// Helper function to save messages
function save_messages($file, $messages) {
    if (is_on_vercel()) {
        return false;
    }
    return file_put_contents($file, json_encode($messages, JSON_PRETTY_PRINT));
}

// Helper to escape output
function e($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

$data = get_settings($settings_file);
$contact = $data['contact'] ?? [];

// Function to check if admin is logged in (session or cookie fallback)
function is_admin_logged_in($data) {
    if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
        return true;
    }
    
    // Cookie fallback for serverless environments (Vercel)
    if (isset($_COOKIE['admin_user']) && isset($_COOKIE['admin_token'])) {
        $username = $_COOKIE['admin_user'];
        $token = $_COOKIE['admin_token'];
        
        $admin_user = $data['admin']['username'] ?? 'admin';
        $admin_hash = $data['admin']['password_hash'] ?? '';
        
        if ($username === $admin_user && !empty($admin_hash)) {
            $expected_token = hash_hmac('sha256', $username, $admin_hash);
            if (hash_equals($expected_token, $token)) {
                // Restore session
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_username'] = $username;
                return true;
            }
        }
    }
    return false;
}

if (!is_admin_logged_in($data)) {
    header('Location: login.php');
    exit;
}


// Stats for dashboard overview
$total_menu = isset($data['menu']) ? count($data['menu']) : 0;
$total_gallery = isset($data['gallery']) ? count($data['gallery']) : 0;
$total_testimonials = isset($data['testimonials']) ? count($data['testimonials']) : 0;

$messages = [];
if (file_exists($messages_file)) {
    $messages = json_decode(file_get_contents($messages_file), true) ?? [];
}
$total_messages = count($messages);

$alert_type = '';
$alert_msg = '';

// Handle file uploads helper
function upload_image($file_key) {
    if (isset($_FILES[$file_key]) && $_FILES[$file_key]['error'] === UPLOAD_ERR_OK) {
        if (is_on_vercel()) {
            return null;
        }
        $file_tmp = $_FILES[$file_key]['tmp_name'];
        $file_name = $_FILES[$file_key]['name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        
        $allowed_exts = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
        if (in_array($file_ext, $allowed_exts)) {
            // Generate unique filename and save to uploads folder
            $new_name = uniqid('img_') . '.' . $file_ext;
            $dest_dir = __DIR__ . '/../uploads/';
            if (!is_dir($dest_dir)) {
                mkdir($dest_dir, 0777, true);
            }
            $dest_path = $dest_dir . $new_name;
            if (move_uploaded_file($file_tmp, $dest_path)) {
                return 'uploads/' . $new_name;
            }
        }
    }
    return null;
}

// Handle Form Submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    // 1. Save General Info & SEO
    if ($action === 'save_general') {
        $data['seo']['title'] = trim($_POST['seo_title'] ?? '');
        $data['seo']['description'] = trim($_POST['seo_description'] ?? '');
        
        $data['general']['site_name'] = trim($_POST['site_name'] ?? '');
        $data['general']['title'] = trim($_POST['hero_title'] ?? '');
        $data['general']['subtitle'] = trim($_POST['hero_subtitle'] ?? '');
        $data['general']['description'] = trim($_POST['hero_desc'] ?? '');
        $data['general']['about_text'] = trim($_POST['about_text'] ?? '');
        
        // Handle hero photo upload
        $hero_upload = upload_image('hero_image');
        if ($hero_upload) {
            $data['general']['hero_image'] = $hero_upload;
        }

        // Handle about photo upload
        $about_upload = upload_image('about_image');
        if ($about_upload) {
            $data['general']['about_image'] = $about_upload;
        }
        
        if (save_settings($settings_file, $data)) {
            $alert_type = 'success';
            $alert_msg = 'Pengaturan umum berhasil disimpan!';
        } else {
            $alert_type = 'error';
            $alert_msg = 'Gagal menyimpan pengaturan umum.';
        }
    }

    // 2. Save Contact & Location Info
    elseif ($action === 'save_contact') {
        $data['contact']['address'] = trim($_POST['address'] ?? '');
        $data['contact']['opening_hours'] = trim($_POST['opening_hours'] ?? '');
        $data['contact']['maps_link'] = trim($_POST['maps_link'] ?? '');
        $data['contact']['maps_embed'] = trim($_POST['maps_embed'] ?? '');
        $data['contact']['whatsapp_number'] = trim($_POST['whatsapp_number'] ?? '');
        $data['contact']['instagram_link'] = trim($_POST['instagram_link'] ?? '');
        
        if (save_settings($settings_file, $data)) {
            $alert_type = 'success';
            $alert_msg = 'Informasi kontak dan lokasi berhasil disimpan!';
        } else {
            $alert_type = 'error';
            $alert_msg = 'Gagal menyimpan informasi kontak.';
        }
    }

    // 3. Add Menu Item
    elseif ($action === 'add_menu') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = intval($_POST['price'] ?? 0);
        $hot_price = intval($_POST['hot_price'] ?? 0);
        $ice_price = intval($_POST['ice_price'] ?? 0);
        $variant = trim($_POST['variant'] ?? '');
        
        $image_path = upload_image('menu_image') ?? '';
        
        if (!empty($name) && !empty($category)) {
            $new_menu = [
                'id' => uniqid('m'),
                'category' => $category,
                'name' => $name,
                'description' => $description,
                'price' => $price,
                'hot_price' => $hot_price,
                'ice_price' => $ice_price,
                'variant' => $variant,
                'image' => $image_path
            ];
            
            $data['menu'][] = $new_menu;
            if (save_settings($settings_file, $data)) {
                $alert_type = 'success';
                $alert_msg = 'Menu baru berhasil ditambahkan!';
                $total_menu++;
            }
        } else {
            $alert_type = 'error';
            $alert_msg = 'Harap isi nama menu dan kategori!';
        }
    }

    // 4. Edit Menu Item
    elseif ($action === 'edit_menu') {
        $id = $_POST['menu_id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $price = intval($_POST['price'] ?? 0);
        $hot_price = intval($_POST['hot_price'] ?? 0);
        $ice_price = intval($_POST['ice_price'] ?? 0);
        $variant = trim($_POST['variant'] ?? '');
        
        $found = false;
        foreach ($data['menu'] as &$item) {
            if ($item['id'] === $id) {
                $item['name'] = $name;
                $item['category'] = $category;
                $item['description'] = $description;
                $item['price'] = $price;
                $item['hot_price'] = $hot_price;
                $item['ice_price'] = $ice_price;
                $item['variant'] = $variant;
                
                // If new image uploaded, replace
                $new_image = upload_image('menu_image');
                if ($new_image) {
                    // Delete old file if exists
                    if (!empty($item['image']) && file_exists(__DIR__ . '/../' . $item['image'])) {
                        @unlink(__DIR__ . '/../' . $item['image']);
                    }
                    $item['image'] = $new_image;
                }
                $found = true;
                break;
            }
        }
        
        if ($found && save_settings($settings_file, $data)) {
            $alert_type = 'success';
            $alert_msg = 'Menu berhasil diperbarui!';
        } else {
            $alert_type = 'error';
            $alert_msg = 'Gagal memperbarui menu.';
        }
    }

    // 5. Delete Menu Item
    elseif ($action === 'delete_menu') {
        $id = $_POST['menu_id'] ?? '';
        $filtered_menu = [];
        $deleted = false;
        
        foreach ($data['menu'] as $item) {
            if ($item['id'] === $id) {
                // Delete photo from directory
                if (!empty($item['image']) && file_exists(__DIR__ . '/../' . $item['image'])) {
                    @unlink(__DIR__ . '/../' . $item['image']);
                }
                $deleted = true;
                $total_menu--;
            } else {
                $filtered_menu[] = $item;
            }
        }
        
        $data['menu'] = $filtered_menu;
        if ($deleted && save_settings($settings_file, $data)) {
            $alert_type = 'success';
            $alert_msg = 'Menu berhasil dihapus!';
        } else {
            $alert_type = 'error';
            $alert_msg = 'Gagal menghapus menu.';
        }
    }

    // 6. Add Gallery Item
    elseif ($action === 'add_gallery') {
        $category = trim($_POST['category'] ?? 'atmosphere');
        $caption = trim($_POST['caption'] ?? '');
        $gallery_img = upload_image('gallery_image');
        
        if ($gallery_img) {
            $new_gallery = [
                'id' => uniqid('g'),
                'image' => $gallery_img,
                'category' => $category,
                'caption' => $caption
            ];
            
            $data['gallery'][] = $new_gallery;
            if (save_settings($settings_file, $data)) {
                $alert_type = 'success';
                $alert_msg = 'Foto galeri berhasil ditambahkan!';
                $total_gallery++;
            }
        } else {
            $alert_type = 'error';
            $alert_msg = 'Harap unggah gambar untuk galeri!';
        }
    }

    // 7. Delete Gallery Item
    elseif ($action === 'delete_gallery') {
        $id = $_POST['gallery_id'] ?? '';
        $filtered_gallery = [];
        $deleted = false;
        
        foreach ($data['gallery'] as $item) {
            if ($item['id'] === $id) {
                // Delete file from disk
                if (!empty($item['image']) && file_exists(__DIR__ . '/../' . $item['image'])) {
                    @unlink(__DIR__ . '/../' . $item['image']);
                }
                $deleted = true;
                $total_gallery--;
            } else {
                $filtered_gallery[] = $item;
            }
        }
        
        $data['gallery'] = $filtered_gallery;
        if ($deleted && save_settings($settings_file, $data)) {
            $alert_type = 'success';
            $alert_msg = 'Foto galeri berhasil dihapus!';
        } else {
            $alert_type = 'error';
            $alert_msg = 'Gagal menghapus foto galeri.';
        }
    }

    // 8. Add Testimonial
    elseif ($action === 'add_testimonial') {
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? 'Pelanggan');
        $text = trim($_POST['text'] ?? '');
        
        if (!empty($name) && !empty($text)) {
            $new_t = [
                'id' => uniqid('t'),
                'name' => $name,
                'role' => $role,
                'text' => $text
            ];
            
            $data['testimonials'][] = $new_t;
            if (save_settings($settings_file, $data)) {
                $alert_type = 'success';
                $alert_msg = 'Testimoni baru berhasil ditambahkan!';
                $total_testimonials++;
            }
        } else {
            $alert_type = 'error';
            $alert_msg = 'Harap isi nama dan ulasan testimoni!';
        }
    }

    // 9. Edit Testimonial
    elseif ($action === 'edit_testimonial') {
        $id = $_POST['testimonial_id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $role = trim($_POST['role'] ?? 'Pelanggan');
        $text = trim($_POST['text'] ?? '');
        
        $found = false;
        foreach ($data['testimonials'] as &$item) {
            if ($item['id'] === $id) {
                $item['name'] = $name;
                $item['role'] = $role;
                $item['text'] = $text;
                $found = true;
                break;
            }
        }
        
        if ($found && save_settings($settings_file, $data)) {
            $alert_type = 'success';
            $alert_msg = 'Testimoni berhasil diperbarui!';
        } else {
            $alert_type = 'error';
            $alert_msg = 'Gagal memperbarui testimoni.';
        }
    }

    // 10. Delete Testimonial
    elseif ($action === 'delete_testimonial') {
        $id = $_POST['testimonial_id'] ?? '';
        $filtered_t = [];
        $deleted = false;
        
        foreach ($data['testimonials'] as $item) {
            if ($item['id'] === $id) {
                $deleted = true;
                $total_testimonials--;
            } else {
                $filtered_t[] = $item;
            }
        }
        
        $data['testimonials'] = $filtered_t;
        if ($deleted && save_settings($settings_file, $data)) {
            $alert_type = 'success';
            $alert_msg = 'Testimoni berhasil dihapus!';
        } else {
            $alert_type = 'error';
            $alert_msg = 'Gagal menghapus testimoni.';
        }
    }

    // 11. Delete Message
    elseif ($action === 'delete_message') {
        $id = $_POST['message_id'] ?? '';
        $filtered_m = [];
        $deleted = false;
        
        foreach ($messages as $msg) {
            if ($msg['id'] === $id) {
                $deleted = true;
                $total_messages--;
            } else {
                $filtered_m[] = $msg;
            }
        }
        
        $messages = $filtered_m;
        if ($deleted && save_messages($messages_file, $messages)) {
            $alert_type = 'success';
            $alert_msg = 'Pesan kotak masuk berhasil dihapus!';
        } else {
            $alert_type = 'error';
            $alert_msg = 'Gagal menghapus pesan.';
        }
    }

    // 12. Save Security Credentials
    elseif ($action === 'save_security') {
        $current_pass = $_POST['current_pass'] ?? '';
        $new_user = trim($_POST['username'] ?? '');
        $new_pass = $_POST['new_pass'] ?? '';
        $confirm_pass = $_POST['confirm_pass'] ?? '';
        
        $admin_hash = $data['admin']['password_hash'] ?? '';
        
        if (password_verify($current_pass, $admin_hash)) {
            if (!empty($new_user)) {
                $data['admin']['username'] = $new_user;
            }
            
            if (!empty($new_pass)) {
                if ($new_pass === $confirm_pass) {
                    $data['admin']['password_hash'] = password_hash($new_pass, PASSWORD_DEFAULT);
                } else {
                    $alert_type = 'error';
                    $alert_msg = 'Password baru dan konfirmasi tidak cocok!';
                    $action = 'error_security';
                }
            }
            
            if ($action !== 'error_security') {
                if (save_settings($settings_file, $data)) {
                    $alert_type = 'success';
                    $alert_msg = 'Kredensial login berhasil diperbarui!';
                } else {
                    $alert_type = 'error';
                    $alert_msg = 'Gagal menyimpan pembaruan kredensial.';
                }
            }
        } else {
            $alert_type = 'error';
            $alert_msg = 'Password saat ini salah. Perubahan ditolak!';
        }
    }
    
    // Vercel Read-Only override
    if (is_on_vercel()) {
        $alert_type = 'error';
        $alert_msg = 'Demo Vercel: Perubahan tidak disimpan karena sistem file bersifat Read-Only (Hanya Baca). Jalankan di XAMPP lokal untuk mengelola secara permanen.';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Admin Dashboard - konservatif.coffee</title>
  <!-- FontAwesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Stylesheets -->
  <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>

  <!-- Sidebar Control -->
  <div class="sidebar">
    <div class="sidebar-brand">
      <i class="fa-solid fa-mug-hot"></i>
      <div>
        <h2>konservatif.coffee</h2>
        <small>Admin Dashboard</small>
      </div>
    </div>
    
    <ul class="sidebar-menu">
      <li>
        <a href="#stats" class="tab-link active" data-tab="tab-stats">
          <i class="fa-solid fa-chart-line"></i> <span>Ringkasan</span>
        </a>
      </li>
      <li>
        <a href="#umum" class="tab-link" data-tab="tab-umum">
          <i class="fa-solid fa-sliders"></i> <span>Tampilan Umum & SEO</span>
        </a>
      </li>
      <li>
        <a href="#kontak" class="tab-link" data-tab="tab-kontak">
          <i class="fa-solid fa-address-book"></i> <span>Kontak & Lokasi</span>
        </a>
      </li>
      <li>
        <a href="#menu-makanan" class="tab-link" data-tab="tab-menu">
          <i class="fa-solid fa-utensils"></i> <span>Daftar Menu</span>
        </a>
      </li>
      <li>
        <a href="#galeri-foto" class="tab-link" data-tab="tab-gallery">
          <i class="fa-solid fa-images"></i> <span>Galeri Foto</span>
        </a>
      </li>
      <li>
        <a href="#testimoni" class="tab-link" data-tab="tab-testimonials">
          <i class="fa-solid fa-quote-left"></i> <span>Testimoni</span>
        </a>
      </li>
      <li>
        <a href="#pesan" class="tab-link" data-tab="tab-messages">
          <i class="fa-solid fa-envelope"></i> <span>Pesan Masuk</span>
          <?php if ($total_messages > 0): ?>
            <span class="badge badge-msg"><?= $total_messages ?></span>
          <?php endif; ?>
        </a>
      </li>
      <li>
        <a href="#keamanan" class="tab-link" data-tab="tab-security">
          <i class="fa-solid fa-shield-halved"></i> <span>Keamanan</span>
        </a>
      </li>
      <li style="margin-top: auto;">
        <a href="../index.php" target="_blank">
          <i class="fa-solid fa-globe"></i> <span>Lihat Website</span>
        </a>
      </li>
      <li>
        <a href="logout.php" style="color: #e74c3c;">
          <i class="fa-solid fa-right-from-bracket"></i> <span>Keluar</span>
        </a>
      </li>
    </ul>
  </div>

  <!-- Main Content Wrapper -->
  <div class="main-content">
    
    <!-- Top Nav Header -->
    <header class="top-nav">
      <div class="menu-toggle">
        <i class="fa-solid fa-bars"></i>
      </div>
      <div class="user-profile">
        <span>Halo, <strong><?= e($_SESSION['admin_username']) ?></strong></span>
        <i class="fa-solid fa-circle-user"></i>
      </div>
    </header>

    <!-- Content Panel Body -->
    <div class="content-body">
      
      <!-- Notifications Alert -->
      <?php if (!empty($alert_msg)): ?>
        <div class="alert alert-<?= $alert_type ?>" id="status-alert">
          <i class="fa-solid <?= $alert_type === 'success' ? 'fa-circle-check' : 'fa-circle-exclamation' ?>"></i>
          <span><?= e($alert_msg) ?></span>
          <button class="alert-close" onclick="document.getElementById('status-alert').style.display='none';">&times;</button>
        </div>
      <?php endif; ?>

      <!-- 1. TAB STATS (OVERVIEW) -->
      <div class="tab-content active" id="tab-stats">
        <h1 class="page-title">Ringkasan Website</h1>
        <p class="page-subtitle">Selamat datang di Panel Administrasi website konservatif.coffee. Berikut statistik data Anda saat ini.</p>
        
        <!-- Stats Cards Grid -->
        <div class="stats-grid">
          <div class="card-stat">
            <div class="stat-icon" style="background-color: rgba(211, 84, 0, 0.1); color: var(--accent-orange);">
              <i class="fa-solid fa-utensils"></i>
            </div>
            <div class="stat-info">
              <h3><?= $total_menu ?></h3>
              <p>Total Menu Sajian</p>
            </div>
          </div>

          <div class="card-stat">
            <div class="stat-icon" style="background-color: rgba(52, 152, 219, 0.1); color: #3498db;">
              <i class="fa-solid fa-images"></i>
            </div>
            <div class="stat-info">
              <h3><?= $total_gallery ?></h3>
              <p>Foto Galeri</p>
            </div>
          </div>

          <div class="card-stat">
            <div class="stat-icon" style="background-color: rgba(155, 89, 182, 0.1); color: #9b59b6;">
              <i class="fa-solid fa-quote-left"></i>
            </div>
            <div class="stat-info">
              <h3><?= $total_testimonials ?></h3>
              <p>Ulasan Pelanggan</p>
            </div>
          </div>

          <div class="card-stat">
            <div class="stat-icon" style="background-color: rgba(46, 204, 113, 0.1); color: #2ecc71;">
              <i class="fa-solid fa-envelope"></i>
            </div>
            <div class="stat-info">
              <h3><?= $total_messages ?></h3>
              <p>Pesan Kontak</p>
            </div>
          </div>
        </div>

        <!-- Quick actions -->
        <div class="quick-actions-panel" style="margin-top: 40px;">
          <h2>Pintasan Cepat</h2>
          <div class="shortcuts-grid">
            <a href="#menu-makanan" onclick="switchTab('tab-menu')" class="btn-shortcut"><i class="fa-solid fa-plus"></i> Tambah Menu Baru</a>
            <a href="#galeri-foto" onclick="switchTab('tab-gallery')" class="btn-shortcut"><i class="fa-solid fa-upload"></i> Unggah Foto Galeri</a>
            <a href="#umum" onclick="switchTab('tab-umum')" class="btn-shortcut"><i class="fa-solid fa-pen"></i> Ubah Deskripsi Hero</a>
            <a href="#pesan" onclick="switchTab('tab-messages')" class="btn-shortcut"><i class="fa-solid fa-envelope-open-text"></i> Baca Kotak Masuk</a>
          </div>
        </div>
      </div>

      <!-- 2. TAB GENERAL INFO & SEO -->
      <div class="tab-content" id="tab-umum">
        <h1 class="page-title">Tampilan Umum & SEO</h1>
        <p class="page-subtitle">Ubah teks pembuka, deskripsi coffee shop, dan data Search Engine Optimization (SEO).</p>
        
        <form action="" method="POST" enctype="multipart/form-data">
          <input type="hidden" name="action" value="save_general">
          
          <div class="card-dashboard">
            <div class="card-header">Pengaturan SEO (Google Search)</div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-label">SEO Title (Judul Tab Web)</label>
                <input type="text" name="seo_title" class="form-control" value="<?= e($data['seo']['title'] ?? '') ?>" required>
                <small class="form-help">Judul yang muncul di hasil pencarian Google dan nama tab browser (Rekomendasi: 50-60 karakter).</small>
              </div>
              <div class="form-group">
                <label class="form-label">SEO Meta Description</label>
                <textarea name="seo_description" class="form-control" rows="3" required><?= e($data['seo']['description'] ?? '') ?></textarea>
                <small class="form-help">Ringkasan isi website yang muncul di deskripsi pencarian Google (Rekomendasi: 120-160 karakter).</small>
              </div>
            </div>
          </div>

          <div class="card-dashboard">
            <div class="card-header">Bagian Hero Halaman Depan</div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-label">Nama Website / Logo</label>
                <input type="text" name="site_name" class="form-control" value="<?= e($data['general']['site_name'] ?? '') ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Judul Utama Hero (H1)</label>
                <input type="text" name="hero_title" class="form-control" value="<?= e($data['general']['title'] ?? '') ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Subjudul Hero</label>
                <input type="text" name="hero_subtitle" class="form-control" value="<?= e($data['general']['subtitle'] ?? '') ?>" required>
              </div>
              <div class="form-group">
                <label class="form-label">Deskripsi Ringkas Hero</label>
                <textarea name="hero_desc" class="form-control" rows="3" required><?= e($data['general']['description'] ?? '') ?></textarea>
              </div>
              <div class="form-group">
                <label class="form-label">Foto Latar Belakang Hero (Ukuran besar, min 1920x1080px)</label>
                <?php if (!empty($data['general']['hero_image'])): ?>
                  <div class="preview-img-wrapper">
                    <img src="../<?= e($data['general']['hero_image']) ?>" alt="Hero Preview">
                  </div>
                <?php endif; ?>
                <input type="file" name="hero_image" class="form-file">
                <small class="form-help">Biarkan kosong jika tidak ingin mengganti foto latar saat ini.</small>
              </div>
            </div>
          </div>

          <div class="card-dashboard">
            <div class="card-header">Bagian Tentang Kami</div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-label">Teks Tentang Kami</label>
                <textarea name="about_text" class="form-control" rows="6" required><?= e($data['general']['about_text'] ?? '') ?></textarea>
              </div>
              <div class="form-group">
                <label class="form-label">Foto Tentang Kami (Disarankan portrait 4:5)</label>
                <?php if (!empty($data['general']['about_image'])): ?>
                  <div class="preview-img-wrapper" style="max-width: 150px;">
                    <img src="../<?= e($data['general']['about_image']) ?>" alt="About Preview">
                  </div>
                <?php endif; ?>
                <input type="file" name="about_image" class="form-file">
                <small class="form-help">Biarkan kosong jika tidak ingin mengganti foto saat ini.</small>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-save"><i class="fa-solid fa-save"></i> Simpan Semua Perubahan</button>
        </form>
      </div>

      <!-- 3. TAB CONTACT & LOCATION -->
      <div class="tab-content" id="tab-kontak">
        <h1 class="page-title">Kontak & Lokasi</h1>
        <p class="page-subtitle">Kelola jam buka, alamat, WhatsApp, Instagram, dan sematan Google Maps.</p>
        
        <form action="" method="POST">
          <input type="hidden" name="action" value="save_contact">

          <div class="card-dashboard">
            <div class="card-header">Informasi Alamat & Jam Buka</div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-label">Alamat Lengkap</label>
                <textarea name="address" class="form-control" rows="3" required><?= e($contact['address'] ?? '') ?></textarea>
              </div>
              <div class="form-group">
                <label class="form-label">Jam Operasional (Jam Buka)</label>
                <input type="text" name="opening_hours" class="form-control" value="<?= e($contact['opening_hours'] ?? '') ?>" placeholder="Setiap Hari: 10:00 - 22:00 WIB" required>
              </div>
            </div>
          </div>

          <div class="card-dashboard">
            <div class="card-header">Google Maps Integration</div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-label">Link Navigasi Google Maps (Buka Google Maps)</label>
                <input type="url" name="maps_link" class="form-control" value="<?= e($contact['maps_link'] ?? '') ?>" required>
                <small class="form-help">Link eksternal yang terbuka ketika pengunjung mengeklik tombol "Buka Google Maps".</small>
              </div>
              <div class="form-group">
                <label class="form-label">Sematan Embed Iframe Google Maps (URL `src` saja)</label>
                <textarea name="maps_embed" class="form-control" rows="3" placeholder="https://www.google.com/maps/embed?..." required><?= e($contact['maps_embed'] ?? '') ?></textarea>
                <small class="form-help">Petunjuk: Buka lokasi di Google Maps -> Klik Bagikan -> Pilih "Sematkan Peta" -> Ambil bagian <strong>src="url"</strong> di dalam tag iframe.</small>
              </div>
            </div>
          </div>

          <div class="card-dashboard">
            <div class="card-header">Nomor WhatsApp & Instagram</div>
            <div class="card-body">
              <div class="form-group">
                <label class="form-label">Nomor WhatsApp Admin (Format Internasional: Tanpa +, spasi, atau strip)</label>
                <input type="text" name="whatsapp_number" class="form-control" value="<?= e($contact['whatsapp_number'] ?? '') ?>" placeholder="Contoh: 628123456789" required>
                <small class="form-help">Gunakan kode negara, contoh <strong>628123456789</strong> pengganti 08123456789.</small>
              </div>
              <div class="form-group">
                <label class="form-label">Link Profil Instagram</label>
                <input type="url" name="instagram_link" class="form-control" value="<?= e($contact['instagram_link'] ?? '') ?>" placeholder="https://www.instagram.com/nama_profile" required>
              </div>
            </div>
          </div>

          <button type="submit" class="btn btn-save"><i class="fa-solid fa-save"></i> Simpan Informasi Kontak</button>
        </form>
      </div>

      <!-- 4. TAB MENU -->
      <div class="tab-content" id="tab-menu">
        <h1 class="page-title">Kelola Menu Sajian</h1>
        <p class="page-subtitle">Tambah, edit, dan hapus sajian di 7 kategori menu.</p>
        
        <div class="dashboard-split">
          <!-- Add Form -->
          <div class="form-panel-side">
            <div class="card-dashboard" id="menuFormCard">
              <div class="card-header" id="menuFormHeader">Tambah Menu Baru</div>
              <div class="card-body">
                <form action="" method="POST" enctype="multipart/form-data" id="menuForm">
                  <input type="hidden" name="action" id="menuFormAction" value="add_menu">
                  <input type="hidden" name="menu_id" id="menuFormId" value="">
                  
                  <div class="form-group">
                    <label class="form-label">Nama Menu</label>
                    <input type="text" name="name" id="menuFormName" class="form-control" required>
                  </div>
                  
                  <div class="form-group">
                    <label class="form-label">Kategori</label>
                    <select name="category" id="menuFormCategory" class="form-control" required>
                      <option value="signature">Signature</option>
                      <option value="coffee">Coffee</option>
                      <option value="manual_brew">Manual Brew</option>
                      <option value="non_coffee">Non Coffee</option>
                      <option value="mocktail">Mocktail</option>
                      <option value="food">Food</option>
                      <option value="snack">Snack</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label class="form-label">Harga Satuan (Rp) — untuk Signature/Mocktail/Food/Snack</label>
                    <input type="number" name="price" id="menuFormPrice" class="form-control" value="0">
                  </div>

                  <div class="form-group">
                    <label class="form-label">Harga Hot (Rp) — untuk Coffee/Manual Brew/Non Coffee</label>
                    <input type="number" name="hot_price" id="menuFormHotPrice" class="form-control" value="0">
                  </div>

                  <div class="form-group">
                    <label class="form-label">Harga Ice (Rp)</label>
                    <input type="number" name="ice_price" id="menuFormIcePrice" class="form-control" value="0">
                  </div>

                  <div class="form-group">
                    <label class="form-label">Deskripsi (Opsional)</label>
                    <textarea name="description" id="menuFormDescription" class="form-control" rows="2"></textarea>
                  </div>

                  <div class="form-group">
                    <label class="form-label">Varian (Opsional, misal: kuah / goreng)</label>
                    <input type="text" name="variant" id="menuFormVariant" class="form-control">
                  </div>

                  <div class="form-group">
                    <label class="form-label">Foto Menu (Opsional)</label>
                    <input type="file" name="menu_image" class="form-file">
                    <small class="form-help" id="menuImageHelp">Disarankan ukuran square 1:1.</small>
                  </div>

                  <div class="form-buttons-row">
                    <button type="submit" class="btn btn-save" style="flex-grow:1;"><i class="fa-solid fa-paper-plane"></i> Simpan Menu</button>
                    <button type="button" class="btn btn-cancel" id="btnResetMenu" style="display:none;">Batal</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- List Table -->
          <div class="table-panel-side">
            <div class="card-dashboard">
              <div class="card-header">Daftar Sajian (<?= $total_menu ?> item)</div>
              <div class="table-responsive">
                <table class="table">
                  <thead>
                    <tr>
                      <th>Nama</th>
                      <th>Kategori</th>
                      <th>Hot</th>
                      <th>Ice</th>
                      <th>Harga</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($data['menu'])): ?>
                      <tr>
                        <td colspan="6" style="text-align: center;">Belum ada menu, tambahkan menu baru di samping.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($data['menu'] as $item): ?>
                        <tr>
                          <td>
                            <strong><?= e($item['name']) ?></strong>
                            <?php if (!empty($item['description'])): ?>
                              <p style="font-size: 0.75rem; color: #888; margin-top: 4px;"><?= e($item['description']) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($item['variant'])): ?>
                              <p style="font-size: 0.7rem; color: var(--accent-orange); margin-top: 2px;">Varian: <?= e($item['variant']) ?></p>
                            <?php endif; ?>
                          </td>
                          <td><span class="label-cat cat-<?= e($item['category']) ?>"><?= ucfirst(str_replace('_', ' ', $item['category'])) ?></span></td>
                          <td><?= ($item['hot_price'] ?? 0) > 0 ? number_format($item['hot_price'], 0, ',', '.') : '-' ?></td>
                          <td><?= ($item['ice_price'] ?? 0) > 0 ? number_format($item['ice_price'], 0, ',', '.') : '-' ?></td>
                          <td><?= ($item['price'] ?? 0) > 0 ? number_format($item['price'], 0, ',', '.') : '-' ?></td>
                          <td>
                            <div class="table-actions">
                              <button class="btn-action-edit" onclick="populateMenuEdit('<?= e($item['id']) ?>', '<?= e($item['name']) ?>', '<?= e($item['category']) ?>', <?= $item['price'] ?? 0 ?>, '<?= e(addslashes($item['description'] ?? '')) ?>', <?= $item['hot_price'] ?? 0 ?>, <?= $item['ice_price'] ?? 0 ?>, '<?= e(addslashes($item['variant'] ?? '')) ?>')">
                                <i class="fa-solid fa-pen-to-square"></i>
                              </button>
                              <form action="" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus menu <?= e($item['name']) ?>?');" style="display: inline;">
                                <input type="hidden" name="action" value="delete_menu">
                                <input type="hidden" name="menu_id" value="<?= e($item['id']) ?>">
                                <button type="submit" class="btn-action-delete">
                                  <i class="fa-solid fa-trash"></i>
                                </button>
                              </form>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 5. TAB GALLERY -->
      <div class="tab-content" id="tab-gallery">
        <h1 class="page-title">Kelola Galeri Foto</h1>
        <p class="page-subtitle">Unggah foto suasana cafe, minuman, area kumpul, dan hapus gambar lama.</p>
        
        <div class="dashboard-split">
          <!-- Upload Box -->
          <div class="form-panel-side">
            <div class="card-dashboard">
              <div class="card-header">Unggah Foto Baru</div>
              <div class="card-body">
                <form action="" method="POST" enctype="multipart/form-data">
                  <input type="hidden" name="action" value="add_gallery">
                  
                  <div class="form-group">
                    <label class="form-label">Kategori Foto</label>
                    <select name="category" class="form-control" required>
                      <option value="atmosphere">Suasana Cafe</option>
                      <option value="drinks">Minuman</option>
                      <option value="food">Snack & Makanan</option>
                      <option value="front">Storefront</option>
                    </select>
                  </div>

                  <div class="form-group">
                    <label class="form-label">Keterangan Foto (Caption)</label>
                    <input type="text" name="caption" class="form-control" placeholder="Tuliskan teks singkat tentang foto..." required>
                  </div>

                  <div class="form-group">
                    <label class="form-label">Pilih Berkas Foto</label>
                    <input type="file" name="gallery_image" class="form-file" required>
                    <small class="form-help">Format: JPG, PNG, WEBP. Maksimal 2MB.</small>
                  </div>

                  <button type="submit" class="btn btn-save" style="width: 100%;"><i class="fa-solid fa-upload"></i> Unggah Foto</button>
                </form>
              </div>
            </div>
          </div>

          <!-- Gallery List Grid -->
          <div class="table-panel-side">
            <div class="card-dashboard">
              <div class="card-header">Daftar Foto Galeri</div>
              <div class="admin-gallery-grid">
                <?php if (empty($data['gallery'])): ?>
                  <p style="grid-column: 1/-1; text-align: center; padding: 20px; color: #888;">Belum ada foto dalam galeri.</p>
                <?php else: ?>
                  <?php foreach ($data['gallery'] as $img): ?>
                    <div class="gallery-item-card">
                      <div class="gallery-item-img">
                        <img src="../<?= e($img['image']) ?>" alt="Gallery item">
                      </div>
                      <div class="gallery-item-info">
                        <span class="label-cat cat-<?= e($img['category']) ?>"><?= ucfirst(e($img['category'])) ?></span>
                        <p><?= e($img['caption']) ?></p>
                        <form action="" method="POST" onsubmit="return confirm('Hapus foto ini dari galeri?');" style="margin-top: 10px;">
                          <input type="hidden" name="action" value="delete_gallery">
                          <input type="hidden" name="gallery_id" value="<?= e($img['id']) ?>">
                          <button type="submit" class="btn btn-danger-sm"><i class="fa-solid fa-trash"></i> Hapus</button>
                        </form>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 6. TAB TESTIMONIALS -->
      <div class="tab-content" id="tab-testimonials">
        <h1 class="page-title">Kelola Ulasan Testimoni</h1>
        <p class="page-subtitle">Atur testimoni pelanggan yang muncul di bagian slider halaman depan.</p>
        
        <div class="dashboard-split">
          <!-- Add/Edit form -->
          <div class="form-panel-side">
            <div class="card-dashboard" id="tFormCard">
              <div class="card-header" id="tFormHeader">Tambah Testimoni</div>
              <div class="card-body">
                <form action="" method="POST" id="tForm">
                  <input type="hidden" name="action" id="tFormAction" value="add_testimonial">
                  <input type="hidden" name="testimonial_id" id="tFormId" value="">
                  
                  <div class="form-group">
                    <label class="form-label">Nama Pengulas</label>
                    <input type="text" name="name" id="tFormName" class="form-control" required>
                  </div>

                  <div class="form-group">
                    <label class="form-label">Peran / Jabatan</label>
                    <input type="text" name="role" id="tFormRole" class="form-control" placeholder="Contoh: Pelanggan Setia, Food Blogger" required>
                  </div>

                  <div class="form-group">
                    <label class="form-label">Isi Ulasan</label>
                    <textarea name="text" id="tFormText" class="form-control" rows="4" placeholder="Ulasan pelanggan..." required></textarea>
                  </div>

                  <div class="form-buttons-row">
                    <button type="submit" class="btn btn-save" style="flex-grow:1;"><i class="fa-solid fa-save"></i> Simpan Testimoni</button>
                    <button type="button" class="btn btn-cancel" id="btnResetT" style="display:none;">Batal</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- List -->
          <div class="table-panel-side">
            <div class="card-dashboard">
              <div class="card-header">Daftar Testimoni</div>
              <div class="table-responsive">
                <table class="table">
                  <thead>
                    <tr>
                      <th>Nama & Peran</th>
                      <th>Isi Testimoni</th>
                      <th>Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php if (empty($data['testimonials'])): ?>
                      <tr>
                        <td colspan="3" style="text-align: center;">Belum ada testimoni. Tambahkan testimoni di samping.</td>
                      </tr>
                    <?php else: ?>
                      <?php foreach ($data['testimonials'] as $t): ?>
                        <tr>
                          <td>
                            <strong><?= e($t['name']) ?></strong>
                            <p style="font-size: 0.75rem; color: var(--accent-orange);"><?= e($t['role'] ?? 'Pelanggan') ?></p>
                          </td>
                          <td style="font-style: italic; max-width: 250px;">
                            "<?= e($t['text']) ?>"
                          </td>
                          <td>
                            <div class="table-actions">
                              <button class="btn-action-edit" onclick="populateTestimonialEdit('<?= e($t['id']) ?>', '<?= e($t['name']) ?>', '<?= e($t['role'] ?? 'Pelanggan') ?>', '<?= e(addslashes($t['text'])) ?>')">
                                <i class="fa-solid fa-pen-to-square"></i>
                              </button>
                              <form action="" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin menghapus testimoni ini?');" style="display: inline;">
                                <input type="hidden" name="action" value="delete_testimonial">
                                <input type="hidden" name="testimonial_id" value="<?= e($t['id']) ?>">
                                <button type="submit" class="btn-action-delete">
                                  <i class="fa-solid fa-trash"></i>
                                </button>
                              </form>
                            </div>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    <?php endif; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 7. TAB MESSAGES (INBOX) -->
      <div class="tab-content" id="tab-messages">
        <h1 class="page-title">Pesan Masuk (Inbox)</h1>
        <p class="page-subtitle">Daftar masukan, pertanyaan, dan saran dari pengunjung lewat formulir website.</p>
        
        <div class="card-dashboard">
          <div class="card-header">Kotak Masuk Terbaru</div>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th style="width: 120px;">Tanggal</th>
                  <th style="width: 180px;">Pengirim</th>
                  <th>Isi Pesan</th>
                  <th style="width: 80px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($messages)): ?>
                  <tr>
                    <td colspan="4" style="text-align: center; padding: 30px;">Belum ada pesan masuk di kotak pesan Anda.</td>
                  </tr>
                <?php else: ?>
                  <?php foreach ($messages as $msg): ?>
                    <tr>
                      <td style="font-size: 0.85rem; color: #888; font-weight: 500;"><?= e($msg['date']) ?></td>
                      <td>
                        <strong><?= e($msg['name']) ?></strong>
                        <p style="font-size: 0.75rem; color: #bbb; margin-top: 2px;"><i class="fa-solid fa-envelope"></i> <?= e($msg['email']) ?></p>
                      </td>
                      <td style="white-space: normal; line-height: 1.5; font-size: 0.92rem; color: var(--text-light);">
                        <?= nl2br(e($msg['message'])) ?>
                      </td>
                      <td>
                        <form action="" method="POST" onsubmit="return confirm('Hapus pesan ini secara permanen?');">
                          <input type="hidden" name="action" value="delete_message">
                          <input type="hidden" name="message_id" value="<?= e($msg['id']) ?>">
                          <button type="submit" class="btn-action-delete" aria-label="Hapus Pesan">
                            <i class="fa-solid fa-trash"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- 8. TAB SECURITY -->
      <div class="tab-content" id="tab-security">
        <h1 class="page-title">Keamanan Akun</h1>
        <p class="page-subtitle">Ganti username administrator atau ubah sandi login Anda.</p>
        
        <div class="card-dashboard" style="max-width: 600px;">
          <div class="card-header">Ubah Kredensial Login</div>
          <div class="card-body">
            <form action="" method="POST" autocomplete="off">
              <input type="hidden" name="action" value="save_security">
              
              <div class="form-group">
                <label class="form-label">Username Saat Ini / Baru</label>
                <input type="text" name="username" class="form-control" value="<?= e($data['admin']['username'] ?? 'admin') ?>" required>
              </div>

              <div class="form-group">
                <label class="form-label">Password Baru (Isi jika ingin diganti)</label>
                <input type="password" name="new_pass" class="form-control" placeholder="Masukkan password baru">
              </div>

              <div class="form-group">
                <label class="form-label">Konfirmasi Password Baru</label>
                <input type="password" name="confirm_pass" class="form-control" placeholder="Konfirmasi password baru">
              </div>
              
              <hr style="border: 0; height: 1px; background: rgba(250,249,246,0.1); margin: 30px 0 20px 0;">

              <div class="form-group">
                <label class="form-label" style="color: #e74c3c;"><i class="fa-solid fa-shield-halved"></i> Masukkan Password Saat Ini (Verifikasi)</label>
                <input type="password" name="current_pass" class="form-control" placeholder="Sandi saat ini untuk konfirmasi" required>
                <small class="form-help">Wajib diisi untuk memverifikasi keabsahan perubahan data.</small>
              </div>

              <button type="submit" class="btn btn-save" style="margin-top: 10px;"><i class="fa-solid fa-key"></i> Perbarui Kredensial</button>
            </form>
          </div>
        </div>
      </div>

    </div>
  </div>

  <!-- Admin JS helpers -->
  <script src="../assets/js/admin.js"></script>
</body>
</html>
