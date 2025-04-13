<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\userdashboard\save_appointment.php
session_start();
include '../database.php';

// Debug log
$log_file = __DIR__ . '/appointment_debug.log';
file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "POST data: " . json_encode($_POST) . PHP_EOL, FILE_APPEND);

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['appointment_error'] = "You must be logged in to book an appointment.";
    header('Location: appointment.php');
    exit;
}

// Check form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['appointment_error'] = "Invalid request.";
    header('Location: appointment.php');
    exit;
}

// Get user_id from session
$user_id = $_SESSION['user_id'];

// IMPORTANT FIX: Get the correct patient_info_id from the patients table for this user
$patient_query = "SELECT patient_info_id FROM patients WHERE user_id = ?";
$patient_stmt = $conn->prepare($patient_query);
$patient_stmt->bind_param('i', $user_id);
$patient_stmt->execute();
$patient_result = $patient_stmt->get_result();

if ($patient_result->num_rows === 0) {
    // No patient record for this user
    $_SESSION['appointment_error'] = "Patient record not found. Please complete your profile first.";
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "Error: No patient record found for user_id $user_id" . PHP_EOL, FILE_APPEND);
    header('Location: appointment.php');
    exit;
}

// Get the correct patient_info_id
$patient_id = $patient_result->fetch_assoc()['patient_info_id'];

// Get form data
$service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
$staff_id = isset($_POST['dentist_id']) && !empty($_POST['dentist_id']) ? intval($_POST['dentist_id']) : NULL;
$selected_date = $_POST['selected_date'] ?? '';
$selected_time = $_POST['selected_time'] ?? '';
$notes = $_POST['notes'] ?? '';
$appointment_for = $_POST['appointment_for'] ?? 'self';
$family_member_id = null;

if ($appointment_for === 'family_member') {
    $family_member_id = $_POST['family_member_id'] ?? null;

    // Verify the family member belongs to this user
    if ($family_member_id) {
        $verify_query = "SELECT member_id FROM family_members WHERE member_id = ? AND user_id = ?";
        $verify_stmt = $conn->prepare($verify_query);
        $verify_stmt->bind_param("ii", $family_member_id, $user_id);
        $verify_stmt->execute();

        if ($verify_stmt->get_result()->num_rows === 0) {
            $_SESSION['appointment_error'] = 'Invalid family member selection.';
            file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "Error: Invalid family member selection" . PHP_EOL, FILE_APPEND);
            header("Location: appointment.php");
            exit;
        }
    } else {
        $_SESSION['appointment_error'] = 'Please select a family member.';
        file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "Error: No family member selected" . PHP_EOL, FILE_APPEND);
        header("Location: appointment.php");
        exit;
    }
}

file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "Processing: user_id=$user_id, patient_id=$patient_id, service=$service_id, staff_id=" . ($staff_id ?? 'NULL') . ", date=$selected_date, time=$selected_time" . PHP_EOL, FILE_APPEND);

// Validate data
if ($service_id <= 0 || empty($selected_date) || empty($selected_time)) {
    $_SESSION['appointment_error'] = "Please fill in all required fields.";
    header('Location: appointment.php');
    exit;
}

// Use staff_id column in appointments table
$sql = "INSERT INTO appointments (patient_id, family_member_id, staff_id, service_id, appointment_date, appointment_time, status, notes, created_at, updated_at) 
        VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())";

$stmt = $conn->prepare($sql);
$stmt->bind_param('iiissss', $patient_id, $family_member_id, $staff_id, $service_id, $selected_date, $selected_time, $notes);

if ($stmt->execute()) {
    $_SESSION['appointment_success'] = "Your appointment has been successfully scheduled. You will receive a confirmation soon.";
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "Appointment saved successfully with ID: " . $conn->insert_id . PHP_EOL, FILE_APPEND);
} else {
    $_SESSION['appointment_error'] = "Error scheduling appointment: " . $stmt->error;
    file_put_contents($log_file, date('[Y-m-d H:i:s] ') . "Error: " . $stmt->error . PHP_EOL, FILE_APPEND);
}

header('Location: appointment.php');
exit;
?>