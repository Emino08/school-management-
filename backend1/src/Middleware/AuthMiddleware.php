<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Psr\Http\Message\ResponseInterface as Response;
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
            $decoded = \App\Utils\JWT::decode($token);

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
        } catch (\Firebase\JWT\ExpiredException $e) {
            error_log('JWT Token Expired: ' . $e->getMessage());
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Token has expired',
                'error' => 'TOKEN_EXPIRED'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        } catch (\Firebase\JWT\SignatureInvalidException $e) {
            // Log the signature issue for debugging
            error_log('JWT Signature Invalid - This usually means JWT_SECRET has changed or token is from different server');
            
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid token signature - Please log in again',
                'error' => 'INVALID_SIGNATURE',
                'hint' => 'Your session is from an old login. Please log out and log in again.'
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        } catch (\PDOException $e) {
            // Database error during token validation
            error_log('Database Error in AuthMiddleware: ' . $e->getMessage());
            error_log('SQL Error Code: ' . ($e->getCode() ?? 'none'));
            error_log('SQL State: ' . ($e->errorInfo[0] ?? 'none'));
            
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid or expired token',
                'error' => 'INVALID_TOKEN',
                'debug' => $_ENV['APP_DEBUG'] ?? false ? $e->getMessage() : null
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        } catch (\Exception $e) {
            // Log the actual error for debugging
            error_log('JWT Decode Error: ' . $e->getMessage());
            error_log('Exception Type: ' . get_class($e));
            
            $response = new SlimResponse();
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Invalid or expired token',
                'error' => 'INVALID_TOKEN',
                'debug' => $_ENV['APP_DEBUG'] ?? false ? $e->getMessage() : null
            ]));
            return $response
                ->withHeader('Content-Type', 'application/json')
                ->withStatus(401);
        }
    }
}
