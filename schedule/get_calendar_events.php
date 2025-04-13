<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\schedule\get_calendar_events.php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

header('Content-Type: application/json');

$dentist_id = isset($_GET['dentist_id']) ? intval($_GET['dentist_id']) : 0;
$start = $_GET['start'] ?? date('Y-m-d');
$end = $_GET['end'] ?? date('Y-m-d', strtotime('+1 month'));

if ($dentist_id <= 0) {
    echo json_encode([]);
    exit;
}

// Initialize the events array
$events = [];

// Calculate the date range
$start_date = new DateTime($start);
$end_date = new DateTime($end);
$interval = new DateInterval('P1D');
$date_range = new DatePeriod($start_date, $interval, $end_date);

// Get weekly schedule
$weekly_query = "SELECT * FROM dentist_availability 
                 WHERE dentist_id = ?";
$weekly_stmt = $conn->prepare($weekly_query);
$weekly_stmt->bind_param('i', $dentist_id);
$weekly_stmt->execute();
$weekly_result = $weekly_stmt->get_result();

$weekly_schedule = [];
while ($row = $weekly_result->fetch_assoc()) {
    $weekly_schedule[$row['day_of_week']] = $row;
}

// Get exceptions
$exception_query = "SELECT * FROM schedule_exceptions 
                    WHERE dentist_id = ? AND exception_date BETWEEN ? AND ?";
$exception_stmt = $conn->prepare($exception_query);
$exception_stmt->bind_param('iss', $dentist_id, $start, $end);
$exception_stmt->execute();
$exception_result = $exception_stmt->get_result();

$exceptions = [];
while ($row = $exception_result->fetch_assoc()) {
    $exceptions[$row['exception_date']] = $row;
}

// Get appointments
$appointment_query = "SELECT a.appointment_date, COUNT(*) as appointment_count 
                      FROM appointments a 
                      JOIN users u ON a.dentist_id = u.id
                      WHERE a.dentist_id = ? AND a.appointment_date BETWEEN ? AND ?
                      AND a.status NOT IN ('cancelled', 'declined')
                      GROUP BY a.appointment_date";
$appointment_stmt = $conn->prepare($appointment_query);
$appointment_stmt->bind_param('iss', $dentist_id, $start, $end);
$appointment_stmt->execute();
$appointment_result = $appointment_stmt->get_result();

$appointments = [];
while ($row = $appointment_result->fetch_assoc()) {
    $appointments[$row['appointment_date']] = $row['appointment_count'];
}

// Loop through each day in the range
foreach ($date_range as $date) {
    $date_str = $date->format('Y-m-d');
    $day_of_week = $date->format('w'); // 0 (Sunday) to 6 (Saturday)
    
    // Check if this date has an exception
    if (isset($exceptions[$date_str])) {
        $exception = $exceptions[$date_str];
        
        if ($exception['is_available'] == 0) {
            // Day off
            $events[] = [
                'title' => 'Unavailable',
                'start' => $date_str,
                'allDay' => true,
                'className' => 'fc-event-unavailable'
            ];
        } else {
            // Custom hours
            $events[] = [
                'title' => 'Custom Hours',
                'start' => $date_str,
                'allDay' => true,
                'className' => 'fc-event-exception'
            ];
        }
    }
    // Check if this day has a regular schedule
    else if (isset($weekly_schedule[$day_of_week])) {
        $schedule = $weekly_schedule[$day_of_week];
        
        if ($schedule['is_available'] == 1) {
            // Regular available day
            $events[] = [
                'title' => 'Available',
                'start' => $date_str,
                'allDay' => true,
                'className' => 'fc-event-available'
            ];
        } else {
            // Regular unavailable day
            $events[] = [
                'title' => 'Unavailable',
                'start' => $date_str,
                'allDay' => true,
                'className' => 'fc-event-unavailable'
            ];
        }
    } else {
        // No schedule defined for this day
        $events[] = [
            'title' => 'No Schedule',
            'start' => $date_str,
            'allDay' => true,
            'className' => 'fc-event-unavailable'
        ];
    }
    
    // Add appointments if there are any
    if (isset($appointments[$date_str])) {
        $events[] = [
            'title' => $appointments[$date_str] . ' Appointment(s)',
            'start' => $date_str,
            'allDay' => true,
            'className' => 'fc-event-booked'
        ];
    }
}

echo json_encode($events);
?>