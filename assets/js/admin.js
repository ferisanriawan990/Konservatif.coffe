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
