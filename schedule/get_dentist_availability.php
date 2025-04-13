<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\schedule\get_dentist_availability.php
include '../database.php';
header('Content-Type: application/json');

// Get parameters
$dentist_id = isset($_GET['dentist_id']) ? intval($_GET['dentist_id']) : 0;
$start_date = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d');
$end_date = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d', strtotime('+30 days'));

if ($dentist_id <= 0) {
    echo json_encode(['error' => 'Invalid dentist ID']);
    exit;
}

// Get regular weekly schedule
$weekly_query = "SELECT * FROM dentist_availability 
                WHERE dentist_id = ? 
                ORDER BY day_of_week, start_time";
$weekly_stmt = $conn->prepare($weekly_query);
$weekly_stmt->bind_param("i", $dentist_id);
$weekly_stmt->execute();
$weekly_result = $weekly_stmt->get_result();

$weekly_schedule = [];
while ($row = $weekly_result->fetch_assoc()) {
    $weekly_schedule[] = $row;
}

// Get exceptions
$exception_query = "SELECT * FROM schedule_exceptions 
                   WHERE dentist_id = ? 
                   AND exception_date BETWEEN ? AND ?
                   ORDER BY exception_date";
$exception_stmt = $conn->prepare($exception_query);
$exception_stmt->bind_param("iss", $dentist_id, $start_date, $end_date);
$exception_stmt->execute();
$exception_result = $exception_stmt->get_result();

$exceptions = [];
while ($row = $exception_result->fetch_assoc()) {
    $exceptions[$row['exception_date']] = $row;
}

// Build availability data
$availability = [];

// Create date range for the requested period
$period_start = new DateTime($start_date);
$period_end = new DateTime($end_date);
$interval = new DateInterval('P1D');
$date_range = new DatePeriod($period_start, $interval, $period_end);

// Check availability for each day
foreach ($date_range as $date) {
    $date_str = $date->format('Y-m-d');
    $day_of_week = $date->format('w'); // 0 (Sunday) through 6 (Saturday)
    
    $day_availability = [
        'date' => $date_str,
        'available' => false,
        'hours' => []
    ];
    
    // Check if this date has an exception
    if (isset($exceptions[$date_str])) {
        $exception = $exceptions[$date_str];
        
        if ($exception['is_available'] && $exception['start_time'] && $exception['end_time']) {
            $day_availability['available'] = true;
            $day_availability['hours'][] = [
                'start' => $exception['start_time'],
                'end' => $exception['end_time']
            ];
        }
    } else {
        // Use regular weekly schedule
        foreach ($weekly_schedule as $schedule) {
            if ($schedule['day_of_week'] == $day_of_week && $schedule['is_available']) {
                $day_availability['available'] = true;
                $day_availability['hours'][] = [
                    'start' => $schedule['start_time'],
                    'end' => $schedule['end_time'],
                    'max_appointments' => $schedule['max_appointments']
                ];
            }
        }
    }
    
    $availability[] = $day_availability;
}

echo json_encode([
    'dentist_id' => $dentist_id,
    'availability' => $availability
]);
?>