// Мобильное меню
const mobileMenuBtn = document.getElementById('mobileMenuBtn');
const mobileMenuClose = document.getElementById('mobileMenuClose');
const mobileMenu = document.getElementById('mobileMenu');
const mobileMenuOverlay = document.getElementById('mobileMenuOverlay');

if (mobileMenuBtn) {
    mobileMenuBtn.addEventListener('click', () => {
        mobileMenu.classList.add('active');
        mobileMenuOverlay.classList.add('active');
        document.body.style.overflow = 'hidden';
    });

    mobileMenuClose.addEventListener('click', () => {
        mobileMenu.classList.remove('active');
        mobileMenuOverlay.classList.remove('active');
        document.body.style.overflow = 'auto';
    });

    mobileMenuOverlay.addEventListener('click', () => {
        mobileMenu.classList.remove('active');
        mobileMenuOverlay.classList.remove('active');
        document.body.style.overflow = 'auto';
    });

    const mobileMenuLinks = document.querySelectorAll('.mobile-menu-links a');
    mobileMenuLinks.forEach(link => {
        link.addEventListener('click', () => {
            mobileMenu.classList.remove('active');
            mobileMenuOverlay.classList.remove('active');
            document.body.style.overflow = 'auto';
        });
    });
}

// Плавная прокрутка для якорных ссылок
document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
        e.preventDefault();
        const targetId = this.getAttribute('href');
        if (targetId === '#') return;
        const targetElement = document.querySelector(targetId);
        if (targetElement) {
            window.scrollTo({
                top: targetElement.offsetTop - 80,
                behavior: 'smooth'
            });
        }
    });
});

// Слайдер на jQuery
$(document).ready(function() {
    const slider = $('#jquery-slider');
    if (slider.length) {
        const slides = $('.slide');
        const totalSlides = slides.length;
        let currentSlide = 0;
        let slideInterval;

        function createDots() {
            const dotsContainer = $('#sliderDots');
            dotsContainer.empty();
            for (let i = 0; i < totalSlides; i++) {
                const dot = $('<span class="dot"></span>');
                dot.data('slide', i);
                dotsContainer.append(dot);
            }
            $('.dot').eq(0).addClass('active');
        }

        function updateSlider() {
            const slideWidth = 100;
            const translateX = -(currentSlide * slideWidth);
            slider.css('transform', `translateX(${translateX}%)`);
            $('.dot').removeClass('active');
            $('.dot').eq(currentSlide).addClass('active');
        }

        function nextSlide() {
            currentSlide = (currentSlide + 1) % totalSlides;
            updateSlider();
        }

        function prevSlide() {
            currentSlide = (currentSlide - 1 + totalSlides) % totalSlides;
            updateSlider();
        }

        function goToSlide(slideIndex) {
            currentSlide = slideIndex;
            updateSlider();
        }

        createDots();

        $('#nextBtn').click(function() {
            nextSlide();
            resetInterval();
        });

        $('#prevBtn').click(function() {
            prevSlide();
            resetInterval();
        });

        $(document).on('click', '.dot', function() {
            const slideIndex = $(this).data('slide');
            goToSlide(slideIndex);
            resetInterval();
        });

        function startInterval() {
            slideInterval = setInterval(nextSlide, 36000);
        }

        function resetInterval() {
            clearInterval(slideInterval);
            startInterval();
        }

        startInterval();

        $('.slider-container').hover(
            function() { clearInterval(slideInterval); },
            function() { startInterval(); }
        );
    }

    // Анимация карточек компетенций при скролле
    function checkCompetenciesAnimation() {
        const windowBottom = $(window).scrollTop() + $(window).height();
        $('.competency-card').each(function() {
            const cardTop = $(this).offset().top;
            if (cardTop < windowBottom - 50) {
                $(this).css({
                    'opacity': '1',
                    'transform': 'translateY(0)'
                });
            }
        });
    }

    $('.competency-card').css({
        'opacity': '0',
        'transform': 'translateY(20px)',
        'transition': 'opacity 0.5s ease, transform 0.5s ease'
    });

    $(window).on('scroll', checkCompetenciesAnimation);
    $(window).on('load', checkCompetenciesAnimation);
    checkCompetenciesAnimation();
});

