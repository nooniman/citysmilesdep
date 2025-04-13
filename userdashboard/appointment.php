<?php
session_start();
include '../database.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$page_errors = [];
$success_message = '';

// Get user data
try {
    $user_query = "SELECT first_name, last_name, email, contact FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param('i', $user_id);
    $user_stmt->execute();
    $user = $user_stmt->get_result()->fetch_assoc();
} catch (Exception $e) {
    $page_errors[] = "Could not load user data: " . $e->getMessage();
    $user = ['first_name' => '', 'last_name' => '', 'email' => '', 'contact' => ''];
}

// Check if user has a patient profile
try {
    $profile_query = "SELECT patient_info_id FROM patients WHERE user_id = ?";
    $profile_stmt = $conn->prepare($profile_query);
    $profile_stmt->bind_param('i', $user_id);
    $profile_stmt->execute();
    $result = $profile_stmt->get_result();
    $has_profile = $result->num_rows > 0;
    
    if ($has_profile) {
        $patient_id = $result->fetch_assoc()['patient_info_id'];
    }
} catch (Exception $e) {
    $page_errors[] = "Error checking patient profile: " . $e->getMessage();
    $has_profile = false;
}

// Process patient profile creation if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create_profile'])) {
    try {
        $first_name = $user['first_name'] ?? $_POST['first_name'];
        $last_name = $user['last_name'] ?? $_POST['last_name'];
        $gender = $_POST['gender'] ?? '';
        $birth_date = $_POST['birth_date'] ?? '';
        $contact = $user['contact'] ?? $_POST['contact'];
        $email = $user['email'] ?? $_POST['email'];
        $address = $_POST['address'] ?? '';
        
        // Validate required fields
        if (empty($gender) || empty($birth_date) || empty($address)) {
            throw new Exception("Please fill in all required fields.");
        }
        
        $insert = "INSERT INTO patients 
                  (user_id, first_name, last_name, gender, birth_date, contact_number, email, address, created_at, updated_at)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        $stmt = $conn->prepare($insert);
        $stmt->bind_param('isssssss', $user_id, $first_name, $last_name, $gender, $birth_date, $contact, $email, $address);
        
        if ($stmt->execute()) {
            $patient_id = $conn->insert_id;
            $has_profile = true;
            $success_message = "Patient profile created successfully!";
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }
    } catch (Exception $e) {
        $page_errors[] = "Profile creation failed: " . $e->getMessage();
    }
}

// Process appointment booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['book_appointment'])) {
    try {
        // Check if patient has a profile
        if (!isset($patient_id) || !$has_profile) {
            throw new Exception("Please create your patient profile first.");
        }
        
        $service_id = $_POST['service_id'] ?? 0;
        $dentist_id = !empty($_POST['dentist_id']) ? intval($_POST['dentist_id']) : null;
        $selected_date = $_POST['selected_date'] ?? '';
        $selected_time = $_POST['selected_time'] ?? '';
        $notes = $_POST['notes'] ?? '';
        $appointment_for = $_POST['appointment_for'] ?? 'self';
        $family_member_id = null;
        
        // Validate appointment data
        if (empty($service_id) || empty($selected_date) || empty($selected_time)) {
            throw new Exception("Please fill in all required appointment details.");
        }
        
        if ($appointment_for === 'family_member') {
            $family_member_id = $_POST['family_member_id'] ?? null;
            if (empty($family_member_id)) {
                throw new Exception("Please select a family member.");
            }
        }
        
        // Insert appointment
        $column = $dentist_id ? "staff_id" : "dentist_id";
        $sql = "INSERT INTO appointments 
                (patient_id, family_member_id, {$column}, service_id, appointment_date, 
                appointment_time, status, notes, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('iiisss', $patient_id, $family_member_id, $dentist_id, $service_id, 
                          $selected_date, $selected_time, $notes);
        
        if ($stmt->execute()) {
            $success_message = "Your appointment has been successfully scheduled! You will receive a confirmation soon.";
        } else {
            throw new Exception("Database error: " . $stmt->error);
        }
    } catch (Exception $e) {
        $page_errors[] = "Appointment booking failed: " . $e->getMessage();
    }
}

