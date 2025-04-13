<?php
session_start();
require __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
include '../database.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Check admin access
if (
    !isset($_SESSION['user_id']) || !isset($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'staff', 'dentist', 'intern', 'assistant'])
) {
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['success' => false, 'error' => 'Unauthorized access']);
    exit();
}

// Initialize response
$response = ['success' => false, 'message' => ''];

try {
    // Validate inputs
    $appointmentId = filter_input(INPUT_POST, 'appointment_id', FILTER_VALIDATE_INT);
    $action = strtolower(filter_input(INPUT_POST, 'action', FILTER_UNSAFE_RAW));

    if (!$appointmentId || !$action) {
        throw new Exception('Invalid request parameters');
    }

    // Get current appointment status
    $statusCheck = $conn->prepare("SELECT status FROM appointments WHERE appointment_id = ?");
    $statusCheck->bind_param("i", $appointmentId);
    $statusCheck->execute();
    $currentStatus = $statusCheck->get_result()->fetch_assoc()['status'];

    // Get appointment details function
    function getAppointmentDetails($conn, $appointmentId)
    {
        $stmt = $conn->prepare("
            SELECT a.*, p.email, p.first_name, p.last_name, p.contact_number
            FROM appointments a
            JOIN patients p ON a.patient_id = p.patient_info_id
            WHERE a.appointment_id = ?
        ");
        $stmt->bind_param("i", $appointmentId);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    // Email sending function
    function sendNotificationEmail($recipient, $subject, $body)
    {
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'sandbox.smtp.mailtrap.io';
            $mail->SMTPAuth = true;
            $mail->Username = '73e591c1a6dce0';
            $mail->Password = 'f3861c0d0afa9e';
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;

            $mail->setFrom('no-reply@clinic.com', 'Dental Clinic');
            $mail->addAddress($recipient['email'], $recipient['name']);
            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $body;
            $mail->AltBody = strip_tags($body);

            return $mail->send();
        } catch (Exception $e) {
            error_log("Mail Error: " . $e->getMessage());
            return false;
        }
    }

    // Process actions
    switch ($action) {
        case 'approve':
            $allowedStatuses = ['pending', 'Pending', 'PENDING'];
            if (!in_array(strtolower($currentStatus), array_map('strtolower', $allowedStatuses))) {
                throw new Exception('Only pending appointments can be approved');
            }

            $stmt = $conn->prepare("UPDATE appointments SET status = 'confirmed' WHERE appointment_id = ?");
            $stmt->bind_param("i", $appointmentId);

            if ($stmt->execute()) {
                $appointment = getAppointmentDetails($conn, $appointmentId);
                $emailSent = false;

                if ($appointment) {
                    $emailBody = "
                        <h2>Appointment Confirmed</h2>
                        <p>Dear {$appointment['first_name']},</p>
                        <p>Your appointment has been confirmed:</p>
                        <ul>
                            <li><strong>Date:</strong> " . date('F j, Y', strtotime($appointment['appointment_date'])) . "</li>
                            <li><strong>Time:</strong> " . date('g:i A', strtotime($appointment['appointment_time'])) . "</li>
                        </ul>
                        <p>Please arrive 10 minutes early.</p>
                    ";

                    $emailSent = sendNotificationEmail(
                        [
                            'email' => $appointment['email'],
                            'name' => "{$appointment['first_name']} {$appointment['last_name']}"
                        ],
                        'Appointment Confirmation',
                        $emailBody
                    );
                }

                $response = [
                    'success' => true,
                    'message' => $emailSent
                        ? 'Appointment approved and confirmation sent'
                        : 'Appointment approved but email failed',
                    'email_sent' => $emailSent
                ];
            } else {
                throw new Exception('Failed to update appointment status');
            }
            break;

        case 'cancel':
            $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE appointment_id = ?");
            $stmt->bind_param("i", $appointmentId);
            if ($stmt->execute()) {
                $response['success'] = true;
                $response['message'] = 'Appointment successfully cancelled';
            } else {
                $response['success'] = false;
                $response['message'] = 'Failed to cancel appointment';
            }
            break;

        case 'completed': // Changed from 'confirm' to 'completed'
            $stmt = $conn->prepare("UPDATE appointments SET status = 'completed' WHERE appointment_id = ?");
            $stmt->bind_param("i", $appointmentId);
            $response['success'] = $stmt->execute();
            $response['message'] = $response['success']
                ? 'Appointment marked as completed'
                : 'Failed to complete appointment';
            break;

        case 'reschedule':
            $newDate = filter_input(INPUT_POST, 'new_date', FILTER_UNSAFE_RAW);
            $newTime = filter_input(INPUT_POST, 'new_time', FILTER_UNSAFE_RAW);

            if (!$newDate || !$newTime) {
                throw new Exception('Both date and time are required');
            }

            if (!DateTime::createFromFormat('Y-m-d', $newDate)) {
                throw new Exception('Invalid date format. Use YYYY-MM-DD');
            }
            if (!DateTime::createFromFormat('H:i', $newTime)) {
                throw new Exception('Invalid time format. Use HH:MM in 24-hour format');
            }
            $newDateTime = strtotime("$newDate $newTime");

            // Update the appointment date, time, and status to 'reschedule'
            $stmt = $conn->prepare("UPDATE appointments SET appointment_date = ?, appointment_time = ?, status = 'reschedule' WHERE appointment_id = ?");
            $stmt->bind_param("ssi", $newDate, $newTime, $appointmentId);

            if ($stmt->execute()) {
                $appointment = getAppointmentDetails($conn, $appointmentId);
                $emailSent = false;

                if ($appointment) {
                    $emailBody = "
                        <h2>Appointment Rescheduled</h2>
                        <p>Dear {$appointment['first_name']},</p>
                        <p>Your appointment has been rescheduled to:</p>
                        <ul>
                            <li><strong>Date:</strong> " . date('F j, Y', $newDateTime) . "</li>
                            <li><strong>Time:</strong> " . date('g:i A', $newDateTime) . "</li>
                        </ul>
                    ";

                    $emailSent = sendNotificationEmail(
                        [
                            'email' => $appointment['email'],
                            'name' => "{$appointment['first_name']} {$appointment['last_name']}"
                        ],
                        'Appointment Rescheduled',
                        $emailBody
                    );
                }

                $response = [
                    'success' => true,
                    'message' => $emailSent
                        ? 'Appointment rescheduled and notification sent'
                        : 'Appointment rescheduled but email failed',
                    'email_sent' => $emailSent
                ];
            } else {
                throw new Exception('Failed to reschedule appointment');
            }
            break;


        default:
            throw new Exception('Invalid action specified');
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    error_log("Appointment Action Error: " . $e->getMessage());
}

header('Content-Type: application/json');
echo json_encode($response);
?>