// ========== Асинхронная работа с формами (AJAX) ==========

// Вспомогательная функция показа ошибок
function showFieldErrors(errors) {
    // Скрываем все ошибки
    document.querySelectorAll('.error').forEach(el => el.style.display = 'none');
    if (!errors) return;
    for (const [field, msg] of Object.entries(errors)) {
        const errorEl = document.getElementById(`${field}-error`);
        if (errorEl) {
            errorEl.textContent = msg;
            errorEl.style.display = 'block';
        }
    }
}

// Показ общего сообщения
function showMessage(element, text, isError = false) {
    element.textContent = text;
    element.className = 'form-message ' + (isError ? 'error' : 'success');
    element.style.display = 'block';
    setTimeout(() => {
        element.style.display = 'none';
    }, 7000);
}

// ================== Регистрация (index.html) ==================
const API_BASE = '/Web_Labs/FullStack_WebProject';
const contactForm = document.getElementById('contactForm');
if (contactForm) {
    contactForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const submitBtn = document.getElementById('submitBtn');
        const submitText = document.getElementById('submitText');
        const submitSpinner = document.getElementById('submitSpinner');
        const formMessage = document.getElementById('formMessage');

        submitBtn.disabled = true;
        submitText.style.display = 'none';
        submitSpinner.style.display = 'inline';

        const formData = new FormData(contactForm);
        const data = Object.fromEntries(formData.entries());

        // Клиентская валидация
        const errors = {};
        if (!data.name || data.name.trim().length < 2) {
            errors.name = 'Имя должно содержать минимум 2 символа';
        }
        if (!data.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
            errors.email = 'Некорректный email';
        }
        if (data.phone && !/^\+?\d{7,15}$/.test(data.phone)) {
            errors.phone = 'Некорректный номер телефона';
        }
        if (data.bio && data.bio.length > 500) {
            errors.bio = 'Сообщение не должно превышать 500 символов';
        }

        if (Object.keys(errors).length > 0) {
            showFieldErrors(errors);
            showMessage(formMessage, 'Пожалуйста, исправьте ошибки в форме.', true);
            submitBtn.disabled = false;
            submitText.style.display = 'inline';
            submitSpinner.style.display = 'none';
            return;
        }

        showFieldErrors(null); // очищаем ошибки

        try {
            const response = await fetch(API_BASE+'/api', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                const msgHtml = `
                    <p>Регистрация успешна!</p>
                    <p>Логин: <strong>${result.login}</strong></p>
                    <p>Пароль: <strong>${result.password}</strong></p>
                    <p>Ссылка на профиль: <a href="/Web_Labs/FullStack_WebProject/login.html">/Web_Labs/FullStack_WebProject/login.html</a></p>
                `;
                formMessage.innerHTML = msgHtml;
                formMessage.className = 'form-message success';
                formMessage.style.display = 'block';
                contactForm.reset();
            } else if (response.status === 422 && result.errors) {
                showFieldErrors(result.errors);
                showMessage(formMessage, 'Пожалуйста, исправьте ошибки в форме.', true);
            } else {
                showMessage(formMessage, result.message || 'Ошибка сервера', true);
            }
        } catch (error) {
            showMessage(formMessage, 'Сетевая ошибка. Попробуйте позже.', true);
        } finally {
            submitBtn.disabled = false;
            submitText.style.display = 'inline';
            submitSpinner.style.display = 'none';
        }
    });
}

// ================== Логин и редактирование (login.html) ==================
const loginForm = document.getElementById('loginForm');
const editForm = document.getElementById('editForm');
const formMessage = document.getElementById('formMessage');
const pageTitle = document.getElementById('pageTitle');

