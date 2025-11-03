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

        $errors = Validator::validate($data, ['complaint' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Determine user type from authenticated user role
            $userType = strtolower($user->role ?? 'student');

            // Only include valid database columns
            $complaintData = [
                'admin_id' => $user->admin_id ?? $user->id,
                'user_id' => $user->id,
                'user_type' => $userType,
                'complaint' => $data['complaint']
            ];

            // Add optional fields if provided
            if (isset($data['title']) && !empty($data['title'])) {
                $complaintData['title'] = $data['title'];
            }
            if (isset($data['teacher_id']) && !empty($data['teacher_id'])) {
                $complaintData['teacher_id'] = $data['teacher_id'];
                $complaintData['category'] = 'teacher'; // Auto-set category if teacher is selected
            }
            if (isset($data['category']) && !empty($data['category'])) {
                $complaintData['category'] = $data['category'];
            }
            if (isset($data['priority']) && !empty($data['priority'])) {
                $complaintData['priority'] = $data['priority'];
            }

            $complaintId = $this->complaintModel->create(Validator::sanitize($complaintData));

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
            // Get query parameters for filtering
            $params = $request->getQueryParams();
            $status = $params['status'] ?? null;
            $category = $params['category'] ?? null;
            $priority = $params['priority'] ?? null;

            $adminId = $user->role === 'Admin' ? $user->id : $user->admin_id;
            $complaints = $this->complaintModel->getComplaintsWithDetails($adminId, $status, $category, $priority);

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
            $userType = strtolower($user->role ?? 'student');
            $complaints = $this->complaintModel->getComplaintsByUserWithDetails($user->id, $userType);

            $response->getBody()->write(json_encode(['success' => true, 'complaints' => $complaints]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch complaints: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getStats(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');

        try {
            $adminId = $user->role === 'Admin' ? $user->id : $user->admin_id;
            $stats = $this->complaintModel->getComplaintStats($adminId);

            $response->getBody()->write(json_encode(['success' => true, 'stats' => $stats]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch stats: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getById(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');

        try {
            $complaint = $this->complaintModel->getComplaintWithDetails($args['id']);

            if (!$complaint) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Complaint not found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Check authorization
            $adminId = $user->role === 'Admin' ? $user->id : $user->admin_id;
            if ($complaint['admin_id'] != $adminId && $complaint['user_id'] != $user->id) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Unauthorized access']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            $response->getBody()->write(json_encode(['success' => true, 'complaint' => $complaint]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch complaint: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function updateStatus(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');
        $data = $request->getParsedBody();

        $errors = Validator::validate($data, ['status' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            // Verify authorization before updating
            $complaint = $this->complaintModel->findById($args['id']);
            if (!$complaint) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Complaint not found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Only admin can update complaint status
            $adminId = $user->role === 'Admin' ? $user->id : $user->admin_id;
            if ($complaint['admin_id'] != $adminId) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Unauthorized access']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            $this->complaintModel->updateComplaintStatus($args['id'], $data['status'], $data['response'] ?? null);
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Complaint status updated successfully']));
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
            // Verify authorization before deleting
            $complaint = $this->complaintModel->findById($args['id']);
            if (!$complaint) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Complaint not found']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
            }

            // Only admin can delete complaints
            $adminId = $user->role === 'Admin' ? $user->id : $user->admin_id;
            if ($complaint['admin_id'] != $adminId) {
                $response->getBody()->write(json_encode(['success' => false, 'message' => 'Unauthorized access']));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(403);
            }

            $this->complaintModel->delete($args['id']);
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Complaint deleted successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}
