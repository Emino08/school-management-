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

        $errors = Validator::validate($data, ['title' => 'required', 'description' => 'required', 'date' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $data['admin_id'] = $user->id;
            $noticeId = $this->noticeModel->create(Validator::sanitize($data));

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
        $data = Validator::sanitize($request->getParsedBody());
        unset($data['id'], $data['admin_id']);

        try {
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
        try {
            $this->noticeModel->delete($args['id']);
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Notice deleted successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
