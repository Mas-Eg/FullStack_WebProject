<?php
require_once 'functions.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once 'functions.php';

header('Content-Type: application/json');

// Получаем базовый путь до папки api
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');  

// Берем URI из запроса
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Убираем базовый путь из начала URI
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}

// Убираем завершающий слеш и приводим к "/" если пусто
$requestUri = rtrim($requestUri, '/') ?: '/';

file_put_contents(__DIR__ . '/debug.log', "$method $requestUri\n", FILE_APPEND);

$method = $_SERVER['REQUEST_METHOD'];
// Получаем тело запроса
$rawBody = file_get_contents('php://input');
$inputData = [];
$contentType = $_SERVER['CONTENT_TYPE'] ?? '';

if (strpos($contentType, 'application/json') !== false) {
    $inputData = json_decode($rawBody, true);
} elseif (strpos($contentType, 'application/xml') !== false) {
    if (!function_exists('simplexml_load_string')) {
        http_response_code(501);
        echo json_encode(['status' => 'error', 'message' => 'XML не поддерживается сервером']);
        exit;
    }
    $xml = simplexml_load_string($rawBody);
    if ($xml) {
        $inputData = json_decode(json_encode($xml), true);
    }
} else {
    $inputData = json_decode($rawBody, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        if (function_exists('simplexml_load_string')) {
            $xml = @simplexml_load_string($rawBody);
            if ($xml) {
                $inputData = json_decode(json_encode($xml), true);
            }
        }
    }
}
// Маршрутизация
try {
    switch (true) {
        case ($method === 'POST' && $requestUri === '/'):
            // Создание нового пользователя
            $errors = validateFormData($inputData);
            if (!empty($errors)) {
                http_response_code(422);
                echo json_encode(['status' => 'error', 'errors' => $errors]);
                break;
            }
            $result = saveUser($inputData);
            http_response_code(201);
            echo json_encode(['status' => 'success'] + $result);
            break;

        case ($method === 'PUT' && preg_match('#^/(\d+)$#', $requestUri, $m)):
            $userId = (int)$m[1];
            requireAuth();            // только авторизованные
            if ($_SESSION['user_id'] != $userId) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Доступ запрещён']);
                break;
            }
            $errors = validateFormData($inputData);
            if (!empty($errors)) {
                http_response_code(422);
                echo json_encode(['status' => 'error', 'errors' => $errors]);
                break;
            }
            $result = updateUser($userId, $inputData);
            echo json_encode($result);
            break;

        case ($method === 'POST' && $requestUri === '/login'):
            // авторизация
            $login = $inputData['login'] ?? '';
            $password = $inputData['password'] ?? '';
            $user = authenticateUser($login, $password);
            if ($user) {
                echo json_encode(['status' => 'success', 'user_id' => $user['id'], 'login' => $user['login']]);
            } else {
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => 'Неверный логин или пароль']);
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Маршрут не найден']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Внутренняя ошибка сервера']);
}
