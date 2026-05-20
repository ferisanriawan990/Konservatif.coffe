/* -------------------------------------------------------------
   KONSERVATIF. CIKUPA - INTERACTIVE JAVASCRIPT
   ------------------------------------------------------------- */

document.addEventListener('DOMContentLoaded', () => {

  /* 1. STICKY NAVBAR */
  const navbar = document.getElementById('navbar');
  window.addEventListener('scroll', () => {
    if (window.scrollY > 50) {
      navbar.classList.add('scrolled');
    } else {
      navbar.classList.remove('scrolled');
    }
  });


  /* 2. MOBILE MENU HAMBURGER */
  const hamburger = document.getElementById('hamburger-btn');
  const navMenu = document.getElementById('nav-menu');
  const navLinks = document.querySelectorAll('.nav-link');

  hamburger.addEventListener('click', () => {
    hamburger.classList.toggle('active');
    navMenu.classList.toggle('active');
  });

  navLinks.forEach(link => {
    link.addEventListener('click', () => {
      hamburger.classList.remove('active');
      navMenu.classList.remove('active');
    });
  });


  /* 3. MENU CATEGORY TAB SWITCHER */
  const tabButtons = document.querySelectorAll('.tab-btn');
  const menuGrids = document.querySelectorAll('.menu-grid');

  tabButtons.forEach(button => {
    button.addEventListener('click', () => {
      // Deactivate current active tab
      tabButtons.forEach(btn => btn.classList.remove('active'));
      menuGrids.forEach(grid => grid.classList.remove('active'));

      // Activate clicked tab
      button.classList.add('active');
      const targetId = button.getAttribute('data-target');
      const targetGrid = document.getElementById(targetId);
      if (targetGrid) {
        targetGrid.classList.add('active');
      }
    });
  });


  /* 4. GALLERY FILTER & LIGHTBOX */
  const filterButtons = document.querySelectorAll('.filter-btn');
  const galleryItems = document.querySelectorAll('.gallery-item');
  const lightbox = document.getElementById('lightbox');
  const lightboxImg = document.getElementById('lightbox-img');
  const lightboxCaption = document.getElementById('lightbox-caption');
  const lightboxClose = document.getElementById('lightbox-close');

  // Filter logic
  filterButtons.forEach(button => {
    button.addEventListener('click', () => {
      filterButtons.forEach(btn => btn.classList.remove('active'));
      button.classList.add('active');

      const filter = button.getAttribute('data-filter');

      galleryItems.forEach(item => {
        const category = item.getAttribute('data-category');
        if (filter === 'all' || category === filter) {
          item.style.display = 'block';
          // Small delay for fade-in effect
          setTimeout(() => {
            item.style.opacity = '1';
            item.style.transform = 'scale(1)';
          }, 10);
        } else {
          item.style.opacity = '0';
          item.style.transform = 'scale(0.8)';
          setTimeout(() => {
            item.style.display = 'none';
          }, 400);
        }
      });
    });
  });

  // Lightbox open logic
  galleryItems.forEach(item => {
    item.addEventListener('click', () => {
      const src = item.getAttribute('data-src');
      const caption = item.getAttribute('data-caption');

      lightboxImg.setAttribute('src', src);
      lightboxCaption.textContent = caption;
      lightbox.classList.add('active');
      document.body.style.overflow = 'hidden'; // Disable page scrolling
    });
  });

  // Lightbox close logic
  const closeLightboxFunc = () => {
    lightbox.classList.remove('active');
    document.body.style.overflow = 'auto'; // Enable page scrolling
  };

  lightboxClose.addEventListener('click', closeLightboxFunc);
  lightbox.addEventListener('click', (e) => {
    if (e.target === lightbox || e.target === lightbox.querySelector('.lightbox-content')) {
      closeLightboxFunc();
    }
  });


  /* 5. TESTIMONIALS SLIDER */
  const track = document.getElementById('testimonial-track');
  const slides = document.querySelectorAll('.testimonial-slide');
  const dots = document.querySelectorAll('.dot');
  let currentIndex = 0;
  let autoplayTimer;

  const updateSlider = (index) => {
    if (!track) return;
    currentIndex = index;
    track.style.transform = `translateX(-${currentIndex * 100}%)`;
    
    // Update dots
    dots.forEach((dot, idx) => {
      if (idx === currentIndex) {
        dot.classList.add('active');
      } else {
        dot.classList.remove('active');
      }
    });
  };

  // Click dot to navigate
  dots.forEach(dot => {
    dot.addEventListener('click', () => {
      const index = parseInt(dot.getAttribute('data-index'), 10);
      updateSlider(index);
      resetAutoplay();
    });
  });

  // Autoplay functionality
  const startAutoplay = () => {
    if (slides.length <= 1) return;
    autoplayTimer = setInterval(() => {
      let nextIndex = currentIndex + 1;
      if (nextIndex >= slides.length) {
        nextIndex = 0;
      }
      updateSlider(nextIndex);
    }, 5000);
  };

  const stopAutoplay = () => {
    clearInterval(autoplayTimer);
  };

  const resetAutoplay = () => {
    stopAutoplay();
    startAutoplay();
  };

  // Start autoplay on load
  startAutoplay();

  // Pause on hover
  const sliderContainer = document.querySelector('.testimonial-slider');
  if (sliderContainer) {
    sliderContainer.addEventListener('mouseenter', stopAutoplay);
    sliderContainer.addEventListener('mouseleave', startAutoplay);
  }


  /* 6. SCROLL REVEAL ANIMATIONS (INTERSECTION OBSERVER) */
  const revealElements = document.querySelectorAll('.reveal');
  
  if ('IntersectionObserver' in window) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('active');
          observer.unobserve(entry.target); // Trigger only once
        }
      });
    }, {
      threshold: 0.15,
      rootMargin: '0px 0px -50px 0px'
    });

    revealElements.forEach(element => {
      revealObserver.observe(element);
    });
  } else {
    // Fallback if IntersectionObserver is not supported
    revealElements.forEach(element => {
      element.classList.add('active');
    });
  }


  /* 7. CONTACT FORM AJAX SUBMISSION */
  const contactForm = document.getElementById('contactForm');
  const submitBtn = document.getElementById('submitBtn');
  const formStatus = document.getElementById('formStatus');

  if (contactForm) {
    contactForm.addEventListener('submit', (e) => {
      e.preventDefault();
      
      // Update UI to submitting state
      submitBtn.disabled = true;
      const originalBtnText = submitBtn.innerHTML;
      submitBtn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Mengirim...';
      formStatus.style.display = 'none';
      formStatus.className = 'form-status';

      const formData = new FormData(contactForm);

      fetch('index.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        if (data.status === 'success') {
          formStatus.classList.add('success');
          formStatus.textContent = data.message;
          contactForm.reset();
        } else {
          formStatus.classList.add('error');
          formStatus.textContent = data.message;
        }
        formStatus.style.display = 'block';
      })
      .catch(error => {
        console.error('Error submitting form:', error);
        formStatus.classList.add('error');
        formStatus.textContent = 'Terjadi kesalahan sistem. Silakan coba lagi nanti atau hubungi WhatsApp kami.';
        formStatus.style.display = 'block';
      })
      .finally(() => {
        submitBtn.disabled = false;
        submitBtn.innerHTML = originalBtnText;
      });
    });
  }

});
