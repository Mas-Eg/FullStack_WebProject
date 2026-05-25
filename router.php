<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Если запрос начинается с /api, подключаем api/index.php
if (strpos($uri, '/api') === 0) {
    $_SERVER['SCRIPT_NAME'] = '/api/index.php';
    require __DIR__ . '/api/index.php';
    return true;
}

// Иначе отдаём существующие файлы
return false;
