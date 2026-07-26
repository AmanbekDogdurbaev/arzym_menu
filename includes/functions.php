<?php
require_once __DIR__ . '/../config/config.php';

function e($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Проверяет, что строка — безопасный HEX-цвет (#RGB или #RRGGBB), иначе возвращает запасной цвет
function safe_hex_color($value, $fallback) {
    if (is_string($value) && preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $value)) {
        return $value;
    }
    return $fallback;
}

function current_lang() {
    $langs = array_keys(SITE_LANGS);
    if (isset($_GET['lang']) && in_array($_GET['lang'], $langs, true)) {
        $_SESSION['lang'] = $_GET['lang'];
    }
    if (isset($_SESSION['lang']) && in_array($_SESSION['lang'], $langs, true)) {
        return $_SESSION['lang'];
    }
    return DEFAULT_LANG;
}

// Возвращает значение поля на нужном языке с откатом на русский, если перевод пуст
function field($row, $base, $lang) {
    $key = $base . '_' . $lang;
    if (!empty($row[$key])) {
        return $row[$key];
    }
    return $row[$base . '_ru'] ?? '';
}

function format_cook_time($minutes, $lang) {
    $minutes = (int)$minutes;
    if ($minutes <= 0) {
        return '';
    }
    if ($lang === 'kg') {
        return $minutes . ' мүнөт';
    }
    if ($lang === 'en') {
        return $minutes . ' min';
    }
    return $minutes . ' мин';
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf($token) {
    return !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], (string)$token);
}

function require_csrf() {
    if (!verify_csrf($_POST['csrf_token'] ?? '')) {
        http_response_code(403);
        die('Ошибка проверки формы (CSRF). Обновите страницу и попробуйте снова.');
    }
}

// Загрузка изображения блюда. Возвращает имя файла или null, если файла не было.
// Бросает Exception при недопустимом файле.
function upload_dish_image($inputName) {
    if (empty($_FILES[$inputName]) || $_FILES[$inputName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$inputName];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Ошибка загрузки файла (код ' . $file['error'] . ')');
    }
    $maxSize = 15 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        throw new Exception('Файл слишком большой (максимум 15 МБ)');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime])) {
        throw new Exception('Разрешены только изображения JPG, PNG или WEBP');
    }

    if (!is_dir(UPLOAD_DIR)) {
        mkdir(UPLOAD_DIR, 0755, true);
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowed[$mime];
    $destination = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new Exception('Не удалось сохранить файл');
    }

    return $filename;
}

function delete_dish_image($filename) {
    if ($filename) {
        $path = UPLOAD_DIR . $filename;
        if (is_file($path)) {
            unlink($path);
        }
    }
}

/**
 * Переводит текст с русского на кыргызский и английский через AI API.
 * Возвращает ['kg' => '...', 'en' => '...'] или бросает Exception при ошибке.
 */
function ai_translate($text, $context = 'название блюда в ресторанном меню') {
    $text = trim($text);
    if ($text === '') {
        return ['kg' => '', 'en' => ''];
    }

    if (AI_PROVIDER === 'google_free') {
        return [
            'kg' => call_google_free($text, 'ky'),
            'en' => call_google_free($text, 'en'),
        ];
    }

    $prompt = "Ты профессиональный переводчик ресторанных меню. Переведи следующий текст ($context) "
        . "с русского языка на кыргызский и английский. Сохраняй кулинарные термины адекватно "
        . "(если точного перевода нет — транслитерируй или оставь общепринятое название). "
        . "Ответь СТРОГО в формате JSON без markdown-разметки и пояснений: {\"kg\": \"...\", \"en\": \"...\"}\n\n"
        . "Текст: \"" . $text . "\"";

    if (AI_PROVIDER === 'openai') {
        $result = call_openai($prompt);
    } else {
        $result = call_anthropic($prompt);
    }

    if (!preg_match('/\{.*\}/s', $result, $m)) {
        throw new Exception('Не удалось разобрать ответ AI: ' . $result);
    }
    $json = json_decode($m[0], true);
    if (!is_array($json) || !isset($json['kg']) || !isset($json['en'])) {
        throw new Exception('Некорректный ответ AI: ' . $result);
    }

    return ['kg' => trim($json['kg']), 'en' => trim($json['en'])];
}

// Бесплатный перевод через неофициальный публичный endpoint Google Translate (без ключа, без оплаты).
// Не гарантируется официально Google и теоретически может быть заблокирован/изменён без предупреждения —
// для платного меню с гарантией стабильности используйте AI_PROVIDER = 'anthropic' или 'openai'.
function call_google_free($text, $targetLang) {
    $url = 'https://translate.googleapis.com/translate_a/single?' . http_build_query([
        'client' => 'gtx',
        'sl' => 'ru',
        'tl' => $targetLang,
        'dt' => 't',
        'q' => $text,
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_USERAGENT => 'Mozilla/5.0',
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception('Ошибка соединения с сервисом перевода: ' . $err);
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        throw new Exception('Сервис перевода вернул ошибку (код ' . $httpCode . ')');
    }

    $data = json_decode($response, true);
    if (!is_array($data) || !isset($data[0]) || !is_array($data[0])) {
        throw new Exception('Не удалось разобрать ответ сервиса перевода');
    }

    $translated = '';
    foreach ($data[0] as $segment) {
        if (isset($segment[0])) {
            $translated .= $segment[0];
        }
    }

    return trim($translated);
}

function call_anthropic($prompt) {
    if (ANTHROPIC_API_KEY === '') {
        throw new Exception('Не задан ANTHROPIC_API_KEY в config/config.php');
    }
    $payload = json_encode([
        'model' => 'claude-haiku-4-5',
        'max_tokens' => 1024,
        'messages' => [
            ['role' => 'user', 'content' => $prompt],
        ],
    ]);

    $ch = curl_init('https://api.anthropic.com/v1/messages');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'x-api-key: ' . ANTHROPIC_API_KEY,
            'anthropic-version: 2023-06-01',
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception('Ошибка соединения с Anthropic API: ' . $err);
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);
    if ($httpCode !== 200) {
        $msg = $data['error']['message'] ?? $response;
        throw new Exception('Anthropic API вернул ошибку: ' . $msg);
    }
    return $data['content'][0]['text'] ?? '';
}

function call_openai($prompt) {
    if (OPENAI_API_KEY === '') {
        throw new Exception('Не задан OPENAI_API_KEY в config/config.php');
    }
    $payload = json_encode([
        'model' => 'gpt-4o-mini',
        'messages' => [
            ['role' => 'user', 'content' => $prompt],
        ],
        'temperature' => 0.2,
    ]);

    $ch = curl_init('https://api.openai.com/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . OPENAI_API_KEY,
        ],
        CURLOPT_TIMEOUT => 30,
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new Exception('Ошибка соединения с OpenAI API: ' . $err);
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $data = json_decode($response, true);
    if ($httpCode !== 200) {
        $msg = $data['error']['message'] ?? $response;
        throw new Exception('OpenAI API вернул ошибку: ' . $msg);
    }
    return $data['choices'][0]['message']['content'] ?? '';
}
