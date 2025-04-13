<?php
session_start();
include_once '../database.php';
include_once '../includes/soft_delete.php';

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

    // Use softDeleteStaff function from soft_delete.php
    $result = softDeleteStaff($conn, $staff_id);

    if ($result['success']) {
        $response = [
            'success' => true,
            'message' => 'Staff member removed successfully'
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Error removing staff member: ' . $result['message']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>