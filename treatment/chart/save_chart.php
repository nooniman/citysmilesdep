<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
include '../../database.php';

// Set the header type to JSON
header('Content-Type: application/json');

// Capture and log raw input for debugging
$raw_input = file_get_contents('php://input');
error_log("Raw Input: " . $raw_input);

// Decode JSON safely
$data = json_decode($raw_input, true);

// Check for JSON decoding errors
if (json_last_error() !== JSON_ERROR_NONE) {
    echo json_encode([
        'success' => false,
        'error' => 'Invalid JSON input',
        'raw' => $raw_input,
        'json_error' => json_last_error_msg()
    ]);
    exit;
}

// Validate required fields
if (empty($data['appointment_id']) || empty($data['chart'])) {
    echo json_encode([
        'success' => false,
        'error' => 'Missing required fields: appointment_id or chart'
    ]);
    exit;
}

$appointment_id = intval($data['appointment_id']); // Ensure appointment_id is an integer
$chart = mysqli_real_escape_string($conn, trim($data['chart'])); // Sanitize chart data

// Debugging logs for validation
error_log("Appointment ID: " . $appointment_id);
error_log("Chart Data (truncated): " . substr($chart, 0, 100)); // Log first 100 characters of the chart

// Fetch the patient_info_id using the appointment_id
$query = "SELECT patient_id FROM appointments WHERE appointment_id = ?";
$stmt = mysqli_prepare($conn, $query);
mysqli_stmt_bind_param($stmt, "i", $appointment_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    error_log("MySQL Error: " . mysqli_error($conn));
    echo json_encode([
        'success' => false,
        'error' => 'Database error while fetching patient_id'
    ]);
    exit;
}

$patient_info = mysqli_fetch_assoc($result);
$patient_info_id = $patient_info['patient_id'] ?? null;

// Debugging patient_info_id
error_log("Patient Info ID: " . ($patient_info_id ?? 'NULL'));

if (!$patient_info_id) {
    echo json_encode([
        'success' => false,
        'error' => 'No patient found for the given appointment_id'
    ]);
    exit;
}

// Update intraoral_exam_image in patient_history table
$update_query = "UPDATE patient_history SET intraoral_exam_image = ?, updated_at = NOW() WHERE patient_info_id = ?";
$update_stmt = mysqli_prepare($conn, $update_query);
mysqli_stmt_bind_param($update_stmt, "si", $chart, $patient_info_id);

if (mysqli_stmt_execute($update_stmt)) {
    if (mysqli_stmt_affected_rows($update_stmt) > 0) {
        echo json_encode([
            'success' => true,
            'message' => 'Chart saved successfully'
        ]);
    } else {
        error_log("No rows updated. Check patient_info_id existence in patient_history.");
        echo json_encode([
            'success' => false,
            'error' => 'No record found to update'
        ]);
    }
} else {
    error_log("MySQL Update Error: " . mysqli_error($conn));
    echo json_encode([
        'success' => false,
        'error' => 'Failed to update patient_history'
    ]);
}

// Close database connection
mysqli_close($conn);
?>