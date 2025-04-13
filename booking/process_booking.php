<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit();
}
include '../database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get posted data
    $user_id          = $_SESSION['user_id']; // This comes from the users table
    $service_id       = $_POST['service_id'];
    $appointment_date = $_POST['appointment_date'];
    $start_time       = $_POST['start_time'];
    $end_time         = $_POST['end_time']; // not used if appointments table has only one time column
    $status           = 'pending';

    // Insert appointment record (appointments table)
    $stmt = $conn->prepare("INSERT INTO appointments (patient_id, appointment_date, appointment_time, status, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())");
    if (!$stmt) {
        die("Prepare for appointments failed: " . $conn->error);
    }
    // Note: Here appointments.patient_id is set to the user's id.
    $appointment_time = $start_time;
    $stmt->bind_param("isss", $user_id, $appointment_date, $appointment_time, $status);
    if (!$stmt->execute()) {
        die("Error inserting appointment: " . $stmt->error);
    }
    $appointment_id = $conn->insert_id;
    $stmt->close();

    // Retrieve the patient's info id from the patients table.
    // This query assumes that the patients table's email matches the users table's email.
    $patient_info_id = null;
    $q = $conn->prepare("SELECT p.patient_info_id 
                         FROM patients p 
                         JOIN users u ON p.email = u.email 
                         WHERE u.id = ?");
    if (!$q) {
        die("Prepare for patient query failed: " . $conn->error);
    }
    $q->bind_param("i", $user_id);
    $q->execute();
    $q->bind_result($patient_info_id);
    if (!$q->fetch()) {
        // No patient record found; insert a new record.
        $q->close();
        
        // Retrieve user's essential info for patient record.
        $userQuery = $conn->prepare("SELECT email, first_name, last_name, middle_name, gender, contact, address, birthdate, occupation, civil_status, religion FROM users WHERE id = ?");
        if (!$userQuery) {
            die("Prepare for retrieving user failed: " . $conn->error);
        }
        $userQuery->bind_param("i", $user_id);
        $userQuery->execute();
        $userQuery->bind_result($user_email, $first_name, $last_name, $middle_name, $gender, $contact, $address, $birthdate, $occupation, $civil_status, $religion);
        if (!$userQuery->fetch()) {
            die("User record not found.");
        }
        $userQuery->close();
        
        // Insert new patient record with available data.
        $insertPatient = $conn->prepare("INSERT INTO patients (first_name, last_name, middle_name, gender, contact_number, address, birth_date, occupation, civil_status, religion, email, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())");
        if (!$insertPatient) {
            die("Prepare for inserting patient record failed: " . $conn->error);
        }
        $insertPatient->bind_param("sssssssssss", $first_name, $last_name, $middle_name, $gender, $contact, $address, $birthdate, $occupation, $civil_status, $religion, $user_email);
        if (!$insertPatient->execute()){
           die("Error inserting patient record: " . $insertPatient->error);
        }
        $patient_info_id = $conn->insert_id;
        $insertPatient->close();
    } else {
        $q->close();
    }

    // Insert into treatments with the obtained patient_info_id.
    // Here we set fee to 0 and notes as an empty string; adjust as needed.
    $fee = 0; 
    $notes = "";
    $stmt2 = $conn->prepare("INSERT INTO treatments (patient_id, appointment_id, service_id, fee, notes, created_at, updated_at) VALUES (?, ?, ?, ?, ?, NOW(), NOW())");
    if (!$stmt2) {
        die("Prepare for treatments failed: " . $conn->error);
    }
    $stmt2->bind_param("iiids", $patient_info_id, $appointment_id, $service_id, $fee, $notes);
    if ($stmt2->execute()) {
        echo "Appointment booked successfully. <a href='book_appointment.php'>Book Another Appointment</a>";
    } else {
        echo "Error inserting treatment: " . $stmt2->error;
    }
    $stmt2->close();
} else {
    header("Location: book_appointment.php");
    exit();
}
?>