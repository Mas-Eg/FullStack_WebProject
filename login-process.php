<?php
require_once 'api/functions.php';

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    require 'api/index.php';
    exit;
}

$action = $_GET['action'] ?? 'login';
$errors = [];
$message = '';
$userData = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'login') {
        $login = $_POST['login'] ?? '';
        $password = $_POST['password'] ?? '';
        $user = authenticateUser($login, $password);
        if ($user) {
            $userData = $user;
        } else {
            $errors['login'] = 'Неверный логин или пароль';
        }
    } elseif ($action === 'update') {
        if (!isLoggedIn()) {
            header('Location: login.html');
            exit;
        }
        $data = [
            'name' => $_POST['name'] ?? '',
            'email' => $_POST['email'] ?? '',
            'phone' => $_POST['phone'] ?? '',
            'bio' => $_POST['bio'] ?? ''
        ];
        $errors = validateFormData($data);
        if (empty($errors)) {
            updateUser($_SESSION['user_id'], $data);
            $message = 'Данные успешно обновлены';
            $userData = [
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'bio' => $data['bio']
            ];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Профиль - СТРОЙМАРКЕТЫ</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container" style="padding:40px;">
        <?php if (!isLoggedIn() && $action !== 'login'): ?>
            <p>Вы не авторизованы. <a href="login.html">Войти</a></p>
        <?php elseif ($action === 'login' && !$userData): ?>
            <?php if (!empty($errors)): ?>
                <div class="form-message error" style="display:block;">
                    <?= htmlspecialchars($errors['login'] ?? '') ?>
                </div>
            <?php endif; ?>
            <form method="post" action="login-process.php?action=login">
                <div class="form-group">
                    <label for="login">Логин</label>
                    <input type="text" id="login" name="login" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" class="btn" style="width:100%;">Войти</button>
            </form>
        <?php elseif ($userData || isLoggedIn()): ?>
            <?php if ($message): ?>
                <div class="form-message success" style="display:block;"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>
            <?php if (!empty($errors)): ?>
                <div class="form-message error" style="display:block;">
                    <ul>
                        <?php foreach ($errors as $field => $msg): ?>
                            <li><?= htmlspecialchars($msg) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            <h2>Редактирование профиля</h2>
            <form method="post" action="login-process.php?action=update">
                <div class="form-group">
                    <label for="name">Имя</label>
                    <input type="text" id="name" name="name" class="form-control" required value="<?= htmlspecialchars($userData['name'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="phone">Телефон</label>
                    <input type="tel" id="phone" name="phone" class="form-control" value="<?= htmlspecialchars($userData['phone'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" class="form-control" required value="<?= htmlspecialchars($userData['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="bio">О себе</label>
                    <textarea id="bio" name="bio" class="form-control" rows="4"><?= htmlspecialchars($userData['bio'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn" style="width:100%;">Сохранить изменения</button>
            </form>
            <p style="text-align:center; margin-top:20px;"><a href="index.html">← На главную</a></p>
        <?php endif; ?>
    </div>
</body>
</html>
