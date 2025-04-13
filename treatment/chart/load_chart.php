<?php
include '../../database.php';

$appointment_id = $_GET['appointment_id'];

// Fetch the patient_info_id using the appointment_id
$patient_info_id_query = "SELECT patient_id FROM appointments WHERE appointment_id = ?";
$stmt = mysqli_prepare($conn, $patient_info_id_query);
mysqli_stmt_bind_param($stmt, "i", $appointment_id);
mysqli_stmt_execute($stmt);
$patient_info_id_result = mysqli_stmt_get_result($stmt);

if (!$patient_info_id_result) {
    echo json_encode(['success' => false, 'error' => 'Failed to fetch patient_info_id: ' . mysqli_error($conn)]);
    exit;
}

$patient_info_id_row = mysqli_fetch_assoc($patient_info_id_result);
$patient_info_id = $patient_info_id_row['patient_id'] ?? null;

// Check if patient_id is found
if (!$patient_info_id) {
    echo json_encode(['success' => false, 'error' => 'No patient found for the given appointment_id']);
    exit;
}

// Fetch the intraoral_exam_image from the patient_history table
$sql = "SELECT intraoral_exam_image FROM patient_history WHERE patient_info_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $patient_info_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result) {
    echo json_encode(['success' => false, 'error' => 'Failed to fetch intraoral_exam_image: ' . mysqli_error($conn)]);
    exit;
}

$row = mysqli_fetch_assoc($result);

// Return the chart data even if no image exists
echo json_encode([
    'success' => true,
    'chart' => $row['intraoral_exam_image'] ?? null, // If no image exists, return null
    'message' => $row['intraoral_exam_image'] ? 'Chart loaded successfully' : 'No existing chart. Ready for a new one.'
]);

mysqli_stmt_close($stmt);
mysqli_close($conn);
?>