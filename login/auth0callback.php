<?php
// Enable all error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Output buffering with error logging
ob_start();

// Log function to help debug
function logDebug($message)
{
    file_put_contents(__DIR__ . '/auth0_debug.log', date('[Y-m-d H:i:s] ') . $message . "\n", FILE_APPEND);
}

logDebug("Callback started");
logDebug("URL: " . $_SERVER['REQUEST_URI']);
logDebug("GET params: " . print_r($_GET, true));

// Include required files
require_once __DIR__ . '/../vendor/autoload.php';
$config = require_once __DIR__ . '/auth0-config.php';
include_once __DIR__ . '/../database.php';

logDebug("Required files loaded");

// Check database connection
if (!isset($conn) || $conn->connect_error) {
    logDebug("Database connection error: " . ($conn->connect_error ?? "Connection variable not set"));
    ob_end_clean();
    die("Database connection failed. Check the logs for details.");
}

session_start();
logDebug("Session started");

try {
    logDebug("Initializing Auth0...");
    // Use identical config to login
    $auth0 = new \Auth0\SDK\Auth0([
        'domain' => $config['domain'],
        'clientId' => $config['client_id'],
        'clientSecret' => $config['client_secret'],
        'redirectUri' => $config['redirect_uri'],
        'cookieSecret' => $config['cookie_secret']
    ]);

    logDebug("Exchanging code for tokens...");
    // Exchange authorization code for tokens
    $auth0->exchange();

    logDebug("Getting user profile...");
    // Get the user profile
    $userInfo = $auth0->getUser();

    if (!$userInfo) {
        logDebug("Failed to get user info");
        throw new Exception('Failed to get user info from Auth0');
    }

    logDebug("User info retrieved: " . json_encode($userInfo));

    // Check for birthdate information and verify age
$birthdate = null;
$minimumAge = 18; // Set your minimum age requirement
$ageVerified = true;

// Check for birthdate in Auth0 user profile (could be in different fields depending on provider)
if (isset($userInfo['birthdate'])) {
    $birthdate = $userInfo['birthdate'];
} elseif (isset($userInfo['user_metadata']) && isset($userInfo['user_metadata']['birthdate'])) {
    $birthdate = $userInfo['user_metadata']['birthdate'];
}

logDebug("Birthdate from Auth0: " . ($birthdate ?? "Not available"));

// If birthdate is available, verify age
if ($birthdate) {
    // Store birthdate in session for pre-filling form
    $_SESSION['auth0_birthdate'] = $birthdate;
    
    // Calculate age
    $age = calculateAge($birthdate);
    $_SESSION['user_age'] = $age;
    
    // Check if user meets minimum age
    if ($age < $minimumAge) {
        logDebug("Age verification failed: User is $age years old");
        $_SESSION['age_verification_failed'] = true;
        header("Location: complete_profile.php");
        exit;
    }
}


    // Check if user exists in your database
    $email = $userInfo['email'];
    logDebug("Checking if user exists with email: " . $email);

    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    if (!$stmt) {
        logDebug("Prepare failed: " . $conn->error);
        throw new Exception("Database prepare failed: " . $conn->error);
    }

    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    logDebug("Database query executed, found rows: " . $result->num_rows);

    // Now it's safe to output or redirect
    ob_end_clean();

    // After successful authentication, mark user as Auth0 authenticated
    if ($result->num_rows > 0) {
        logDebug("Existing user found, logging in");
        // User exists, log them in
        $user = $result->fetch_assoc();

        // Update auth0_id if not already set
        if (empty($user['auth0_id'])) {
            $auth0_id = $userInfo['sub'];
            logDebug("Updating auth0_id for user");
            $updateStmt = $conn->prepare("UPDATE users SET auth0_id = ? WHERE id = ?");
            $updateStmt->bind_param("si", $auth0_id, $user['id']);
            $updateStmt->execute();
        }

        if (isset($userInfo['picture'])) {
            $profile_picture = $userInfo['picture'];
            logDebug("Updating profile picture for user from Auth0");
            $updatePicStmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
            $updatePicStmt->bind_param("si", $profile_picture, $user['id']);
            $updatePicStmt->execute();
        }

        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['auth0_user'] = true;

        logDebug("Session vars set, role: " . $user['role']);

        // Role-based redirection
        if (in_array($user['role'], ['admin', 'staff', 'dentist'])) {
            logDebug("Redirecting to admin dashboard");
            header("Location: ../dashboard/dashboard.php");
        } else {
            logDebug("Redirecting to user dashboard");
            header("Location: ../userdashboard/dashboard.php");
        }
        exit;
    } else {
        logDebug("New user, registering");
        // Create username from email or name
        $username = explode('@', $email)[0] . rand(100, 999);
        $first_name = isset($userInfo['given_name']) ? $userInfo['given_name'] : '';
        $last_name = isset($userInfo['family_name']) ? $userInfo['family_name'] : '';
        $auth0_id = $userInfo['sub'];
        $role = 'patient'; // Default role
        $profile_picture = isset($userInfo['picture']) ? $userInfo['picture'] : null; // ADD THIS LINE

        logDebug("Inserting new user: " . $username);

        // Insert new user
        $sql = "INSERT INTO users (first_name, last_name, username, email, auth0_id, role, profile_picture, birthdate, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    logDebug("Prepare failed: " . $conn->error);
    die("Database prepare failed: " . $conn->error);
}

// Update bind_param to include birth_date
$stmt->bind_param("ssssssss", $first_name, $last_name, $username, $email, $auth0_id, $role, $profile_picture, $birthdate);

        if ($stmt->execute()) {
            logDebug("New user created, ID: " . $conn->insert_id);
            $_SESSION['user_id'] = $conn->insert_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            $_SESSION['auth0_user'] = true;

            logDebug("Redirecting to profile completion");
            header("Location: complete_profile.php");
            exit;
        } else {
            logDebug("Error creating user: " . $stmt->error);
            die("Error creating user: " . $stmt->error);
        }
    }
} catch (Exception $e) {
    logDebug("ERROR: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    ob_end_clean();
    echo '<h2>Auth0 callback error:</h2>';
    echo '<p style="color:red">' . htmlspecialchars($e->getMessage()) . '</p>';
    echo '<pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre>';
    echo '<p><a href="login.php">Return to login</a></p>';
}

function calculateAge($birthdate) {
    $birth = new DateTime($birthdate);
    $today = new DateTime('today');
    $age = $birth->diff($today)->y;
    return $age;
}
?>