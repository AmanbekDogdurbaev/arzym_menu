<?php
require_once __DIR__ . '/../includes/auth.php';

$pageTitle = 'Категории';
$active = 'categories';
$flash = '';
$flashType = 'success';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    require_csrf();
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare('DELETE FROM categories WHERE id = ?');
    $stmt->execute([$id]);
    $flash = 'Категория удалена (вместе со всеми блюдами в ней)';
}

$categories = $pdo->query(
    'SELECT c.*, (SELECT COUNT(*) FROM dishes d WHERE d.category_id = c.id) AS dish_count
     FROM categories c ORDER BY sort_order, id'
)->fetchAll();

require __DIR__ . '/partials/header.php';
?>

<?php if ($flash): ?><div class="flash flash-<?= e($flashType) ?>"><?= e($flash) ?></div><?php endif; ?>

<p><a class="btn btn-primary" href="category_form.php">+ Добавить категорию</a></p>

<div class="panel">
  <div class="table-scroll">
  <table class="data-table">
    <thead>
      <tr><th>#</th><th>Название (ru)</th><th>kg</th><th>en</th><th>Блюд</th><th>Порядок</th><th>Активна</th><th></th></tr>
    </thead>
    <tbody>
      <?php foreach ($categories as $cat): ?>
        <tr>
          <td><?= (int)$cat['id'] ?></td>
          <td><?= e($cat['name_ru']) ?></td>
          <td><?= e($cat['name_kg']) ?></td>
          <td><?= e($cat['name_en']) ?></td>
          <td><?= (int)$cat['dish_count'] ?></td>
          <td><?= (int)$cat['sort_order'] ?></td>
          <td><?= $cat['is_active'] ? '✅' : '—' ?></td>
          <td class="actions-cell">
            <a class="btn btn-secondary btn-sm" href="category_form.php?id=<?= (int)$cat['id'] ?>">Изменить</a>
            <form method="post" onsubmit="return confirm('Удалить категорию «<?= e($cat['name_ru']) ?>» и все блюда в ней?');" style="display:inline;">
              <input type="hidden" name="csrf_token" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$cat['id'] ?>">
              <button class="btn btn-danger btn-sm" type="submit">Удалить</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (empty($categories)): ?>
        <tr><td colspan="8">Пока нет категорий</td></tr>
      <?php endif; ?>
    </tbody>
  </table>
  </div>
</div>

<?php require __DIR__ . '/partials/footer.php'; ?>
