/* -------------------------------------------------------------
   KONSERVATIF. CIKUPA - ADMIN PANEL UI JAVASCRIPT
   ------------------------------------------------------------- */

document.addEventListener('DOMContentLoaded', () => {

  /* 1. DASHBOARD TAB ROUTING */
  const tabLinks = document.querySelectorAll('.tab-link');
  const tabContents = document.querySelectorAll('.tab-content');

  const switchTab = (tabId) => {
    // Deactivate all
    tabLinks.forEach(link => link.classList.remove('active'));
    tabContents.forEach(content => content.classList.remove('active'));

    // Activate selected
    const targetLink = document.querySelector(`.tab-link[data-tab="${tabId}"]`);
    const targetContent = document.getElementById(tabId);

    if (targetLink && targetContent) {
      targetLink.classList.add('active');
      targetContent.classList.add('active');
    }
  };

  // Click handler
  tabLinks.forEach(link => {
    link.addEventListener('click', (e) => {
      // Allow default behavior if it's external link (logout, etc.)
      const isExternal = link.getAttribute('href') && !link.getAttribute('href').startsWith('#');
      if (isExternal) return;

      e.preventDefault();
      const tabId = link.getAttribute('data-tab');
      switchTab(tabId);
      
      // Update hash in URL (optional, helps on reload)
      window.location.hash = link.getAttribute('href');
    });
  });

  // Handle Initial Load Hash Route
  const routeByHash = () => {
    const hash = window.location.hash;
    if (hash) {
      const activeLink = document.querySelector(`.tab-link[href="${hash}"]`);
      if (activeLink) {
        const tabId = activeLink.getAttribute('data-tab');
        switchTab(tabId);
      }
    }
  };

  // Run on load
  routeByHash();


  /* 2. MOBILE SIDEBAR MENU TOGGLE */
  const menuToggle = document.querySelector('.menu-toggle');
  const sidebar = document.querySelector('.sidebar');

  if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', () => {
      sidebar.classList.toggle('active');
    });

    // Close sidebar on link click (mobile viewport)
    tabLinks.forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth <= 768) {
          sidebar.classList.remove('active');
        }
      });
    });
  }


  /* 3. NOTIFICATION ALERT AUTO-DISMISS */
  const statusAlert = document.getElementById('status-alert');
  if (statusAlert) {
    setTimeout(() => {
      statusAlert.style.transition = 'opacity 0.5s ease';
      statusAlert.style.opacity = '0';
      setTimeout(() => {
        statusAlert.style.display = 'none';
      }, 500);
    }, 4000); // Fades out after 4 seconds
  }

});

/* -------------------------------------------------------------
   4. DYNAMIC FORM POPULATION (EDIT HANDLERS)
   ------------------------------------------------------------- */

// Helper to switch tabs programmatically
function switchTab(tabId) {
  const tabLinks = document.querySelectorAll('.tab-link');
  const tabContents = document.querySelectorAll('.tab-content');
  
  tabLinks.forEach(link => link.classList.remove('active'));
  tabContents.forEach(content => content.classList.remove('active'));

  const targetLink = document.querySelector(`.tab-link[data-tab="${tabId}"]`);
  const targetContent = document.getElementById(tabId);

  if (targetLink && targetContent) {
    targetLink.classList.add('active');
    targetContent.classList.add('active');
  }
}

// Menu Edit populate function
function populateMenuEdit(id, name, category, price, description, hot_price, ice_price, variant) {
  const cardHeader = document.getElementById('menuFormHeader');
  const formAction = document.getElementById('menuFormAction');
  const formId = document.getElementById('menuFormId');
  const formName = document.getElementById('menuFormName');
  const formCategory = document.getElementById('menuFormCategory');
  const formPrice = document.getElementById('menuFormPrice');
  const formHotPrice = document.getElementById('menuFormHotPrice');
  const formIcePrice = document.getElementById('menuFormIcePrice');
  const formDescription = document.getElementById('menuFormDescription');
  const formVariant = document.getElementById('menuFormVariant');
  const btnReset = document.getElementById('btnResetMenu');
  const imageHelp = document.getElementById('menuImageHelp');

  // Fill in values
  formAction.value = 'edit_menu';
  formId.value = id;
  formName.value = name;
  formCategory.value = category;
  formPrice.value = price || 0;
  formHotPrice.value = hot_price || 0;
  formIcePrice.value = ice_price || 0;
  formDescription.value = description || '';
  formVariant.value = variant || '';

  // UI adjustments
  cardHeader.textContent = 'Edit Menu Sajian';
  cardHeader.style.color = 'var(--accent-orange)';
  btnReset.style.display = 'inline-block';
  imageHelp.textContent = 'Unggah foto baru jika ingin mengganti foto saat ini.';

  // Scroll to form (useful on mobile viewports)
  document.getElementById('menuFormCard').scrollIntoView({ behavior: 'smooth' });
}

