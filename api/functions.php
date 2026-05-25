<?php
session_start();
require_once 'db.php';

function generateLoginPassword() {
    $login = 'u' . bin2hex(random_bytes(3));   
    $password = bin2hex(random_bytes(4));      
    return [$login, $password];
}

function validateFormData($data) {
    $errors = [];
    
    // Проверка имени (минимум 2 символа Unicode)
    if (empty($data['name']) || !preg_match('/^.{2,}$/u', $data['name'])) {
        $errors['name'] = 'Имя должно содержать минимум 2 символа';
    }
    
    // Проверка email
    if (empty($data['email']) || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Некорректный email';
    }
    
    // Проверка телефона (если указан)
    if (!empty($data['phone']) && !preg_match('/^\+?\d{7,15}$/', $data['phone'])) {
        $errors['phone'] = 'Некорректный номер телефона';
    }
    
    // Проверка сообщения (не более 500 символов Unicode)
    if (!empty($data['bio']) && !preg_match('/^.{0,500}$/u', $data['bio'])) {
        $errors['bio'] = 'Сообщение не должно превышать 500 символов';
    }
    
    return $errors;
}

function saveUser($data) {
    $pdo = getDB();
    list($login, $password) = generateLoginPassword();
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    $sql = "INSERT INTO user_project (login, password, name, email, phone, bio) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$login, $hashedPassword, $data['name'], $data['email'], $data['phone'] ?? '', $data['bio'] ?? '']);
    $userId = $pdo->lastInsertId();
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    $projectDir = rtrim($scriptDir, '/') == '/api' ? dirname($scriptDir) : $scriptDir;
    $profile_url = $projectDir . '/login.html?id=' . $userId;
    return [
        'user_id' => $userId,
        'login' => $login,
        'password' => $password,   
        'profile_url' => $profile_url
    ];
}

function updateUser($userId, $data) {
    $pdo = getDB();
    $sql = "UPDATE user_project SET name = ?, email = ?, phone = ?, bio = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['name'], $data['email'], $data['phone'] ?? '', $data['bio'] ?? '', $userId]);
    return ['status' => 'success', 'message' => 'Данные обновлены'];
}

function authenticateUser($login, $password) {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM user_project WHERE login = ?");
    $stmt->execute([$login]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_login'] = $user['login'];
        return $user;
    }
    return false;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireAuth() {
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Необходима авторизация']);
        exit;
    }
}

// ========== Администратор ==========
function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] === true;
}

function adminAuthenticate($login, $password) {
    $adminLogin = 'admin';
    $adminPasswordHash = '$2y$10$66B0lHx5jZvUn1HAme1X1.oBcisqtWixA3kO.oETq7RwiteAouyqS';// Хеш пароля "admin"
    
    if ($login === $adminLogin && password_verify($password, $adminPasswordHash)) {
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_login'] = $login;
        return true;
    }
    return false;
}

function requireAdmin() {
    if (!isAdmin()) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Необходима авторизация администратора']);
        exit;
    }
}
