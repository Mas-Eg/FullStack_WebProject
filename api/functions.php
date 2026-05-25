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

//админ панель
// Хеш пароля для admin (пароль: admin123)
define('ADMIN_PASSWORD_HASH', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi');
define('ADMIN_LOGIN', 'admin');

function authenticateAdmin($login, $password) {
    if ($login !== ADMIN_LOGIN) return false;
    if (password_verify($password, ADMIN_PASSWORD_HASH)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_login'] = ADMIN_LOGIN;
        adminLogAction('LOGIN', 'Successful login');
        return true;
    }
    return false;
}

function isAdminLoggedIn() {
    return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
}

function requireAdmin() {
    if (!isAdminLoggedIn()) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
        exit;
    }
}

function adminLogout() {
    adminLogAction('LOGOUT', 'Logged out');
    unset($_SESSION['admin_logged_in'], $_SESSION['admin_login']);
    session_regenerate_id(true);
}

function getAllUsersForAdmin() {
    $pdo = getDB();
    $stmt = $pdo->query("SELECT id, login, name, email, phone, bio FROM user_project ORDER BY id DESC");
    return $stmt->fetchAll();
}

function adminUpdateAnyUser($userId, $data) {
    $pdo = getDB();
    $sql = "UPDATE user_project SET name = ?, email = ?, phone = ?, bio = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$data['name'], $data['email'], $data['phone'] ?? '', $data['bio'] ?? '', $userId]);
    return true;
}

function adminDeleteUser($userId) {
    $pdo = getDB();
    $stmt = $pdo->prepare("DELETE FROM user_project WHERE id = ?");
    $stmt->execute([$userId]);
    return $stmt->rowCount() > 0;
}
