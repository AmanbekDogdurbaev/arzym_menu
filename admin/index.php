<?php
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Главная';
$active = 'dashboard';

$categoriesCount = (int)$pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
$dishesCount = (int)$pdo->query('SELECT COUNT(*) FROM dishes')->fetchColumn();
$activeDishesCount = (int)$pdo->query('SELECT COUNT(*) FROM dishes WHERE is_active = 1')->fetchColumn();
$missingTranslations = (int)$pdo->query(
    "SELECT COUNT(*) FROM dishes WHERE name_kg = '' OR name_kg IS NULL OR name_en = '' OR name_en IS NULL"
)->fetchColumn();

require __DIR__ . '/partials/header.php';
?>
<div class="stats-row">
  <div class="stat-box"><div class="num"><?= $categoriesCount ?></div><div class="label">Категорий</div></div>
  <div class="stat-box"><div class="num"><?= $dishesCount ?></div><div class="label">Всего блюд</div></div>
  <div class="stat-box"><div class="num"><?= $activeDishesCount ?></div><div class="label">Активных блюд</div></div>
  <div class="stat-box"><div class="num"><?= $missingTranslations ?></div><div class="label">Без перевода kg/en</div></div>
</div>

<div class="panel">
  <h3 style="margin-top:0;">Быстрые действия</h3>
  <p><a class="btn btn-primary" href="dish_form.php">+ Добавить блюдо</a>
     <a class="btn btn-secondary" href="category_form.php">+ Добавить категорию</a>
     <a class="btn btn-secondary" href="../index.php" target="_blank">Открыть меню сайта ↗</a></p>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
