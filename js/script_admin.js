document.addEventListener('DOMContentLoaded', function() {
    // Определяем базовый путь API
    const API_BASE = window.location.pathname.includes('/Web_Labs/FullStack_WebProject/')
        ? '/Web_Labs/FullStack_WebProject/api'
        : '/api';

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
            const resp = await fetch(`${API_BASE}/admin/users`);
            if (resp.ok) {
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
            const resp = await fetch(`${API_BASE}/admin/users`);
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
                                const delResp = await fetch(`${API_BASE}/admin/users/${userId}`, {
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
                // сессия истекла или ошибка доступа
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
        try {
            await fetch(`${API_BASE}/admin/logout`);
        } catch (e) {}
        adminLoginBlock.style.display = 'block';
        adminPanel.style.display = 'none';
        adminLoginForm.reset();
    });

    // Функция показа сообщений
    function showMessage(element, text, isError = false) {
        element.textContent = text;
        element.className = 'form-message ' + (isError ? 'error' : 'success');
        element.style.display = 'block';
        setTimeout(() => {
            element.style.display = 'none';
        }, 5000);
    }

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

    // Стартовая проверка
    checkAdminAuth();
});
