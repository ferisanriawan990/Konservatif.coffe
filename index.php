<?php
// Set timezone
date_default_timezone_set('Asia/Jakarta');

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

// Handle contact form submission (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_contact') {
    header('Content-Type: application/json');
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $message = trim($_POST['message'] ?? '');
    
    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'Semua kolom wajib diisi.']);
        exit;
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Format email tidak valid.']);
        exit;
    }
    
    $messages_file = __DIR__ . '/data/messages.json';
    $messages = [];
    if (file_exists($messages_file)) {
        $messages = json_decode(file_get_contents($messages_file), true);
        if (!is_array($messages)) $messages = [];
    }
    
    $new_message = [
        'id' => uniqid('msg_'),
        'name' => $name,
        'email' => $email,
        'message' => $message,
        'date' => date('d M Y, H:i')
    ];
    
    // Only write to file if not on Vercel
    if (!is_on_vercel()) {
        array_unshift($messages, $new_message); // Newest messages first
        file_put_contents($messages_file, json_encode($messages, JSON_PRETTY_PRINT));
    }
    
    echo json_encode(['status' => 'success', 'message' => 'Terima kasih! Pesan Anda telah berhasil dikirim.']);
    exit;
}

// Load configurations
$settings_file = __DIR__ . '/data/settings.json';
$data = [];
if (file_exists($settings_file)) {
    $data = json_decode(file_get_contents($settings_file), true);
}

// Helper to escape output
function e($text) {
    return htmlspecialchars($text ?? '', ENT_QUOTES, 'UTF-8');
}

// Fallback configuration values
$site_name = $data['general']['site_name'] ?? 'konservatif.coffee';
$hero_title = $data['general']['title'] ?? 'konservatif.coffee';
$hero_subtitle = $data['general']['subtitle'] ?? 'Tempat ngopi santai dengan suasana nyaman di Cikupa';
$hero_desc = $data['general']['description'] ?? 'Tempat ngopi, nongkrong, kerja santai, dan kumpul teman.';
$hero_image = $data['general']['hero_image'] ?? 'uploads/hero_cozy_cafe.png';
$hero_badge = $data['general']['hero_badge'] ?? 'Premium Coffee Experience';
$hero_bg_type = $data['general']['hero_bg_type'] ?? 'image';
$hero_video = $data['general']['hero_video'] ?? '';
$hero_gif = $data['general']['hero_gif'] ?? '';
$hero_cta1_text = $data['general']['hero_cta1_text'] ?? 'Lihat Menu';
$hero_cta1_link = $data['general']['hero_cta1_link'] ?? '#menu';
$hero_cta2_text = $data['general']['hero_cta2_text'] ?? 'Hubungi WhatsApp';
$hero_cta2_link = $data['general']['hero_cta2_link'] ?? '';
$hero_opening_hours = $data['general']['hero_opening_hours'] ?? 'Open Daily • 08.00 - 23.00';
$hero_highlight = $data['general']['hero_highlight'] ?? 'Coffee • Cozy Place • Free WiFi';
$about_text = $data['general']['about_text'] ?? 'konservatif.coffee adalah coffee shop lokal dengan suasana hangat, santai, dan nyaman.';
$about_image = $data['general']['about_image'] ?? 'uploads/hero_cozy_cafe.png';

$seo_title = $data['seo']['title'] ?? 'konservatif.coffee - Coffee Shop & Eatery';
$seo_description = $data['seo']['description'] ?? 'Company profile konservatif.coffee';

$contact = $data['contact'] ?? [];
$address = $contact['address'] ?? 'Cikupa, Tangerang';
$opening_hours = $contact['opening_hours'] ?? 'Setiap Hari: 10:00 - 22:00';
$maps_link = $contact['maps_link'] ?? '#';
$maps_embed = $contact['maps_embed'] ?? '';
$whatsapp_number = $contact['whatsapp_number'] ?? '';
$instagram_link = $contact['instagram_link'] ?? '#';

$music = $data['music'] ?? [
    'enabled' => 'no',
    'title' => 'Warm Coffee Shop Ambient',
    'file_url' => '',
    'url' => '',
    'volume' => '50',
    'loop' => 'yes',
    'show_button' => 'yes',
    'button_position' => 'bottom-left'
];

