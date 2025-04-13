<?php
session_start();
include '../database.php';

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

// Handle form submission for profile updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $firstName = $_POST['first_name'];
    $lastName = $_POST['last_name'];
    $middleName = $_POST['middle_name'] ?? '';
    $birthdate = $_POST['birthdate'];
    $contact = $_POST['contact'];
    $address = $_POST['address'];
    $email = $_POST['email'];
    $gender = $_POST['gender'];

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
}

// Handle form submission for password changes
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_password'])) {
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile | City Smile Dental Clinic</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
        }

        .content {
            margin-left: 0;
            margin-top: 12vh;
            padding: 30px;
            background-color: #f5f7fa;
            min-height: calc(100vh - 12vh);
        }

        .page-title {
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-title i {
            color: #7B32AB;
            font-size: 1.5rem;
        }

        .profile-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
            padding: 25px;
            margin-bottom: 25px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eaeaea;
            padding-bottom: 15px;
        }

        .card-header h2 {
            font-size: 1.5rem;
            color: #7B32AB;
            margin: 0;
            font-weight: 600;
        }

        .edit-button {
            background-color: #297859;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .edit-button:hover {
            background-color: #1f5a40;
        }

        .profile-info {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }

        .info-group {
            margin-bottom: 15px;
        }

        .info-label {
            font-size: 12px;
            color: #6c757d;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-value {
            font-size: 16px;
            color: #333;
            font-weight: 500;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1050;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background-color: white;
            margin: auto;
            border-radius: 12px;
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 600px;
            padding: 25px;
            position: relative;
        }

        .modal-tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #eaeaea;
        }

        .modal-tab {
            padding: 10px 20px;
            font-weight: 500;
            cursor: pointer;
            color: #666;
            background: none;
            border: none;
            border-bottom: 3px solid transparent;
            transition: all 0.2s;
        }

        .modal-tab.active {
            color: #7B32AB;
            border-bottom: 3px solid #7B32AB;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            border-color: #7B32AB;
            outline: none;
        }

        .form-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-top: 20px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 500;
            cursor: pointer;
            border: none;
            transition: background-color 0.2s;
        }

        .btn-primary {
            background-color: #297859;
            color: white;
        }

        .btn-primary:hover {
            background-color: #1f5a40;
        }

        .btn-secondary {
            background-color: #e9ecef;
            color: #495057;
        }

        .btn-secondary:hover {
            background-color: #dee2e6;
        }

        .profile-picture {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 15px;
            border: 3px solid #7B32AB;
        }

        .profile-picture-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            margin-bottom: 20px;
        }

        .alert {
            padding: 10px 15px;
            margin-bottom: 15px;
            border-radius: 6px;
        }

        .alert-success {
            background-color: rgba(41, 120, 89, 0.1);
            color: #297859;
            border-left: 4px solid #297859;
        }

        .alert-danger {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border-left: 4px solid #dc3545;
        }

        @media (max-width: 768px) {
            .form-row {
                flex-direction: column;
                gap: 10px;
            }

            .content {
                padding: 15px;
            }

            .profile-info {
                display: block;
            }
        }
    </style>
</head>

