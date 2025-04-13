<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\schedule\get_weekly_tabs.php
// Basic error handling to prevent 500 errors
ini_set('display_errors', 0);
error_reporting(0);

// Set proper content type header
header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

// Get dentist ID
$dentist_id = isset($_GET['dentist_id']) ? intval($_GET['dentist_id']) : 0;

// Validate dentist ID
if ($dentist_id == 0) {
  echo json_encode(array('error' => 'No dentist selected'));
  exit;
}

try {
  // FIXED: Removed exception_date IS NULL since column doesn't exist
  $query = "SELECT 
                availability_id,
                dentist_id,
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
                day_of_week";

  $stmt = $conn->prepare($query);

  // If prepare fails
  if (!$stmt) {
    throw new Exception("Database prepare error: " . $conn->error);
  }

  $stmt->bind_param('i', $dentist_id);
  $success = $stmt->execute();

  // If execution fails
  if (!$success) {
    throw new Exception("Query execution failed: " . $stmt->error);
  }

  $result = $stmt->get_result();
  $data = array();

  // Fetch data
  while ($row = $result->fetch_assoc()) {
    // Ensure 'is_available' has a default value if missing
    $row['is_available'] = isset($row['is_available']) ? (int) $row['is_available'] : 0;
    $data[] = array(
      'availability_id' => $row['availability_id'],
      'day_of_week' => $row['day_of_week'],
      'start_time' => $row['start_time'],
      'end_time' => $row['end_time'],
      'is_available' => $row['is_available'],
      'max_appointments' => $row['max_appointments'],
      'start_time_formatted' => $row['start_time_formatted'],
      'end_time_formatted' => $row['end_time_formatted']
    );
  }

  $stmt->close();
  echo json_encode($data);

} catch (Exception $e) {
  http_response_code(500); // Set HTTP status code for server error
  echo json_encode(['error' => 'An error occurred while fetching weekly tabs: ' . $e->getMessage()]);
  exit;
}
?>