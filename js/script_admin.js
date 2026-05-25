document.addEventListener('DOMContentLoaded', function () {
    // Базовый путь API
    const API_BASE = '/Web_Labs/FullStack_WebProject/api';

    const adminLoginForm = document.getElementById('adminLoginForm');
    const adminLoginBlock = document.getElementById('adminLoginBlock');
    const adminPanel = document.getElementById('adminPanel');
    const adminLoginMessage = document.getElementById('adminLoginMessage');
    const adminPanelMessage = document.getElementById('adminPanelMessage');
    const usersTableBody = document.getElementById('usersTableBody');
    const logoutAdminBtn = document.getElementById('logoutAdminBtn');

    // Проверка, авторизован ли администратор
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
                renderUsersTable(data.users);
            } else {
                // сессия истекла или ошибка доступа
                adminLoginBlock.style.display = 'block';
                adminPanel.style.display = 'none';
            }
        } catch (e) {
            showMessage(adminPanelMessage, 'Ошибка загрузки пользователей', true);
        }
    }

    // Отрисовка таблицы пользователей с кнопками действий
    function renderUsersTable(users) {
        usersTableBody.innerHTML = '';
        users.forEach(user => {
            const row = document.createElement('tr');
            row.setAttribute('data-user-id', user.id);
            row.innerHTML = `
                <td>${user.id}</td>
                <td class="user-login">${escapeHtml(user.login)}</td>
                <td class="user-name">${escapeHtml(user.name)}</td>
                <td class="user-email">${escapeHtml(user.email)}</td>
                <td class="user-phone">${escapeHtml(user.phone || '')}</td>
                <td>${user.created_at}</td>
                <td class="actions-cell">
                    <button class="btn-edit edit-user" data-id="${user.id}">Редактировать</button>
                    <button class="btn-danger delete-user" data-id="${user.id}">Удалить</button>
                </td>
            `;
            usersTableBody.appendChild(row);
        });

        // Привязываем обработчики удаления
        document.querySelectorAll('.delete-user').forEach(btn => {
            btn.addEventListener('click', handleDeleteUser);
        });

        // Привязываем обработчики редактирования
        document.querySelectorAll('.edit-user').forEach(btn => {
            btn.addEventListener('click', handleEditUser);
        });
    }

    // Удаление пользователя
    async function handleDeleteUser(e) {
        const userId = this.dataset.id;
        if (!confirm('Удалить пользователя безвозвратно?')) return;
        try {
            const resp = await fetch(`${API_BASE}/admin/users/${userId}`, { method: 'DELETE' });
            const data = await resp.json();
            if (resp.ok && data.status === 'success') {
                showMessage(adminPanelMessage, 'Пользователь удалён', false);
                loadUsers(); // обновить таблицу
            } else {
                showMessage(adminPanelMessage, data.message || 'Ошибка удаления', true);
            }
        } catch (e) {
            showMessage(adminPanelMessage, 'Сетевая ошибка при удалении', true);
        }
    }

    // Переключение строки в режим редактирования
    function handleEditUser(e) {
        const userId = this.dataset.id;
        const row = document.querySelector(`tr[data-user-id="${userId}"]`);
        if (!row) return;

        // Если строка уже редактируется – ничего не делаем
        if (row.classList.contains('editing')) return;

        // Сохраняем исходные значения
        const originalName = row.querySelector('.user-name').textContent;
        const originalEmail = row.querySelector('.user-email').textContent;
        const originalPhone = row.querySelector('.user-phone').textContent;
        const originalBio = ''; // bio нет в таблице, будем запрашивать через API, но для простоты оставим поле пустым

        // Заменяем ячейки на поля ввода
        row.classList.add('editing');
        row.querySelector('.user-name').innerHTML = `<input type="text" class="edit-input" id="edit-name-${userId}" value="${escapeHtml(originalName)}">`;
        row.querySelector('.user-email').innerHTML = `<input type="email" class="edit-input" id="edit-email-${userId}" value="${escapeHtml(originalEmail)}">`;
        row.querySelector('.user-phone').innerHTML = `<input type="text" class="edit-input" id="edit-phone-${userId}" value="${escapeHtml(originalPhone)}">`;
        // Добавим скрытое поле bio, если нужно, но в таблице его нет. Можно не редактировать bio через таблицу, но для полноты добавим возможность в модальном окне. Пока пропустим, чтобы не усложнять. Администратор сможет редактировать bio, если мы добавим отдельную форму. Но в задании это не критично. Для простоты оставим только поля из таблицы.
        // Заменим кнопки на "Сохранить" и "Отмена"
        const actionsCell = row.querySelector('.actions-cell');
        actionsCell.innerHTML = `
            <button class="btn-save save-user" data-id="${userId}">Сохранить</button>
            <button class="btn-cancel cancel-edit" data-id="${userId}">Отмена</button>
        `;

        // Обработчики для кнопок
        row.querySelector('.save-user').addEventListener('click', saveUserEdit);
        row.querySelector('.cancel-edit').addEventListener('click', cancelEdit);
    }

    // Отмена редактирования – перезагружаем таблицу (проще всего)
    async function cancelEdit() {
        // Просто обновим таблицу, вернув исходные данные
        loadUsers();
    }

    // Сохранение отредактированных данных
    async function saveUserEdit(e) {
        const userId = this.dataset.id;
        const row = document.querySelector(`tr[data-user-id="${userId}"]`);
        const name = row.querySelector(`#edit-name-${userId}`).value.trim();
        const email = row.querySelector(`#edit-email-${userId}`).value.trim();
        const phone = row.querySelector(`#edit-phone-${userId}`).value.trim();
        const bio = ''; // пока не редактируем bio из таблицы

        // Простейшая валидация на клиенте
        if (!name || !email) {
            showMessage(adminPanelMessage, 'Имя и Email обязательны', true);
            return;
        }

        const payload = { name, email, phone, bio };

        try {
            const resp = await fetch(`${API_BASE}/admin/users/${userId}`, {
                method: 'PUT',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const data = await resp.json();
            if (resp.ok && data.status === 'success') {
                showMessage(adminPanelMessage, 'Данные обновлены', false);
                loadUsers(); // обновить таблицу
            } else if (data.errors) {
                // сервер вернул ошибки валидации
                let errorMsg = '';
                for (const [field, msg] of Object.entries(data.errors)) {
                    errorMsg += `${field}: ${msg}\n`;
                }
                showMessage(adminPanelMessage, errorMsg, true);
            } else {
                showMessage(adminPanelMessage, data.message || 'Ошибка обновления', true);
            }
        } catch (e) {
            showMessage(adminPanelMessage, 'Сетевая ошибка при сохранении', true);
        }
    }

    // Вход администратора
    adminLoginForm.addEventListener('submit', async function (e) {
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
    logoutAdminBtn.addEventListener('click', async function () {
        try {
            await fetch(`${API_BASE}/admin/logout`);
        } catch (e) {}
        adminLoginBlock.style.display = 'block';
        adminPanel.style.display = 'none';
        adminLoginForm.reset();
    });

    // Утилиты
    function showMessage(element, text, isError = false) {
        element.textContent = text;
        element.className = 'form-message ' + (isError ? 'error' : 'success');
        element.style.display = 'block';
        setTimeout(() => {
            element.style.display = 'none';
        }, 5000);
    }

    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return String(text).replace(/[&<>"']/g, m => map[m]);
    }

    // Старт
    checkAdminAuth();
});
