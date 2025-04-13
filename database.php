<?php
 // Include the config file

$host = getenv('DB_HOST') ?: '127.0.0.1'; // Use environment variable or fallback to 127.0.0.1
$user = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$database = getenv('DB_NAME') ?: 'dentalsystem';

$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>