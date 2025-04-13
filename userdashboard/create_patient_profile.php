<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\userdashboard\create_patient_profile.php
session_start();
include '../database.php';

// Only allow logged in users
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to access this page.");
}

$user_id = $_SESSION['user_id'];

// Get user data
$user_query = "SELECT * FROM users WHERE id = ?";
$user_stmt = $conn->prepare($user_query);
$user_stmt->bind_param('i', $user_id);
$user_stmt->execute();
$user = $user_stmt->get_result()->fetch_assoc();

if (!$user) {
    die("User not found.");
}

// Check if patient record already exists
$check_query = "SELECT patient_info_id FROM patients WHERE user_id = ?";
$check_stmt = $conn->prepare($check_query);
$check_stmt->bind_param('i', $user_id);
$check_stmt->execute();

if ($check_stmt->get_result()->num_rows > 0) {
    echo "Patient profile already exists.";
    echo "<p><a href='appointment.php'>Back to Appointments</a></p>";
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $first_name = $user['first_name'] ?? $_POST['first_name'] ?? '';
    $last_name = $user['last_name'] ?? $_POST['last_name'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $birth_date = $_POST['birth_date'] ?? '';
    $contact_number = $user['contact_number'] ?? $_POST['contact_number'] ?? '';
    $email = $user['email'] ?? $_POST['email'] ?? '';
    $address = $_POST['address'] ?? '';
    
    // Insert into patients table
    $sql = "INSERT INTO patients (user_id, first_name, last_name, gender, birth_date, contact_number, email, address, created_at, updated_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('isssssss', $user_id, $first_name, $last_name, $gender, $birth_date, $contact_number, $email, $address);
    
    if ($stmt->execute()) {
        echo "<div class='alert alert-success'>Patient profile created successfully!</div>";
        echo "<p><a href='appointment.php'>Continue to Appointments</a></p>";
        exit;
    } else {
        $error = "Error creating patient profile: " . $stmt->error;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Patient Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title">Create Patient Profile</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <p>Please complete your patient profile to book appointments.</p>
                        
                        <form method="post">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo $user['first_name'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo $user['last_name'] ?? ''; ?>" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="gender" class="form-label">Gender</label>
                                    <select class="form-select" id="gender" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="Male">Male</option>
                                        <option value="Female">Female</option>
                                        <option value="Other">Other</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="birth_date" class="form-label">Date of Birth</label>
                                    <input type="date" class="form-control" id="birth_date" name="birth_date" required>
                                </div>
                            </div>
                            
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="contact_number" class="form-label">Phone</label>
                                    <input type="tel" class="form-control" id="contact_number" name="contact_number" value="<?php echo $user['contact_number'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo $user['email'] ?? ''; ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3" required></textarea>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Create Profile</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>