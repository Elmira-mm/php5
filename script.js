// Практикум №5 — AJAX/JSON без перезавантаження сторінки.

const resultsBody = document.getElementById('results');
const searchInput = document.getElementById('search');
const addForm = document.getElementById('add-form');
const addSubmitBtn = document.getElementById('add-submit');
const pageMessage = document.getElementById('page-message');

/**
 * Екранування тексту перед вставкою в innerHTML — базовий захист від XSS
 * (детальніше буде в практикумі №8, тут — мінімально необхідне).
 */
function escapeHtml(value) {
    const div = document.createElement('div');
    div.textContent = String(value);
    return div.innerHTML;
}

function showPageMessage(text, isError = true) {
    pageMessage.textContent = text;
    pageMessage.hidden = false;
    pageMessage.className = isError ? 'alert alert-error' : 'alert alert-success';
}

function hidePageMessage() {
    pageMessage.hidden = true;
}

/**
 * Крок 3/4. Малює отриманий список товарів у контейнер #results.
 */
function renderProducts(products) {
    if (!Array.isArray(products) || products.length === 0) {
        resultsBody.innerHTML = '<tr><td colspan="5" class="empty-row">Товарів не знайдено.</td></tr>';
        return;
    }

    resultsBody.innerHTML = products.map((product) => {
        const inStock = Number(product.stock) > 0;
        const statusClass = inStock ? 'badge-in' : 'badge-out';
        const statusText = inStock ? 'В наявності' : 'Немає в наявності';
        const rowClass = inStock ? 'row-in' : 'row-out';

        return `
            <tr class="${rowClass}">
                <td><span class="product-name">${escapeHtml(product.name)}</span></td>
                <td class="stock">${escapeHtml(product.sku)}</td>
                <td><span class="price">${Number(product.price).toFixed(2)}</span><span class="price-currency">грн</span></td>
                <td class="stock">${escapeHtml(product.stock)}</td>
                <td><span class="badge ${statusClass}">${statusText}</span></td>
            </tr>
        `;
    }).join('');
}

/**
 * Крок 3/4/7. Завантажує список (усі товари або відфільтровані за назвою),
 * обробляючи мережеві й серверні помилки.
 */
async function loadProducts(query = '') {
    resultsBody.innerHTML = '<tr><td colspan="5" class="empty-row">Завантаження…</td></tr>';

    try {
        const url = query
            ? `api_list.php?q=${encodeURIComponent(query)}`
            : 'api_list.php';

        const response = await fetch(url);

        if (!response.ok) {
            throw new Error(`Сервер повернув помилку: ${response.status}`);
        }

        const products = await response.json();
        renderProducts(products);
    } catch (error) {
        resultsBody.innerHTML = '<tr><td colspan="5" class="empty-row">Не вдалося завантажити список товарів. Спробуйте оновити сторінку.</td></tr>';
        console.error('loadProducts failed:', error);
    }
}

// Крок 3. Початкове завантаження списку при відкритті сторінки.
loadProducts();

// Крок 4. Живий пошук — на кожну зміну поля, без перезавантаження.
searchInput.addEventListener('input', () => {
    loadProducts(searchInput.value.trim());
});

/**
 * Скидає повідомлення про помилки полів форми додавання.
 */
function clearFormErrors() {
    document.querySelectorAll('#add-form .error').forEach((el) => {
        el.textContent = '';
    });
}

function showFormErrors(errors) {
    clearFormErrors();
    Object.entries(errors).forEach(([field, message]) => {
        const el = document.querySelector(`#add-form .error[data-error-for="${field}"]`);
        if (el) {
            el.textContent = message;
        } else {
            // Поле "general" або невідоме — показуємо загальним повідомленням.
            showPageMessage(message, true);
        }
    });
}

// Крок 6/7. Додавання товару через AJAX, без перезавантаження сторінки.
addForm.addEventListener('submit', async (event) => {
    event.preventDefault();
    clearFormErrors();
    hidePageMessage();
    addSubmitBtn.disabled = true;

    const payload = {
        name: document.getElementById('name').value.trim(),
        price: document.getElementById('price').value.trim(),
        sku: document.getElementById('sku').value.trim(),
        stock: document.getElementById('stock').value.trim(),
    };

    try {
        const response = await fetch('api_add.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
        });

        const data = await response.json();

        if (!response.ok) {
            // 422 (валідація) або 409 (дублікат SKU) — показуємо помилки біля полів.
            showFormErrors(data.errors || { general: 'Не вдалося додати товар.' });
            return;
        }

        // Успіх (201) — оновлюємо список без перезавантаження сторінки.
        addForm.reset();
        showPageMessage(`Товар «${data.product.name}» додано.`, false);
        loadProducts(searchInput.value.trim());
    } catch (error) {
        showPageMessage('Помилка мережі: не вдалося зв\'язатися з сервером.', true);
        console.error('addProduct failed:', error);
    } finally {
        addSubmitBtn.disabled = false;
    }
});
