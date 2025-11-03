<?php

namespace App\Controllers;

use App\Models\FinanceUser;
use App\Utils\JWT;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class FinanceUserController
{
    private $financeUserModel;

    public function __construct()
    {
        $this->financeUserModel = new FinanceUser();
    }

    /**
     * Finance user login (PSR-7)
     */
    public function login(Request $request, Response $response)
    {
        try {
            $data = $request->getParsedBody() ?? [];
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';

            if (empty($email) || empty($password)) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Email and password are required'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }

            $user = $this->financeUserModel->verifyPassword($email, $password);

            if (!$user) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Invalid email or password'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(401);
            }

            if (!(int)$user['is_active']) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Your account has been deactivated. Please contact administrator.'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            $token = JWT::encode([
                'id' => $user['id'],
                'role' => 'FinanceUser',
                'email' => $user['email'],
                'admin_id' => $user['admin_id']
            ]);

            $response->getBody()->write(json_encode([
                'success' => true,
                'message' => 'Login successful',
                'role' => 'FinanceUser',
                'token' => $token,
                'user' => $user
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get finance user profile
     */
    public function getProfile(Request $request, Response $response)
    {
        try {
            $user = $request->getAttribute('user');
            $userId = $user->id ?? null;
            $adminId = $user->admin_id ?? null;

            $found = $this->financeUserModel->findByIdAndAdmin($userId, $adminId);

            if (!$found) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'User not found'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            $response->getBody()->write(json_encode([
                'success' => true,
                'user' => $found
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch profile: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Update finance user profile
     */
    public function updateProfile(Request $request, Response $response)
    {
        try {
            $user = $request->getAttribute('user');
            $userId = $user->id ?? null;
            $adminId = $user->admin_id ?? null;
            $data = $request->getParsedBody() ?? [];

            $result = $this->financeUserModel->updateFinanceUser($userId, $adminId, $data);

            if ($result) {
                $response->getBody()->write(json_encode([
                    'success' => true,
                    'message' => 'Profile updated successfully'
                ]));
                return $response->withHeader('Content-Type', 'application/json');
            }

            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update profile'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to update profile: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
