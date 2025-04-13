<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\payments\get_invoice_details.php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

$invoice_id = $_GET['invoice_id'] ?? 0;

if (empty($invoice_id)) {
    echo '<div class="alert alert-danger">Invalid invoice ID</div>';
    exit;
}

// Get invoice details
$query = "SELECT i.invoice_id, i.appointment_id, i.amount, i.created_at, 
          a.appointment_date, a.appointment_time, 
          p.patient_info_id, p.first_name, p.last_name, p.contact_number, p.address, p.email,
          s.name as service_name, s.description as service_description,
          t.treatment_id, t.notes as treatment_notes, t.fee as treatment_fee
          FROM invoices i
          JOIN appointments a ON i.appointment_id = a.appointment_id
          JOIN patients p ON a.patient_id = p.patient_info_id
          JOIN services s ON a.service_id = s.services_id
          LEFT JOIN treatments t ON a.appointment_id = t.appointment_id
          WHERE i.invoice_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $invoice_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="alert alert-danger">Invoice not found</div>';
    exit;
}

$invoice = $result->fetch_assoc();

// Get payments for this invoice
$payments_query = "SELECT payment_id, amount, payment_date, payment_method, notes, created_at 
                   FROM payments 
                   WHERE invoice_id = ? 
                   ORDER BY payment_date";
$payments_stmt = $conn->prepare($payments_query);
$payments_stmt->bind_param('i', $invoice_id);
$payments_stmt->execute();
$payments_result = $payments_stmt->get_result();

// Calculate totals
$total_paid = 0;
while ($payment = $payments_result->fetch_assoc()) {
    $total_paid += $payment['amount'];
    $payments[] = $payment;
}
$balance = $invoice['amount'] - $total_paid;

// Reset payments result
$payments_stmt->execute();
$payments_result = $payments_stmt->get_result();
?>

<div class="row">
    <div class="col-md-12 mb-4">
        <h4>Invoice #INV-<?php echo $invoice_id; ?></h4>
        <p class="text-muted">Created on <?php echo date('M d, Y h:i A', strtotime($invoice['created_at'])); ?></p>
    </div>
    
    <div class="col-md-6">
        <h5>Patient Information</h5>
        <table class="table table-sm">
            <tr>
                <th>Name:</th>
                <td><?php echo $invoice['first_name'] . ' ' . $invoice['last_name']; ?></td>
            </tr>
            <tr>
                <th>Contact:</th>
                <td><?php echo $invoice['contact_number']; ?></td>
            </tr>
            <tr>
                <th>Email:</th>
                <td><?php echo $invoice['email']; ?></td>
            </tr>
            <tr>
                <th>Address:</th>
                <td><?php echo $invoice['address']; ?></td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-6">
        <h5>Treatment Information</h5>
        <table class="table table-sm">
            <tr>
                <th>Appointment:</th>
                <td><?php echo date('M d, Y', strtotime($invoice['appointment_date'])) . ' at ' . 
                           date('h:i A', strtotime($invoice['appointment_time'])); ?></td>
            </tr>
            <tr>
                <th>Service:</th>
                <td><?php echo $invoice['service_name']; ?></td>
            </tr>
            <tr>
                <th>Description:</th>
                <td><?php echo $invoice['service_description']; ?></td>
            </tr>
            <tr>
                <th>Treatment Notes:</th>
                <td><?php echo nl2br($invoice['treatment_notes'] ?? ''); ?></td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-12 mt-4">
        <h5>Financial Summary</h5>
        <table class="table">
            <tr>
                <th>Total Invoice Amount:</th>
                <td>₱<?php echo number_format($invoice['amount'], 2); ?></td>
            </tr>
            <tr>
                <th>Total Paid:</th>
                <td>₱<?php echo number_format($total_paid, 2); ?></td>
            </tr>
            <tr class="<?php echo ($balance <= 0) ? 'table-success' : 'table-warning'; ?>">
                <th>Remaining Balance:</th>
                <td>₱<?php echo number_format($balance, 2); ?></td>
            </tr>
        </table>
    </div>
    
    <div class="col-md-12 mt-4">
        <h5>Payment History</h5>
        <?php if ($payments_result->num_rows > 0): ?>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Payment ID</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Method</th>
                        <th>Notes</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($payment = $payments_result->fetch_assoc()): ?>
                        <tr>
                            <td>PMT-<?php echo $payment['payment_id']; ?></td>
                            <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                            <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                            <td><?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?></td>
                            <td><?php echo $payment['notes'] ?? ''; ?></td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="alert alert-info">No payments recorded yet.</div>
        <?php endif; ?>
    </div>
</div>