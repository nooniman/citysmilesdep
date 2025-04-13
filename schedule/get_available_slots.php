<?php
include '../database.php';

header('Content-Type: application/json');

// Enable cross-origin requests
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Log for debugging
$debug = false;
function logDebug($message) {
    global $debug;
    if ($debug) {
        file_put_contents(__DIR__ . '/slots_debug.log', date('[Y-m-d H:i:s] ') . $message . PHP_EOL, FILE_APPEND);
    }
}

// Get parameters
$date = $_GET['date'] ?? null;
$dentist_id = isset($_GET['dentist_id']) ? intval($_GET['dentist_id']) : 0;
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;

logDebug("Request parameters: date=$date, dentist_id=$dentist_id, service_id=$service_id");

// Validate inputs
if (empty($date)) {
    echo json_encode(['error' => 'Date is required']);
    exit;
}

if (empty($service_id)) {
    echo json_encode(['error' => 'Service is required']);
    exit;
}

try {
    // Format date and get day of week
    $formatted_date = date('Y-m-d', strtotime($date));
    $day_of_week = date('w', strtotime($formatted_date)); // 0 (Sunday) to 6 (Saturday)
    
    logDebug("Formatted date: $formatted_date, day of week: $day_of_week");
    
    // Check if the date is in the past
    $today = date('Y-m-d');
    if ($formatted_date < $today) {
        echo json_encode([
            'date' => $formatted_date,
            'available' => false,
            'message' => 'Cannot book appointments for past dates',
            'slots' => []
        ]);
        exit;
    }
    
    // Get service duration
    $service_query = "SELECT name, duration_minutes FROM services WHERE services_id = ?";
    $stmt = $conn->prepare($service_query);
    $stmt->bind_param("i", $service_id);
    $stmt->execute();
    $service_result = $stmt->get_result();
    
    if ($service_result->num_rows === 0) {
        echo json_encode([
            'date' => $formatted_date,
            'available' => false,
            'message' => 'Service not found',
            'slots' => []
        ]);
        exit;
    }
    
    $service = $service_result->fetch_assoc();
    $service_duration = $service['duration_minutes'] ?: 60; // Default to 60 minutes if not specified
    $service_name = $service['name'];
    
    logDebug("Service: $service_name, Duration: $service_duration minutes");
    
    // Initialize schedule variables
    $schedule = null;
    $is_available = false;
    
    // STEP 1: Check for schedule exception first
    $has_exception = false;
    
    // Determine which column to use for dentist identification
    $column_check = $conn->query("SHOW COLUMNS FROM schedule_exceptions LIKE 'user_id'");
    $dentist_id_column = ($column_check->num_rows > 0) ? 'user_id' : 'dentist_id';
    
    logDebug("Schedule exceptions using column: $dentist_id_column");
    
    if ($dentist_id > 0) {
        $exception_query = "SELECT * FROM schedule_exceptions WHERE $dentist_id_column = ? AND exception_date = ?";
        $exception_stmt = $conn->prepare($exception_query);
        $exception_stmt->bind_param("is", $dentist_id, $formatted_date);
        $exception_stmt->execute();
        $exception_result = $exception_stmt->get_result();
        
        if ($exception_result->num_rows > 0) {
            $has_exception = true;
            $exception = $exception_result->fetch_assoc();
            
            logDebug("Found exception for date $formatted_date: " . json_encode($exception));
            
            // If dentist is not available on this day, return no slots
            if ($exception['is_available'] == 0) {
                echo json_encode([
                    'date' => $formatted_date,
                    'available' => false,
                    'message' => 'Dentist is not available on this date',
                    'slots' => []
                ]);
                exit;
            }
            
            // Use the exception's custom hours
            $schedule = [
                'start_time' => $exception['start_time'],
                'end_time' => $exception['end_time'],
                'max_appointments' => 1 // Default for exceptions
            ];
            $is_available = true;
        }
    }
    
    // STEP 2: If no exception found, check regular schedule
    if (!$has_exception) {
        // Determine which column to use for dentist identification in availability table
        $column_check = $conn->query("SHOW COLUMNS FROM dentist_availability LIKE 'user_id'");
        $dentist_id_column = ($column_check->num_rows > 0) ? 'user_id' : 'dentist_id';
        
        logDebug("Dentist availability using column: $dentist_id_column");
        
        $schedule_query = "SELECT start_time, end_time, max_appointments, is_available 
                          FROM dentist_availability 
                          WHERE $dentist_id_column = ? AND day_of_week = ?";
        
        $schedule_stmt = $conn->prepare($schedule_query);
        $schedule_stmt->bind_param("ii", $dentist_id, $day_of_week);
        $schedule_stmt->execute();
        $schedule_result = $schedule_stmt->get_result();
        
        if ($schedule_result->num_rows === 0) {
            echo json_encode([
                'date' => $formatted_date,
                'available' => false,
                'message' => 'No schedule found for this day',
                'slots' => []
            ]);
            exit;
        }
        
        $schedule_row = $schedule_result->fetch_assoc();
        
        // Check if dentist is available on this day in their regular schedule
        if ($schedule_row['is_available'] == 0) {
            echo json_encode([
                'date' => $formatted_date,
                'available' => false,
                'message' => 'Dentist is not scheduled on this day',
                'slots' => []
            ]);
            exit;
        }
        
        $schedule = $schedule_row;
        $is_available = true;
        
        logDebug("Regular schedule for day $day_of_week: " . json_encode($schedule));
    }
    
    // STEP 3: Check for existing appointments to find conflicts
    $existing_appointments = [];
    
    if ($dentist_id > 0) {
        // Determine which column to use for dentist in appointments
        $column_check = $conn->query("SHOW COLUMNS FROM appointments LIKE 'staff_id'");
        $dentist_appointment_column = ($column_check->num_rows > 0) ? 'staff_id' : 'dentist_id';
        
        logDebug("Appointments using column: $dentist_appointment_column");
        
        $appointments_query = "SELECT appointment_time, s.duration_minutes 
                              FROM appointments a
                              JOIN services s ON a.service_id = s.services_id
                              WHERE $dentist_appointment_column = ? 
                              AND appointment_date = ? 
                              AND status NOT IN ('cancelled', 'declined')";
        
        $appointments_stmt = $conn->prepare($appointments_query);
        $appointments_stmt->bind_param("is", $dentist_id, $formatted_date);
        $appointments_stmt->execute();
        $appointments_result = $appointments_stmt->get_result();
        
        while ($appt = $appointments_result->fetch_assoc()) {
            $start_time = strtotime($appt['appointment_time']);
            $duration = $appt['duration_minutes'] ?: 60; // Default if missing
            $end_time = $start_time + ($duration * 60);
            
            $existing_appointments[] = [
                'start' => $start_time,
                'end' => $end_time
            ];
        }
        
        logDebug("Found " . count($existing_appointments) . " existing appointments");
    }
    
    // STEP 4: Generate available time slots
    $slots = [];
    
    if ($is_available && $schedule) {
        $start_time = strtotime($schedule['start_time']);
        $end_time = strtotime($schedule['end_time']);
        $interval = 30 * 60; // 30 minutes in seconds
        $service_duration_seconds = $service_duration * 60;
        
        // For each potential slot start time
        for ($time = $start_time; $time <= $end_time - $service_duration_seconds; $time += $interval) {
            $slot_end = $time + $service_duration_seconds;
            $slot_available = true;
            
            // Check if this slot conflicts with any existing appointment
            foreach ($existing_appointments as $appt) {
                // If there's any overlap between the slot and appointment
                if ($time < $appt['end'] && $slot_end > $appt['start']) {
                    $slot_available = false;
                    break;
                }
            }
            
            if ($slot_available) {
                $slots[] = [
                    'time' => date('H:i', $time),
                    'formatted_time' => date('g:i A', $time),
                    'end_time' => date('H:i', $slot_end),
                    'formatted_end_time' => date('g:i A', $slot_end)
                ];
            }
        }
        
        logDebug("Generated " . count($slots) . " available slots");
    }
    
    // Return available slots
    echo json_encode([
        'date' => $formatted_date,
        'available' => count($slots) > 0,
        'service' => [
            'id' => $service_id,
            'name' => $service_name,
            'duration' => $service_duration
        ],
        'slots' => $slots
    ]);
    
} catch (Exception $e) {
    logDebug("ERROR: " . $e->getMessage());
    echo json_encode([
        'error' => 'Server error',
        'message' => $e->getMessage()
    ]);
}
?>