// Проверка авторизации при загрузке login.html
async function checkAuth() {
    try {
        const resp = await fetch(`${API_BASE}/api/check-auth`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (resp.ok) {
            const data = await resp.json();
            if (data.status === 'success' && data.user_id) {
                // пользователь уже авторизован, загружаем его профиль
                loadUserProfile(data.user_id);
            }
        }
    } catch (e) {}
}

async function loadUserProfile(userId) {
    try {
        const resp = await fetch(`${API_BASE}/api/${userId}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        if (resp.ok) {
            const data = await resp.json();
            if (data.status === 'success' && data.user) {
                document.getElementById('userId').value = data.user.id;
                document.getElementById('editName').value = data.user.name || '';
                document.getElementById('editPhone').value = data.user.phone || '';
                document.getElementById('editEmail').value = data.user.email || '';
                document.getElementById('editBio').value = data.user.bio || '';

                loginForm.style.display = 'none';
                editForm.style.display = 'block';
                pageTitle.textContent = 'Редактирование профиля';
                return;
            }
        }
    } catch (e) {}
    // если не удалось – показываем форму входа
    loginForm.style.display = 'block';
    editForm.style.display = 'none';
}

if (loginForm) {
    // Фоллбек: если JS отключен, форма отправляется обычным способом на login-process.php
    loginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const formData = new FormData(loginForm);
        const credentials = Object.fromEntries(formData.entries());

        try {
            const response = await fetch(`${API_BASE}/api/login`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(credentials)
            });

            const result = await response.json();
            if (response.ok && result.status === 'success') {
                document.getElementById('userId').value = result.user_id;
                document.getElementById('editName').value = result.name || '';
                document.getElementById('editPhone').value = result.phone || '';
                document.getElementById('editEmail').value = result.email || '';
                document.getElementById('editBio').value = result.bio || '';

                loginForm.style.display = 'none';
                editForm.style.display = 'block';
                pageTitle.textContent = 'Редактирование профиля';
                showMessage(formMessage, 'Вход выполнен успешно', false);
            } else {
                showMessage(formMessage, result.message || 'Ошибка входа', true);
            }
        } catch (error) {
            showMessage(formMessage, 'Сетевая ошибка', true);
        }
    });

    editForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const userId = document.getElementById('userId').value;
        const formData = new FormData(editForm);
        const data = Object.fromEntries(formData.entries());
        delete data.userId; // не отправляем userId в теле, он в URL

        // Клиентская валидация
        const errors = {};
        if (!data.name || data.name.trim().length < 2) {
            errors.name = 'Имя должно содержать минимум 2 символа';
        }
        if (!data.email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(data.email)) {
            errors.email = 'Некорректный email';
        }
        if (data.phone && !/^\+?\d{7,15}$/.test(data.phone)) {
            errors.phone = 'Некорректный номер телефона';
        }
        if (data.bio && data.bio.length > 500) {
            errors.bio = 'Сообщение не должно превышать 500 символов';
        }

        if (Object.keys(errors).length > 0) {
            showFieldErrors(errors);
            showMessage(formMessage, 'Пожалуйста, исправьте ошибки в форме.', true);
            return;
        }
        showFieldErrors(null);

        try {
            const response = await fetch(`${API_BASE}/api/${userId}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();
            if (response.ok && result.status === 'success') {
                showMessage(formMessage, 'Данные успешно обновлены', false);
            } else if (response.status === 422 && result.errors) {
                showFieldErrors(result.errors);
                showMessage(formMessage, 'Пожалуйста, исправьте ошибки в форме.', true);
            } else {
                showMessage(formMessage, result.message || 'Ошибка обновления', true);
            }
        } catch (error) {
            showMessage(formMessage, 'Сетевая ошибка', true);
        }
    });

    // Проверяем авторизацию сразу после загрузки
    checkAuth();
}

