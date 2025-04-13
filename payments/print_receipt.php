<?php
// Keep the existing PHP code at the top unchanged
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

$invoice_id = $_GET['invoice_id'] ?? 0;

if (empty($invoice_id)) {
    echo '<div class="alert alert-danger">Invalid invoice ID</div>';
    exit;
}

// Get invoice and latest payment details
$query = "SELECT i.invoice_id, i.appointment_id, i.amount as invoice_amount, i.created_at as invoice_date,
          p.patient_info_id, p.first_name, p.last_name, p.contact_number,
          a.appointment_date, a.appointment_time,
          s.name as service_name,
          pay.payment_id, pay.amount as payment_amount, pay.payment_date, pay.payment_method,
          pay.created_at as payment_created_at
          FROM invoices i
          JOIN appointments a ON i.appointment_id = a.appointment_id
          JOIN patients p ON a.patient_id = p.patient_info_id
          JOIN services s ON a.service_id = s.services_id
          JOIN payments pay ON i.invoice_id = pay.invoice_id
          WHERE i.invoice_id = ?
          ORDER BY pay.payment_date DESC
          LIMIT 1";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $invoice_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo '<div class="alert alert-danger">Receipt not found</div>';
    exit;
}

$receipt = $result->fetch_assoc();

// Get total payments for this invoice
$total_query = "SELECT SUM(amount) as total_paid FROM payments WHERE invoice_id = ?";
$total_stmt = $conn->prepare($total_query);
$total_stmt->bind_param('i', $invoice_id);
$total_stmt->execute();
$total_result = $total_stmt->get_result();
$total_row = $total_result->fetch_assoc();
$total_paid = $total_row['total_paid'];
$balance = $receipt['invoice_amount'] - $total_paid;

// Get clinic info
$clinic_query = "SELECT * FROM clinic_info LIMIT 1";
$clinic_result = $conn->query($clinic_query);
$clinic = $clinic_result->fetch_assoc();

