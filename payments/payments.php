<?php
// Start output buffering to prevent "headers already sent" error
ob_start();

require_once __DIR__ . '/../admin_check.php';
include '../database.php';

// Set default filter values
$filter_patient = $_GET['patient_id'] ?? '';
$filter_status = $_GET['status'] ?? '';
$filter_date_from = $_GET['date_from'] ?? '';
$filter_date_to = $_GET['date_to'] ?? '';
$search = $_GET['search'] ?? '';

// Build the invoice query
$invoice_query = "SELECT i.*, 
                 a.appointment_date, a.appointment_time, a.patient_id, a.service_id, a.status as appointment_status,
                 s.name as service_name,
                 CONCAT(p.first_name, ' ', p.last_name) as patient_name
                 FROM invoices i
                 LEFT JOIN appointments a ON i.appointment_id = a.appointment_id
                 LEFT JOIN services s ON a.service_id = s.services_id
                 LEFT JOIN patients p ON a.patient_id = p.patient_info_id
                 WHERE 1=1";

// Apply filters
if (!empty($filter_patient)) {
    $invoice_query .= " AND p.patient_info_id = '$filter_patient'";
}
if (!empty($filter_date_from)) {
    $invoice_query .= " AND i.created_at >= '$filter_date_from 00:00:00'";
}
if (!empty($filter_date_to)) {
    $invoice_query .= " AND i.created_at <= '$filter_date_to 23:59:59'";
}
if (!empty($search)) {
    $invoice_query .= " AND (p.first_name LIKE '%$search%' OR p.last_name LIKE '%$search%' OR i.invoice_id LIKE '%$search%')";
}

$invoice_query .= " ORDER BY i.created_at DESC";
$invoice_result = $conn->query($invoice_query);

// Get payment data in separate query
$payment_query = "SELECT invoice_id, SUM(amount) as total_paid FROM payments GROUP BY invoice_id";
$payment_result = $conn->query($payment_query);

// Create a map of invoice_id to total_paid
$payment_map = [];
if ($payment_result && $payment_result->num_rows > 0) {
    while ($row = $payment_result->fetch_assoc()) {
        $payment_map[$row['invoice_id']] = $row['total_paid'];
    }
}

// Filter by payment status if needed
$filtered_invoices = [];
if ($invoice_result && $invoice_result->num_rows > 0) {
    while ($row = $invoice_result->fetch_assoc()) {
        $paid_amount = $payment_map[$row['invoice_id']] ?? 0;
        $balance = $row['amount'] - $paid_amount;
        $row['paid_amount'] = $paid_amount;
        $row['balance'] = $balance;

        if (empty($filter_status)) {
            $filtered_invoices[] = $row;
        } else if ($filter_status == 'paid' && $balance <= 0) {
            $filtered_invoices[] = $row;
        } else if ($filter_status == 'unpaid' && $paid_amount == 0) {
            $filtered_invoices[] = $row;
        } else if ($filter_status == 'partial' && $paid_amount > 0 && $balance > 0) {
            $filtered_invoices[] = $row;
        }
    }
}

// Calculate totals
$total_amount = 0;
$total_paid = 0;
$total_balance = 0;

