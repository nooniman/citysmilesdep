<?php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

header('Content-Type: application/json');

// Enable debugging if needed
$debug = false;
$log = [];
if ($debug)
    $log[] = "Starting get_schedule_events.php";

// Get parameters
$dentist_id = isset($_GET['dentist_id']) ? intval($_GET['dentist_id']) : 0;
$start = $_GET['start'] ?? date('Y-m-d', strtotime('-1 month'));
$end = $_GET['end'] ?? date('Y-m-d', strtotime('+3 months'));

if ($debug)
    $log[] = "Parameters: dentist_id=$dentist_id, start=$start, end=$end";

// Validate dentist ID
if ($dentist_id <= 0) {
    if ($debug)
        file_put_contents(__DIR__ . '/schedule.log', date('[Y-m-d H:i:s] ') . implode("\n", $log) . PHP_EOL, FILE_APPEND);
    echo json_encode(['error' => 'Invalid dentist ID']);
    exit;
}

$events = [];

// Start date for the calendar
$current_date = new DateTime($start);
$current_date->modify('first day of this month'); // Start from the beginning of the month
if ($debug)
    $log[] = "Adjusted start date: " . $current_date->format('Y-m-d');

// End date for the calendar
$end_date = new DateTime($end);
if ($debug)
    $log[] = "End date: " . $end_date->format('Y-m-d');

// Get the weekly schedule for the dentist
// First check if we should use user_id or dentist_id
$column_check = $conn->query("SHOW COLUMNS FROM dentist_availability LIKE 'user_id'");
$use_user_id = ($column_check->num_rows > 0);

if ($use_user_id) {
    $weekly_sql = "SELECT * FROM dentist_availability WHERE user_id = ?";
    if ($debug)
        $log[] = "Using user_id column for dentist_availability table";
} else {
    $weekly_sql = "SELECT * FROM dentist_availability WHERE dentist_id = ?";
    if ($debug)
        $log[] = "Using dentist_id column for dentist_availability table";
}

$weekly_stmt = $conn->prepare($weekly_sql);
$weekly_stmt->bind_param('i', $dentist_id);
$weekly_stmt->execute();
$weekly_result = $weekly_stmt->get_result();

if ($debug)
    $log[] = "Weekly schedule result rows: " . $weekly_result->num_rows;

$weekly_schedule = [];
while ($row = $weekly_result->fetch_assoc()) {
    // Ensure 'is_available' has a default value if missing
    $row['is_available'] = isset($row['is_available']) ? (int) $row['is_available'] : 0;
    $weekly_schedule[$row['day_of_week']] = $row;
    if ($debug)
        $log[] = "Found schedule for day " . $row['day_of_week'];
}

// Get exceptions - check if we need to use user_id or dentist_id
$column_check = $conn->query("SHOW COLUMNS FROM schedule_exceptions LIKE 'user_id'");
$use_user_id_exceptions = ($column_check->num_rows > 0);

if ($use_user_id_exceptions) {
    $exception_sql = "SELECT * FROM schedule_exceptions 
                     WHERE user_id = ? AND exception_date BETWEEN ? AND ?";
    if ($debug)
        $log[] = "Using user_id column for schedule_exceptions table";
} else {
    $exception_sql = "SELECT * FROM schedule_exceptions 
                     WHERE dentist_id = ? AND exception_date BETWEEN ? AND ?";
    if ($debug)
        $log[] = "Using dentist_id column for schedule_exceptions table";
}

$exception_stmt = $conn->prepare($exception_sql);
$exception_stmt->bind_param('iss', $dentist_id, $start, $end);
$exception_stmt->execute();
$exception_result = $exception_stmt->get_result();

if ($debug)
    $log[] = "Exception result rows: " . $exception_result->num_rows;

$exceptions = [];
while ($row = $exception_result->fetch_assoc()) {
    $exceptions[$row['exception_date']] = $row;
    if ($debug)
        $log[] = "Found exception for date " . $row['exception_date'];
}

// Get dentist name for event titles
$dentist_query = "SELECT first_name, last_name FROM users WHERE id = ?";
$dentist_stmt = $conn->prepare($dentist_query);
$dentist_stmt->bind_param('i', $dentist_id);
$dentist_stmt->execute();
$dentist_result = $dentist_stmt->get_result();
$dentist_name = "Dr. Unknown";

