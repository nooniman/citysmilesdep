<?php
include '../database.php';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_POST['id']) || empty($_POST['id'])) {
        error_log('Error: Service ID is missing or empty.'); // Debugging: Log missing ID
        echo json_encode(['status' => 'error', 'message' => 'Invalid service ID.']);
        exit();
    }

    $id = intval($_POST['id']); // Ensure the ID is an integer
    error_log("Received service ID for deletion: $id"); // Debugging: Log the received ID

    // Check for related appointments
    $checkQuery = $conn->prepare("SELECT COUNT(*) FROM appointments WHERE service_id = ?");
    if (!$checkQuery) {
        error_log('Error: Failed to prepare appointment check query.'); // Debugging
        echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
        exit();
    }
    $checkQuery->bind_param("i", $id);
    $checkQuery->execute();
    $checkQuery->bind_result($count);
    $checkQuery->fetch();
    $checkQuery->close();

    error_log("Related appointments count for service ID $id: $count"); // Debugging

    if ($count > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Cannot delete service. Conflict with appointments.']);
        exit();
    } else {
        // Delete the service from the database
        $stmt = $conn->prepare("DELETE FROM services WHERE services_id = ?");
        if (!$stmt) {
            error_log('Error: Failed to prepare delete query.'); // Debugging
            echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
            exit();
        }
        $stmt->bind_param("i", $id);
        $stmt->execute();

        error_log("Affected rows after delete query: " . $stmt->affected_rows); // Debugging

        if ($stmt->affected_rows > 0) {
            echo json_encode(['status' => 'success', 'message' => 'Service deleted successfully.']);
        } else {
            // Debugging: Check if the service ID exists
            $checkServiceQuery = $conn->prepare("SELECT COUNT(*) FROM services WHERE services_id = ?");
            $checkServiceQuery->bind_param("i", $id);
            $checkServiceQuery->execute();
            $checkServiceQuery->bind_result($serviceExists);
            $checkServiceQuery->fetch();
            $checkServiceQuery->close();

            error_log("Service existence check for ID $id: $serviceExists"); // Debugging

            if ($serviceExists > 0) {
                echo json_encode(['status' => 'error', 'message' => 'Failed to delete service due to an unknown error.']);
            } else {
                echo json_encode(['status' => 'error', 'message' => 'Service does not exist.']);
            }
        }
        $stmt->close();
        exit();
    }
} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
    exit();
}
?>