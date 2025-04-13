<?php
// get_patient_details.php
include '../database.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No patient ID provided']);
    exit;
}

$patient_id = intval($_GET['id']);
$response = [];

// 1. Get patient details
$patientQuery = $conn->prepare("SELECT * FROM patients WHERE patient_info_id = ?");
$patientQuery->bind_param("i", $patient_id);
$patientQuery->execute();
$patientResult = $patientQuery->get_result();

if ($patientResult->num_rows > 0) {
    $response['patient'] = $patientResult->fetch_assoc();
} else {
    echo json_encode(['error' => 'Patient not found']);
    exit;
}

// 2. Get health declaration
$healthQuery = $conn->prepare("SELECT * FROM patient_health_declaration WHERE patient_info_id = ?");
$healthQuery->bind_param("i", $patient_id);
$healthQuery->execute();
$healthResult = $healthQuery->get_result();
$response['health'] = $healthResult->fetch_assoc() ?? [];

// 3. Get patient history
$historyQuery = $conn->prepare("SELECT * FROM patient_history WHERE patient_info_id = ?");
$historyQuery->bind_param("i", $patient_id);
$historyQuery->execute();
$historyResult = $historyQuery->get_result();
$response['history'] = $historyResult->fetch_assoc() ?? [];

echo json_encode($response);
?>
