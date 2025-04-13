<?php
include '../database.php';

header('Content-Type: application/json');

// Get parameters
$start = $_GET['start'] ?? date('Y-m-d');
$end = $_GET['end'] ?? date('Y-m-d', strtotime('+3 months'));
$dentist_id = !empty($_GET['dentist_id']) ? (int)$_GET['dentist_id'] : 0;
$service_id = !empty($_GET['service_id']) ? (int)$_GET['service_id'] : 0;

// Enable debugging if needed (set to true to enable)
$debug = false;
$log_file = __DIR__ . '/dates_debug.log';

function debug_log($message) {
    global $debug, $log_file;
    if ($debug) {
        file_put_contents($log_file, date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
    }
}

debug_log("GET request: " . json_encode($_GET));
debug_log("Processing: start=$start, end=$end, dentist_id=$dentist_id, service_id=$service_id");

try {
    // Create date range
    $start_date = new DateTime($start);
    $end_date = new DateTime($end);
    $period = new DatePeriod(
        $start_date,
        new DateInterval('P1D'),
        $end_date->modify('+1 day')
    );
    
    $events = [];
    $today = new DateTime();
    
    // If dentist is specified, get their regular schedule
    $available_days = [];
    if ($dentist_id > 0) {
        $schedule_query = "SELECT day_of_week FROM dentist_availability 
                           WHERE dentist_id = ? AND is_available = 1";
        $stmt = $conn->prepare($schedule_query);
        $stmt->bind_param("i", $dentist_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $available_days[] = $row['day_of_week'];
        }
        
        debug_log("Available days: " . implode(",", $available_days));
    } else {
        // If no dentist specified, assume all weekdays are available
        $available_days = [1, 2, 3, 4, 5]; // Monday through Friday
    }
    
    // Get schedule exceptions (days marked as unavailable or with custom hours)
    $exceptions = [];
    if ($dentist_id > 0) {
        $exception_query = "SELECT exception_date, is_available FROM schedule_exceptions 
                           WHERE dentist_id = ? AND exception_date BETWEEN ? AND ?";
        $stmt = $conn->prepare($exception_query);
        $stmt->bind_param("iss", $dentist_id, $start, $end);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $exceptions[$row['exception_date']] = $row['is_available'];
        }
    }
    
    // Check each day in the date range
    $available_dates = [];
    foreach ($period as $date) {
        $date_string = $date->format('Y-m-d');
        $day_of_week = $date->format('w'); // 0 (Sunday) to 6 (Saturday)
        $is_past = $date < $today;
        
        // Skip past dates
        if ($is_past) {
            continue;
        }
        
        // Check if this date has an exception
        if (isset($exceptions[$date_string])) {
            $is_available = (bool)$exceptions[$date_string];
        } else {
            // Otherwise, check if this day of week is in the regular schedule
            $is_available = in_array($day_of_week, $available_days);
        }
        
        // Add this date to the events array
        $events[] = [
            'date' => $date_string,
            'available' => $is_available,
            'title' => $is_available ? 'Available' : 'Not Available'
        ];
        
        if ($is_available) {
            $available_dates[] = $date_string;
        }
    }
    
    debug_log("Found " . count($available_dates) . " available dates");
    echo json_encode($events);
    
} catch (Exception $e) {
    debug_log("ERROR: " . $e->getMessage());
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
?>