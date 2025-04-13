<?php
require_once __DIR__ . '/../admin_check.php';
if (!isset($_SESSION['user_id'])) {
    header('Location: ..old/login/login.php');
    exit();
}

include '../database.php';

// Fetch dashboard stats
$query = "SELECT COUNT(*) AS total_patients FROM patients";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$totalPatients = $row['total_patients'];

// Fetch pending requests
$query = "SELECT COUNT(*) AS pendingRequests FROM appointments WHERE status = 'pending'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$pendingRequests = $row['pendingRequests'];

// Fetch new patients in last 30 days
$query = "SELECT COUNT(*) AS newPatients 
          FROM patients 
          WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$newPatients = $row['newPatients'];

// Fetch cancelled appointments
$query = "SELECT COUNT(*) AS cancelledAppointments FROM appointments WHERE status = 'cancelled'";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$cancelledAppointments = $row['cancelledAppointments'];

// Fetch pending payments (Unpaid and Partial)
$query = "
SELECT COUNT(*) AS pendingPayments
FROM invoices i
LEFT JOIN (
    SELECT invoice_id, SUM(amount) AS total_paid FROM payments GROUP BY invoice_id
) p ON i.invoice_id = p.invoice_id
WHERE i.amount > IFNULL(p.total_paid, 0) -- Balance > 0
";
$result = mysqli_query($conn, $query);
$row = mysqli_fetch_assoc($result);
$pendingPayments = $row['pendingPayments'];

// Fetch appointments for FullCalendar with error handling
$calendarEvents = [];
$queryAppointments = "
    SELECT a.appointment_id, a.appointment_date, a.appointment_time, 
           a.status, p.first_name, p.last_name, s.name as service_name
    FROM appointments AS a
    JOIN patients AS p ON a.patient_id = p.patient_info_id
    LEFT JOIN services AS s ON a.service_id = s.services_id
    WHERE a.appointment_date IS NOT NULL
    ORDER BY a.appointment_date ASC
";

$resultEvents = mysqli_query($conn, $queryAppointments);

if (!$resultEvents) {
    $error = "Error fetching appointments: " . mysqli_error($conn);
} else if (mysqli_num_rows($resultEvents) == 0) {
    $error = "No appointments found in database.";
} else {
    while ($rowEvent = mysqli_fetch_assoc($resultEvents)) {
        // Skip records with null dates or times
        if (empty($rowEvent['appointment_date']) || empty($rowEvent['appointment_time'])) {
            continue;
        }

        $title = $rowEvent['first_name'] . ' ' . $rowEvent['last_name'];
        $service = $rowEvent['service_name'] ? ' - ' . $rowEvent['service_name'] : '';
        $displayTitle = $title . $service;

        // Combine date/time for FullCalendar's "start"
        $start = $rowEvent['appointment_date'] . 'T' . $rowEvent['appointment_time'];

        // Updated color scheme for appointment status
        $color = '#9aeaa1'; // Default: green-medium
        switch (strtolower($rowEvent['status'])) {
            case 'pending':
                $color = 'rgba(157, 125, 237, 0.7)'; // lilac with transparency
                break;
            case 'confirmed':
                $color = 'rgba(60, 179, 113, 0.7)'; // green with transparency
                break;
            case 'cancelled':
                $color = 'rgba(220, 53, 69, 0.7)'; // red with transparency
                break;
            case 'completed':
                $color = 'rgba(23, 162, 184, 0.7)'; // teal with transparency
                break;
            case 'rescheduled':
                $color = 'rgba(255, 193, 7, 0.7)'; // amber with transparency
                break;
        }

        $calendarEvents[] = [
            'id' => $rowEvent['appointment_id'],
            'title' => $displayTitle,
            'start' => $start,
            'color' => $color,
            'extendedProps' => [
                'patient' => $rowEvent['first_name'] . ' ' . $rowEvent['last_name'],
                'service' => $rowEvent['service_name'] ?: 'Not specified',
                'time' => date("g:i A", strtotime($rowEvent['appointment_time'])),
                'date' => date("F j, Y", strtotime($rowEvent['appointment_date'])),
                'status' => ucfirst($rowEvent['status']),
            ],
        ];
    }
}

