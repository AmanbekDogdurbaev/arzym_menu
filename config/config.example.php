<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', '1'); // на боевом хостинге поставить '0'

define('DB_HOST', 'localhost');
define('DB_NAME', 'arzym_menu');
define('DB_USER', 'root');
define('DB_PASS', '');

define('UPLOAD_DIR', __DIR__ . '/../uploads/dishes/');
define('UPLOAD_URL', '/uploads/dishes/');

// Провайдер автоперевода: 'google_free' (бесплатно, без ключа), 'anthropic' или 'openai' (платно, нужен ключ)
define('AI_PROVIDER', 'google_free');
define('ANTHROPIC_API_KEY', ''); // нужен только если AI_PROVIDER = 'anthropic'
define('OPENAI_API_KEY', '');    // нужен только если AI_PROVIDER = 'openai'

define('SITE_LANGS', ['ru' => 'Рус', 'kg' => 'Кырг', 'en' => 'Eng']);
define('DEFAULT_LANG', 'ru');

try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );
} catch (PDOException $e) {
    die('Ошибка подключения к базе данных. Проверьте, что MySQL запущен и база arzym_menu создана. (' . $e->getMessage() . ')');
}
