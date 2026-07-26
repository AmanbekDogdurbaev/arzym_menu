<?php
// Ожидается, что вызывающая страница уже подключила includes/auth.php и задала $pageTitle
?>
<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($pageTitle ?? 'Админка') ?></title>
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
<div class="admin-layout">
  <aside class="admin-sidebar">
    <div class="admin-sidebar-top">
      <div class="admin-brand">Arzym Admin</div>
      <button type="button" class="admin-nav-toggle" id="admin-nav-toggle" aria-expanded="false" aria-label="Меню">☰</button>
    </div>
    <nav id="admin-nav">
      <a href="index.php" class="<?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>">Главная</a>
      <a href="categories.php" class="<?= ($active ?? '') === 'categories' ? 'active' : '' ?>">Категории</a>
      <a href="dishes.php" class="<?= ($active ?? '') === 'dishes' ? 'active' : '' ?>">Блюда</a>
      <a href="settings.php" class="<?= ($active ?? '') === 'settings' ? 'active' : '' ?>">Настройки</a>
      <a href="../index.php" target="_blank">Посмотреть меню ↗</a>
      <a href="logout.php">Выйти</a>
    </nav>
  </aside>
  <main class="admin-main">
    <div class="admin-topbar">
      <h1><?= e($pageTitle ?? '') ?></h1>
      <span class="admin-user"><?= e($_SESSION['admin_username'] ?? '') ?></span>
    </div>
    <div class="admin-content">
