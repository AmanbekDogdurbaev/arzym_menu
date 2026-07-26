document.addEventListener('DOMContentLoaded', function () {
  var toolsEl = document.getElementById('sticky-tools');
  var root = document.documentElement;

  function updateStickyOffsets() {
    if (toolsEl) root.style.setProperty('--tools-h', toolsEl.offsetHeight + 'px');
  }
  updateStickyOffsets();
  window.addEventListener('resize', updateStickyOffsets);
  window.addEventListener('orientationchange', updateStickyOffsets);
  if (document.fonts && document.fonts.ready) {
    document.fonts.ready.then(updateStickyOffsets);
  }

  var navLinks = document.querySelectorAll('.cat-nav a');
  var sections = document.querySelectorAll('.menu-section');
  var catToggle = document.getElementById('cat-toggle');
  var catToggleLabel = document.getElementById('cat-toggle-label');
  var catNav = document.getElementById('cat-nav');

  function setActiveNav() {
    if (!sections.length) return;
    var offset = (toolsEl ? toolsEl.offsetHeight : 0) + 20;
    var scrollPos = window.scrollY + offset;
    var current = sections[0];
    sections.forEach(function (sec) {
      if (sec.offsetTop <= scrollPos) current = sec;
    });
    navLinks.forEach(function (link) {
      var isActive = link.getAttribute('href') === '#' + current.id;
      link.classList.toggle('active', isActive);
      if (isActive && catToggleLabel) catToggleLabel.textContent = link.textContent;
    });
  }

  // Категории на мобильном — выпадающий список под кнопкой ☰
  function closeCatDropdown() {
    if (!catNav) return;
    catNav.classList.remove('open');
    if (catToggle) catToggle.setAttribute('aria-expanded', 'false');
  }
  if (catToggle && catNav) {
    catToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      var isOpen = catNav.classList.toggle('open');
      catToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    catNav.addEventListener('click', function (e) {
      if (e.target.tagName === 'A') closeCatDropdown();
    });
    document.addEventListener('click', function (e) {
      if (!catNav.contains(e.target) && e.target !== catToggle && !catToggle.contains(e.target)) {
        closeCatDropdown();
      }
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeCatDropdown();
    });
  }

  // Скрываем панель поиска/категорий при скролле вниз, показываем при скролле вверх
  var lastScrollY = window.scrollY;
  var scrollThreshold = 8;
  function handleScroll() {
    var currentY = window.scrollY;
    var toolsHeight = toolsEl ? toolsEl.offsetHeight : 0;
    if (toolsEl) {
      if (currentY <= toolsHeight) {
        toolsEl.classList.remove('tools-hidden');
      } else if (currentY > lastScrollY + scrollThreshold) {
        toolsEl.classList.add('tools-hidden');
      } else if (currentY < lastScrollY - scrollThreshold) {
        toolsEl.classList.remove('tools-hidden');
      }
    }
    lastScrollY = currentY;
    setActiveNav();
  }
  window.addEventListener('scroll', handleScroll, { passive: true });
  setActiveNav();

  var searchInput = document.getElementById('dish-search');
  var noResults = document.getElementById('no-results');
  if (searchInput) {
    searchInput.addEventListener('input', function () {
      var q = searchInput.value.trim().toLowerCase();
      var anyVisible = false;
      sections.forEach(function (sec) {
        var sectionHasMatch = false;
        sec.querySelectorAll('.dish-card').forEach(function (card) {
          var name = (card.dataset.name || '').toLowerCase();
          var match = q === '' || name.indexOf(q) !== -1;
          card.style.display = match ? '' : 'none';
          if (match) sectionHasMatch = true;
        });
        sec.style.display = (q === '' || sectionHasMatch) ? '' : 'none';
        if (sectionHasMatch || q === '') anyVisible = true;
      });
      if (noResults) noResults.style.display = anyVisible ? 'none' : 'block';
    });
  }

  // Модальное окно с полным описанием блюда
  var overlay = document.getElementById('dish-modal-overlay');
  var box = document.getElementById('dish-modal-box');
  var closeBtn = document.getElementById('dish-modal-close');
  var modalImg = document.getElementById('modal-img');
  var modalBadge = document.getElementById('modal-badge');
  var modalName = document.getElementById('modal-name');
  var modalCookTime = document.getElementById('modal-cook-time');
  var modalDescription = document.getElementById('modal-description');
  var modalPrice = document.getElementById('modal-price');

  function openDishModal(dish) {
    if (!overlay) return;
    modalName.textContent = dish.name || '';
    modalDescription.textContent = dish.description || '';
    modalPrice.textContent = dish.price || '';

    if (dish.image) {
      modalImg.src = dish.image;
      modalImg.alt = dish.name || '';
      modalImg.style.display = '';
    } else {
      modalImg.removeAttribute('src');
      modalImg.style.display = 'none';
    }

    if (dish.cookTime) {
      modalCookTime.textContent = '⏱ ' + dish.cookTime;
      modalCookTime.style.display = '';
    } else {
      modalCookTime.style.display = 'none';
    }

    if (dish.isFeatured) {
      modalBadge.textContent = dish.hitLabel || 'Хит';
      modalBadge.style.display = '';
    } else {
      modalBadge.style.display = 'none';
    }

    overlay.classList.add('open');
    document.body.classList.add('modal-open');
  }

  function closeDishModal() {
    if (!overlay) return;
    overlay.classList.remove('open');
    document.body.classList.remove('modal-open');
  }

  document.querySelectorAll('.dish-card').forEach(function (card) {
    card.addEventListener('click', function () {
      var raw = card.dataset.dish;
      if (!raw) return;
      try {
        openDishModal(JSON.parse(raw));
      } catch (e) {
        /* некорректные данные блюда — просто не открываем модалку */
      }
    });
    card.addEventListener('keydown', function (e) {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        card.click();
      }
    });
  });

  if (closeBtn) closeBtn.addEventListener('click', closeDishModal);
  if (overlay) {
    overlay.addEventListener('click', function (e) {
      if (e.target === overlay) closeDishModal();
    });
  }
  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && overlay && overlay.classList.contains('open')) {
      closeDishModal();
    }
  });
});
