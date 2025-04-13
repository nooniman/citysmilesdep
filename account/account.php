<?php
session_start();
include '../database.php';

if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit();
}

// Fetch user details
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle profile picture upload
$profile_picture_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errorMsg = "Security validation failed";
    } else {
        $firstName = $_POST['first_name'];
        $lastName = $_POST['last_name'];
        $middleName = $_POST['middle_name'] ?? '';
        $birthdate = $_POST['birthdate'];
        $contact = $_POST['contact'];
        $address = $_POST['address'];
        $email = $_POST['email'];
        $gender = $_POST['gender'];

        // Handle profile picture upload if a file was selected
        if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_picture']['name'];
            $filetype = pathinfo($filename, PATHINFO_EXTENSION);
            
            if (in_array(strtolower($filetype), $allowed)) {
                $new_filename = 'profile_' . $user_id . '_' . time() . '.' . $filetype;
                $upload_path = '../uploads/profiles/' . $new_filename;
                
                // Create directory if it doesn't exist
                if (!file_exists('../uploads/profiles/')) {
                    mkdir('../uploads/profiles/', 0777, true);
                }
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_path)) {
                    $profile_picture_db_path = '../uploads/profiles/' . $new_filename;
                    
                    // Update the profile picture path in the database
                    $updateSql = "UPDATE users SET 
                                first_name = ?,
                                last_name = ?,
                                middle_name = ?,
                                birthdate = ?,
                                contact = ?,
                                address = ?,
                                email = ?,
                                gender = ?,
                                profile_picture = ?
                                WHERE id = ?";
                    
                    $updateStmt = $conn->prepare($updateSql);
                    $updateStmt->bind_param("sssssssssi", $firstName, $lastName, $middleName, $birthdate, $contact, $address, $email, $gender, $profile_picture_db_path, $user_id);
                    
                    if ($updateStmt->execute()) {
                        $successMsg = "Profile updated successfully!";
                        // Refresh user data
                        $stmt->execute();
                        $result = $stmt->get_result();
                        $user = $result->fetch_assoc();
                    } else {
                        $errorMsg = "Error updating profile: " . $conn->error;
                    }
                } else {
                    $errorMsg = "Failed to upload profile picture";
                }
            } else {
                $errorMsg = "Invalid file type. Only JPG, JPEG, PNG and GIF files are allowed.";
            }
        } else {
            // No new profile picture, just update other fields
            $updateSql = "UPDATE users SET 
                        first_name = ?,
                        last_name = ?,
                        middle_name = ?,
                        birthdate = ?,
                        contact = ?,
                        address = ?,
                        email = ?,
                        gender = ?
                        WHERE id = ?";
            
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("ssssssssi", $firstName, $lastName, $middleName, $birthdate, $contact, $address, $email, $gender, $user_id);
            
            if ($updateStmt->execute()) {
                $successMsg = "Profile updated successfully!";
                // Refresh user data
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
            } else {
                $errorMsg = "Error updating profile: " . $conn->error;
            }

            if ($updateStmt->execute()) {
                $successMsg = "Profile updated successfully!";
                // Refresh user data
                $stmt->execute();
                $result = $stmt->get_result();
                $user = $result->fetch_assoc();
                
                // Update session variable for immediate effect across the site
                $_SESSION['profile_picture'] = $profile_picture_db_path;
            }
        }
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $passwordErrorMsg = "Security validation failed";
    } else {
        $currentPassword = $_POST['current_password'];
        $newPassword = $_POST['new_password'];
        $confirmPassword = $_POST['confirm_password'];

        // Validate current password
        $passwordSql = "SELECT password FROM users WHERE id = ?";
        $passwordStmt = $conn->prepare($passwordSql);
        $passwordStmt->bind_param("i", $user_id);
        $passwordStmt->execute();
        $passwordResult = $passwordStmt->get_result();
        $userData = $passwordResult->fetch_assoc();

        if (password_verify($currentPassword, $userData['password'])) {
            if ($newPassword === $confirmPassword) {
                $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

                $updatePasswordSql = "UPDATE users SET password = ? WHERE id = ?";
                $updatePasswordStmt = $conn->prepare($updatePasswordSql);
                $updatePasswordStmt->bind_param("si", $hashedPassword, $user_id);

                if ($updatePasswordStmt->execute()) {
                    $passwordSuccessMsg = "Password updated successfully!";
                } else {
                    $passwordErrorMsg = "Error updating password: " . $conn->error;
                }
            } else {
                $passwordErrorMsg = "New passwords do not match.";
            }
        } else {
            $passwordErrorMsg = "Current password is incorrect.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | City Smile Dental Clinic</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../userdashboard/dashboard.css">
    <style>
        :root {
            --primary-color: #7B32AB;
            --secondary-color: #297859;
            --light-gray: #f5f7fa;
            --border-color: #eaeaea;
            --text-dark: #333;
            --text-medium: #666;
            --text-light: #6c757d;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --header-height: 60px;
            --border-radius: 12px;
            --box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--light-gray);
            margin: 0;
            padding: 0;
            color: var(--text-dark);
            display: flex;
            transition: var(--transition);
        }

        .sidebar {
            width: var(--sidebar-width);
            transition: width 0.3s ease;
        }

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .content {
            width: calc(100% - var(--sidebar-width));
            margin-left: var(--sidebar-width);
            padding: 30px;
            transition: var(--transition);
            min-height: calc(100vh - var(--header-height));
        }

        body.cs-sidebar-collapsed .content {
            width: calc(100% - var(--sidebar-collapsed-width));
            margin-left: var(--sidebar-collapsed-width);
        }

        /* Page header */
        .page-title {
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .page-title i {
            color: var(--primary-color);
            font-size: 1.6rem;
        }

        .page-title h1 {
            font-size: 1.8rem;
            font-weight: 600;
            margin: 0;
            color: var(--text-dark);
        }

        /* Cards */
        .profile-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 30px;
            margin-bottom: 30px;
            transition: var(--transition);
        }

        .profile-card:hover {
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.08);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 20px;
        }

        .card-header h2 {
            font-size: 1.4rem;
            color: var(--primary-color);
            margin: 0;
            font-weight: 600;
        }

        /* Buttons */
        .edit-button {
            background-color: var(--secondary-color);
            color: white;
            border: none;
            padding: 10px 16px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 8px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .edit-button:hover {
            background-color: #1f5a40;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        /* Profile info */
        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 25px;
        }

        .info-group {
            margin-bottom: 18px;
        }

        .info-label {
            font-size: 0.75rem;
            color: var(--text-light);
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            font-weight: 500;
        }

        .info-value {
            font-size: 1rem;
            color: var(--text-dark);
            font-weight: 500;
            word-break: break-word;
        }

        /* Profile picture */
        .profile-picture-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 30px;
        }

        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 4px solid var(--primary-color);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: var(--transition);
        }

        .profile-picture:hover {
            transform: scale(1.05);
            box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
        }

        .profile-picture-container h3 {
            margin: 10px 0 5px;
            font-size: 1.3rem;
            font-weight: 600;
        }

        .profile-picture-container p {
            margin: 0;
            color: var(--text-light);
            text-transform: capitalize;
            font-size: 0.9rem;
        }

        /* Alerts */
        .alert {
            padding: 14px 18px;
            margin-bottom: 20px;
            border-radius: 8px;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 12px;
            animation: fadeIn 0.3s ease;
        }

        .alert::before {
            font-family: "Font Awesome 6 Free";
            font-weight: 900;
            font-size: 1.1rem;
        }

        .alert-success {
            background-color: rgba(41, 120, 89, 0.12);
            color: #297859;
            border-left: 4px solid #297859;
        }

        .alert-success::before {
            content: "\f058"; /* fa-check-circle */
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.12);
            color: #dc3545;
            border-left: 4px solid #dc3545;
        }

        .alert-danger::before {
            content: "\f057"; /* fa-times-circle */
        }

        /* Modal */
        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: opacity 0.3s ease;
            overflow-y: auto;
        }

        .modal.active {
            display: flex;
            opacity: 1;
        }

        .modal-content {
            background-color: white;
            margin: auto;
            border-radius: var(--border-radius);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            width: 100%;
            max-width: 650px;
            padding: 0;
            position: relative;
            transform: translateY(-20px);
            transition: transform 0.3s ease;
            overflow: hidden;
            max-height: 90vh;
            display: flex;
            flex-direction: column;
        }

        .modal.active .modal-content {
            transform: translateY(0);
        }

        .modal-header {
            padding: 20px 25px;
            background-color: var(--primary-color);
            color: white;
            position: relative;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 1.4rem;
            font-weight: 600;
        }

        .modal-close {
            position: absolute;
            right: 20px;
            top: 20px;
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-close:hover {
            transform: scale(1.2);
        }

        .modal-body {
            padding: 25px;
            overflow-y: auto;
        }

        /* Form styles */
        .form-group {
            margin-bottom: 20px;
        }

        .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 0;
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 0.95rem;
            transition: var(--transition);
            background-color: #f9f9f9;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(123, 50, 171, 0.2);
            background-color: white;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-size: 0.9rem;
            font-weight: 500;
            color: var(--text-medium);
        }

        select.form-control {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' viewBox='0 0 24 24' fill='none' stroke='%236c757d' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 40px;
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .form-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 30px;
        }

        .btn {
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 0.95rem;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: var(--transition);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: var(--secondary-color);
            color: white;
        }

        .btn-primary:hover {
            background-color: #1f5a40;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .btn-secondary {
            background-color: #e9ecef;
            color: #495057;
        }

        .btn-secondary:hover {
            background-color: #dee2e6;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        /* Password strength */
        .password-strength {
            margin-top: 10px;
            font-size: 0.85rem;
        }

        .password-requirements {
            margin-top: 10px;
            font-size: 0.85rem;
            color: var(--text-medium);
        }

        .password-requirements ul {
            padding-left: 20px;
            margin-top: 5px;
        }

        .file-upload {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            border: 2px dashed #ccc;
            border-radius: 8px;
            padding: 30px 20px;
            text-align: center;
            margin-bottom: 20px;
            transition: var(--transition);
            cursor: pointer;
            position: relative;
        }

        .file-upload:hover {
            border-color: var(--secondary-color);
            background-color: rgba(41, 120, 89, 0.05);
        }

        .file-upload i {
            font-size: 2.5rem;
            color: var(--secondary-color);
            margin-bottom: 15px;
        }

        .file-upload-text {
            color: var(--text-medium);
            margin-bottom: 5px;
        }

        .file-upload-subtext {
            font-size: 0.8rem;
            color: var(--text-light);
        }

        .file-upload input[type="file"] {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        .file-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            display: none;
        }

        /* Animation */
        @keyframes fadeIn {
            0% { opacity: 0; }
            100% { opacity: 1; }
        }

        /* Responsive */
        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 15px;
            }

            .content {
                padding: 20px;
            }

            .profile-card {
                padding: 20px;
            }

            .profile-info {
                display: block;
            }

            .modal-content {
                max-width: 95%;
            }
        }
    </style>
