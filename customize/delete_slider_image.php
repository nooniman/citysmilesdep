<?php
include '../database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id = $_POST['id'];

    // Fetch the image path from the database
    $stmt = $conn->prepare("SELECT image_path FROM slider_images WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($imagePath);
    $stmt->fetch();
    $stmt->close();

    // Delete the image file from the server
    if (file_exists($imagePath)) {
        unlink($imagePath);
    }

    // Delete the image info from the database
    $stmt = $conn->prepare("DELETE FROM slider_images WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success', 'message' => 'Slider image deleted successfully!']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Error deleting slider image: ' . $stmt->error]);
    }
    exit();
}
?>