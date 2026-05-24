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
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Регистрация - СТРОЙМАРКЕТЫ</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        /* Дополнительные стили для страницы результата */
        .page-wrapper {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .content-area {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .result-container {
            max-width: 600px;
            width: 100%;
        }
        .nav {
            background-color: rgba(0, 0, 0, 0.85);
        }
    </style>
</head>
<body>
    <div class="page-wrapper">
        <!-- Шапка как на главной, но без видео-фона -->
        <header>
            <nav class="nav">
                <a href="index.html" class="logo">СТРОЙ<span>МАРКЕТЫ</span></a>
                <ul class="nav-links">
                    <li><a href="index.html">Главная</a></li>
                    <li><a href="index.html#competencies">Компетенции</a></li>
                    <li><a href="index.html#slider">Проекты</a></li>
                    <li><a href="index.html#form">Контакты</a></li>
                </ul>
            </nav>
        </header>

        <!-- Основной контент -->
        <main class="content-area">
            <div class="result-container">
                <div class="form-container" style="margin: 0; box-shadow: 0 5px 30px rgba(0,0,0,0.1);">
                    <?php if ($result): ?>
                        <h2 class="form-title" style="color: #155724;">Регистрация успешна!</h2>
                        <div class="form-message success" style="display:block;">
                            <p style="font-size: 18px; margin-bottom: 10px;">Ваши данные для входа:</p>
                            <p><strong>Логин:</strong> <?= htmlspecialchars($result['login']) ?></p>
                            <p><strong>Пароль:</strong> <?= htmlspecialchars($result['password']) ?></p>
                            <p style="margin-top: 20px;">
                                <a href="<?= htmlspecialchars($result['profile_url']) ?>" class="btn" style="display: inline-block;">
                                    Перейти в профиль
                                </a>
                            </p>
                            <p style="margin-top: 15px; font-size: 14px; color: #666;">
                                Сохраните логин и пароль — они больше не будут показаны.
                            </p>
                        </div>
                    <?php else: ?>
                        <h2 class="form-title">Оставить заявку</h2>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="form-message error" style="display:block;">
                                <ul>
                                    <?php foreach ($errors as $field => $msg): ?>
                                        <li><?= htmlspecialchars($msg) ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="process.php">
                            <div class="form-group">
                                <label for="name">Ваше имя *</label>
                                <input type="text" id="name" name="name" class="form-control" required
                                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="Иван Иванов">
                            </div>
                            <div class="form-group">
                                <label for="phone">Телефон</label>
                                <input type="tel" id="phone" name="phone" class="form-control"
                                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="+7 (999) 123-45-67">
                            </div>
                            <div class="form-group">
                                <label for="email">Email *</label>
                                <input type="email" id="email" name="email" class="form-control" required
                                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="example@mail.ru">
                            </div>
                            <div class="form-group">
                                <label for="bio">Сообщение</label>
                                <textarea id="bio" name="bio" class="form-control" rows="4"
                                          placeholder="Опишите ваш проект или задайте вопрос..."><?= htmlspecialchars($_POST['bio'] ?? '') ?></textarea>
                            </div>
                            <button type="submit" class="btn" style="width: 100%;">
                                Отправить заявку
                            </button>
                        </form>
                    <?php endif; ?>
                    
                    <p style="text-align: center; margin-top: 20px;">
                        <a href="index.html" style="color: #ff9900;">← Вернуться на главную</a>
                    </p>
                </div>
            </div>
        </main>

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
    </div>
</body>
</html>
