<?php

require __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Utils\JWT;

$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

// Get the Authorization header
$authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
if (!$authHeader && function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
}

$result = [
    'timestamp' => date('Y-m-d H:i:s'),
    'request_method' => $_SERVER['REQUEST_METHOD'],
    'auth_header_present' => !empty($authHeader),
    'auth_header_preview' => $authHeader ? substr($authHeader, 0, 30) . '...' : null,
];

if (!$authHeader) {
    $result['error'] = 'No Authorization header found';
    $result['hint'] = 'Make sure the Authorization header is being sent';
    echo json_encode($result, JSON_PRETTY_PRINT);
    exit;
}

// Extract token
$token = str_replace('Bearer ', '', $authHeader);
$result['token_length'] = strlen($token);

try {
    // Try to decode the token
    $decoded = JWT::decode($token);
    
    $result['success'] = true;
    $result['token_valid'] = true;
    $result['decoded'] = [
        'id' => $decoded->id ?? null,
        'role' => $decoded->role ?? null,
        'email' => $decoded->email ?? null,
        'admin_id' => $decoded->admin_id ?? null,
        'account_id' => $decoded->account_id ?? null,
        'issued_at' => isset($decoded->iat) ? date('Y-m-d H:i:s', $decoded->iat) : null,
        'expires_at' => isset($decoded->exp) ? date('Y-m-d H:i:s', $decoded->exp) : null,
        'time_remaining' => isset($decoded->exp) ? max(0, $decoded->exp - time()) : null,
        'time_remaining_minutes' => isset($decoded->exp) ? max(0, floor(($decoded->exp - time()) / 60)) : null,
    ];
    
    if (isset($decoded->exp) && $decoded->exp < time()) {
        $result['warning'] = 'Token has expired!';
        $result['expired_since'] = floor((time() - $decoded->exp) / 60) . ' minutes ago';
    }
    
} catch (\Firebase\JWT\ExpiredException $e) {
    $result['success'] = false;
    $result['error'] = 'TOKEN_EXPIRED';
    $result['message'] = $e->getMessage();
} catch (\Firebase\JWT\SignatureInvalidException $e) {
    $result['success'] = false;
    $result['error'] = 'INVALID_SIGNATURE';
    $result['message'] = $e->getMessage();
    $result['hint'] = 'JWT secret key mismatch or token corrupted';
} catch (\Exception $e) {
    $result['success'] = false;
    $result['error'] = 'INVALID_TOKEN';
    $result['message'] = $e->getMessage();
}

echo json_encode($result, JSON_PRETTY_PRINT);
