<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\schedule\debug_slots.php
// Simple script to test the availability API directly

// Include database connection
require_once '../database.php';

// Set headers for plain text output
header('Content-Type: text/plain');

echo "=== SLOT AVAILABILITY DEBUG ===\n\n";

// Test parameters
$date = $_GET['date'] ?? date('Y-m-d');
$service_id = $_GET['service_id'] ?? 1;
$dentist_id = $_GET['dentist_id'] ?? '';

echo "Testing with:\n";
echo "Date: $date\n";
echo "Service ID: $service_id\n";
echo "Dentist ID: $dentist_id\n\n";

// Get day of week
$day_of_week = date('w', strtotime($date));
$day_names = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
echo "Day of week: {$day_names[$day_of_week]} ($day_of_week)\n\n";

// Verify dentist_availability table
$table_check = $conn->query("SHOW TABLES LIKE 'dentist_availability'");
echo "dentist_availability table exists: " . ($table_check->num_rows > 0 ? "YES" : "NO") . "\n";

if ($table_check->num_rows > 0) {
    $count_query = "SELECT COUNT(*) as count FROM dentist_availability";
    $count_result = $conn->query($count_query);
    $count = $count_result->fetch_assoc()['count'];
    echo "Total records in dentist_availability: $count\n\n";
    
    // Check for specific day
    $day_query = "SELECT COUNT(*) as count FROM dentist_availability WHERE day_of_week = $day_of_week";
    $day_result = $conn->query($day_query);
    $day_count = $day_result->fetch_assoc()['count'];
    echo "Records for {$day_names[$day_of_week]}: $day_count\n\n";
    
    // Show sample data
    echo "Sample data:\n";
    $sample_query = "SELECT da.*, u.first_name, u.last_name 
                    FROM dentist_availability da 
                    JOIN users u ON da.user_id = u.id 
                    WHERE da.day_of_week = $day_of_week 
                    LIMIT 5";
    $sample_result = $conn->query($sample_query);
    
    if ($sample_result->num_rows > 0) {
        while ($row = $sample_result->fetch_assoc()) {
            echo "- Dentist: Dr. {$row['first_name']} {$row['last_name']} (ID: {$row['user_id']})\n";
            echo "  Hours: {$row['start_time']} - {$row['end_time']}\n";
            echo "  Max appointments: {$row['max_appointments']}\n";
            echo "  Available: " . ($row['is_available'] ? "Yes" : "No") . "\n\n";
        }
    } else {
        echo "No dentists available on {$day_names[$day_of_week]}\n\n";
    }
}

echo "=== MANUAL SLOT CALCULATION ===\n\n";

// Simple check to see if we can manually calculate slots
$query = "SELECT da.user_id, da.start_time, da.end_time, da.max_appointments,
          u.first_name, u.last_name
          FROM dentist_availability da
          JOIN users u ON da.user_id = u.id
          WHERE da.day_of_week = $day_of_week AND da.is_available = 1";

if (!empty($dentist_id)) {
    $query .= " AND da.user_id = $dentist_id";
}

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        echo "Dentist: Dr. {$row['first_name']} {$row['last_name']} (ID: {$row['user_id']})\n";
        echo "Hours: {$row['start_time']} - {$row['end_time']}\n";
        
        $start = strtotime($row['start_time']);
        $end = strtotime($row['end_time']);
        
        echo "Available slots (30 min intervals):\n";
        for ($time = $start; $time < $end - 1800; $time += 1800) {
            echo "  - " . date('h:i A', $time) . "\n";
        }
        echo "\n";
    }
} else {
    echo "No dentists available for this day.\n\n";
}

echo "=== END OF DEBUG ===\n";
?>