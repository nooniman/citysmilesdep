<?php
include '../database.php';

if (isset($_GET['prescription_id'])) {
    $prescription_id = $_GET['prescription_id'];
    $stmt = $conn->prepare("DELETE FROM prescriptions WHERE prescription_id = ?");
    $stmt->bind_param("i", $prescription_id);
    if ($stmt->execute()) {
        header("Location: treatment.php");
        exit;
    } else {
        die("Error deleting prescription: " . $stmt->error);
    }
} else {
    die("Invalid request.");
}
?>