if (empty($hero_cta2_link) && !empty($whatsapp_number)) {
    $hero_cta2_link = "https://wa.me/" . $whatsapp_number . "?text=Halo%20konservatif.coffee,%20saya%20ingin%20tanya-tanya%20info%20menu/reservasi";
}

$menu_items = $data['menu'] ?? [];
$gallery_items = $data['gallery'] ?? [];
$testimonials = $data['testimonials'] ?? [];

// Group menus by category
$cat_keys = ['signature','coffee','manual_brew','non_coffee','mocktail','food','snack'];
$categories = array_fill_keys($cat_keys, []);
foreach ($menu_items as $item) {
    $cat = $item['category'] ?? '';
    if (array_key_exists($cat, $categories)) {
        $categories[$cat][] = $item;
    }
}

// Helper: format price as "25k"
function format_k($price) {
    if (empty($price) || $price <= 0) return '-';
    return number_format($price / 1000, 0) . 'k';
}

// Category display config
$cat_labels = [
    'signature' => ['label' => 'Signature', 'icon' => 'fa-star', 'type' => 'single'],
    'coffee' => ['label' => 'Coffee', 'icon' => 'fa-mug-hot', 'type' => 'hot_ice'],
    'manual_brew' => ['label' => 'Manual Brew', 'icon' => 'fa-fire-burner', 'type' => 'hot_ice'],
    'non_coffee' => ['label' => 'Non Coffee', 'icon' => 'fa-glass-water', 'type' => 'hot_ice'],
    'mocktail' => ['label' => 'Mocktail', 'icon' => 'fa-champagne-glasses', 'type' => 'single'],
    'food' => ['label' => 'Food', 'icon' => 'fa-utensils', 'type' => 'food'],
    'snack' => ['label' => 'Snack', 'icon' => 'fa-cookie-bite', 'type' => 'food'],
];

$wa_text = urlencode("Halo kak, saya mau tanya menu di Konservatif. Cikupa.");
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= e($seo_description) ?>">
  <title><?= e($seo_title) ?></title>
  
  <!-- FontAwesome Icons for premium design details -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  
  <!-- Custom Stylesheets -->
  <link rel="stylesheet" href="assets/css/style.css">
  
  <!-- Favicon -->
  <link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><text y=%22.9em%22 font-size=%2290%22>☕</text></svg>">