// Format receipt number
$receipt_number = 'RCPT-' . str_pad($receipt['payment_id'], 5, '0', STR_PAD_LEFT);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Receipt #<?php echo $receipt_number; ?></title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-green: #3cb371;
            --light-green: #9aeaa1;
            --dark-green: #297859;
            --primary-lilac: #9d7ded;
            --light-lilac: #e0d4f9;
            --dark-lilac: #7B32AB;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --border-radius: 12px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            line-height: 1.6;
            background-color: #f5f5f5;
            color: var(--gray-800);
            padding: 20px;
        }

        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            border-radius: var(--border-radius);
            overflow: hidden;
        }

        .receipt-header {
            position: relative;
            text-align: center;
            padding: 30px;
            background: linear-gradient(135deg, var(--light-lilac) 0%, var(--light-green) 100%);
        }

        .receipt-header::after {
            content: "";
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 6px;
            background: linear-gradient(90deg, var(--primary-lilac), var(--primary-green));
        }

        .receipt-header img {
            max-width: 120px;
            margin-bottom: 10px;
            filter: drop-shadow(0 2px 5px rgba(0, 0, 0, 0.1));
        }

        .receipt-title {
            font-size: 28px;
            font-weight: 700;
            margin: 10px 0;
            color: var(--dark-lilac);
        }

        .receipt-subtitle {
            color: var(--gray-700);
            font-size: 16px;
            margin-bottom: 5px;
        }

        .receipt-number-container {
            background: white;
            width: fit-content;
            margin: 20px auto 10px;
            padding: 10px 25px;
            border-radius: 50px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .receipt-number {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-green);
        }

        .receipt-date {
            font-size: 14px;
            color: var(--gray-600);
        }

        .receipt-body {
            padding: 30px;
        }

        .receipt-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--dark-lilac);
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--light-lilac);
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .info-item {
            margin-bottom: 10px;
        }

        .info-label {
            font-size: 14px;
            color: var(--gray-600);
            display: block;
            margin-bottom: 3px;
        }

        .info-value {
            font-size: 16px;
            font-weight: 500;
        }

        .receipt-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        .receipt-table th {
            background: var(--gray-100);
            padding: 12px;
            text-align: left;
            font-size: 14px;
            color: var(--gray-700);
            font-weight: 600;
        }

        .receipt-table td {
            padding: 12px;
            font-size: 15px;
            border-bottom: 1px solid var(--gray-200);
        }

        .receipt-table .amount {
            text-align: right;
            font-family: 'Inter', monospace;
            font-weight: 500;
        }

        .amount-value {
            font-family: 'Inter', monospace;
            font-weight: 500;
        }

        .totals {
            background-color: var(--gray-100);
            border-radius: 8px;
            padding: 15px;
            margin-top: 30px;
        }

        .totals-table {
            width: 100%;
        }

        .totals-table td {
            padding: 8px 12px;
        }

        .totals-table .total-label {
            color: var(--gray-700);
        }

        .totals-table .total-value {
            text-align: right;
            font-family: 'Inter', monospace;
            font-weight: 500;
        }

        .total-line {
            font-weight: 600;
            border-top: 2px solid var(--gray-300);
            padding-top: 8px !important;
            margin-top: 8px;
        }

        .payment-status {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            text-align: center;
        }

        .status-paid {
            background-color: var(--light-green);
            color: var(--dark-green);
        }

        .status-partial {
            background-color: #fff3cd;
            color: #856404;
        }

        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }

        .signature-box {
            width: 45%;
        }

        .signature-line {
            border-top: 1px solid var(--gray-400);
            padding-top: 8px;
            margin-top: 30px;
            text-align: center;
            font-size: 14px;
            color: var(--gray-600);
        }

        .receipt-footer {
            text-align: center;
            padding: 20px;
            background: linear-gradient(135deg, var(--light-green) 0%, var(--light-lilac) 100%);
            color: var(--gray-700);
            font-size: 14px;
            line-height: 1.5;
            border-top: 6px solid;
            border-image: linear-gradient(90deg, var(--primary-green), var(--primary-lilac)) 1;
        }

        .receipt-footer p:first-child {
            font-weight: 600;
            font-size: 16px;
            margin-bottom: 5px;
        }

        .receipt-footer p:last-child {
            font-size: 13px;
            color: var(--gray-600);
        }

        .receipt-watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-45deg);
            font-size: 120px;
            font-weight: 700;
            color: rgba(0, 0, 0, 0.03);
            letter-spacing: 10px;
            z-index: 0;
            pointer-events: none;
        }

        .button-container {
            text-align: center;
            margin-top: 30px;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background: linear-gradient(90deg, var(--primary-lilac), var(--primary-green));
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            margin: 0 10px;
        }

        .button:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .button.secondary {
            background: white;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }

        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: 1fr;
            }

            .signature-section {
                flex-direction: column;
                gap: 30px;
            }

            .signature-box {
                width: 100%;
            }
        }

        @media print {
            body {
                padding: 0;
                background: white;
            }

            .receipt-container {
                box-shadow: none;
                max-width: 100%;
            }

            .no-print {
                display: none;
            }

            .receipt-watermark {
                display: none;
            }

            @page {
                margin: 0.5cm;
                size: portrait;
            }
        }
    </style>
</head>

