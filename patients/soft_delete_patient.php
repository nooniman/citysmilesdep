<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\patients\soft_delete_patient.php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff', 'dentist'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include '../database.php';
include '../includes/soft_delete.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    // Extract numeric ID from string like "P-00001"
    $id = intval(preg_replace('/[^0-9]/', '', $_POST['id']));
    $result = softDeletePatient($conn, $id);
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>