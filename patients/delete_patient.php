<?php
include '../database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['patient_id'])) {
    $patientId = intval($_POST['patient_id']);

    // Delete the patient from the patients table
    $deletePatient = "DELETE FROM patients WHERE patient_info_id = $patientId";
    if ($conn->query($deletePatient)) {
        header('Location: patient.php?success=1');
    } else {
        echo "Error deleting patient: " . $conn->error;
    }
} else {
    echo "Invalid request";
}
?>