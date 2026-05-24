<?php
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
// Если запрос к API, перенаправляем на api/index.php
if (strpos($uri, '/api') === 0) {
    $_SERVER['SCRIPT_NAME'] = '/api/index.php';
    require __DIR__ . '/api/index.php';
    return;
}
// Иначе отдаём существующие файлы
return false;
