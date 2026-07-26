document.addEventListener('DOMContentLoaded', function () {
  var csrfInput = document.querySelector('input[name=csrf_token]');
  var csrfToken = csrfInput ? csrfInput.value : '';

  document.querySelectorAll('[data-translate-from]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      var fromId = btn.dataset.translateFrom;
      var targets = btn.dataset.translateTargets.split(',');
      var context = btn.dataset.translateContext || '';
      var fromInput = document.getElementById(fromId);
      var status = document.querySelector('[data-status-for="' + fromId + '"]');
      var text = fromInput ? fromInput.value.trim() : '';

      if (!text) {
        if (status) status.textContent = 'Сначала заполните русский текст';
        return;
      }

      btn.disabled = true;
      btn.textContent = 'Переводим...';
      if (status) status.textContent = '';

      var body = new URLSearchParams();
      body.set('csrf_token', csrfToken);
      body.set('text', text);
      body.set('context', context);

      fetch('ajax_translate.php', { method: 'POST', body: body })
        .then(function (r) { return r.json(); })
        .then(function (data) {
          if (data.error) {
            if (status) status.textContent = 'Ошибка: ' + data.error;
            return;
          }
          targets.forEach(function (targetId) {
            var suffix = targetId.split('_').pop(); // kg или en
            var input = document.getElementById(targetId);
            if (input && data[suffix] !== undefined) {
              input.value = data[suffix];
            }
          });
          if (status) status.textContent = 'Переведено. Проверьте текст перед сохранением.';
        })
        .catch(function (err) {
          if (status) status.textContent = 'Ошибка запроса: ' + err;
        })
        .finally(function () {
          btn.disabled = false;
          btn.textContent = 'Перевести на kg/en';
        });
    });
  });

  var imageInput = document.getElementById('image');
  var preview = document.getElementById('image-preview');
  if (imageInput && preview) {
    imageInput.addEventListener('change', function () {
      var file = imageInput.files[0];
      if (file) {
        preview.src = URL.createObjectURL(file);
        preview.style.display = 'block';
      }
    });
  }

  // Боковое меню на мобильном — выпадающий список под кнопкой ☰
  var navToggle = document.getElementById('admin-nav-toggle');
  var adminNav = document.getElementById('admin-nav');
  if (navToggle && adminNav) {
    function closeAdminNav() {
      adminNav.classList.remove('open');
      navToggle.setAttribute('aria-expanded', 'false');
    }
    navToggle.addEventListener('click', function (e) {
      e.stopPropagation();
      var isOpen = adminNav.classList.toggle('open');
      navToggle.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });
    document.addEventListener('click', function (e) {
      if (!adminNav.contains(e.target) && e.target !== navToggle) closeAdminNav();
    });
    document.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') closeAdminNav();
    });
  }
});
