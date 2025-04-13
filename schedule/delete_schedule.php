<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\schedule\delete_schedule.php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

// Add debugging
file_put_contents(__DIR__ . '/schedule.log', date('[Y-m-d H:i:s] ') . "delete_schedule - POST: " . json_encode($_POST) . PHP_EOL, FILE_APPEND);

// Security check
if (!isset($_POST['availability_id']) || !isset($_POST['dentist_id'])) {
    $_SESSION['schedule_error'] = "Invalid request.";
    header('Location: schedule.php');
    exit;
}

$availability_id = intval($_POST['availability_id']);
$dentist_id = intval($_POST['dentist_id']);

// Additional security check to ensure the user can only delete their own schedules or is admin
if ($_SESSION['role'] !== 'admin' && $_SESSION['user_id'] != $dentist_id) {
    $_SESSION['schedule_error'] = "You don't have permission to delete this schedule.";
    header('Location: schedule.php');
    exit;
}

// Delete the schedule - FIXED: using user_id instead of dentist_id
$query = "DELETE FROM dentist_availability WHERE availability_id = ? AND user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $availability_id, $dentist_id);

$result = $stmt->execute();
file_put_contents(__DIR__ . '/schedule.log', date('[Y-m-d H:i:s] ') . "Delete result: " . ($result ? "success" : "fail: " . $stmt->error) . PHP_EOL, FILE_APPEND);

if ($result) {
    $_SESSION['schedule_message'] = "Schedule slot successfully deleted.";
} else {
    $_SESSION['schedule_error'] = "Error deleting schedule: " . $conn->error;
}

// Redirect back to the schedule page
header('Location: schedule.php?dentist_id=' . $dentist_id);
exit;
?>