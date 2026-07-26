<?php
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !verify_csrf($_POST['csrf_token'] ?? '')) {
    http_response_code(403);
    echo json_encode(['error' => 'Ошибка проверки формы (CSRF)']);
    exit;
}

$text = trim($_POST['text'] ?? '');
$context = trim($_POST['context'] ?? 'название блюда в ресторанном меню');

if ($text === '') {
    echo json_encode(['kg' => '', 'en' => '']);
    exit;
}

try {
    $result = ai_translate($text, $context);
    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
