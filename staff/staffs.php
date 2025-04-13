<?php
session_start();
include_once '../database.php';

// Check access permissions
if (
    !isset($_SESSION['user_id']) || !isset($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'staff', 'dentist', 'assistant', 'intern'])
) {
    // Return unauthorized for AJAX requests
    if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
        header('HTTP/1.1 401 Unauthorized');
        echo json_encode(['error' => 'Unauthorized access']);
        exit();
    }
    // Otherwise redirect to login page
    header('Location: ../login.php');
    exit();
}

// Create uploads directory if it doesn't exist
$uploads_dir = 'uploads';
if (!file_exists($uploads_dir)) {
    mkdir($uploads_dir, 0777, true);
}

// Consolidated staff helper functions
function generateUsername($first, $last, $conn)
{
    $base_username = strtolower(substr($first, 0, 1) . $last);
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

function handleStaffImage($file)
{
    if (empty($file['name'])) {
        return '';
    }

    $target_dir = "uploads/";
    $image_name = time() . '_' . basename($file['name']);
    $target_file = $target_dir . $image_name;
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];

    if (!in_array($imageFileType, $allowed_types)) {
        throw new Exception('Invalid image format. Allowed formats: JPG, JPEG, PNG, GIF');
    }

    if (!move_uploaded_file($file['tmp_name'], $target_file)) {
        throw new Exception('Failed to upload image');
    }

    return $target_file;
}

