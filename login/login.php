<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\login\login.php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | City Smile Dental Clinic</title>
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
        
        .login-success {
            background-color: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
            animation: fadeOut 5s forwards;
        }
        
        @keyframes fadeOut {
            0% { opacity: 1; }
            80% { opacity: 1; }
            100% { opacity: 0; }
        }
        
        .login-error {
            background-color: #f8d7da;
            color: #721c24;
            padding: 12px;
            border-radius: 5px;
            margin-bottom: 15px;
        }
        
        .login-box {
            width: 400px;
            max-width: 90%;
            padding: 0;
            overflow: hidden;
            border-radius: 20px;
        }
        
        .auth0-button {
            background: white;
            border: 1px solid #ddd;
            transition: all 0.3s ease;
        }
        
        .auth0-button:hover {
            background: #f8f9fa;
            border-color: #ccc;
        }
        
        .auth0-button img {
            width: 20px;
            height: auto;
            margin-right: 8px;
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
                    <h3 class="text-center text-primary mb-0" style="color: #000069 !important;">Welcome Back!</h3>
                </div>
                <div class="card-body p-4">
                    <!-- Display success message -->
                    <?php if (isset($_SESSION['register_success'])): ?>
                        <div class="login-success">
                            <?php 
                            echo $_SESSION['register_success']; 
                            unset($_SESSION['register_success']); 
                            ?>
                        </div>
                    <?php endif; ?>

                    <!-- Display error message -->
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="login-error">
                            <?php 
                            echo $_SESSION['error']; 
                            unset($_SESSION['error']); 
                            ?>
                        </div>
                    <?php endif; ?>

                    <form action="postlogin.php" method="POST">
                        <div class="mb-3">
                            <label for="username" class="form-label fw-semibold">Username</label>
                            <input type="text" id="username" name="username" class="form-control" placeholder="Enter your username" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="form-label fw-semibold">Password</label>
                            <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 mb-3" style="background-color: #000069;">Login</button>
                        
                        <div class="text-center mb-3">
                            <span class="separator">or</span>
                        </div>
                        
                        <button type="button" class="auth0-button w-100 d-flex align-items-center justify-content-center py-2 mb-3" onclick="window.location.href='auth0login.php';">
                            <img src="../icons/gmail.png" alt="Gmail">
                            Sign in with Gmail
                        </button>
                        
                        <div class="d-flex justify-content-between mt-4">
                            <a href="create.php" class="text-decoration-none" style="color: #000069;">Create an Account</a>
                            <a href="forgotpass.php" class="text-decoration-none" style="color: #000069;">Forgot Password?</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>