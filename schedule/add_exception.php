<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\schedule\add_exception.php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

header('Content-Type: application/json');
ob_start(); // Start output buffering

// Ensure no PHP warnings or notices are output
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

// Set the correct timezone
date_default_timezone_set('America/New_York'); // Replace with your desired timezone

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean(); // Clear any unexpected output
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get form data
$dentist_id = isset($_POST['dentist_id']) ? intval($_POST['dentist_id']) : 0;
$exception_date = $_POST['exception_date'] ?? '';
$exception_type = $_POST['exception_type'] ?? '';
$start_time = $_POST['exception_start_time'] ?? null;
$end_time = $_POST['exception_end_time'] ?? null;
$reason = $_POST['reason'] ?? '';

// Validate data
if ($dentist_id <= 0 || empty($exception_date) || empty($exception_type)) {
    ob_clean(); // Clear any unexpected output
    echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
    exit;
}

// Validate exception_type
$allowed_types = ['custom', 'unavailable'];
if (!in_array($exception_type, $allowed_types)) {
    ob_clean(); // Clear any unexpected output
    echo json_encode(['success' => false, 'message' => 'Invalid exception type.']);
    exit;
}

// Set availability flag
$is_available = ($exception_type === 'custom') ? 1 : 0;

// For custom hours, validate time range
if ($exception_type === 'custom') {
    if (empty($start_time) || empty($end_time)) {
        ob_clean(); // Clear any unexpected output
        echo json_encode(['success' => false, 'message' => 'Start time and end time are required for custom hours.']);
        exit;
    }

    if (strtotime($end_time) <= strtotime($start_time)) {
        ob_clean(); // Clear any unexpected output
        echo json_encode(['success' => false, 'message' => 'End time must be after start time.']);
        exit;
    }
} else {
    // If unavailable, set times to null
    $start_time = null;
    $end_time = null;
}

// For non-custom exceptions, ensure start_time and end_time are null
if ($exception_type === 'unavailable') {
    $start_time = null;
    $end_time = null;
}

// Check if an exception for this date already exists
$check_sql = "SELECT exception_id FROM schedule_exceptions 
              WHERE dentist_id = ? AND exception_date = ?"; // Replace 'user_id' with 'dentist_id'
$check_stmt = $conn->prepare($check_sql);
$check_stmt->bind_param('is', $dentist_id, $exception_date);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    ob_clean(); // Clear any unexpected output
    echo json_encode(['success' => false, 'message' => 'An exception for this date already exists. Please edit the existing exception.']);
    $check_stmt->close();
    exit;
}

$check_stmt->close(); // Close the statement after use

// Insert new exception
$sql = "INSERT INTO schedule_exceptions (dentist_id, exception_date, exception_type, start_time, end_time, reason, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())";
$stmt = $conn->prepare($sql);
$stmt->bind_param('isssss', $dentist_id, $exception_date, $exception_type, $start_time, $end_time, $reason);

if ($stmt->execute()) {
    ob_clean(); // Clear any unexpected output
    echo json_encode(['success' => true, 'message' => 'Exception added successfully.']);
} else {
    ob_clean(); // Clear any unexpected output
    echo json_encode(['success' => false, 'message' => 'Error adding exception: ' . htmlspecialchars($stmt->error)]);
}

$stmt->close(); // Close the statement after use
$conn->close(); // Close the database connection

?>