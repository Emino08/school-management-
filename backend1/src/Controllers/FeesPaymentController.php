<?php

namespace App\Controllers;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use App\Models\FeesPayment;
use App\Models\AcademicYear;
use App\Utils\Validator;

class FeesPaymentController
{
    private $feesModel;
    private $academicYearModel;

    public function __construct()
    {
        $this->feesModel = new FeesPayment();
        $this->academicYearModel = new AcademicYear();
    }

    public function create(Request $request, Response $response)
    {
        $data = $request->getParsedBody();
        $user = $request->getAttribute('user');

        $errors = Validator::validate($data, ['student_id' => 'required|numeric', 'term' => 'required', 'amount' => 'required|numeric', 'payment_date' => 'required']);
        if (!empty($errors)) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Validation failed', 'errors' => $errors]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
        }

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            $data['academic_year_id'] = $currentYear['id'];

            // Map numeric term to schema terms
            $termMap = [ '1' => '1st Term', '2' => '2nd Term', '3' => 'Full Year' ];
            if (isset($data['term']) && isset($termMap[$data['term']])) {
                $data['term'] = $termMap[$data['term']];
            }
            // Map reference_number to receipt_number
            if (isset($data['reference_number']) && !isset($data['receipt_number'])) {
                $data['receipt_number'] = $data['reference_number'];
                unset($data['reference_number']);
            }

            // Map optional notes to remarks to match schema
            if (isset($data['notes']) && !isset($data['remarks'])) {
                $data['remarks'] = $data['notes'];
                unset($data['notes']);
            }

            // Duplicate check: prevent same student + term + academic year
            $db = \App\Config\Database::getInstance()->getConnection();
            $dup = $db->prepare("SELECT id FROM fees_payments WHERE student_id = :sid AND academic_year_id = :year AND term = :term LIMIT 1");
            $dup->execute([':sid' => $data['student_id'], ':year' => $data['academic_year_id'], ':term' => $data['term']]);
            if ($dup->fetch()) {
                $response->getBody()->write(json_encode([
                    'success' => false,
                    'message' => 'Duplicate payment: this student already has a payment for the selected term'
                ]));
                return $response->withHeader('Content-Type', 'application/json')->withStatus(409);
            }

            $paymentId = $this->feesModel->create(Validator::sanitize($data));

            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Payment recorded successfully', 'payment_id' => $paymentId]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to record payment: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getStudentPayments(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            $payments = $this->feesModel->getStudentPayments($args['studentId'], $currentYear ? $currentYear['id'] : null);

            $response->getBody()->write(json_encode(['success' => true, 'payments' => $payments]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch payments: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getPaymentsByTerm(Request $request, Response $response, $args)
    {
        $user = $request->getAttribute('user');

        try {
            $currentYear = $this->academicYearModel->getCurrentYear($user->id);
            $payments = $this->feesModel->getPaymentsByTerm($user->id, $currentYear ? $currentYear['id'] : null, $args['term']);

            $response->getBody()->write(json_encode(['success' => true, 'payments' => $payments]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch payments: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getAllPayments(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();
        $academicYearId = $params['academic_year_id'] ?? null;

        try {
            $sql = "SELECT fp.*,
                           s.name as student_name,
                           s.id_number as id_number,
                           s.admission_no,
                           c.class_name,
                           c.id as class_id
                    FROM fees_payments fp
                    INNER JOIN students s ON fp.student_id = s.id
                    LEFT JOIN student_enrollments se ON se.student_id = s.id AND se.academic_year_id = fp.academic_year_id
                    LEFT JOIN classes c ON se.class_id = c.id
                    WHERE s.admin_id = :admin_id";

            $bindings = [':admin_id' => $user->id];

            if ($academicYearId) {
                $sql .= " AND fp.academic_year_id = :academic_year_id";
                $bindings[':academic_year_id'] = $academicYearId;
            }

            $sql .= " ORDER BY fp.payment_date DESC, fp.created_at DESC";

            $stmt = \App\Config\Database::getInstance()->getConnection()->prepare($sql);
            $stmt->execute($bindings);
            $payments = $stmt->fetchAll(\PDO::FETCH_ASSOC);

            $response->getBody()->write(json_encode([
                'success' => true,
                'payments' => $payments
            ]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode([
                'success' => false,
                'message' => 'Failed to fetch payments: ' . $e->getMessage()
            ]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function getPaymentStats(Request $request, Response $response)
    {
        $user = $request->getAttribute('user');
        $params = $request->getQueryParams();
        $term = $params['term'] ?? null;
        $academicYearId = $params['academic_year_id'] ?? null;

        try {
            if (!$academicYearId) {
                $currentYear = $this->academicYearModel->getCurrentYear($user->id);
                $academicYearId = $currentYear ? $currentYear['id'] : null;
            }
            $stats = $this->feesModel->getPaymentStats($academicYearId, $term);

            $response->getBody()->write(json_encode(['success' => true, 'stats' => $stats]));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Failed to fetch stats: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function updatePayment(Request $request, Response $response, $args)
    {
        $data = Validator::sanitize($request->getParsedBody());
        unset($data['id'], $data['student_id'], $data['academic_year_id']);

        try {
            $this->feesModel->update($args['id'], $data);
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Payment updated successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Update failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }

    public function deletePayment(Request $request, Response $response, $args)
    {
        try {
            $this->feesModel->delete($args['id']);
            $response->getBody()->write(json_encode(['success' => true, 'message' => 'Payment deleted successfully']));
            return $response->withHeader('Content-Type', 'application/json');
        } catch (\Exception $e) {
            $response->getBody()->write(json_encode(['success' => false, 'message' => 'Deletion failed: ' . $e->getMessage()]));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    }
}



