<?php
include '../database.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        // Set upload directory (make sure this directory exists or is created)
        $uploadDir = '../website/image/';

        // Create the directory if it does not exist
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $filename = basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $filename;

        // Optionally, add validations for file types and size here
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            // Use a relative path to the image for the database
            $imagePath = "image/" . $filename;
        } else {
            echo "Failed to move uploaded file.";
            exit;
        }
    } else {
        echo "No valid image file uploaded.";
        exit;
    }

    // Prepare SQL statement
    $stmt = $conn->prepare("INSERT INTO services (name, description, image_path) VALUES (?, ?, ?)");
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("sss", $name, $description, $imagePath);

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: customize.php?success=Service added successfully!");
        exit();
    } else {
        $stmt->close();
        header("Location: customize.php?error=Error adding service: " . $stmt->error);
        exit();
    }
} else {
    header("Location: customize.php");
    exit();
}
?>