// Fetch the service types for dropdown
$servicesQuery = "SELECT services_id, name FROM services ORDER BY name";
$servicesResult = mysqli_query($conn, $servicesQuery);
$services = [];

while ($row = mysqli_fetch_assoc($servicesResult)) {
    $services[] = $row;
}

// Fetch data for the chart (all treatments by default)
$dataQuery = "
    SELECT 
        MONTH(a.appointment_date) AS month,
        s.name AS service_name,
        COUNT(*) AS appointment_count
    FROM 
        appointments a
    JOIN 
        services s ON a.service_id = s.services_id
    WHERE 
        YEAR(a.appointment_date) = YEAR(CURDATE())
    GROUP BY 
        MONTH(a.appointment_date), s.name
    ORDER BY 
        MONTH(a.appointment_date)
";
$dataResult = mysqli_query($conn, $dataQuery);

// Prepare data for Chart.js
$months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
$chartData = [];
$chartLabels = $months;

// Create an array to hold data for each service
foreach ($services as $service) {
    $chartData[$service['name']] = array_fill(0, 12, 0);
}

// Fill in the actual data
while ($row = mysqli_fetch_assoc($dataResult)) {
    $monthIndex = $row['month'] - 1; // SQL months are 1-12, array is 0-11
    $chartData[$row['service_name']][$monthIndex] = intval($row['appointment_count']);
}

// Generate datasets for Chart.js
$datasets = [];
$colors = ['#9d7ded', '#3cb371', '#17a2b8', '#ffc107', '#dc3545', '#6c757d', '#fd7e14', '#20c997'];
$i = 0;

