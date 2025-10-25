<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\Complaint;
use App\Utils\Validator;

class ComplaintController
{
    private $complaintModel;

    public function __construct()
    {
        $this->complaintModel = new Complaint();
    }

    public function create(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $errors = Validator::validate($data, ['complaint' => 'required', 'user_type' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $data['admin_id'] = $user->admin_id ?? $user->id;
            $data['user_id'] = $user->id;
            $complaintId = $this->complaintModel->create(Validator::sanitize($data));

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Complaint submitted successfully', 'complaint_id' => $complaintId]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Submission failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getAll(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        try {
            $complaints = $this->complaintModel->getComplaintsByAdmin($user->id);
            $response->getBody()->write(json_encode(['success' => true, 'complaints' => $complaints]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch complaints: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getUserComplaints(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        try {
            $complaints = $this->complaintModel->getComplaintsByUser($user->id, $user->role);
            $response->getBody()->write(json_encode(['success' => true, 'complaints' => $complaints]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch complaints: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function updateStatus(Request $request, Response $response, $args)
    {
        $data = $request->getParsedBody();

        $errors = Validator::validate($data, ['status' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $this->complaintModel->updateComplaintStatus($args['id'], $data['status'], $data['response'] ?? null);
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Complaint status updated successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
