<?php
include '../database.php';

header('Content-Type: application/json');

if (!isset($_GET['patient_info_id'])) {
    echo json_encode([]);
    exit;
}

$patientInfoId = intval($_GET['patient_info_id']);
$query = "SELECT id, TO_BASE64(xray_image) AS xray_image FROM patient_history WHERE patient_info_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $patientInfoId);
$stmt->execute();
$result = $stmt->get_result();

$images = [];
while ($row = $result->fetch_assoc()) {
    $images[] = $row;
}

echo json_encode($images);
?>