foreach ($chartData as $serviceName => $data) {
    $datasets[] = [
        'label' => $serviceName,
        'data' => $data,
        'backgroundColor' => $colors[$i % count($colors)] . '80', // Add 50% transparency
        'borderColor' => $colors[$i % count($colors)],
        'borderWidth' => 2
    ];
    $i++;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | City Smiles Dental</title>
    <!-- Add Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.4/index.global.min.css" rel="stylesheet">
    <style>
      
        :root {
            /* Primary Colors */
            --primary-green: #3cb371;
            --light-green: #9aeaa1;
            --dark-green: #297859;
            --primary-lilac: #9d7ded;
            --light-lilac: #e0d4f9;
            --dark-lilac: #7B32AB;

            /* Neutral Colors */
            --white: #ffffff;
            --off-white: #f8f9fa;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;

            /* Status Colors */
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;

            /* Shadow Variables */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);

            /* Border Radius */
            --radius-sm: 0.25rem;
            --radius: 0.5rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --radius-full: 9999px;

            /* Spacing */
            --spacing-1: 0.25rem;
            --spacing-2: 0.5rem;
            --spacing-3: 0.75rem;
            --spacing-4: 1rem;
            --spacing-5: 1.25rem;
            --spacing-6: 1.5rem;
            --spacing-8: 2rem;
            --spacing-10: 2.5rem;
            --spacing-12: 3rem;

            /* Transitions */
            --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-bezier: cubic-bezier(0.4, 0, 0.2, 1);

            /* Layout constants */
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --header-height: 70px;

            /* Z-index layers */
            --z-dropdown: 1000;
            --z-sticky: 1020;
            --z-fixed: 1030;
            --z-modal-backdrop: 1040;
            --z-modal: 1050;
            --z-popover: 1060;
            --z-tooltip: 1070;
        }

        /* Base Styles */
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--gray-50);
            margin: 0;
            padding: 0;
            color: var(--gray-800);
            line-height: 1.5;
            overflow-x: hidden;
        }

        * {
            box-sizing: border-box;
        }

        /* Dashboard Layout */
        .dashboard-container {
            margin-left: var(--sidebar-width);
            padding: calc(var(--header-height) + var(--spacing-6)) var(--spacing-8) var(--spacing-8);
            transition: margin-left 0.3s var(--transition-bezier);
            min-height: 100vh;
        }

        body.cs-sidebar-collapsed .dashboard-container {
            margin-left: var(--sidebar-collapsed-width);
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: var(--spacing-6);
        }

        .dashboard-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark-lilac);
            margin: 0;
        }

        .date-display {
            color: var(--gray-600);
            font-size: 0.95rem;
            font-weight: 500;
        }

        /* Action Button */
        .action-button {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-2);
            padding: var(--spacing-3) var(--spacing-5);
            background: linear-gradient(135deg, var(--primary-lilac), var(--dark-lilac));
            color: white;
            border: none;
            border-radius: var(--radius);
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            text-decoration: none;
            box-shadow: var(--shadow-md);
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(240px, 1fr));
            gap: var(--spacing-6);
            margin-bottom: var(--spacing-8);
        }

        .stat-card {
            background: white;
            border-radius: var(--radius-md);
            padding: var(--spacing-6);
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            border-left: 4px solid transparent;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-lg);
        }

        .stat-card.payments {
            border-left-color: var(--primary-lilac);
        }

        .stat-card.requests {
            border-left-color: var(--warning);
        }

        .stat-card.patients {
            border-left-color: var(--success);
        }

        .stat-card.cancelled {
            border-left-color: var(--danger);
        }

        .stat-icon {
            position: absolute;
            top: var(--spacing-6);
            right: var(--spacing-6);
            width: 48px;
            height: 48px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.9;
        }

        .stat-card.payments .stat-icon {
            background-color: var(--light-lilac);
            color: var(--primary-lilac);
        }

        .stat-card.requests .stat-icon {
            background-color: #fff3cd;
            color: var(--warning);
        }

        .stat-card.patients .stat-icon {
            background-color: #d1fae5;
            color: var(--success);
        }

        .stat-card.cancelled .stat-icon {
            background-color: #fee2e2;
            color: var(--danger);
        }

        .stat-icon i {
            font-size: 1.5rem;
        }

        .stat-label {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--gray-600);
            margin-top: 0;
            margin-bottom: var(--spacing-3);
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: var(--spacing-3);
            color: var(--gray-900);
        }

        .stat-trend {
            display: flex;
            align-items: center;
            gap: var(--spacing-2);
            font-size: 0.85rem;
            color: var(--gray-600);
            margin-top: auto;
        }

        .stat-trend.positive {
            color: var(--success);
        }

        .stat-trend.negative {
            color: var(--danger);
        }

        /* Content Grid - Updated to vertical layout */
        .content-grid {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-6);
            margin-bottom: var(--spacing-8);
        }

        /* Analytics Section */
        .dashboard-card {
            background: white;
            border-radius: var(--radius-md);
            box-shadow: var(--shadow);
            overflow: hidden;
            height: 100%;
        }

        .dashboard-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: var(--spacing-6);
            border-bottom: 1px solid var(--gray-200);
        }

        .card-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--gray-900);
            margin: 0;
        }

        .card-actions {
            display: flex;
            gap: var(--spacing-3);
        }

        .filter-select {
            padding: var(--spacing-2) var(--spacing-3);
            border-radius: var(--radius);
            border: 1px solid var(--gray-300);
            font-size: 0.875rem;
            color: var(--gray-700);
            background-color: white;
            cursor: pointer;
            transition: var(--transition);
        }

        .filter-select:hover {
            border-color: var(--gray-400);
        }

        .filter-select:focus {
            outline: none;
            border-color: var(--primary-lilac);
            box-shadow: 0 0 0 2px var(--light-lilac);
        }

        .dashboard-card-body {
            padding: var(--spacing-6);
        }

        .analytics-container {
            height: 350px;
            width: 100%;
            position: relative;
        }

        /* Calendar Section */
        .calendar-container {
            height: 500px;
            /* Increased height for better visibility */
            display: flex;
            flex-direction: column;
        }

        .calendar-body {
            flex-grow: 1;
            padding: var(--spacing-4);
        }

        .fc {
            height: 100%;
            font-family: 'Inter', sans-serif;
        }

        .fc .fc-toolbar-title {
            font-size: 1.2rem !important;
            font-weight: 600;
        }

        .fc .fc-button-primary {
            background-color: var(--primary-lilac) !important;
            border-color: var(--primary-lilac) !important;
            font-weight: 500 !important;
            text-transform: capitalize;
            font-size: 0.875rem !important;
            padding: 0.4rem 0.75rem !important;
        }

        .fc .fc-button-primary:hover {
            background-color: var(--dark-lilac) !important;
            border-color: var(--dark-lilac) !important;
        }

        .fc .fc-daygrid-day {
            padding: 4px !important;
        }

        .fc .fc-col-header-cell-cushion {
            padding: 8px 4px !important;
            font-weight: 600 !important;
            text-decoration: none;
        }

        .fc .fc-daygrid-day-number {
            font-size: 0.9rem !important;
            text-decoration: none;
            padding: 4px 8px !important;
        }

        .fc-day-today {
            background-color: var(--light-lilac) !important;
        }

        .fc-event {
            border-radius: var(--radius-sm) !important;
            font-size: 0.8rem !important;
            padding: 2px 4px !important;
            cursor: pointer;
            border: none !important;
        }

        .add-event-button {
            position: absolute;
            bottom: var(--spacing-6);
            right: var(--spacing-6);
            width: 48px;
            height: 48px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-lilac), var(--dark-lilac));
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            box-shadow: var(--shadow-lg);
            cursor: pointer;
            transition: var(--transition);
            z-index: 5;
        }

        .add-event-button:hover {
            transform: scale(1.1);
        }

        /* Modal Design */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: var(--z-modal-backdrop);
            backdrop-filter: blur(3px);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            padding-top: 40px;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        @keyframes modalFadeIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(10px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .modal-header {
            padding: var(--spacing-6);
            background: linear-gradient(135deg, var(--primary-lilac), var(--dark-lilac));
            color: white;
            position: relative;
        }

        .modal-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0;
        }

        .modal-subtitle {
            margin-top: var(--spacing-1);
            font-size: 0.95rem;
            opacity: 0.9;
        }

        .modal-close {
            position: absolute;
            top: var(--spacing-4);
            right: var(--spacing-4);
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            cursor: pointer;
            transition: var(--transition);
        }

        .modal-close:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: rotate(90deg);
        }

        .modal-body {
            padding: var(--spacing-6);
        }

        .modal-footer {
            padding: var(--spacing-4) var(--spacing-6);
            border-top: 1px solid var(--gray-200);
            display: flex;
            justify-content: flex-end;
            gap: var(--spacing-3);
        }

        /* Form Elements */
        .form-group {
            margin-bottom: var(--spacing-5);
        }

        .form-label {
            display: block;
            margin-bottom: var(--spacing-2);
            font-weight: 500;
            color: var(--gray-700);
            font-size: 0.95rem;
        }

        .form-control {
            display: block;
            width: 100%;
            padding: var(--spacing-3) var(--spacing-4);
            font-size: 0.95rem;
            line-height: 1.5;
            color: var(--gray-700);
            background-color: white;
            background-clip: padding-box;
            border: 1px solid var(--gray-300);
            border-radius: var(--radius);
            transition: var(--transition);
        }

        .form-control:focus {
            border-color: var(--primary-lilac);
            outline: 0;
            box-shadow: 0 0 0 2px var(--light-lilac);
        }

        textarea.form-control {
            height: auto;
            min-height: 100px;
            resize: vertical;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: var(--spacing-3) var(--spacing-6);
            font-size: 0.95rem;
            font-weight: 500;
            line-height: 1.5;
            text-align: center;
            white-space: nowrap;
            vertical-align: middle;
            cursor: pointer;
            user-select: none;
            border: 1px solid transparent;
            border-radius: var(--radius);
            transition: var(--transition);
        }

        .btn-primary {
            color: white;
            background: linear-gradient(135deg, var(--primary-lilac), var(--dark-lilac));
            border: none;
        }

        .btn-primary:hover {
            box-shadow: 0 0 0 2px var(--light-lilac);
        }

        .btn-secondary {
            color: var(--gray-700);
            background-color: var(--gray-200);
            border-color: var(--gray-300);
        }

        .btn-secondary:hover {
            background-color: var(--gray-300);
        }

        /* Event Details Modal */
        .event-details {
            padding: var(--spacing-3) 0;
        }

        .event-detail {
            display: flex;
            align-items: center;
            gap: var(--spacing-3);
            padding: var(--spacing-3) 0;
            border-bottom: 1px solid var(--gray-200);
        }

        .event-detail:last-child {
            border-bottom: none;
        }

        .detail-icon {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 36px;
            height: 36px;
            border-radius: var(--radius-sm);
            background-color: var(--light-lilac);
            color: var(--primary-lilac);
            flex-shrink: 0;
        }

        .detail-content {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }

        .detail-label {
            font-size: 0.8rem;
            color: var(--gray-500);
            margin-bottom: var(--spacing-1);
        }

        .detail-value {
            font-weight: 500;
            color: var(--gray-800);
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: var(--spacing-2);
            padding: var(--spacing-2) var(--spacing-3);
            font-size: 0.8rem;
            font-weight: 500;
            border-radius: var(--radius-full);
            line-height: 1;
        }

        .badge-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .badge-confirmed {
            background-color: #d1fae5;
            color: #064e3b;
        }

        .badge-cancelled {
            background-color: #fee2e2;
            color: #991b1b;
        }

        .badge-completed {
            background-color: #e0f2fe;
            color: #0369a1;
        }

        .badge-rescheduled {
            background-color: #f3e8ff;
            color: #6b21a8;
        }

        /* Responsive Adjustments */
        @media (max-width: 1024px) {
            .dashboard-container {
                margin-left: 0;
                padding: calc(var(--header-height) + var(--spacing-6)) var(--spacing-4) var(--spacing-4);
            }

            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-4);
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .dashboard-card-header {
                flex-direction: column;
                align-items: flex-start;
                gap: var(--spacing-3);
            }

            .card-actions {
                width: 100%;
            }

            .filter-select {
                width: 100%;
                flex: 1;
            }
        }

        /* Quick Add Patient Button */
        .quick-add-container {
            margin-bottom: var(--spacing-6);
        }

        .quick-add-button {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: var(--spacing-3);
            padding: var(--spacing-4) var(--spacing-6);
            width: 100%;
            background: linear-gradient(135deg, var(--primary-green), var(--dark-green));
            color: white;
            border: none;
            border-radius: var(--radius-md);
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            box-shadow: var(--shadow-md);
            text-decoration: none;
        }

        .quick-add-button:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
            filter: brightness(1.05);
        }

        .quick-add-button i {
            font-size: 1.3rem;
        }

        /* Empty State */
        .empty-state {
            padding: var(--spacing-8);
            text-align: center;
            color: var(--gray-600);
        }

        .empty-state i {
            font-size: 2.5rem;
            margin-bottom: var(--spacing-4);
            opacity: 0.6;
        }

        /* Fix for add patient modal */
        #addPatientModal {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 10000;
            display: none;
            justify-content: center;
            align-items: center;
            background-color: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(3px);
        }

        /* Override any conflicting styles */
        .patient-modal-content {
            position: relative;
            background-color: #fff;
            width: 90%;
            max-width: 700px;
            margin: 0 auto;
            padding: 20px;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-lg);
            max-height: 90vh;
            overflow-y: auto;
            animation: modalFadeIn 0.3s var(--transition-bezier);
        }

        /* Ensure modal content doesn't exceed viewport */
        .patient-modal-content::-webkit-scrollbar {
            width: 8px;
        }

        .patient-modal-content::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .patient-modal-content::-webkit-scrollbar-thumb {
            background: var(--gray-400);
            border-radius: 10px;
        }

        .patient-modal-content::-webkit-scrollbar-thumb:hover {
            background: var(--gray-500);
        }

        /* Add these styles for the patient add modal */

        /* Modal styling to match the dashboard design */
        #addPatientModal .modal-content,
        #dentalHistoryModal .modal-content,
        #medicalHistoryModal .modal-content {
            background-color: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);
            max-width: 700px;
            width: 100%;
            max-height: 90vh;
            overflow-y: auto;
        }

        #addPatientModal h2,
        #dentalHistoryModal h3,
        #medicalHistoryModal h3 {
            color: #7B32AB;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--lilac-medium);
        }

        .image-upload-container {
            display: flex;
            justify-content: center;
            margin-bottom: 20px;
        }

        .profile-placeholder {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background-color: var(--gray-200);
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .profile-placeholder input[type="file"] {
            position: absolute;
            opacity: 0;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        .profile-placeholder span {
            color: var(--gray-500);
            font-size: 14px;
            text-align: center;
        }

        .patient-form {
            width: 100%;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            width: 100%;
        }

        .patient-form input[type="text"],
        .patient-form input[type="email"],
        .patient-form input[type="number"],
        .patient-form input[type="date"],
        .patient-form select {
            padding: 10px 15px;
            border: 1px solid var(--gray-300);
            border-radius: 5px;
            font-size: 14px;
            width: 100%;
        }

        .modal-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 25px;
        }

        .btn {
            padding: 10px 20px;
            border-radius: 5px;
            border: none;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn.cancel {
            background-color: var(--gray-300);
            color: var(--gray-700);
        }

        .btn.next {
            background-color: #7B32AB;
            color: white;
        }

        .btn.cancel:hover {
            background-color: var(--gray-400);
        }

        .btn.next:hover {
            background-color: #632789;
        }

        /* Styling for medical history table */
        .medical-history-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .medical-history-table th,
        .medical-history-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid var(--gray-300);
        }

        .medical-history-table th {
            background-color: var(--gray-100);
            font-weight: 600;
        }

        /* Checkboxes styling */
        .checkbox-group {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
            margin-top: 15px;
        }

        .custom-checkbox {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
        }

        .custom-checkbox input[type="checkbox"] {
            accent-color: #7B32AB;
        }

        /* File upload styling */
        .file-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 15px;
        }

        .file-item {
            flex: 1;
            min-width: 200px;
        }

        .custom-file-upload {
            display: inline-block;
            padding: 8px 15px;
            background-color: var(--gray-200);
            border-radius: 5px;
            cursor: pointer;
            margin-top: 5px;
        }

        .custom-file-upload:hover {
            background-color: var(--gray-300);
        }

        /* Make sure modals don't show until triggered */
        #addPatientModal,
        #dentalHistoryModal,
        #medicalHistoryModal {
            display: none;
        }

        /* Other option areas in forms */
        .other-option {
            margin-top: 8px;
            width: 100%;
        }

        .other-option textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid var(--gray-300);
            border-radius: 5px;
            resize: vertical;
        }
        .cs-profile {
    position: relative;
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
    min-width: 200px;
    padding: 0.5rem 0;
    margin: 0;
    background-color: var(--white);
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
    gap: 0.75rem;
    transition: all 0.2s ease;
}

