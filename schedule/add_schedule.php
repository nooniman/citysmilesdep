<?php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

// Get form data
$dentist_id = isset($_POST['dentist_id']) ? intval($_POST['dentist_id']) : 0;
$day_of_week = isset($_POST['day_of_week']) ? intval($_POST['day_of_week']) : null;
$start_time = $_POST['start_time'] ?? '';
$end_time = $_POST['end_time'] ?? '';
$max_appointments = isset($_POST['max_appointments']) ? intval($_POST['max_appointments']) : 1;
$is_available = isset($_POST['is_available']) ? 1 : 0;

// Validation
if ($dentist_id <= 0 || $day_of_week === null || empty($start_time) || empty($end_time)) {
    echo json_encode(['success' => false, 'message' => 'Invalid input data.']);
    exit;
}

if (strtotime($end_time) <= strtotime($start_time)) {
    echo json_encode(['success' => false, 'message' => 'End time must be after start time.']);
    exit;
}

// Check for overlapping schedules
$overlap_query = "SELECT * FROM dentist_availability 
                  WHERE dentist_id = ? AND day_of_week = ? 
                  AND ((? < end_time AND ? > start_time) OR (? < end_time AND ? > start_time))";
$overlap_stmt = $conn->prepare($overlap_query);
$overlap_stmt->bind_param('iissss', $dentist_id, $day_of_week, $start_time, $start_time, $end_time, $end_time);
$overlap_stmt->execute();
$overlap_result = $overlap_stmt->get_result();

if ($overlap_result->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Schedule overlaps with an existing schedule.']);
    exit;
}

// Insert schedule
$insert_query = "INSERT INTO dentist_availability (dentist_id, day_of_week, start_time, end_time, max_appointments, is_available) 
                 VALUES (?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($insert_query);
$stmt->bind_param('iissii', $dentist_id, $day_of_week, $start_time, $end_time, $max_appointments, $is_available);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'message' => 'Schedule added successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to add schedule: ' . $stmt->error]);
}
?>