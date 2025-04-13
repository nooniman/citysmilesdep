<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\login\forgotpass.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | City Smile Dental Clinic</title>
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
            width: 400px;
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
                    <h3 class="text-center text-primary mb-0" style="color: #000069 !important;">Forgot Password</h3>
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

                    <!-- Display success message if any -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success">
                            <?php 
                            echo $_SESSION['success']; 
                            unset($_SESSION['success']); 
                            ?>
                        </div>
                    <?php endif; ?>

                    <p class="text-muted text-center mb-4">Enter your email address and we'll send you a link to reset your password.</p>

                    <form action="process_forgot.php" method="POST">
                        <div class="mb-4">
                            <label for="email" class="form-label fw-semibold">Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" placeholder="Enter your email" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3">Reset Password</button>
                        
                        <div class="text-center mt-3">
                            <a href="login.php" class="text-decoration-none" style="color: #000069;">Back to Login</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>