</head>

<body>
    <?php include '../sidebar/sidebar.php'; ?>

    <main class="content">
        <div class="page-title">
            <i class="fas fa-user-circle"></i>
            <h1>My Profile</h1>
        </div>

        <?php if (isset($successMsg)): ?>
            <div class="alert alert-success"><?php echo $successMsg; ?></div>
        <?php endif; ?>

        <?php if (isset($errorMsg)): ?>
            <div class="alert alert-danger"><?php echo $errorMsg; ?></div>
        <?php endif; ?>

        <div class="profile-card">
            <div class="card-header">
                <h2>Personal Information</h2>
                <button id="editProfileBtn" class="edit-button">
                    <i class="fas fa-pencil-alt"></i> Edit Profile
                </button>
            </div>

            <div class="profile-picture-container">
                <img src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : '../icons/profile.png'; ?>" alt="Profile Picture" class="profile-picture">
                <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                <p><?php echo htmlspecialchars($user['role']); ?></p>
            </div>

            <div class="profile-info">
                <div class="info-group">
                    <div class="info-label">Full Name</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . ($user['middle_name'] ? $user['middle_name'] . ' ' : '') . $user['last_name']); ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">Birth Date</div>
                    <div class="info-value">
                        <?php echo $user['birthdate'] ? date('F j, Y', strtotime($user['birthdate'])) : 'Not set'; ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">Gender</div>
                    <div class="info-value">
                        <?php echo $user['gender'] ? htmlspecialchars(ucfirst($user['gender'])) : 'Not set'; ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">Email Address</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($user['email']); ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">Contact Number</div>
                    <div class="info-value">
                        <?php echo $user['contact'] ? htmlspecialchars($user['contact']) : 'Not set'; ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">Address</div>
                    <div class="info-value">
                        <?php echo $user['address'] ? htmlspecialchars($user['address']) : 'Not set'; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="profile-card">
            <div class="card-header">
                <h2>Account Security</h2>
                <button id="changePasswordBtn" class="edit-button">
                    <i class="fas fa-lock"></i> Change Password
                </button>
            </div>

            <?php if (isset($passwordSuccessMsg)): ?>
                <div class="alert alert-success"><?php echo $passwordSuccessMsg; ?></div>
            <?php endif; ?>

            <?php if (isset($passwordErrorMsg)): ?>
                <div class="alert alert-danger"><?php echo $passwordErrorMsg; ?></div>
            <?php endif; ?>

            <div class="profile-info">
                <div class="info-group">
                    <div class="info-label">Password</div>
                    <div class="info-value">••••••••••</div>
                </div>
                
                <div class="info-group">
                    <div class="info-label">Last Password Update</div>
                    <div class="info-value">
                        <?php echo date('F j, Y', strtotime($user['updated_at'])); ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Edit Profile Modal -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Edit Profile</h2>
                <button type="button" class="modal-close" id="closeProfileModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <form method="post" action="" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="file-upload">
                        <img id="profilePreview" class="file-preview" src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : '../icons/profile.png'; ?>">
                        <i class="fas fa-cloud-upload-alt"></i>
                        <div class="file-upload-text">Drag & Drop or Click to Upload Profile Picture</div>
                        <div class="file-upload-subtext">JPG, PNG or GIF • Max 5MB</div>
                        <input type="file" id="profile_picture" name="profile_picture" accept="image/*">
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" class="form-control"
                                value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" class="form-control"
                                value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="middle_name">Middle Name</label>
                        <input type="text" id="middle_name" name="middle_name" class="form-control"
                            value="<?php echo htmlspecialchars($user['middle_name'] ?? ''); ?>">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="birthdate">Birth Date</label>
                            <input type="date" id="birthdate" name="birthdate" class="form-control"
                                value="<?php echo htmlspecialchars($user['birthdate'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="gender">Gender</label>
                            <select id="gender" name="gender" class="form-control" required>
                                <option value="male" <?php echo ($user['gender'] === 'male') ? 'selected' : ''; ?>>Male</option>
                                <option value="female" <?php echo ($user['gender'] === 'female') ? 'selected' : ''; ?>>Female</option>
                                <option value="other" <?php echo ($user['gender'] === 'other') ? 'selected' : ''; ?>>Other</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <input type="email" id="email" name="email" class="form-control"
                            value="<?php echo htmlspecialchars($user['email']); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="contact">Contact Number</label>
                        <input type="text" id="contact" name="contact" class="form-control"
                            value="<?php echo htmlspecialchars($user['contact'] ?? ''); ?>" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="address">Address</label>
                        <textarea id="address" name="address" class="form-control" rows="3"
                            required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>

                    <div class="form-buttons">
                        <button type="button" class="btn btn-secondary" id="cancelEditBtn">Cancel</button>
                        <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Change Password</h2>
                <button type="button" class="modal-close" id="closePasswordModal">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <div class="modal-body">
                <form method="post" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    
                    <div class="form-group">
                        <label class="form-label" for="current_password">Current Password</label>
                        <input type="password" id="current_password" name="current_password" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="new_password">New Password</label>
                        <input type="password" id="new_password" name="new_password" class="form-control" required>
                        <div id="password_requirements" class="password-requirements">
                            Password should contain:
                            <ul>
                                <li>At least 8 characters</li>
                                <li>At least one uppercase letter</li>
                                <li>At least one lowercase letter</li>
                                <li>At least one number</li>
                            </ul>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="confirm_password">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                    </div>

                    <div class="form-buttons">
                        <button type="button" class="btn btn-secondary" id="cancelPasswordBtn">Cancel</button>
                        <button type="submit" name="update_password" class="btn btn-primary">Update Password</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Profile picture preview
            const profilePictureInput = document.getElementById('profile_picture');
            const profilePreview = document.getElementById('profilePreview');
            
            // Show preview initially if there's a profile picture
            if (profilePreview.src && profilePreview.src !== '') {
                profilePreview.style.display = 'block';
            }
            
            profilePictureInput.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        profilePreview.src = e.target.result;
                        profilePreview.style.display = 'block';
                    }
                    reader.readAsDataURL(file);
                }
            });
            
            // Password validation
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            
            newPasswordInput.addEventListener('input', function() {
                validatePassword(this.value);
            });
            
            confirmPasswordInput.addEventListener('input', function() {
                const newPassword = newPasswordInput.value;
                if (this.value !== newPassword) {
                    this.setCustomValidity("Passwords don't match");
                } else {
                    this.setCustomValidity('');
                }
            });
            
            function validatePassword(password) {
                const requirements = [];
                const requirementsList = document.querySelector('#password_requirements ul');
                requirementsList.innerHTML = '';
                
                if (password.length < 8) {
                    requirements.push('<li class="invalid">At least 8 characters</li>');
                } else {
                    requirements.push('<li class="valid">At least 8 characters ✓</li>');
                }
                
                if (!/[A-Z]/.test(password)) {
                    requirements.push('<li class="invalid">At least one uppercase letter</li>');
                } else {
                    requirements.push('<li class="valid">At least one uppercase letter ✓</li>');
                }
                
                if (!/[a-z]/.test(password)) {
                    requirements.push('<li class="invalid">At least one lowercase letter</li>');
                } else {
                    requirements.push('<li class="valid">At least one lowercase letter ✓</li>');
                }
                
                if (!/[0-9]/.test(password)) {
                    requirements.push('<li class="invalid">At least one number</li>');
                } else {
                    requirements.push('<li class="valid">At least one number ✓</li>');
                }
                
                requirementsList.innerHTML = requirements.join('');
            }
            
            // Modal controls
            const profileModal = document.getElementById('profileModal');
            const passwordModal = document.getElementById('passwordModal');
            
            const editProfileBtn = document.getElementById('editProfileBtn');
            const changePasswordBtn = document.getElementById('changePasswordBtn');
            const cancelEditBtn = document.getElementById('cancelEditBtn');
            const cancelPasswordBtn = document.getElementById('cancelPasswordBtn');
            const closeProfileModal = document.getElementById('closeProfileModal');
            const closePasswordModal = document.getElementById('closePasswordModal');
            
            // Open profile modal
            editProfileBtn.addEventListener('click', function() {
                profileModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
            
            // Open password modal
            changePasswordBtn.addEventListener('click', function() {
                passwordModal.classList.add('active');
                document.body.style.overflow = 'hidden';
            });
            
            // Close modals
            function closeModals() {
                profileModal.classList.remove('active');
                passwordModal.classList.remove('active');
                document.body.style.overflow = '';
            }
            
            cancelEditBtn.addEventListener('click', closeModals);
            cancelPasswordBtn.addEventListener('click', closeModals);
            closeProfileModal.addEventListener('click', closeModals);
            closePasswordModal.addEventListener('click', closeModals);
            
            // Close when clicking outside
            window.addEventListener('click', function(event) {
                if (event.target === profileModal) {
                    closeModals();
                }
                if (event.target === passwordModal) {
                    closeModals();
                }
            });
            
            // File upload styling
            const fileUpload = document.querySelector('.file-upload');
            
            // Highlight on dragover
            fileUpload.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.style.borderColor = '#297859';
                this.style.backgroundColor = 'rgba(41, 120, 89, 0.1)';
            });
            
            fileUpload.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.style.borderColor = '#ccc';
                this.style.backgroundColor = '';
            });
            
            fileUpload.addEventListener('drop', function(e) {
                e.preventDefault();
                this.style.borderColor = '#ccc';
                this.style.backgroundColor = '';
                
                if (e.dataTransfer.files.length) {
                    profilePictureInput.files = e.dataTransfer.files;
                    
                    // Trigger change event manually
                    const event = new Event('change');
                    profilePictureInput.dispatchEvent(event);
                }
            });
        });
    </script>
</body>
</html>