<?php
declare(strict_types=1);

ob_start();

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/lib/products.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        ob_end_clean();
        http_response_code(405);
        echo json_encode(['errors' => ['general' => 'Дозволено лише POST-запити.']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Приймаємо дані або як звичайну форму ($_POST), або як JSON-тіло запиту.
    $contentType = $_SERVER['CONTENT_TYPE'] ?? '';

    if (str_contains($contentType, 'application/json')) {
        $input = json_decode(file_get_contents('php://input'), true) ?? [];
    } else {
        $input = $_POST;
    }

    $name  = trim((string) ($input['name'] ?? ''));
    $price = trim((string) ($input['price'] ?? ''));
    $sku   = trim((string) ($input['sku'] ?? ''));
    $stock = trim((string) ($input['stock'] ?? ''));

    $errors = validateProductInput($name, $price, $sku, $stock);

    if (!empty($errors)) {
        ob_end_clean();
        http_response_code(422);
        echo json_encode(['errors' => $errors], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $result = addProduct($pdo, $name, (float) $price, $sku, (int) $stock);

    if (!$result['success']) {
        ob_end_clean();
        http_response_code(409);
        echo json_encode(['errors' => $result['errors']], JSON_UNESCAPED_UNICODE);
        exit;
    }

    ob_end_clean();
    http_response_code(201);
    echo json_encode(['product' => $result['product']], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    ob_end_clean();
    http_response_code(500);
    echo json_encode(['errors' => ['general' => 'Внутрішня помилка сервера.']], JSON_UNESCAPED_UNICODE);
}