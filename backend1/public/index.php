<?php

use Slim\Factory\AppFactory;
use DI\Container;
use Dotenv\Dotenv;
use Selective\BasePath\BasePathMiddleware;

require __DIR__ . '/../vendor/autoload.php';

// Load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Create Container
$container = new Container();
AppFactory::setContainer($container);

// Create App
$app = AppFactory::create();

// Add Body Parsing Middleware - MUST come before routing
$app->addBodyParsingMiddleware();

// Add Routing Middleware
$app->addRoutingMiddleware();

// Add Base Path Middleware
$app->add(new BasePathMiddleware($app));

// CORS Middleware - Support multiple origins and proper preflight
// NOTE: Add CORS AFTER error middleware so it wraps error responses too
$corsMiddleware = function ($request, $handler) {
    $allowedOrigins = array_filter(array_map('trim', explode(',', $_ENV['CORS_ORIGIN'] ?? '')));
    $requestOrigin = $request->getHeaderLine('Origin');

    // Default to first allowed origin if provided, otherwise use '*'
    $allowOrigin = '*';
    if (!empty($allowedOrigins)) {
        $allowOrigin = in_array($requestOrigin, $allowedOrigins, true) ? $requestOrigin : $allowedOrigins[0];
    }

    // Handle preflight requests early
    if (strtoupper($request->getMethod()) === 'OPTIONS') {
        $response = new \Slim\Psr7\Response(204);
        return $response
            ->withHeader('Access-Control-Allow-Origin', $allowOrigin)
            ->withHeader('Vary', 'Origin')
            ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
            ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
            ->withHeader('Access-Control-Allow-Credentials', 'true');
    }

    $response = $handler->handle($request);
    return $response
        ->withHeader('Access-Control-Allow-Origin', $allowOrigin)
        ->withHeader('Vary', 'Origin')
        ->withHeader('Access-Control-Allow-Headers', 'X-Requested-With, Content-Type, Accept, Origin, Authorization')
        ->withHeader('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, PATCH, OPTIONS')
        ->withHeader('Access-Control-Allow-Credentials', 'true');
};

// Handle OPTIONS requests (kept for compatibility)
$app->options('/{routes:.+}', function ($request, $response) {
    return $response->withStatus(204);
});

// Error Middleware
$errorMiddleware = $app->addErrorMiddleware(
    $_ENV['APP_DEBUG'] === 'true',
    true,
    true
);

// Add CORS middleware last so it executes first (wraps all responses including errors)
$app->add($corsMiddleware);

// Register routes
require __DIR__ . '/../src/Routes/api.php';

// Run App
$app->run();
