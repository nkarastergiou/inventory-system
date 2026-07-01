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

$app->run();