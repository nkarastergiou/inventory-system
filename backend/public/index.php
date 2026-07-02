<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../src/config/database.php';

use Slim\Factory\AppFactory;

function getBearerTokenFromRequest($request): ?string
{
    $authorizationHeader = $request->getHeaderLine('Authorization');

    if (!$authorizationHeader) {
        return null;
    }

    if (preg_match('/Bearer\s+(.*)$/i', $authorizationHeader, $matches)) {
        return trim($matches[1]);
    }

    return null;
}

function getAuthenticatedUserFromRequest($request): ?array
{
    $token = getBearerTokenFromRequest($request);

    if (!$token) {
        return null;
    }

    $pdo = getDatabaseConnection();

    $stmt = $pdo->prepare("
        SELECT 
            users.id,
            users.name,
            users.email
        FROM auth_tokens
        INNER JOIN users ON auth_tokens.user_id = users.id
        WHERE auth_tokens.token = :token
          AND (auth_tokens.expires_at IS NULL OR auth_tokens.expires_at > NOW())
        LIMIT 1
    ");

    $stmt->execute([':token' => $token]);

    $user = $stmt->fetch();

    return $user ?: null;
}

function unauthorizedResponse($response)
{
    $data = [
        'status' => 'error',
        'message' => 'Unauthorized'
    ];

    $response->getBody()->write(json_encode($data));

    return $response
        ->withHeader('Content-Type', 'application/json')
        ->withStatus(401);
}

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
        $user = getAuthenticatedUserFromRequest($request);

if (!$user) {
    return unauthorizedResponse($response);
}
        $pdo = getDatabaseConnection();

        $sql = "
            SELECT 
                p.id,
                p.category_id,
                p.supplier_id,
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
        $user = getAuthenticatedUserFromRequest($request);

if (!$user) {
    return unauthorizedResponse($response);
}
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
        $user = getAuthenticatedUserFromRequest($request);

if (!$user) {
    return unauthorizedResponse($response);
}
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
        $user = getAuthenticatedUserFromRequest($request);

if (!$user) {
    return unauthorizedResponse($response);
}
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
        $user = getAuthenticatedUserFromRequest($request);

if (!$user) {
    return unauthorizedResponse($response);
}
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

$app->put('/api/products/{id}', function ($request, $response, $args) {
    try {
        $user = getAuthenticatedUserFromRequest($request);

if (!$user) {
    return unauthorizedResponse($response);
}
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

        $sql = "
            UPDATE products
            SET 
                category_id = :category_id,
                supplier_id = :supplier_id,
                name = :name,
                sku = :sku,
                description = :description,
                quantity = :quantity,
                min_stock = :min_stock,
                price = :price
            WHERE id = :id
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
            ':price' => $price,
            ':id' => $id
        ]);

        $data = [
            'status' => 'ok',
            'message' => 'Product updated successfully'
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

$app->post('/api/stock-movements', function ($request, $response) {
    try {
        $user = getAuthenticatedUserFromRequest($request);

if (!$user) {
    return unauthorizedResponse($response);
}
        $pdo = getDatabaseConnection();

        $body = $request->getParsedBody();

        $productId = (int) ($body['product_id'] ?? 0);
        $movementType = $body['movement_type'] ?? '';
        $quantity = (int) ($body['quantity'] ?? 0);
        $note = trim($body['note'] ?? '');

        if ($productId <= 0 || !in_array($movementType, ['in', 'out']) || $quantity <= 0) {
            $data = [
                'status' => 'error',
                'message' => 'Invalid stock movement data'
            ];

            $response->getBody()->write(json_encode($data));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $pdo->beginTransaction();

        $productStmt = $pdo->prepare('SELECT id, quantity FROM products WHERE id = :id');
        $productStmt->execute([':id' => $productId]);
        $product = $productStmt->fetch();

        if (!$product) {
            $pdo->rollBack();

            $data = [
                'status' => 'error',
                'message' => 'Product not found'
            ];

            $response->getBody()->write(json_encode($data));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(404);
        }

        $currentQuantity = (int) $product['quantity'];

        if ($movementType === 'out' && $quantity > $currentQuantity) {
            $pdo->rollBack();

            $data = [
                'status' => 'error',
                'message' => 'Not enough stock available'
            ];

            $response->getBody()->write(json_encode($data));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $newQuantity = $movementType === 'in'
            ? $currentQuantity + $quantity
            : $currentQuantity - $quantity;

        $movementStmt = $pdo->prepare("
            INSERT INTO stock_movements 
            (product_id, movement_type, quantity, note)
            VALUES 
            (:product_id, :movement_type, :quantity, :note)
        ");

        $movementStmt->execute([
            ':product_id' => $productId,
            ':movement_type' => $movementType,
            ':quantity' => $quantity,
            ':note' => $note
        ]);

        $updateStmt = $pdo->prepare("
            UPDATE products 
            SET quantity = :quantity 
            WHERE id = :id
        ");

        $updateStmt->execute([
            ':quantity' => $newQuantity,
            ':id' => $productId
        ]);

        $pdo->commit();

        $data = [
            'status' => 'ok',
            'message' => 'Stock movement created successfully',
            'new_quantity' => $newQuantity
        ];

        $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE));

        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(201);

    } catch (Exception $e) {
        if (isset($pdo) && $pdo->inTransaction()) {
            $pdo->rollBack();
        }

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

$app->get('/api/stock-movements', function ($request, $response) {
    try {
        $user = getAuthenticatedUserFromRequest($request);

if (!$user) {
    return unauthorizedResponse($response);
}
        $pdo = getDatabaseConnection();

        $sql = "
            SELECT 
                sm.id,
                sm.product_id,
                p.name AS product_name,
                p.sku,
                sm.movement_type,
                sm.quantity,
                sm.note,
                sm.created_at
            FROM stock_movements sm
            INNER JOIN products p ON sm.product_id = p.id
            ORDER BY sm.id DESC
        ";

        $stmt = $pdo->query($sql);
        $movements = $stmt->fetchAll();

        $data = [
            'status' => 'ok',
            'movements' => $movements
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

$app->post('/api/login', function ($request, $response) {
    try {
        $pdo = getDatabaseConnection();

        $body = $request->getParsedBody();

        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if ($email === '' || $password === '') {
            $data = [
                'status' => 'error',
                'message' => 'Email and password are required'
            ];

            $response->getBody()->write(json_encode($data));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(400);
        }

        $stmt = $pdo->prepare('SELECT id, name, email, password_hash FROM users WHERE email = :email');
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            $data = [
                'status' => 'error',
                'message' => 'Invalid email or password'
            ];

            $response->getBody()->write(json_encode($data));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 day'));

        $tokenStmt = $pdo->prepare("
            INSERT INTO auth_tokens (user_id, token, expires_at)
            VALUES (:user_id, :token, :expires_at)
        ");

        $tokenStmt->execute([
            ':user_id' => $user['id'],
            ':token' => $token,
            ':expires_at' => $expiresAt
        ]);

        $data = [
            'status' => 'ok',
            'message' => 'Login successful',
            'token' => $token,
            'user' => [
                'id' => $user['id'],
                'name' => $user['name'],
                'email' => $user['email']
            ]
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

$app->get('/api/me', function ($request, $response) {
    try {
        $pdo = getDatabaseConnection();

        $token = getBearerTokenFromRequest($request);

        if (!$token) {
            $data = [
                'status' => 'error',
                'message' => 'Missing authentication token'
            ];

            $response->getBody()->write(json_encode($data));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        $stmt = $pdo->prepare("
            SELECT 
                users.id,
                users.name,
                users.email
            FROM auth_tokens
            INNER JOIN users ON auth_tokens.user_id = users.id
            WHERE auth_tokens.token = :token
              AND (auth_tokens.expires_at IS NULL OR auth_tokens.expires_at > NOW())
            LIMIT 1
        ");

        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch();

        if (!$user) {
            $data = [
                'status' => 'error',
                'message' => 'Invalid or expired token'
            ];

            $response->getBody()->write(json_encode($data));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        $data = [
            'status' => 'ok',
            'user' => $user
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

$app->post('/api/logout', function ($request, $response) {
    try {
        $pdo = getDatabaseConnection();

        $token = getBearerTokenFromRequest($request);

        if (!$token) {
            $data = [
                'status' => 'error',
                'message' => 'Missing authentication token'
            ];

            $response->getBody()->write(json_encode($data));

            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        $stmt = $pdo->prepare('DELETE FROM auth_tokens WHERE token = :token');
        $stmt->execute([':token' => $token]);

        $data = [
            'status' => 'ok',
            'message' => 'Logout successful'
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