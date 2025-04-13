<?php
session_start();
include '../database.php'; // Ensure you have a database connection file

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['role'];
    $last_name = $_POST['last_name'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'] ?? '';
    $gender = $_POST['gender'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $image_path = '';

    // Generate a random username based on first and last name
    function generateUsername($first, $last, $conn)
    {
        $base_username = strtolower($first[0] . $last);
        $username = $base_username;
        $counter = 1;
        while (true) {
            $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows === 0) {
                $stmt->close();
                return $username;
            }
            $stmt->close();
            $username = $base_username . $counter;
            $counter++;
        }
    }
    $username = generateUsername($first_name, $last_name, $conn);

    // Password confirmation check
    if ($password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match';
        header('Location: staffs.php'); // Redirect back to form
        exit();
    }

    // Hash the password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    // Handle file upload
    if (!empty($_FILES['staff_image']['name'])) {
        $target_dir = "uploads/"; // Ensure this folder exists and is writable
        $image_name = time() . '_' . basename($_FILES['staff_image']['name']);
        $target_file = $target_dir . $image_name;

        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES['staff_image']['tmp_name'], $target_file)) {
                $image_path = $target_file;
            } else {
                $_SESSION['error'] = 'Failed to upload image';
                header('Location: staffs.php');
                exit();
            }
        } else {
            $_SESSION['error'] = 'Invalid image format';
            header('Location: staffs.php');
            exit();
        }
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO users (role, last_name, first_name, middle_name, username, gender, contact, email, password, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssss", $role, $last_name, $first_name, $middle_name, $username, $gender, $contact_number, $email, $hashed_password, $image_path);

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Staff added successfully';
    } else {
        $_SESSION['error'] = 'Failed to add staff';
    }

    $stmt->close();
    $conn->close();

    header('Location: staffs.php'); // Redirect to staff list page
    exit();
} else {
    header('Location: staffs.php');
    exit();
}
