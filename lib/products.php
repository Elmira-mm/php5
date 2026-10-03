<?php
declare(strict_types=1);

function getAllProducts(PDO $pdo): array
{
    return $pdo->query('SELECT * FROM products ORDER BY id')->fetchAll();
}

function searchProductsByName(PDO $pdo, string $query): array
{
    $stmt = $pdo->prepare('SELECT * FROM products WHERE name LIKE :query ORDER BY id');
    $stmt->execute([':query' => '%' . $query . '%']);

    return $stmt->fetchAll();
}

function getProductById(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();

    return $row !== false ? $row : null;
}

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
