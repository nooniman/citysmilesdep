<?php
session_start();

// Check admin access
if (
    !isset($_SESSION['user_id']) || !isset($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'staff', 'dentist', 'intern', 'assistant'])
) {
    header('Location: ../login.php');
    exit();
}

include '../database.php';
$statusFilter = $_GET['status_filter'] ?? 'all';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Appointment Management | City Smiles Dental</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <style>

        :root {
            --primary-green: #3cb371;
            --light-green: #9aeaa1;
            --dark-green: #297859;
            --primary-lilac: #9d7ded;
            --light-lilac: #e0d4f9;
            --dark-lilac: #7B32AB;
            --white: #ffffff;
            --off-white: #f8f9fa;
            --gray-100: #f8f9fa;
            --gray-200: #e9ecef;
            --gray-300: #dee2e6;
            --gray-400: #ced4da;
            --gray-500: #adb5bd;
            --gray-600: #6c757d;
            --gray-700: #495057;
            --gray-800: #343a40;
            --gray-900: #212529;
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --header-height: 70px;
            --box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            --border-radius: 12px;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }

        /* Main content */
        .app-container {
            margin-left: var(--sidebar-width);
            padding-top: calc(var(--header-height) + 20px);
            padding-left: 25px;
            padding-right: 25px;
            padding-bottom: 25px;
            transition: margin-left 0.3s ease;
        }

        body.cs-sidebar-collapsed .app-container {
            margin-left: var(--sidebar-collapsed-width);
        }

        /* Dashboard components */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .dashboard-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark-lilac);
            margin: 0;
        }

        .actions-container {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .btn-primary {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--primary-lilac), var(--dark-lilac));
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(123, 50, 171, 0.2);
        }

        .btn-primary i {
            font-size: 14px;
        }

        /* Stats bar/row styles */
        .stats-bar, .stats-row {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }
        .stat-trend {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.85rem;
            color: #28a745;
            margin-top: auto;
            white-space: nowrap;
        }

        .stat-trend i {
            color: #28a745;
        }

        .stat-trend.negative {
            color: var(--danger);
        }
        .stat-card {
            flex: 1;
            min-width: 180px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 25px;
            display: flex;
            flex-direction: column;
            position: relative;
            cursor: pointer;
            transition: all 0.3s ease;
            overflow: hidden;
            text-decoration: none;
            border-left: 4px solid var(--primary-lilac);
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-title {
            display: flex;
            align-items: center;
            justify-content: flex-start;
            margin-bottom: 15px;
            gap: 15px;
        }

        .stat-title span {
            font-size: 15px;
            font-weight: 600;
            color: var(--gray-700);
            order: -1;
        }

        .stat-icon {
            position: absolute;
            top: var(--spacing-6, 1.5rem);
            right: var(--spacing-6, 1.5rem);
            width: 48px;
            height: 48px;
            border-radius: var(--radius, 10px);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.9;
        }

        .stat-icon i {
            font-size: 1.5rem;
        }

        /* Update icon styles for each status */
        .stat-all .stat-icon {
            background-color: var(--light-lilac);
            color: var(--primary-lilac);
        }

        .stat-pending .stat-icon {
            background-color: #fff3cd;
            color: #856404;
        }

        .stat-confirmed .stat-icon {
            background-color: #e0f7fa;
            color: #0097a7;
        }

        .stat-completed .stat-icon {
            background-color: #d1fae5;
            color: #28a745;
        }

        .stat-cancelled .stat-icon {
            background-color: #fee2e2;
            color: #dc3545;
        }

        /* Update border colors for different statuses */
        .stat-all {
            border-left-color: var(--primary-lilac);
        }

        .stat-pending {
            border-left-color: #ffc107;
        }

        .stat-confirmed {
            border-left-color: #17a2b8;
        }

        .stat-completed {
            border-left-color: #28a745;
        }

        .stat-cancelled {
            border-left-color: #dc3545;
        }



        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .search-container {
            position: relative;
            flex-grow: 1;
            max-width: 400px;
        }

        .search-input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 1px solid var(--gray-300);
            border-radius: 10px;
            font-size: 14px;
            transition: all 0.2s ease;
            background: white;
        }

        .search-input:focus {
            outline: none;
            border-color: var(--primary-lilac);
            box-shadow: 0 0 0 4px rgba(157, 125, 237, 0.15);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--gray-500);
        }

        /* Table styles */
        .table-container {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            margin-bottom: 25px;
        }

        .appointments-table {
            width: 100%;
            border-collapse: collapse;
            table-layout: fixed;
        }

        .appointments-table th {
            background: var(--gray-100);
            color: var(--gray-700);
            font-weight: 600;
            text-align: center;
            /* Center-align headers */
            padding: 16px 20px;
            font-size: 14px;
            border-bottom: 1px solid var(--gray-300);
        }

        .appointments-table td {
            padding: 16px 20px;
            vertical-align: middle;
            border-bottom: 1px solid var(--gray-200);
            font-size: 14px;
        }

        .appointments-table td:last-child {
            text-align: center;
            /* Center-align actions column */
        }

        .appointments-table tr:last-child td {
            border-bottom: none;
        }

        .appointments-table tr:hover {
            background-color: #f1f1f1;
            /* Light gray for hover effect */
        }

        /* Patient info */
        .patient-info {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .patient-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            flex-shrink: 0;
            background: linear-gradient(135deg, var(--primary-lilac), var(--dark-lilac));
        }

        .patient-name {
            font-weight: 600;
            color: var(--gray-800);
            line-height: 1.4;
        }

        .patient-email {
            font-size: 13px;
            color: var(--gray-600);
        }

        /* Status badges */
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 30px;
            font-weight: 600;
            font-size: 12px;
            line-height: 1;
            white-space: nowrap;
        }

        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-confirmed {
            background-color: #e0f7fa;
            color: #0097a7;
        }

        .badge-completed {
            background-color: #e8f5e9;
            color: #388e3c;
        }

        .badge-cancelled {
            background-color: #ffebee;
            color: #d32f2f;
        }

        .badge-rescheduled {
            background-color: #e8eaf6;
            color: #3f51b5;
        }

        /* Date and time styles */
        .datetime-info {
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .date-display {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-800);
            font-weight: 500;
        }

        .time-display {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--gray-600);
            font-size: 13px;
        }

        .date-display i,
        .time-display i {
            color: var(--gray-500);
            font-size: 14px;
            width: 16px;
            text-align: center;
        }

        /* Action buttons */
        .actions {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            justify-content: center;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 600;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            white-space: nowrap;
            min-width: 100px;
        }

        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .btn-approve {
            background-color: var(--primary-green);
            color: white;
        }

        .btn-complete {
            background-color: var(--primary-lilac);
            color: white;
        }

        .btn-reschedule {
            background-color: #ffa726;
            color: white;
        }

        .btn-cancel {
            background-color: #ff5252;
            color: white;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            display: none;
        }

        .empty-state img {
            width: 120px;
            height: auto;
            margin-bottom: 20px;
            opacity: 0.7;
        }

        .empty-state h3 {
            font-size: 20px;
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--gray-600);
            max-width: 400px;
            margin: 0 auto;
        }

        @media (max-width: 992px) {
            .app-container {
                margin-left: 0;
                padding-right: 20px;
                padding-left: 20px;
            }

            .stats-bar, .stats-row {
                gap: 15px;
            }

            .stat-card {
                min-width: calc(50% - 15px);
                margin: 0;
            }

            .filter-bar {
                flex-direction: column;
                align-items: flex-start;
            }

            .search-container {
                max-width: 100%;
                width: 100%;
            }

            .table-container {
                overflow-x: auto;
            }

            .appointments-table {
                min-width: 800px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }
        }

        @media (max-width: 576px) {
            .stat-card {
                min-width: 100%;
            }
        }

        tr.status-completed {
            background-color: rgba(232, 245, 233, 0.2);
        }

        tr.status-cancelled {
            background-color: rgba(255, 235, 238, 0.2);
        }

        .toggle-switch input:checked + .toggle-slider {
    background-color: #2196F3;
}
.toggle-switch input:checked + .toggle-slider:before {
    transform: translateX(20px);
}
.toggle-slider:before {
    position: absolute;
    content: "";
    height: 16px;
    width: 16px;
    left: 2px;
    bottom: 2px;
    background-color: white;
    transition: .4s;
    border-radius: 50%;
}
.deleted-record {
    background-color: #ffebee !important;
    opacity: 0.7;
}
.deletion-info {
    color: #c62828;
    font-size: 0.8em;
    text-decoration: none !important;
    display: inline-block;
    display: block;
    margin-top: 4px;
    text-align: left;
}

