<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

include_once '../database.php';

// Get existing user data
$userId = $_SESSION['user_id'];
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $userId);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if age verification failed from Auth0
$ageVerificationFailed = isset($_SESSION['age_verification_failed']) && $_SESSION['age_verification_failed'] === true;
$minimumAge = 18; // Set your minimum required age
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="main.css">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete Your Profile</title>
</head>

<body>
    <header class="header">
        <div class="logo-wrappers">
            <img src="../images/Screenshot__522_-removebg-preview.png" alt="Logo" class="logo">
            <h1>City Smile Dental Clinic</h1>
        </div>
    </header>
    <div class="login-container">
    <div class="login-box" style="max-width: 500px;">
        <div class="logo-wrapper">
            <img class="logo" src="../images/Screenshot__522_-removebg-preview.png" alt="Logo">
            <h2><?php echo $ageVerificationFailed ? 'Age Restriction' : 'Complete Your Profile'; ?></h2>
        </div>
        
        <?php if ($ageVerificationFailed): ?>
        <!-- Show age restriction message -->
        <div style="background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
            <h3 style="color: #721c24; margin-top: 0;">Age Verification Failed</h3>
            <p>You must be at least <?php echo $minimumAge; ?> years old to create an account.</p>
            <p>Based on the birthdate retrieved from your account, you do not meet the minimum age requirement.</p>
            <a href="logout.php" class="btn btn-primary" style="display: inline-block; margin-top: 15px; text-decoration: none; background-color: #0d6efd; color: white; padding: 8px 16px; border-radius: 4px;">Return to Login</a>
        </div>
        <?php else: ?>
        <!-- Show normal form -->
        <form action="process_complete_profile.php" method="POST">
            <div class="form-group">
                <label for="birth_date">Birth Date</label>
                <input type="date" id="birth_date" name="birth_date" 
                       value="<?php echo isset($_SESSION['auth0_birthdate']) ? $_SESSION['auth0_birthdate'] : ''; ?>" 
                       <?php echo isset($_SESSION['auth0_birthdate']) ? 'readonly' : ''; ?> 
                       required>
                <?php if (isset($_SESSION['auth0_birthdate'])): ?>
                    <small>Retrieved from your account</small>
                <?php endif; ?>
            </div>
                <div class="form-group">
                    <label for="contact">Contact Number</label>
                    <input type="tel" id="contact" name="contact" pattern="[0-9]{10,11}" maxlength="11" required>
                </div>
                <div class="form-group">
                    <label for="address">Address</label>
                    <input type="text" id="address" name="address" required>
                </div>
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select Gender</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                    </select>
                </div>
                <div class="form-group">
                    <button type="submit">Complete Profile</button>
                </div>
                </form>
        <?php endif; ?>
    </div>
</div>
</body>

</html>