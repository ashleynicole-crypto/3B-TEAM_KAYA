<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(array $body, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    exit;
}

function requestData(): array
{
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        header('Allow: POST');
        respond(['error' => 'Use POST to submit this form.'], 405);
    }

    try {
        $data = json_decode(file_get_contents('php://input') ?: '', true, 512, JSON_THROW_ON_ERROR);
    } catch (JsonException) {
        respond(['error' => 'The request body must contain valid JSON.'], 400);
    }

    if (!is_array($data)) {
        respond(['error' => 'The request body must be a JSON object.'], 400);
    }

    return $data;
}

function textValue(array $data, string $key): string
{
    $value = $data[$key] ?? '';
    return is_string($value) ? trim($value) : '';
}

function database(): PDO
{
    static $pdo;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $host = getenv('SUNSON_DB_HOST') ?: '127.0.0.1';
    $port = getenv('SUNSON_DB_PORT') ?: '3306';
    $name = getenv('SUNSON_DB_NAME') ?: 'sun_son_solar';
    $user = getenv('SUNSON_DB_USER') ?: 'root';
    $password = getenv('SUNSON_DB_PASSWORD');
    if ($password === false) {
        $password = '';
    }

    $dsn = "mysql:host={$host};port={$port};dbname={$name};charset=utf8mb4";
    $pdo = new PDO($dsn, $user, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);

    return $pdo;
}

function startUserSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) {
        return;
    }

    ini_set('session.use_strict_mode', '1');
    session_name('SUNSONSESSID');
    $https = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $https,
        'samesite' => 'Lax',
        'path' => '/',
    ]);
    session_start();
}

function databaseUnavailable(Throwable $error): never
{
    error_log('Sun Son Solar database error: ' . $error->getMessage());
    respond([
        'error' => 'Cannot connect to MySQL. Start MySQL in XAMPP and import database.sql in phpMyAdmin.',
    ], 503);
}
