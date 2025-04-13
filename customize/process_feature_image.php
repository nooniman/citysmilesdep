<?php
session_start();
require_once '../database.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header('HTTP/1.1 403 Forbidden');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $target_dir = "../images/features/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $target_file = $target_dir . basename($_FILES["feature_image"]["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

    // Check if image file is valid
    $check = getimagesize($_FILES["feature_image"]["tmp_name"]);
    if ($check === false) {
        die("File is not an image.");
    }

    // Generate unique filename
    $new_filename = "feature_" . uniqid() . "." . $imageFileType;
    $target_path = $target_dir . $new_filename;

    if (move_uploaded_file($_FILES["feature_image"]["tmp_name"], $target_path)) {
        // Update database
        $conn->query("UPDATE clinic_info SET feature_image = '$target_path' LIMIT 1");
        header("Location: customize.php?success=Feature+image+updated");
    } else {
        header("Location: customize.php?error=Upload+failed");
    }
    exit();
}