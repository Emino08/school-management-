<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Notice;
use App\Utils\Validator;

class NoticeController
{
    private $noticeModel;

    public function __construct()
    {
        $this->noticeModel = new Notice();
    }

    public function create(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        // Support both 'description' and 'details' from the frontend
        if (!isset($data['description']) && isset($data['details'])) {
            $data['description'] = $data['details'];
        }
        $errors = Validator::validate($data, ['title' => 'required', 'description' => 'required', 'date' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $allowed = ['admin_id','title','description','date','target_audience'];
            $payload = ['admin_id' => $user->id];
            foreach ($allowed as $k) {
                if ($k === 'admin_id') continue;
                if (isset($data[$k])) { $payload[$k] = $data[$k]; }
            }
            $noticeId = $this->noticeModel->create(Validator::sanitize($payload));

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Notice created successfully', 'notice_id' => $noticeId]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Creation failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getAll(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        try {
            $notices = $this->noticeModel->getNoticesByAdmin($user->id);
            $response->getBody()->write(json_encode(['success' => true, 'notices' => $notices]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch notices: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getByAudience(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');

        try {
            $notices = $this->noticeModel->getNoticesByAudience($user->id, $args['audience']);
            $response->getBody()->write(json_encode(['success' => true, 'notices' => $notices]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch notices: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function update(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');
        $data = Validator::sanitize($request->getParsedBody());
        if (!isset($data['description']) && isset($data['details'])) {
            $data['description'] = $data['details'];
        }
        // Only allow known columns
        $allowed = ['title','description','date','target_audience'];
        $data = array_intersect_key($data, array_flip($allowed));

        try {
            // Verify ownership before updating
            $notice = $this->noticeModel->findById($args['id']);
            if (!$notice) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Notice not found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            if ($notice['admin_id'] != $user->id) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Unauthorized access']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            $this->noticeModel->update($args['id'], $data);
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Notice updated successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function delete(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');

        try {
            // Verify ownership before deleting
            $notice = $this->noticeModel->findById($args['id']);
            if (!$notice) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Notice not found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            if ($notice['admin_id'] != $user->id) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Unauthorized access']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            $this->noticeModel->delete($args['id']);
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Notice deleted successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getStats(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        try {
            $adminId = $request->getAttribute('admin_id') ?? ($user->admin_id ?? $user->id);
            $stats = $this->noticeModel->getNoticeStats($adminId);

            $response->getBody()->write(json_encode(['success' => true, 'stats' => $stats]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch stats: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
