<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\schedule\update_exception.php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: schedule.php');
    exit;
}

// Get form data
$dentist_id = isset($_POST['dentist_id']) ? intval($_POST['dentist_id']) : 0;
$exception_id = isset($_POST['exception_id']) ? intval($_POST['exception_id']) : 0;
$exception_type = $_POST['exception_type'] ?? '';
$start_time = $_POST['exception_start_time'] ?? null;
$end_time = $_POST['exception_end_time'] ?? null;
$reason = $_POST['reason'] ?? '';

// Validate data
if ($exception_id <= 0 || empty($exception_type)) {
    $_SESSION['schedule_error'] = "Invalid exception data provided.";
    header('Location: schedule.php?dentist_id=' . $dentist_id);
    exit;
}

// Set availability flag
$is_available = ($exception_type === 'custom') ? 1 : 0;

// For custom hours, validate time range
if ($exception_type === 'custom') {
    if (empty($start_time) || empty($end_time)) {
        $_SESSION['schedule_error'] = "Start time and end time are required for custom hours.";
        header('Location: schedule.php?dentist_id=' . $dentist_id);
        exit;
    }
    
    if (strtotime($end_time) <= strtotime($start_time)) {
        $_SESSION['schedule_error'] = "End time must be after start time.";
        header('Location: schedule.php?dentist_id=' . $dentist_id);
        exit;
    }
} else {
    // If unavailable, set times to null
    $start_time = null;
    $end_time = null;
}

// Update exception
$sql = "UPDATE schedule_exceptions 
        SET is_available = ?, start_time = ?, end_time = ?, reason = ? 
        WHERE exception_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('isssii', $is_available, $start_time, $end_time, $reason, $exception_id, $dentist_id);

if ($stmt->execute()) {
    $_SESSION['schedule_message'] = "Exception updated successfully.";
} else {
    $_SESSION['schedule_error'] = "Error updating exception: " . $stmt->error;
}

header('Location: schedule.php?dentist_id=' . $dentist_id);
exit;
?>