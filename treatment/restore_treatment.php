<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\treatment\restore_treatment.php

session_start();
include_once '../database.php';

// Check access permissions
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['treatment_id'])) {
    $treatment_id = intval($_POST['treatment_id']);

    // Restore the treatment record by clearing is_deleted flag and deleted_at timestamp
    $stmt = $conn->prepare("UPDATE treatments SET is_deleted = 0, deleted_at = NULL WHERE treatment_id = ?");
    $stmt->bind_param("i", $treatment_id);

    if ($stmt->execute()) {
        $response = [
            'success' => true,
            'message' => 'Treatment restored successfully'
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Error restoring treatment: ' . $conn->error
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>