// AJAX handler for staff operations
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    include_once '../database.php';
    $response = ['success' => false];

    try {
        switch ($_POST['action']) {
            case 'add':
                $role = $_POST['role'];
                $last_name = $_POST['last_name'];
                $first_name = $_POST['first_name'];
                $middle_name = $_POST['middle_name'] ?? '';
                $gender = $_POST['gender'];
                $contact_number = $_POST['contact_number'];
                $email = $_POST['email'];
                $password = $_POST['password'];
                $confirm_password = $_POST['confirm_password'];

                // Validation
                if ($password !== $confirm_password) {
                    throw new Exception('Passwords do not match');
                }

                $username = generateUsername($first_name, $last_name, $conn);
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                // Handle image upload
                $image_path = !empty($_FILES['staff_image']['name']) ? handleStaffImage($_FILES['staff_image']) : '';

                // Insert into database
                $stmt = $conn->prepare("INSERT INTO users (role, last_name, first_name, middle_name, username, gender, contact, email, password, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssss", $role, $last_name, $first_name, $middle_name, $username, $gender, $contact_number, $email, $hashed_password, $image_path);

                if (!$stmt->execute()) {
                    throw new Exception('Database error: ' . $stmt->error);
                }

                $response = [
                    'success' => true,
                    'message' => 'Staff member added successfully',
                    'staff_id' => $conn->insert_id
                ];
                break;

            case 'edit':
                $id = $_POST['staff_id'];
                $role = $_POST['role'];
                $last_name = $_POST['last_name'];
                $first_name = $_POST['first_name'];
                $middle_name = $_POST['middle_name'] ?? '';
                $gender = $_POST['gender'];
                $contact_number = $_POST['contact_number'];
                $email = $_POST['email'];
                $password = $_POST['password'] ?? '';
                $confirm_password = $_POST['confirm_password'] ?? '';

                // Check if passwords match if they're being changed
                if (!empty($password) && $password !== $confirm_password) {
                    throw new Exception('Passwords do not match');
                }

                // Handle file upload
                $image_path = '';
                if (!empty($_FILES['staff_image']['name'])) {
                    $image_path = handleStaffImage($_FILES['staff_image']);

                    // Delete old image if it exists
                    $stmt = $conn->prepare("SELECT image FROM users WHERE id = ?");
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    $row = $result->fetch_assoc();

                    if (!empty($row['image']) && file_exists($row['image']) && $row['image'] !== 'default.jpg') {
                        unlink($row['image']);
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
                        $stmt->bind_param("ssssssssi", $role, $last_name, $first_name, $middle_name, $gender, $contact_number, $email, $hashed_password, $id);
                    }
                } else {
                    if (!empty($image_path)) {
                        $sql = "UPDATE users SET role=?, last_name=?, first_name=?, middle_name=?, gender=?, contact=?, email=?, image=? WHERE id=?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("ssssssssi", $role, $last_name, $first_name, $middle_name, $gender, $contact_number, $email, $image_path, $id);
                    } else {
                        $sql = "UPDATE users SET role=?, last_name=?, first_name=?, middle_name=?, gender=?, contact=?, email=? WHERE id=?";
                        $stmt->bind_param("sssssssi", $role, $last_name, $first_name, $middle_name, $gender, $contact_number, $email, $id);
                    }
                }

                if (!$stmt->execute()) {
                    throw new Exception('Database error: ' . $stmt->error);
                }

                $response = [
                    'success' => true,
                    'message' => 'Staff member updated successfully'
                ];
                break;


            case 'delete':
                header('Location: soft_delete_staffs.php');

                break;


            case 'fetch':
                // Get the show_deleted parameter with default of 0 (false)
                $showDeleted = isset($_POST['show_deleted']) ? intval($_POST['show_deleted']) : 0;

                // Build the SQL query based on whether to show deleted records
                if ($showDeleted) {
                    $sql = "SELECT id, first_name, last_name, middle_name, email, contact, gender, role, image, created_at, is_deleted, deleted_at 
                                FROM users 
                                WHERE role IN ('staff', 'assistant', 'intern', 'dentist')
                                ORDER BY last_name ASC";
                } else {
                    $sql = "SELECT id, first_name, last_name, middle_name, email, contact, gender, role, image, created_at, is_deleted, deleted_at 
                                FROM users 
                                WHERE role IN ('staff', 'assistant', 'intern', 'dentist') 
                                AND (is_deleted = 0 OR is_deleted IS NULL)
                                ORDER BY last_name ASC";
                }

                $result = $conn->query($sql);
                $staff = [];

                if ($result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        $staff[] = [
                            'id' => $row['id'],
                            'first_name' => $row['first_name'],
                            'last_name' => $row['last_name'],
                            'middle_name' => $row['middle_name'] ?? '',
                            'full_name' => $row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name'],
                            'email' => $row['email'],
                            'contact' => $row['contact'],
                            'gender' => $row['gender'],
                            'role' => $row['role'],
                            'image' => !empty($row['image']) ? $row['image'] : 'default.jpg',
                            'created_at' => $row['created_at'],
                            'is_deleted' => !empty($row['is_deleted']),
                            'deleted_at' => $row['deleted_at']
                        ];
                    }
                }

                $response = [
                    'success' => true,
                    'data' => $staff
                ];
                break;
        }
    } catch (Exception $e) {
        $response = [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }

    header('Content-Type: application/json');
    echo json_encode($response);
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <link rel="stylesheet" href="staffs.css">
    <link rel="stylesheet" href="modals.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <title>Staff Management | City Smiles Dental</title>
    <style>
        :root {
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --transition-speed: 0.3s;
        }

        /* Content area with proper transitions */
        .content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            transition: all var(--transition-speed) ease;
            min-height: 100vh;
            width: calc(100% - var(--sidebar-width));
            box-sizing: border-box;
        }

        /* Sidebar collapsed state */
        body.cs-sidebar-collapsed .content {
            margin-left: var(--sidebar-collapsed-width);
            width: calc(100% - var(--sidebar-collapsed-width));
        }

        /* Responsive layout adjustments */
        @media screen and (max-width: 1200px) {
            .staff-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
            }

            .stats-row {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }

        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: var(--z-modal-backdrop);
            backdrop-filter: blur(3px);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            padding-top: 40px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(10px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-header {
            padding: var(--spacing-6);
            background: linear-gradient(135deg, var(--primary-lilac), var(--dark-lilac));
            color: white;
            position: relative;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .modal-subtitle {
            margin-top: var(--spacing-1);
            font-size: 0.95rem;
            opacity: 0.9;
        }


        .modal-body {
            padding: var(--spacing-6);
        }

        .modal-footer {
            padding: var(--spacing-4) var(--spacing-6);
            border-top: 1px solid var(--gray-200);
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-3);
        }

        /* Form Elements */
        .form-group {
            margin-bottom: var(--spacing-5);
        }

        .form-label {
            display: block;
            margin-bottom: var(--spacing-2);
            font-weight: 500;
            color: var(--gray-700);
            font-size: 0.95rem;
        }

        .form-control {
            display: block;
            width: 100%;
            padding: var(--spacing-3) var(--spacing-4);
            font-size: 0.95rem;
            line-height: 1.5;
            color: var(--gray-700);
            background-color: white;
            background-clip: padding-box;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-lilac);
            outline: 0;
            box-shadow: 0 0 0 2px var(--light-lilac);
        }

        textarea.form-control {
            height: auto;
            min-height: 100px;
            resize: vertical;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-3) var(--spacing-6);
            font-size: 0.95rem;
            font-weight: 500;
            line-height: 1.5;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            user-select: none;
            border: 1px solid transparent;
            border-radius: var(--radius);
            transition: var(--transition);
            e
        }

        .btn-primary {
            color: white;
            background: linear-gradient(135deg, var(--primary-lilac), var(--dark-lilac));
            border: none;
        }

        .btn-primary:hover {
            box-shadow: 0 0 0 2px var(--light-lilac);
        }

        .btn-secondary {
            color: var(--gray-700);
            background-color: var(--gray-200);
            border-color: var(--gray-300);
        }

        .btn-secondary:hover {
            background-color: var(--gray-300);
        }

        /* Event Details Modal */
        .event-details {
            padding: var(--spacing-3) 0;
        }

        .event-detail {
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
            padding: var(--spacing-3) 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .event-detail:last-child {
            border-bottom: none;
        }

        /* Animation Classes */
        .modal.show {
            display: flex;
            align-items: center;
            animation: modalFadeIn 0.3s ease;
        }

        .modal.show .modal-content {
            animation: modalSlideIn 0.3s ease;
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes modalSlideIn {
            from {
                transform: translateY(50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Responsive Adjustments */
        @media (max-width: 640px) {
            .modal-content {
                margin: 1rem;
                padding: 1.5rem;
            }

            .modal-content h2 {
                font-size: 1.5rem;
            }

            .modal-content .btn-group {
                flex-direction: column;
            }

            .modal-content button {
                width: 100%;
            }
        }

        @media screen and (max-width: 992px) {
            .content {
                margin-left: var(--sidebar-width);
                width: calc(100% - var(--sidebar-width));
            }

            body.cs-sidebar-collapsed .content {
                margin-left: 0;
                width: 100%;
            }

            .staff-grid {
                grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            }
        }

            .search-filter-container {
                flex-direction: column;
                gap: 10px;
            }

            .search-wrapper,
            .filter-select,
            .view-toggle {
                width: 100%;
            }

            .actions-container {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }

            .stats-row {
                grid-template-columns: 1fr;
            }

            .staff-grid {
                grid-template-columns: 1fr;
            }
        }

        /* Fix for content shifting */
        .dashboard-container {
            transition: all var(--transition-speed) ease;
        }

        /* Loading overlay fix */
        .loading-overlay {
            left: var(--sidebar-width);
            width: calc(100% - var(--sidebar-width));
            transition: all var(--transition-speed) ease;
        }

        body.cs-sidebar-collapsed .loading-overlay {
            left: var(--sidebar-collapsed-width);
            width: calc(100% - var(--sidebar-collapsed-width));
        }

        @media (max-width: 992px) {
            .loading-overlay {
                left: 0;
                width: 100%;
            }
        }

        /* Fix for sidebar covering content */
        .content {
            margin-left: var(--cs-sidebar-width);
            transition: margin-left 0.3s ease;
            padding: 20px;
        }

        body.cs-sidebar-collapsed .content {
            margin-left: var(--cs-sidebar-collapsed-width);
        }

        .search-wrapper {
            position: relative;
            flex: 1;
            min-width: 200px;
        }

        .profile-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Loading overlay animation */
        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        .spinner {
            border: 5px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top: 5px solid var(--primary-color);
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        /* Notification animation */
        @keyframes slideIn {
            from {
                transform: translateY(-100%);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        @keyframes slideOut {
            from {
                transform: translateY(0);
                opacity: 1;
            }

            to {
                transform: translateY(-100%);
                opacity: 0;
            }
        }

        #staffForm select {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #1F2937;
            background-color: #F9FAFB;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            transition: all 0.2s ease-in-out;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='none' viewBox='0 0 12 12'%3E%3Cpath stroke='%236B7280' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M3 5l3 3 3-3'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 1rem center;
            background-size: 12px;
            padding-right: 2.5rem;
        }

        #staffForm select:focus {
            outline: none;
            border-color: #8B5CF6;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        #staffForm select:hover {
            border-color: #D1D5DB;
        }

        #staffForm .mb-3 {
            margin-bottom: 1.5rem;
            position: relative;
        }

        #staffForm label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
            color: #374151;
            font-size: 0.95rem;
            transition: all 0.2s ease;
        }

        #staffForm input {
            width: 100%;
            padding: 0.75rem 1rem;
            font-size: 1rem;
            line-height: 1.5;
            color: #1F2937;
            background-color: #F9FAFB;
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            transition: all 0.2s ease-in-out;
        }

        #staffForm input:focus {
            outline: none;
            border-color: #8B5CF6;
            background-color: #fff;
            box-shadow: 0 0 0 3px rgba(139, 92, 246, 0.1);
        }

        #staffForm input:hover {
            border-color: #D1D5DB;
        }

        #staffForm input::placeholder {
            color: #9CA3AF;
        }


        /* Responsive adjustments */
        @media (max-width: 640px) {
            #staffForm {
                padding: 1rem;
            }

            #staffForm input {
                padding: 0.625rem 0.875rem;
                font-size: 0.95rem;
            }
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-6);
        }

        .dashboard-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark-lilac);
            margin: 0;
        }


        body.cs-sidebar-collapsed .dashboard-container {
            margin-left: var(--sidebar-collapsed-width);
        }



        .stat-card {
            background: white;
            border-radius: var(--radius-md);
            padding: var(--spacing-6);
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            border-left: 4px solid transparent;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }





        .stat-icon {
            position: absolute;
            top: var(--spacing-6);
            right: var(--spacing-6);
            width: 48px;
            height: 48px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.9;
        }



        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: var(--spacing-3);
            color: var(--gray-900);
        }





        .staff-body {
            background-color: var(--white);
            padding: var(--spacing-6);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow);
            border-top-left-radius: 0;
            border-top-right-radius: 0;
        }

        /* Update the search-filter-container styling */
        .search-filter-container {
            display: flex;
            gap: var(--spacing-4);
            flex-wrap: wrap;
            padding: var(--spacing-4);
            background-color: var(--gray-50);
            border-radius: var(--radius);
            margin-bottom: var(--spacing-6);
        }

        /* Update the staff grid container */
        .staff-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: var(--spacing-6);
            padding: var(--spacing-4);
        }
    </style>
