<?php
session_start();
include '../database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = trim($_POST['password']);

    // If either field is missing, set error in session and redirect back
    if (empty($username) || empty($password)) {
        $_SESSION['error'] = "Please enter both username and password.";
        header("Location: login.php");
        exit();
    }

    $sql = "SELECT * FROM users WHERE username = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        $_SESSION['error'] = "Database error: " . $conn->error;
        header("Location: login.php");
        exit();
    }

    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if exactly one user was found
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        // Verify the password using the password_verify() function
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect to appropriate dashboard based on role
    if (in_array($user['role'], ['admin', 'staff', 'dentist'])) {
        header("Location: ../dashboard/dashboard.php");
    } else {
        header("Location: ../userdashboard/dashboard.php");
    }
    exit();
        } else {
            $_SESSION['error'] = "Invalid username or password.";
        }
    } else {
        $_SESSION['error'] = "Invalid username or password.";
    }

    $stmt->close();
    $conn->close();

    // Redirect back to login.php with error set in session
    header("Location: login.php");
    exit();
}
