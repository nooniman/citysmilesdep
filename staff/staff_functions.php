<?php
include_once '../database.php';

// Function to fetch all staff members
function fetchStaff($conn) {
    $staff = [];
    $sql = "SELECT id, first_name, last_name, middle_name, email, contact, gender, role, image, created_at 
            FROM users WHERE role IN ('staff', 'assistant', 'intern', 'dentist')";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $row['full_name'] = $row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name'];
            $row['image_path'] = !empty($row['image']) ? $row['image'] : 'uploads/default.jpg';
            $staff[] = $row;
        }
    }
    
    return $staff;
}

// Function to add new staff
function addStaff($conn, $data, $file) {
    // Extract data
    $role = $data['role'];
    $last_name = $data['last_name'];
    $first_name = $data['first_name'];
    $middle_name = $data['middle_name'] ?? '';
    $gender = $data['gender'];
    $contact_number = $data['contact_number'];
    $email = $data['email'];
    $password = $data['password'];
    $confirm_password = $data['confirm_password'];
    
    // Generate username
    $username = generateUsername($first_name, $last_name, $conn);
    
    // Validate passwords
    if ($password !== $confirm_password) {
        return ['status' => 'error', 'message' => 'Passwords do not match'];
    }
    
    // Hash password
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    
    // Handle image upload
    $image_path = processImageUpload($file);
    if (is_array($image_path) && isset($image_path['status']) && $image_path['status'] === 'error') {
        return $image_path;
    }
    
    // Insert into database
    $stmt = $conn->prepare("INSERT INTO users (role, last_name, first_name, middle_name, username, gender, contact, email, password, image) 
                          VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssss", $role, $last_name, $first_name, $middle_name, $username, $gender, $contact_number, $email, $hashed_password, $image_path);
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['status' => 'success', 'message' => 'Staff added successfully'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['status' => 'error', 'message' => 'Failed to add staff: ' . $error];
    }
}

// Function to edit existing staff
function editStaff($conn, $data, $file) {
    // Extract data
    $id = $data['staff_id'];
    $role = $data['role'];
    $last_name = $data['last_name'];
    $first_name = $data['first_name'];
    $middle_name = $data['middle_name'] ?? '';
    $gender = $data['gender'];
    $contact_number = $data['contact_number'];
    $email = $data['email'];
    $password = isset($data['password']) ? $data['password'] : '';
    $confirm_password = isset($data['confirm_password']) ? $data['confirm_password'] : '';
    
    // Check if passwords match if provided
    if (!empty($password) && $password !== $confirm_password) {
        return ['status' => 'error', 'message' => 'Passwords do not match'];
    }
    
    // Process image if uploaded
    $image_path = '';
    if (!empty($file['staff_image']['name'])) {
        $image_path = processImageUpload($file);
        if (is_array($image_path) && isset($image_path['status']) && $image_path['status'] === 'error') {
            return $image_path;
        }
        
        // Delete old image
        $stmt = $conn->prepare("SELECT image FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            if (!empty($row['image']) && file_exists($row['image']) && $row['image'] !== 'uploads/default.jpg') {
                unlink($row['image']);
            }
        }
        $stmt->close();
    }
    
    // Prepare SQL based on what's being updated
    if (!empty($password) && !empty($image_path)) {
        // Update password and image
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET role=?, last_name=?, first_name=?, middle_name=?, gender=?, contact=?, email=?, password=?, image=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssssi", $role, $last_name, $first_name, $middle_name, $gender, $contact_number, $email, $hashed_password, $image_path, $id);
    } elseif (!empty($password)) {
        // Update password only
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "UPDATE users SET role=?, last_name=?, first_name=?, middle_name=?, gender=?, contact=?, email=?, password=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssi", $role, $last_name, $first_name, $middle_name, $gender, $contact_number, $email, $hashed_password, $id);
    } elseif (!empty($image_path)) {
        // Update image only
        $sql = "UPDATE users SET role=?, last_name=?, first_name=?, middle_name=?, gender=?, contact=?, email=?, image=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssssi", $role, $last_name, $first_name, $middle_name, $gender, $contact_number, $email, $image_path, $id);
    } else {
        // Update without password or image
        $sql = "UPDATE users SET role=?, last_name=?, first_name=?, middle_name=?, gender=?, contact=?, email=? WHERE id=?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssi", $role, $last_name, $first_name, $middle_name, $gender, $contact_number, $email, $id);
    }
    
    if ($stmt->execute()) {
        $stmt->close();
        return ['status' => 'success', 'message' => 'Staff updated successfully'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['status' => 'error', 'message' => 'Failed to update staff: ' . $error];
    }
}

// Function to delete staff
function deleteStaff($conn, $id) {
    // Check if staff exists and get image
    $stmt = $conn->prepare("SELECT image FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        $stmt->close();
        return ['status' => 'error', 'message' => 'Staff not found'];
    }
    
    $row = $result->fetch_assoc();
    $image_path = $row['image'];
    $stmt->close();
    
    // Delete the staff record
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        // Delete the image file if it exists
        if (!empty($image_path) && file_exists($image_path) && $image_path !== 'uploads/default.jpg') {
            unlink($image_path);
        }
        
        $stmt->close();
        return ['status' => 'success', 'message' => 'Staff deleted successfully'];
    } else {
        $error = $stmt->error;
        $stmt->close();
        return ['status' => 'error', 'message' => 'Failed to delete staff: ' . $error];
    }
}

// Helper function to generate username
function generateUsername($first, $last, $conn) {
    $base_username = strtolower(substr($first, 0, 1) . $last);
    $base_username = preg_replace('/[^a-z0-9]/', '', $base_username);
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

// Helper function to process image uploads
function processImageUpload($file) {
    if (empty($file['staff_image']['name'])) {
        return '';
    }
    
    // Create uploads directory if it doesn't exist
    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    
    $image_name = time() . '_' . basename($file['staff_image']['name']);
    $target_file = $target_dir . $image_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check file type
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($imageFileType, $allowed_types)) {
        return ['status' => 'error', 'message' => 'Sorry, only JPG, JPEG, PNG & GIF files are allowed'];
    }
    
    // Upload the file
    if (move_uploaded_file($file['staff_image']['tmp_name'], $target_file)) {
        return $target_file;
    } else {
        return ['status' => 'error', 'message' => 'Failed to upload image'];
    }
}

// Process AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $response = ['status' => 'error', 'message' => 'Invalid action'];
    
    switch ($_POST['action']) {
        case 'add':
            $response = addStaff($conn, $_POST, $_FILES);
            break;
            
        case 'edit':
            $response = editStaff($conn, $_POST, $_FILES);
            break;
            
        case 'delete':
            if (isset($_POST['staff_id'])) {
                $response = deleteStaff($conn, $_POST['staff_id']);
            }
            break;
            
        case 'fetch':
            $staff = fetchStaff($conn);
            $response = ['status' => 'success', 'data' => $staff];
            break;
    }
    
    // Return JSON response for AJAX calls
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
?>