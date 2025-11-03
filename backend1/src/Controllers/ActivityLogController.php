<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Utils\ActivityLogger;

class ActivityLogController
{
    private $logger;

    public function __construct()
    {
        $database = new \App\Config\Database();
        $this->logger = new ActivityLogger($database->getConnection());
    }

    /**
     * Get activity logs
     */
    public function getLogs(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();

        // Only admin can view all logs
        if ($user->role !== 'Admin') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized access'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        try {
            $userId = $params['user_id'] ?? null;
            $userType = $params['user_type'] ?? null;
            $activityType = $params['activity_type'] ?? null;
            $entityType = $params['entity_type'] ?? null;
            $limit = isset($params['limit']) ? (int)$params['limit'] : 100;
            $offset = isset($params['offset']) ? (int)$params['offset'] : 0;

            $logs = $this->logger->getLogs(
                $userId,
                $userType,
                $activityType,
                $entityType,
                $limit,
                $offset
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'logs' => $logs,
                'count' => count($logs)
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch logs: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get activity statistics
     */
    public function getStats(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();

        // Only admin can view stats
        if ($user->role !== 'Admin') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized access'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        try {
            $userId = $params['user_id'] ?? null;
            $userType = $params['user_type'] ?? null;

            $stats = $this->logger->getStats($userId, $userType);

            $response->getBody()->write(json_encode([
                'success' => true,
                'stats' => $stats
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch statistics: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Get user's own activity logs
     */
    public function getMyLogs(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();

        try {
            $limit = isset($params['limit']) ? (int)$params['limit'] : 50;
            $offset = isset($params['offset']) ? (int)$params['offset'] : 0;

            $logs = $this->logger->getLogs(
                $user->id,
                $user->role,
                null,
                null,
                $limit,
                $offset
            );

            $response->getBody()->write(json_encode([
                'success' => true,
                'logs' => $logs,
                'count' => count($logs)
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch logs: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    /**
     * Export activity logs to CSV
     */
    public function export(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();

        // Only admin can export logs
        if ($user->role !== 'Admin') {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Unauthorized access'
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
        }

        try {
            $userId = $params['user_id'] ?? null;
            $userType = $params['user_type'] ?? null;
            $activityType = $params['activity_type'] ?? null;
            $entityType = $params['entity_type'] ?? null;

            $logs = $this->logger->getLogs(
                $userId,
                $userType,
                $activityType,
                $entityType,
                10000, // Export up to 10,000 records
                0
            );

            // Generate CSV
            $csv = "ID,User ID,User Type,Activity Type,Entity Type,Entity ID,Description,IP Address,Timestamp\n";

            foreach ($logs as $log) {
                $csv .= sprintf(
                    "%s,%s,%s,%s,%s,%s,\"%s\",%s,%s\n",
                    $log['id'] ?? '',
                    $log['user_id'] ?? '',
                    $log['user_type'] ?? '',
                    $log['activity_type'] ?? '',
                    $log['entity_type'] ?? '',
                    $log['entity_id'] ?? '',
                    str_replace('"', '""', $log['description'] ?? ''),
                    $log['ip_address'] ?? '',
                    $log['created_at'] ?? ''
                );
            }

            $filename = 'activity_logs_' . date('Y-m-d_His') . '.csv';

            $response->getBody()->write($csv);
            return $response
                ->withHeader('Content-Type', 'text/csv')
                ->withHeader('Content-Disposition', 'attachment; filename="' . $filename . '"');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to export logs: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
