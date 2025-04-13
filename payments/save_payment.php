<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\payments\save_payment.php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get form data
$invoice_id = $_POST['invoice_id'] ?? 0;
$amount = $_POST['amount'] ?? 0;
$payment_method = $_POST['payment_method'] ?? 'cash';
$payment_date = $_POST['payment_date'] ?? date('Y-m-d');
$notes = $_POST['notes'] ?? '';

// Validate data
if (empty($invoice_id) || empty($amount) || !is_numeric($amount)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input data']);
    exit;
}

// Check if payment exceeds remaining balance
$balance_query = "SELECT i.amount as invoice_amount, COALESCE(SUM(p.amount), 0) as paid_amount 
                  FROM invoices i 
                  LEFT JOIN payments p ON i.invoice_id = p.invoice_id 
                  WHERE i.invoice_id = ?
                  GROUP BY i.invoice_id";
$balance_stmt = $conn->prepare($balance_query);
$balance_stmt->bind_param('i', $invoice_id);
$balance_stmt->execute();
$balance_result = $balance_stmt->get_result();
$balance_row = $balance_result->fetch_assoc();

$remaining_balance = $balance_row['invoice_amount'] - $balance_row['paid_amount'];

if ($amount > $remaining_balance) {
    http_response_code(400);
    echo json_encode(['error' => 'Payment amount exceeds remaining balance']);
    exit;
}

// Insert payment
$query = "INSERT INTO payments (invoice_id, amount, payment_date, payment_method, created_at, updated_at) 
          VALUES (?, ?, ?, ?, NOW(), NOW())";
$stmt = $conn->prepare($query);
$stmt->bind_param('idss', $invoice_id, $amount, $payment_date, $payment_method);

if ($stmt->execute()) {
    $payment_id = $conn->insert_id;
    
    // Add notes if provided
    if (!empty($notes)) {
        $update_query = "UPDATE payments SET notes = ? WHERE payment_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param('si', $notes, $payment_id);
        $update_stmt->execute();
    }
    
    echo json_encode(['success' => true, 'payment_id' => $payment_id]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to record payment: ' . $conn->error]);
}
?>