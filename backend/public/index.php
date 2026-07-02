<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/config/database.php';

use Slim\Factory\AppFactory;

$app = AppFactory::create();

$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

$app->add(function ($request, $handler) {
    $response = $handler->handle($request);

    return $response
        ->withHeader('Access-Control-Allow-Origin', '*')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS');
});

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

$app->options('/{routes:.+}', function ($request, $response) {
    return $response;
});

$app->get('/api/categories', function ($request, $response) {
    try {
        $pdo = getDatabaseConnection();

        $stmt = $pdo->query('SELECT id, name FROM categories ORDER BY name ASC');
        $categories = $stmt->fetchAll();

        $data = [
            'status' => 'ok',
            'categories' => $categories
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

$app->get('/api/suppliers', function ($request, $response) {
    try {
        $pdo = getDatabaseConnection();

        $stmt = $pdo->query('SELECT id, name FROM suppliers ORDER BY name ASC');
        $suppliers = $stmt->fetchAll();

        $data = [
            'status' => 'ok',
            'suppliers' => $suppliers
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

$app->post('/api/products', function ($request, $response) {
    try {
        $pdo = getDatabaseConnection();

        $body = $request->getParsedBody();

        $name = trim($body['name'] ?? '');
        $sku = trim($body['sku'] ?? '');
        $description = trim($body['description'] ?? '');
        $categoryId = $body['category_id'] ?? null;
        $supplierId = $body['supplier_id'] ?? null;
        $quantity = $body['quantity'] ?? 0;
        $minStock = $body['min_stock'] ?? 5;
        $price = $body['price'] ?? 0;

        if ($name === '' || $sku === '') {
            $data = [
                'status' => 'error',
                'message' => 'Name and SKU are required'
            ];

            $response->getBody()->write(json_encode($data));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $sql = "
            INSERT INTO products 
            (category_id, supplier_id, name, sku, description, quantity, min_stock, price)
            VALUES
            (:category_id, :supplier_id, :name, :sku, :description, :quantity, :min_stock, :price)
        ";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':category_id' => $categoryId,
            ':supplier_id' => $supplierId,
            ':name' => $name,
            ':sku' => $sku,
            ':description' => $description,
            ':quantity' => $quantity,
            ':min_stock' => $minStock,
            ':price' => $price
        ]);

        $data = [
            'status' => 'ok',
            'message' => 'Product created successfully',
            'product_id' => $pdo->lastInsertId()
        ];

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);

    } catch (PDOException $e) {
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

$app->delete('/api/products/{id}', function ($request, $response, $args) {
    try {
        $pdo = getDatabaseConnection();

        $id = (int) $args['id'];

        if ($id <= 0) {
            $data = [
                'status' => 'error',
                'message' => 'Invalid product ID'
            ];

            $response->getBody()->write(json_encode($data));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $checkStmt = $pdo->prepare('SELECT id FROM products WHERE id = :id');
        $checkStmt->execute([':id' => $id]);
        $product = $checkStmt->fetch();

        if (!$product) {
            $data = [
                'status' => 'error',
                'message' => 'Product not found'
            ];

            $response->getBody()->write(json_encode($data));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $deleteStmt = $pdo->prepare('DELETE FROM products WHERE id = :id');
        $deleteStmt->execute([':id' => $id]);

        $data = [
            'status' => 'ok',
            'message' => 'Product deleted successfully'
        ];

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(200);

    } catch (PDOException $e) {
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