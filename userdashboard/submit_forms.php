<?php
session_start();
include '../database.php';  // Database connection

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "User not logged in.";
    exit();
}

$user_id = $_SESSION['user_id']; // Get logged-in user ID

// Fetch user details
$userQuery = $conn->prepare("SELECT * FROM users WHERE id = ?");
$userQuery->bind_param("i", $user_id);
$userQuery->execute();
$user = $userQuery->get_result()->fetch_assoc();

if (!$user) {
    echo "User not found.";
    exit();
}

$conn->begin_transaction(); // Start transaction

try {
    // Check if the patient already exists in the patients table
    $stmt = $conn->prepare("SELECT patient_info_id FROM patients WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        // Insert new patient (Ensure column names match)
        $stmt = $conn->prepare("INSERT INTO patients 
            (last_name, first_name, middle_name, gender, contact_number, address, birth_date, occupation, civil_status, religion, email, created_at, image_path, user_id) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?)");
        $stmt->bind_param(
            "ssssssssssssi",
            $user['last_name'],
            $user['first_name'],
            $user['middle_name'],
            $user['gender'],
            $user['contact'],
            $user['address'],
            $user['birthdate'],
            $user['occupation'],
            $user['civil_status'],
            $user['religion'],
            $user['email'],
            $user['image'],  // Assuming image_path is the correct column
            $user_id
        );
        $stmt->execute();
        $patient_id = $conn->insert_id; // Get the newly inserted patient ID
    } else {
        // Patient already exists, use the existing ID
        $row = $result->fetch_assoc();
        $patient_id = $row['patient_info_id'];
    }

    // Ensure $_POST variables are set before using them
    $appointment_date = $_POST['appointment_date'] ?? null;
    $appointment_time = $_POST['appointment_time'] ?? null;

    if (!$appointment_date || !$appointment_time) {
        throw new Exception("Appointment date or time is missing.");
    }

    // Insert appointment
    $stmt = $conn->prepare("INSERT INTO appointments 
        (patient_id, appointment_date, appointment_time, status, created_at, visit_type) 
        VALUES (?, ?, ?, 'Pending', NOW(), 'Online')");
    $stmt->bind_param("iss", $patient_id, $appointment_date, $appointment_time);
    $stmt->execute();

    // Assign $_POST values to variables before using them in bind_param()
    $previous_dentist = $_POST['previous_dentist'] ?? '';
    $last_dental_visit = $_POST['last_dental_visit'] ?? '';
    $past_dental_issues = $_POST['past_dental_issues'] ?? '';
    $previous_treatment = $_POST['previous_treatment'] ?? '';
    $chief_complaint = $_POST['chief_complaint'] ?? '';

    // Insert into patient history
    $stmt = $conn->prepare("INSERT INTO patient_history 
        (patient_info_id, previous_dentist, last_dental_visit, past_dental_issues, previous_treatment, chief_complaint) 
        VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "isssss",
        $patient_id,
        $previous_dentist,
        $last_dental_visit,
        $past_dental_issues,
        $previous_treatment,
        $chief_complaint
    );
    $stmt->execute();

    // Assign $_POST values for patient health declaration
    $height = $_POST['height'] ?? '';
    $weight = $_POST['weight'] ?? '';
    $good_health = $_POST['good_health'] ?? '';
    $under_medical_treatment = $_POST['under_medical_treatment'] ?? '';
    $medical_condition = $_POST['medical_condition'] ?? '';
    $surgical_operation = $_POST['surgical_operation'] ?? '';
    $surgical_operation_details = $_POST['surgical_operation_details'] ?? '';
    $hospital_admission = $_POST['hospital_admission'] ?? '';
    $hospital_admission_reason = $_POST['hospital_admission_reason'] ?? '';
    $taking_medications = $_POST['taking_medications'] ?? '';

    // Insert into patient health declaration
    $stmt = $conn->prepare("INSERT INTO patient_health_declaration 
        (patient_info_id, height, weight, good_health, under_medical_treatment, medical_condition, surgical_operation, surgical_operation_details, hospital_admission, hospital_admission_reason, taking_medications) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param(
        "issssssssss",
        $patient_id,
        $height,
        $weight,
        $good_health,
        $under_medical_treatment,
        $medical_condition,
        $surgical_operation,
        $surgical_operation_details,
        $hospital_admission,
        $hospital_admission_reason,
        $taking_medications
    );
    $stmt->execute();

    $conn->commit(); // Commit transaction
    echo "All forms submitted successfully!";
} catch (Exception $e) {
    $conn->rollback(); // Rollback on error
    echo "Error: " . $e->getMessage();
}
?>