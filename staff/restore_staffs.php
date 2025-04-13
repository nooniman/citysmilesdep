<?php
session_start();
include_once '../database.php';

// Check access permissions
if (
    !isset($_SESSION['user_id']) || !isset($_SESSION['role']) ||
    $_SESSION['role'] !== 'admin'
) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['id'])) {
    $staff_id = intval($_POST['id']);

    // Restore the staff record by clearing the is_deleted flag and deleted_at timestamp
    $stmt = $conn->prepare("UPDATE users SET is_deleted = 0, deleted_at = NULL WHERE id = ?");
    $stmt->bind_param("i", $staff_id);

    if ($stmt->execute()) {
        $response = [
            'success' => true,
            'message' => 'Staff member restored successfully'
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Error restoring staff member: ' . $conn->error
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>