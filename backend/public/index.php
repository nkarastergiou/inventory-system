<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/config/database.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$errorMiddleware = $app->addErrorMiddleware(true, true, true);

$app->get('/api/health', function ($request, $response) {
    $data = [
        'status' => 'ok',
        'message' => 'Inventory API is running'
    ];

    $response->getBody()->write(json_encode($data));

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(200);
});

$app->get('/api/db-test', function ($request, $response) {
    try {
        $pdo = getDatabaseConnection();

        $stmt = $pdo->query('SELECT COUNT(*) AS total_products FROM products');
        $result = $stmt->fetch();

        $data = [
            'status' => 'ok',
            'message' => 'Database connection successful',
            'total_products' => $result['total_products']
        ];

        $response->getBody()->write(json_encode($data));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);

    } catch (Exception $e) {
        $data = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];

        $response->getBody()->write(json_encode($data));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});

$app->get('/api/products', function ($request, $response) {
    try {
        $pdo = getDatabaseConnection();

        $sql = "
            SELECT 
                p.id,
                p.name,
                p.sku,
                p.description,
                p.quantity,
                p.min_stock,
                p.price,
                p.created_at,
                p.updated_at,
                c.name AS category_name,
                s.name AS supplier_name
            FROM products p
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN suppliers s ON p.supplier_id = s.id
            ORDER BY p.id DESC
        ";

        $stmt = $pdo->query($sql);
        $products = $stmt->fetchAll();

        $data = [
            'status' => 'ok',
            'products' => $products
        ];

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);

    } catch (Exception $e) {
        $data = [
            'status' => 'error',
            'message' => $e->getMessage()
        ];

        $response->getBody()->write(json_encode($data));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(500);
    }
});

$app->run();