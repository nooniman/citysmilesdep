<?php 
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="main.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Age Restriction</title>
</head>
<body>
    <header class="header">
        <div class="logo-wrapper">
            <img src="../images/Screenshot__522_-removebg-preview.png" alt="Logo" class="logo">
            <h1>City Smile Dental Clinic</h1>
        </div>
    </header>
    <div class="login-container">
        <div class="login-box" style="max-width: 500px;">
            <div class="logo-wrapper">
                <img class="logo" src="../images/Screenshot__522_-removebg-preview.png" alt="Logo">
                <h2>Age Verification Failed</h2>
            </div>
            <div class="error-message">
                <p>You must be at least 18 years old to create an account.</p>
                <p>Based on the birthdate from your account, you do not meet the minimum age requirement.</p>
            </div>
            <div class="form-group">
                <a href="login.php" class="btn btn-primary">Return to Login</a>
            </div>
        </div>
    </div>
</body>
</html>