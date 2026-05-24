<?php
require_once 'api/functions.php';

// Если пришёл AJAX, просто вызываем логику API
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
    // этот блок не должен выполняться, т.к. JS использует /api, но для надёжности оставим
    header('Content-Type: application/json');
    // ... та же логика, что в api/index.php для POST /
    exit;
}

// Стандартная обработка формы (без JS)
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST;
    $errors = validateFormData($data);
    if (empty($errors)) {
        $result = saveUser($data);
        // показываем HTML-страницу с логином/паролем
        ?>
        <!DOCTYPE html>
        <html>
        <head><title>Регистрация завершена</title></head>
        <body>
            <h1>Регистрация успешна!</h1>
            <p>Ваш логин: <?= htmlspecialchars($result['login']) ?></p>
            <p>Ваш пароль: <?= htmlspecialchars($result['password']) ?></p>
            <p>Ссылка на профиль: <a href="<?= htmlspecialchars($result['profile_url']) ?>">перейти</a></p>
        </body>
        </html>
        <?php
        exit;
    }
}
// Если есть ошибки или страница открыта впервые, показываем форму снова
?>
<!DOCTYPE html>
<html>
<head><title>Регистрация</title></head>
<body>
    <form method="post">
        <!-- поля, аналогичные index.html -->
        <?php if (!empty($errors)): ?>
            <ul class="errors">
                <?php foreach ($errors as $field => $msg): ?>
                    <li><?= htmlspecialchars($msg) ?></li>
                <?php endforeach; ?>
            </ul>
        <?php endif; ?>
        <input type="text" name="name" required placeholder="Имя" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>">
        <input type="email" name="email" required placeholder="Email">
        <button type="submit">Отправить</button>
    </form>
</body>
</html>
