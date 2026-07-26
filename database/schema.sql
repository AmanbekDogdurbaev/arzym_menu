-- Схема базы данных для электронного меню
-- Кодировка: utf8mb4 (поддержка ru/kg/en без проблем)

CREATE DATABASE IF NOT EXISTS `arzym_menu` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `arzym_menu`;

-- Администраторы
CREATE TABLE `admins` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Логин: admin / admin123 (ОБЯЗАТЕЛЬНО смените пароль после первого входа в разделе "Настройки")
INSERT INTO `admins` (`username`, `password_hash`) VALUES
('admin', '$2y$10$/x60Odciqqj.v6XaUr8/ZukI6By7iowUqBo8TQb5G0WqWOX9T5bCS');

-- Категории меню
CREATE TABLE `categories` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `name_ru` VARCHAR(150) NOT NULL,
  `name_kg` VARCHAR(150) DEFAULT NULL,
  `name_en` VARCHAR(150) DEFAULT NULL,
  `sort_order` INT DEFAULT 0,
  `is_active` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Блюда
CREATE TABLE `dishes` (
  `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `category_id` INT UNSIGNED NOT NULL,
  `name_ru` VARCHAR(200) NOT NULL,
  `name_kg` VARCHAR(200) DEFAULT NULL,
  `name_en` VARCHAR(200) DEFAULT NULL,
  `description_ru` TEXT DEFAULT NULL,
  `description_kg` TEXT DEFAULT NULL,
  `description_en` TEXT DEFAULT NULL,
  `price` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `cook_time_minutes` SMALLINT UNSIGNED DEFAULT NULL,
  `image` VARCHAR(255) DEFAULT NULL,
  `is_active` TINYINT(1) DEFAULT 1,
  `is_featured` TINYINT(1) DEFAULT 0,
  `sort_order` INT DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`category_id`) REFERENCES `categories`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Настройки сайта (одна строка)
CREATE TABLE `settings` (
  `id` INT UNSIGNED PRIMARY KEY DEFAULT 1,
  `site_name` VARCHAR(150) DEFAULT 'Arzym',
  `phone` VARCHAR(50) DEFAULT NULL,
  `address_ru` VARCHAR(255) DEFAULT NULL,
  `address_kg` VARCHAR(255) DEFAULT NULL,
  `address_en` VARCHAR(255) DEFAULT NULL,
  `working_hours` VARCHAR(150) DEFAULT NULL,
  `instagram` VARCHAR(255) DEFAULT NULL,
  `whatsapp` VARCHAR(255) DEFAULT NULL,
  `logo` VARCHAR(255) DEFAULT NULL,
  `currency` VARCHAR(10) DEFAULT 'сом',
  `theme_bg` VARCHAR(7) DEFAULT '#faf3e9',
  `theme_dark` VARCHAR(7) DEFAULT '#3b2417',
  `theme_accent` VARCHAR(7) DEFAULT '#c8932a',
  `theme_text` VARCHAR(7) DEFAULT '#2c1e14',
  `theme_font` VARCHAR(20) DEFAULT 'modern',
  `theme_card_style` VARCHAR(20) DEFAULT 'rounded',
  `theme_header_style` VARCHAR(20) DEFAULT 'compact',
  `hero_image` VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

INSERT INTO `settings` (`id`, `site_name`, `currency`) VALUES (1, 'Arzym', 'сом');

-- Демонстрационные категории и блюда
INSERT INTO `categories` (`name_ru`, `name_kg`, `name_en`, `sort_order`) VALUES
('Салаты', 'Салаттар', 'Salads', 1),
('Горячие блюда', 'Ысык тамактар', 'Main dishes', 2),
('Напитки', 'Ичимдиктер', 'Drinks', 3);

INSERT INTO `dishes` (`category_id`, `name_ru`, `name_kg`, `name_en`, `description_ru`, `description_kg`, `description_en`, `price`, `is_featured`, `sort_order`) VALUES
(1, 'Цезарь с курицей', 'Тооктуу Цезарь', 'Chicken Caesar', 'Куриное филе, салат романо, сыр пармезан, соус цезарь, гренки', 'Тоок филеси, романо салаты, пармезан сыры, цезарь соусу, кострюк', 'Chicken fillet, romaine lettuce, parmesan, caesar dressing, croutons', 280.00, 1, 1),
(2, 'Бешбармак', 'Бешбармак', 'Beshbarmak', 'Традиционное блюдо из отварной баранины с домашней лапшой', 'Койдун этинен жасалган үй лапшасы менен салттуу тамак', 'Traditional dish of boiled lamb with homemade noodles', 450.00, 1, 1),
(3, 'Чай', 'Чай', 'Tea', 'Черный или зеленый чай', 'Кара же жашыл чай', 'Black or green tea', 50.00, 0, 1);
