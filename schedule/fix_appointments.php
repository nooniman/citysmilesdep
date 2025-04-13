<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\schedule\fix_appointments.php
require_once '../database.php';

echo "<h1>Fixing Appointments Database</h1>";

// Check appointments table schema
$check_sql = "DESCRIBE appointments";
$check_result = $conn->query($check_sql);

if (!$check_result) {
    echo "<p>Error: Could not check appointments table structure</p>";
    exit;
}

$has_dentist_id = false;
$has_user_id = false;

while ($row = $check_result->fetch_assoc()) {
    if ($row['Field'] === 'dentist_id') {
        $has_dentist_id = true;
    }
    if ($row['Field'] === 'user_id') {
        $has_user_id = true;
    }
}

echo "<p>Table analysis: dentist_id column exists: " . ($has_dentist_id ? "Yes" : "No") . "</p>";
echo "<p>Table analysis: user_id column exists: " . ($has_user_id ? "Yes" : "No") . "</p>";

// Add dentist_id column if it doesn't exist
if (!$has_dentist_id && $has_user_id) {
    $alter_sql = "ALTER TABLE appointments CHANGE user_id dentist_id INT(11)";
    if ($conn->query($alter_sql)) {
        echo "<p>Success: Renamed user_id column to dentist_id</p>";
    } else {
        echo "<p>Error: Failed to rename column - " . $conn->error . "</p>";
    }
} else if (!$has_dentist_id && !$has_user_id) {
    $alter_sql = "ALTER TABLE appointments ADD dentist_id INT(11) AFTER patient_id";
    if ($conn->query($alter_sql)) {
        echo "<p>Success: Added dentist_id column</p>";
    } else {
        echo "<p>Error: Failed to add column - " . $conn->error . "</p>";
    }
}

echo "<p>Fix complete. <a href='../userdashboard/appointment.php'>Go to appointment page</a></p>";
?>