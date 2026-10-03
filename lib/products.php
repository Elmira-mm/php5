<?php
declare(strict_types=1);

/**
 * Функції для роботи з таблицею products (той самий домен, що й у практикумі №4).
 * Використовуються з JSON-ендпоінтів api_list.php і api_add.php.
 */

/**
 * Усі товари.
 */
function getAllProducts(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM products ORDER BY id')->fetchAll();
}

/**
 * Крок 4. Жива фільтрація за назвою товару (LIKE-пошук, підготовлений запит).
 */
function searchProductsByName(PDO $pdo, string $query): array
{
    $stmt = $pdo->prepare('SELECT * FROM products WHERE name LIKE :query ORDER BY id');
    $stmt->execute([':query' => '%' . $query . '%']);

    return $stmt->fetchAll();
}

/**
 * Один товар за id — потрібен одразу після INSERT, щоб повернути повний рядок у JSON.
 */
function getProductById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    return $row !== false ? $row : null;
}

/**
 * Крок 5. Додавання товару. Повертає структурований результат:
 * ['success' => bool, 'product' => array|null, 'errors' => array]
 * замість того, щоб виводити HTML — цей результат серіалізується в JSON у api_add.php.
 */
function addProduct(PDO $pdo, string $name, float $price, string $sku, int $stock): array
{
    $stmt = $pdo->prepare(
        'INSERT INTO products (name, price, sku, stock) VALUES (:name, :price, :sku, :stock)'
    );

    try {
        $stmt->execute([
            ':name'  => $name,
            ':price' => $price,
            ':sku'   => strtoupper($sku),
            ':stock' => $stock,
        ]);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            return ['success' => false, 'product' => null, 'errors' => ['sku' => 'Товар з таким SKU вже існує.']];
        }

        throw $e;
    }

    $newId = (int) $pdo->lastInsertId();
    $product = getProductById($pdo, $newId);

    return ['success' => true, 'product' => $product, 'errors' => []];
}

/**
 * Валідація полів — ті самі правила, що й у попередніх практикумах.
 */
function validateProductInput(string $name, string $price, string $sku, string $stock): array
{
    $errors = [];

    if (trim($name) === '') {
        $errors['name'] = 'Назва товару обов\'язкова.';
    }

    if ($price === '' || !is_numeric($price) || (float) $price <= 0) {
        $errors['price'] = 'Ціна має бути числом більшим за 0.';
    }

    if ($sku === '' || !preg_match('/^[A-Za-z]+-\d+$/', $sku)) {
        $errors['sku'] = 'Формат SKU: літери-дефіс-цифри, наприклад MAT-045.';
    }

    if ($stock === '' || filter_var($stock, FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]) === false) {
        $errors['stock'] = 'Залишок має бути цілим невід\'ємним числом.';
    }

    return $errors;
}
