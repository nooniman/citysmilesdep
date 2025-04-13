<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\purge_old_records.php
include 'database.php';

// Set error logging
ini_set('log_errors', 1);
ini_set('error_log', 'purge_errors.log');

try {
    $log = "Purge executed at: " . date('Y-m-d H:i:s') . "\n";
    
    // Tables with their own deletion logic
    $tables = [
        'patients' => 'patient_info_id',
        'appointments' => 'appointment_id',
        // Removed schedules as they don't use soft deletion
        'treatments' => 'treatment_id',
        'payments' => 'payment_id'
    ];
    
    foreach ($tables as $table => $id_column) {
        $stmt = $conn->prepare("DELETE FROM $table WHERE is_deleted = 1 AND deleted_at < DATE_SUB(NOW(), INTERVAL 5 YEAR)");
        $stmt->execute();
        $count = $stmt->affected_rows;
        $log .= "Purged $count records from $table\n";
    }
    
    // Special case for staff (users table)
    $stmt = $conn->prepare("DELETE FROM users WHERE 
                           is_deleted = 1 
                           AND deleted_at < DATE_SUB(NOW(), INTERVAL 5 YEAR)
                           AND role IN ('staff', 'dentist', 'intern', 'assistant')");
    $stmt->execute();
    $count = $stmt->affected_rows;
    $log .= "Purged $count staff records from users table\n";
    
    // Add a separate process for schedules - clean up old schedules directly
    $stmt = $conn->prepare("DELETE FROM schedules WHERE date < DATE_SUB(NOW(), INTERVAL 1 YEAR)");
    $stmt->execute();
    $count = $stmt->affected_rows;
    $log .= "Removed $count old schedule records (direct deletion)\n";
    
    // Save log
    file_put_contents('purge_log.txt', $log, FILE_APPEND);
    
    echo "Purge completed successfully";
} catch (Exception $e) {
    error_log('Purge error: ' . $e->getMessage());
    echo "Error during purge process: " . $e->getMessage();
}
?>