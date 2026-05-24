<?php
require_once 'functions.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

// --- вычисляем базовый путь и URI как в рабочей версии ---
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}
$requestUri = rtrim($requestUri, '/') ?: '/';

// --- ОТЛАДОЧНЫЙ ВЫВОД ---
echo json_encode([
    'method'     => $method,
    'requestUri' => $requestUri,
    'basePath'   => $basePath,
    'rawUri'     => $_SERVER['REQUEST_URI'],
    'scriptName' => $_SERVER['SCRIPT_NAME']
]);
exit;
