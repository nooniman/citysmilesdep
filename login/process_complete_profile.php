<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include_once '../database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $birth_date = $_POST['birth_date'];
    $contact = $_POST['contact'];
    $address = $_POST['address'];
    $gender = $_POST['gender'];
    $userId = $_SESSION['user_id'];
    
    // Update user profile
    $sql = "UPDATE users SET 
            birthdate = ?, 
            contact = ?, 
            address = ?, 
            gender = ?, 
            updated_at = NOW() 
            WHERE id = ?";
            
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ssssi", $birth_date, $contact, $address, $gender, $userId);
    
    if ($stmt->execute()) {
        // Redirect based on role
        if ($_SESSION['role'] === 'admin') {
            header("Location: ../dashboard/dashboard.php");
        } else {
            // Assuming you have a user dashboard
            header("Location: ../dashboard/dashboard.php");
        }
        exit();
    } else {
        echo "Error updating profile: " . $stmt->error;
    }
} else {
    header("Location: complete_profile.php");
    exit();
}
?>