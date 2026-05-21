/* -------------------------------------------------------------
   KONSERVATIF. CIKUPA - INTERACTIVE JAVASCRIPT
   All interactive logic, 3D effects, and animations
   ------------------------------------------------------------- */

document.addEventListener('DOMContentLoaded', () => {

  /* -------------------------------------------------------------
     1. PRELOADER TRANSITION
     ------------------------------------------------------------- */
  const loader = document.getElementById('loader');
  if (loader) {
    window.addEventListener('load', () => {
      setTimeout(() => {
        loader.classList.add('loaded');
        document.body.classList.remove('no-scroll');
      }, 600); // Elegant brief pause
    });

    // Fallback if load event takes too long
    setTimeout(() => {
      if (!loader.classList.contains('loaded')) {
        loader.classList.add('loaded');
        document.body.classList.remove('no-scroll');
      }
    }, 3000);
  }


  /* -------------------------------------------------------------
     2. STICKY NAVBAR
     ------------------------------------------------------------- */
  const navbar = document.getElementById('navbar');
  if (navbar) {
    const handleScroll = () => {
      if (window.scrollY > 40) {
        navbar.classList.add('scrolled');
      } else {
        navbar.classList.remove('scrolled');
      }
    };
    window.addEventListener('scroll', handleScroll);
    handleScroll(); // Trigger initial state
  }


  /* -------------------------------------------------------------
     3. MOBILE NAV MENU TOGGLE
     ------------------------------------------------------------- */
  const navToggle = document.getElementById('nav-toggle');
  const navMenu = document.getElementById('nav-menu');
  const navOverlay = document.getElementById('nav-overlay');
  const navLinks = document.querySelectorAll('.nav-link');

  if (navToggle && navMenu) {
    const toggleMenu = () => {
      navToggle.classList.toggle('active');
      navMenu.classList.toggle('active');
      if (navOverlay) navOverlay.classList.toggle('visible');
      document.body.classList.toggle('no-scroll');
    };

    const closeMenu = () => {
      navToggle.classList.remove('active');
      navMenu.classList.remove('active');
      if (navOverlay) navOverlay.classList.remove('visible');
      document.body.classList.remove('no-scroll');
    };

    navToggle.addEventListener('click', toggleMenu);
    if (navOverlay) navOverlay.addEventListener('click', closeMenu);

    navLinks.forEach(link => {
      link.addEventListener('click', closeMenu);
    });
  }


  /* -------------------------------------------------------------
     4. STATS COUNTER UP ANIMATION
     ------------------------------------------------------------- */
  const statNumbers = document.querySelectorAll('.hero-stat-number');
  
  const animateCount = (el) => {
    const target = parseFloat(el.getAttribute('data-count'));
    const suffix = el.getAttribute('data-suffix') || '';
    const duration = 2000; // 2 seconds
    const start = 0;
    const startTime = performance.now();

    const update = (now) => {
      const elapsed = now - startTime;
      const progress = Math.min(elapsed / duration, 1);
      
      // Ease out quad formula
      const ease = progress * (2 - progress);
      const current = start + ease * (target - start);

      if (target % 1 === 0) {
        // Integer
        el.textContent = Math.floor(current) + suffix;
      } else {
        // Float (like rating 4.8)
        el.textContent = current.toFixed(1) + suffix;
      }

      if (progress < 1) {
        requestAnimationFrame(update);
      } else {
        el.textContent = target + suffix;
      }
    };

    requestAnimationFrame(update);
  };

  if (statNumbers.length > 0 && 'IntersectionObserver' in window) {
    const statsObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          animateCount(entry.target);
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.5 });

    statNumbers.forEach(num => statsObserver.observe(num));
  } else {
    // Fallback: immediate values if observer not supported
    statNumbers.forEach(num => {
      const count = num.getAttribute('data-count');
      const suffix = num.getAttribute('data-suffix') || '';
      num.textContent = count + suffix;
    });
  }


  /* -------------------------------------------------------------
     5. 3D TILT EFFECT ON CARDS
     ------------------------------------------------------------- */
  const tiltCards = document.querySelectorAll('.tilt-card');
  
  tiltCards.forEach(card => {
    card.addEventListener('mousemove', (e) => {
      const rect = card.getBoundingClientRect();
      const x = e.clientX - rect.left; // Mouse X relative to card
      const y = e.clientY - rect.top;  // Mouse Y relative to card
      
      const centerX = rect.width / 2;
      const centerY = rect.height / 2;
      
      // Calculate rotation (-10deg to 10deg)
      const rotateX = ((centerY - y) / centerY) * 10;
      const rotateY = ((x - centerX) / centerX) * 10;
      
      card.style.transform = `perspective(1000px) rotateX(${rotateX}deg) rotateY(${rotateY}deg) scale3d(1.02, 1.02, 1.02)`;
      
      // Create reflect shininess relative positions
      const percentX = (x / rect.width) * 100;
      const percentY = (y / rect.height) * 100;
      card.style.setProperty('--mouse-x', `${percentX}%`);
      card.style.setProperty('--mouse-y', `${percentY}%`);
    });

    card.addEventListener('mouseleave', () => {
      card.style.transform = 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)';
    });
  });


  /* -------------------------------------------------------------
     6. FRIDAY PROMO COUNTDOWN TIMER
     ------------------------------------------------------------- */
  const countdownEl = document.getElementById('promo-countdown');
  
  const updateCountdown = () => {
    if (!countdownEl) return;

    const now = new Date();
    
    // Find next Friday (day 5 of week) at midnight 00:00:00
    let nextFriday = new Date();
    nextFriday.setHours(0, 0, 0, 0);
    
    // Get difference in days
    const currentDay = now.getDay(); // 0 is Sunday, 5 is Friday, 6 is Saturday
    let daysUntilFriday = 5 - currentDay;
    
    if (daysUntilFriday < 0) {
      // It's Saturday, Friday is next week
      daysUntilFriday += 7;
    } else if (daysUntilFriday === 0) {
      // It's Friday! Check if it's currently Friday
      // If it's Friday, the promo is ACTIVE!
      countdownEl.innerHTML = `
        <div style="background: rgba(37, 211, 102, 0.1); border: 1px solid rgba(37, 211, 102, 0.3); padding: 12px 24px; border-radius: 12px; color: #25D366; text-align: center; font-weight: 600; width: 100%;">
          <i class="fa-solid fa-circle-check"></i> PROMO SEDANG AKTIF HARI INI! Silakan pesan menu signature favorit Anda.
        </div>
      `;
      return;
    }

    nextFriday.setDate(now.getDate() + daysUntilFriday);
    const diff = nextFriday.getTime() - now.getTime();

    // Time calculations
    const days = Math.floor(diff / (1000 * 60 * 60 * 24));
    const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((diff % (1000 * 60)) / 1000);

    countdownEl.innerHTML = `
      <div class="countdown-box">
        <div class="countdown-value">${days}</div>
        <div class="countdown-lbl">Hari</div>
      </div>
      <div class="countdown-box">
        <div class="countdown-value">${String(hours).padStart(2, '0')}</div>
        <div class="countdown-lbl">Jam</div>
      </div>
      <div class="countdown-box">
        <div class="countdown-value">${String(minutes).padStart(2, '0')}</div>
        <div class="countdown-lbl">Menit</div>
      </div>
      <div class="countdown-box">
        <div class="countdown-value">${String(seconds).padStart(2, '0')}</div>
        <div class="countdown-lbl">Detik</div>
      </div>
    `;
  };

  if (countdownEl) {
    updateCountdown();
    setInterval(updateCountdown, 1000);
  }


  /* -------------------------------------------------------------
     7. MENU CATEGORY TAB SWITCHER
     ------------------------------------------------------------- */
  const tabButtons = document.querySelectorAll('.tab-btn');
  const menuPanels = document.querySelectorAll('.menu-panel');

  tabButtons.forEach(button => {
    button.addEventListener('click', () => {
      // Deactivate current active tab
      tabButtons.forEach(btn => btn.classList.remove('active'));
      menuPanels.forEach(panel => panel.classList.remove('active'));

      // Activate clicked tab
      button.classList.add('active');
      const targetId = button.getAttribute('data-target');
      const targetPanel = document.getElementById(targetId);
      if (targetPanel) {
        targetPanel.classList.add('active');
      }
    });
  });


  /* -------------------------------------------------------------
     8. GALLERY FILTER & LIGHTBOX MODAL
     ------------------------------------------------------------- */
  const filterButtons = document.querySelectorAll('.filter-btn');
  const galleryItems = document.querySelectorAll('.gallery-item');
  const lightbox = document.getElementById('lightbox');
  const lightboxImg = document.getElementById('lightbox-img');
  const lightboxCaption = document.getElementById('lightbox-caption');
  const lightboxClose = document.getElementById('lightbox-close');

  // Filtering Logic
  filterButtons.forEach(button => {
    button.addEventListener('click', () => {
      filterButtons.forEach(btn => btn.classList.remove('active'));
      button.classList.add('active');

      const filter = button.getAttribute('data-filter');

      galleryItems.forEach(item => {
        const category = item.getAttribute('data-category');
        if (filter === 'all' || category === filter) {
          item.style.display = 'block';
          setTimeout(() => {
            item.style.opacity = '1';
            item.style.transform = 'scale(1)';
          }, 20);
        } else {
          item.style.opacity = '0';
          item.style.transform = 'scale(0.85)';
          setTimeout(() => {
            item.style.display = 'none';
          }, 350);
        }
      });
    });
  });

  // Lightbox Open
  galleryItems.forEach(item => {
    item.addEventListener('click', () => {
      const src = item.getAttribute('data-src');
      const caption = item.getAttribute('data-caption');

      if (lightboxImg && lightboxCaption && lightbox) {
        lightboxImg.setAttribute('src', src);
        lightboxCaption.textContent = caption;
        lightbox.classList.add('active');
        document.body.classList.add('no-scroll');
      }
    });
  });

  // Lightbox Close
  const closeLightbox = () => {
    if (lightbox) {
      lightbox.classList.remove('active');
      document.body.classList.remove('no-scroll');
    }
  };

  if (lightboxClose) lightboxClose.addEventListener('click', closeLightbox);
  if (lightbox) {
    lightbox.addEventListener('click', (e) => {
      if (e.target === lightbox || e.target.classList.contains('lightbox-content')) {
        closeLightbox();
      }
    });
  }


  /* -------------------------------------------------------------
     9. TESTIMONIALS SWIPE SLIDER
     ------------------------------------------------------------- */
  const track = document.getElementById('testimonial-track');
  const slides = document.querySelectorAll('.testimonial-slide');
  const dots = document.querySelectorAll('.dot');
  let currentIndex = 0;
  let autoplayTimer;

  const updateSlider = (index) => {
    if (!track) return;
    
    // Bounds check
    if (index < 0) index = slides.length - 1;
    if (index >= slides.length) index = 0;

    currentIndex = index;
    track.style.transform = `translateX(-${currentIndex * 100}%)`;
    
    // Update active dot
    dots.forEach((dot, idx) => {
      if (idx === currentIndex) {
        dot.classList.add('active');
      } else {
        dot.classList.remove('active');
      }
    });
  };

  // Dot navigation
  dots.forEach(dot => {
    dot.addEventListener('click', () => {
      const index = parseInt(dot.getAttribute('data-index'), 10);
      updateSlider(index);
      resetAutoplay();
    });
  });

  // Autoplay Logic
  const startAutoplay = () => {
    if (slides.length <= 1) return;
    autoplayTimer = setInterval(() => {
      updateSlider(currentIndex + 1);
    }, 5500);
  };

  const stopAutoplay = () => {
    clearInterval(autoplayTimer);
  };

  const resetAutoplay = () => {
    stopAutoplay();
    startAutoplay();
  };

  startAutoplay();

  // Hover pauses autoplay
  const sliderContainer = document.querySelector('.testimonial-slider');
  if (sliderContainer) {
    sliderContainer.addEventListener('mouseenter', stopAutoplay);
    sliderContainer.addEventListener('mouseleave', startAutoplay);
  }

  // Touch Swipe Support for Mobile
  let startX = 0;
  let isSwiping = false;

  if (sliderContainer) {
    sliderContainer.addEventListener('touchstart', (e) => {
      startX = e.touches[0].clientX;
      isSwiping = true;
      stopAutoplay();
    }, { passive: true });

    sliderContainer.addEventListener('touchmove', (e) => {
      if (!isSwiping) return;
      const currentX = e.touches[0].clientX;
      const diffX = startX - currentX;
      
      // Minimum swipe threshold
      if (Math.abs(diffX) > 60) {
        if (diffX > 0) {
          // Swipe left -> next slide
          updateSlider(currentIndex + 1);
        } else {
          // Swipe right -> prev slide
          updateSlider(currentIndex - 1);
        }
        isSwiping = false;
      }
    }, { passive: true });

    sliderContainer.addEventListener('touchend', () => {
      isSwiping = false;
      startAutoplay();
    });
  }


  /* -------------------------------------------------------------
     10. SCROLL REVEAL ANIMATIONS (INTERSECTION OBSERVER)
     ------------------------------------------------------------- */
  const revealElements = document.querySelectorAll('.reveal');
  
  if ('IntersectionObserver' in window && revealElements.length > 0) {
    const revealObserver = new IntersectionObserver((entries, observer) => {
      entries.forEach(entry => {
        if (entry.isIntersecting) {
          entry.target.classList.add('active');
          observer.unobserve(entry.target); // Reveal only once
        }
      });
    }, {
      threshold: 0.1,
      rootMargin: '0px 0px -50px 0px'
    });

    revealElements.forEach(element => {
      revealObserver.observe(element);
    });
  } else {
    // Fallback: immediately activate if IntersectionObserver is not supported
    revealElements.forEach(element => {
      element.classList.add('active');
    });
  }


  /* -------------------------------------------------------------
     11. CONTACT FORM AJAX SUBMISSION
     ------------------------------------------------------------- */
  const contactForm = document.getElementById('contactForm');
  const submitBtn = document.getElementById('submitBtn');
  const formStatus = document.getElementById('formStatus');

  if (contactForm && submitBtn && formStatus) {
    contactForm.addEventListener('submit', (e) => {
      e.preventDefault();
      
      // Update UI state
      submitBtn.disabled = true;
      const originalBtnText = submitBtn.innerHTML;
      submitBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Mengirim...';
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
