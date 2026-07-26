<?php
require_once __DIR__ . '/includes/functions.php';

$lang = current_lang();
$settings = $pdo->query("SELECT * FROM settings WHERE id = 1")->fetch();
if (!$settings) {
    $settings = ['site_name' => 'Меню', 'logo' => null, 'phone' => null, 'working_hours' => null,
        'instagram' => null, 'whatsapp' => null, 'currency' => 'сом'];
}

$themeBg = safe_hex_color($settings['theme_bg'] ?? null, '#faf3e9');
$themeDark = safe_hex_color($settings['theme_dark'] ?? null, '#3b2417');
$themeAccent = safe_hex_color($settings['theme_accent'] ?? null, '#c8932a');
$themeText = safe_hex_color($settings['theme_text'] ?? null, '#2c1e14');
$themeFont = in_array($settings['theme_font'] ?? '', ['modern', 'classic', 'decorative'], true) ? $settings['theme_font'] : 'modern';
$themeCardStyle = in_array($settings['theme_card_style'] ?? '', ['rounded', 'square', 'outline'], true) ? $settings['theme_card_style'] : 'rounded';
$themeHeaderStyle = in_array($settings['theme_header_style'] ?? '', ['compact', 'hero'], true) ? $settings['theme_header_style'] : 'compact';

$bodyClasses = ['theme-font-' . $themeFont];
if ($themeCardStyle !== 'rounded') {
    $bodyClasses[] = 'card-' . $themeCardStyle;
}
if ($themeHeaderStyle === 'hero') {
    $bodyClasses[] = 'header-hero';
}

$categories = $pdo->query("SELECT * FROM categories WHERE is_active = 1 ORDER BY sort_order, id")->fetchAll();

$dishStmt = $pdo->prepare("SELECT * FROM dishes WHERE category_id = ? AND is_active = 1 ORDER BY sort_order, id");
$dishesByCategory = [];
foreach ($categories as $cat) {
    $dishStmt->execute([$cat['id']]);
    $dishesByCategory[$cat['id']] = $dishStmt->fetchAll();
}

$siteName = e($settings['site_name'] ?? 'Меню');
$currency = e($settings['currency'] ?? 'сом');
?>
<!DOCTYPE html>
<html lang="<?= e($lang) ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= $siteName ?> — Меню</title>
<link rel="stylesheet" href="assets/css/style.css">
<?php if ($themeFont === 'decorative'): ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&display=swap" rel="stylesheet">
<?php endif; ?>
<style>
:root {
  --bg: <?= $themeBg ?>;
  --dark: <?= $themeDark ?>;
  --accent: <?= $themeAccent ?>;
  --text: <?= $themeText ?>;
}
</style>
</head>
<body class="<?= e(implode(' ', $bodyClasses)) ?>">

<header class="site-header"<?php if ($themeHeaderStyle === 'hero' && !empty($settings['hero_image'])): ?> style="background-image: url('uploads/dishes/<?= e($settings['hero_image']) ?>')"<?php endif; ?>>
  <div class="header-inner">
    <div class="brand">
      <?php if (!empty($settings['logo'])): ?>
        <img src="uploads/dishes/<?= e($settings['logo']) ?>" alt="">
      <?php endif; ?>
      <span><?= $siteName ?></span>
    </div>
    <div class="lang-switch">
      <?php foreach (SITE_LANGS as $code => $label): ?>
        <a href="?lang=<?= e($code) ?>" class="<?= $lang === $code ? 'active' : '' ?>"><?= e($label) ?></a>
      <?php endforeach; ?>
    </div>
  </div>
</header>

<div class="sticky-tools" id="sticky-tools">
  <div class="search-bar">
    <input type="search" id="dish-search" placeholder="<?=
      $lang === 'kg' ? 'Тамактарды издөө...' : ($lang === 'en' ? 'Search dishes...' : 'Поиск блюд...')
    ?>">
  </div>

  <div class="cat-nav-wrap">
    <button type="button" class="cat-toggle" id="cat-toggle" aria-expanded="false">
      <span class="cat-toggle-icon">☰</span>
      <span id="cat-toggle-label"><?= !empty($categories) ? e(field($categories[0], 'name', $lang)) : '' ?></span>
      <span class="cat-toggle-caret">▾</span>
    </button>
    <nav class="cat-nav" id="cat-nav">
      <?php foreach ($categories as $cat): ?>
        <a href="#cat-<?= (int)$cat['id'] ?>"><?= e(field($cat, 'name', $lang)) ?></a>
      <?php endforeach; ?>
    </nav>
  </div>
</div>

