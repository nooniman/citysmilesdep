<?php
include '../database.php';
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $appointment_id = $_POST['appointment_id'] ?? null;
    $date = $_POST['date'] ?? null;
    $medicine = $_POST['medicine'] ?? null;
    $instructions = $_POST['instructions'] ?? null;
    $additionalNotes = $_POST['notes'] ?? '';

    // Validate required fields
    if (!$appointment_id || !$date || !$medicine || !$instructions) {
        die("All fields are required.");
    }

    // Combine instructions and additional notes into one notes field
    $notesCombined = "Instructions: " . $instructions . "\nAdditional Notes: " . $additionalNotes;

    // Get the patient_info_id from the appointments table
    $stmt = $conn->prepare("SELECT patient_id FROM appointments WHERE appointment_id = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $patient_info_id = $row['patient_id'];
    } else {
        die("Appointment not found.");
    }

    $currentTime = date('Y-m-d H:i:s');

    // Insert into prescriptions.
    // Notice that the prescriptions table uses the column date_prescripted for the date.
    $stmtInsert = $conn->prepare("INSERT INTO prescriptions (appointment_id, patient_info_id, date_prescripted, medicine, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmtInsert->bind_param("iisssss", $appointment_id, $patient_info_id, $date, $medicine, $notesCombined, $currentTime, $currentTime);

    if ($stmtInsert->execute()) {
        header("Location: treatment.php");
        exit;
    } else {
        die("Error inserting prescription: " . $stmtInsert->error);
    }
} else {
    die("Invalid request.");
}
?>