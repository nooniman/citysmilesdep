<?php
error_log("Treatment soft delete requested");
error_log("POST data: " . print_r($_POST, true));

session_start();
include_once '../database.php';
include_once '../includes/soft_delete.php';



// Check access permissions
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['treatment_id'])) {
    $treatment_id = intval($_POST['treatment_id']);

    // Use softDeleteTreatment function from soft_delete.php
    $result = softDeleteTreatment($conn, $treatment_id);

    if ($result['success']) {
        $response = [
            'success' => true,
            'message' => 'Treatment removed successfully'
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Error removing treatment: ' . $result['message']
        ];
    }
}

header('Content-Type: application/json');
echo json_encode($response);
exit;
?>