<body>
    <div class="receipt-container">
        <!-- Watermark (visible only on screen, not when printed) -->
        <div class="receipt-watermark">RECEIPT</div>

        <!-- Header Section -->
        <header class="receipt-header" style="padding: 15px;">
            <img src="..\images\Group 9.png" alt="City Smiles Dental Clinic" style="max-width: 70px;">
            <h1 class="receipt-title" style="margin: 5px 0;">City Smiles Dental Clinic</h1>
            <p class="receipt-subtitle" style="margin: 2px 0;">
                <?php echo $clinic['address'] ?? '123 Main St, Quezon City, Philippines'; ?></p>
            <p class="receipt-subtitle" style="margin: 2px 0;">Tel: <?php echo $clinic['phone'] ?? '(02) 8123-4567'; ?>
            </p>

            <div class="receipt-number-container" style="margin: 10px auto 5px;">
                <div class="receipt-number"><?php echo $receipt_number; ?></div>
                <div class="receipt-date"><?php echo date('F d, Y', strtotime($receipt['payment_date'])); ?></div>
            </div>
        </header>

        <!-- Body Section -->
        <div class="receipt-body" style="padding: 10px 20px;">
            <!-- Patient Info Section -->
            <section class="receipt-section" style="margin-bottom: 10px;">
                <h3 class="section-title" style="margin-bottom: 5px; font-size: 16px; padding-bottom: 5px;">Patient
                    Information</h3>
                <div class="info-grid" style="gap: 5px;">
                    <div>
                        <div class="info-item" style="margin-bottom: 3px;">
                            <span class="info-label">Patient Name</span>
                            <span
                                class="info-value"><?php echo $receipt['first_name'] . ' ' . $receipt['last_name']; ?></span>
                        </div>
                        <div class="info-item" style="margin-bottom: 3px;">
                            <span class="info-label">Contact Number</span>
                            <span class="info-value"><?php echo $receipt['contact_number']; ?></span>
                        </div>
                    </div>
                    <div>
                        <div class="info-item" style="margin-bottom: 3px;">
                            <span class="info-label">Invoice #</span>
                            <span class="info-value">INV-<?php echo $receipt['invoice_id']; ?></span>
                        </div>
                        <div class="info-item" style="margin-bottom: 3px;">
                            <span class="info-label">Payment Method</span>
                            <span
                                class="info-value"><?php echo ucfirst(str_replace('_', ' ', $receipt['payment_method'])); ?></span>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Payment Details Section -->
            <section class="receipt-section" style="margin-bottom: 10px;">
                <h3 class="section-title" style="margin-bottom: 5px; font-size: 16px; padding-bottom: 5px;">Payment
                    Details</h3>
                <table class="receipt-table" style="margin-top: 3px;">
                    <thead>
                        <tr>
                            <th width="50%">Description</th>
                            <th>Date</th>
                            <th class="amount">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo $receipt['service_name']; ?> (Invoice
                                #INV-<?php echo $receipt['invoice_id']; ?>)</td>
                            <td><?php echo date('M d, Y', strtotime($receipt['appointment_date'])); ?></td>
                            <td class="amount">₱<?php echo number_format($receipt['invoice_amount'], 2); ?></td>
                        </tr>
                    </tbody>
                </table>

                <!-- Totals Section -->
                <div class="totals" style="margin-top: 10px; padding: 8px;">
                    <table class="totals-table">
                        <tr>
                            <td class="total-label">Total Invoice Amount</td>
                            <td class="total-value">₱<?php echo number_format($receipt['invoice_amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <td class="total-label">Previous Payments</td>
                            <td class="total-value">
                                ₱<?php echo number_format($total_paid - $receipt['payment_amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <td class="total-label">This Payment</td>
                            <td class="total-value">₱<?php echo number_format($receipt['payment_amount'], 2); ?></td>
                        </tr>
                        <tr>
                            <td class="total-label">Remaining Balance</td>
                            <td class="total-value">₱<?php echo number_format($balance, 2); ?></td>
                        </tr>
                        <tr>
                            <td class="total-label total-line">Payment Status</td>
                            <td class="total-value total-line">
                                <span
                                    class="payment-status <?php echo ($balance <= 0) ? 'status-paid' : 'status-partial'; ?>">
                                    <?php echo ($balance <= 0) ? 'PAID IN FULL' : 'PARTIAL PAYMENT'; ?>
                                </span>
                            </td>
                        </tr>
                    </table>
                </div>
            </section>

            <!-- Signature Section -->
            <div class="signature-section" style="margin-top: 10px;">
                <div class="signature-box">
                    <div class="signature-line">
                        Received By
                    </div>
                </div>
                <div class="signature-box">
                    <div class="signature-line">
                        Authorized Signature
                    </div>
                </div>
            </div>
        </div>

        <!-- Footer Section -->
        <div class="receipt-footer" style="padding: 8px; font-size: 12px;">
            <p>Thank you for choosing City Smiles Dental Clinic!</p>
            <p>This receipt is your proof of payment. Please keep it for your records.</p>
        </div>
    </div>

    <!-- Print & Close Buttons (not shown when printing) -->
    <div class="button-container no-print">
        <button class="button" onclick="window.print()">Print Receipt</button>
        <button class="button secondary" onclick="window.close()">Close</button>
    </div>

    <style>
        @media print {
            body {
                padding: 0;
                margin: 0;
                background: white;
            }

            .receipt-container {
                box-shadow: none;
                width: 100%;
                max-width: 100%;
            }

            .receipt-watermark {
                display: none;
            }

            @page {
                size: letter;
                margin: 0.4in 0.25in;
            }
        }
    </style>

    <script>
        // Auto-print when page loads
        window.onload = function () {
            setTimeout(function () {
                window.print();
            }, 800);
        };
    </script>
</body>

</html>