.dropdown-item:hover {
    background-color: var(--gray-100);
    color: var(--primary-lilac);
}

    </style>
</head>

<body class="<?php echo isset($_SESSION['sidebar_collapsed']) && $_SESSION['sidebar_collapsed'] ? 'cs-sidebar-collapsed' : ''; ?>">
    <?php include '../sidebar/sidebar.php'; ?>

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="dashboard-title" >Dashboard</h1>
            <div class="date-display">
                <i class="far fa-calendar-alt"></i> <?= date("F j, Y") ?>
            </div>
        </div>

        <div class="stats-grid">
            <a href="../payments/payments.php?filter=p" class="stat-card payments">
                <div class="stat-icon">
                    <i class="fas fa-file-invoice-dollar"></i>
                </div>
                <p class="stat-label">Pending Payments</p>
                <div class="stat-value"><?= $pendingPayments ?></div>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i> 12% from last month
                </div>
            </a>

            <a href="../appointmentlist/appointments.php" class="stat-card requests">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <p class="stat-label">Pending Requests</p>
                <div class="stat-value"><?= $pendingRequests ?></div>
                <div class="stat-trend">
                    <i class="fas fa-arrow-up"></i> 8% from last month
                </div>
            </a>

            <a href="../patients/patient.php" class="stat-card patients">
                <div class="stat-icon">
                    <i class="fas fa-user-plus"></i>
                </div>
                <p class="stat-label">New Patients</p>
                <div class="stat-value"><?= $newPatients ?></div>
                <div class="stat-trend positive">
                    <i class="fas fa-arrow-up"></i> 5% from last month
                </div>
            </a>

            <a href="../appointmentlist/appointments.php?status_filter=cancelled" class="stat-card cancelled">
                <div class="stat-icon">
                    <i class="fas fa-calendar-times"></i>
                </div>
                <p class="stat-label">Cancelled Appointments</p>
                <div class="stat-value"><?= $cancelledAppointments ?></div>
                <div class="stat-trend negative">
                    <i class="fas fa-arrow-down"></i> 3% from last month
                </div>
            </a>
        </div>

        <div class="content-grid">
            <!-- Calendar Section - Now Full Width -->
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h2 class="card-title">Upcoming Appointments</h2>
                </div>
                <div class="calendar-container">
                    <div class="calendar-body">
                        <div id="calendar"></div>
                    </div>
                    <button id="addEventBtn" class="add-event-button">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>

            <!-- Analytics Section - Also Full Width, Stacked Below Calendar -->
            <div class="dashboard-card">
                <div class="dashboard-card-header">
                    <h2 class="card-title">Treatment Analytics</h2>
                    <div class="card-actions">
                        <select id="treatmentType" class="filter-select">
                            <option value="all">All Treatments</option>
                            <?php foreach ($services as $service): ?>
                                <option value="<?= $service['services_id'] ?>"><?= $service['name'] ?></option>
                            <?php endforeach; ?>
                        </select>

                        <select id="timeFilter" class="filter-select">
                            <option value="year">This Year</option>
                            <option value="6months">Last 6 Months</option>
                            <option value="3months">Last 3 Months</option>
                        </select>
                    </div>
                </div>
                <div class="dashboard-card-body">
                    <div class="analytics-container">
                        <canvas id="analyticsChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Modal -->
    <div class="modal fade" id="eventModal" tabindex="-1" aria-labelledby="eventModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="eventModalLabel">Add New Event</h5>
                </div>
                <div class="modal-body">
                    <form id="eventForm">
                        <div class="mb-3">
                            <label for="eventDate" class="form-label">Event Date</label>
                            <input type="date" class="form-control" id="eventDate" required>
                        </div>
                        <div class="mb-3">
                            <label for="eventTitle" class="form-label">Event Title</label>
                            <input type="text" class="form-control" id="eventTitle" placeholder="Enter event title"
                                required>
                        </div>
                        <div class="mb-3">
                            <label for="eventDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="eventDescription" rows="3"
                                placeholder="Enter event description"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="saveEventBtn" class="btn btn-primary">Save Event</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Event Details Modal -->
    <div class="modal fade" id="eventDetailsModal" tabindex="-1" aria-labelledby="eventDetailsModalLabel"
        aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="eventDetailsModalLabel">Appointment Details</h5>
                </div>
                <div class="modal-body">
                    <div id="eventDetailsContent">
                        <p><strong>Date:</strong> <span id="eventDate"></span></p>
                        <p><strong>Patient:</strong> <span id="eventPatient"></span></p>
                        <p><strong>Service:</strong> <span id="eventService"></span></p>
                        <p><strong>Time:</strong> <span id="eventTime"></span></p>
                        <p><strong>Status:</strong> <span id="eventStatus" class="badge"></span></p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <a href="../appointmentlist/appointments.php" class="btn btn-primary">View All Appointments</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.4/index.global.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            // Initialize analytics chart
            const ctx = document.getElementById('analyticsChart').getContext('2d');
            const analyticsChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode($chartLabels) ?>,
                    datasets: <?= json_encode($datasets) ?>
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true,
                                font: {
                                    family: "'Inter', sans-serif",
                                    size: 12
                                }
                            }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                stepSize: 1,
                                font: {
                                    family: "'Inter', sans-serif"
                                }
                            },
                            grid: {
                                drawBorder: false
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                font: {
                                    family: "'Inter', sans-serif"
                                }
                            }
                        }
                    }
                }
            });

            // Add event listeners for treatment type and time filter
            document.getElementById('treatmentType').addEventListener('change', updateChart);
            document.getElementById('timeFilter').addEventListener('change', updateChart);

            function updateChart() {
                const treatmentType = document.getElementById('treatmentType').value;
                const timeFilter = document.getElementById('timeFilter').value;
                
                // Here you would typically make an AJAX call to get new data
                // For now, we'll just simulate a loading state
                analyticsChart.data.datasets.forEach(dataset => {
                    dataset.data = dataset.data.map(() => Math.floor(Math.random() * 10));
                });
                analyticsChart.update();
            }

            // Existing calendar initialization code...
            const eventModal = new bootstrap.Modal(document.getElementById('eventModal'));
            const eventDetailsModal = new bootstrap.Modal(document.getElementById('eventDetailsModal'));

            // Add Event Button
            document.getElementById('addEventBtn').addEventListener('click', function () {
                // Open the Add Event modal
                eventModal.show();

                // Set default date to today
                const today = new Date().toISOString().split('T')[0];
                document.getElementById('eventDate').value = today;
            });

            // Save Event Button
            document.getElementById('saveEventBtn').addEventListener('click', function () {
                const title = document.getElementById('eventTitle').value;
                const date = document.getElementById('eventDate').value;
                const description = document.getElementById('eventDescription').value;

                if (!title || !date) {
                    alert('Please fill in all required fields');
                    return;
                }

                // Add event to calendar (example logic)
                alert('Event added successfully! In a production environment, this would be saved to the database.');

                // Reset form and close modal
                document.getElementById('eventForm').reset();
                eventModal.hide();
            });

            // Event click handler for FullCalendar
            const calendarElement = document.getElementById('calendar');
            if (calendarElement) {
                const calendar = new FullCalendar.Calendar(calendarElement, {
                    initialView: 'dayGridMonth',
                    height: '100%',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth,listWeek'
                    },
                    events: <?= json_encode($calendarEvents) ?>,
                    eventClick: function (info) {
                        // Populate event details modal
                        document.getElementById('eventDate').textContent = info.event.extendedProps.date;
                        document.getElementById('eventPatient').textContent = info.event.extendedProps.patient;
                        document.getElementById('eventService').textContent = info.event.extendedProps.service;
                        document.getElementById('eventTime').textContent = info.event.extendedProps.time;

                        const statusElement = document.getElementById('eventStatus');
                        const status = info.event.extendedProps.status?.toLowerCase() || 'pending';

                        statusElement.textContent = info.event.extendedProps.status;
                        statusElement.className = 'status-badge badge-' + status;

                        // Show the Event Details modal
                        eventDetailsModal.show();
                    },
                    dayMaxEvents: true
                });

                calendar.render();
            }
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>