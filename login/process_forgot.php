<?php
session_start();
include '../database.php';
require '../vendor/autoload.php'; // Make sure to include the Composer autoload file

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Check if the email exists.
    $sql = "SELECT id FROM users WHERE email = ?";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        die("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $userId = $user['id'];

        // Generate a unique token
        $token = bin2hex(random_bytes(50));

        // Store the token in the database with an expiration time
        $stmt = $conn->prepare("INSERT INTO password_resets (user_id, token, expires_at) VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))");
        $stmt->bind_param('is', $userId, $token);
        $stmt->execute();

        // Send the reset link to the user's email using PHPMailer
        $mail = new PHPMailer(true);
        try {
            // Mailtrap SMTP configuration
            $mail->isSMTP();
            $mail->Host = 'sandbox.smtp.mailtrap.io';
            $mail->SMTPAuth = true;
            $mail->Username = '73e591c1a6dce0'; // Replace with your Mailtrap username
            $mail->Password = 'f3861c0d0afa9e'; // Replace with your Mailtrap password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 2525;

            // Email settings
            $mail->setFrom('no-reply@citysmiles.com', 'City Smile Dental Clinic');
            $mail->addAddress($email);
            $mail->isHTML(true);
            $mail->Subject = 'Password Reset Request';
            $mail->Body = "Click the following link to reset your password: <a href='http://yourdomain.com/reset_password.php?token=$token'>Reset Password</a>";

            $mail->send();
            $_SESSION['message'] = "A password reset link has been sent to your email.";
            $_SESSION['message_type'] = "success"; // For Bootstrap alert type
        } catch (Exception $e) {
            $_SESSION['message'] = "Failed to send the password reset link. Please try again.";
            $_SESSION['message_type'] = "danger"; // For Bootstrap alert type
        }
    } else {
        $_SESSION['message'] = "Email not found.";
        $_SESSION['message_type'] = "danger"; // For Bootstrap alert type
    }
    $stmt->close();
    $conn->close();

    // Redirect back to the form page
    header("Location: forgotpass.php");
    exit();
} else {
    header("Location: forgotpass.php");
    exit();
}
?>