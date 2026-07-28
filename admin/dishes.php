<?php
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Блюда';
$active = 'dishes';
$flash = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    require_csrf();
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare('SELECT image FROM dishes WHERE id = ?');
    $stmt->execute([$id]);
    $dish = $stmt->fetch();
    if ($dish) {
        delete_dish_image($dish['image']);
        $pdo->prepare('DELETE FROM dishes WHERE id = ?')->execute([$id]);
        $flash = 'Блюдо удалено';
    }
}

$categoryFilter = isset($_GET['category_id']) ? (int)$_GET['category_id'] : 0;
$categories = $pdo->query('SELECT * FROM categories ORDER BY sort_order, id')->fetchAll();

$sql = 'SELECT d.*, c.name_ru AS cat_name FROM dishes d JOIN categories c ON c.id = d.category_id';
$params = [];
if ($categoryFilter) {
    $sql .= ' WHERE d.category_id = ?';
    $params[] = $categoryFilter;
}
$sql .= ' ORDER BY c.sort_order, d.sort_order, d.id';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$dishes = $stmt->fetchAll();

require __DIR__ . '/partials/header.php';
?>

<?php if ($flash): ?><div class="flash flash-success"><?= e($flash) ?></div><?php endif; ?>

<p>
  <a class="btn btn-primary" href="dish_form.php">+ Добавить блюдо</a>
</p>

<div class="panel">
  <form method="get" style="margin-bottom:16px;">
    <div class="field" style="max-width:280px;">
      <label>Фильтр по категории</label>
      <select name="category_id" onchange="this.form.submit()">
        <option value="0">Все категории</option>
        <?php foreach ($categories as $cat): ?>
          <option value="<?= (int)$cat['id'] ?>" <?= $categoryFilter === (int)$cat['id'] ? 'selected' : '' ?>><?= e($cat['name_ru']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  </form>

  <div class="table-scroll">
  <table class="data-table">
    <thead>
      <tr><th>Фото</th><th>Название (ru)</th><th>Категория</th><th>Цена</th><th>Время</th><th>Метки</th><th>Активно</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($dishes as $dish): ?>
        <tr>
          <td><?php if ($dish['image']): ?><img class="thumb" src="../uploads/dishes/<?= e($dish['image']) ?>"><?php else: ?>—<?php endif; ?></td>
          <td><?= e($dish['name_ru']) ?></td>
          <td><?= e($dish['cat_name']) ?></td>
          <td><?= number_format((float)$dish['price'], 0, '.', ' ') ?></td>
          <td><?= $dish['cook_time_minutes'] ? (int)$dish['cook_time_minutes'] . ' мин' : '—' ?></td>
          <td>
            <?= $dish['is_featured'] ? '⭐' : '' ?><?= $dish['is_spicy'] ? '🌶' : '' ?><?= $dish['is_vegan'] ? '🌱' : '' ?><?= $dish['is_new'] ? '✨' : '' ?><?= $dish['is_promo'] ? '🔥' : '' ?>
          </td>
          <td><?= $dish['is_active'] ? '✅' : '—' ?></td>
          <td class="actions-cell">
            <a class="btn btn-secondary btn-sm" href="dish_form.php?id=<?= (int)$dish['id'] ?>">Изменить</a>
            <form method="post" onsubmit="return confirm('Удалить блюдо «<?= e($dish['name_ru']) ?>»?');" style="display:inline;">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$dish['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit">Удалить</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($dishes)): ?>
        <tr><td colspan="8">Блюд не найдено</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