if ($dentist_result->num_rows > 0) {
    $dentist = $dentist_result->fetch_assoc();
    $dentist_name = "Dr. " . $dentist['first_name'] . " " . $dentist['last_name'];
}

// Set timezone to ensure consistency
date_default_timezone_set('Asia/Manila'); // Replace with your server's timezone

// Log the current date for debugging
if ($debug) {
    $log[] = "Server timezone: " . date_default_timezone_get();
    $log[] = "Current server date: " . date('Y-m-d H:i:s');
}

// Loop through each day in the range
while ($current_date <= $end_date) {
    $date_str = $current_date->format('Y-m-d');
    $day_of_week = (int) $current_date->format('w'); // 0 (Sunday) through 6 (Saturday)

    // Log the current day's processing
    if ($debug && $date_str === date('Y-m-d')) {
        $log[] = "Processing current day: $date_str";
    }

    // Check if there's an exception for this date
    if (isset($exceptions[$date_str])) {
        $exception = $exceptions[$date_str];
        if ($debug)
            $log[] = "Processing exception for $date_str";

        $exception['is_available'] = isset($exception['is_available']) ? (int) $exception['is_available'] : 0;

        if ($exception['is_available'] == 1) {
            // Custom available hours
            $events[] = [
                'id' => 'exception_' . $exception['exception_id'],
                'title' => $dentist_name . ' - Custom Hours',
                'start' => $date_str . 'T' . $exception['start_time'],
                'end' => $date_str . 'T' . $exception['end_time'],
                'backgroundColor' => '#6ace70',
                'borderColor' => '#6ace70',
                'textColor' => '#fff',
                'allDay' => false,
                'extendedProps' => [
                    'type' => 'exception',
                    'is_available' => true,
                    'exception_id' => $exception['exception_id'],
                    'reason' => $exception['reason'] ?? ''
                ]
            ];

            if ($debug)
                $log[] = "Added available exception event for $date_str";
        } else {
            // Day off or unavailable
            $events[] = [
                'id' => 'exception_' . $exception['exception_id'],
                'title' => $dentist_name . ' - Unavailable',
                'start' => $date_str,
                'backgroundColor' => '#dc3545',
                'borderColor' => '#dc3545',
                'textColor' => '#fff',
                'allDay' => true,
                'extendedProps' => [
                    'type' => 'exception',
                    'is_available' => false,
                    'exception_id' => $exception['exception_id'],
                    'reason' => $exception['reason'] ?? ''
                ]
            ];

            if ($debug)
                $log[] = "Added unavailable exception event for $date_str";
        }
    }
    // Check regular weekly schedule if no exception
    else if (isset($weekly_schedule[$day_of_week])) {
        $schedule = $weekly_schedule[$day_of_week];
        if ($debug)
            $log[] = "Processing regular schedule for $date_str (day $day_of_week)";

        $schedule['is_available'] = isset($schedule['is_available']) ? (int) $schedule['is_available'] : 0;

        if ($schedule['is_available'] == 1) {
            $events[] = [
                'id' => 'schedule_' . $schedule['availability_id'] . '_' . $date_str,
                'title' => $dentist_name . ' - Regular Hours',
                'start' => $date_str . 'T' . $schedule['start_time'],
                'end' => $date_str . 'T' . $schedule['end_time'],
                'backgroundColor' => '#9d7ded',
                'borderColor' => '#9d7ded',
                'textColor' => '#fff',
                'allDay' => false,
                'extendedProps' => [
                    'type' => 'regular',
                    'is_available' => true,
                    'availability_id' => $schedule['availability_id'],
                    'max_appointments' => $schedule['max_appointments']
                ]
            ];

            if ($debug)
                $log[] = "Added available regular event for $date_str";
        } else {
            // Regular unavailable day
            $events[] = [
                'id' => 'schedule_' . $schedule['availability_id'] . '_' . $date_str,
                'title' => $dentist_name . ' - Not Scheduled',
                'start' => $date_str,
                'backgroundColor' => '#6c757d',
                'borderColor' => '#6c757d',
                'textColor' => '#fff',
                'allDay' => true,
                'extendedProps' => [
                    'type' => 'regular',
                    'is_available' => false,
                    'availability_id' => $schedule['availability_id']
                ]
            ];

            if ($debug)
                $log[] = "Added unavailable regular event for $date_str";
        }
    }

    // Move to the next day
    $current_date->modify('+1 day');
}

