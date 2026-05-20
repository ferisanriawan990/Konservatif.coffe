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
  
  <!-- Custom Stylesheet -->
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

  <!-- Navigation Bar -->
  <nav class="navbar" id="navbar">
    <div class="nav-container">
      <a href="#" class="logo" id="nav-logo">
        <i class="fa-solid fa-mug-hot" style="color: var(--accent-orange);"></i> <?= e($site_name) ?>
      </a>
      <ul class="nav-menu" id="nav-menu">
        <li><a href="#" class="nav-link">Beranda</a></li>
        <li><a href="#tentang-kami" class="nav-link">Tentang Kami</a></li>
        <li><a href="#menu" class="nav-link">Menu</a></li>
        <li><a href="#galeri" class="nav-link">Galeri</a></li>
        <li><a href="#lokasi" class="nav-link">Lokasi</a></li>
        <li><a href="#kontak" class="nav-link">Kontak</a></li>
        <li><a href="admin/login.php" class="nav-link" style="opacity: 0.7;"><i class="fa-solid fa-user-lock"></i> Admin</a></li>
      </ul>
      <button class="hamburger" id="hamburger-btn" aria-label="Toggle Menu">
        <span></span>
        <span></span>
        <span></span>
      </button>
    </div>
  </nav>

  <!-- Hero Section -->
  <header class="hero" id="home">
    <div class="hero-bg" style="background-image: url('<?= e($hero_image) ?>');"></div>
    <div class="container">
      <div class="hero-content">
        <span class="hero-subtitle"><?= e($hero_subtitle) ?></span>
        <h1 class="hero-title"><?= e($hero_title) ?></h1>
        <p class="hero-desc"><?= e($hero_desc) ?></p>
        <div class="hero-buttons">
          <a href="#menu" class="btn btn-primary"><i class="fa-solid fa-utensils"></i> Lihat Menu</a>
          <a href="<?= e($maps_link) ?>" target="_blank" rel="noopener" class="btn btn-secondary"><i class="fa-solid fa-map-location-dot"></i> Buka Google Maps</a>
          <a href="https://wa.me/<?= e($whatsapp_number) ?>?text=Halo%20konservatif.coffee,%20saya%20ingin%20tanya-tanya%20info%20menu/reservasi" target="_blank" rel="noopener" class="btn btn-outline" style="border-color: var(--accent-orange); color: var(--text-light);"><i class="fa-brands fa-whatsapp"></i> Chat WhatsApp</a>
        </div>
      </div>
    </div>
  </header>

  <!-- Tentang Kami Section -->
  <section id="tentang-kami">
    <div class="container">
      <div class="about-grid">
        <div class="about-content reveal reveal-right">
          <span class="section-tag">Kisah Kami</span>
          <h2 class="about-title">Tentang Kami</h2>
          <p class="about-desc"><?= nl2br(e($about_text)) ?></p>
          <a href="#kontak" class="btn btn-primary"><i class="fa-solid fa-paper-plane"></i> Hubungi Kami</a>
        </div>
        <div class="about-image-wrapper reveal reveal-left">
          <img src="<?= e($about_image) ?>" alt="Suasana konservatif.coffee" class="about-image" loading="lazy">
          <div class="about-badge">
            <h4>konservatif.coffee</h4>
            <p>Modern Vintage Aesthetic</p>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- Menu Section -->
  <section id="menu" style="background-color: var(--bone-white);">
    <div class="container">
      <div class="section-header reveal reveal-up">
        <span class="section-tag">Sajian Terbaik Kami</span>
        <h2 class="section-title">Menu</h2>
        <p>Nikmati pilihan sajian rasa autentik yang diseduh dan dimasak dengan penuh cinta.</p>
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
            <!-- Drink table with Hot/Ice columns -->
            <div class="menu-table-wrap">
              <table class="menu-table">
                <thead>
                  <tr>
                    <th class="col-name">Menu</th>
                    <th class="col-price"><i class="fa-solid fa-mug-hot"></i> Hot</th>
                    <th class="col-price"><i class="fa-solid fa-snowflake"></i> Ice</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($items)): ?>
                    <tr><td colspan="3" style="text-align:center; padding:24px;">Belum ada menu tersedia.</td></tr>
                  <?php else: ?>
                    <?php foreach ($items as $item): ?>
                      <tr>
                        <td class="col-name">
                          <span class="menu-item-name"><?= e($item['name']) ?></span>
                          <?php if (!empty($item['description'])): ?>
                            <span class="menu-item-desc"><?= e($item['description']) ?></span>
                          <?php endif; ?>
                        </td>
                        <td class="col-price"><?= format_k($item['hot_price'] ?? 0) ?></td>
                        <td class="col-price"><?= format_k($item['ice_price'] ?? 0) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

          <?php elseif ($cfg['type'] === 'single'): ?>
            <!-- Single price drink (Signature / Mocktail) -->
            <div class="menu-table-wrap">
              <table class="menu-table">
                <thead>
                  <tr>
                    <th class="col-name">Menu</th>
                    <th class="col-price">Harga</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (empty($items)): ?>
                    <tr><td colspan="2" style="text-align:center; padding:24px;">Belum ada menu tersedia.</td></tr>
                  <?php else: ?>
                    <?php foreach ($items as $item): ?>
                      <tr>
                        <td class="col-name">
                          <span class="menu-item-name"><?= e($item['name']) ?></span>
                          <?php if (!empty($item['description'])): ?>
                            <span class="menu-item-desc"><?= e($item['description']) ?></span>
                          <?php endif; ?>
                        </td>
                        <td class="col-price"><?= format_k($item['price'] ?? 0) ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>

          <?php else: ?>
            <!-- Food / Snack cards -->
            <div class="menu-food-grid">
              <?php if (empty($items)): ?>
                <p style="grid-column: 1/-1; text-align: center;">Belum ada menu tersedia.</p>
              <?php else: ?>
                <?php foreach ($items as $item): ?>
                  <div class="menu-food-card">
                    <div class="food-card-info">
                      <h3 class="food-card-name"><?= e($item['name']) ?></h3>
                      <?php if (!empty($item['description'])): ?>
                        <p class="food-card-desc"><?= e($item['description']) ?></p>
                      <?php endif; ?>
                      <?php if (!empty($item['variant'])): ?>
                        <span class="food-card-variant">Varian: <?= e($item['variant']) ?></span>
                      <?php endif; ?>
                    </div>
                    <span class="food-card-price"><?= format_k($item['price'] ?? 0) ?></span>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          <?php endif; ?>

          <!-- WhatsApp Order Button -->
          <div class="menu-wa-btn-wrap">
            <a href="https://wa.me/<?= e($whatsapp_number) ?>?text=<?= $wa_text ?>" target="_blank" rel="noopener" class="btn btn-wa-order">
              <i class="fa-brands fa-whatsapp"></i> Pesan via WhatsApp
            </a>
          </div>
        </div>
        <?php $first = false; endforeach; ?>
      </div>
    </div>
  </section>



  <!-- Fitur Unggulan Section -->
  <section class="features-section" id="keunggulan">
    <div class="container">
      <div class="section-header reveal reveal-up">
        <span class="section-tag">Kenapa Harus Kami</span>
        <h2 class="section-title" style="color: var(--bone-white);">Fitur Unggulan</h2>
        <p style="color: rgba(250,249,246,0.65);">Kami berkomitmen untuk memberikan pengalaman nongkrong terbaik dengan kualitas prima.</p>
      </div>
      
      <div class="features-grid">
        <!-- Feature 1 -->
        <div class="feature-card reveal reveal-up">
          <div class="feature-icon-box">
            <i class="fa-solid fa-chair"></i>
          </div>
          <h3>Suasana Nyaman</h3>
          <p>Interior modern vintage yang hangat, tempat duduk santai, dan dekorasi estetik yang pas untuk bersantai melepas penat.</p>
        </div>

        <!-- Feature 2 -->
        <div class="feature-card reveal reveal-up">
          <div class="feature-icon-box">
            <i class="fa-solid fa-laptop"></i>
          </div>
          <h3>Cocok untuk Kerja Santai</h3>
          <p>Dilengkapi koneksi Wi-Fi yang stabil, colokan listrik di berbagai sudut, serta suasana tenang yang produktif untuk WFC (Work From Cafe).</p>
        </div>

        <!-- Feature 3 -->
        <div class="feature-card reveal reveal-up">
          <div class="feature-icon-box">
            <i class="fa-solid fa-seedling"></i>
          </div>
          <h3>Menu Kopi & Non-Kopi</h3>
          <p>Kombinasi seduhan kopi nusantara yang aromatik serta varian minuman non-kopi manis segar untuk memanjakan selera Anda.</p>
        </div>

        <!-- Feature 4 -->
        <div class="feature-card reveal reveal-up">
          <div class="feature-icon-box">
            <i class="fa-solid fa-map-pin"></i>
          </div>
          <h3>Lokasi Mudah Ditemukan</h3>
          <p>Terletak strategis di Cikupa dengan akses jalan yang mudah serta area parkir yang memadai untuk kendaraan Anda.</p>
        </div>

        <!-- Feature 5 -->
        <div class="feature-card reveal reveal-up">
          <div class="feature-icon-box">
            <i class="fa-solid fa-users"></i>
          </div>
          <h3>Kumpul Teman</h3>
          <p>Layout ruang yang ramah kelompok, cocok untuk mengobrol santai, boardgames, kumpul alumni, hingga kumpul keluarga.</p>
        </div>

        <!-- Feature 6 -->
        <div class="feature-card reveal reveal-up">
          <div class="feature-icon-box">
            <i class="fa-solid fa-calendar-check"></i>
          </div>
          <h3>Acara Kecil & Komunitas</h3>
          <p>Area kami terbuka untuk disewa sebagai tempat pertemuan komunitas, workshop, arisan, hingga pesta ulang tahun mini.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- Galeri Section -->
  <section id="galeri">
    <div class="container">
      <div class="section-header reveal reveal-up">
        <span class="section-tag">Sudut Estetik Kami</span>
        <h2 class="section-title">Galeri Foto</h2>
        <p>Intip kehangatan sudut interior, kelezatan minuman, dan momen nongkrong seru di konservatif.coffee.</p>
      </div>

      <!-- Filter Buttons -->
      <div class="gallery-filter reveal reveal-up">
        <button class="filter-btn active" data-filter="all">Semua</button>
        <button class="filter-btn" data-filter="atmosphere">Suasana Cafe</button>
        <button class="filter-btn" data-filter="drinks">Minuman</button>
        <button class="filter-btn" data-filter="food">Snack & Makanan</button>
        <button class="filter-btn" data-filter="front">Storefront</button>
      </div>

      <!-- Gallery Grid -->
      <div class="gallery-grid reveal reveal-up">
        <?php if (empty($gallery_items)): ?>
          <p style="grid-column: 1/-1; text-align: center;">Belum ada foto galeri tersedia.</p>
        <?php else: ?>
          <?php foreach ($gallery_items as $item): ?>
            <div class="gallery-item" data-category="<?= e($item['category']) ?>" data-src="<?= e($item['image']) ?>" data-caption="<?= e($item['caption']) ?>">
              <img src="<?= e($item['image']) ?>" alt="<?= e($item['caption']) ?>" loading="lazy">
              <div class="gallery-overlay">
                <div class="gallery-icon"><i class="fa-solid fa-magnifying-glass-plus"></i></div>
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

  <!-- Lokasi Section -->
  <section id="lokasi" style="background-color: var(--bone-white);">
    <div class="container">
      <div class="location-grid">
        <div class="location-info reveal reveal-right">
          <span class="section-tag">Kunjungi Kami</span>
          <h2 class="loc-title">Lokasi & Jam Operasional</h2>
          
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
              <h4>Jam Buka</h4>
              <p><?= e($opening_hours) ?></p>
            </div>
          </div>

          <div class="info-item">
            <div class="info-icon"><i class="fa-solid fa-circle-info"></i></div>
            <div class="info-text">
              <h4>Area</h4>
              <p>Cikupa, Kabupaten Tangerang, Banten</p>
            </div>
          </div>

          <div class="loc-buttons">
            <a href="<?= e($maps_link) ?>" target="_blank" rel="noopener" class="btn btn-primary"><i class="fa-solid fa-directions"></i> Petunjuk Arah / Rute</a>
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
            <div style="display:flex; align-items:center; justify-content:center; height:100%; background:#e0e0e0;">
              <p>Google Maps belum disematkan.</p>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </section>

  <!-- Testimonial Section -->
  <section class="testimonials-section" id="testimoni">
    <div class="container">
      <div class="section-header reveal reveal-up">
        <span class="section-tag">Ulasan Pengunjung</span>
        <h2 class="section-title">Apa Kata Mereka?</h2>
        <p>Pendapat jujur dari para penikmat kopi setia konservatif.coffee.</p>
      </div>

      <div class="testimonial-slider reveal reveal-up">
        <div class="testimonial-track" id="testimonial-track">
          <?php if (empty($testimonials)): ?>
            <div class="testimonial-slide">
              <div class="quote-icon">“</div>
              <p class="testimonial-text">Belum ada testimoni tersedia saat ini.</p>
            </div>
          <?php else: ?>
            <?php foreach ($testimonials as $t): ?>
              <div class="testimonial-slide">
                <div class="quote-icon"><i class="fa-solid fa-quote-left"></i></div>
                <p class="testimonial-text">“<?= e($t['text']) ?>”</p>
                <div class="testimonial-author">
                  <p class="author-name"><?= e($t['name']) ?></p>
                  <p class="author-role"><?= e($t['role'] ?? 'Pelanggan') ?></p>
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
  </section>

  <!-- Kontak Section -->
  <section id="kontak">
    <div class="container">
      <div class="contact-grid">
        <div class="contact-quick reveal reveal-right">
          <span class="section-tag">Hubungi Kami</span>
          <h3>Ada Pertanyaan?</h3>
          <p>Kami selalu senang mendengar dari Anda! Silakan kirimkan pesan, pertanyaan, atau booking tempat untuk acara melalui form, atau gunakan link cepat di bawah ini.</p>
          
          <div class="social-buttons">
            <a href="https://wa.me/<?= e($whatsapp_number) ?>" target="_blank" rel="noopener" class="social-btn">
              <div class="social-icon" style="color: #25D366;"><i class="fa-brands fa-whatsapp"></i></div>
              <div>
                <p style="font-size: 0.8rem; color: var(--text-muted); font-weight: normal; margin-bottom: 2px;">Kirim Pesan WhatsApp</p>
                <p>Chat Langsung Admin</p>
              </div>
            </a>
            
            <a href="<?= e($instagram_link) ?>" target="_blank" rel="noopener" class="social-btn">
              <div class="social-icon" style="color: #E1306C;"><i class="fa-brands fa-instagram"></i></div>
              <div>
                <p style="font-size: 0.8rem; color: var(--text-muted); font-weight: normal; margin-bottom: 2px;">Ikuti Instagram Kami</p>
                <p>@konservatif.coffee</p>
              </div>
            </a>

            <a href="<?= e($maps_link) ?>" target="_blank" rel="noopener" class="social-btn">
              <div class="social-icon" style="color: #4285F4;"><i class="fa-solid fa-map-location-dot"></i></div>
              <div>
                <p style="font-size: 0.8rem; color: var(--text-muted); font-weight: normal; margin-bottom: 2px;">Buka di Google Maps</p>
                <p>konservatif.coffee</p>
              </div>
            </a>
          </div>
        </div>

        <div class="contact-form-container reveal reveal-left">
          <h3>Kirim Pesan</h3>
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
              <textarea id="message" name="message" class="form-control" placeholder="Tuliskan pesan Anda di sini..." required></textarea>
            </div>

            <button type="submit" class="btn btn-primary" id="submitBtn" style="width: 100%;">
              <i class="fa-solid fa-paper-plane"></i> Kirim Sekarang
            </button>
            
            <div id="formStatus" class="form-status"></div>
          </form>
        </div>
      </div>
    </div>
  </section>

  <!-- Floating WhatsApp Button -->
  <a href="https://wa.me/<?= e($whatsapp_number) ?>" class="floating-whatsapp" target="_blank" rel="noopener" aria-label="Chat WhatsApp">
    <i class="fa-brands fa-whatsapp"></i>
  </a>

  <!-- Footer -->
  <footer class="footer">
    <div class="container">
      <div class="footer-grid">
        <div class="footer-info">
          <h3><?= e($site_name) ?></h3>
          <p><?= e($hero_desc) ?></p>
        </div>
        
        <div class="footer-links">
          <h4>Peta Situs</h4>
          <ul>
            <li><a href="#">Beranda</a></li>
            <li><a href="#tentang-kami">Tentang Kami</a></li>
            <li><a href="#menu">Menu Favorit</a></li>
            <li><a href="#galeri">Galeri Foto</a></li>
            <li><a href="#lokasi">Lokasi Kami</a></li>
            <li><a href="#kontak">Hubungi Kontak</a></li>
          </ul>
        </div>

        <div class="footer-links">
          <h4>Hubungi & Temukan</h4>
          <ul>
            <li><a href="<?= e($maps_link) ?>" target="_blank" rel="noopener"><i class="fa-solid fa-compass"></i> Rute Google Maps</a></li>
            <li><a href="https://wa.me/<?= e($whatsapp_number) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-whatsapp"></i> Chat WhatsApp</a></li>
            <li><a href="<?= e($instagram_link) ?>" target="_blank" rel="noopener"><i class="fa-brands fa-instagram"></i> Instagram Profile</a></li>
          </ul>
        </div>
      </div>
      
      <div class="footer-bottom">
        <p>&copy; <?= date('Y') ?> <?= e($site_name) ?>. All Rights Reserved. Crafted with care.</p>
      </div>
    </div>
  </footer>

  <!-- Client-Side Interaction Script -->
  <script src="assets/js/main.js"></script>
</body>
</html>