// Get previous appointments
try {
    $appointments_query = "
        SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, 
               s.name AS service_name, 
               CONCAT(u.first_name, ' ', u.last_name) AS dentist_name
        FROM appointments a
        JOIN services s ON a.service_id = s.services_id
        LEFT JOIN users u ON a.staff_id = u.id
        JOIN patients p ON a.patient_id = p.patient_info_id
        WHERE p.user_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC
        LIMIT 5
    ";
    $appointments_stmt = $conn->prepare($appointments_query);
    $appointments_stmt->bind_param('i', $user_id);
    $appointments_stmt->execute();
    $appointments = $appointments_stmt->get_result();
} catch (Exception $e) {
    $page_errors[] = "Could not load appointment history: " . $e->getMessage();
    // Create empty result set for appointments
    $appointments = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
    };
}

// Get all dentists for the dropdown
try {
    $dentists_query = "SELECT id, first_name, last_name FROM users WHERE role = 'dentist' ORDER BY last_name, first_name";
    $dentists_result = $conn->query($dentists_query);
} catch (Exception $e) {
    $page_errors[] = "Could not load dentists: " . $e->getMessage();
    $dentists_result = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
    };
}

// Get all services
try {
    $services_query = "SELECT services_id, name, description, duration_minutes FROM services ORDER BY name";
    $services_result = $conn->query($services_query);
} catch (Exception $e) {
    $page_errors[] = "Could not load services: " . $e->getMessage();
    $services_result = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
    };
}