// Now check if there are appointments for this dentist in the date range
// This can help visualize actual bookings on the calendar
$appointments_query = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, 
                      a.status, CONCAT(p.first_name, ' ', p.last_name) AS patient_name, 
                      s.name AS service_name, s.duration_minutes
                      FROM appointments a 
                      JOIN patients p ON a.patient_id = p.patient_info_id
                      JOIN services s ON a.service_id = s.services_id
                      WHERE a.appointment_date BETWEEN ? AND ?";

// Check if staff_id or dentist_id column exists and add appropriate condition
$column_check = $conn->query("SHOW COLUMNS FROM appointments LIKE 'staff_id'");
if ($column_check->num_rows > 0) {
    $appointments_query .= " AND a.staff_id = ?";
    $use_staff_id = true;
} else {
    $column_check = $conn->query("SHOW COLUMNS FROM appointments LIKE 'dentist_id'");
    if ($column_check->num_rows > 0) {
        $appointments_query .= " AND a.dentist_id = ?";
        $use_staff_id = false;
    } else {
        // No dentist column found - we'll get all appointments
        $use_staff_id = null;
    }
}

$appointments_query .= " AND a.status != 'cancelled' ORDER BY a.appointment_date, a.appointment_time";

if ($use_staff_id !== null) {
    $appt_stmt = $conn->prepare($appointments_query);
    $appt_stmt->bind_param('ssi', $start, $end, $dentist_id);
} else {
    $appt_stmt = $conn->prepare($appointments_query);
    $appt_stmt->bind_param('ss', $start, $end);
}

$appt_stmt->execute();
$appt_result = $appt_stmt->get_result();

if ($debug)
    $log[] = "Found " . $appt_result->num_rows . " appointments";

// Add appointment events
while ($appt = $appt_result->fetch_assoc()) {
    // Calculate end time based on duration
    $start_time = $appt['appointment_date'] . 'T' . $appt['appointment_time'];
    $duration = $appt['duration_minutes'] ?? 60; // Default 60 minutes if not specified

    // Create DateTime object from the start time
    $end_time_obj = new DateTime($start_time);
    // Add duration in minutes
    $end_time_obj->modify('+' . $duration . ' minutes');
    $end_time = $end_time_obj->format('Y-m-d\TH:i:s');

    // Set color based on status
    $color = '#17a2b8'; // Default info color
    switch ($appt['status']) {
        case 'confirmed':
            $color = '#28a745'; // Green
            break;
        case 'pending':
            $color = '#ffc107'; // Yellow
            break;
        case 'completed':
            $color = '#6c757d'; // Gray
            break;
    }

    $events[] = [
        'id' => 'appointment_' . $appt['appointment_id'],
        'title' => $appt['patient_name'] . ' - ' . $appt['service_name'],
        'start' => $start_time,
        'end' => $end_time,
        'backgroundColor' => $color,
        'borderColor' => $color,
        'textColor' => '#fff',
        'allDay' => false,
        'extendedProps' => [
            'type' => 'appointment',
            'appointment_id' => $appt['appointment_id'],
            'status' => $appt['status'],
            'patient_name' => $appt['patient_name'],
            'service_name' => $appt['service_name']
        ]
    ];

    if ($debug)
        $log[] = "Added appointment event ID " . $appt['appointment_id'] . " for " . $start_time;
}

// Log the events for debugging
if ($debug) {
    $log[] = "Events array before JSON encoding: " . print_r($events, true);
}

// Encode events to JSON and handle errors
$json_response = json_encode($events);
if (json_last_error() !== JSON_ERROR_NONE) {
    if ($debug) {
        $log[] = "JSON encoding error: " . json_last_error_msg();
        file_put_contents(__DIR__ . '/schedule.log', date('[Y-m-d H:i:s] ') . implode("\n", $log) . PHP_EOL, FILE_APPEND);
    }
    echo json_encode(['error' => 'Failed to encode events to JSON']);
    exit;
}

// Log the JSON response for debugging
if ($debug) {
    $log[] = "JSON response: " . $json_response;
    file_put_contents(__DIR__ . '/schedule.log', date('[Y-m-d H:i:s] ') . implode("\n", $log) . PHP_EOL, FILE_APPEND);
}

echo $json_response;
?>