</head>
<body>

  <!-- ========== PRELOADER ========== -->
  <div class="loader" id="loader">
    <div class="loader-logo">konservatif.<span>coffee</span></div>
    <div class="loader-bar"></div>
  </div>

  <!-- ========== NAVBAR ========== -->
  <nav class="navbar" id="navbar">
    <div class="nav-container">
      <a href="#" class="logo" id="nav-logo">
        <i class="fa-solid fa-mug-hot"></i> konservatif.<span>coffee</span>
      </a>
      <ul class="nav-menu" id="nav-menu">
        <li><a href="#" class="nav-link active">Beranda</a></li>
        <li><a href="#tentang-kami" class="nav-link">Tentang Kami</a></li>
        <li><a href="#menu" class="nav-link">Menu</a></li>
        <li><a href="#galeri" class="nav-link">Galeri</a></li>
        <li><a href="#lokasi" class="nav-link">Lokasi</a></li>
        <li><a href="#kontak" class="nav-link">Kontak</a></li>
        <li class="nav-admin"><a href="admin/login.php" class="nav-link"><i class="fa-solid fa-user-lock"></i> Admin</a></li>
      </ul>
      <div class="nav-toggle" id="nav-toggle">
        <span></span>
        <span></span>
        <span></span>
      </div>
    </div>
    <div class="nav-overlay" id="nav-overlay"></div>
  </nav>

  <!-- ========== HERO SECTION ========== -->
  <header class="hero" id="home">
    <?php if ($hero_bg_type === 'video' && !empty($hero_video)): ?>
      <video class="hero-video" autoplay muted loop playsinline poster="<?= e($hero_image) ?>">
        <source src="<?= e($hero_video) ?>" type="video/mp4">
        <!-- Fallback background image if video fails to play -->
        <div class="hero-bg" style="background-image: url('<?= e($hero_image) ?>');"></div>
      </video>
    <?php elseif ($hero_bg_type === 'gif' && !empty($hero_gif)): ?>
      <div class="hero-bg" style="background-image: url('<?= e($hero_gif) ?>');"></div>
    <?php else: ?>
      <div class="hero-bg" style="background-image: url('<?= e($hero_image) ?>');"></div>
    <?php endif; ?>
    <div class="hero-bg-overlay"></div>
    <div class="hero-bg-glow"></div>

    <!-- Floating Coffee Beans -->
    <div class="hero-particles">
      <div class="coffee-bean bean-1"></div>
      <div class="coffee-bean bean-2"></div>
      <div class="coffee-bean bean-3"></div>
      <div class="coffee-bean bean-4"></div>
      <div class="coffee-bean bean-5"></div>
      <div class="coffee-bean bean-6"></div>
    </div>

    <div class="container">
      <div class="hero-grid">
        <div class="hero-content">
          <div class="hero-badge">
            <i class="fa-solid fa-medal"></i> <?= e($hero_badge) ?>
          </div>
          <span class="hero-subtitle"><?= e($hero_subtitle) ?></span>
          <h1 class="hero-title text-gradient"><?= e($hero_title) ?></h1>
          <p class="hero-desc"><?= e($hero_desc) ?></p>
          
          <div class="hero-buttons">
            <a href="<?= e($hero_cta1_link) ?>" class="btn btn-primary"><i class="fa-solid fa-mug-hot"></i> <?= e($hero_cta1_text) ?></a>
            <a href="<?= e($hero_cta2_link) ?>" target="_blank" rel="noopener" class="btn btn-outline"><i class="fa-brands fa-whatsapp"></i> <?= e($hero_cta2_text) ?></a>
            <a href="<?= e($maps_link) ?>" target="_blank" rel="noopener" class="btn btn-secondary"><i class="fa-solid fa-compass"></i> Lokasi Cafe</a>
          </div>

          <!-- Open hours and highlight badge row under the buttons -->
          <div class="hero-info-footer">
            <span class="hero-info-item"><i class="fa-solid fa-clock"></i> <?= e($hero_opening_hours) ?></span>
            <span class="hero-info-divider">•</span>
            <span class="hero-info-item"><i class="fa-solid fa-circle-check"></i> <?= e($hero_highlight) ?></span>
          </div>
          
          <!-- Statistics Counter -->
          <div class="hero-stats">
            <div class="hero-stat">
              <div class="hero-stat-number" data-count="<?= count($menu_items) ?>" data-suffix="+">0</div>
              <div class="hero-stat-label">Menu Pilihan</div>
            </div>
            <div class="hero-stat">
              <div class="hero-stat-number" data-count="120" data-suffix="K+">0</div>
              <div class="hero-stat-label">Pelanggan Puas</div>
            </div>
            <div class="hero-stat">
              <div class="hero-stat-number" data-count="4" data-suffix=".8">0</div>
              <div class="hero-stat-label">Rating Google</div>
            </div>
          </div>
        </div>

        <div class="hero-visual">
          <div class="hero-cup-wrapper">
            <div class="hero-cup-glow"></div>
            <img src="assets/images/hero-coffee.png" alt="Premium Coffee Cup" class="hero-cup-image">
            <div class="hero-steam steam-container">
              <div class="steam-line"></div>
              <div class="steam-line"></div>
              <div class="steam-line"></div>
            </div>
            
            <!-- Best Seller floating card -->
            <div class="hero-floating-card">
              <div class="card-icon"><i class="fa-solid fa-star"></i></div>
              <div class="card-content">
                <span class="card-tag">Best Seller</span>
                <h4 class="card-title">Kopi Susu Gula Aren</h4>
                <p class="card-price">Rp 24.000</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </header>

  <!-- ========== TENTANG KAMI SECTION ========== -->
  <section class="section section-bg-alt" id="tentang-kami">
    <div class="container">
      <div class="about-grid">
        <div class="about-content reveal reveal-right">
          <span class="section-tag">Kisah Kami</span>
          <h2 class="section-title text-gradient">Tentang Kami</h2>
          <p class="about-desc"><?= nl2br(e($about_text)) ?></p>
          
          <div class="about-highlights">
            <div class="about-highlight-card">
              <div class="icon"><i class="fa-solid fa-mug-hot"></i></div>
              <h4>Fresh Roast</h4>
              <p>Biji kopi nusantara pilihan diseduh segar setiap hari.</p>
            </div>
            <div class="about-highlight-card">
              <div class="icon"><i class="fa-solid fa-couch"></i></div>
              <h4>Cozy Space</h4>
              <p>Suasana modern vintage yang hangat, pas untuk santai & WFC.</p>
            </div>
          </div>
          
          <a href="#kontak" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Hubungi Kami</a>
        </div>
        
        <div class="about-image-wrapper reveal reveal-left">
          <img src="<?= e($about_image) ?>" alt="Suasana konservatif.coffee" class="about-image" loading="lazy">
          <div class="about-image-frame"></div>
          <div class="about-badge">
            <h4>konservatif.coffee</h4>
            <p>Modern Vintage Aesthetic</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- ========== MENU SECTION ========== -->
  <section class="section" id="menu">
    <div class="container">
      <div class="section-header reveal reveal-up">
        <span class="section-tag">Sajian Terbaik Kami</span>
        <h2 class="section-title text-gradient">Eksplorasi Menu</h2>
        <p class="section-subtitle">Nikmati pilihan sajian rasa autentik yang diseduh dan dimasak dengan penuh cinta.</p>
      </div>

      <!-- Menu Category Tabs -->
      <div class="menu-tabs reveal reveal-up">
        <?php $first = true; foreach ($cat_labels as $key => $cfg): ?>
          <button class="tab-btn<?= $first ? ' active' : '' ?>" data-target="menu-<?= $key ?>">
            <i class="fa-solid <?= $cfg['icon'] ?>"></i> <?= $cfg['label'] ?>
          </button>
        <?php $first = false; endforeach; ?>
      </div>

      <!-- Tab Contents -->
      <div class="menu-content-wrapper reveal reveal-up">
        <?php $first = true; foreach ($cat_labels as $key => $cfg):
          $items = $categories[$key];
        ?>
        <div class="menu-panel<?= $first ? ' active' : '' ?>" id="menu-<?= $key ?>">
          
          <?php if ($cfg['type'] === 'hot_ice'): ?>
            <!-- Drink Grid with Hot & Ice Options -->
            <div class="menu-cards-grid">
              <?php if (empty($items)): ?>
                <p class="no-menu">Belum ada menu tersedia.</p>
              <?php else: ?>
                <?php foreach ($items as $item): ?>
                  <div class="menu-premium-card tilt-card">
                    <div class="menu-card-body">
                      <div class="menu-card-header">
                        <h3 class="menu-card-title"><?= e($item['name']) ?></h3>
                        <div class="menu-card-price-pills">
                          <?php if (!empty($item['hot_price']) && $item['hot_price'] > 0): ?>
                            <span class="price-pill hot"><i class="fa-solid fa-mug-hot"></i> <?= format_k($item['hot_price']) ?></span>
                          <?php endif; ?>
                          <?php if (!empty($item['ice_price']) && $item['ice_price'] > 0): ?>
                            <span class="price-pill ice"><i class="fa-solid fa-snowflake"></i> <?= format_k($item['ice_price']) ?></span>
                          <?php endif; ?>
                        </div>
                      </div>
                      <?php if (!empty($item['description'])): ?>
                        <p class="menu-card-description"><?= e($item['description']) ?></p>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

          <?php elseif ($cfg['type'] === 'single'): ?>
            <!-- Single Price Grid (Signature / Mocktails) -->
            <div class="menu-cards-grid">
              <?php if (empty($items)): ?>
                <p class="no-menu">Belum ada menu tersedia.</p>
              <?php else: ?>
                <?php foreach ($items as $item): ?>
                  <div class="menu-premium-card tilt-card highlighted">
                    <div class="menu-card-badge">★ Favorite</div>
                    <div class="menu-card-body">
                      <div class="menu-card-header">
                        <h3 class="menu-card-title"><?= e($item['name']) ?></h3>
                        <span class="price-pill single-price"><?= format_k($item['price'] ?? 0) ?></span>
                      </div>
                      <?php if (!empty($item['description'])): ?>
                        <p class="menu-card-description"><?= e($item['description']) ?></p>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>

          <?php else: ?>
            <!-- Food / Snack Grid -->
            <div class="menu-cards-grid food-grid">
              <?php if (empty($items)): ?>
                <p class="no-menu">Belum ada menu tersedia.</p>
              <?php else: ?>
                <?php foreach ($items as $item): ?>
                  <div class="menu-premium-card tilt-card food-card">
                    <div class="menu-card-body">
                      <div class="menu-card-header">
                        <h3 class="menu-card-title"><?= e($item['name']) ?></h3>
                        <span class="price-pill food-price"><?= format_k($item['price'] ?? 0) ?></span>
                      </div>
                      <?php if (!empty($item['description'])): ?>
                        <p class="menu-card-description"><?= e($item['description']) ?></p>
                      <?php endif; ?>
                      <?php if (!empty($item['variant'])): ?>
                        <span class="menu-card-variant">Varian: <?= e($item['variant']) ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <!-- WhatsApp Order Button -->
          <div class="menu-wa-btn-wrap">
            <a href="https://wa.me/<?= e($whatsapp_number) ?>?text=<?= $wa_text ?>" target="_blank" rel="noopener" class="btn btn-whatsapp">
              <i class="fa-brands fa-whatsapp"></i> Pesan via WhatsApp
            </a>
          </div>
        </div>
        <?php $first = false; endforeach; ?>
      </div>
    </div>
  </section>

  <!-- ========== FEATURE SECTION ========== -->
  <section class="section section-bg-alt" id="keunggulan">
    <div class="container">
      <div class="section-header reveal reveal-up">
        <span class="section-tag">Kenapa Harus Kami</span>
        <h2 class="section-title text-gradient">Fitur & Keunggulan</h2>
        <p class="section-subtitle">Komitmen kami untuk memberikan pengalaman bersantai terbaik bagi Anda.</p>
      </div>
      
      <div class="features-grid">
        <div class="feature-card reveal reveal-up">
          <div class="feature-icon-box"><i class="fa-solid fa-couch"></i></div>
          <h3>Suasana Nyaman</h3>
          <p>Interior modern vintage yang hangat, tempat duduk santai, dan dekorasi estetik yang pas untuk melepas penat.</p>
        </div>

        <div class="feature-card reveal reveal-up">
          <div class="feature-icon-box"><i class="fa-solid fa-laptop"></i></div>
          <h3>Cocok untuk WFC</h3>
          <p>Dilengkapi koneksi Wi-Fi kencang, colokan melimpah, dan suasana tenang yang meningkatkan produktivitas Anda.</p>
        </div>

        <div class="feature-card reveal reveal-up">
          <div class="feature-icon-box"><i class="fa-solid fa-seedling"></i></div>
          <h3>Menu Kopi Pilihan</h3>
          <p>Kombinasi seduhan biji kopi single-origin lokal aromatik dan minuman manis segar non-kopi yang memikat selera.</p>
        </div>

        <div class="feature-card reveal reveal-up">
          <div class="feature-icon-box"><i class="fa-solid fa-map-pin"></i></div>
          <h3>Lokasi Strategis</h3>
          <p>Terletak di Cikupa dengan akses rute sangat mudah serta area parkir yang aman dan memadai.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- ========== PROMO SECTION ========== -->
  <section class="section" id="promo">
    <div class="container">
      <div class="section-header reveal reveal-up">
        <span class="section-tag">Spesial Untuk Anda</span>
        <h2 class="section-title text-gradient">Promo Menarik</h2>
        <p class="section-subtitle">Jangan lewatkan kesempatan promo seru hanya di konservatif.coffee!</p>
      </div>

      <div class="promo-grid">
        <div class="promo-card reveal reveal-up">
          <div class="promo-card-deco"></div>
          <div class="promo-card-icon"><i class="fa-solid fa-percent"></i></div>
          <h3>Diskon Hari Jumat</h3>
          <p>Dapatkan penawaran potongan harga spesial 15% khusus untuk semua menu signature base pada hari Jumat!</p>
          <span class="badge badge-promo">Setiap Hari Jumat</span>
          <div class="promo-countdown" id="promo-countdown">
            <!-- Countdown timer (JS rendered) -->
          </div>
        </div>

        <div class="promo-card reveal reveal-up">
          <div class="promo-card-deco"></div>
          <div class="promo-card-icon"><i class="fa-solid fa-graduation-cap"></i></div>
          <h3>Promo Pelajar / Mahasiswa</h3>
          <p>Tunjukkan kartu identitas pelajar/mahasiswa Anda dan dapatkan potongan harga langsung 10% untuk menu minuman.</p>
          <span class="badge badge-promo">Berlaku Setiap Hari</span>
        </div>
      </div>
    </div>
  </section>

  <!-- ========== GALERI SECTION ========== -->
  <section class="section section-bg-alt" id="galeri">
    <div class="container">
      <div class="section-header reveal reveal-up">
        <span class="section-tag">Sudut Estetik Kami</span>
        <h2 class="section-title text-gradient">Galeri Foto</h2>
        <p class="section-subtitle">Intip kehangatan interior, kelezatan sajian, dan momen seru di konservatif.coffee.</p>
      </div>

      <!-- Filter Buttons -->
      <div class="gallery-filter reveal reveal-up">
        <button class="filter-btn active" data-filter="all">Semua</button>
        <button class="filter-btn" data-filter="atmosphere">Suasana Cafe</button>
        <button class="filter-btn" data-filter="drinks">Minuman</button>
        <button class="filter-btn" data-filter="food">Makanan & Snack</button>
        <button class="filter-btn" data-filter="front">Tampak Depan</button>
      </div>

      <!-- Gallery Grid -->
      <div class="gallery-grid reveal reveal-up">
        <?php if (empty($gallery_items)): ?>
          <p class="no-gallery">Belum ada foto galeri tersedia.</p>
        <?php else: ?>
          <?php foreach ($gallery_items as $item): ?>
            <div class="gallery-item" data-category="<?= e($item['category']) ?>" data-src="<?= e($item['image']) ?>" data-caption="<?= e($item['caption']) ?>">
              <img src="<?= e($item['image']) ?>" alt="<?= e($item['caption']) ?>" loading="lazy">
              <div class="gallery-overlay">
                <div class="gallery-icon"><i class="fa-solid fa-expand"></i></div>
                <p class="gallery-item-title"><?= e($item['caption']) ?></p>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- Lightbox Modal -->
  <div class="lightbox" id="lightbox">
    <div class="lightbox-content">
      <button class="lightbox-close" id="lightbox-close">&times;</button>
      <img src="" alt="" class="lightbox-img" id="lightbox-img">
      <p class="lightbox-caption" id="lightbox-caption"></p>
    </div>
  </div>

  <!-- ========== LOKASI SECTION ========== -->
  <section class="section" id="lokasi">
    <div class="container">
      <div class="location-grid">
        <div class="location-info reveal reveal-right">
          <span class="section-tag">Kunjungi Kami</span>
          <h2 class="loc-title text-gradient">Lokasi & Jam Buka</h2>
          
          <div class="info-item">
            <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
            <div class="info-text">
              <h4>Alamat Lengkap</h4>
              <p><?= e($address) ?></p>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon"><i class="fa-solid fa-clock"></i></div>
            <div class="info-text">
              <h4>Jam Operasional</h4>
              <p><?= e($opening_hours) ?></p>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon"><i class="fa-solid fa-circle-info"></i></div>
            <div class="info-text">
              <h4>Area Wilayah</h4>
              <p>Kecamatan Cikupa, Kabupaten Tangerang, Banten 15710</p>
            </div>
          </div>

          <div class="loc-buttons">
            <a href="<?= e($maps_link) ?>" target="_blank" rel="noopener" class="btn btn-primary"><i class="fa-solid fa-map-location-dot"></i> Rute Navigasi</a>
          </div>
        </div>

        <div class="map-wrapper reveal reveal-left">
          <?php if (!empty($maps_embed)): ?>
            <iframe 
              src="<?= $maps_embed ?>" 
              allowfullscreen="" 
              loading="lazy" 
              referrerpolicy="no-referrer-when-downgrade"
              title="konservatif.coffee Google Maps Location">
            </iframe>
          <?php else: ?>
            <div class="no-map">
              <p><i class="fa-solid fa-circle-exclamation"></i> Google Maps belum disematkan.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- ========== TESTIMONIAL SECTION ========== -->
  <section class="section section-bg-alt" id="testimoni">
    <div class="container">
      <div class="section-header reveal reveal-up">
        <span class="section-tag">Ulasan Pengunjung</span>
        <h2 class="section-title text-gradient">Apa Kata Mereka?</h2>
        <p class="section-subtitle">Pendapat jujur dari para penikmat setia kafe kami.</p>
      </div>

      <div class="testimonial-slider-wrapper reveal reveal-up">
        <div class="testimonial-slider">
          <div class="testimonial-track" id="testimonial-track">
            <?php if (empty($testimonials)): ?>
              <div class="testimonial-slide">
                <div class="quote-icon"><i class="fa-solid fa-quote-left"></i></div>
                <p class="testimonial-text">Belum ada testimoni tersedia saat ini.</p>
              </div>
            <?php else: ?>
              <?php foreach ($testimonials as $t): ?>
                <div class="testimonial-slide">
                  <div class="quote-icon"><i class="fa-solid fa-quote-left"></i></div>
                  <p class="testimonial-text">“<?= e($t['text']) ?>”</p>
                  <div class="testimonial-author">
                    <div class="testimonial-avatar"><?= substr(e($t['name']), 0, 1) ?></div>
                    <div class="author-details">
                      <p class="author-name"><?= e($t['name']) ?></p>
                      <p class="author-role"><?= e($t['role'] ?? 'Pelanggan') ?></p>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
        </div>
        
        <?php if (!empty($testimonials) && count($testimonials) > 1): ?>
          <div class="slider-dots" id="slider-dots">
            <?php foreach ($testimonials as $index => $t): ?>
              <button class="dot <?= $index === 0 ? 'active' : '' ?>" data-index="<?= $index ?>" aria-label="Slide <?= $index + 1 ?>"></button>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <!-- ========== KONTAK & RESERVASI SECTION ========== -->
  <section class="section" id="kontak">
    <div class="container">
      <div class="contact-grid">
        <div class="contact-quick reveal reveal-right">
          <span class="section-tag">Hubungi Kami</span>
          <h2 class="contact-title text-gradient">Ada Pertanyaan?</h2>
          <p>Pintu kami selalu terbuka untuk masukan, kerja sama event, atau obrolan santai lainnya. Jangan ragu menghubungi kami melalui media sosial atau WhatsApp!</p>
          
          <div class="social-buttons">
            <a href="https://wa.me/<?= e($whatsapp_number) ?>" target="_blank" rel="noopener" class="social-btn">
              <div class="social-icon wa"><i class="fa-brands fa-whatsapp"></i></div>
              <div class="social-btn-text">
                <span>Kirim Pesan WhatsApp</span>
                <p>Chat Admin Langsung</p>
              </div>
            </a>
            
            <a href="<?= e($instagram_link) ?>" target="_blank" rel="noopener" class="social-btn">
              <div class="social-icon ig"><i class="fa-brands fa-instagram"></i></div>
              <div class="social-btn-text">
                <span>Ikuti Instagram Kami</span>
                <p>@konservatif.coffee</p>
              </div>
            </a>

            <a href="<?= e($maps_link) ?>" target="_blank" rel="noopener" class="social-btn">
              <div class="social-icon maps"><i class="fa-solid fa-map-location-dot"></i></div>
              <div class="social-btn-text">
                <span>Buka Rute Navigasi</span>
                <p>Google Maps Lokasi</p>
              </div>
            </a>
          </div>
        </div>

        <div class="contact-form-container reveal reveal-left">
          <h3>Kirim Pesan Langsung</h3>
          <form id="contactForm" method="POST">
            <input type="hidden" name="action" value="submit_contact">
            
            <div class="form-group">
              <label for="name" class="form-label">Nama Lengkap</label>
              <input type="text" id="name" name="name" class="form-control" placeholder="Masukkan nama lengkap Anda" required>
            </div>

            <div class="form-group">
              <label for="email" class="form-label">Alamat Email</label>
              <input type="email" id="email" name="email" class="form-control" placeholder="Masukkan email aktif Anda" required>
            </div>

            <div class="form-group">
              <label for="message" class="form-label">Pesan / Masukan</label>
              <textarea id="message" name="message" class="form-control" placeholder="Tuliskan pesan atau saran Anda di sini..." required></textarea>
            </div>

            <button type="submit" class="btn btn-primary" id="submitBtn" style="width: 100%;">
              <i class="fa-solid fa-paper-plane"></i> Kirim Pesan
            </button>
            
            <div id="formStatus" class="form-status"></div>
          </form>
        </div>
      </div>
    </div>
  </section>

  <!-- Floating WhatsApp Action -->
  <a href="https://wa.me/<?= e($whatsapp_number) ?>" class="floating-whatsapp" target="_blank" rel="noopener" aria-label="Chat WhatsApp">
    <i class="fa-brands fa-whatsapp"></i>
  </a>

  <!-- ========== FOOTER ========== -->
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-info">
          <h3>konservatif.<span>coffee</span></h3>
          <p><?= e($hero_desc) ?></p>
        </div>
        
        <div class="footer-links">
          <h4>Peta Situs</h4>
          <ul>
            <li><a href="#">Beranda</a></li>
            <li><a href="#tentang-kami">Tentang Kami</a></li>
            <li><a href="#menu">Sajian Menu</a></li>
            <li><a href="#galeri">Galeri Foto</a></li>
            <li><a href="#lokasi">Lokasi Kami</a></li>
            <li><a href="#kontak">Hubungi Kontak</a></li>
          </ul>
        </div>

        <div class="footer-links">
          <h4>Temukan Kami</h4>
          <ul>
            <li><a href="<?= e($maps_link) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-compass"></i> Arah Google Maps</a></li>
            <li><a href="https://wa.me/<?= e($whatsapp_number) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Chat WhatsApp</a></li>
            <li><a href="<?= e($instagram_link) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-instagram"></i> Ikuti di Instagram</a></li>
          </ul>
        </div>
      </div>
      
      <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> konservatif.coffee. All Rights Reserved. Crafted with visual excellence.</p>
      </div>
    </div>
  </footer>

  <?php if (($music['enabled'] ?? 'no') === 'yes' && (!empty($music['file_url']) || !empty($music['url']))): ?>
  <!-- Elegant Background Music Player Component -->
  <div id="music-player-container" 
       class="music-player-container <?= e($music['button_position'] ?? 'bottom-left') ?>" 
       style="display: <?= ($music['show_button'] ?? 'yes') === 'yes' ? 'flex' : 'none' ?>;"
       data-volume="<?= e($music['volume'] ?? 50) ?>"
       data-loop="<?= ($music['loop'] ?? 'yes') === 'yes' ? 'true' : 'false' ?>"
       data-title="<?= e($music['title'] ?? 'Ambient Music') ?>">
       
    <audio id="bg-audio" 
           src="<?= !empty($music['file_url']) ? e($music['file_url']) : e($music['url']) ?>" 
           preload="auto" 
           <?= ($music['loop'] ?? 'yes') === 'yes' ? 'loop' : '' ?>></audio>
           
    <button id="music-toggle-btn" class="music-toggle-btn" aria-label="Toggle Background Music">
      <div class="music-icon-wrapper">
        <i id="music-icon" class="fa-solid fa-volume-xmark"></i>
        <!-- Sound Wave Bouncing Columns -->
        <div class="music-wave" id="music-wave">
          <span class="bar bar-1"></span>
          <span class="bar bar-2"></span>
          <span class="bar bar-3"></span>
          <span class="bar bar-4"></span>
        </div>
      </div>
      <div class="music-tooltip" id="music-tooltip">
        <span class="tooltip-title"><?= e($music['title'] ?? 'Warm Ambient') ?></span>
        <span class="tooltip-status">Klik untuk memutar</span>
      </div>
    </button>
  </div>
  <?php endif; ?>

  <!-- Interactive JavaScript Scripts -->
  <script src="assets/js/main.js"></script>
</body>
</html>
