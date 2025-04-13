<?php
// Start output buffering to prevent "headers already sent" error
ob_start();

// Include database and session start
include '../database.php';
session_start();

// Check authorization first (move this from sidebar.php)
if (
    !isset($_SESSION['user_id']) || !isset($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'staff', 'dentist', 'assistant', 'intern'])
) {
    header('Location: ../login/login.php');
    exit();
}

// Now continue with the rest of your code
$filter = isset($_GET['filter']) ? $_GET['filter'] : '';

// Fetch all invoices and their payment data
$invoice_query = "
    SELECT i.invoice_id, i.amount, i.created_at, 
           CONCAT(p.first_name, ' ', p.last_name) AS patient_name
    FROM invoices i
    LEFT JOIN appointments a ON i.appointment_id = a.appointment_id
    LEFT JOIN patients p ON a.patient_id = p.patient_info_id
    WHERE 1=1
";

$invoice_query .= " ORDER BY i.created_at DESC";

$invoice_result = $conn->query($invoice_query);

// Fetch payment data
$payment_query = "SELECT invoice_id, SUM(amount) AS total_paid FROM payments GROUP BY invoice_id";
$payment_result = $conn->query($payment_query);

// Create a map of invoice_id to total_paid
$payment_map = [];
if ($payment_result && $payment_result->num_rows > 0) {
    while ($row = $payment_result->fetch_assoc()) {
        $payment_map[$row['invoice_id']] = $row['total_paid'];
    }
}

// Process invoices and calculate payment status
$filtered_invoices = [];
if ($invoice_result && $invoice_result->num_rows > 0) {
    while ($row = $invoice_result->fetch_assoc()) {
        $paid_amount = $payment_map[$row['invoice_id']] ?? 0;
        $balance = $row['amount'] - $paid_amount;
        $row['paid_amount'] = $paid_amount;
        $row['balance'] = $balance;

        // Determine payment status
        if ($balance <= 0) {
            $row['status'] = 'Paid';
        } elseif ($paid_amount > 0) {
            $row['status'] = 'Partial';
        } else {
            $row['status'] = 'Unpaid';
        }

        // Apply filter for pending payments
        if ($filter === 'p' && ($row['status'] === 'Unpaid' || $row['status'] === 'Partial')) {
            $filtered_invoices[] = $row;
        } elseif (empty($filter)) {
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
    <title>Payments List - City Smile Dental Clinic</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
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
            margin-left: 20%;
            /* Account for sidebar */
            margin-top: 80px;
            /* Account for header */
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
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

        .actions {
            display: flex;
            gap: 1rem;
            align-items: center;
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

        .btn-outline:hover {
            border-color: var(--primary-lilac);
            color: var(--dark-lilac);
        }

        .search-container {
            position: relative;
        }

        .search-input {
            padding: 0.65rem 1.25rem 0.65rem 2.75rem;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            width: 250px;
            transition: var(--transition);
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-lilac);
            width: 300px;
            box-shadow: 0 0 0 3px rgba(157, 125, 237, 0.2);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
        }

        .card {
            background-color: white;
            border-radius: var(--border-radius);
            box-shadow: var(--shadow-md);
            overflow: hidden;
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

        .status-badge {
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

        .amount {
            font-family: 'Inter', monospace;
            font-weight: 500;
        }

        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
            gap: 0.5rem;
        }

        .pagination-item {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 8px;
            border: 1px solid var(--gray-300);
            background: white;
            color: var(--gray-700);
            font-weight: 500;
            cursor: pointer;
            transition: var(--transition);
        }

        .pagination-item:hover,
        .pagination-item.active {
            background-color: var(--primary-lilac);
            color: white;
            border-color: var(--primary-lilac);
        }

        .summary-section {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
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

        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--gray-600);
            text-decoration: none;
            font-size: 0.9rem;
            margin-bottom: 1rem;
            transition: var(--transition);
        }

        .back-btn:hover {
            color: var(--dark-lilac);
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

            .actions {
                width: 100%;
                justify-content: space-between;
            }

            .search-input {
                width: 100%;
            }

            .table-responsive {
                margin: 0 -1rem;
                width: calc(100% + 2rem);
            }
        }
    </style>
</head>

<body>
    <?php include '../sidebar/sidebar.php'; ?>

    <div class="container">
        <a href="../dashboard/dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="page-header">
            <div class="page-title">
                <div class="page-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <h1><?php echo $filter === 'p' ? 'Pending Payments' : 'All Payments'; ?></h1>
            </div>

            <div class="actions">
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" class="search-input" placeholder="Search payments...">
                </div>

                <a href="paymentslist.php" class="btn btn-outline <?php echo empty($filter) ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> All
                </a>
                <a href="paymentslist.php?filter=p"
                    class="btn btn-outline <?php echo $filter === 'p' ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Pending
                </a>
                <a href="#" class="btn btn-primary">
                    <i class="fas fa-plus"></i> New Payment
                </a>
            </div>
        </div>

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

        <div class="card mt-4">
            <div class="card-header">
                <h2 class="card-title">Payment Records</h2>
                <div class="card-actions">
                    <button class="btn btn-outline">
                        <i class="fas fa-download"></i> Export
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table>
                        <thead>
                            <tr>
                                <th>Invoice #</th>
                                <th>Patient Name</th>
                                <th>Total Amount</th>
                                <th>Paid Amount</th>
                                <th>Balance</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($filtered_invoices) > 0): ?>
                                <?php foreach ($filtered_invoices as $row): ?>
                                    <tr>
                                        <td><strong>#<?php echo $row['invoice_id']; ?></strong></td>
                                        <td><?php echo $row['patient_name']; ?></td>
                                        <td class="amount">₱<?php echo number_format($row['amount'], 2); ?></td>
                                        <td class="amount">₱<?php echo number_format($row['paid_amount'], 2); ?></td>
                                        <td class="amount">₱<?php echo number_format($row['balance'], 2); ?></td>
                                        <td>
                                            <span class="status-badge <?php
                                            if ($row['status'] == 'Paid')
                                                echo 'status-paid';
                                            elseif ($row['status'] == 'Partial')
                                                echo 'status-partial';
                                            else
                                                echo 'status-unpaid';
                                            ?>">
                                                <?php echo $row['status']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo date("M j, Y", strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <a href="#" title="View Details"><i class="fas fa-eye text-primary"></i></a>
                                            &nbsp;&nbsp;
                                            <a href="#" title="Record Payment"><i
                                                    class="fas fa-plus-circle text-success"></i></a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <i class="fas fa-exclamation-circle fa-2x mb-3" style="color: var(--gray-400);"></i>
                                        <p>No payments found</p>
                                        <?php if ($filter === 'p'): ?>
                                            <small>All patients are fully paid. Great job!</small>
                                        <?php else: ?>
                                            <small>No payment records found in the system.</small>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if (count($filtered_invoices) > 10): ?>
            <div class="pagination">
                <div class="pagination-item"><i class="fas fa-chevron-left"></i></div>
                <div class="pagination-item active">1</div>
                <div class="pagination-item">2</div>
                <div class="pagination-item">3</div>
                <div class="pagination-item">...</div>
                <div class="pagination-item"><i class="fas fa-chevron-right"></i></div>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Search functionality
        document.querySelector('.search-input').addEventListener('keyup', function (e) {
            const searchTerm = e.target.value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchTerm) ? '' : 'none';
            });
        });
    </script>
</body>

</html>