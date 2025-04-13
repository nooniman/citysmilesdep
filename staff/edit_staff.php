<?php
session_start();
include '../database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $role = $_POST['role'];
    $last_name = $_POST['last_name'];
    $first_name = $_POST['first_name'];
    $middle_name = $_POST['middle_name'] ?? '';
    $gender = $_POST['gender'];
    $contact_number = $_POST['contact_number'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Check if passwords match if they're being changed
    if (!empty($password) && $password !== $confirm_password) {
        $_SESSION['error'] = 'Passwords do not match';
        header('Location: staffs.php');
        exit();
    }

    // Handle file upload
    $image_path = '';
    if (!empty($_FILES['staff_image']['name'])) {
        $target_dir = "uploads/";
        $image_name = time() . '_' . basename($_FILES['staff_image']['name']);
        $target_file = $target_dir . $image_name;

        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
        $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

        if (in_array($imageFileType, $allowed_types)) {
            if (move_uploaded_file($_FILES['staff_image']['tmp_name'], $target_file)) {
                $image_path = $target_file;
                // Delete old image if it exists
                $stmt = $conn->prepare("SELECT image FROM users WHERE id = ?");
                $stmt->bind_param("i", $id);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                if (!empty($row['image']) && file_exists($row['image'])) {
                    unlink($row['image']);
                }
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

    // Prepare the SQL query
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        if (!empty($image_path)) {
            $sql = "UPDATE users SET role=?, last_name=?, first_name=?, middle_name=?, gender=?, contact=?, email=?, password=?, image=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssssi", $role, $last_name, $first_name, $middle_name, $gender, $contact_number, $email, $hashed_password, $image_path, $id);
        } else {
            $sql = "UPDATE users SET role=?, last_name=?, first_name=?, middle_name=?, gender=?, contact=?, email=?, password=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssi", $role, $last_name, $first_name, $middle_name, $gender, $contact_number, $email, $hashed_password, $id);
        }
    } else {
        if (!empty($image_path)) {
            $sql = "UPDATE users SET role=?, last_name=?, first_name=?, middle_name=?, gender=?, contact=?, email=?, image=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssssssssi", $role, $last_name, $first_name, $middle_name, $gender, $contact_number, $email, $image_path, $id);
        } else {
            $sql = "UPDATE users SET role=?, last_name=?, first_name=?, middle_name=?, gender=?, contact=?, email=? WHERE id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssssssi", $role, $last_name, $first_name, $middle_name, $gender, $contact_number, $email, $id);
        }
    }

    if ($stmt->execute()) {
        $_SESSION['success'] = 'Staff updated successfully';
    } else {
        $_SESSION['error'] = 'Failed to update staff: ' . $conn->error;
    }

    $stmt->close();
    $conn->close();

    header('Location: staffs.php');
    exit();
} else {
    header('Location: staffs.php');
    exit();
}
?>