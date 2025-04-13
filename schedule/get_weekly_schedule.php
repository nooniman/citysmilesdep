<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\schedule\get_weekly_schedule.php
// Basic error handling
ini_set('display_errors', 0);
error_reporting(0);

// Include required files
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

// Get dentist ID
$dentist_id = isset($_GET['dentist_id']) ? intval($_GET['dentist_id']) : 0;

// Validate dentist ID
if ($dentist_id == 0) {
    echo '<tr><td colspan="3" class="text-center">No dentist selected</td></tr>';
    exit;
}

try {
    // Get weekly schedules - Using dentist_id column instead of user_id
    $query = "SELECT 
                availability_id,
                day_of_week, 
                TIME_FORMAT(start_time, '%h:%i %p') AS start_time_formatted,
                TIME_FORMAT(end_time, '%h:%i %p') AS end_time_formatted,
                start_time, 
                end_time,
                is_available,
                max_appointments
              FROM 
                dentist_availability
              WHERE 
                dentist_id = ? 
              ORDER BY 
                FIELD(day_of_week, 1, 2, 3, 4, 5, 6, 0)"; // Sort by weekday order

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception("Database prepare error: " . $conn->error);
    }

    $stmt->bind_param('i', $dentist_id);
    $success = $stmt->execute();

    if (!$success) {
        throw new Exception("Query execution failed: " . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        echo '<tr><td colspan="3" class="text-center">No schedules found for this dentist</td></tr>';
    } else {
        $days = array(
            0 => 'Sunday',
            1 => 'Monday',
            2 => 'Tuesday',
            3 => 'Wednesday',
            4 => 'Thursday',
            5 => 'Friday',
            6 => 'Saturday'
        );

        while ($row = $result->fetch_assoc()) {
            // Ensure 'is_available' has a default value if missing
            $row['is_available'] = isset($row['is_available']) ? (int) $row['is_available'] : 0;
            $day = isset($days[$row['day_of_week']]) ? $days[$row['day_of_week']] : 'Unknown';
            $status = $row['is_available'] ?
                '<span class="status available">Available</span>' :
                '<span class="status unavailable">Not Available</span>';

            echo '<tr>';
            echo '<td>' . htmlspecialchars($day) . '</td>';
            echo '<td>' . htmlspecialchars($row['start_time_formatted']) . ' - ' . htmlspecialchars($row['end_time_formatted']) .
                '<br>Max: ' . htmlspecialchars($row['max_appointments']) . ' ' . ($row['max_appointments'] > 1 ? 'appointments' : 'appointment') .
                '<br>' . $status . '</td>';
            echo '<td>';
            echo '<button class="btn-action btn-edit" onclick="openEditScheduleModal(' . $row['availability_id'] . ')">';
            echo '<i class="fas fa-edit"></i> Edit</button>';
            echo '</td>';
            echo '</tr>';
        }
    }

    $stmt->close();

} catch (Exception $e) {
    echo '<tr><td colspan="3" class="text-danger">Error: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
    exit;
}
?>