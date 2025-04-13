<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\login\verification.php
session_start();

// Redirect if not part of the verification process
if (!isset($_SESSION['verification_email'])) {
    header("Location: login.php");
    exit();
}

$email = $_SESSION['verification_email'];
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Verification | City Smile Dental Clinic</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="main.css">
    <style>
        .card-header {
            border-top: 8px solid #000069;
            border-radius: 15px 15px 0 0 !important;
        }
        
        .login-box {
            width: 450px;
            max-width: 90%;
            padding: 0;
            overflow: hidden;
            border-radius: 20px;
        }
        
        .otp-input {
            width: 50px;
            height: 50px;
            text-align: center;
            font-size: 24px;
            margin: 0 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        
        .otp-input:focus {
            border-color: #000069;
            box-shadow: 0 0 0 0.25rem rgba(0, 0, 105, 0.25);
        }
        
        .resend-link {
            color: #000069;
            font-weight: 500;
            text-decoration: none;
        }
        
        .resend-link:hover {
            text-decoration: underline;
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
                    <h3 class="text-center text-primary mb-0" style="color: #000069 !important;">Email Verification</h3>
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

                    <p class="text-center mb-3">We've sent a verification code to:<br><strong><?php echo htmlspecialchars($email); ?></strong></p>

                    <form action="verify_code.php" method="POST" id="verification-form">
                        <div class="otp-inputs d-flex justify-content-center my-4">
                            <input type="text" class="otp-input" name="otp[]" maxlength="1" pattern="[0-9]" required autofocus>
                            <input type="text" class="otp-input" name="otp[]" maxlength="1" pattern="[0-9]" required>
                            <input type="text" class="otp-input" name="otp[]" maxlength="1" pattern="[0-9]" required>
                            <input type="text" class="otp-input" name="otp[]" maxlength="1" pattern="[0-9]" required>
                            <input type="text" class="otp-input" name="otp[]" maxlength="1" pattern="[0-9]" required>
                            <input type="text" class="otp-input" name="otp[]" maxlength="1" pattern="[0-9]" required>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100 mb-3" style="background-color: #000069;">Verify</button>
                        
                        <div class="text-center mt-3">
                            <p class="mb-1">Didn't receive the code?</p>
                            <a href="resend_code.php" class="resend-link">Resend Code</a>
                        </div>
                        
                        <div class="text-center mt-4">
                            <a href="login.php" class="text-decoration-none" style="color: #000069;">Back to Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Auto-focus to next input when typing
        const otpInputs = document.querySelectorAll('.otp-input');
        
        otpInputs.forEach((input, index) => {
            input.addEventListener('input', (e) => {
                if (e.target.value.length === 1) {
                    if (index < otpInputs.length - 1) {
                        otpInputs[index + 1].focus();
                    }
                }
            });
            
            input.addEventListener('keydown', (e) => {
                if (e.key === 'Backspace' && e.target.value.length === 0) {
                    if (index > 0) {
                        otpInputs[index - 1].focus();
                    }
                }
            });
        });
    </script>
</body>
</html>