<?php

use DI\Container;
use Dotenv\Dotenv;
use Slim\Factory\AppFactory;
use Slim\Psr7\Response;
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

// CORS Middleware - Support multiple origins, strict matching in production, and proper preflight responses
// NOTE: Add CORS AFTER error middleware so it wraps error responses too
$corsMiddleware = function ($request, $handler) {
    $allowedOrigins = array_filter(array_map('trim', explode(',', $_ENV['CORS_ORIGIN'] ?? '')));
    $allowedMethods = $_ENV['CORS_METHODS'] ?? 'GET,POST,PUT,PATCH,DELETE,OPTIONS';
    $allowedHeaders = $_ENV['CORS_HEADERS'] ?? 'Origin,Content-Type,Accept,Authorization,X-Requested-With';
    $maxAge = $_ENV['CORS_MAX_AGE'] ?? '86400';
    $allowCredentials = filter_var($_ENV['CORS_ALLOW_CREDENTIALS'] ?? 'true', FILTER_VALIDATE_BOOLEAN);
    $appEnv = strtolower($_ENV['APP_ENV'] ?? 'production');

    $requestOrigin = $request->getHeaderLine('Origin');
    $isPreflight = strtoupper($request->getMethod()) === 'OPTIONS';
    $originAllowed = false;
    $shouldValidateOrigin = $requestOrigin !== '';

    if ($shouldValidateOrigin) {
        if (empty($allowedOrigins)) {
            $originAllowed = $appEnv !== 'production';
        } else {
            $originAllowed = in_array($requestOrigin, $allowedOrigins, true);
        }
    }

    // Block disallowed origins early in production to avoid leaking responses
    if ($shouldValidateOrigin && !$originAllowed && $appEnv === 'production') {
        if ($isPreflight) {
            $response = new Response();
            $response->getBody()->write(json_encode(['message' => 'Origin not allowed']));
            return $response
                ->withStatus(403)
                ->withHeader('Content-Type', 'application/json');
        }

        // Continue without CORS headers so the browser enforces the block
        return $handler->handle($request);
    }

    $response = $isPreflight ? new Response(204) : $handler->handle($request);

    $allowOriginHeader = '*';
    if ($shouldValidateOrigin) {
        if ($originAllowed) {
            $allowOriginHeader = $requestOrigin;
        } elseif ($appEnv === 'production') {
            $allowOriginHeader = null;
        } else {
            $allowOriginHeader = $allowedOrigins[0] ?? '*';
        }
    }

    if ($allowOriginHeader) {
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $allowOriginHeader)
            ->withHeader('Vary', 'Origin')
            ->withHeader('Access-Control-Allow-Headers', $allowedHeaders)
            ->withHeader('Access-Control-Allow-Methods', $allowedMethods)
            ->withHeader('Access-Control-Max-Age', $maxAge);

        if ($allowOriginHeader !== '*' && $allowCredentials) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        } else {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'false');
        }
    }

    return $response;
};

// Handle OPTIONS requests (kept for compatibility)
$app->options('/{routes:.+}', function ($request, $response) {
    return $response->withStatus(204);
});

// Error Middleware
$errorMiddleware = $app->addErrorMiddleware(
    filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
    true,
    true
);

// Security Headers Middleware for baseline hardening
$securityHeaders = function ($request, $handler) {
    $response = $handler->handle($request);

    return $response
        ->withHeader('X-Frame-Options', 'SAMEORIGIN')
        ->withHeader('X-Content-Type-Options', 'nosniff')
        ->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
        ->withHeader('X-XSS-Protection', '1; mode=block')
        ->withHeader('Permissions-Policy', 'geolocation=(), microphone=(), camera=()')
        ->withHeader('Strict-Transport-Security', 'max-age=63072000; includeSubDomains; preload');
};

// Add middleware so CORS executes first, followed by security headers
$app->add($corsMiddleware);
$app->add($securityHeaders);

// Register routes
require __DIR__ . '/../src/Routes/api.php';

// Run App
$app->run();