.stat-value {
    font-size: 2rem;
            font-weight: 700;
            margin-bottom: 14px;
            color: var(--gray-900);
        }
.appointments-table th:first-child,
.appointments-table td:first-child {
    width: 30%;
    text-align: left;
}

.appointments-table th:nth-child(2),
.appointments-table td:nth-child(2) {
    width: 20%;
    text-align: left;
}

.appointments-table th:nth-child(3),
.appointments-table td:nth-child(3) {
    width: 15%;
    text-align: center;
}

.appointments-table th:last-child,
.appointments-table td:last-child {
    width: 35%;
    text-align: center;
    .cs-profile {
    position: relative;
    z-index: 1000;
}

.cs-profile .dropdown-toggle {
    padding: 0;
    background: none;
    border: none;
    cursor: pointer;
}

.cs-profile .dropdown-toggle::after {
    display: none;
}

.cs-profile-image {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    border: 2px solid var(--primary-lilac);
    transition: all 0.2s ease;
}

.cs-profile-image:hover {
    transform: scale(1.05);
}

.dropdown-menu {
    margin-top: 0.5rem;
    min-width: 200px;
    padding: 0.5rem 0;
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: var(--radius);
    box-shadow: var(--shadow-lg);
}

.dropdown-item {
    padding: 0.75rem 1.25rem;
    color: var(--gray-700);
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background-color: var(--gray-100);
    color: var(--primary-lilac);
}

.dropdown-divider {
    margin: 0.5rem 0;
    border-top: 1px solid #333333;
}

    </style>
</head>

<body>
    <?php include '../sidebar/sidebar.php'; ?>

    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto toast-title">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>

    <div class="app-container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Appointment Management</h1>
        </div>

        <!-- Stats Cards -->
        <div class="stats-bar">
            <?php
            // Get appointment counts for each status
            $statusCounts = [];
            $statuses = ['all', 'pending', 'confirmed', 'completed', 'cancelled'];

            $totalQuery = "SELECT COUNT(*) as count FROM appointments";
            $totalResult = $conn->query($totalQuery);
            $statusCounts['all'] = $totalResult->fetch_assoc()['count'];

            foreach (['pending', 'confirmed', 'completed', 'cancelled'] as $status) {
                $countQuery = "SELECT COUNT(*) as count FROM appointments WHERE status = '$status'";
                $countResult = $conn->query($countQuery);
                $statusCounts[$status] = $countResult->fetch_assoc()['count'];
            }

            // Handle rescheduled separately
            $rescheduleQuery = "SELECT COUNT(*) as count FROM appointments WHERE status = 'reschedule' OR status = 'rescheduled'";
            $rescheduleResult = $conn->query($rescheduleQuery);
            $statusCounts['rescheduled'] = $rescheduleResult->fetch_assoc()['count'];

            // Icons for each status
            $statusIcons = [
                'all' => 'fa-calendar-check',
                'pending' => 'fa-clock',
                'confirmed' => 'fa-check-circle',
                'completed' => 'fa-check-double',
                'cancelled' => 'fa-times-circle',
                'rescheduled' => 'fa-calendar-day'
            ];

            foreach ($statuses as $status):
                $isActive = $statusFilter === $status;
                $displayName = ucfirst($status);
                ?>
                <a href="?status_filter=<?= $status ?>"
                    class="stat-card stat-<?= $status ?> <?= $isActive ? 'active' : '' ?>">
                    <div class="stat-title">
                        <div class="stat-icon icon-<?= $status ?>">
                            <i class="fas <?= $statusIcons[$status] ?>"></i>
                        </div>
                        <span><?= $displayName ?></span>
                    </div>
                    <div class="stat-value"><?= $statusCounts[$status] ?></div>
                    <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i> 12% from last month
                </div>
                </a>
                
            <?php endforeach; ?>

            <a href="?status_filter=rescheduled"
                class="stat-card stat-confirmed <?= $statusFilter === 'rescheduled' ? 'active' : '' ?>">
                <div class="stat-title">
                    <div class="stat-icon icon-confirmed">
                        <i class="fas fa-calendar-day"></i>
                    </div>
                    <span>Rescheduled</span>
                </div>
                <div class="stat-value"><?= $statusCounts['rescheduled'] ?></div>
            </a>
        </div>

        <!-- Search Filter -->
        <div class="filter-bar">
            <div class="search-container">
                <i class="fas fa-search search-icon"></i>
                <input type="text" id="search-input" class="search-input" placeholder="Search by patient name...">
            </div>
        </div>

        <!-- Admin-only toggle for viewing deleted appointments -->
<?php if ($_SESSION['role'] == 'admin'): ?>
<div class="deleted-toggle" style="text-align: right; margin-bottom: 10px;">
    <label for="showDeletedToggle" style="display: inline-flex; align-items: center; cursor: pointer;">
        <span style="margin-right: 10px;">Show deleted appointments</span>
        <div class="toggle-switch" style="position: relative; display: inline-block; width: 40px; height: 20px;">
            <input type="checkbox" id="showDeletedToggle" style="opacity: 0; width: 0; height: 0;" 
                  <?php echo isset($_GET['show_deleted']) && $_GET['show_deleted'] == '1' ? 'checked' : ''; ?>>
            <span class="toggle-slider" style="position: absolute; cursor: pointer; top: 0; left: 0; right: 0; bottom: 0; background-color: #ccc; border-radius: 20px; transition: .4s;"></span>
        </div>
    </label>
</div>


<script>
    document.getElementById('showDeletedToggle').addEventListener('change', function() {
        const url = new URL(window.location.href);
        if (this.checked) {
            url.searchParams.set('show_deleted', '1');
        } else {
            url.searchParams.delete('show_deleted');
        }
        window.location.href = url.toString();
    });
</script>
<?php endif; ?>

        <!-- Appointments Table -->
        <div class="table-container">
            <table class="appointments-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Date & Time</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="appointments-table-body">
                    <?php
                    // Build the SQL query based on status filter
                    // Check if we should show deleted records (admin only)
                    $showDeleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] == '1' && $_SESSION['role'] == 'admin';

                    // Build the SQL query based on status filter
                    $query = "SELECT a.appointment_id, a.appointment_date, a.appointment_time, a.status, 
                            p.patient_info_id, p.first_name, p.last_name, p.email";
                            
                    if ($showDeleted) {
                        $query .= ", a.deleted_at";
                    }

                    $query .= " FROM appointments a
                            JOIN patients p ON a.patient_id = p.patient_info_id";

                    // Apply soft delete and status filters
                    if ($showDeleted) {
                        $query .= " WHERE a.is_deleted = 1";
                    } else {
                        $query .= " WHERE (a.is_deleted = 0 OR a.is_deleted IS NULL)";
                        
                        if ($statusFilter !== 'all') {
                            if ($statusFilter === 'rescheduled') {
                                $query .= " AND (a.status = 'reschedule' OR a.status = 'rescheduled')";
                            } else {
                                $query .= " AND a.status = ?";
                            }
                        }
                    }

                    // Order by appropriate column
                    if ($showDeleted) {
                        $query .= " ORDER BY a.deleted_at DESC";
                    } else {
                        $query .= " ORDER BY a.appointment_date DESC, a.appointment_time DESC";
                    }

                    if ($statusFilter !== 'all' && $statusFilter !== 'rescheduled') {
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param("s", $statusFilter);
                        $stmt->execute();
                        $result = $stmt->get_result();
                    } else {
                        $result = $conn->query($query);
                    }

                    if ($result->num_rows > 0) {
                        while ($row = $result->fetch_assoc()) {
                            $fullName = htmlspecialchars($row['first_name'] . ' ' . $row['last_name']);
                            $email = htmlspecialchars($row['email']);
                            $initials = strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1));
                            $date = date('M j, Y', strtotime($row['appointment_date']));
                            $time = date('g:i A', strtotime($row['appointment_time']));

                            // Handle empty status as pending
                            $status = !empty($row['status']) ? strtolower($row['status']) : 'pending';

                            // Normalize status value
                            if ($status === 'reschedule') {
                                $status = 'rescheduled';
                            }

                            $statusDisplay = ucfirst($status);

                            // Add a class to the row based on status
                            $rowClass = "status-{$status}";
                            ?>
                            <tr class="<?= $rowClass ?> <?= $showDeleted ? 'deleted-record' : '' ?>">
                                <td>
                                    <div class="patient-info">
                                        <div class="patient-avatar"><?= $initials ?></div>
                                        <div>
                                            <div class="patient-name"><?= $fullName ?></div>
                                            <div class="patient-email"><?= $email ?></div>
                                            <?php if ($showDeleted): ?>
                                                <div class="deletion-info">Deleted: <?= date('M d, Y', strtotime($row['deleted_at'])) ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="datetime-info">
                                        <div class="date-display">
                                            <i class="far fa-calendar-alt"></i>
                                            <?= $date ?>
                                        </div>
                                        <div class="time-display">
                                            <i class="far fa-clock"></i>
                                            <?= $time ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="status-badge badge-<?= $status ?>">
                                        <?php if ($status === 'pending'): ?>
                                            <i class="fas fa-clock"></i>
                                        <?php elseif ($status === 'confirmed'): ?>
                                            <i class="fas fa-check-circle"></i>
                                        <?php elseif ($status === 'completed'): ?>
                                            <i class="fas fa-check-double"></i>
                                        <?php elseif ($status === 'cancelled'): ?>
                                            <i class="fas fa-times-circle"></i>
                                        <?php elseif ($status === 'rescheduled'): ?>
                                            <i class="fas fa-calendar-day"></i>
                                        <?php endif; ?>
                                        <?= $statusDisplay ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="actions">
                                        <?php if ($status === 'pending'): ?>
                                            <!-- Pending: Show Approve and Cancel -->
                                            <button class="btn-action btn-approve" data-action="approve"
                                                data-id="<?= $row['appointment_id'] ?>">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn-action btn-cancel" data-action="cancel"
                                                data-id="<?= $row['appointment_id'] ?>">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        <?php elseif ($status === 'reschedule' || $status === 'rescheduled'): ?>
                                            <!-- Rescheduled: Show Reschedule, Cancel, and Complete -->
                                            <button class="btn-action btn-reschedule" data-action="reschedule"
                                                data-id="<?= $row['appointment_id'] ?>">
                                                <i class="fas fa-calendar-alt"></i> Reschedule
                                            </button>
                                            <button class="btn-action btn-cancel" data-action="cancel"
                                                data-id="<?= $row['appointment_id'] ?>">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                            <button class="btn-action btn-complete" data-action="completed"
                                                data-id="<?= $row['appointment_id'] ?>">
                                                <i class="fas fa-check-circle"></i> Complete
                                            </button>
                                        <?php elseif ($status === 'confirmed'): ?>
                                            <!-- Confirmed: Show Reschedule, Cancel, and Complete -->
                                            <button class="btn-action btn-reschedule" data-action="reschedule"
                                                data-id="<?= $row['appointment_id'] ?>">
                                                <i class="fas fa-calendar-alt"></i> Reschedule
                                            </button>
                                            <button class="btn-action btn-cancel" data-action="cancel"
                                                data-id="<?= $row['appointment_id'] ?>">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                            <button class="btn-action btn-complete" data-action="completed"
                                                data-id="<?= $row['appointment_id'] ?>">
                                                <i class="fas fa-check-circle"></i> Complete
                                            </button>
                                        <?php elseif ($status === '' || $status === null): ?>
                                            <!-- Handle empty status as Pending -->
                                            <button class="btn-action btn-approve" data-action="approve"
                                                data-id="<?= $row['appointment_id'] ?>">
                                                <i class="fas fa-check"></i> Approve
                                            </button>
                                            <button class="btn-action btn-cancel" data-action="cancel"
                                                data-id="<?= $row['appointment_id'] ?>">
                                                <i class="fas fa-times"></i> Cancel
                                            </button>
                                        <?php endif; ?>
                                        <?php if ($_SESSION['role'] == 'admin' && !$showDeleted && 
                                                ($status === 'completed' || $status === 'cancelled')): ?>
                                            <button class="btn-action btn-cancel" data-action="remove"
                                                    data-id="<?= $row['appointment_id'] ?>" style="background-color: #D84040;">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php
                        }
                    } else {
                        echo '<tr><td colspan="4" style="text-align:center;padding:30px;">No appointments found</td></tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Reschedule Modal -->
        <div class="modal fade" id="reschedule-modal" tabindex="-1" aria-labelledby="rescheduleModalLabel"
            aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="rescheduleModalLabel">Reschedule Appointment</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form id="reschedule-form">
                            <input type="hidden" id="appointment-id" name="appointment_id">
                            <div class="mb-3">
                                <label for="new-date" class="form-label">New Date</label>
                                <input type="date" id="new-date" name="new_date" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="new-time" class="form-label">New Time</label>
                                <input type="time" id="new-time" name="new_time" class="form-control" required>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-primary" id="confirm-reschedule">Confirm</button>
                    </div>
                </div>
            </div>
        </div>


        <!-- Custom Delete Confirmation Modal -->
        <div class="modal fade" id="delete-confirm-modal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Confirm Removal
                    </h5>
                </div>
                    <div class="modal-body">
                        <p>Are you sure you want to remove this appointment?</p>
                        <p class="mb-0 font-weight-bold">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="button" class="btn btn-danger" id="confirm-delete">
                            <i class="fas fa-trash me-1"></i>
                            Remove Appointment
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <div class="cs-profile">
    <div class="dropdown">
        <button class="dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
            <img src="../icons/profile.png" alt="Profile" class="cs-profile-image">
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item" href="../account/account.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../login/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sign Out</a></li>
        </ul>
    </div>
</div>
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
        <script>
            $(document).ready(function () {
                const urlParams = new URLSearchParams(window.location.search);
                const successMessage = urlParams.get('success');
                const errorMessage = urlParams.get('error');

                // Check if there's a success or error message in the URL
                if (successMessage) {
                    localStorage.setItem('toastMessage', JSON.stringify({ title: 'Success', message: successMessage, type: 'success' }));
                    clearQueryParams(); // Clear query parameters after storing the message
                }

                if (errorMessage) {
                    localStorage.setItem('toastMessage', JSON.stringify({ title: 'Error', message: errorMessage, type: 'error' }));
                    clearQueryParams(); // Clear query parameters after storing the message
                }

                // Display the toast message from localStorage
                const storedToast = localStorage.getItem('toastMessage');
                if (storedToast) {
                    const { title, message, type } = JSON.parse(storedToast);
                    showToast(title, message, type);
                    localStorage.removeItem('toastMessage'); // Remove the message after displaying it
                }

                function showToast(title, message, type) {
                    const toast = document.createElement('div');
                    toast.className = `toast align-items-center text-bg-${type === 'success' ? 'success' : 'danger'} border-0`;
                    toast.setAttribute('role', 'alert');
                    toast.setAttribute('aria-live', 'assertive');
                    toast.setAttribute('aria-atomic', 'true');

                    toast.innerHTML = `
                        <div class="d-flex">
                            <div class="toast-body">
                                <strong>${title}</strong>: ${message}
                            </div>
                            <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                        </div>
                    `;

                    document.querySelector('.toast-container').appendChild(toast);
                    const bootstrapToast = new bootstrap.Toast(toast);
                    bootstrapToast.show();
                }

                function clearQueryParams() {
                    const url = new URL(window.location.href);
                    url.searchParams.delete('success'); // Remove only the 'success' parameter
                    url.searchParams.delete('error');   // Remove only the 'error' parameter
                    window.history.replaceState({}, document.title, url.toString());
                }

                // Search functionality
                $("#search-input").on("input", function () {
                    let searchTerm = $(this).val().toLowerCase();
                    let found = false;

                    $("#appointments-table-body tr").each(function () {
                        let patientName = $(this).find(".patient-name").text().toLowerCase();
                        let patientEmail = $(this).find(".patient-email").text().toLowerCase();

                        if (patientName.includes(searchTerm) || patientEmail.includes(searchTerm)) {
                            $(this).show();
                            found = true;
                        } else {
                            $(this).hide();
                        }
                    });

                    // Toggle empty state
                    if (found) {
                        $("#empty-state").hide();
                        $(".table-container").show();
                    } else {
                        $("#empty-state").show();
                        $(".table-container").hide();
                    }
                });

                // Modify the general action buttons handler to exclude "remove" action
                $(".btn-action").on("click", function () {
                    const action = $(this).data("action");
                    const appointmentId = $(this).data("id");

                    // Skip processing for remove action since it's handled by the specific handler
                    if (action === "remove") {
                        return; // Do nothing here - let the specific handler take care of it
                    }

                    if (action === "reschedule") {
                        // Set appointment ID in the modal form
                        $("#appointment-id").val(appointmentId);

                        // Set minimum date to today
                        const today = new Date().toISOString().split('T')[0];
                        $("#new-date").attr("min", today);

                        // Show modal
                        const rescheduleModal = new bootstrap.Modal(document.getElementById('reschedule-modal'));
                        rescheduleModal.show();
                    } else {
                        // Confirm other actions
                        if (confirm(`Are you sure you want to ${action} this appointment?`)) {
                            updateAppointmentStatus(appointmentId, action);
                        }
                    }
                });

                // Confirm reschedule
                $("#confirm-reschedule").on("click", function () {
                    const appointmentId = $("#appointment-id").val();
                    const newDate = $("#new-date").val();
                    const newTime = $("#new-time").val();

                    if (!newDate || !newTime) {
                        showToast("Error", "Please select both date and time", "error");
                        return;
                    }

                    updateAppointmentStatus(appointmentId, "reschedule", {
                        new_date: newDate,
                        new_time: newTime
                    });

                    const rescheduleModal = bootstrap.Modal.getInstance(document.getElementById('reschedule-modal'));
                    rescheduleModal.hide();
                });

                // Update appointment status
                function updateAppointmentStatus(appointmentId, action, additionalData = {}) {
                    const data = {
                        appointment_id: appointmentId,
                        action: action,
                        ...additionalData
                    };

                    $.ajax({
                        url: "appointment_actions.php",
                        type: "POST",
                        data: data,
                        dataType: "json",
                        success: function (response) {
                            if (response.success) {
                                showToast("Success", response.message || "Appointment updated successfully", "success");
                                setTimeout(() => {
                                    window.location.reload();
                                }, 2000);
                            } else {
                                showToast("Error", response.message || "An error occurred", "error");
                            }
                        },
                        error: function (xhr) {
                            console.error(xhr.responseText);
                            showToast("Error", "Something went wrong. Please try again.", "error");
                        }
                    });
                }

                // Replace the existing remove button click handler
                $(".btn-action[data-action='remove']").on("click", function () {
                    // Store the appointment ID in a variable that's accessible to the modal
                    const appointmentId = $(this).data("id");
                    
                    // Store the current button for UI updates
                    const $button = $(this);
                    
                    // Show the confirmation modal instead of browser confirm
                    const deleteModal = new bootstrap.Modal(document.getElementById('delete-confirm-modal'));
                    deleteModal.show();
                    
                    // Set up the confirmation button's one-time click handler
                    $("#confirm-delete").one("click", function() {
                        deleteModal.hide();
                        
                        // Show loading state on the button
                        $button.html('<i class="fas fa-spinner fa-spin"></i> Removing...').prop('disabled', true);
                        
                        // Perform the AJAX request
                        $.ajax({
                            url: "soft_delete_appointment.php",
                            type: "POST",
                            data: {id: appointmentId},
                            dataType: "json",
                            success: function (response) {
                                if (response.success) {
                                    showToast("Success", "Appointment removed successfully", "success");
                                    
                                    // Fade out the row with animation
                                    $button.closest("tr").fadeOut(400);
                                    
                                    setTimeout(() => {
                                        window.location.reload();
                                    }, 2000);
                                } else {
                                    showToast("Error", response.message || "An error occurred", "error");
                                    // Reset button state
                                    $button.html('<i class="fas fa-trash"></i> Remove').prop('disabled', false);
                                }
                            },
                            error: function (xhr) {
                                console.error(xhr.responseText);
                                showToast("Error", "Something went wrong. Please try again.", "error");
                                // Reset button state
                                $button.html('<i class="fas fa-trash"></i> Remove').prop('disabled', false);
                            }
                        });
                    });
                });
            });

            document.addEventListener('DOMContentLoaded', function() {
    const dropdownElementList = document.querySelectorAll('.dropdown-toggle');
    const dropdownList = [...dropdownElementList].map(dropdownToggleEl => new bootstrap.Dropdown(dropdownToggleEl));
});

            
        </script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>

</html>
</body>

</html>