<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\payments\save_invoice.php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

// Enable error reporting
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Log to file for debugging
function logDebug($message) {
    file_put_contents(__DIR__ . '/invoice_debug.log', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
}

logDebug("Invoice save request received: " . json_encode($_POST));

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Get form data
$appointment_id = $_POST['appointment_id'] ?? null;
$amount = $_POST['amount'] ?? 0;
$notes = $_POST['notes'] ?? '';

// Validate amount
if (empty($amount) || !is_numeric($amount)) {
    logDebug("Invalid amount: $amount");
    echo json_encode(['success' => false, 'error' => 'Invalid amount']);
    exit;
}

// Insert invoice
try {
    if (empty($appointment_id)) {
        $query = "INSERT INTO invoices (amount, created_at, updated_at) VALUES (?, NOW(), NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('d', $amount);
    } else {
        $query = "INSERT INTO invoices (appointment_id, amount, created_at, updated_at) VALUES (?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('id', $appointment_id, $amount);
    }
    
    if ($stmt->execute()) {
        $invoice_id = $conn->insert_id;
        logDebug("Invoice created with ID: $invoice_id");
        
        // If there are treatment notes and appointment_id, update them
        if (!empty($notes) && !empty($appointment_id)) {
            // First check if treatment exists
            $treatment_check = "SELECT treatment_id FROM treatments WHERE appointment_id = ?";
            $check_stmt = $conn->prepare($treatment_check);
            $check_stmt->bind_param('i', $appointment_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows > 0) {
                // Update existing treatment
                $update_query = "UPDATE treatments SET notes = CONCAT(IFNULL(notes, ''), '\n', ?), fee = ? WHERE appointment_id = ?";
                $update_stmt = $conn->prepare($update_query);
                $update_stmt->bind_param('sdi', $notes, $amount, $appointment_id);
                $update_stmt->execute();
                logDebug("Updated treatment for appointment: $appointment_id");
            } else {
                // Get service_id from appointment
                $service_query = "SELECT service_id FROM appointments WHERE appointment_id = ?";
                $service_stmt = $conn->prepare($service_query);
                $service_stmt->bind_param('i', $appointment_id);
                $service_stmt->execute();
                $service_result = $service_stmt->get_result();
                $service_row = $service_result->fetch_assoc();
                $service_id = $service_row['service_id'] ?? 1;
                
                // Create new treatment
                $insert_query = "INSERT INTO treatments (appointment_id, service_id, notes, fee, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())";
                $insert_stmt = $conn->prepare($insert_query);
                $insert_stmt->bind_param('issd', $appointment_id, $service_id, $notes, $amount);
                $insert_stmt->execute();
                logDebug("Created new treatment for appointment: $appointment_id");
            }
        }
        
        echo json_encode(['success' => true, 'invoice_id' => $invoice_id]);
    } else {
        logDebug("Execute failed: " . $stmt->error);
        echo json_encode(['success' => false, 'error' => 'Database error: ' . $stmt->error]);
    }
} catch (Exception $e) {
    logDebug("Exception: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Exception: ' . $e->getMessage()]);
}
?>