<main>
<?php if (empty($categories)): ?>
  <p class="no-results" style="display:block;">
    <?= $lang === 'kg' ? 'Азырынча категориялар жок' : ($lang === 'en' ? 'No categories yet' : 'Пока нет категорий') ?>
  </p>
<?php endif; ?>

<?php foreach ($categories as $cat): ?>
  <section class="menu-section" id="cat-<?= (int)$cat['id'] ?>">
    <h2><?= e(field($cat, 'name', $lang)) ?></h2>
    <div class="dishes-grid">
      <?php foreach ($dishesByCategory[$cat['id']] as $dish):
        $dishData = [
            'name' => field($dish, 'name', $lang),
            'description' => field($dish, 'description', $lang),
            'price' => number_format((float)$dish['price'], 0, '.', ' ') . ' ' . $currency,
            'image' => !empty($dish['image']) ? 'uploads/dishes/' . $dish['image'] : '',
            'cookTime' => format_cook_time($dish['cook_time_minutes'] ?? 0, $lang),
            'isFeatured' => (bool)$dish['is_featured'],
            'hitLabel' => 'Хит',
        ];
      ?>
        <div class="dish-card" data-name="<?= e(field($dish, 'name', $lang)) ?>" data-dish="<?= e(json_encode($dishData, JSON_UNESCAPED_UNICODE)) ?>" tabindex="0" role="button">
          <?php if ($dish['is_featured']): ?>
            <span class="badge-hit"><?= $lang === 'kg' ? 'Хит' : ($lang === 'en' ? 'Hit' : 'Хит') ?></span>
          <?php endif; ?>
          <?php if (!empty($dish['image'])): ?>
            <img class="dish-img" src="uploads/dishes/<?= e($dish['image']) ?>" alt="<?= e(field($dish, 'name', $lang)) ?>">
          <?php else: ?>
            <div class="dish-img placeholder"><?= $lang === 'kg' ? 'Сүрөт жок' : ($lang === 'en' ? 'No photo' : 'Нет фото') ?></div>
          <?php endif; ?>
          <div class="dish-body">
            <h3><?= e(field($dish, 'name', $lang)) ?></h3>
            <?php if (!empty(field($dish, 'description', $lang))): ?>
              <p><?= e(field($dish, 'description', $lang)) ?></p>
            <?php endif; ?>
            <div class="dish-price"><?= number_format((float)$dish['price'], 0, '.', ' ') ?> <?= $currency ?></div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (empty($dishesByCategory[$cat['id']])): ?>
        <p class="no-results" style="display:block;"><?= $lang === 'kg' ? 'Бул категорияда тамак жок' : ($lang === 'en' ? 'No dishes in this category' : 'В этой категории пока нет блюд') ?></p>
      <?php endif; ?>
    </div>
  </section>
<?php endforeach; ?>

<p class="no-results" id="no-results"><?= $lang === 'kg' ? 'Эч нерсе табылган жок' : ($lang === 'en' ? 'Nothing found' : 'Ничего не найдено') ?></p>
</main>

<footer class="site-footer">
  <div class="footer-inner">
    <div class="contacts">
      <?php if (!empty($settings['phone'])): ?><span>☎ <?= e($settings['phone']) ?></span><?php endif; ?>
      <?php if (!empty($settings['working_hours'])): ?><span>🕒 <?= e($settings['working_hours']) ?></span><?php endif; ?>
      <?php $addr = field($settings, 'address', $lang); if (!empty($addr)): ?><span>📍 <?= e($addr) ?></span><?php endif; ?>
    </div>
    <div>
      <?php if (!empty($settings['instagram'])): ?><a class="social" href="<?= e($settings['instagram']) ?>" target="_blank" rel="noopener">Instagram</a><?php endif; ?>
      <?php if (!empty($settings['whatsapp'])): ?> · <a class="social" href="<?= e($settings['whatsapp']) ?>" target="_blank" rel="noopener">WhatsApp</a><?php endif; ?>
    </div>
  </div>
</footer>

<div class="modal-overlay" id="dish-modal-overlay">
  <div class="modal-box" id="dish-modal-box">
    <button class="modal-close" id="dish-modal-close" aria-label="Закрыть" type="button">&times;</button>
    <div class="modal-img-wrap">
      <img class="modal-img" id="modal-img" src="" alt="">
      <span class="badge-hit modal-badge" id="modal-badge" style="display:none;">Хит</span>
    </div>
    <div class="modal-content">
      <h2 id="modal-name"></h2>
      <div class="modal-meta">
        <span class="modal-cook-time" id="modal-cook-time" style="display:none;"></span>
      </div>
      <p id="modal-description"></p>
      <div class="modal-price" id="modal-price"></div>
    </div>
  </div>
</div>

<script src="assets/js/main.js"></script>
</body>
</html>
