<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\payments\debug_invoices.php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

echo "<h1>Database Debugging</h1>";

// Check invoices table
echo "<h2>Invoices Table</h2>";
$invoices = $conn->query("SELECT * FROM invoices ORDER BY invoice_id DESC");
if ($invoices->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Appointment ID</th><th>Amount</th><th>Created</th></tr>";
    while ($row = $invoices->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['invoice_id']}</td>";
        echo "<td>{$row['appointment_id']}</td>";
        echo "<td>{$row['amount']}</td>";
        echo "<td>{$row['created_at']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No invoices found</p>";
}

// Check treatments table
echo "<h2>Treatments Table</h2>";
$treatments = $conn->query("SELECT * FROM treatments ORDER BY treatment_id DESC");
if ($treatments->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>ID</th><th>Appointment ID</th><th>Service ID</th><th>Notes</th><th>Fee</th></tr>";
    while ($row = $treatments->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['treatment_id']}</td>";
        echo "<td>{$row['appointment_id']}</td>";
        echo "<td>{$row['service_id']}</td>";
        echo "<td>{$row['notes']}</td>";
        echo "<td>{$row['fee']}</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "<p>No treatments found</p>";
}

// Show the SQL query used in payments.php
echo "<h2>SQL Query Used</h2>";
$query = "SELECT i.invoice_id, i.appointment_id, i.amount as invoice_amount, i.created_at as invoice_date, 
          p.patient_info_id, CONCAT(p.first_name, ' ', p.last_name) as patient_name,
          a.appointment_date, a.status as appointment_status,
          COALESCE(SUM(pay.amount), 0) as paid_amount,
          s.name as service_name
          FROM invoices i
          LEFT JOIN appointments a ON i.appointment_id = a.appointment_id
          LEFT JOIN patients p ON a.patient_id = p.patient_info_id
          LEFT JOIN services s ON a.service_id = s.services_id
          LEFT JOIN payments pay ON i.invoice_id = pay.invoice_id
          GROUP BY i.invoice_id ORDER BY i.created_at DESC";

echo "<pre>" . htmlspecialchars($query) . "</pre>";

$result = $conn->query($query);
echo "<p>Query result count: " . $result->num_rows . "</p>";

if ($result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>Invoice ID</th><th>Patient</th><th>Service</th><th>Amount</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>{$row['invoice_id']}</td>";
        echo "<td>{$row['patient_name']}</td>";
        echo "<td>{$row['service_name']}</td>";
        echo "<td>{$row['invoice_amount']}</td>";
        echo "</tr>";
    }
    echo "</table>";
}
?>