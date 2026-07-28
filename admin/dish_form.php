<?php
require_once __DIR__ . '/../includes/auth.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : (isset($_POST['id']) ? (int)$_POST['id'] : 0);
$dish = [
    'category_id' => '', 'name_ru' => '', 'name_kg' => '', 'name_en' => '',
    'description_ru' => '', 'description_kg' => '', 'description_en' => '',
    'price' => '', 'cook_time_minutes' => '', 'image' => null, 'is_active' => 1, 'is_featured' => 0,
    'is_spicy' => 0, 'is_vegan' => 0, 'is_new' => 0, 'is_promo' => 0, 'sort_order' => 0,
];
$error = '';

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM dishes WHERE id = ?');
    $stmt->execute([$id]);
    $found = $stmt->fetch();
    if ($found) $dish = $found;
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY sort_order, id')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_csrf();
    $dish['category_id'] = (int)($_POST['category_id'] ?? 0);
    $dish['name_ru'] = trim($_POST['name_ru'] ?? '');
    $dish['name_kg'] = trim($_POST['name_kg'] ?? '');
    $dish['name_en'] = trim($_POST['name_en'] ?? '');
    $dish['description_ru'] = trim($_POST['description_ru'] ?? '');
    $dish['description_kg'] = trim($_POST['description_kg'] ?? '');
    $dish['description_en'] = trim($_POST['description_en'] ?? '');
    $dish['price'] = str_replace(',', '.', trim($_POST['price'] ?? '0'));
    $dish['cook_time_minutes'] = trim($_POST['cook_time_minutes'] ?? '') === '' ? null : (int)$_POST['cook_time_minutes'];
    $dish['is_active'] = isset($_POST['is_active']) ? 1 : 0;
    $dish['is_featured'] = isset($_POST['is_featured']) ? 1 : 0;
    $dish['is_spicy'] = isset($_POST['is_spicy']) ? 1 : 0;
    $dish['is_vegan'] = isset($_POST['is_vegan']) ? 1 : 0;
    $dish['is_new'] = isset($_POST['is_new']) ? 1 : 0;
    $dish['is_promo'] = isset($_POST['is_promo']) ? 1 : 0;
    $dish['sort_order'] = (int)($_POST['sort_order'] ?? 0);

    if ($dish['name_ru'] === '') {
        $error = 'Название на русском обязательно';
    } elseif ($dish['category_id'] <= 0) {
        $error = 'Выберите категорию';
    } elseif (!is_numeric($dish['price']) || (float)$dish['price'] < 0) {
        $error = 'Цена указана некорректно';
    } else {
        try {
            $newImage = upload_dish_image('image');
            if ($newImage) {
                if ($id && !empty($dish['image'])) {
                    delete_dish_image($dish['image']);
                }
                $dish['image'] = $newImage;
            }

            if ($id) {
                $stmt = $pdo->prepare(
                    'UPDATE dishes SET category_id=?, name_ru=?, name_kg=?, name_en=?,
                     description_ru=?, description_kg=?, description_en=?, price=?, cook_time_minutes=?, image=?,
                     is_active=?, is_featured=?, is_spicy=?, is_vegan=?, is_new=?, is_promo=?, sort_order=? WHERE id=?'
                );
                $stmt->execute([
                    $dish['category_id'], $dish['name_ru'], $dish['name_kg'], $dish['name_en'],
                    $dish['description_ru'], $dish['description_kg'], $dish['description_en'],
                    $dish['price'], $dish['cook_time_minutes'], $dish['image'], $dish['is_active'], $dish['is_featured'],
                    $dish['is_spicy'], $dish['is_vegan'], $dish['is_new'], $dish['is_promo'], $dish['sort_order'], $id,
                ]);
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO dishes (category_id, name_ru, name_kg, name_en, description_ru, description_kg,
                     description_en, price, cook_time_minutes, image, is_active, is_featured, is_spicy, is_vegan, is_new, is_promo, sort_order)
                     VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
                );
                $stmt->execute([
                    $dish['category_id'], $dish['name_ru'], $dish['name_kg'], $dish['name_en'],
                    $dish['description_ru'], $dish['description_kg'], $dish['description_en'],
                    $dish['price'], $dish['cook_time_minutes'], $dish['image'], $dish['is_active'], $dish['is_featured'],
                    $dish['is_spicy'], $dish['is_vegan'], $dish['is_new'], $dish['is_promo'], $dish['sort_order'],
                ]);
            }
            header('Location: /admin/dishes.php');
            exit;
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
    }
}

