<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\schedule\delete_exception.php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['exception_id'])) {
    header('Location: schedule.php');
    exit;
}

$exception_id = intval($_POST['exception_id']);

// Get dentist_id for redirect
$sql = "SELECT dentist_id FROM schedule_exceptions WHERE exception_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $exception_id);
$stmt->execute();
$result = $stmt->get_result();
$dentist_id = 0;

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $dentist_id = $row['dentist_id'];
}

// Delete exception
$sql = "DELETE FROM schedule_exceptions WHERE exception_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $exception_id);

if ($stmt->execute()) {
    $_SESSION['schedule_message'] = "Exception deleted successfully.";
} else {
    $_SESSION['schedule_error'] = "Error deleting exception: " . $stmt->error;
}

header('Location: schedule.php' . ($dentist_id ? '?dentist_id=' . $dentist_id : ''));
exit;
?>