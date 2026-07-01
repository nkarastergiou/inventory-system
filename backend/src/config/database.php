<?php

function getDatabaseConnection(): PDO
{
    $envPath = __DIR__ . '/../../.env';

    if (!file_exists($envPath)) {
        throw new Exception('.env file not found');
    }

    $env = parse_ini_file($envPath);

    $host = $env['DB_HOST'];
    $dbName = $env['DB_NAME'];
    $user = $env['DB_USER'];
    $pass = $env['DB_PASS'];

    $dsn = "mysql:host={$host};dbname={$dbName};charset=utf8mb4";

    return new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
}