<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\includes\soft_delete.php

// Central soft delete implementation
function softDelete($conn, $table, $id_column, $id) {
    $stmt = $conn->prepare("UPDATE $table SET is_deleted = 1, deleted_at = CURRENT_TIMESTAMP WHERE $id_column = ?");
    $stmt->bind_param('i', $id);
    $success = $stmt->execute();
    return ['success' => $success, 'message' => $success ? 'Record marked as deleted' : 'Error: ' . $conn->error];
}

// Table-specific functions
function softDeletePatient($conn, $id) {
    return softDelete($conn, 'patients', 'patient_info_id', $id);
}

function softDeleteAppointment($conn, $id) {
    return softDelete($conn, 'appointments', 'appointment_id', $id);
}


function softDeleteTreatment($conn, $id) {
    return softDelete($conn, 'treatments', 'treatment_id', $id);
}

function softDeletePayment($conn, $id) {
    return softDelete($conn, 'payments', 'payment_id', $id);
}

// For staff (stored in users table)
function softDeleteStaff($conn, $id) {
    return softDelete($conn, 'users', 'id', $id);
}
?>