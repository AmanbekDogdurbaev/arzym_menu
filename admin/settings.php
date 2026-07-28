<?php
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Настройки';
$active = 'settings';
$flash = '';
$error = '';

$settings = $pdo->query('SELECT * FROM settings WHERE id = 1')->fetch();
if (!$settings) {
    $pdo->exec("INSERT INTO settings (id, site_name, currency) VALUES (1, 'Arzym', 'сом')");
    $settings = $pdo->query('SELECT * FROM settings WHERE id = 1')->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $form = $_POST['form'] ?? '';

    if ($form === 'settings') {
        $fields = ['site_name', 'phone', 'address_ru', 'address_kg', 'address_en', 'working_hours', 'instagram', 'whatsapp', 'currency'];
        foreach ($fields as $f) {
            $settings[$f] = trim($_POST[$f] ?? '');
        }
        try {
            $newLogo = upload_dish_image('logo');
            if ($newLogo) {
                if (!empty($settings['logo'])) delete_dish_image($settings['logo']);
                $settings['logo'] = $newLogo;
            }
            $stmt = $pdo->prepare(
                'UPDATE settings SET site_name=?, phone=?, address_ru=?, address_kg=?, address_en=?,
                 working_hours=?, instagram=?, whatsapp=?, currency=?, logo=? WHERE id=1'
            );
            $stmt->execute([
                $settings['site_name'], $settings['phone'], $settings['address_ru'], $settings['address_kg'],
                $settings['address_en'], $settings['working_hours'], $settings['instagram'], $settings['whatsapp'],
                $settings['currency'], $settings['logo'],
            ]);
            $flash = 'Настройки сохранены';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }

    if ($form === 'theme') {
        $settings['theme_bg'] = safe_hex_color($_POST['theme_bg'] ?? null, '#faf3e9');
        $settings['theme_dark'] = safe_hex_color($_POST['theme_dark'] ?? null, '#3b2417');
        $settings['theme_accent'] = safe_hex_color($_POST['theme_accent'] ?? null, '#c8932a');
        $settings['theme_text'] = safe_hex_color($_POST['theme_text'] ?? null, '#2c1e14');
        $settings['theme_font'] = in_array($_POST['theme_font'] ?? '', ['modern', 'classic', 'decorative'], true) ? $_POST['theme_font'] : 'modern';
        $settings['theme_card_style'] = in_array($_POST['theme_card_style'] ?? '', ['rounded', 'square', 'outline'], true) ? $_POST['theme_card_style'] : 'rounded';
        $settings['theme_header_style'] = in_array($_POST['theme_header_style'] ?? '', ['compact', 'hero'], true) ? $_POST['theme_header_style'] : 'compact';

        try {
            $newHero = upload_dish_image('hero_image');
            if ($newHero) {
                if (!empty($settings['hero_image'])) delete_dish_image($settings['hero_image']);
                $settings['hero_image'] = $newHero;
            }

            $newBg = upload_dish_image('bg_image');
            if ($newBg) {
                if (!empty($settings['bg_image'])) delete_dish_image($settings['bg_image']);
                $settings['bg_image'] = $newBg;
            }
            if (!empty($_POST['remove_bg_image']) && !$newBg) {
                delete_dish_image($settings['bg_image']);
                $settings['bg_image'] = null;
            }

            $stmt = $pdo->prepare(
                'UPDATE settings SET theme_bg=?, theme_dark=?, theme_accent=?, theme_text=?,
                 theme_font=?, theme_card_style=?, theme_header_style=?, hero_image=?, bg_image=? WHERE id=1'
            );
            $stmt->execute([
                $settings['theme_bg'], $settings['theme_dark'], $settings['theme_accent'], $settings['theme_text'],
                $settings['theme_font'], $settings['theme_card_style'], $settings['theme_header_style'],
                $settings['hero_image'], $settings['bg_image'],
            ]);
            $flash = 'Тема оформления сохранена';
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }

    if ($form === 'password') {
        $current = (string)($_POST['current_password'] ?? '');
        $new = (string)($_POST['new_password'] ?? '');
        $confirm = (string)($_POST['confirm_password'] ?? '');

        $stmt = $pdo->prepare('SELECT * FROM admins WHERE id = ?');
        $stmt->execute([$_SESSION['admin_id']]);
        $admin = $stmt->fetch();

        if (!$admin || !password_verify($current, $admin['password_hash'])) {
            $error = 'Текущий пароль указан неверно';
        } elseif (strlen($new) < 6) {
            $error = 'Новый пароль должен быть не короче 6 символов';
        } elseif ($new !== $confirm) {
            $error = 'Пароли не совпадают';
        } else {
            $hash = password_hash($new, PASSWORD_DEFAULT);
            $pdo->prepare('UPDATE admins SET password_hash = ? WHERE id = ?')->execute([$hash, $_SESSION['admin_id']]);
            $flash = 'Пароль успешно изменён';
        }
    }
}

require __DIR__ . '/partials/header.php';
?>

<?php if ($flash): ?><div class="flash flash-success"><?= e($flash) ?></div><?php endif; ?>
<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

<div class="panel">
  <h3 style="margin-top:0;">Данные сайта</h3>
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="form" value="settings">

    <div class="form-grid">
      <div class="field"><label>Название заведения</label><input type="text" name="site_name" value="<?= e($settings['site_name']) ?>"></div>
      <div class="field"><label>Телефон</label><input type="text" name="phone" value="<?= e($settings['phone']) ?>"></div>
      <div class="field"><label>Адрес (ru)</label><input type="text" name="address_ru" value="<?= e($settings['address_ru']) ?>"></div>
      <div class="field"><label>Адрес (kg)</label><input type="text" name="address_kg" value="<?= e($settings['address_kg']) ?>"></div>
      <div class="field"><label>Адрес (en)</label><input type="text" name="address_en" value="<?= e($settings['address_en']) ?>"></div>
      <div class="field"><label>Часы работы</label><input type="text" name="working_hours" value="<?= e($settings['working_hours']) ?>" placeholder="09:00 - 23:00"></div>
      <div class="field"><label>Instagram (ссылка)</label><input type="text" name="instagram" value="<?= e($settings['instagram']) ?>"></div>
      <div class="field"><label>WhatsApp (ссылка)</label><input type="text" name="whatsapp" value="<?= e($settings['whatsapp']) ?>"></div>
      <div class="field"><label>Валюта</label><input type="text" name="currency" value="<?= e($settings['currency']) ?>"></div>
    </div>

    <div class="field" style="margin-top:10px;">
      <label>Логотип</label>
      <?php if (!empty($settings['logo'])): ?>
        <img class="thumb-preview" src="../uploads/dishes/<?= e($settings['logo']) ?>">
      <?php endif; ?>
      <input type="file" name="logo" accept="image/*" capture="environment">
    </div>

    <div class="form-actions"><button class="btn btn-primary" type="submit">Сохранить</button></div>
  </form>
</div>

<div class="panel">
  <h3 style="margin-top:0;">Тема оформления меню</h3>
  <div class="theme-editor">
    <form method="post" enctype="multipart/form-data" id="theme-form">
      <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="form" value="theme">

      <div class="form-grid">
        <div class="field">
          <label>Цвет фона</label>
          <input type="color" name="theme_bg" id="th_bg" value="<?= e($settings['theme_bg'] ?: '#faf3e9') ?>">
        </div>
        <div class="field">
          <label>Цвет шапки/подвала</label>
          <input type="color" name="theme_dark" id="th_dark" value="<?= e($settings['theme_dark'] ?: '#3b2417') ?>">
        </div>
        <div class="field">
          <label>Акцентный цвет (цены, кнопки, хиты)</label>
          <input type="color" name="theme_accent" id="th_accent" value="<?= e($settings['theme_accent'] ?: '#c8932a') ?>">
        </div>
        <div class="field">
          <label>Цвет текста</label>
          <input type="color" name="theme_text" id="th_text" value="<?= e($settings['theme_text'] ?: '#2c1e14') ?>">
        </div>

        <div class="field">
          <label>Шрифт</label>
          <select name="theme_font" id="th_font">
            <option value="modern" <?= $settings['theme_font'] === 'modern' ? 'selected' : '' ?>>Современный (Segoe UI)</option>
            <option value="classic" <?= $settings['theme_font'] === 'classic' ? 'selected' : '' ?>>Классический (Georgia)</option>
            <option value="decorative" <?= $settings['theme_font'] === 'decorative' ? 'selected' : '' ?>>Декоративный (Playfair Display)</option>
          </select>
        </div>
        <div class="field">
          <label>Стиль карточек блюд</label>
          <select name="theme_card_style" id="th_card">
            <option value="rounded" <?= $settings['theme_card_style'] === 'rounded' ? 'selected' : '' ?>>Скруглённые с тенью</option>
            <option value="square" <?= $settings['theme_card_style'] === 'square' ? 'selected' : '' ?>>Прямые углы, без тени</option>
            <option value="outline" <?= $settings['theme_card_style'] === 'outline' ? 'selected' : '' ?>>С контурной рамкой</option>
          </select>
        </div>
        <div class="field">
          <label>Вид шапки</label>
          <select name="theme_header_style" id="th_header">
            <option value="compact" <?= $settings['theme_header_style'] === 'compact' ? 'selected' : '' ?>>Компактная</option>
            <option value="hero" <?= $settings['theme_header_style'] === 'hero' ? 'selected' : '' ?>>Большая, с фото-баннером</option>
          </select>
        </div>
        <div class="field">
          <label>Фото для шапки-баннера (если выбран вид «с фото-баннером»)</label>
          <input type="file" name="hero_image" id="th_hero_file" accept="image/*" capture="environment">
        </div>
        <div class="field full">
          <label>Фоновое изображение всей страницы меню (необязательно)</label>
          <?php if (!empty($settings['bg_image'])): ?>
            <img class="thumb-preview" src="../uploads/dishes/<?= e($settings['bg_image']) ?>">
            <label style="font-weight:400;"><input type="checkbox" name="remove_bg_image" value="1"> Убрать фоновое изображение</label>
          <?php endif; ?>
          <input type="file" name="bg_image" id="th_bg_file" accept="image/*" capture="environment">
          <small style="color:#6b5c4f;">Фото будет растянуто на весь фон меню с лёгким затемнением в цвет фона — для читаемости текста поверх.</small>
        </div>
      </div>

      <div class="form-actions"><button class="btn btn-primary" type="submit">Сохранить тему</button></div>
    </form>

    <div class="theme-preview-wrap">
      <div class="theme-preview-label">Живой предпросмотр</div>
      <div class="theme-preview" id="tp-root">
        <div class="tp-header" id="tp-header">
          <div class="tp-brand" id="tp-brand">Arzym</div>
          <div class="tp-lang"><span class="tp-lang-item active" id="tp-lang-active">Рус</span><span class="tp-lang-item">Kg</span></div>
        </div>
        <div class="tp-body" id="tp-body">
          <div class="tp-card" id="tp-card">
            <div class="tp-img">Фото</div>
            <div class="tp-info">
              <strong id="tp-name">Бешбармак</strong>
              <p id="tp-desc">Баранина, домашняя лапша, лук</p>
              <div class="tp-price" id="tp-price">450 сом</div>
            </div>
            <span class="tp-badge" id="tp-badge">Хит</span>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="panel">
  <h3 style="margin-top:0;">Смена пароля администратора</h3>
  <form method="post" style="max-width:360px;">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
    <input type="hidden" name="form" value="password">
    <div class="field"><label>Текущий пароль</label><input type="password" name="current_password" required></div>
    <div class="field"><label>Новый пароль</label><input type="password" name="new_password" required></div>
    <div class="field"><label>Повторите новый пароль</label><input type="password" name="confirm_password" required></div>
    <div class="form-actions"><button class="btn btn-primary" type="submit">Изменить пароль</button></div>
  </form>
</div>

<div class="panel">
  <h3 style="margin-top:0;">Автоперевод</h3>
  <p>Текущий провайдер: <strong><?= e(AI_PROVIDER) ?></strong>.</p>
  <?php if (AI_PROVIDER === 'google_free'): ?>
    <p>Используется бесплатный перевод (без ключа и без оплаты). Это неофициальный публичный сервис — в редких
    случаях перевод может быть неточным или временно недоступным. Всегда проверяйте и при необходимости
    поправьте текст на kg/en вручную перед сохранением.</p>
  <?php endif; ?>
  <p>Провайдер и ключи API меняются в файле <code>config/config.php</code> (константа <code>AI_PROVIDER</code>:
  <code>google_free</code> / <code>anthropic</code> / <code>openai</code>), редактировать через админку не нужно —
  это делается один раз при настройке сервера.</p>
</div>

<script>
(function () {
  var bg = document.getElementById('th_bg');
  var dark = document.getElementById('th_dark');
  var accent = document.getElementById('th_accent');
  var text = document.getElementById('th_text');
  var fontSel = document.getElementById('th_font');
  var cardSel = document.getElementById('th_card');
  var headerSel = document.getElementById('th_header');
  var heroFile = document.getElementById('th_hero_file');
  var bgFile = document.getElementById('th_bg_file');

  var root = document.getElementById('tp-root');
  var header = document.getElementById('tp-header');
  var body = document.getElementById('tp-body');
  var card = document.getElementById('tp-card');
  var badge = document.getElementById('tp-badge');
  var price = document.getElementById('tp-price');
  var langActive = document.getElementById('tp-lang-active');

  var fontStacks = {
    modern: "'Segoe UI', Tahoma, Arial, sans-serif",
    classic: "Georgia, 'Times New Roman', Times, serif",
    decorative: "'Playfair Display', Georgia, serif",
  };

  function mixWhite(hex, amount) {
    var c = hex.replace('#', '');
    if (c.length === 3) c = c.split('').map(function (ch) { return ch + ch; }).join('');
    var r = parseInt(c.substr(0, 2), 16), g = parseInt(c.substr(2, 2), 16), b = parseInt(c.substr(4, 2), 16);
    r = Math.round(r + (255 - r) * amount);
    g = Math.round(g + (255 - g) * amount);
    b = Math.round(b + (255 - b) * amount);
    return 'rgb(' + r + ',' + g + ',' + b + ')';
  }

  function update() {
    var bgVal = bg.value, darkVal = dark.value, accentVal = accent.value, textVal = text.value;
    var cardBg = mixWhite(bgVal, 0.18);
    var borderCol = mixWhite(textVal, 0.86);

    root.style.background = bgVal;
    root.style.color = textVal;
    root.style.fontFamily = fontStacks[fontSel.value] || fontStacks.modern;

    header.style.background = darkVal;
    body.style.background = bgVal;

    langActive.style.background = accentVal;
    langActive.style.borderColor = accentVal;

    card.style.background = cardBg;
    price.style.color = accentVal;
    badge.style.background = accentVal;

    if (headerSel.value === 'hero') {
      header.style.minHeight = '150px';
      header.style.alignItems = 'flex-end';
    } else {
      header.style.minHeight = '';
      header.style.alignItems = 'center';
    }

    if (cardSel.value === 'square') {
      card.style.borderRadius = '0';
      card.style.boxShadow = 'none';
      card.style.border = '1px solid ' + borderCol;
    } else if (cardSel.value === 'outline') {
      card.style.borderRadius = '10px';
      card.style.boxShadow = 'none';
      card.style.border = '2px solid ' + borderCol;
    } else {
      card.style.borderRadius = '14px';
      card.style.boxShadow = '0 4px 16px rgba(0,0,0,0.08)';
      card.style.border = 'none';
    }
  }

  [bg, dark, accent, text, fontSel, cardSel, headerSel].forEach(function (el) {
    el.addEventListener('input', update);
    el.addEventListener('change', update);
  });

  if (heroFile) {
    heroFile.addEventListener('change', function () {
      var file = heroFile.files[0];
      if (file && headerSel.value === 'hero') {
        header.style.backgroundImage = 'url(' + URL.createObjectURL(file) + ')';
        header.style.backgroundSize = 'cover';
        header.style.backgroundPosition = 'center';
      }
    });
  }

  if (bgFile) {
    bgFile.addEventListener('change', function () {
      var file = bgFile.files[0];
      if (file) {
        root.style.backgroundImage = 'url(' + URL.createObjectURL(file) + ')';
        root.style.backgroundSize = 'cover';
        root.style.backgroundPosition = 'center';
        body.style.background = 'color-mix(in srgb, ' + bg.value + ' 82%, transparent)';
      }
    });
  }

  update();
})();
</script>

<?php require __DIR__ . '/partials/footer.php'; ?>
