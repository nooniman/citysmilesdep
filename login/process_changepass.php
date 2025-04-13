<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
include '../database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $current_password = trim($_POST['current_password']);
    $new_password     = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    
    if ($new_password !== $confirm_password) {
        echo "New passwords do not match. <a href='changepass.php'>Try again</a>";
        exit();
    }
    
    // Get current user data
    $sql = "SELECT password FROM users WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $_SESSION['user_id']);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    if ($stmt->fetch()) {
        if (!password_verify($current_password, $hashed_password)) {
            echo "Current password is incorrect. <a href='changepass.php'>Try again</a>";
            exit();
        }
    } else {
        echo "User not found. <a href='changepass.php'>Try again</a>";
        exit();
    }
    $stmt->close();
    
    // Update password
    $new_hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    $sql = "UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("si", $new_hashed_password, $_SESSION['user_id']);
    if ($stmt->execute()) {
        echo "Password changed successfully. <a href='login.php'>Login</a>";
    } else {
        echo "Error: " . $stmt->error;
    }
    $stmt->close();
} else {
    header("Location: changepass.php");
    exit();
}
?>