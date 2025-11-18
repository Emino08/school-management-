<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Slim\Psr7\Response as SlimResponse;

class AuthMiddleware
{
    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Authorization header missing'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }

        // Extract token from "Bearer <token>"
        $token = str_replace('Bearer ', '', $authHeader);

        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));

            $adminId = $decoded->admin_id ?? null;
            $accountId = $decoded->account_id ?? $decoded->id ?? null;

            // Backfill admin_id for legacy admin tokens
            if (!$adminId && isset($decoded->role) && strtolower($decoded->role) === 'admin') {
                $adminId = $decoded->id ?? null;
                $decoded->admin_id = $adminId;
            }

            if (!isset($decoded->account_id)) {
                $decoded->account_id = $accountId;
            }

            // Add user info to request attributes
            $request = $request->withAttribute('user', $decoded);
            $request = $request->withAttribute('user_id', $accountId);
            $request = $request->withAttribute('role', $decoded->role ?? null);
            $request = $request->withAttribute('admin_id', $adminId);
            $request = $request->withAttribute('account_id', $accountId);

            return $handler->handle($request);
        } catch (\Exception $e) {
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid or expired token'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
    }
}
