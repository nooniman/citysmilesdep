<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\userdashboard\save_appointment.php
session_start();
include '../database.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['appointment_error'] = "Invalid request method.";
    header("Location: appointment.php");
    exit();
}

// Get form data
$patient_id = $_SESSION['user_id'];
$service_id = isset($_POST['service_id']) ? intval($_POST['service_id']) : 0;
$dentist_id = !empty($_POST['dentist_id']) ? intval($_POST['dentist_id']) : null;
$selected_date = $_POST['selected_date'] ?? '';
$selected_time = $_POST['selected_time'] ?? '';
$notes = $_POST['notes'] ?? '';

// Validation
if (empty($selected_date) || empty($selected_time) || $service_id <= 0) {
    $_SESSION['appointment_error'] = "Please complete all required fields.";
    header("Location: appointment.php");
    exit();
}

// Get service duration
$duration_query = "SELECT duration_minutes FROM services WHERE services_id = ?";
$duration_stmt = $conn->prepare($duration_query);
$duration_stmt->bind_param('i', $service_id);
$duration_stmt->execute();
$duration_result = $duration_stmt->get_result();
$duration = 60; // Default duration
if ($duration_result->num_rows > 0) {
    $duration = $duration_result->fetch_assoc()['duration_minutes'];
}

// If no specific dentist is selected, find an available one
if (empty($dentist_id)) {
    $day_of_week = date('w', strtotime($selected_date));
    
    // Check for dentists available at the selected time
    $available_dentist_query = "
        SELECT da.dentist_id
        FROM dentist_availability da
        LEFT JOIN schedule_exceptions se ON da.dentist_id = se.dentist_id AND se.exception_date = ?
        WHERE (
            (da.day_of_week = ? AND da.is_available = 1) OR 
            (se.exception_date IS NOT NULL AND se.is_available = 1)
        )
        AND (
            (se.exception_date IS NULL AND ? >= da.start_time AND DATE_ADD(?, INTERVAL ? MINUTE) <= da.end_time) OR
            (se.exception_date IS NOT NULL AND ? >= se.start_time AND DATE_ADD(?, INTERVAL ? MINUTE) <= se.end_time)
        )
        AND da.dentist_id NOT IN (
            SELECT a.dentist_id
            FROM appointments a
            WHERE a.appointment_date = ?
            AND a.status NOT IN ('cancelled', 'declined')
            AND (
                (a.appointment_time <= ? AND DATE_ADD(a.appointment_time, INTERVAL a.duration MINUTE) > ?) OR
                (a.appointment_time > ? AND a.appointment_time < DATE_ADD(?, INTERVAL ? MINUTE))
            )
        )
        LIMIT 1
    ";
    
    $stmt = $conn->prepare($available_dentist_query);
    $stmt->bind_param('sissisisssssi', $selected_date, $day_of_week, $selected_time, $selected_time, $duration, 
                      $selected_time, $selected_time, $duration, $selected_date, $selected_time, $selected_time, 
                      $selected_time, $selected_time, $duration);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $dentist_id = $result->fetch_assoc()['dentist_id'];
    } else {
        $_SESSION['appointment_error'] = "No dentists are available at the selected time. Please choose another time.";
        header("Location: appointment.php");
        exit();
    }
}

// Check if the selected time slot is available
$check_query = "
    SELECT COUNT(*) as count
    FROM appointments
    WHERE appointment_date = ?
    AND dentist_id = ?
    AND status NOT IN ('cancelled', 'declined')
    AND (
        (appointment_time <= ? AND DATE_ADD(appointment_time, INTERVAL duration MINUTE) > ?) OR
        (appointment_time > ? AND appointment_time < DATE_ADD(?, INTERVAL ? MINUTE))
    )
";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param('sississi', $selected_date, $dentist_id, $selected_time, $selected_time, 
                       $selected_time, $selected_time, $duration);
$check_stmt->execute();
$check_result = $check_stmt->get_result();
$row = $check_result->fetch_assoc();

if ($row['count'] > 0) {
    $_SESSION['appointment_error'] = "This time slot is no longer available. Please choose another time.";
    header("Location: appointment.php");
    exit();
}

// Insert appointment
$query = "INSERT INTO appointments 
          (patient_id, dentist_id, service_id, appointment_date, appointment_time, duration, status, notes, created_at) 
          VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW())";
$stmt = $conn->prepare($query);
$stmt->bind_param('iiissss', $patient_id, $dentist_id, $service_id, $selected_date, $selected_time, $duration, $notes);

if ($stmt->execute()) {
    $appointment_id = $conn->insert_id;
    $_SESSION['appointment_success'] = "Your appointment has been scheduled successfully. Appointment ID: " . $appointment_id;
} else {
    $_SESSION['appointment_error'] = "Failed to schedule appointment: " . $stmt->error;
}

header("Location: appointment.php");
exit();
?>