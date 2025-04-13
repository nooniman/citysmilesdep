<?php
include '../database.php';

if (isset($_GET['patient_info_id'])) {
    $patientInfoId = intval($_GET['patient_info_id']);
    $query = "SELECT previous_dentist, last_dental_visit, intraoral_exam_image, chief_complaint 
              FROM patient_history 
              WHERE patient_info_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $patientInfoId);
    $stmt->execute();
    $result = $stmt->get_result();
    $history = $result->fetch_assoc();

    echo json_encode($history);
} else {
    echo json_encode(['error' => 'Invalid patient_info_id']);
}
?>