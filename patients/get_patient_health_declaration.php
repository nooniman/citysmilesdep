<?php
include '../database.php';

header('Content-Type: application/json');

if (!isset($_GET['patient_info_id'])) {
    echo json_encode(['error' => 'No patient ID provided']);
    exit;
}

$patient_info_id = intval($_GET['patient_info_id']);

$query = "SELECT * FROM patient_health_declaration WHERE patient_info_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patient_info_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo json_encode($result->fetch_assoc());
} else {
    echo json_encode(['error' => 'No health declaration found']);
}
?>