// ================== Админ-панель ==================
if (document.getElementById('adminLoginForm')) {
    const adminLoginForm = document.getElementById('adminLoginForm');
    const adminLoginBlock = document.getElementById('adminLoginBlock');
    const adminPanel = document.getElementById('adminPanel');
    const adminLoginMessage = document.getElementById('adminLoginMessage');
    const adminPanelMessage = document.getElementById('adminPanelMessage');
    const usersTableBody = document.getElementById('usersTableBody');
    const logoutAdminBtn = document.getElementById('logoutAdminBtn');


    // Проверка, залогинен ли админ (при загрузке страницы)
    async function checkAdminAuth() {
        try {
            const resp = await fetch(`${API_BASE}/api/admin/users`);
            if (resp.ok) {
                // админ авторизован
                adminLoginBlock.style.display = 'none';
                adminPanel.style.display = 'block';
                loadUsers();
            } else {
                adminLoginBlock.style.display = 'block';
                adminPanel.style.display = 'none';
            }
        } catch (e) {
            adminLoginBlock.style.display = 'block';
            adminPanel.style.display = 'none';
        }
    }

    // Загрузка списка пользователей
    async function loadUsers() {
        try {
            const resp = await fetch(`${API_BASE}/api/admin/users`);
            const data = await resp.json();
            if (data.status === 'success') {
                usersTableBody.innerHTML = '';
                data.users.forEach(user => {
                    const row = document.createElement('tr');
                    row.innerHTML = `
                        <td>${user.id}</td>
                        <td>${escapeHtml(user.login)}</td>
                        <td>${escapeHtml(user.name)}</td>
                        <td>${escapeHtml(user.email)}</td>
                        <td>${escapeHtml(user.phone || '')}</td>
                        <td>${user.created_at}</td>
                        <td>
                            <button class="btn-danger delete-user" data-id="${user.id}">Удалить</button>
                        </td>
                    `;
                    usersTableBody.appendChild(row);
                });

                // Навешиваем обработчики удаления
                document.querySelectorAll('.delete-user').forEach(btn => {
                    btn.addEventListener('click', async function() {
                        const userId = this.dataset.id;
                        if (confirm('Удалить пользователя?')) {
                            try {
                                const delResp = await fetch(`${API_BASE}/api/admin/users/${userId}`, {
                                    method: 'DELETE'
                                });
                                const delData = await delResp.json();
                                if (delResp.ok && delData.status === 'success') {
                                    showMessage(adminPanelMessage, 'Пользователь удалён', false);
                                    loadUsers(); // обновить таблицу
                                } else {
                                    showMessage(adminPanelMessage, delData.message || 'Ошибка удаления', true);
                                }
                            } catch (err) {
                                showMessage(adminPanelMessage, 'Сетевая ошибка', true);
                            }
                        }
                    });
                });
            } else {
                // если ошибка, возможно сессия истекла
                adminLoginBlock.style.display = 'block';
                adminPanel.style.display = 'none';
            }
        } catch (e) {
            showMessage(adminPanelMessage, 'Ошибка загрузки пользователей', true);
        }
    }

    // Вход админа
    adminLoginForm.addEventListener('submit', async function(e) {
        e.preventDefault();
        const login = document.getElementById('adminLogin').value;
        const password = document.getElementById('adminPassword').value;

        try {
            const resp = await fetch(`${API_BASE}/admin/login`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ login, password })
            });
            const data = await resp.json();
            if (resp.ok && data.status === 'success') {
                adminLoginBlock.style.display = 'none';
                adminPanel.style.display = 'block';
                loadUsers();
            } else {
                showMessage(adminLoginMessage, data.message || 'Ошибка входа', true);
            }
        } catch (e) {
            showMessage(adminLoginMessage, 'Сетевая ошибка', true);
        }
    });

    // Выход
    logoutAdminBtn.addEventListener('click', async function() {
        // Удаляем сессию админа на сервере (можно сделать отдельный метод logout)
        // Простейший способ: вызвать любой URL, который сбросит сессию, или просто перезагрузить страницу
        // Добавим метод GET /api/admin/logout в api/index.php? Но для простоты просто перезагрузим
        // Чтобы сбросить сессию, можно сделать запрос на несуществующий маршрут или специальный logout
        // Реализуем быстрый logout через api
        try {
            await fetch(`${API_BASE}/api/admin/logout`, { method: 'GET' });
        } catch(e) {}
        adminLoginBlock.style.display = 'block';
        adminPanel.style.display = 'none';
        adminLoginForm.reset();
    });

    // Экранирование HTML
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Инициализация
    checkAdminAuth();
}
