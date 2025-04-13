<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\login\changepass.php
session_start();

// Redirect if user is not logged in or not part of the password reset flow
if (!isset($_SESSION['user_id']) && !isset($_SESSION['reset_token'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password | City Smile Dental Clinic</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="main.css">
    <style>
        .card-header {
            border-top: 8px solid #000069;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .form-control:focus {
            border-color: #000069;
            box-shadow: 0 0 0 0.25rem rgba(0, 0, 105, 0.25);
        }
        
        .login-box {
            width: 450px;
            max-width: 90%;
            padding: 0;
            overflow: hidden;
            border-radius: 20px;
        }
        
        .btn-primary {
            background-color: #000069;
            border-color: #000069;
        }
        
        .btn-primary:hover {
            background-color: #00004d;
            border-color: #00004d;
        }
        
        .password-strength {
            height: 5px;
            border-radius: 2px;
            margin-top: 5px;
        }
        
        .password-feedback {
            font-size: 12px;
            margin-top: 5px;
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

    <div class="login-container">
        <div class="login-box shadow">
            <div class="card border-0 h-100">
                <div class="card-header bg-white pt-4 pb-3">
                    <div class="text-center mb-3">
                        <img src="../images/Screenshot__522_-removebg-preview.png" alt="Logo" style="height: 80px;">
                    </div>
                    <h3 class="text-center text-primary mb-0" style="color: #000069 !important;">
                        <?php echo isset($_SESSION['user_id']) ? 'Change Password' : 'Reset Password'; ?>
                    </h3>
                </div>
                <div class="card-body p-4">
                    <!-- Display error message if any -->
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger">
                            <?php 
                            echo $_SESSION['error']; 
                            unset($_SESSION['error']); 
                            ?>
                        </div>
                    <?php endif; ?>

                    <form action="process_changepass.php" method="POST" id="password-form">
                        <?php if (isset($_SESSION['user_id']) && !isset($_SESSION['reset_token'])): ?>
                        <div class="mb-3">
                            <label for="current_password" class="form-label fw-semibold">Current Password</label>
                            <input type="password" id="current_password" name="current_password" class="form-control" required>
                        </div>
                        <?php endif; ?>
                        
                        <div class="mb-3">
                            <label for="new_password" class="form-label fw-semibold">New Password</label>
                            <input type="password" id="new_password" name="new_password" class="form-control" required>
                            <div class="password-strength w-100 bg-light"></div>
                            <div class="password-feedback text-muted"></div>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label fw-semibold">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-control" required>
                        </div>

                        <?php if (isset($_SESSION['reset_token'])): ?>
                            <input type="hidden" name="token" value="<?php echo $_SESSION['reset_token']; ?>">
                        <?php endif; ?>

                        <button type="submit" class="btn btn-primary w-100 mb-3">
                            <?php echo isset($_SESSION['user_id']) ? 'Update Password' : 'Reset Password'; ?>
                        </button>
                        
                        <div class="text-center mt-3">
                            <a href="<?php echo isset($_SESSION['user_id']) ? '../dashboard/dashboard.php' : 'login.php'; ?>" class="text-decoration-none" style="color: #000069;">
                                <?php echo isset($_SESSION['user_id']) ? 'Back to Dashboard' : 'Back to Login'; ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Password strength meter
        const passwordInput = document.getElementById('new_password');
        const confirmInput = document.getElementById('confirm_password');
        const strengthBar = document.querySelector('.password-strength');
        const feedbackElement = document.querySelector('.password-feedback');
        
        passwordInput.addEventListener('input', function() {
            const password = this.value;
            let strength = 0;
            let feedback = [];
            
            if (password.length >= 8) {
                strength += 25;
            } else {
                feedback.push('At least 8 characters');
            }
            
            if (password.match(/[A-Z]/)) {
                strength += 25;
            } else {
                feedback.push('At least 1 uppercase letter');
            }
            
            if (password.match(/[0-9]/)) {
                strength += 25;
            } else {
                feedback.push('At least 1 number');
            }
            
            if (password.match(/[^A-Za-z0-9]/)) {
                strength += 25;
            } else {
                feedback.push('At least 1 special character');
            }
            
            strengthBar.style.width = strength + '%';
            
            if (strength < 50) {
                strengthBar.style.backgroundColor = '#dc3545';
                feedbackElement.style.color = '#dc3545';
            } else if (strength < 75) {
                strengthBar.style.backgroundColor = '#ffc107';
                feedbackElement.style.color = '#ffc107';
            } else {
                strengthBar.style.backgroundColor = '#28a745';
                feedbackElement.style.color = '#28a745';
            }
            
            feedbackElement.textContent = feedback.join(', ');
        });
        
        // Password matching validation
        const form = document.getElementById('password-form');
        form.addEventListener('submit', function(e) {
            if (passwordInput.value !== confirmInput.value) {
                e.preventDefault();
                alert('Passwords do not match!');
            }
        });
    </script>
</body>
</html>