// Reset Menu Form
const btnResetMenu = document.getElementById('btnResetMenu');
if (btnResetMenu) {
  btnResetMenu.addEventListener('click', () => {
    const cardHeader = document.getElementById('menuFormHeader');
    const formAction = document.getElementById('menuFormAction');
    const formId = document.getElementById('menuFormId');
    const form = document.getElementById('menuForm');
    const btnReset = document.getElementById('btnResetMenu');
    const imageHelp = document.getElementById('menuImageHelp');

    form.reset();
    formAction.value = 'add_menu';
    formId.value = '';
    
    // Reset new fields to 0
    document.getElementById('menuFormPrice').value = 0;
    document.getElementById('menuFormHotPrice').value = 0;
    document.getElementById('menuFormIcePrice').value = 0;
    
    cardHeader.textContent = 'Tambah Menu Baru';
    cardHeader.style.color = 'var(--cream-medium)';
    btnReset.style.display = 'none';
    imageHelp.textContent = 'Disarankan ukuran square 1:1.';
  });
}

// Testimonials Edit populate function
function populateTestimonialEdit(id, name, role, text) {
  const cardHeader = document.getElementById('tFormHeader');
  const formAction = document.getElementById('tFormAction');
  const formId = document.getElementById('tFormId');
  const formName = document.getElementById('tFormName');
  const formRole = document.getElementById('tFormRole');
  const formText = document.getElementById('tFormText');
  const btnReset = document.getElementById('btnResetT');

  // Fill in values
  formAction.value = 'edit_testimonial';
  formId.value = id;
  formName.value = name;
  formRole.value = role;
  formText.value = text;

  // UI adjustments
  cardHeader.textContent = 'Edit Testimoni';
  cardHeader.style.color = 'var(--accent-orange)';
  btnReset.style.display = 'inline-block';

  // Scroll to form
  document.getElementById('tFormCard').scrollIntoView({ behavior: 'smooth' });
}

// Reset Testimonial Form
const btnResetT = document.getElementById('btnResetT');
if (btnResetT) {
  btnResetT.addEventListener('click', () => {
    const cardHeader = document.getElementById('tFormHeader');
    const formAction = document.getElementById('tFormAction');
    const formId = document.getElementById('tFormId');
    const form = document.getElementById('tForm');
    const btnReset = document.getElementById('btnResetT');

    form.reset();
    formAction.value = 'add_testimonial';
    formId.value = '';
    
    cardHeader.textContent = 'Tambah Testimoni';
    cardHeader.style.color = 'var(--cream-medium)';
    btnReset.style.display = 'none';
  });
}

/* -------------------------------------------------------------
   5. BACKGROUND MUSIC SETTINGS PANEL LOGIC
   ------------------------------------------------------------- */
