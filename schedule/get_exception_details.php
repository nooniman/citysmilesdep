<?php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

header('Content-Type: application/json');

// Get exception ID from request
$exception_id = isset($_GET['exception_id']) ? intval($_GET['exception_id']) : 0;

if ($exception_id <= 0) {
    echo json_encode(['error' => 'Invalid exception ID']);
    exit;
}

// Check if the table uses user_id or dentist_id
$column_check = $conn->query("SHOW COLUMNS FROM schedule_exceptions LIKE 'user_id'");
$use_user_id = ($column_check->num_rows > 0);

$sql = "SELECT * FROM schedule_exceptions WHERE exception_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $exception_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Exception not found']);
    exit;
}

$row = $result->fetch_assoc();

// Add formatted date and times for display
$row['formatted_date'] = date('F j, Y', strtotime($row['exception_date']));
if (!empty($row['start_time'])) {
    $row['formatted_start_time'] = date('g:i A', strtotime($row['start_time']));
}
if (!empty($row['end_time'])) {
    $row['formatted_end_time'] = date('g:i A', strtotime($row['end_time']));
}

// Determine exception type for UI
$row['exception_type'] = $row['is_available'] ? 'custom' : 'unavailable';

// Get dentist info
$dentist_id = $use_user_id ? $row['user_id'] : $row['dentist_id'];
$dentist_query = "SELECT first_name, last_name FROM users WHERE id = ?";
$dentist_stmt = $conn->prepare($dentist_query);
$dentist_stmt->bind_param('i', $dentist_id);
$dentist_stmt->execute();
$dentist_result = $dentist_stmt->get_result();

if ($dentist_result->num_rows > 0) {
    $dentist = $dentist_result->fetch_assoc();
    $row['dentist_name'] = $dentist['first_name'] . ' ' . $dentist['last_name'];
}

echo json_encode($row);
?>