</head>

<body
    class="<?php echo isset($_SESSION['sidebar_collapsed']) && $_SESSION['sidebar_collapsed'] ? 'cs-sidebar-collapsed' : ''; ?>">
    <?php include '../sidebar/sidebar.php'; ?>

    <div class="content">
        <div class="dashboard-container">
            <div class="dashboard-header">
                <h1 class="dashboard-title">Staff Management</h1>
            </div>
        </div>
        <!-- Stats Row -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-md"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-value" id="dentist-count">0</div>
                    <div class="stat-label">Dentists</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-nurse"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-value" id="assistant-count">0</div>
                    <div class="stat-label">Assistants</div>
                </div>
            </div>

            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-value" id="intern-count">0</div>
                    <div class="stat-label">Interns</div>
                </div>
            </div>
        </div>




        <div class="header-container"
            style="border-bottom: 0.1rem solid #cccccc; margin-bottom: 0;  border-bottom-left-radius: 0;   border-bottom-right-radius: 0;">
            <div class="text-staff">
                <h1>Staff List</h1>
            </div>
            <div class="addstaff-container">
                <button class="addstaff-button" id="openAddModal">
                    <i class="fas fa-plus"></i> Add New Staff
                </button>
            </div>
            <div class="modal fade" id="staffModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-primary text-white">
                            <h5 div id="addStaffModal" class="modal-title">Add New Staff</h5>
                        </div>
                        <div class="modal-body">
                            <form id="staffForm">
                                <div class="mb-3">
                                    <label for="staffName">Name:</label>
                                    <input type="text" id="staffName" name="staffName" required>
                                </div>
                                <div class="mb-3">
                                    <label for="staffRole">Role:</label>
                                    <select id="staffRole" name="staffRole" class="form-control" required>
                                        <option value="" disabled selected>Select a role</option>
                                        <option value="dentist">Dentist</option>
                                        <option value="assistant">Assistant</option>
                                        <option value="intern">Intern</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="staffEmail">Email:</label>
                                    <input type="email" id="staffEmail" name="staffEmail" required>
                                </div>
                            </form>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" id="saveEventBtn" class="btn btn-primary">Add Staff</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="staff-body">
            <!-- Search and Filter Container -->
            <div class="search-filter-container">
                <div class="search-wrapper">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="staffSearch" class="search-input" placeholder="Search staff by name...">
                </div>

                <select class="filter-select" id="roleFilter">
                    <option value="all">All Roles</option>
                    <option value="dentist">Dentists</option>
                    <option value="assistant">Assistants</option>
                    <option value="intern">Interns</option>
                </select>

                <button class="addstaff-button" id="exportExcel" style="background: #4CAF50;">
                    <i class="fas fa-file-excel"></i> Excel
                </button>

                <button class="addstaff-button" id="exportPDF" style="background: #f44336;">
                    <i class="fas fa-file-pdf"></i> PDF
                </button>

                <div class="view-toggle">
                    <button class="toggle-btn active" id="cardView">
                        <i class="fas fa-th"></i> Cards
                    </button>
                    <button class="toggle-btn" id="tableView">
                        <i class="fas fa-list"></i> Table
                    </button>
                </div>

                <!-- Add this right after the view-toggle div -->
                <div class="deleted-toggle">
                    <label for="showDeletedToggle" class="toggle-label">Show Removed Staff</label>
                    <label class="toggle-switch">
                        <input type="checkbox" id="showDeletedToggle">
                        <span class="toggle-slider"></span>
                    </label>
                </div>
            </div>

            <!-- Staff Grid (Card View) -->
            <div class="staff-grid" id="staffCardsContainer">
                <!-- Cards will be populated via JavaScript -->
            </div>

            <!-- Staff Table (Hidden initially) -->
            <div class="staff-table-container" id="staffTableContainer" style="display: none;">
                <table class="staff-table">
                    <thead>
                        <tr>
                            <th>Photo</th>
                            <th>Staff Name</th>
                            <th>Gender</th>
                            <th>Contact</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="staffTableBody">
                        <!-- Table rows will be populated via JavaScript -->
                    </tbody>
                </table>
            </div>

            <!-- Empty State (shown when no staff found) -->
            <div class="empty-state" id="emptyState" style="display: none;">
                <i class="fas fa-users-slash"></i>
                <h3>No staff members found</h3>
                <p>Try adjusting your search criteria or add new staff members</p>
            </div>
        </div>
    </div>
    <!-- Add Staff Modal -->
    <div class="modal" id="addStaffModal">
        <div class="modal-backdrop"></div>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Staff Member</h5>
                    <button type="button" class="close-modal" id="closeAddModal">&times;</button>
                </div>

                <form id="addStaffForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <!-- Profile Image Upload -->
                        <div class="profile-view-header text-center mb-4">
                            <div class="profile-avatar mx-auto mb-3" id="imagePreview">
                                <i class="fas fa-user"></i>
                            </div>
                            <input type="file" id="staffImage" name="staff_image" accept="image/*"
                                style="display: none;">
                            <button type="button" class="btn btn-secondary"
                                onclick="document.getElementById('staffImage').click();">
                                Upload Photo
                            </button>
                        </div>

                        <!-- Form Row - Role Selection -->
                        <div class="form-group">
                            <label for="role" class="form-label">Staff Role</label>
                            <select id="role" name="role" class="form-select" required>
                                <option value="" disabled selected>Select a role</option>
                                <option value="dentist">Dentist</option>
                                <option value="assistant">Assistant</option>
                                <option value="intern">Intern</option>
                            </select>
                        </div>

                        <!-- Form Row - Name Fields -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="firstName" class="form-label">First Name</label>
                                <input type="text" id="firstName" name="first_name" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="lastName" class="form-label">Last Name</label>
                                <input type="text" id="lastName" name="last_name" class="form-control" required>
                            </div>
                        </div>

                        <!-- Middle Name Field -->
                        <div class="form-group">
                            <label for="middleName" class="form-label">Middle Name (Optional)</label>
                            <input type="text" id="middleName" name="middle_name" class="form-control">
                        </div>

                        <!-- Form Row - Contact & Gender -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="gender" class="form-label">Gender</label>
                                <select id="gender" name="gender" class="form-select" required>
                                    <option value="" disabled selected>Select gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="contactNumber" class="form-label">Contact Number</label>
                                <input type="text" id="contactNumber" name="contact_number" class="form-control"
                                    required>
                            </div>
                        </div>

                        <!-- Email Field -->
                        <div class="form-group">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" required>
                        </div>

                        <!-- Password Fields -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="confirmPassword" class="form-label">Confirm Password</label>
                                <input type="password" id="confirmPassword" name="confirm_password" class="form-control"
                                    required>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-user-plus"></i> Add Staff
                        </button>
                    </div>

                    <input type="hidden" name="action" value="add">
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Staff Modal -->
    <div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editModalLabel">Edit Staff Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <form id="editStaffForm" enctype="multipart/form-data">
                    <div class="modal-body">
                        <input type="hidden" id="editId" name="staff_id">

                        <!-- Profile Image Upload -->
                        <div class="profile-view-header">
                            <div class="profile-avatar" id="editImagePreview">
                                <img id="currentStaffImage" src="" alt="Staff Photo">
                            </div>
                            <input type="file" id="editStaffImage" name="staff_image" accept="image/*"
                                style="display: none;">
                            <button type="button" class="btn btn-secondary"
                                onclick="document.getElementById('editStaffImage').click();">
                                Change Photo
                            </button>
                        </div>

                        <!-- Form Row - Role Selection -->
                        <div class="form-group">
                            <label for="editRole" class="form-label">Staff Role</label>
                            <select id="editRole" name="role" class="form-control" required>
                                <option value="" disabled>Select a role</option>
                                <option value="dentist">Dentist</option>
                                <option value="assistant">Assistant</option>
                                <option value="intern">Intern</option>
                            </select>
                        </div>

                        <!-- Form Row - Name Fields -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="editFirstName" class="form-label">First Name</label>
                                <input type="text" id="editFirstName" name="first_name" class="form-control" required>
                            </div>

                            <div class="form-group">
                                <label for="editLastName" class="form-label">Last Name</label>
                                <input type="text" id="editLastName" name="last_name" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="editLastName" class="form-label">Last Name</label>
                            <input type="text" id="editLastName" name="last_name" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label for="editMiddleName" class="form-label">Middle Name (Optional)</label>
                            <input type="text" id="editMiddleName" name="middle_name" class="form-control">
                        </div>

                        <!-- Form Row - Contact & Gender -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="editGender" class="form-label">Gender</label>
                                <select id="editGender" name="gender" class="form-select" required>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="editContactNumber" class="form-label">Contact Number</label>
                                <input type="text" id="editContactNumber" name="contact_number" class="form-control"
                                    required>
                            </div>
                        </div>

                        <!-- Email Field -->
                        <div class="form-group">
                            <label for="editEmail" class="form-label">Email Address</label>
                            <input type="email" id="editEmail" name="email" class="form-control" required>
                        </div>

                        <!-- Password Fields (optional for edit) -->
                        <div class="form-row">
                            <div class="form-group">
                                <label for="editPassword" class="form-label">New Password (leave blank to keep
                                    current)</label>
                                <input type="password" id="editPassword" name="password" class="form-control">
                            </div>

                            <div class="form-group">
                                <label for="editConfirmPassword" class="form-label">Confirm New Password</label>
                                <input type="password" id="editConfirmPassword" name="confirm_password"
                                    class="form-control">
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" id="cancelEdit">Cancel</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>

                    <input type="hidden" name="action" value="edit">
                </form>
            </div>
        </div>
    </div>

    <!-- View Staff Details Modal -->
    <div class="modal fade" id="viewStaffModal" tabindex="-1" aria-labelledby="viewModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="viewModalLabel">Staff Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="profile-view-header text-center mb-4">
                        <div class="profile-avatar mx-auto mb-3" id="viewStaffImage">
                            <img src="" alt="Staff Photo" class="rounded-circle" style="width: 150px; height: 150px; object-fit: cover;">
                        </div>
                        <h2 class="profile-name mb-2" id="viewStaffName"></h2>
                        <div class="profile-role badge bg-primary" id="viewStaffRole"></div>
                    </div>

                    <div class="profile-info">
                        <div class="info-group">
                            <div class="info-label">Gender</div>
                            <div class="info-value" id="viewStaffGender"></div>
                        </div>

                        <div class="info-group">
                            <div class="info-label">Contact</div>
                            <div class="info-value" id="viewStaffContact"></div>
                        </div>

                        <div class="info-group">
                            <div class="info-label">Email</div>
                            <div class="info-value" id="viewStaffEmail"></div>
                        </div>

                        <div class="info-group">
                            <div class="info-label">Join Date</div>
                            <div class="info-value" id="viewStaffJoinDate"></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="editFromViewBtn">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteStaffModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="confirm-icon">
                        <i class="fas fa-trash-alt"></i>
                    </div>
                    <div class="confirm-message">Are you sure you want to remove this staff member?</div>
                    <div class="confirm-details">This action will mark the staff as deleted, but the record will be
                        preserved for audit purposes. You can restore the staff record later if needed.</div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="fas fa-trash-alt"></i> Delete
                    </button>
                </div>

                <form id="deleteStaffForm" style="display: none;">
                    <input type="hidden" id="deleteStaffId" name="staff_id">
                    <input type="hidden" name="action" value="delete">
                </form>
            </div>
        </div>
    </div>

    <!-- Notification -->
    <div class="notification" id="notification">
        <div class="notification-icon">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="notification-message" id="notificationMessage"></div>
        <button class="notification-close" id="closeNotification">&times;</button>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner"></div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Initialize state variables
            let allStaff = [];
            let filteredStaff = [];
            let currentStaffId = null;
            const currentView = {
                mode: 'cards',
                filter: 'all',
                search: ''
            };

            // DOM Elements
            const staffCardsContainer = document.getElementById('staffCardsContainer');
            const staffTableContainer = document.getElementById('staffTableContainer');
            const staffTableBody = document.getElementById('staffTableBody');
            const emptyState = document.getElementById('emptyState');
            const dentistCount = document.getElementById('dentist-count');
            const assistantCount = document.getElementById('assistant-count');
            const internCount = document.getElementById('intern-count');
            const roleFilter = document.getElementById('roleFilter');
            const searchInput = document.getElementById('staffSearch');
            const cardViewBtn = document.getElementById('cardView');
            const tableViewBtn = document.getElementById('tableView');
            const loadingOverlay = document.getElementById('loadingOverlay');
            const notification = document.getElementById('notification');

            // Modal elements
            const addModal = document.getElementById('addStaffModal');
            const editModal = document.getElementById('editStaffModal');
            const viewModal = document.getElementById('viewStaffModal');
            const deleteModal = document.getElementById('deleteStaffModal');

            // Image preview for add staff
            document.getElementById('staffImage').addEventListener('change', function () {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        const preview = document.getElementById('imagePreview');
                        preview.innerHTML = `<img src="${e.target.result}" alt="Staff Preview">`;
                    }
                    reader.readAsDataURL(file);
                }
            });

            // Image preview for edit staff
            document.getElementById('editStaffImage').addEventListener('change', function () {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        document.getElementById('currentStaffImage').src = e.target.result;
                    }
                    reader.readAsDataURL(file);
                }
            });

            // Fetch staff data
            function fetchStaffData() {
                showLoading();
                const showDeleted = document.getElementById('showDeletedToggle').checked ? 1 : 0;

                fetch('staffs.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `action=fetch&show_deleted=${showDeleted}`
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            allStaff = data.data;
                            updateStats();
                            filterAndDisplayStaff();
                        } else {
                            showNotification(data.error || 'Failed to fetch staff data', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching staff data:', error);
                        showNotification('Error fetching staff data. Please try again.', 'error');
                    })
                    .finally(() => {
                        hideLoading();
                    });
            }

            // Update statistics
            function updateStats() {
                const counts = allStaff.reduce((acc, staff) => {
                    if (staff.role === 'dentist') acc.dentist++;
                    else if (staff.role === 'assistant') acc.assistant++;
                    else if (staff.role === 'intern') acc.intern++;
                    return acc;
                }, { dentist: 0, assistant: 0, intern: 0 });

                dentistCount.textContent = counts.dentist;
                assistantCount.textContent = counts.assistant;
                internCount.textContent = counts.intern;
            }

            // Filter and display staff
            function filterAndDisplayStaff() {
                // Filter by role and search term
                filteredStaff = allStaff.filter(staff => {
                    const roleMatch = currentView.filter === 'all' || staff.role === currentView.filter;
                    const searchMatch = staff.full_name.toLowerCase().includes(currentView.search) ||
                        staff.email.toLowerCase().includes(currentView.search);
                    return roleMatch && searchMatch;
                });

                // Display in current view mode
                if (currentView.mode === 'cards') {
                    displayStaffCards();
                } else {
                    displayStaffTable();
                }

                // Show/hide empty state
                emptyState.style.display = filteredStaff.length === 0 ? 'block' : 'none';
            }

            // Display staff cards
            function displayStaffCards() {
                staffCardsContainer.innerHTML = '';

                filteredStaff.forEach(staff => {
                    const card = createStaffCard(staff);
                    staffCardsContainer.appendChild(card);
                });
            }

            // Create staff card element
            function createStaffCard(staff) {
                const card = document.createElement('div');
                card.className = 'staff-card';
                if (staff.is_deleted) {
                    card.className += ' deleted-record';
                }
                card.dataset.id = staff.id;

                const roleIcon = {
                    'dentist': 'fa-user-md',
                    'assistant': 'fa-user-nurse',
                    'intern': 'fa-user-graduate'
                }[staff.role] || 'fa-user';

                const roleName = staff.role.charAt(0).toUpperCase() + staff.role.slice(1);

                let deletionInfo = '';
                if (staff.is_deleted) {
                    deletionInfo = `<div class="deletion-info">Removed: ${new Date(staff.deleted_at).toLocaleDateString()}</div>`;
                }

                card.innerHTML = `
        <div class="staff-card-header">
            <h3 class="staff-name">${staff.full_name}</h3>
            <p class="staff-specialty">${staff.email}</p>
            ${deletionInfo}
            <span class="staff-role">${roleName}</span>
        </div>
        <div class="staff-card-body">
            <div class="staff-info">
                <div class="info-item">
                    <i class="fas ${roleIcon}"></i>
                    <span>${roleName}</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-venus-mars"></i>
                    <span>${staff.gender}</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-phone"></i>
                    <span>${staff.contact}</span>
                </div>
                <div class="info-item">
                    <i class="fas fa-envelope"></i>
                    <span>${staff.email}</span>
                </div>
            </div>
        </div>
        <div class="staff-card-footer">
            <button class="staff-action action-view" data-id="${staff.id}">
                <i class="fas fa-eye"></i> View
            </button>
            ${staff.is_deleted ?
                        `<button class="staff-action action-restore" data-id="${staff.id}" style="background: #4CAF50;">
                    <i class="fas fa-undo"></i> Restore
                </button>` :
                        `<button class="staff-action action-edit" data-id="${staff.id}">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="staff-action action-delete" data-id="${staff.id}">
                    <i class="fas fa-trash-alt"></i> Remove
                </button>`
                    }
        </div>
    `;

                // Add event listeners to the card buttons
                card.querySelector('.action-view').addEventListener('click', () => openViewModal(staff.id));

                if (staff.is_deleted) {
                    card.querySelector('.action-restore').addEventListener('click', () => restoreStaff(staff.id));
                } else {
                    card.querySelector('.action-edit').addEventListener('click', () => openEditModal(staff.id));
                    card.querySelector('.action-delete').addEventListener('click', () => openDeleteModal(staff.id));
                }

                return card;
            }


            function restoreStaff(staffId) {
                if (!confirm('Are you sure you want to restore this staff member?')) {
                    return;
                }

                showLoading();

                const formData = new FormData();
                formData.append('id', staffId);

                fetch('restore_staff.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');
                            fetchStaffData();
                        } else {
                            showNotification(data.error || 'An error occurred', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error restoring staff:', error);
                        showNotification('An error occurred. Please try again.', 'error');
                    })
                    .finally(() => {
                        hideLoading();
                    });
            }

            // Display staff table
            function displayStaffTable() {
                staffTableBody.innerHTML = '';

                filteredStaff.forEach(staff => {
                    const row = document.createElement('tr');

                    const roleName = staff.role.charAt(0).toUpperCase() + staff.role.slice(1);

                    row.innerHTML = `
                        <td>
                            <div class="table-avatar">
                                <img src="${staff.image}" alt="${staff.full_name}" onerror="this.src='uploads/default.jpg'">
                            </div>
                        </td>
                        <td>${staff.full_name}</td>
                        <td>${staff.gender}</td>
                        <td>${staff.contact}</td>
                        <td>${staff.email}</td>
                        <td>${roleName}</td>
                        <td>
                            <div class="table-actions">
                                <button class="staff-action action-view" data-id="${staff.id}">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button class="staff-action action-edit" data-id="${staff.id}">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <button class="staff-action action-delete" data-id="${staff.id}">
                                    <i class="fas fa-trash-alt"></i>
                                </button>
                            </div>
                        </td>
                    `;

                    // Add event listeners to row buttons
                    row.querySelector('.action-view').addEventListener('click', () => openViewModal(staff.id));
                    row.querySelector('.action-edit').addEventListener('click', () => openEditModal(staff.id));
                    row.querySelector('.action-delete').addEventListener('click', () => openDeleteModal(staff.id));

                    staffTableBody.appendChild(row);
                });
            }


            function deleteStaff(staffId) {
                showLoading();

                const formData = new FormData();
                formData.append('id', staffId);

                fetch('soft_delete_staffs.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification('Staff member removed successfully', 'success');
                            deleteModal.classList.remove('show');
                            fetchStaffData();
                        } else {
                            showNotification(data.message || 'An error occurred', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showNotification('Something went wrong. Please try again.', 'error');
                    })
                    .finally(() => {
                        hideLoading();
                    });
            }

            // Open view staff modal
            function openViewModal(staffId) {
                const staff = getStaffById(staffId);
                if (!staff) return;

                document.getElementById('viewStaffName').textContent = staff.full_name;
                document.getElementById('viewStaffRole').textContent = staff.role.charAt(0).toUpperCase() + staff.role.slice(1);
                document.getElementById('viewStaffGender').textContent = staff.gender;
                document.getElementById('viewStaffContact').textContent = staff.contact;
                document.getElementById('viewStaffEmail').textContent = staff.email;
                document.getElementById('viewStaffJoinDate').textContent = new Date(staff.created_at).toLocaleDateString();

                const staffImage = document.getElementById('viewStaffImage');
                staffImage.innerHTML = `<img src="${staff.image}" alt="${staff.full_name}" onerror="this.src='uploads/default.jpg'">`;

                currentStaffId = staffId;
                viewModal.classList.add('show');

                // Set up edit button in view modal
                document.getElementById('editFromViewBtn').onclick = function () {
                    viewModal.classList.remove('show');
                    openEditModal(staffId);
                };
            }

            // Open edit staff modal
            function openEditModal(staffId) {
                const staff = getStaffById(staffId);
                if (!staff) return;

                document.getElementById('editId').value = staff.id;
                document.getElementById('editRole').value = staff.role;
                document.getElementById('editFirstName').value = staff.first_name;
                document.getElementById('editLastName').value = staff.last_name;
                document.getElementById('editMiddleName').value = staff.middle_name || '';
                document.getElementById('editGender').value = staff.gender;
                document.getElementById('editContactNumber').value = staff.contact;
                document.getElementById('editEmail').value = staff.email;
                document.getElementById('editPassword').value = '';
                document.getElementById('editConfirmPassword').value = '';

                // Set staff image
                document.getElementById('currentStaffImage').src = staff.image || 'uploads/default.jpg';

                editModal.classList.add('show');
            }

            // Open delete staff modal
            function openDeleteModal(staffId) {
                const staff = getStaffById(staffId);
                if (!staff) return;

                document.getElementById('deleteStaffId').value = staffId;
                const deleteModal = new bootstrap.Modal(document.getElementById('deleteStaffModal'));
                deleteModal.show();
            }

            // Helper function to get staff by ID
            function getStaffById(id) {
                return allStaff.find(staff => staff.id == id);
            }

            // Show notification
            function showNotification(message, type = 'success') {
                const notificationElem = document.getElementById('notification');
                const messageElem = document.getElementById('notificationMessage');
                const iconElem = notificationElem.querySelector('.notification-icon i');

                iconElem.className = type === 'success' ? 'fas fa-check-circle' : 'fas fa-exclamation-circle';
                messageElem.textContent = message;

                if (type === 'success') {
                    notificationElem.style.backgroundColor = '#E7F8F0';
                    iconElem.style.color = '#6ACE70';
                } else {
                    notificationElem.style.backgroundColor = '#FFF0F0';
                    iconElem.style.color = '#F44336';
                }

                notificationElem.classList.remove('hide');
                notificationElem.style.display = 'flex';

                setTimeout(() => {
                    notificationElem.classList.add('hide');
                    setTimeout(() => {
                        notificationElem.style.display = 'none';
                    }, 300);
                }, 5000);
            }

            // Show/hide loading overlay
            function showLoading() {
                loadingOverlay.style.display = 'flex';
            }

            function hideLoading() {
                loadingOverlay.style.display = 'none';
            }

            // Form submission handlers
            document.getElementById('addStaffForm').addEventListener('submit', function (e) {
                e.preventDefault();
                submitForm(this, 'add');
            });

            document.getElementById('editStaffForm').addEventListener('submit', function (e) {
                e.preventDefault();
                submitForm(this, 'edit');
            });

            document.getElementById('showDeletedToggle').addEventListener('change', function () {
                fetchStaffData();
            });

            document.getElementById('confirmDelete').addEventListener('click', function () {
                const staffId = document.getElementById('deleteStaffId').value;
                deleteStaff(staffId);
            });

            function submitForm(form, action) {
                showLoading();

                const formData = new FormData(form);

                fetch('staffs.php', {
                    method: 'POST',
                    body: formData
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            showNotification(data.message, 'success');

                            // Close modals
                            document.querySelectorAll('.modal').forEach(modal => {
                                modal.classList.remove('show');
                            });

                            // Reset add form
                            if (action === 'add') {
                                form.reset();
                                document.getElementById('imagePreview').innerHTML = '<i class="fas fa-user"></i>';
                            }

                            // Refresh staff data
                            fetchStaffData();
                        } else {
                            showNotification(data.error || 'An error occurred', 'error');
                        }
                    })
                    .catch(error => {
                        console.error('Error submitting form:', error);
                        showNotification('An error occurred. Please try again.', 'error');
                    })
                    .finally(() => {
                        hideLoading();
                    });
            }

            // View toggle handlers
            cardViewBtn.addEventListener('click', function () {
                currentView.mode = 'cards';
                this.classList.add('active');
                tableViewBtn.classList.remove('active');
                staffCardsContainer.style.display = 'grid';
                staffTableContainer.style.display = 'none';
            });

            tableViewBtn.addEventListener('click', function () {
                currentView.mode = 'table';
                this.classList.add('active');
                cardViewBtn.classList.remove('active');
                staffCardsContainer.style.display = 'none';
                staffTableContainer.style.display = 'block';
            });

            // Modal handlers
            document.getElementById('openAddModal').addEventListener('click', function () {
                document.getElementById('addStaffForm').reset();
                document.getElementById('imagePreview').innerHTML = '<i class="fas fa-user"></i>';
                const addModal = new bootstrap.Modal(document.getElementById('addStaffModal'));
                addModal.show();
            });

            // Close modal buttons
            document.querySelectorAll('.close-modal, #cancelAdd, #cancelEdit, #cancelDelete, #closeViewBtn').forEach(btn => {
                btn.addEventListener('click', function () {
                    this.closest('.modal').classList.remove('show');
                });
            });

            // Close notification button
            document.getElementById('closeNotification').addEventListener('click', function () {
                notification.classList.add('hide');
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 300);
            });

            // Search and filter handlers
            searchInput.addEventListener('input', function () {
                currentView.search = this.value.toLowerCase().trim();
                filterAndDisplayStaff();
            });

            roleFilter.addEventListener('change', function () {
                currentView.filter = this.value;
                filterAndDisplayStaff();
            });

            // Export buttons
            document.getElementById('exportExcel').addEventListener('click', function () {
                alert('Export to Excel functionality will be implemented here');
                // Implementation for Excel export would go here
            });

            document.getElementById('exportPDF').addEventListener('click', function () {
                alert('Export to PDF functionality will be implemented here');
                // Implementation for PDF export would go here
            });

            // Initialize by loading staff data
            fetchStaffData();
        });
        const modal = document.getElementById('addStaffModal');
        const openBtn = document.getElementById('openAddModal');
        const closeBtn = document.getElementById('closeAddModal');

        document.addEventListener('DOMContentLoaded', function () {
            const addStaffButton = document.querySelector('.addstaff-button');
            const staffModalElement = document.getElementById('staffModal');
            const staffModal = new bootstrap.Modal(staffModalElement);

            addStaffButton.addEventListener('click', function (event) {
                event.preventDefault(); // Optional: prevent default behavior
                staffModal.show();
            });
        });
    </script>
</body>

</html>