<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\payments\get_patient_appointments.php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

// Get patient ID
$patient_id = $_GET['patient_id'] ?? 0;

// Get patient's appointments that don't have invoices yet
$query = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, s.name as service_name,
          t.fee as treatment_fee
          FROM appointments a
          LEFT JOIN services s ON a.service_id = s.services_id
          LEFT JOIN treatments t ON a.appointment_id = t.appointment_id
          LEFT JOIN invoices i ON a.appointment_id = i.appointment_id
          WHERE a.patient_id = ? AND a.status = 'completed' AND i.invoice_id IS NULL
          ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $patient_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    echo '<option value="">-- Select Appointment --</option>';
    while ($row = $result->fetch_assoc()) {
        $date = date('M d, Y', strtotime($row['appointment_date']));
        $time = date('h:i A', strtotime($row['appointment_time']));
        $fee = !empty($row['treatment_fee']) ? " - ₱" . number_format($row['treatment_fee'], 2) : "";
        echo "<option value='{$row['appointment_id']}' data-fee='{$row['treatment_fee']}'>{$date} {$time} - {$row['service_name']}{$fee}</option>";
    }
} else {
    echo '<option value="">No completed appointments found</option>';
}
?>