// Get family members
try {
    $family_query = "SELECT member_id, first_name, last_name, relationship FROM family_members WHERE user_id = ? ORDER BY first_name, last_name";
    $family_stmt = $conn->prepare($family_query);
    $family_stmt->bind_param("i", $user_id);
    $family_stmt->execute();
    $family_result = $family_stmt->get_result();
} catch (Exception $e) {
    $page_errors[] = "Could not load family members: " . $e->getMessage();
    $family_result = new class {
        public $num_rows = 0;
        public function fetch_assoc() { return null; }
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Appointment | City Smiles Dental</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        :root {
            --primary: #9d7ded;
            --primary-dark: #8a65e0;
            --success: #6ace70;
            --light: #f8f9fa;
            --dark: #343a40;
            --border-radius: 10px;
            --box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        
        body {
            background-color: #f5f7fa;
            font-family: 'Inter', sans-serif;
        }
        
        .content-container {
            padding: 2rem;
            margin-left: 260px;
        }
        
        .section-header {
            color: var(--primary-dark);
            font-weight: 600;
        }
        
        .card {
            border: none;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            margin-bottom: 1.5rem;
            background-color: #fff;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0,0,0,.1);
            padding: 1.25rem;
        }
        
        .card-body {
            padding: 1.5rem;
        }
        
        .form-control:focus {
            border-color: var(--primary);
            box-shadow: 0 0 0 0.2rem rgba(157, 125, 237, 0.25);
        }
        
        .btn-primary {
            background-color: var(--primary);
            border-color: var(--primary);
        }
        
        .btn-primary:hover {
            background-color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .btn-success {
            background-color: var(--success);
            border-color: var(--success);
        }
        
        /* Appointment form steps */
        .appointment-step {
            display: none;
        }
        
        .appointment-step.active {
            display: block;
        }
        
        .step-indicator {
            margin-bottom: 2rem;
        }
        
        .step-indicator .step {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            color: #6c757d;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        
        .step-indicator .step.active {
            background-color: var(--primary);
            color: white;
        }
        
        .step-indicator .step-title {
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .step-indicator .active .step-title {
            color: var(--primary-dark);
            font-weight: 500;
        }
        
        /* Date picker styling */
        .flatpickr-day.available {
            background-color: rgba(106, 206, 112, 0.1);
            border-color: rgba(106, 206, 112, 0.2);
        }
        
        .flatpickr-day.available:hover {
            background-color: rgba(106, 206, 112, 0.3);
        }
        
        /* Time slots styling */
        .time-slots-container {
            max-height: 300px;
            overflow-y: auto;
        }
        
        .time-slot {
            padding: 10px 15px;
            border: 1px solid #ddd;
            border-radius: 8px;
            margin-bottom: 10px;
            cursor: pointer;
            transition: all 0.2s;
            text-align: center;
        }
        
        .time-slot:hover {
            border-color: var(--primary);
            background-color: rgba(157, 125, 237, 0.05);
        }
        
        .time-slot.selected {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary-dark);
        }
        
        /* Previous appointments styling */
        .appointment-list {
            list-style: none;
            padding: 0;
        }
        
        .appointment-item {
            padding: 15px;
            border-left: 4px solid #ddd;
            margin-bottom: 15px;
            background-color: #f8f9fa;
            border-radius: 4px;
        }
        
        .appointment-item.pending {
            border-left-color: #ffc107;
        }
        
        .appointment-item.confirmed {
            border-left-color: #0d6efd;
        }
        
        .appointment-item.completed {
            border-left-color: #198754;
        }
        
        .appointment-item.cancelled {
            border-left-color: #dc3545;
            opacity: 0.7;
        }
        
        .spinner-container {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 150px;
        }
        
        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.8);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            display: none;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid var(--primary);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Error and success styling */
        .alert {
            border-radius: var(--border-radius);
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include '../userdashboard/user_sidebar.php'; ?>
    
    <div class="loading-overlay" id="loadingOverlay">
        <div class="loading-spinner"></div>
    </div>
    
    <div class="content-container">
        <div class="container-fluid">
            <h1 class="mb-4 section-header">Book an Appointment</h1>
            
            <!-- Alert messages -->
            <?php if (!empty($page_errors)): ?>
                <?php foreach($page_errors as $error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i> <?php echo htmlspecialchars($error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> <?php echo htmlspecialchars($success_message); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <div class="row">
                <div class="col-md-8">
                    <?php if (!$has_profile): ?>
                    <!-- Patient Profile Form -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="fas fa-user-circle me-2"></i>Create Patient Profile</h4>
                            <p class="text-muted mb-0 small">Complete your profile to book appointments</p>
                        </div>
<<<<<<< HEAD

                        <div class="mb-3">
                            <label class="form-label">This appointment is for:</label>
                            <select class="form-select" id="appointment-for" name="appointment_for" required>
                                <option value="self">Myself</option>
                                <option value="family_member">Others</option>
                            </select>
                        </div>

                        <div class="mb-3" id="family-member-select" style="display: none;">
                            <label class="form-label">Select Patient:</label>
                            <div class="d-flex">
                                <select class="form-select me-2" id="family-member-id" name="family_member_id">
                                    <option value="">-- Select a Patient --</option>
                                    <?php
                                    // Get family members for this user
                                    $family_query = "SELECT member_id, first_name, last_name, relationship FROM family_members WHERE user_id = ? ORDER BY first_name, last_name";
                                    $family_stmt = $conn->prepare($family_query);
                                    $family_stmt->bind_param("i", $user_id);
                                    $family_stmt->execute();
                                    $family_result = $family_stmt->get_result();

                                    while ($member = $family_result->fetch_assoc()): ?>
                                        <option value="<?php echo $member['member_id']; ?>">
                                            <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name'] . ' (' . $member['relationship'] . ')'); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <a href="family_members.php" class="btn btn-outline-primary">
                                    <i class="fas fa-plus"></i> Add
                                </a>
=======
                        <div class="card-body">
                            <form id="patientProfileForm" method="POST" action="">
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="first_name" class="form-label">First Name</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user['first_name'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="last_name" class="form-label">Last Name</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user['last_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="gender" class="form-label">Gender</label>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <option value="" disabled selected>Select gender</option>
                                            <option value="male">Male</option>
                                            <option value="female">Female</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="birth_date" class="form-label">Date of Birth</label>
                                        <input type="date" class="form-control" id="birth_date" name="birth_date" required>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label for="contact" class="form-label">Contact Number</label>
                                        <input type="tel" class="form-control" id="contact" name="contact" value="<?php echo htmlspecialchars($user['contact'] ?? ''); ?>" required>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label for="email" class="form-label">Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="address" class="form-label">Address</label>
                                    <textarea class="form-control" id="address" name="address" rows="2" required></textarea>
                                </div>
                                
                                <div class="text-end mt-4">
                                    <button type="submit" name="create_profile" class="btn btn-primary">
                                        <i class="fas fa-save me-2"></i>Save Profile
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    <?php else: ?>
                    
                    <!-- Appointment Booking Form (Multi-step) -->
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0"><i class="fas fa-calendar-plus me-2"></i>Book Your Appointment</h4>
                        </div>
                        <div class="card-body">
                            <div class="row step-indicator mb-4">
                                <div class="col text-center">
                                    <div class="step active mx-auto" id="step-indicator-1">1</div>
                                    <div class="step-title">Service</div>
                                </div>
                                <div class="col text-center">
                                    <div class="step mx-auto" id="step-indicator-2">2</div>
                                    <div class="step-title">Dentist</div>
                                </div>
                                <div class="col text-center">
                                    <div class="step mx-auto" id="step-indicator-3">3</div>
                                    <div class="step-title">Date & Time</div>
                                </div>
                                <div class="col text-center">
                                    <div class="step mx-auto" id="step-indicator-4">4</div>
                                    <div class="step-title">Confirm</div>
                                </div>
>>>>>>> 10f8150e9310af455e8f57eaa231d6f0861eb37c
                            </div>

                            <form id="appointmentForm" method="POST" action="">
                                <!-- Step 1: Service Selection -->
                                <div class="appointment-step active" id="step-1">
                                    <h5 class="mb-4">Select a Service</h5>
                                    
                                    <div class="form-group mb-4">
                                        <label for="appointment_for" class="form-label">Who is this appointment for?</label>
                                        <div class="d-flex">
                                            <div class="form-check me-4">
                                                <input class="form-check-input" type="radio" name="appointment_for" id="for_self" value="self" checked>
                                                <label class="form-check-label" for="for_self">Myself</label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="appointment_for" id="for_family" value="family_member" <?php echo ($family_result->num_rows == 0) ? 'disabled' : ''; ?>>
                                                <label class="form-check-label" for="for_family">Family Member</label>
                                            </div>
                                        </div>
                                        <?php if ($family_result->num_rows == 0): ?>
                                            <div class="small text-muted mt-1">
                                                <i class="fas fa-info-circle me-1"></i>
                                                No family members added. You can add family members in your profile.
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="form-group mb-4" id="family_member_section" style="display: none;">
                                        <label for="family_member_id" class="form-label">Select Family Member</label>
                                        <select class="form-select" id="family_member_id" name="family_member_id">
                                            <option value="" selected disabled>-- Choose a family member --</option>
                                            <?php while($family_member = $family_result->fetch_assoc()): ?>
                                                <option value="<?php echo $family_member['member_id']; ?>">
                                                    <?php echo htmlspecialchars($family_member['first_name'] . ' ' . $family_member['last_name'] . ' (' . $family_member['relationship'] . ')'); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group mb-4">
                                        <label for="service_id" class="form-label">Service</label>
                                        <select class="form-select" id="service_id" name="service_id" required>
                                            <option value="" selected disabled>-- Select a service --</option>
                                            <?php while($service = $services_result->fetch_assoc()): ?>
                                                <option value="<?php echo $service['services_id']; ?>" 
                                                        data-duration="<?php echo $service['duration_minutes']; ?>">
                                                    <?php echo htmlspecialchars($service['name']); ?> 
                                                    (<?php echo $service['duration_minutes']; ?> min)
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="text-end mt-4">
                                        <button type="button" class="btn btn-primary next-step" data-next="2">
                                            Next <i class="fas fa-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Step 2: Dentist Selection -->
                                <div class="appointment-step" id="step-2">
                                    <h5 class="mb-4">Select a Dentist (optional)</h5>
                                    
                                    <div class="form-group mb-4">
                                        <label for="dentist_id" class="form-label">Preferred Dentist</label>
                                        <select class="form-select" id="dentist_id" name="dentist_id">
                                            <option value="" selected>No preference (assign any available dentist)</option>
                                            <?php while($dentist = $dentists_result->fetch_assoc()): ?>
                                                <option value="<?php echo $dentist['id']; ?>">
                                                    Dr. <?php echo htmlspecialchars($dentist['first_name'] . ' ' . $dentist['last_name']); ?>
                                                </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <div class="small text-muted mt-1">
                                            <i class="fas fa-info-circle me-1"></i>
                                            Selecting a specific dentist may limit available appointment times.
                                        </div>
                                    </div>
                                    
                                    <div class="text-end mt-4">
                                        <button type="button" class="btn btn-outline-secondary prev-step me-2" data-prev="1">
                                            <i class="fas fa-arrow-left me-2"></i> Back
                                        </button>
                                        <button type="button" class="btn btn-primary next-step" data-next="3">
                                            Next <i class="fas fa-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Step 3: Date and Time Selection -->
                                <div class="appointment-step" id="step-3">
                                    <h5 class="mb-4">Select Date & Time</h5>
                                    
                                    <div class="row">
                                        <div class="col-md-6 mb-4">
                                            <label for="date_picker" class="form-label">Appointment Date</label>
                                            <input type="text" class="form-control" id="date_picker" name="selected_date" placeholder="Select a date" required>
                                            <div id="date-error" class="invalid-feedback"></div>
                                        </div>
                                        
                                        <div class="col-md-6 mb-4">
                                            <label for="time-slots-container" class="form-label">Available Time Slots</label>
                                            <div id="time-slots-container" class="time-slots-container p-2 border rounded">
                                                <div class="text-center text-muted py-4">
                                                    <i class="far fa-calendar-alt fa-2x mb-2"></i>
                                                    <p>Please select a date first</p>
                                                </div>
                                            </div>
                                            <input type="hidden" id="selected_time" name="selected_time" required>
                                        </div>
                                    </div>
                                    
                                    <div class="text-end mt-4">
                                        <button type="button" class="btn btn-outline-secondary prev-step me-2" data-prev="2">
                                            <i class="fas fa-arrow-left me-2"></i> Back
                                        </button>
                                        <button type="button" class="btn btn-primary next-step" data-next="4" id="date-time-next">
                                            Next <i class="fas fa-arrow-right ms-2"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Step 4: Confirmation -->
                                <div class="appointment-step" id="step-4">
                                    <h5 class="mb-4">Confirm Your Appointment</h5>
                                    
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Service:</label>
                                                <div id="confirm-service" class="form-control-plaintext">-</div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Dentist:</label>
                                                <div id="confirm-dentist" class="form-control-plaintext">-</div>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Date:</label>
                                                <div id="confirm-date" class="form-control-plaintext">-</div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Time:</label>
                                                <div id="confirm-time" class="form-control-plaintext">-</div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="notes" class="form-label">Additional Notes (optional)</label>
                                        <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Any special requests or information you'd like us to know..."></textarea>
                                    </div>
                                    
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        By booking this appointment, you confirm that the information provided is correct. 
                                        We'll send you a confirmation when your appointment is approved.
                                    </div>
                                    
                                    <div class="text-end mt-4">
                                        <button type="button" class="btn btn-outline-secondary prev-step me-2" data-prev="3">
                                            <i class="fas fa-arrow-left me-2"></i> Back
                                        </button>
                                        <button type="submit" name="book_appointment" class="btn btn-success">
                                            <i class="fas fa-calendar-check me-2"></i> Book Appointment
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <?php endif; ?>
                </div>
                <div class="col-md-4">
                    <!-- Previous Appointments -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-history me-2"></i>Your Appointments</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($appointments->num_rows > 0): ?>
                                <ul class="appointment-list">
                                    <?php while($appointment = $appointments->fetch_assoc()): ?>
                                        <li class="appointment-item <?php echo strtolower($appointment['status']); ?>">
                                            <div class="d-flex justify-content-between">
                                                <span class="badge bg-<?php 
                                                    echo match($appointment['status']) {
                                                        'pending' => 'warning',
                                                        'confirmed' => 'primary',
                                                        'completed' => 'success',
                                                        'cancelled' => 'danger',
                                                        default => 'secondary'
                                                    };
                                                ?>"><?php echo ucfirst($appointment['status']); ?></span>
                                                <small class="text-muted">
                                                    <?php echo date('M d, Y', strtotime($appointment['appointment_date'])); ?>
                                                </small>
                                            </div>
                                            <h6 class="mt-2 mb-1"><?php echo htmlspecialchars($appointment['service_name']); ?></h6>
                                            <div class="d-flex justify-content-between text-muted small">
                                                <span>
                                                    <i class="far fa-clock me-1"></i>
                                                    <?php echo date('h:i A', strtotime($appointment['appointment_time'])); ?>
                                                </span>
                                                <?php if (!empty($appointment['dentist_name'])): ?>
                                                <span>
                                                    <i class="fas fa-user-md me-1"></i>
                                                    Dr. <?php echo htmlspecialchars($appointment['dentist_name']); ?>
                                                </span>
                                                <?php endif; ?>
                                            </div>
                                        </li>
                                    <?php endwhile; ?>
                                </ul>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="far fa-calendar-alt fa-3x mb-3 text-muted"></i>
                                    <p>No previous appointments found</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Information/Help Card -->
                    <div class="card mt-4">
                        <div class="card-header bg-light">
                            <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Help Information</h5>
                        </div>
                        <div class="card-body">
                            <h6><i class="fas fa-phone-alt me-2 text-primary"></i>Need Help?</h6>
                            <p>Call us at (062) 991-2345 for assistance with your appointment.</p>
                            
                            <h6 class="mt-3"><i class="fas fa-clock me-2 text-primary"></i>Clinic Hours</h6>
                            <p>Monday - Saturday: 9:00 AM - 6:00 PM</p>
                            
                            <h6 class="mt-3"><i class="fas fa-exclamation-triangle me-2 text-warning"></i>Cancellation Policy</h6>
                            <p class="mb-0">Please inform us at least 24 hours before your appointment if you need to cancel or reschedule.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script>
        $(document).ready(function() {
            // Initialize family member section toggle
            $('input[name="appointment_for"]').change(function() {
                if ($(this).val() === 'family_member') {
                    $('#family_member_section').slideDown();
                } else {
                    $('#family_member_section').slideUp();
                }
            });
            
            // Multi-step form navigation
            $('.next-step').click(function() {
                const currentStep = $(this).closest('.appointment-step');
                const nextStep = $(this).data('next');
                
                // Validate current step
                if (!validateStep(currentStep.attr('id'))) {
                    return false;
                }
                
                // If moving to step 3 (date/time), update available dates
                if (nextStep === 3) {
                    initDatePicker();
                }
                
                // If moving to step 4 (confirmation), update summary
                if (nextStep === 4) {
                    updateConfirmation();
                }
                
                // Hide current step and show next step
                currentStep.removeClass('active');
                $(`#step-${nextStep}`).addClass('active');
                
                // Update step indicators
                $('.step').removeClass('active');
                $(`#step-indicator-${nextStep}`).addClass('active');
            });
            
            $('.prev-step').click(function() {
                const currentStep = $(this).closest('.appointment-step');
                const prevStep = $(this).data('prev');
                
                // Hide current step and show previous step
                currentStep.removeClass('active');
                $(`#step-${prevStep}`).addClass('active');
                
                // Update step indicators
                $('.step').removeClass('active');
                $(`#step-indicator-${prevStep}`).addClass('active');
            });
            
            // Validate each step
            function validateStep(stepId) {
                switch(stepId) {
                    case 'step-1':
                        // Validate service selection
                        if ($('#service_id').val() === null || $('#service_id').val() === '') {
                            alert('Please select a service to continue.');
                            return false;
                        }
                        
                        // Validate family member selection if needed
                        if ($('#for_family').prop('checked') && 
                            ($('#family_member_id').val() === null || $('#family_member_id').val() === '')) {
                            alert('Please select a family member.');
                            return false;
                        }
                        return true;
                    
                    case 'step-2':
                        // No validation needed for dentist selection (optional)
                        return true;
                        
                    case 'step-3':
                        // Validate date and time selection
                        if ($('#date_picker').val() === '') {
                            alert('Please select an appointment date.');
                            return false;
                        }
                        
                        if ($('#selected_time').val() === '') {
                            alert('Please select an appointment time slot.');
                            return false;
                        }
                        return true;
                        
                    default:
                        return true;
                }
            }
            
            // Initialize date picker when we reach step 3
            function initDatePicker() {
                // Show loading overlay
                $('#loadingOverlay').show();
                
                // Get selected service and dentist
                const serviceId = $('#service_id').val();
                const dentistId = $('#dentist_id').val() || '';
                
                // First clear any existing date picker
                if (typeof datePicker !== 'undefined') {
                    datePicker.destroy();
                }
                
                // Reset time slots and selection
                $('#time-slots-container').html(`
                    <div class="spinner-container">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `);
                $('#selected_time').val('');
                
                // Fetch available dates from server
                $.ajax({
                    url: '../schedule/get_available_dates.php',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        start: new Date().toISOString().split('T')[0],  // Today
                        end: new Date(new Date().setMonth(new Date().getMonth() + 3)).toISOString().split('T')[0], // 3 months ahead
                        service_id: serviceId,
                        dentist_id: dentistId
                    },
                    success: function(response) {
                        const availableDates = [];
                        
                        if (response && Array.isArray(response)) {
                            response.forEach(function(event) {
                                if (event.available) {
                                    availableDates.push(event.date);
                                }
                            });
                        }
                        
                        if (availableDates.length > 0) {
                            // Initialize date picker with available dates
                            datePicker = flatpickr('#date_picker', {
                                minDate: 'today',
                                maxDate: new Date().fp_incr(90), // 90 days from now
                                disable: [
                                    function(date) {
                                        // Disable dates that are not in availableDates
                                        const dateString = date.toISOString().split('T')[0];
                                        return !availableDates.includes(dateString);
                                    }
                                ],
                                onChange: function(selectedDates, dateStr) {
                                    // When date changes, fetch available time slots
                                    fetchTimeSlots(dateStr);
                                }
                            });
                            
                            $('#date-error').text('').hide();
                        } else {
                            // No available dates
                            $('#date_picker').val('');
                            $('#date-error').text('No available dates found in the next 3 months.').show();
                            $('#time-slots-container').html(`
                                <div class="text-center text-warning py-4">
                                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                    <p>No available appointment dates found</p>
                                    <p class="small">Please try another service or dentist</p>
                                </div>
                            `);
                        }
                        
                        // Hide loading overlay
                        $('#loadingOverlay').hide();
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching available dates:", error);
                        $('#date_picker').val('');
                        $('#date-error').text('Failed to load available dates. Please try again later.').show();
                        $('#time-slots-container').html(`
                            <div class="text-center text-danger py-4">
                                <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                                <p>Error loading available dates</p>
                                <p class="small">Please try refreshing the page</p>
                            </div>
                        `);
                        
                        // Hide loading overlay
                        $('#loadingOverlay').hide();
                    }
                });
            }
            
            // Fetch available time slots for the selected date
            function fetchTimeSlots(date) {
                // Show loading in time slots container
                $('#time-slots-container').html(`
                    <div class="spinner-container">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                `);
                $('#selected_time').val('');
                
                // Get selected service and dentist
                const serviceId = $('#service_id').val();
                const dentistId = $('#dentist_id').val() || '';
                
                // Fetch available time slots from server
                $.ajax({
                    url: '../schedule/get_available_slots.php',
                    type: 'GET',
                    dataType: 'json',
                    data: {
                        date: date,
                        service_id: serviceId,
                        dentist_id: dentistId
                    },
                    success: function(response) {
                        if (response.error) {
                            $('#time-slots-container').html(`
                                <div class="text-center text-danger py-4">
                                    <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                                    <p>${response.message || 'Error loading time slots'}</p>
                                </div>
                            `);
                            return;
                        }
                        
                        if (response.slots && response.slots.length > 0) {
                            let slotsHtml = '<div class="row">';
                            response.slots.forEach(function(slot) {
                                slotsHtml += `
                                    <div class="col-md-6 col-lg-4 mb-2">
                                        <div class="time-slot" data-time="${slot.time}" data-formatted="${slot.formatted_time}">
                                            ${slot.formatted_time}
                                        </div>
                                    </div>
                                `;
                            });
                            slotsHtml += '</div>';
                            
                            $('#time-slots-container').html(slotsHtml);
                            
                            // Bind click event to time slots
                            $('.time-slot').click(function() {
                                // Clear all selections
                                $('.time-slot').removeClass('selected');
                                
                                // Add selection to clicked slot
                                $(this).addClass('selected');
                                
                                // Update hidden input
                                $('#selected_time').val($(this).data('time'));
                            });
                        } else {
                            $('#time-slots-container').html(`
                                <div class="text-center text-warning py-4">
                                    <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                    <p>No available time slots for this date</p>
                                    <p class="small">Please select another date</p>
                                </div>
                            `);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching time slots:", error);
                        $('#time-slots-container').html(`
                            <div class="text-center text-danger py-4">
                                <i class="fas fa-exclamation-circle fa-2x mb-2"></i>
                                <p>Failed to load time slots</p>
                                <p class="small">Please try again later</p>
                            </div>
                        `);
                    }
                });
            }
            
            // Update confirmation step with selected information
            function updateConfirmation() {
                // Get selected values
                const serviceText = $('#service_id option:selected').text();
                const dentistText = $('#dentist_id option:selected').text() || 'Any available dentist';
                const dateText = $('#date_picker').val();
                const timeText = $('.time-slot.selected').data('formatted') || '';
                
                // Update confirmation text
                $('#confirm-service').text(serviceText);
                $('#confirm-dentist').text(dentistText);
                $('#confirm-date').text(dateText);
                $('#confirm-time').text(timeText);
            }
            
            // Form submission handler
            $('#appointmentForm').submit(function() {
                $('#loadingOverlay').show();
            });
        });
    </script>
</body>
</html>