<?php
require_once 'functions.php';

// Показываем ошибки (только для отладки, потом можно убрать)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json');

// Определяем $method в самом начале
$method = $_SERVER['REQUEST_METHOD'];

// Вычисляем базовый путь и URI
$basePath = rtrim(dirname($_SERVER['SCRIPT_NAME']), '/');
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($requestUri, $basePath) === 0) {
    $requestUri = substr($requestUri, strlen($basePath));
}
$requestUri = rtrim($requestUri, '/') ?: '/';

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
        // Регистрация нового пользователя
        case ($method === 'POST' && $requestUri === '/'):
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

        // Обновление данных авторизованного пользователя
        case ($method === 'PUT' && preg_match('#^/(\d+)$#', $requestUri, $m)):
            $userId = (int)$m[1];
            requireAuth();
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

        // Авторизация
        case ($method === 'POST' && $requestUri === '/login'):
            $login = $inputData['login'] ?? '';
            $password = $inputData['password'] ?? '';
            $user = authenticateUser($login, $password);
            if ($user) {
                echo json_encode([
                    'status' => 'success',
                    'user_id' => $user['id'],
                    'login' => $user['login'],
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'phone' => $user['phone'],
                    'bio' => $user['bio']
                ]);
            } else {
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => 'Неверный логин или пароль']);
            }
            break;

        // Получение данных пользователя
        case ($method === 'GET' && preg_match('#^/(\d+)$#', $requestUri, $m)):
            $userId = (int)$m[1];
            requireAuth();
            if ($_SESSION['user_id'] != $userId) {
                http_response_code(403);
                echo json_encode(['status' => 'error', 'message' => 'Доступ запрещён']);
                break;
            }
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT id, login, name, email, phone, bio FROM user_project WHERE id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch();
            if ($user) {
                echo json_encode(['status' => 'success', 'user' => $user]);
            } else {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Пользователь не найден']);
            }
            break;

        // Проверка авторизации
        case ($method === 'GET' && $requestUri === '/check-auth'):
            if (isLoggedIn()) {
                echo json_encode(['status' => 'success', 'user_id' => $_SESSION['user_id'], 'login' => $_SESSION['user_login']]);
            } else {
                http_response_code(401);
                echo json_encode(['status' => 'error', 'message' => 'Не авторизован']);
            }
            break;

       // ========== Администратор ==========
// Вход администратора
case ($method === 'POST' && $requestUri === '/admin/login'):
    $login = $inputData['login'] ?? '';
    $password = $inputData['password'] ?? '';
    if (adminAuthenticate($login, $password)) {
        echo json_encode(['status' => 'success', 'message' => 'Вход выполнен']);
    } else {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Неверный логин или пароль']);
    }
    break;

// Получение списка всех пользователей (только админ)
case ($method === 'GET' && $requestUri === '/admin/users'):
    requireAdmin();
    $pdo = getDB();
    $stmt = $pdo->query("SELECT id, login, name, email, phone, bio, created_at FROM user_project ORDER BY id DESC");
    $users = $stmt->fetchAll();
    echo json_encode(['status' => 'success', 'users' => $users]);
    break;

// Удаление пользователя (только админ)
case ($method === 'DELETE' && preg_match('#^/admin/users/(\d+)$#', $requestUri, $m)):
    requireAdmin();
    $userId = (int)$m[1];
    $pdo = getDB();
    $stmt = $pdo->prepare("DELETE FROM user_project WHERE id = ?");
    $stmt->execute([$userId]);
    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Пользователь удалён']);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Пользователь не найден']);
    }
    break;

// Выход администратора
case ($method === 'GET' && $requestUri === '/admin/logout'):
    unset($_SESSION['is_admin']);
    session_destroy();
    echo json_encode(['status' => 'success', 'message' => 'Выход выполнен']);
    break;
?>
