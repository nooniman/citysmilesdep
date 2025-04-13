<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\schedule\update_schedule.php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: schedule.php');
    exit;
}

// Get form data
$dentist_id = isset($_POST['dentist_id']) ? intval($_POST['dentist_id']) : 0;
$availability_id = isset($_POST['availability_id']) ? intval($_POST['availability_id']) : 0;
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$max_appointments = isset($_POST['max_appointments']) ? intval($_POST['max_appointments']) : 1;
$is_available = isset($_POST['is_available']) ? 1 : 0;

// Validate data
if ($availability_id <= 0 || empty($start_time) || empty($end_time)) {
    $_SESSION['schedule_error'] = "Invalid schedule data provided.";
    header('Location: schedule.php?dentist_id=' . $dentist_id);
    exit;
}

// Check if time range is valid
if (strtotime($end_time) <= strtotime($start_time)) {
    $_SESSION['schedule_error'] = "End time must be after start time.";
    header('Location: schedule.php?dentist_id=' . $dentist_id);
    exit;
}

// Update schedule
$sql = "UPDATE dentist_availability 
        SET start_time = ?, end_time = ?, max_appointments = ?, is_available = ?, updated_at = NOW() 
        WHERE availability_id = ? AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('ssiiii', $start_time, $end_time, $max_appointments, $is_available, $availability_id, $dentist_id);

if ($stmt->execute()) {
    $_SESSION['schedule_message'] = "Schedule updated successfully.";
} else {
    $_SESSION['schedule_error'] = "Error updating schedule: " . $stmt->error;
}

header('Location: schedule.php?dentist_id=' . $dentist_id);
exit;
?>