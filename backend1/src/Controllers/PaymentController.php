<?php

namespace App\Controllers;

use App\Config\Database;
use PDO;
use PDOException;

class PaymentController
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // ======= FEE STRUCTURES =======

    // Create fee structure
    public function createFeeStructure($data)
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO fee_structures
                (fee_name, class_id, amount, frequency, academic_year_id, description, is_mandatory)
                VALUES (:fee_name, :class_id, :amount, :frequency, :academic_year_id, :description, :is_mandatory)
            ");

            $stmt->execute([
                ':fee_name' => $data['fee_name'],
                ':class_id' => $data['class_id'] ?? null,
                ':amount' => $data['amount'],
                ':frequency' => $data['frequency'] ?? 'Termly',
                ':academic_year_id' => $data['academic_year_id'],
                ':description' => $data['description'] ?? null,
                ':is_mandatory' => $data['is_mandatory'] ?? true
            ]);

            return [
                'success' => true,
                'message' => 'Fee structure created successfully',
                'id' => $this->db->lastInsertId()
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error creating fee structure: ' . $e->getMessage()
            ];
        }
    }

    // Get all fee structures
    public function getAllFeeStructures($academicYearId = null, $classId = null)
    {
        try {
            $sql = "
                SELECT fs.*,
                       c.class_name,
                       ay.year_name
                FROM fee_structures fs
                LEFT JOIN classes c ON fs.class_id = c.id
                JOIN academic_years ay ON fs.academic_year_id = ay.id
                WHERE 1=1
            ";

            $params = [];

            if ($academicYearId) {
                $sql .= " AND fs.academic_year_id = :academic_year_id";
                $params[':academic_year_id'] = $academicYearId;
            }

            if ($classId) {
                $sql .= " AND (fs.class_id = :class_id OR fs.class_id IS NULL)";
                $params[':class_id'] = $classId;
            }

            $sql .= " ORDER BY fs.is_mandatory DESC, fs.fee_name";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $feeStructures = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'feeStructures' => $feeStructures
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching fee structures: ' . $e->getMessage()
            ];
        }
    }

    // Update fee structure
    public function updateFeeStructure($id, $data)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE fee_structures
                SET fee_name = :fee_name,
                    class_id = :class_id,
                    amount = :amount,
                    frequency = :frequency,
                    description = :description,
                    is_mandatory = :is_mandatory,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");

            $stmt->execute([
                ':id' => $id,
                ':fee_name' => $data['fee_name'],
                ':class_id' => $data['class_id'] ?? null,
                ':amount' => $data['amount'],
                ':frequency' => $data['frequency'],
                ':description' => $data['description'] ?? null,
                ':is_mandatory' => $data['is_mandatory'] ?? true
            ]);

            return [
                'success' => true,
                'message' => 'Fee structure updated successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error updating fee structure: ' . $e->getMessage()
            ];
        }
    }

    // Delete fee structure
    public function deleteFeeStructure($id)
    {
        try {
            $stmt = $this->db->prepare("DELETE FROM fee_structures WHERE id = :id");
            $stmt->execute([':id' => $id]);

            return [
                'success' => true,
                'message' => 'Fee structure deleted successfully'
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error deleting fee structure: ' . $e->getMessage()
            ];
        }
    }

    // ======= PAYMENTS =======

    // Record a payment
    public function recordPayment($data)
    {
        try {
            // Generate receipt number
            $receiptNumber = $this->generateReceiptNumber();

            $stmt = $this->db->prepare("
                INSERT INTO payments
                (student_id, fee_structure_id, amount_paid, payment_date, payment_method,
                 reference_number, receipt_number, term, academic_year_id, status, notes, recorded_by)
                VALUES (:student_id, :fee_structure_id, :amount_paid, :payment_date, :payment_method,
                        :reference_number, :receipt_number, :term, :academic_year_id, :status, :notes, :recorded_by)
            ");

            $stmt->execute([
                ':student_id' => $data['student_id'],
                ':fee_structure_id' => $data['fee_structure_id'],
                ':amount_paid' => $data['amount_paid'],
                ':payment_date' => $data['payment_date'] ?? date('Y-m-d'),
                ':payment_method' => $data['payment_method'],
                ':reference_number' => $data['reference_number'] ?? null,
                ':receipt_number' => $receiptNumber,
                ':term' => $data['term'] ?? 1,
                ':academic_year_id' => $data['academic_year_id'],
                ':status' => $data['status'] ?? 'Completed',
                ':notes' => $data['notes'] ?? null,
                ':recorded_by' => $data['recorded_by']
            ]);

            $paymentId = $this->db->lastInsertId();

            // Update invoice if it exists
            if (isset($data['invoice_id'])) {
                $this->updateInvoicePayment($data['invoice_id']);
            }

            return [
                'success' => true,
                'message' => 'Payment recorded successfully',
                'id' => $paymentId,
                'receipt_number' => $receiptNumber
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error recording payment: ' . $e->getMessage()
            ];
        }
    }

    // Get payment history for a student
    public function getStudentPaymentHistory($studentId, $academicYearId = null)
    {
        try {
            $sql = "
                SELECT p.*,
                       fs.fee_name,
                       fs.amount as fee_amount,
                       CONCAT(a.first_name, ' ', a.last_name) as recorded_by_name,
                       s.first_name as student_first_name,
                       s.last_name as student_last_name
                FROM payments p
                JOIN fee_structures fs ON p.fee_structure_id = fs.id
                JOIN admins a ON p.recorded_by = a.id
                JOIN students s ON p.student_id = s.id
                WHERE p.student_id = :student_id
            ";

            $params = [':student_id' => $studentId];

            if ($academicYearId) {
                $sql .= " AND p.academic_year_id = :academic_year_id";
                $params[':academic_year_id'] = $academicYearId;
            }

            $sql .= " ORDER BY p.payment_date DESC, p.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'payments' => $payments
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching payment history: ' . $e->getMessage()
            ];
        }
    }

    // Get all payments with filters
    public function getAllPayments($filters = [])
    {
        try {
            $sql = "
                SELECT p.*,
                       fs.fee_name,
                       CONCAT(s.first_name, ' ', s.last_name) as student_name,
                       s.admission_no,
                       c.class_name,
                       CONCAT(a.first_name, ' ', a.last_name) as recorded_by_name
                FROM payments p
                JOIN fee_structures fs ON p.fee_structure_id = fs.id
                JOIN students s ON p.student_id = s.id
                LEFT JOIN classes c ON s.class_id = c.id
                JOIN admins a ON p.recorded_by = a.id
                WHERE 1=1
            ";

            $params = [];

            if (isset($filters['academic_year_id'])) {
                $sql .= " AND p.academic_year_id = :academic_year_id";
                $params[':academic_year_id'] = $filters['academic_year_id'];
            }

            if (isset($filters['term'])) {
                $sql .= " AND p.term = :term";
                $params[':term'] = $filters['term'];
            }

            if (isset($filters['class_id'])) {
                $sql .= " AND s.class_id = :class_id";
                $params[':class_id'] = $filters['class_id'];
            }

            if (isset($filters['payment_method'])) {
                $sql .= " AND p.payment_method = :payment_method";
                $params[':payment_method'] = $filters['payment_method'];
            }

            if (isset($filters['status'])) {
                $sql .= " AND p.status = :status";
                $params[':status'] = $filters['status'];
            }

            if (isset($filters['start_date'])) {
                $sql .= " AND p.payment_date >= :start_date";
                $params[':start_date'] = $filters['start_date'];
            }

            if (isset($filters['end_date'])) {
                $sql .= " AND p.payment_date <= :end_date";
                $params[':end_date'] = $filters['end_date'];
            }

            $sql .= " ORDER BY p.payment_date DESC, p.created_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'payments' => $payments
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching payments: ' . $e->getMessage()
            ];
        }
    }

    // ======= INVOICES =======

    // Create invoice
    public function createInvoice($data)
    {
        try {
            $this->db->beginTransaction();

            // Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber();

            // Calculate total amount
            $totalAmount = 0;
            foreach ($data['items'] as $item) {
                $totalAmount += $item['total_price'];
            }

            // Insert invoice
            $stmt = $this->db->prepare("
                INSERT INTO invoices
                (invoice_number, student_id, academic_year_id, term, total_amount, balance, due_date, notes, issued_by)
                VALUES (:invoice_number, :student_id, :academic_year_id, :term, :total_amount, :balance, :due_date, :notes, :issued_by)
            ");

            $stmt->execute([
                ':invoice_number' => $invoiceNumber,
                ':student_id' => $data['student_id'],
                ':academic_year_id' => $data['academic_year_id'],
                ':term' => $data['term'] ?? 1,
                ':total_amount' => $totalAmount,
                ':balance' => $totalAmount,
                ':due_date' => $data['due_date'] ?? null,
                ':notes' => $data['notes'] ?? null,
                ':issued_by' => $data['issued_by']
            ]);

            $invoiceId = $this->db->lastInsertId();

            // Insert invoice items
            $stmt = $this->db->prepare("
                INSERT INTO invoice_items
                (invoice_id, fee_structure_id, description, quantity, unit_price, total_price)
                VALUES (:invoice_id, :fee_structure_id, :description, :quantity, :unit_price, :total_price)
            ");

            foreach ($data['items'] as $item) {
                $stmt->execute([
                    ':invoice_id' => $invoiceId,
                    ':fee_structure_id' => $item['fee_structure_id'],
                    ':description' => $item['description'] ?? null,
                    ':quantity' => $item['quantity'] ?? 1,
                    ':unit_price' => $item['unit_price'],
                    ':total_price' => $item['total_price']
                ]);
            }

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Invoice created successfully',
                'id' => $invoiceId,
                'invoice_number' => $invoiceNumber
            ];
        } catch (PDOException $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Error creating invoice: ' . $e->getMessage()
            ];
        }
    }

    // Get invoice by ID with items
    public function getInvoice($id)
    {
        try {
            // Get invoice
            $stmt = $this->db->prepare("
                SELECT i.*,
                       CONCAT(s.first_name, ' ', s.last_name) as student_name,
                       s.admission_no,
                       c.class_name,
                       CONCAT(a.first_name, ' ', a.last_name) as issued_by_name
                FROM invoices i
                JOIN students s ON i.student_id = s.id
                LEFT JOIN classes c ON s.class_id = c.id
                JOIN admins a ON i.issued_by = a.id
                WHERE i.id = :id
            ");
            $stmt->execute([':id' => $id]);
            $invoice = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$invoice) {
                return [
                    'success' => false,
                    'message' => 'Invoice not found'
                ];
            }

            // Get invoice items
            $stmt = $this->db->prepare("
                SELECT ii.*,
                       fs.fee_name
                FROM invoice_items ii
                JOIN fee_structures fs ON ii.fee_structure_id = fs.id
                WHERE ii.invoice_id = :invoice_id
            ");
            $stmt->execute([':invoice_id' => $id]);
            $invoice['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'invoice' => $invoice
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching invoice: ' . $e->getMessage()
            ];
        }
    }

    // Get all invoices
    public function getAllInvoices($filters = [])
    {
        try {
            $sql = "
                SELECT i.*,
                       CONCAT(s.first_name, ' ', s.last_name) as student_name,
                       s.admission_no,
                       c.class_name
                FROM invoices i
                JOIN students s ON i.student_id = s.id
                LEFT JOIN classes c ON s.class_id = c.id
                WHERE 1=1
            ";

            $params = [];

            if (isset($filters['academic_year_id'])) {
                $sql .= " AND i.academic_year_id = :academic_year_id";
                $params[':academic_year_id'] = $filters['academic_year_id'];
            }

            if (isset($filters['student_id'])) {
                $sql .= " AND i.student_id = :student_id";
                $params[':student_id'] = $filters['student_id'];
            }

            if (isset($filters['status'])) {
                $sql .= " AND i.status = :status";
                $params[':status'] = $filters['status'];
            }

            $sql .= " ORDER BY i.issued_at DESC";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $invoices = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'invoices' => $invoices
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching invoices: ' . $e->getMessage()
            ];
        }
    }

    // Update invoice payment status
    private function updateInvoicePayment($invoiceId)
    {
        try {
            // Calculate total paid
            $stmt = $this->db->prepare("
                SELECT COALESCE(SUM(p.amount_paid), 0) as total_paid
                FROM payments p
                JOIN invoice_items ii ON p.fee_structure_id = ii.fee_structure_id
                WHERE ii.invoice_id = :invoice_id AND p.status = 'Completed'
            ");
            $stmt->execute([':invoice_id' => $invoiceId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            $totalPaid = $result['total_paid'];

            // Get invoice total
            $stmt = $this->db->prepare("SELECT total_amount FROM invoices WHERE id = :id");
            $stmt->execute([':id' => $invoiceId]);
            $totalAmount = $stmt->fetchColumn();

            // Calculate balance
            $balance = $totalAmount - $totalPaid;

            // Determine status
            $status = 'Pending';
            if ($balance <= 0) {
                $status = 'Paid';
                $balance = 0;
            } elseif ($totalPaid > 0) {
                $status = 'Partially Paid';
            }

            // Update invoice
            $stmt = $this->db->prepare("
                UPDATE invoices
                SET amount_paid = :amount_paid,
                    balance = :balance,
                    status = :status,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $invoiceId,
                ':amount_paid' => $totalPaid,
                ':balance' => $balance,
                ':status' => $status
            ]);

            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    // Generate receipt number
    private function generateReceiptNumber()
    {
        $prefix = 'RCP';
        $date = date('Ymd');
        $stmt = $this->db->query("SELECT COUNT(*) FROM payments WHERE DATE(created_at) = CURDATE()");
        $count = $stmt->fetchColumn() + 1;
        return $prefix . $date . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // Generate invoice number
    private function generateInvoiceNumber()
    {
        $prefix = 'INV';
        $date = date('Ymd');
        $stmt = $this->db->query("SELECT COUNT(*) FROM invoices WHERE DATE(issued_at) = CURDATE()");
        $count = $stmt->fetchColumn() + 1;
        return $prefix . $date . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    // Get payment summary/statistics
    public function getPaymentSummary($academicYearId, $term = null)
    {
        try {
            $sql = "
                SELECT
                    COUNT(*) as total_payments,
                    SUM(amount_paid) as total_amount,
                    COUNT(DISTINCT student_id) as students_paid,
                    payment_method,
                    status
                FROM payments
                WHERE academic_year_id = :academic_year_id
            ";

            $params = [':academic_year_id' => $academicYearId];

            if ($term) {
                $sql .= " AND term = :term";
                $params[':term'] = $term;
            }

            $sql .= " GROUP BY payment_method, status";

            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $summary = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return [
                'success' => true,
                'summary' => $summary
            ];
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Error fetching payment summary: ' . $e->getMessage()
            ];
        }
    }
}
