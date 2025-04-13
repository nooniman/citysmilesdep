<?php
session_start();
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff', 'dentist'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

include '../database.php';
include '../includes/soft_delete.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $result = softDeleteAppointment($conn, $id);
    echo json_encode($result);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
?>