$pageTitle = $id ? 'Изменить блюдо' : 'Новое блюдо';
$active = 'dishes';
require __DIR__ . '/partials/header.php';
?>

<?php if ($error): ?><div class="flash flash-error"><?= e($error) ?></div><?php endif; ?>

<div class="panel">
  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">

    <div class="field">
      <label>Категория</label>
      <select name="category_id" required>
        <option value="">— выберите —</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= (int)$cat['id'] ?>" <?= (int)$dish['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name_ru']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="lang-field-group">
      <div class="lfg-head"><h4>Название блюда</h4>
        <button type="button" class="btn btn-ai btn-sm" data-translate-from="name_ru" data-translate-targets="name_kg,name_en" data-translate-context="название блюда">Перевести на kg/en</button>
      </div>
      <div class="form-grid">
        <div class="field"><label>Русский</label><input type="text" name="name_ru" id="name_ru" value="<?= e($dish['name_ru']) ?>" required></div>
        <div class="field"><label>Кыргызча</label><input type="text" name="name_kg" id="name_kg" value="<?= e($dish['name_kg']) ?>"></div>
        <div class="field"><label>English</label><input type="text" name="name_en" id="name_en" value="<?= e($dish['name_en']) ?>"></div>
      </div>
      <span class="ai-status" data-status-for="name_ru"></span>
    </div>

    <div class="lang-field-group">
      <div class="lfg-head"><h4>Описание блюда</h4>
        <button type="button" class="btn btn-ai btn-sm" data-translate-from="description_ru" data-translate-targets="description_kg,description_en" data-translate-context="описание блюда, состав">Перевести на kg/en</button>
      </div>
      <div class="form-grid">
        <div class="field full"><label>Русский</label><textarea name="description_ru" id="description_ru"><?= e($dish['description_ru']) ?></textarea></div>
        <div class="field"><label>Кыргызча</label><textarea name="description_kg" id="description_kg"><?= e($dish['description_kg']) ?></textarea></div>
        <div class="field"><label>English</label><textarea name="description_en" id="description_en"><?= e($dish['description_en']) ?></textarea></div>
      </div>
      <span class="ai-status" data-status-for="description_ru"></span>
    </div>

    <div class="form-grid">
      <div class="field"><label>Цена (сом)</label><input type="text" inputmode="decimal" name="price" value="<?= e($dish['price']) ?>" required></div>
      <div class="field"><label>Время приготовления (мин)</label><input type="number" min="0" name="cook_time_minutes" value="<?= e((string)$dish['cook_time_minutes']) ?>" placeholder="например, 15"></div>
      <div class="field"><label>Порядок сортировки</label><input type="number" name="sort_order" value="<?= (int)$dish['sort_order'] ?>"></div>
    </div>

    <div class="field">
      <label>Фото блюда</label>
      <?php if (!empty($dish['image'])): ?>
        <img class="thumb-preview" id="image-preview" src="../uploads/dishes/<?= e($dish['image']) ?>">
      <?php else: ?>
        <img class="thumb-preview" id="image-preview" style="display:none;">
      <?php endif; ?>
      <input type="file" name="image" id="image" accept="image/*" capture="environment">
      <small style="color:#6b5c4f;">На телефоне откроется выбор: снять фото камерой или выбрать из галереи</small>
    </div>

    <div class="form-grid checkbox-grid">
      <div class="field"><label><input type="checkbox" name="is_active" <?= $dish['is_active'] ? 'checked' : '' ?>> Активно (показывать в меню)</label></div>
      <div class="field"><label><input type="checkbox" name="is_featured" <?= $dish['is_featured'] ? 'checked' : '' ?>> ⭐ Хит / рекомендуем</label></div>
      <div class="field"><label><input type="checkbox" name="is_spicy" <?= $dish['is_spicy'] ? 'checked' : '' ?>> 🌶 Острое</label></div>
      <div class="field"><label><input type="checkbox" name="is_vegan" <?= $dish['is_vegan'] ? 'checked' : '' ?>> 🌱 Веган</label></div>
      <div class="field"><label><input type="checkbox" name="is_new" <?= $dish['is_new'] ? 'checked' : '' ?>> ✨ Новинка</label></div>
      <div class="field"><label><input type="checkbox" name="is_promo" <?= $dish['is_promo'] ? 'checked' : '' ?>> 🔥 Акция</label></div>
    </div>

    <input type="hidden" name="id" value="<?= (int)$id ?>">
    <div class="form-actions">
      <button class="btn btn-primary" type="submit">Сохранить</button>
      <a class="btn btn-secondary" href="dishes.php">Отмена</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
