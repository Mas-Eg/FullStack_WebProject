<?php
require_once 'api/functions.php';

// Если AJAX – перенаправляем на API (не должно случиться)
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
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Профиль - СТРОЙМАРКЕТЫ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        body { background-color: #f8f9fa; }
        .nav { background-color: rgba(0,0,0,0.85); }
    </style>
</head>
<body>
    <!-- Шапка -->
    <header>
        <nav class="nav">
            <a href="index.html" class="logo">СТРОЙ<span>МАРКЕТЫ</span></a>
            <ul class="nav-links">
                <li><a href="index.html">Главная</a></li>
                <li><a href="index.html#competencies">Компетенции</a></li>
                <li><a href="index.html#slider">Проекты</a></li>
                <li><a href="index.html#form">Контакты</a></li>
                <li><a href="login.html">Профиль</a></li>
            </ul>
        </nav>
    </header>

    <!-- Основной контент -->
    <div class="container" style="padding-top:60px; padding-bottom:60px;">
        <div class="form-container" style="max-width:600px; margin:0 auto;">
            <?php if (!isLoggedIn() && $action !== 'login'): ?>
                <h2 class="form-title">Доступ запрещён</h2>
                <p style="text-align:center;">Вы не авторизованы.</p>
                <p style="text-align:center;"><a href="login.html" class="btn">Войти</a></p>
            <?php elseif ($action === 'login' && !$userData): ?>
                <h2 class="form-title">Вход в профиль</h2>
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
                <h2 class="form-title">Редактирование профиля</h2>
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
                <form method="post" action="login-process.php?action=update">
                    <div class="form-group">
                        <label for="editName">Ваше имя *</label>
                        <input type="text" id="editName" name="name" class="form-control" required
                               value="<?= htmlspecialchars($userData['name'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="editPhone">Телефон</label>
                        <input type="tel" id="editPhone" name="phone" class="form-control"
                               value="<?= htmlspecialchars($userData['phone'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="editEmail">Email *</label>
                        <input type="email" id="editEmail" name="email" class="form-control" required
                               value="<?= htmlspecialchars($userData['email'] ?? '') ?>">
                    </div>
                    <div class="form-group">
                        <label for="editBio">О себе</label>
                        <textarea id="editBio" name="bio" class="form-control" rows="4"><?= htmlspecialchars($userData['bio'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn" style="width:100%;">Сохранить изменения</button>
                </form>
            <?php endif; ?>
            <p style="text-align:center; margin-top:20px;"><a href="index.html" style="color: #ff9900;">← На главную</a></p>
        </div>
    </div>

    <!-- Футер -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-column">
                    <h3>СТРОЙМАРКЕТЫ</h3>
                    <p>Всё для строительства и ремонта. Профессиональные материалы, инструменты и оборудование от ведущих производителей.</p>
                </div>
                <div class="footer-column">
                    <h3>Контакты</h3>
                    <ul class="footer-links">
                        <li><i class="fas fa-map-marker-alt"></i> Москва, ул. Строителей, 15</li>
                        <li><i class="fas fa-phone"></i> +7 (495) 123-45-67</li>
                        <li><i class="fas fa-envelope"></i> info@stroymarkety.ru</li>
                    </ul>
                </div>
                <div class="footer-column">
                    <h3>Быстрые ссылки</h3>
                    <ul class="footer-links">
                        <li><a href="index.html">Главная</a></li>
                        <li><a href="index.html#competencies">Компетенции</a></li>
                        <li><a href="index.html#slider">Проекты</a></li>
                        <li><a href="index.html#form">Контакты</a></li>
                    </ul>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2023 СТРОЙМАРКЕТЫ. Все права защищены.</p>
            </div>
        </div>
    </footer>
</body>
</html>