document.addEventListener('DOMContentLoaded', () => {
  const musicVolumeSlider = document.getElementById('music_volume_slider');
  const volumeValDisplay = document.getElementById('volume-val-display');
  
  if (musicVolumeSlider && volumeValDisplay) {
    musicVolumeSlider.addEventListener('input', () => {
      volumeValDisplay.textContent = musicVolumeSlider.value + '%';
      // Sync with preview slider if audio exists
      const previewVolumeSlider = document.getElementById('preview_volume_slider');
      const previewVolumeText = document.getElementById('preview-volume-text');
      if (previewVolumeSlider && previewVolumeText) {
        previewVolumeSlider.value = musicVolumeSlider.value;
        previewVolumeText.textContent = musicVolumeSlider.value + '%';
        const previewAudio = document.getElementById('admin-preview-audio');
        if (previewAudio) {
          previewAudio.volume = parseFloat(musicVolumeSlider.value) / 100;
        }
      }
    });
  }

  // Preview Player Logic
  const previewAudio = document.getElementById('admin-preview-audio');
  const btnPlay = document.getElementById('btn-preview-play');
  const playIcon = document.getElementById('preview-play-icon');
  const btnStop = document.getElementById('btn-preview-stop');
  const previewVolSlider = document.getElementById('preview_volume_slider');
  const previewVolText = document.getElementById('preview-volume-text');
  const diskContainer = document.getElementById('preview-disk-container');
  const diskIcon = document.getElementById('preview-disk-icon');
  
  // Set default initial volume on load
  if (previewAudio && previewVolSlider) {
    previewAudio.volume = parseFloat(previewVolSlider.value) / 100;
  }

  const setPlayState = (isPlaying) => {
    if (!playIcon || !diskContainer || !diskIcon) return;
    if (isPlaying) {
      playIcon.className = 'fa-solid fa-pause';
      diskContainer.classList.add('spinning-vinyl');
      diskIcon.className = 'fa-solid fa-compact-disc fa-spin';
    } else {
      playIcon.className = 'fa-solid fa-play';
      diskContainer.classList.remove('spinning-vinyl');
      diskIcon.className = 'fa-solid fa-music';
    }
  };

  if (btnPlay && previewAudio) {
    btnPlay.addEventListener('click', () => {
      // Check if there is a valid src
      if (!previewAudio.src || previewAudio.src === window.location.href) {
        alert('Belum ada file musik atau URL audio yang diatur untuk diputar.');
        return;
      }
      
      if (previewAudio.paused) {
        previewAudio.play()
          .then(() => setPlayState(true))
          .catch(err => {
            console.error('Audio play error:', err);
            alert('Gagal memutar audio preview. Periksa link audio Anda.');
          });
      } else {
        previewAudio.pause();
        setPlayState(false);
      }
    });
  }

  if (btnStop && previewAudio) {
    btnStop.addEventListener('click', () => {
      previewAudio.pause();
      previewAudio.currentTime = 0;
      setPlayState(false);
    });
  }

  if (previewVolSlider && previewAudio) {
    previewVolSlider.addEventListener('input', () => {
      const vol = parseFloat(previewVolSlider.value);
      previewAudio.volume = vol / 100;
      if (previewVolText) {
        previewVolText.textContent = vol + '%';
      }
      // Sync settings input slider
      if (musicVolumeSlider && volumeValDisplay) {
        musicVolumeSlider.value = vol;
        volumeValDisplay.textContent = vol + '%';
      }
    });
  }

  if (previewAudio) {
    previewAudio.addEventListener('ended', () => {
      setPlayState(false);
    });
  }

  // File Input Validation and Live Preview Streaming
  const musicFileInput = document.getElementById('musicFileVal');
  const previewTitle = document.getElementById('preview-music-title');
  const previewSource = document.getElementById('preview-music-source');

  if (musicFileInput) {
    musicFileInput.addEventListener('change', () => {
      const file = musicFileInput.files[0];
      if (!file) return;

      // Validate Format
      const filename = file.name;
      const extension = filename.split('.').pop().toLowerCase();
      const allowedExts = ['mp3', 'wav', 'ogg'];
      
      if (!allowedExts.includes(extension)) {
        alert('Format file tidak didukung! Hanya diperbolehkan file audio berformat .mp3, .wav, atau .ogg.');
        musicFileInput.value = ''; // Reset input
        return;
      }

      // Validate Size (max 10MB)
      const maxSize = 10 * 1024 * 1024;
      if (file.size > maxSize) {
        alert('Ukuran file terlalu besar! Maksimal ukuran file audio adalah 10MB.');
        musicFileInput.value = ''; // Reset input
        return;
      }

      // Stream local preview
      const objectUrl = URL.createObjectURL(file);
      
      // Stop current play if any
      if (previewAudio) {
        previewAudio.pause();
        previewAudio.currentTime = 0;
        previewAudio.src = objectUrl;
        previewAudio.load();
        setPlayState(false);
      }

      // Update text details
      if (previewTitle) {
        previewTitle.textContent = filename;
      }
      if (previewSource) {
        previewSource.innerHTML = `<i class="fa-solid fa-file-arrow-up"></i> File Baru Seleksi: <strong>${filename}</strong>`;
      }
    });
  }
});
