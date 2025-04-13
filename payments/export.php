<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\payments\export.php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

// Get export format and filters
$format = $_GET['format'] ?? 'excel';
$filter_patient = $_GET['patient_id'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build query based on filters (same as in payments.php)
$query = "SELECT i.invoice_id, i.appointment_id, i.amount as invoice_amount, i.created_at as invoice_date, 
          p.patient_info_id, CONCAT(p.first_name, ' ', p.last_name) as patient_name,
          a.appointment_date, a.status as appointment_status,
          (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE invoice_id = i.invoice_id) as paid_amount,
          s.name as service_name
          FROM invoices i
          LEFT JOIN appointments a ON i.appointment_id = a.appointment_id
          LEFT JOIN patients p ON a.patient_id = p.patient_info_id
          LEFT JOIN services s ON a.service_id = s.services_id
          WHERE 1=1";

// Apply filters (same as in payments.php)
if (!empty($filter_patient)) {
    $query .= " AND p.patient_info_id = '$filter_patient'";
}
if (!empty($filter_date_from)) {
    $query .= " AND i.created_at >= '$filter_date_from 00:00:00'";
}
if (!empty($filter_date_to)) {
    $query .= " AND i.created_at <= '$filter_date_to 23:59:59'";
}
if (!empty($search)) {
    $query .= " AND (p.first_name LIKE '%$search%' OR p.last_name LIKE '%$search%' OR i.invoice_id LIKE '%$search%')";
}

$query .= " GROUP BY i.invoice_id ORDER BY i.created_at DESC";
$result = $conn->query($query);

// Prepare data array
$data = [];
$data[] = ['Invoice #', 'Patient', 'Service', 'Date', 'Total Amount', 'Paid Amount', 'Balance', 'Status'];

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $balance = $row['invoice_amount'] - $row['paid_amount'];
        
        // Determine payment status
        if ($balance <= 0) {
            $status = "Paid";
        } else if ($row['paid_amount'] > 0) {
            $status = "Partial";
        } else {
            $status = "Unpaid";
        }
        
        $data[] = [
            'INV-' . $row['invoice_id'],
            $row['patient_name'],
            $row['service_name'],
            date('Y-m-d', strtotime($row['invoice_date'])),
            $row['invoice_amount'],
            $row['paid_amount'],
            $balance,
            $status
        ];
    }
}

// Generate filename
$filename = 'payments_export_' . date('Y-m-d_H-i-s');

// Export based on format
if ($format === 'excel') {
    // Set headers for Excel download
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment; filename="' . $filename . '.xls"');
    
    // Output Excel file content
    echo "<table border='1'>";
    foreach ($data as $row) {
        echo "<tr>";
        foreach ($row as $cell) {
            echo "<td>" . htmlspecialchars($cell) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
} else {
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '.csv"');
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Output each row
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    
    // Close output stream
    fclose($output);
}
exit;
?>