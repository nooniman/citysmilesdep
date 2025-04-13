<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\login\create.php
session_start();
include '../database.php';

$errors = [];
$error_fields = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $first_name = $_POST['first_name'] ?? '';
    $middle_name = $_POST['middle_name'] ?? '';
    $last_name = $_POST['last_name'] ?? '';
    $birthdate = $_POST['birthdate'] ?? '';
    $contact = $_POST['contact'] ?? '';
    $email = $_POST['email'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate form data

    if (!empty($birthdate)) {
        $today = new DateTime();
        $birth = new DateTime($birthdate);
        $age = $today->diff($birth)->y;
        
        if ($age < 18) {
            $errors[] = "You must be at least 18 years old to create an account.";
            $error_fields[] = 'birthdate'; // Mark birthdate as an error field
        }
    }

    if (empty($first_name)) {
        $errors[] = "First name is required";
        $error_fields[] = 'first_name';
    }
    
    // Add similar error_fields[] entries for other validation checks
    if (empty($last_name)) {
        $errors[] = "Last name is required";
        $error_fields[] = 'last_name';
    }
    
    if (empty($email)) {
        $errors[] = "Email is required";
        $error_fields[] = 'email';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
        $error_fields[] = 'email';
    }
    
    // Same for password validation
    if (empty($password)) {
        $errors[] = "Password is required";
        $error_fields[] = 'password';
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
        $error_fields[] = 'confirm_password';
    }
    
    // Check if email already exists
    $check_email = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($check_email);
    $stmt->bind_param('s', $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $errors[] = "Email already exists.";
        $error_fields[] = 'email';
    }
    
    // If no errors, create user account
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $username = strtolower($first_name . "." . $last_name);
        $role = 'patient'; // Default role
        
        // Create username with number suffix if it already exists
        $check_username = "SELECT * FROM users WHERE username = ?";
        $stmt = $conn->prepare($check_username);
        $stmt->bind_param('s', $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $i = 1;
        $original_username = $username;
        while ($result->num_rows > 0) {
            $username = $original_username . $i;
            $stmt = $conn->prepare($check_username);
            $stmt->bind_param('s', $username);
            $stmt->execute();
            $result = $stmt->get_result();
            $i++;
        }
        
        // Insert user data
        $sql = "INSERT INTO users (first_name, middle_name, last_name, username, email, password, contact, gender, birthdate, role, created_at, updated_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ssssssssss', $first_name, $middle_name, $last_name, $username, $email, $hashed_password, $contact, $gender, $birthdate, $role);
        
        if ($stmt->execute()) {
            $_SESSION['register_success'] = "Account created successfully! You can now login.";
            header('Location: login.php');
            exit;
        } else {
            $errors[] = "Error creating account: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | City Smile Dental Clinic</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Keep existing styles -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <!-- Add Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link rel="stylesheet" href="main.css">
    <style>
        /* Additional custom styles */
        .registration-form {
            padding: 20px;
        }
        
        .form-floating {
            margin-bottom: 15px;
        }
        
        .input-group-text {
            background-color: #f8f9fa;
        }
        
        .text-purple {
            color: #7B32AB;
        }
        
        .btn-primary {
            background-color: #000069;
            border-color: #000069;
        }
        
        .btn-primary:hover {
            background-color: #00004d;
            border-color: #00004d;
        }
        
        .btn-outline-primary {
            color: #000069;
            border-color: #000069;
        }
        
        .btn-outline-primary:hover {
            background-color: #000069;
            color: white;
        }
        
        .alert {
            border-radius: 10px;
        }
        
        .card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-header {
            background-color: white;
            border-bottom: none;
            border-top: 8px solid #000069;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .login-box {
            width: 50%;
            max-width: 700px;
            height: auto;
        }

        .password-strength-meter {
        margin-top: 10px;
    }
    
    .password-requirements {
        background-color: #f8f9fa;
        padding: 10px;
        border-radius: 5px;
        margin-top: 10px;
    }
    
    .password-requirements ul {
        margin-bottom: 0;
    }
    
    .password-requirements li {
        margin-bottom: 3px;
    }

    /* Add this to your existing style section */
.password-toggle {
    position: absolute;
    right: 12px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #6c757d;
    z-index: 10;
}

.password-toggle:hover {
    color: #000069;
}
    </style>
</head>

<body>
    <header class="header">
        <div class="logo-wrappers">
            <img src="../images/Screenshot__522_-removebg-preview.png" alt="Logo" class="logo">
            <h1>City Smile Dental Clinic</h1>
        </div>
    </header>

    <div class="login-container mt-5">
        <div class="login-box">
            <div class="logo-wrappers">
                <img class="logos" src="../images/Screenshot__522_-removebg-preview.png" alt="Logo">
                <h1>Create an Account</h1>
            </div>

            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="registration-form">
                <div class="row">
                    <!-- Name Fields -->
                    <div class="col-12 mb-3">
                        <label class="form-label fw-bold">Full Name</label>
                        <div class="row g-2">
                        <div class="col-md-4">
                            <input type="text" 
                                name="first_name" 
                                placeholder="First Name" 
                                class="form-control <?php echo in_array('first_name', $error_fields ?? []) ? 'is-invalid' : ''; ?>" 
                                value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>" 
                                required>
                            <?php if (in_array('first_name', $error_fields ?? [])): ?>
                                <div class="invalid-feedback">
                                    First name is required
                                </div>
                            <?php endif; ?>
                        </div>
                            <div class="col-md-4">
                                <input type="text" name="middle_name" placeholder="Middle Name" class="form-control" value="<?php echo isset($_POST['middle_name']) ? htmlspecialchars($_POST['middle_name']) : ''; ?>">
                            </div>
                            <div class="col-md-4">
                                <input type="text" name="last_name" placeholder="Last Name" class="form-control" value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Birth Date & Contact -->
                    <div class="col-12 mb-3">
                        <label class="form-label fw-bold">Birth Date & Contact</label>
                        <div class="row g-2">
                            <div class="col-md-6">
                                <input type="date" 
                                    name="birthdate" 
                                    class="form-control <?php echo in_array('birthdate', $error_fields ?? []) ? 'is-invalid' : ''; ?>" 
                                    value="<?php echo isset($_POST['birthdate']) ? htmlspecialchars($_POST['birthdate']) : ''; ?>" 
                                    required>
                                <?php if (in_array('birthdate', $error_fields ?? [])): ?>
                                    <div class="invalid-feedback">
                                        You must be at least 18 years old to create an account.
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <input type="tel" id="contact-number" name="contact" class="form-control" placeholder="Contact Number" pattern="[0-9]{10,11}" maxlength="11" value="<?php echo isset($_POST['contact']) ? htmlspecialchars($_POST['contact']) : ''; ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- Email & Gender -->
                    <div class="col-12 mb-3">
                        <label class="form-label fw-bold">Email & Gender</label>
                        <div class="row g-2">
                        <div class="col-md-6">
                            <input type="email" 
                                name="email" 
                                class="form-control <?php echo in_array('email', $error_fields ?? []) ? 'is-invalid' : ''; ?>" 
                                placeholder="Email Address" 
                                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>" 
                                required>
                            <?php if (in_array('email', $error_fields ?? [])): ?>
                                <div class="invalid-feedback">
                                    <?php
                                    foreach ($errors as $error) {
                                        if (strpos($error, 'Email') !== false) {
                                            echo htmlspecialchars($error);
                                            break;
                                        }
                                    }
                                    ?>
                                </div>
                            <?php endif; ?>
                        </div>
                            <div class="col-md-6">
                                <select id="gender" name="gender" class="form-select" required>
                                    <option value="" disabled <?php echo !isset($_POST['gender']) ? 'selected' : ''; ?>>Select Gender</option>
                                    <option value="Male" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Male') ? 'selected' : ''; ?>>Male</option>
                                    <option value="Female" <?php echo (isset($_POST['gender']) && $_POST['gender'] === 'Female') ? 'selected' : ''; ?>>Female</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Password Fields - Updated with strength meter and view toggle -->
<div class="col-12 mb-3">
    <label class="form-label fw-bold">Password</label>
    <div class="position-relative">
        <input type="password" id="password" name="password" class="form-control" placeholder="Create a strong password" required>
        <span class="password-toggle" onclick="togglePasswordVisibility('password')">
            <i class="fa fa-eye" aria-hidden="true"></i>
        </span>
    </div>

    <div class="password-strength-meter mt-2">
        <div class="progress" style="height: 8px;">
            <div id="password-strength-bar" class="progress-bar" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
        </div>
        <div id="password-strength-text" class="small text-muted mt-1">Password strength: None</div>

        
        <div class="password-requirements small mt-2">
            <p class="mb-1">Password must contain:</p>
            <ul class="ps-3 mb-0">
                <li id="length-check"><span class="text-danger">✗</span> At least 8 characters</li>
                <li id="lowercase-check"><span class="text-danger">✗</span> Lowercase letters (a-z)</li>
                <li id="uppercase-check"><span class="text-danger">✗</span> Uppercase letters (A-Z)</li>
                <li id="number-check"><span class="text-danger">✗</span> Numbers (0-9)</li>
                <li id="special-check"><span class="text-danger">✗</span> Special characters (!@#$%^&*)</li>
            </ul>
        </div>
    </div>
</div>
<!-- Confirm Password Field - Updated with view toggle -->
<div class="col-12 mb-3">
    <label class="form-label fw-bold">Confirm Password</label>
    <div class="position-relative">
        <input type="password" id="confirm-password" name="confirm_password" class="form-control" placeholder="Confirm your password" required>
        <span class="password-toggle" onclick="togglePasswordVisibility('confirm-password')">
            <i class="fa fa-eye" aria-hidden="true"></i>
        </span>
    </div>
    <div id="password-match-message" class="small mt-1"></div>
</div>

                    <!-- Form Actions -->
                    <div class="col-12 mt-4 d-flex justify-content-between">
                        <a href="login.php" class="btn btn-outline-secondary">Cancel</a>
                        <button type="submit" class="btn btn-primary px-4">Create Account</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script>

        // Add this to your existing script section
function togglePasswordVisibility(inputId) {
    const passwordInput = document.getElementById(inputId);
    const toggleIcon = passwordInput.nextElementSibling.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        toggleIcon.classList.remove('fa-eye');
        toggleIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        toggleIcon.classList.remove('fa-eye-slash');
        toggleIcon.classList.add('fa-eye');
    }
}
        // Client-side validation
        document.getElementById('contact-number').addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, ''); // Remove non-numeric characters
        });

        // Password strength checker
    document.getElementById('password').addEventListener('input', checkPasswordStrength);
    
    function checkPasswordStrength() {
        const password = document.getElementById('password').value;
        const strengthBar = document.getElementById('password-strength-bar');
        const strengthText = document.getElementById('password-strength-text');
        
        // Check requirements
        const hasLength = password.length >= 8;
        const hasLower = /[a-z]/.test(password);
        const hasUpper = /[A-Z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecial = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
        
        // Update requirement checks
        updateRequirement('length-check', hasLength);
        updateRequirement('lowercase-check', hasLower);
        updateRequirement('uppercase-check', hasUpper);
        updateRequirement('number-check', hasNumber);
        updateRequirement('special-check', hasSpecial);
        
        // Calculate strength score (0-100)
        let strength = 0;
        
        if (password.length > 0) {
            // Base points for having any password
            strength += 10;
            
            // Length points (up to 30)
            strength += Math.min(30, password.length * 2);
            
            // Complexity points
            if (hasLower) strength += 10;
            if (hasUpper) strength += 15;
            if (hasNumber) strength += 15;
            if (hasSpecial) strength += 20;
            
            // Bonus for combination
            const complexity = [hasLower, hasUpper, hasNumber, hasSpecial].filter(Boolean).length;
            if (complexity >= 3) strength += 10;
            if (complexity >= 4) strength += 10;
        }
        
        // Cap at 100
        strength = Math.min(100, strength);
        
        // Update UI
        strengthBar.style.width = strength + '%';
        
        // Color and text based on strength
        if (strength < 30) {
            strengthBar.className = 'progress-bar bg-danger';
            strengthText.textContent = 'Password strength: Very Weak';
            strengthText.className = 'small text-danger mt-1';
        } else if (strength < 50) {
            strengthBar.className = 'progress-bar bg-warning';
            strengthText.textContent = 'Password strength: Weak';
            strengthText.className = 'small text-warning mt-1';
        } else if (strength < 75) {
            strengthBar.className = 'progress-bar bg-info';
            strengthText.textContent = 'Password strength: Moderate';
            strengthText.className = 'small text-info mt-1';
        } else if (strength < 90) {
            strengthBar.className = 'progress-bar bg-primary';
            strengthText.textContent = 'Password strength: Strong';
            strengthText.className = 'small text-primary mt-1';
        } else {
            strengthBar.className = 'progress-bar bg-success';
            strengthText.textContent = 'Password strength: Very Strong';
            strengthText.className = 'small text-success mt-1';
        }
    }
    
    function updateRequirement(id, isMet) {
        const element = document.getElementById(id);
        if (isMet) {
            element.innerHTML = element.innerHTML.replace('✗', '✓');
            element.innerHTML = element.innerHTML.replace('text-danger', 'text-success');
        } else {
            element.innerHTML = element.innerHTML.replace('✓', '✗');
            element.innerHTML = element.innerHTML.replace('text-success', 'text-danger');
        }
    }

    // Add this to your existing JavaScript
document.getElementById('confirm-password').addEventListener('input', checkPasswordsMatch);

function checkPasswordsMatch() {
    const password = document.getElementById('password').value;
    const confirmPassword = document.getElementById('confirm-password').value;
    const matchMessage = document.getElementById('password-match-message');
    
    if (confirmPassword.length > 0) {
        if (password === confirmPassword) {
            matchMessage.textContent = "Passwords match";
            matchMessage.className = "small text-success mt-1";
        } else {
            matchMessage.textContent = "Passwords don't match";
            matchMessage.className = "small text-danger mt-1";
        }
    } else {
        matchMessage.textContent = "";
    }
}


    </script>
</body>
</html>