document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('applicationForm');
    const messageDiv = document.getElementById('formMessage');
    const submitBtn = document.getElementById('submitBtn');

    if (!form) return;

    // Валидация на клиенте (дублирует серверную)
    function validateForm(data) {
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
        return errors;
    }

    // Отображение ошибок
    function showErrors(errors) {
        // Очистить предыдущие
        document.querySelectorAll('.error').forEach(el => el.textContent = '');
        for (const [field, msg] of Object.entries(errors)) {
            const errorSpan = document.getElementById(`${field}-error`);
            if (errorSpan) {
                errorSpan.textContent = msg;
            }
        }
    }

    // Отображение результата
    function showMessage(html, isError = false) {
        messageDiv.innerHTML = html;
        messageDiv.className = isError ? 'message error' : 'message success';
    }

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        submitBtn.disabled = true;
        submitBtn.textContent = 'Отправка...';

        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        // Клиентская валидация
        const clientErrors = validateForm(data);
        if (Object.keys(clientErrors).length > 0) {
            showErrors(clientErrors);
            showMessage('<ul><li>Исправьте ошибки в форме</li></ul>', true);
            submitBtn.disabled = false;
            submitBtn.textContent = 'Отправить заявку';
            return;
        }

        try {
            const response = await fetch('/api', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                const msg = `
                    <p>Регистрация успешна!</p>
                    <p>Логин: <strong>${result.login}</strong></p>
                    <p>Пароль: <strong>${result.password}</strong></p>
                    <p>Ссылка на профиль: <a href="${result.profile_url}">${result.profile_url}</a></p>
                `;
                showMessage(msg, false);
                form.reset();
                // очищаем ошибки
                document.querySelectorAll('.error').forEach(el => el.textContent = '');
            } else if (response.status === 422 && result.errors) {
                // Ошибки валидации с сервера
                showErrors(result.errors);
                showMessage('<ul><li>Проверьте правильность заполнения полей</li></ul>', true);
            } else {
                showMessage(`<p>Ошибка: ${result.message || 'Неизвестная ошибка'}</p>`, true);
            }
        } catch (error) {
            showMessage('<p>Произошла сетевая ошибка. Попробуйте позже.</p>', true);
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Отправить заявку';
        }
    });
});
