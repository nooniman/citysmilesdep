<?php
include '../database.php';

if (isset($_GET['id'])) {
    $patientId = intval($_GET['id']);
    $query = "SELECT * FROM patients WHERE patient_info_id = $patientId";
    $result = $conn->query($query);

    if ($result->num_rows > 0) {
        $patient = $result->fetch_assoc();
        echo json_encode($patient);
    } else {
        echo json_encode(['error' => 'Patient not found']);
    }
} else {
    echo json_encode(['error' => 'Invalid request']);
}
?>