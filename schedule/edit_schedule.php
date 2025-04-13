<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\schedule\edit_schedule.php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['schedule_error'] = "Invalid request method.";
    header("Location: schedule.php");
    exit();
}

// Get form data
$availability_id = isset($_POST['availability_id']) ? intval($_POST['availability_id']) : 0;
$dentist_id = isset($_POST['dentist_id']) ? intval($_POST['dentist_id']) : 0;
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$max_appointments = isset($_POST['max_appointments']) ? intval($_POST['max_appointments']) : 1;
$is_available = isset($_POST['is_available']) ? 1 : 0;

// Validation
if ($availability_id <= 0 || $dentist_id <= 0) {
    $_SESSION['schedule_error'] = "Invalid schedule or dentist ID.";
    header("Location: schedule.php");
    exit();
}

if (empty($start_time) || empty($end_time)) {
    $_SESSION['schedule_error'] = "Start time and end time are required.";
    header("Location: schedule.php?dentist_id=$dentist_id");
    exit();
}

// Check if end time is after start time
if (strtotime($end_time) <= strtotime($start_time)) {
    $_SESSION['schedule_error'] = "End time must be after start time.";
    header("Location: schedule.php?dentist_id=$dentist_id");
    exit();
}

// Get the day of week for the availability to check for overlaps
$get_day_query = "SELECT day_of_week FROM dentist_availability WHERE availability_id = ?";
$get_day_stmt = $conn->prepare($get_day_query);
$get_day_stmt->bind_param('i', $availability_id);
$get_day_stmt->execute();
$day_result = $get_day_stmt->get_result();

if ($day_result->num_rows === 0) {
    $_SESSION['schedule_error'] = "Schedule not found.";
    header("Location: schedule.php?dentist_id=$dentist_id");
    exit();
}

$day_of_week = $day_result->fetch_assoc()['day_of_week'];

// Check for overlapping schedules (excluding the current one)
$check_query = "SELECT * FROM dentist_availability 
                WHERE dentist_id = ? AND day_of_week = ? AND availability_id != ? AND 
                ((start_time <= ? AND end_time > ?) OR 
                 (start_time < ? AND end_time >= ?) OR 
                 (start_time >= ? AND end_time <= ?))";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param('iiissssss', $dentist_id, $day_of_week, $availability_id, $end_time, $start_time, $end_time, $start_time, $start_time, $end_time);
$check_stmt->execute();
$check_result = $check_stmt->get_result();

if ($check_result->num_rows > 0) {
    $_SESSION['schedule_error'] = "There's already another schedule that overlaps with this time period.";
    header("Location: schedule.php?dentist_id=$dentist_id");
    exit();
}

// Update schedule
$update_query = "UPDATE dentist_availability 
                SET start_time = ?, end_time = ?, is_available = ?, max_appointments = ?, updated_at = NOW()
                WHERE availability_id = ? AND dentist_id = ?";
$update_stmt = $conn->prepare($update_query);
$update_stmt->bind_param('ssiiii', $start_time, $end_time, $is_available, $max_appointments, $availability_id, $dentist_id);

if ($update_stmt->execute()) {
    $_SESSION['schedule_message'] = "Schedule updated successfully.";
} else {
    $_SESSION['schedule_error'] = "Error updating schedule: " . $update_stmt->error;
}

header("Location: schedule.php?dentist_id=$dentist_id");
exit();
?>