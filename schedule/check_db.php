<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\schedule\check_db.php
require_once '../database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>Database Diagnostic Utility</h2>";

// Function to check if a table exists
function tableExists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result->num_rows > 0;
}

// Function to check if a column exists in a table
function columnExists($conn, $table, $column) {
    if (!tableExists($conn, $table)) return false;
    
    $result = $conn->query("SHOW COLUMNS FROM $table LIKE '$column'");
    return $result->num_rows > 0;
}

// Check important tables
$tables = ['users', 'patients', 'dentist_availability', 'appointments', 'services'];

echo "<h3>Table Status</h3>";
echo "<ul>";
foreach ($tables as $table) {
    $exists = tableExists($conn, $table);
    echo "<li>$table: " . ($exists ? "✅ Exists" : "❌ Missing") . "</li>";
    
    if ($exists) {
        // Count records
        $count = $conn->query("SELECT COUNT(*) as count FROM $table")->fetch_assoc()['count'];
        echo "<ul><li>Records: $count</li>";
        
        // Show sample record
        if ($count > 0) {
            $sample = $conn->query("SELECT * FROM $table LIMIT 1")->fetch_assoc();
            echo "<li>Sample columns: " . implode(", ", array_keys($sample)) . "</li>";
        }
        echo "</ul>";
    }
}
echo "</ul>";

// Check specific column issues
echo "<h3>Critical Column Checks</h3>";
echo "<ul>";

// Check dentist_id in appointments
$dentist_id_exists = columnExists($conn, 'appointments', 'dentist_id');
$user_id_exists = columnExists($conn, 'appointments', 'user_id');

echo "<li>appointments.dentist_id: " . ($dentist_id_exists ? "✅ Exists" : "❌ Missing") . "</li>";
echo "<li>appointments.user_id: " . ($user_id_exists ? "✅ Exists" : "❌ Missing") . "</li>";

// Check user_id in dentist_availability
$da_user_id_exists = columnExists($conn, 'dentist_availability', 'user_id');
$da_dentist_id_exists = columnExists($conn, 'dentist_availability', 'dentist_id');

echo "<li>dentist_availability.user_id: " . ($da_user_id_exists ? "✅ Exists" : "❌ Missing") . "</li>";
echo "<li>dentist_availability.dentist_id: " . ($da_dentist_id_exists ? "✅ Exists" : "❌ Missing") . "</li>";

echo "</ul>";

// Offer fix options
echo "<h3>Fix Options</h3>";

if (!$dentist_id_exists && $user_id_exists) {
    echo "<form method='post'>";
    echo "<button type='submit' name='fix_column' value='rename_user_id' class='btn btn-warning'>Rename user_id to dentist_id in appointments table</button>";
    echo "</form>";
} else if (!$dentist_id_exists && !$user_id_exists) {
    echo "<form method='post'>";
    echo "<button type='submit' name='fix_column' value='add_dentist_id' class='btn btn-warning'>Add dentist_id column to appointments table</button>";
    echo "</form>";
}

// Execute the fix if requested
if (isset($_POST['fix_column'])) {
    echo "<h3>Executing Fix</h3>";
    
    if ($_POST['fix_column'] === 'rename_user_id') {
        $sql = "ALTER TABLE appointments CHANGE user_id dentist_id INT(11)";
        if ($conn->query($sql)) {
            echo "<p>✅ Successfully renamed user_id to dentist_id in appointments table</p>";
        } else {
            echo "<p>❌ Error: " . $conn->error . "</p>";
        }
    }
    
    if ($_POST['fix_column'] === 'add_dentist_id') {
        $sql = "ALTER TABLE appointments ADD dentist_id INT(11) AFTER patient_id";
        if ($conn->query($sql)) {
            echo "<p>✅ Successfully added dentist_id column to appointments table</p>";
        } else {
            echo "<p>❌ Error: " . $conn->error . "</p>";
        }
    }
}

echo "<p><a href='../userdashboard/appointment.php' class='btn btn-primary'>Back to Appointment Page</a></p>";
?>  