foreach ($filtered_invoices as $invoice) {
    $total_amount += $invoice['amount'];
    $total_paid += $invoice['paid_amount'];
    $total_balance += $invoice['balance'];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management | City Smiles Dental Clinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        :root {
            --primary-green: #3cb371;
            --light-green: #9aeaa1;
            --dark-green: #297859;
            --primary-lilac: #9d7ded;
            --light-lilac: #e0d4f9;
            --dark-lilac: #7B32AB;
            --navy: #000069;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --red: #dc3545;
            --amber: #ffc107;
            --success: #28a745;
            --border-radius: 12px;
            --shadow-sm: 0 2px 4px rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 8px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 8px 16px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --cs-sidebar-width: 250px;
            --cs-sidebar-collapsed-width: 70px;
            --transition-speed: 0.3s;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            color: var(--gray-800);
            line-height: 1.6;
            padding-bottom: 2rem;
            transition: all var(--transition-speed) ease;
        }

        .container {
            max-width: calc(100% - 2rem);
            width: 1200px;
            margin: 0 auto;
            padding: 0 1rem;
            transition: max-width 0.3s ease;
        }

        @media (max-width: 992px) {
            body {
                margin-left: 0;
            }
            .container {
                max-width: 100%;
                padding: 0 1rem;
            }
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--gray-300);
        }

        .page-title {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .page-title h1 {
            font-size: 1.75rem;
            font-weight: 600;
            color: var(--dark-lilac);
            margin: 0;
        }

        .page-title p {
            color: var(--gray-600);
            margin-top: 0.25rem;
        }

        .page-icon {
            background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
            color: white;
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-sm);
        }

        .page-icon i {
            font-size: 1.5rem;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.65rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            font-size: 0.9rem;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            border: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-lilac), var(--dark-lilac));
            color: white;
            box-shadow: var(--shadow-sm);
        }

        .btn-outline {
            background: white;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .btn-primary:hover {
            background: linear-gradient(135deg, var(--dark-lilac), var(--primary-lilac));
        }

        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .card-header {
            background: linear-gradient(to right, var(--light-lilac), var(--light-green));
            padding: 1.25rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--gray-800);
            margin: 0;
        }

        .card-body {
            padding: 1.25rem;
        }

        .filter-section {
            margin-bottom: 2rem;
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray-700);
            margin-bottom: 0.5rem;
        }

        .form-control {
            display: block;
            width: 100%;
            padding: 0.65rem;
            font-size: 0.9rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            background-color: white;
            transition: var(--transition);
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-lilac);
            box-shadow: 0 0 0 3px rgba(157, 125, 237, 0.2);
        }

        .filter-buttons {
            display: flex;
            justify-content: flex-end;
            align-items: flex-end;
            gap: 1rem;
        }

        .table-responsive {
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        th,
        td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--gray-200);
        }

        th {
            color: var(--gray-700);
            font-weight: 600;
            background-color: var(--gray-100);
            position: sticky;
            top: 0;
        }

        tr:hover {
            background-color: var(--gray-100);
        }

        .payment-status {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 50px;
            font-size: 0.8rem;
            font-weight: 500;
            text-align: center;
            min-width: 90px;
        }

        .status-paid {
            background-color: var(--light-green);
            color: var(--dark-green);
        }

        .status-partial {
            background-color: var(--amber);
            color: #856404;
        }

        .status-unpaid {
            background-color: #f8d7da;
            color: var(--red);
        }

        .export-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .export {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 6px;
            background-color: white;
            color: var(--gray-700);
            border: 1px solid var(--gray-300);
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .export:hover {
            border-color: var(--gray-400);
            background-color: var(--gray-100);
        }

        .summary-section {
            display: flex;
            justify-content: space-between;
            margin-bottom: 2rem;
            gap: 1rem;
        }

        .summary-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 1.25rem;
            flex: 1;
            box-shadow: var(--shadow-md);
            border-top: 4px solid;
        }

        .summary-card:nth-child(1) {
            border-top-color: var(--dark-lilac);
        }

        .summary-card:nth-child(2) {
            border-top-color: var(--dark-green);
        }

        .summary-card:nth-child(3) {
            border-top-color: var(--red);
        }

        .summary-label {
            font-size: 0.9rem;
            color: var(--gray-600);
            margin-bottom: 0.5rem;
        }

        .summary-value {
            font-size: 1.5rem;
            font-weight: 600;
        }

        /* Action buttons */
        .action-btn {
            width: 32px;
            height: 32px;
            border-radius: 6px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: white;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            margin-right: 0.5rem;
        }

        .action-btn:last-child {
            margin-right: 0;
        }

        .btn-view {
            background-color: var(--primary-lilac);
        }

        .btn-pay {
            background-color: var(--primary-green);
        }

        .btn-print {
            background-color: var(--gray-600);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
            opacity: 0.9;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.3s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        .modal-dialog {
            margin: 5% auto;
            width: 90%;
            max-width: 600px;
        }

        .modal-content {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: slideDown 0.3s;
        }

        @keyframes slideDown {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .modal-header {
            background: linear-gradient(to right, var(--primary-lilac), var(--primary-green));
            color: white;
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .btn-close {
            background: transparent;
            border: none;
            color: white;
            font-size: 1.5rem;
            cursor: pointer;
            opacity: 0.8;
            transition: var(--transition);
        }

        .btn-close:hover {
            opacity: 1;
        }

        .modal-body {
            padding: 1.5rem;
        }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            padding: 1.5rem;
            border-top: 1px solid var(--gray-200);
        }

        @media print {
            .no-print {
                display: none;
            }

            .print-only {
                display: block;
            }

            body {
                margin: 0;
                padding: 0;
            }
        }

        .print-only {
            display: none;
        }

        @media (max-width: 992px) {
            .summary-section {
                flex-direction: column;
            }

            body {
                margin-left: 0;
            }
        }

        @media (max-width: 768px) {
            .page-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }

            .filter-grid {
                grid-template-columns: 1fr;
            }
        }

        .action-buttons {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            justify-content: flex-start;
        }

        .action-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 6px 12px;
            border-radius: 6px;
            color: white;
            border: none;
            font-size: 0.85rem;
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
            min-width: auto;
            width: auto;
            height: auto;
            margin: 0;
        }

        .btn-text {
            display: inline-block;
            white-space: nowrap;
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .btn-view {
            background-color: var(--primary-lilac);
        }

        .btn-pay {
            background-color: var(--primary-green);
        }

        .btn-print {
            background-color: var(--gray-600);
        }

        @media (max-width: 768px) {
            .action-buttons {
                flex-direction: column;
                gap: 6px;
            }
        }
        .content {
    margin-left: var(--cs-sidebar-width);
    margin-top: 80px; /* Add this line for top margin */
    padding: 20px;
    transition: all var(--transition-speed) ease;
    min-height: 100vh;
    width: calc(100% - var(--cs-sidebar-width));
    box-sizing: border-box;
}

/* Update the responsive styles for content as well */
@media screen and (max-width: 992px) {
    .content {
        margin-left: 0;
        margin-top: 60px; /* Slightly smaller margin for mobile */
        width: 100%;
        padding: 15px;
    }
}

        body.cs-sidebar-collapsed .content {
            margin-left: var(--cs-sidebar-collapsed-width);
            width: calc(100% - var(--cs-sidebar-collapsed-width));
        }

        /* Responsive layout adjustments */
        @media screen and (max-width: 1200px) {
            .staff-grid {
                grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
                gap: 20px;
            }

            .stats-row {
                grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            }
        }

        @media screen and (max-width: 992px) {
            .content {
                margin-left: 0;
                width: 100%;
                padding: 15px;
            }

            body.cs-sidebar-collapsed .content {
                margin-left: 0;
                width: 100%;
            }}
            .content {
            margin-left: var(--cs-sidebar-width);
            transition: margin-left 0.3s ease;
            padding: 20px;
        }
        
        body.cs-sidebar-collapsed .content {
            margin-left: var(--cs-sidebar-collapsed-width);
        }
        
    </style>
</head>

<body class="<?php echo isset($_SESSION['sidebar_collapsed']) && $_SESSION['sidebar_collapsed'] ? 'cs-sidebar-collapsed' : ''; ?>">
    <?php include '../sidebar/sidebar.php'; ?>

    <div class="content">
        <div class="page-header">
            <div class="page-title" style="width: 100%;">
                <div
                    style="background-color: #ffffff; border-radius: 12px; padding: 15px 20px; box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05); display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <div style="display: flex; align-items: center; gap: 15px;">
                        <div class="page-icon"
                            style="background: linear-gradient(135deg, var(--primary-green), var(--dark-green));">
                            <i class="fas fa-file-invoice-dollar"></i>
                        </div>
                        <div>
                            <h1>Payment Management</h1>
                            <p>Manage invoices and payments for dental services</p>
                        </div>
                    </div>
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addInvoiceModal">
                        <i class="fas fa-plus"></i> Create Invoice
                    </a>
                </div>
            </div>
        </div>

        <!-- Summary Cards Section -->
        <div class="summary-section">
            <div class="summary-card">
                <div class="summary-label">Total Amount</div>
                <div class="summary-value">₱<?php echo number_format($total_amount, 2); ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Total Paid</div>
                <div class="summary-value">₱<?php echo number_format($total_paid, 2); ?></div>
            </div>
            <div class="summary-card">
                <div class="summary-label">Total Balance</div>
                <div class="summary-value">₱<?php echo number_format($total_balance, 2); ?></div>
            </div>
        </div>

        <!-- Filter Section Card -->
        <div class="card no-print">
            <div class="card-header">
                <h2 class="card-title">Filter Options</h2>
            </div>
            <div class="card-body">
                <form method="GET" action="">
                    <div class="filter-grid">
                        <div class="form-group">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search"
                                placeholder="Name or Invoice #" value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                        <div class="form-group">
                            <label for="status" class="form-label">Payment Status</label>
                            <select class="form-control" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="paid" <?php echo ($filter_status == 'paid') ? 'selected' : ''; ?>>Paid
                                </option>
                                <option value="unpaid" <?php echo ($filter_status == 'unpaid') ? 'selected' : ''; ?>>
                                    Unpaid
                                </option>
                                <option value="partial" <?php echo ($filter_status == 'partial') ? 'selected' : ''; ?>>
                                    Partial
                                </option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="date_from" class="form-label">Date From</label>
                            <input type="date" class="form-control" id="date_from" name="date_from"
                                value="<?php echo $filter_date_from; ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_to" class="form-label">Date To</label>
                            <input type="date" class="form-control" id="date_to" name="date_to"
                                value="<?php echo $filter_date_to; ?>">
                        </div>
                        <div class="form-group filter-buttons">
                            <button type="submit" class="btn btn-primary">Apply Filters</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Export Buttons -->
        <div class="export-buttons no-print">
            <button class="export" onclick="window.print()"><i class="fas fa-print"></i> Print</button>
            <button class="export" onclick="exportToExcel()"><i class="fas fa-file-excel"></i> Excel</button>
            <button class="export" onclick="exportToCSV()"><i class="fas fa-file-csv"></i> CSV</button>
        </div>

        <!-- Invoices and Payments Table Card -->
        <div class="card">
            <div class="card-header">
                <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                    <div>
                        <h2 class="card-title"><i class="fas fa-file-invoice-dollar me-2"></i> Payment Records</h2>
                        <span class="text-muted" style="font-size: 0.9rem;">
                            <?php echo count($filtered_invoices); ?> records found
                        </span>
                    </div>
                    <div>
                        <select class="form-control" id="sortBy" onchange="sortTable(this.value)"
                            style="max-width: 200px; display: inline-block;">
                            <option value="date_desc">Date (Newest First)</option>
                            <option value="date_asc">Date (Oldest First)</option>
                            <option value="amount_desc">Amount (Highest First)</option>
                            <option value="amount_asc">Amount (Lowest First)</option>
                            <option value="status">Payment Status</option>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th style="width: 10%;">Invoice #</th>
                                <th style="width: 15%;">Patient</th>
                                <th style="width: 15%;">Service</th>
                                <th style="width: 10%;">Date</th>
                                <th style="width: 10%;">Total Amount</th>
                                <th style="width: 10%;">Paid Amount</th>
                                <th style="width: 10%;">Balance</th>
                                <th style="width: 10%;">Status</th>
                                <th class="no-print" style="width: 20%;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if (count($filtered_invoices) > 0) {
                                foreach ($filtered_invoices as $row) {
                                    $balance = $row['amount'] - $row['paid_amount'];

                                    // Determine payment status
                                    if ($balance <= 0) {
                                        $status = "Paid";
                                        $status_class = "status-paid";
                                        $icon = "<i class='fas fa-check-circle'></i> ";
                                    } else if ($row['paid_amount'] > 0) {
                                        $status = "Partial";
                                        $status_class = "status-partial";
                                        $icon = "<i class='fas fa-clock'></i> ";
                                    } else {
                                        $status = "Unpaid";
                                        $status_class = "status-unpaid";
                                        $icon = "<i class='fas fa-exclamation-circle'></i> ";
                                    }

                                    // Convert date to a more readable format
                                    $invoice_date = date('M d, Y', strtotime($row['created_at']));

                                    echo "<tr>";
                                    echo "<td><strong>INV-{$row['invoice_id']}</strong></td>";
                                    echo "<td>" . ($row['patient_name'] ?? 'N/A') . "</td>";
                                    echo "<td>" . ($row['service_name'] ?? 'N/A') . "</td>";
                                    echo "<td>{$invoice_date}</td>";
                                    echo "<td>₱" . number_format($row['amount'], 2) . "</td>";
                                    echo "<td>₱" . number_format($row['paid_amount'], 2) . "</td>";
                                    echo "<td>₱" . number_format($balance, 2) . "</td>";
                                    echo "<td><span class='payment-status {$status_class}'>{$icon}{$status}</span></td>";
                                    echo "<td class='no-print'>";
                                    echo "<div class='action-buttons'>";

                                    // View Invoice Details button
                                    echo "<button class='action-btn btn-view' onclick='viewInvoiceDetails({$row['invoice_id']})' title='View Details'>";
                                    echo "<i class='fas fa-eye'></i><span class='btn-text'>Details</span>";
                                    echo "</button>";

                                    // Add Payment button (only if balance > 0)
                                    if ($balance > 0) {
                                        echo "<button class='action-btn btn-pay' onclick='addPayment({$row['invoice_id']}, {$balance})' title='Add Payment'>";
                                        echo "<i class='fas fa-money-bill'></i><span class='btn-text'>Pay</span>";
                                        echo "</button>";
                                    }

                                    // Print Receipt button (only if has payments)
                                    if ($row['paid_amount'] > 0) {
                                        echo "<button class='action-btn btn-print' onclick='printReceipt({$row['invoice_id']})' title='Print Receipt'>";
                                        echo "<i class='fas fa-receipt'></i><span class='btn-text'>Receipt</span>";
                                        echo "</button>";
                                    }

                                    echo "</div>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='9' style='text-align: center; padding: 20px;'>";
                                echo "<div style='display: flex; flex-direction: column; align-items: center; gap: 10px;'>";
                                echo "<i class='fas fa-search' style='font-size: 2rem; color: var(--gray-400);'></i>";
                                echo "<p>No invoices found matching your criteria</p>";
                                echo "<a href='?' class='btn btn-outline' style='margin-top: 10px;'>Clear filters</a>";
                                echo "</div>";
                                echo "</td></tr>";
                            }
                            ?>
                        </tbody>
                    </table>
                </div>

                <?php if (count($filtered_invoices) > 15): ?>
                    <div class="pagination-container" style="display: flex; justify-content: center; margin-top: 20px;">
                        <nav aria-label="Page navigation">
                            <ul class="pagination" style="display: flex; list-style: none; gap: 5px;">
                                <li>
                                    <a href="#" class="btn btn-outline" aria-label="Previous" style="padding: 5px 10px;">
                                        <span aria-hidden="true">&laquo; Previous</span>
                                    </a>
                                </li>
                                <li>
                                    <a href="#" class="btn btn-primary" style="padding: 5px 10px;">1</a>
                                </li>
                                <li>
                                    <a href="#" class="btn btn-outline" style="padding: 5px 10px;">2</a>
                                </li>
                                <li>
                                    <a href="#" class="btn btn-outline" aria-label="Next" style="padding: 5px 10px;">
                                        <span aria-hidden="true">Next &raquo;</span>
                                    </a>
                                </li>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <script>
            function sortTable(sortBy) {
                // Get current URL parameters
                const urlParams = new URLSearchParams(window.location.search);

                // Add or update sort parameter
                urlParams.set('sort', sortBy);

                // Redirect with new sort parameter
                window.location.href = window.location.pathname + '?' + urlParams.toString();
            }
        </script>
    </div>

    <!-- Add Invoice Modal -->
    <div class="modal" id="addInvoiceModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Create New Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="invoiceForm">
                        <div class="form-group">
                            <label for="patient_select" class="form-label">Select Patient</label>
                            <select class="form-control" id="patient_select" required>
                                <option value="">-- Select Patient --</option>
                                <?php
                                $patients = $conn->query("SELECT patient_info_id, CONCAT(first_name, ' ', last_name) as name FROM patients ORDER BY last_name");
                                while ($patient = $patients->fetch_assoc()) {
                                    echo "<option value='{$patient['patient_info_id']}'>{$patient['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="appointment_select" class="form-label">Select Appointment</label>
                            <select class="form-control" id="appointment_select" required disabled>
                                <option value="">-- First Select Patient --</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="invoice_amount" class="form-label">Invoice Amount</label>
                            <input type="number" class="form-control" id="invoice_amount" min="0" step="0.01" required>
                        </div>

                        <div class="form-group">
                            <label for="invoice_notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="invoice_notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveInvoiceBtn">Save Invoice</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Payment Modal -->
    <div class="modal" id="addPaymentModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <form id="paymentForm">
                        <input type="hidden" id="payment_invoice_id">

                        <div class="form-group">
                            <label for="payment_amount" class="form-label">Payment Amount</label>
                            <input type="number" class="form-control" id="payment_amount" min="0" step="0.01" required>
                            <small class="text-muted">Maximum balance: ₱<span id="max_payment"></span></small>
                        </div>

                        <div class="form-group">
                            <label for="payment_method" class="form-label">Payment Method</label>
                            <select class="form-control" id="payment_method" required>
                                <option value="cash">Cash</option>
                                <option value="credit_card">Credit Card</option>
                                <option value="insurance">Insurance</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="other">Other</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="payment_date" class="form-label">Payment Date</label>
                            <input type="date" class="form-control" id="payment_date"
                                value="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="payment_notes" class="form-label">Notes</label>
                            <textarea class="form-control" id="payment_notes" rows="2"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="savePaymentBtn">Save Payment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Invoice Details Modal -->
    <div class="modal" id="viewInvoiceModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Invoice Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body" id="invoiceDetailsContent">
                    <!-- Invoice details will be loaded here -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printInvoiceDetails()">Print</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Bootstrap modal implementation without Bootstrap JS
        function setupModals() {
            // Get all elements with modal attribute
            const modalTriggers = document.querySelectorAll('[data-bs-toggle="modal"]');
            const modalClosers = document.querySelectorAll('[data-bs-dismiss="modal"]');

            // Add click handlers to all triggers
            modalTriggers.forEach(trigger => {
                const targetId = trigger.getAttribute('data-bs-target').substring(1);
                const modal = document.getElementById(targetId);

                trigger.addEventListener('click', () => {
                    modal.style.display = 'block';
                });
            });

            // Add click handlers to close buttons
            modalClosers.forEach(closer => {
                const modal = closer.closest('.modal');
                closer.addEventListener('click', () => {
                    modal.style.display = 'none';
                });
            });

            // Close modal when clicked outside
            window.addEventListener('click', (e) => {
                document.querySelectorAll('.modal').forEach(modal => {
                    if (e.target === modal) {
                        modal.style.display = 'none';
                    }
                });
            });
        }

        document.addEventListener('DOMContentLoaded', setupModals);

        // When patient is selected, load their appointments
        $('#patient_select').change(function () {
            const patientId = $(this).val();
            if (patientId) {
                $.ajax({
                    url: 'get_patient_appointments.php',
                    type: 'GET',
                    data: { patient_id: patientId },
                    success: function (data) {
                        $('#appointment_select').html(data);
                        $('#appointment_select').prop('disabled', false);
                    }
                });
            } else {
                $('#appointment_select').html('<option value="">-- First Select Patient --</option>');
                $('#appointment_select').prop('disabled', true);
            }
        });

        // Save new invoice
        $('#saveInvoiceBtn').click(function () {
            const appointmentId = $('#appointment_select').val();
            const amount = $('#invoice_amount').val();
            const notes = $('#invoice_notes').val();

            if (!amount) {
                alert('Please enter an amount');
                return;
            }

            // Disable the button to prevent double submission
            $(this).prop('disabled', true);
            $(this).html('<i class="fa fa-spinner fa-spin"></i> Saving...');

            $.ajax({
                url: 'save_invoice.php',
                type: 'POST',
                data: {
                    appointment_id: appointmentId,
                    amount: amount,
                    notes: notes
                },
                dataType: 'json',
                success: function (response) {
                    console.log('Response:', response);
                    if (response.success) {
                        alert('Invoice #' + response.invoice_id + ' created successfully');
                        // Force page reload with cache bust parameter
                        window.location.href = window.location.pathname + '?refresh=' + new Date().getTime();
                    } else {
                        alert('Error: ' + (response.error || 'Unknown error occurred'));
                        $('#saveInvoiceBtn').prop('disabled', false);
                        $('#saveInvoiceBtn').html('Save Invoice');
                    }
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', xhr.responseText);
                    alert('Error creating invoice. Please check the console for details.');
                    $('#saveInvoiceBtn').prop('disabled', false);
                    $('#saveInvoiceBtn').html('Save Invoice');
                }
            });
        });

        // Function to open payment modal
        function addPayment(invoiceId, balance) {
            $('#payment_invoice_id').val(invoiceId);
            $('#max_payment').text(balance.toFixed(2));
            $('#payment_amount').attr('max', balance);
            $('#addPaymentModal').css('display', 'block');
        }

        // Save payment
        $('#savePaymentBtn').click(function () {
            const invoiceId = $('#payment_invoice_id').val();
            const amount = $('#payment_amount').val();
            const paymentMethod = $('#payment_method').val();
            const paymentDate = $('#payment_date').val();
            const notes = $('#payment_notes').val();

            if (!invoiceId || !amount || !paymentMethod || !paymentDate) {
                alert('Please fill all required fields');
                return;
            }

            $.ajax({
                url: 'save_payment.php',
                type: 'POST',
                data: {
                    invoice_id: invoiceId,
                    amount: amount,
                    payment_method: paymentMethod,
                    payment_date: paymentDate,
                    notes: notes
                },
                dataType: 'json',
                success: function (response) {
                    if (response.success) {
                        alert('Payment recorded successfully');
                        $('#addPaymentModal').css('display', 'none');
                        location.reload();
                    } else {
                        alert('Error: ' + (response.error || 'Unknown error occurred'));
                    }
                },
                error: function () {
                    alert('Error recording payment');
                }
            });
        });

        // View invoice details
        function viewInvoiceDetails(invoiceId) {
            $.ajax({
                url: 'get_invoice_details.php',
                type: 'GET',
                data: { invoice_id: invoiceId },
                success: function (data) {
                    $('#invoiceDetailsContent').html(data);
                    $('#viewInvoiceModal').css('display', 'block');
                }
            });
        }

        // Print invoice details
        function printInvoiceDetails() {
            const content = document.getElementById('invoiceDetailsContent').innerHTML;
            const printWindow = window.open('', '_blank');

            printWindow.document.write(`
                <html>
                <head>
                    <title>Invoice Details</title>
                    <style>
                        body { font-family: 'Inter', sans-serif; padding: 20px; }
                        .logo { text-align: center; margin-bottom: 20px; }
                        .clinic-info { text-align: center; margin-bottom: 30px; }
                        .footer { margin-top: 50px; text-align: center; font-size: 12px; }
                        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                        th { background-color: #f5f7fa; }
                    </style>
                </head>
                <body>
                    <div class="logo">
                        <img src="../images/Screenshot__522_-removebg-preview.png" alt="City Smiles Dental Clinic" style="max-width: 150px;">
                    </div>
                    <div class="clinic-info">
                        <h3>City Smiles Dental Clinic</h3>
                        <p>123 Main Street, Brgy. San Antonio, Quezon City<br>
                        Tel: (02) 8123-4567 | Email: info@citysmiles.com</p>
                    </div>
                    ${content}
                    <div class="footer">
                        <p>Thank you for choosing City Smiles Dental Clinic!</p>
                    </div>
                </body>
                </html>
            `);

            printWindow.document.close();
            printWindow.focus();

            // Print after a short delay to allow styles to load
            setTimeout(() => {
                printWindow.print();
            }, 500);
        }

        // Print receipt
        function printReceipt(invoiceId) {
            window.open('print_receipt.php?invoice_id=' + invoiceId, '_blank');
        }

        // Export to Excel
        function exportToExcel() {
            window.location.href = 'export.php?format=excel&' + new URLSearchParams(window.location.search).toString();
        }

        // Export to CSV
        function exportToCSV() {
            window.location.href = 'export.php?format=csv&' + new URLSearchParams(window.location.search).toString();
        }

        // Add sidebar toggle handler
        document.addEventListener('DOMContentLoaded', function() {
            const body = document.querySelector('body');

            // Listen for sidebar toggle events
            document.addEventListener('sidebarToggle', function(e) {
                body.classList.toggle('cs-sidebar-collapsed', e.detail.isCollapsed);
            });

            // Initial state check
            const sidebar = document.querySelector('.sidebar');
            if (sidebar) {
                body.classList.toggle('cs-sidebar-collapsed', sidebar.classList.contains('collapsed'));
            }

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth <= 992) {
                    body.classList.remove('cs-sidebar-collapsed');
                } else {
                    if (sidebar && sidebar.classList.contains('collapsed')) {
                        body.classList.add('cs-sidebar-collapsed');
                    }
                }
            });
        });
    </script>
</body>

</html>