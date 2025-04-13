<?php
require '../../database.php'; // Include your database connection

// Step 2: Debugging logs for appointment_id
$appointment_id = $_GET['appointment_id'];

if (!$appointment_id) {
    error_log("Missing appointment_id in request."); // Log missing parameter
    echo json_encode(['success' => false, 'error' => 'Missing appointment_id']);
    exit;
}

error_log("Fetching age for appointment_id: " . $appointment_id); // Log received appointment_id

// Proceed with fetching birth_date
$query = "SELECT p.birth_date 
          FROM appointments a
          JOIN patients p ON a.patient_id = p.patient_info_id
          WHERE a.appointment_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $appointment_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $birth_date = $row['birth_date'];
    $age = date_diff(date_create($birth_date), date_create('today'))->y; // Calculate age

    echo json_encode(['success' => true, 'age' => $age]);
} else {
    error_log("No patient found for appointment_id: " . $appointment_id); // Log missing patient data
    echo json_encode(['success' => false, 'error' => 'Patient not found.']);
}

$stmt->close();
$conn->close();
?>