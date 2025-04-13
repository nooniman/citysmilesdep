<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\schedule\setup_database.php
require_once '../database.php';

// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Setting Up Appointment System Database</h1>";

// Check if dentist_availability table exists
$table_check = $conn->query("SHOW TABLES LIKE 'dentist_availability'");
if ($table_check->num_rows === 0) {
    echo "<p>Creating dentist_availability table...</p>";
    
    $create_table = "CREATE TABLE IF NOT EXISTS `dentist_availability` (
        `availability_id` int(11) NOT NULL AUTO_INCREMENT,
        `user_id` int(11) NOT NULL,
        `day_of_week` int(1) NOT NULL,
        `start_time` time NOT NULL,
        `end_time` time NOT NULL,
        `max_appointments` int(11) DEFAULT 1,
        `is_available` tinyint(1) DEFAULT 1,
        `created_at` timestamp NULL DEFAULT current_timestamp(),
        `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
        PRIMARY KEY (`availability_id`)
    )";
    
    if ($conn->query($create_table)) {
        echo "<p>Table created successfully</p>";
    } else {
        echo "<p>Error creating table: " . $conn->error . "</p>";
    }
}

// Get all dentists
$dentists_query = "SELECT id, first_name, last_name FROM users WHERE role = 'dentist'";
$dentists_result = $conn->query($dentists_query);

if ($dentists_result->num_rows === 0) {
    echo "<p><strong>Warning:</strong> No dentists found in the database. Please add users with role='dentist'.</p>";
} else {
    echo "<p>Found " . $dentists_result->num_rows . " dentists</p>";
    
    // Check if there's any data in dentist_availability
    $avail_check = $conn->query("SELECT COUNT(*) as count FROM dentist_availability");
    $avail_count = $avail_check->fetch_assoc()['count'];
    
    if ($avail_count == 0) {
        echo "<h2>Adding Sample Availability</h2>";
        
        // Add availability for each dentist
        while ($dentist = $dentists_result->fetch_assoc()) {
            $dentist_id = $dentist['id'];
            $dentist_name = $dentist['first_name'] . ' ' . $dentist['last_name'];
            
            echo "<p>Adding availability for Dr. $dentist_name (ID: $dentist_id)</p>";
            
            // Add all days of the week (0-6)
            for ($day = 0; $day <= 6; $day++) {
                $start_time = '09:00:00';
                $end_time = '17:00:00';
                $max_appointments = 2;
                
                // Skip weekends (0 = Sunday, 6 = Saturday)
                $is_available = ($day > 0 && $day < 6) ? 1 : 0;
                
                $insert_query = "INSERT INTO dentist_availability 
                                (user_id, day_of_week, start_time, end_time, max_appointments, is_available)
                                VALUES (?, ?, ?, ?, ?, ?)";
                
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param('iissii', $dentist_id, $day, $start_time, $end_time, $max_appointments, $is_available);
                
                if ($stmt->execute()) {
                    $day_name = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'][$day];
                    echo "<p>- Added $day_name schedule: $start_time - $end_time" . ($is_available ? " (Available)" : " (Unavailable)") . "</p>";
                } else {
                    echo "<p>- Error adding schedule: " . $stmt->error . "</p>";
                }
            }
        }
    } else {
        echo "<p>Availability data already exists: $avail_count records</p>";
    }
}

echo "<h2>Database Setup Complete</h2>";
echo "<p><a href='../userdashboard/appointment.php' class='btn btn-primary'>Go to Appointment Page</a></p>";
?>