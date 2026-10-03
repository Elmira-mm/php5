<?php
declare(strict_types=1);

ob_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/products.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $query = trim((string) ($_GET['q'] ?? ''));

    $products = $query !== ''
        ? searchProductsByName($pdo, $query)
        : getAllProducts($pdo);

    ob_end_clean();
    echo json_encode($products, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['errors' => ['general' => 'Внутрішня помилка сервера.']], JSON_UNESCAPED_UNICODE);
}