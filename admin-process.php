<?php
require_once 'api/functions.php';

// Если AJAX – не должно случиться
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    header('Content-Type: application/json');
    require 'api/index.php';
    exit;
}

$action = $_GET['action'] ?? 'login';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $action === 'login') {
    $login = $_POST['login'] ?? '';
    $password = $_POST['password'] ?? '';
    if (adminAuthenticate($login, $password)) {
        // редирект на admin.html с показом панели (но без JS будет просто форма)
        header('Location: admin.html?admin=1');
        exit;
    } else {
        $error = 'Неверный логин или пароль';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Админ-панель - СТРОЙМАРКЕТЫ</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-table { width:100%; border-collapse:collapse; margin-top:20px; }
        .admin-table th, .admin-table td { padding:12px; text-align:left; border-bottom:1px solid #ddd; }
        .admin-table th { background-color:#f2f2f2; }
    </style>
</head>
<body>
    <div class="container" style="padding:40px;">
        <div class="form-container">
            <?php if (isAdmin()): ?>
                <h2>Пользователи</h2>
                <?php
                $pdo = getDB();
                $users = $pdo->query("SELECT id, login, name, email, phone, created_at FROM user_project ORDER BY id DESC")->fetchAll();
                ?>
                <table class="admin-table">
                    <tr><th>ID</th><th>Логин</th><th>Имя</th><th>Email</th><th>Телефон</th><th>Дата</th></tr>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= htmlspecialchars($user['login']) ?></td>
                        <td><?= htmlspecialchars($user['name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['phone'] ?? '') ?></td>
                        <td><?= $user['created_at'] ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <p><a href="admin.html?logout=1">Выйти</a></p>
            <?php else: ?>
                <h2>Вход для администратора</h2>
                <?php if ($error): ?>
                    <div class="form-message error"><?= htmlspecialchars($error) ?></div>
                <?php endif; ?>
                <form method="post" action="admin-process.php?action=login">
                    <div class="form-group">
                        <label>Логин</label>
                        <input type="text" name="login" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label>Пароль</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <button type="submit" class="btn">Войти</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
