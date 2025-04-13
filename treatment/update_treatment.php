<?php
include '../database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $appointment_id = mysqli_real_escape_string($conn, $_POST['appointment_id']);
    $service_id = mysqli_real_escape_string($conn, $_POST['service_id']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    $currentTime = date('Y-m-d H:i:s');

    $sql = "UPDATE treatments
            SET service_id='$service_id',
                notes='$notes',
                updated_at='$currentTime'
            WHERE appointment_id='$appointment_id'";

    if (mysqli_query($conn, $sql)) {
        echo 'Success';
    } else {
        http_response_code(500);
        echo 'Error updating treatment: ' . mysqli_error($conn);
    }
    mysqli_close($conn);
} else {
    http_response_code(405);
    echo 'Method not allowed.';
}
?>