<body>
    <?php include '../userdashboard/user_sidebar.php'; ?>

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
                <img src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : '../icons/profile.png'; ?>"
                    alt="Profile Picture" class="profile-picture">
                <h3><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></h3>
                <p><?php echo htmlspecialchars($user['role']); ?></p>
            </div>

            <div class="profile-info">
                <div class="info-group">
                    <div class="info-label">Full Name</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['middle_name'] . ' ' . $user['last_name']); ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">Birth Date</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($user['birthdate']); ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">Gender</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars(ucfirst($user['gender'])); ?>
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
                        <?php echo htmlspecialchars($user['contact']); ?>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">Address</div>
                    <div class="info-value">
                        <?php echo htmlspecialchars($user['address']); ?>
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
            </div>
        </div>
    </main>

    <!-- Edit Profile Modal -->
    <div id="profileModal" class="modal">
        <div class="modal-content">
            <div class="card-header">
                <h2>Edit Profile</h2>
            </div>

            <form method="post" action="">
                <div class="form-row">
                    <div class="form-group">
                        <label for="first_name">First Name</label>
                        <input type="text" id="first_name" name="first_name" class="form-control"
                            value="<?php echo htmlspecialchars($user['first_name']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="last_name">Last Name</label>
                        <input type="text" id="last_name" name="last_name" class="form-control"
                            value="<?php echo htmlspecialchars($user['last_name']); ?>" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="middle_name">Middle Name</label>
                    <input type="text" id="middle_name" name="middle_name" class="form-control"
                        value="<?php echo htmlspecialchars($user['middle_name']); ?>">
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="birthdate">Birth Date</label>
                        <input type="date" id="birthdate" name="birthdate" class="form-control"
                            value="<?php echo htmlspecialchars($user['birthdate']); ?>" required>
                    </div>
                    <div class="form-group">
                        <label for="gender">Gender</label>
                        <select id="gender" name="gender" class="form-control" required>
                            <option value="male" <?php echo ($user['gender'] === 'male') ? 'selected' : ''; ?>>Male
                            </option>
                            <option value="female" <?php echo ($user['gender'] === 'female') ? 'selected' : ''; ?>>Female
                            </option>
                            <option value="other" <?php echo ($user['gender'] === 'other') ? 'selected' : ''; ?>>Other
                            </option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" class="form-control"
                        value="<?php echo htmlspecialchars($user['email']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="contact">Contact Number</label>
                    <input type="text" id="contact" name="contact" class="form-control"
                        value="<?php echo htmlspecialchars($user['contact']); ?>" required>
                </div>

                <div class="form-group">
                    <label for="address">Address</label>
                    <textarea id="address" name="address" class="form-control" rows="3"
                        required><?php echo htmlspecialchars($user['address']); ?></textarea>
                </div>

                <div class="form-buttons">
                    <button type="button" class="btn btn-secondary" id="cancelEditBtn">Cancel</button>
                    <button type="submit" name="update_profile" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Change Password Modal -->
    <div id="passwordModal" class="modal">
        <div class="modal-content">
            <div class="card-header">
                <h2>Change Password</h2>
            </div>

            <form method="post" action="">
                <div class="form-group">
                    <label for="current_password">Current Password</label>
                    <input type="password" id="current_password" name="current_password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <input type="password" id="new_password" name="new_password" class="form-control" required>
                </div>

                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                </div>

                <div class="form-buttons">
                    <button type="button" class="btn btn-secondary" id="cancelPasswordBtn">Cancel</button>
                    <button type="submit" name="update_password" class="btn btn-primary">Update Password</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Get modal elements
            const profileModal = document.getElementById('profileModal');
            const passwordModal = document.getElementById('passwordModal');

            // Get buttons
            const editProfileBtn = document.getElementById('editProfileBtn');
            const changePasswordBtn = document.getElementById('changePasswordBtn');
            const cancelEditBtn = document.getElementById('cancelEditBtn');
            const cancelPasswordBtn = document.getElementById('cancelPasswordBtn');

            // Open profile modal
            editProfileBtn.addEventListener('click', function () {
                profileModal.style.display = 'flex';
            });

            // Open password modal
            changePasswordBtn.addEventListener('click', function () {
                passwordModal.style.display = 'flex';
            });

            // Close profile modal
            cancelEditBtn.addEventListener('click', function () {
                profileModal.style.display = 'none';
            });

            // Close password modal
            cancelPasswordBtn.addEventListener('click', function () {
                passwordModal.style.display = 'none';
            });

            // Close modals when clicked outside
            window.addEventListener('click', function (event) {
                if (event.target === profileModal) {
                    profileModal.style.display = 'none';
                }
                if (event.target === passwordModal) {
                    passwordModal.style.display = 'none';
                }
            });
        });
    </script>
</body>

</html>