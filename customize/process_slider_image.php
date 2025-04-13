<?php
include '../database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $image = $_FILES['image'];

    // Validate and upload the image
    if ($image['error'] == 0) {
        $targetDir = "../images/";
        $targetFile = $targetDir . basename($image['name']);
        if (move_uploaded_file($image['tmp_name'], $targetFile)) {
            // Insert image info into the database
            $stmt = $conn->prepare("INSERT INTO slider_images (image_path) VALUES (?)");
            $stmt->bind_param("s", $targetFile);
            if ($stmt->execute()) {
                header("Location: customize.php?success=Slider image added successfully!");
                exit();
            } else {
                echo "Error: " . $stmt->error;
            }
        } else {
            echo "Error uploading the file.";
        }
    } else {
        echo "Error: " . $image['error'];
    }
}
?>