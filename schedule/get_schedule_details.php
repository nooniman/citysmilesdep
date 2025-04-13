<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\schedule\get_schedule_details.php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

header('Content-Type: application/json');

$availability_id = isset($_GET['availability_id']) ? intval($_GET['availability_id']) : 0;

if ($availability_id <= 0) {
    echo json_encode(['error' => 'Invalid availability ID']);
    exit;
}

$sql = "SELECT * FROM dentist_availability WHERE availability_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param('i', $availability_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'Schedule not found']);
    exit;
}

$row = $result->fetch_assoc();
echo json_encode($row);
?>