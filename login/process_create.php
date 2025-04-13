<?php
session_start();
include '../database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = trim($_POST['first_name']);
    $middle_name = trim($_POST['middle_name']);
    $last_name = trim($_POST['last_name']);
    $username = $first_name . "." . $last_name; // Generate username if not provided
    $contact = trim($_POST['contact-number']); // Note the dash in form field name
    $email = trim($_POST['email']);
    $gender = $_POST['gender'];
    $birth_date = $_POST['birthdate']; // Was missing
    $password = trim($_POST['password']);
    $confirm_pass = trim($_POST['confirm_password']);

    if ($password !== $confirm_pass) {
        echo "Passwords do not match. <a href='create.php'>Try again</a>";
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Set default role as 'patient'
    $role = 'patient';

    $sql = "INSERT INTO users (first_name, last_name, middle_name, username, email, password, contact, gender, birthdate, role, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("ssssssssss", $first_name, $last_name, $middle_name, $username, $email, $hashedPassword, $contact, $gender, $birth_date, $role);

    if ($stmt->execute()) {
        header("Location: login.php");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
} else {
    header("Location: create.php");
    exit();
}
?>