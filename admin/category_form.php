<?php
require_once __DIR__ . '/../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$category = ['name_ru' => '', 'name_kg' => '', 'name_en' => '', 'sort_order' => 0, 'is_active' => 1];
$error = '';

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) $category = $found;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $category['name_ru'] = trim($_POST['name_ru'] ?? '');
    $category['name_kg'] = trim($_POST['name_kg'] ?? '');
    $category['name_en'] = trim($_POST['name_en'] ?? '');
    $category['sort_order'] = (int)($_POST['sort_order'] ?? 0);
    $category['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if ($category['name_ru'] === '') {
        $error = 'Название на русском обязательно';
    } else {
        if ($id) {
            $stmt = $pdo->prepare(
                'UPDATE categories SET name_ru=?, name_kg=?, name_en=?, sort_order=?, is_active=? WHERE id=?'
            );
            $stmt->execute([$category['name_ru'], $category['name_kg'], $category['name_en'], $category['sort_order'], $category['is_active'], $id]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO categories (name_ru, name_kg, name_en, sort_order, is_active) VALUES (?,?,?,?,?)'
            );
            $stmt->execute([$category['name_ru'], $category['name_kg'], $category['name_en'], $category['sort_order'], $category['is_active']]);
        }
        header('Location: /admin/categories.php');
        exit;
    }
}

$pageTitle = $id ? 'Изменить категорию' : 'Новая категория';
$active = 'categories';
require __DIR__ . '/partials/header.php';
?>

<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

<div class="panel">
  <form method="post">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <div class="lang-field-group">
      <div class="lfg-head"><h4>Название категории</h4>
        <button type="button" class="btn btn-ai btn-sm" data-translate-from="name_ru" data-translate-targets="name_kg,name_en">Перевести на kg/en</button>
      </div>
      <div class="form-grid">
        <div class="field"><label>Русский</label><input type="text" name="name_ru" id="name_ru" value="<?= e($category['name_ru']) ?>" required></div>
        <div class="field"><label>Кыргызча</label><input type="text" name="name_kg" id="name_kg" value="<?= e($category['name_kg']) ?>"></div>
        <div class="field"><label>English</label><input type="text" name="name_en" id="name_en" value="<?= e($category['name_en']) ?>"></div>
      </div>
      <span class="ai-status" data-status-for="name_ru"></span>
    </div>

    <div class="form-grid">
      <div class="field"><label>Порядок сортировки</label><input type="number" name="sort_order" value="<?= (int)$category['sort_order'] ?>"></div>
      <div class="field"><label><input type="checkbox" name="is_active" <?= $category['is_active'] ? 'checked' : '' ?>> Активна (показывать в меню)</label></div>
    </div>

    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Сохранить</button>
      <a class="btn btn-secondary" href="categories.php">Отмена</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
