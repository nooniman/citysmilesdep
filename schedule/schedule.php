<?php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

// Get all dentists from users table
$dentists_query = "SELECT id, first_name, last_name FROM users WHERE role = 'dentist' ORDER BY last_name, first_name";
$dentists_result = $conn->query($dentists_query);

// Get services for duration info
$services_query = "SELECT services_id, name, description FROM services ORDER BY name";
$services_result = $conn->query($services_query);
$services = [];
if ($services_result && $services_result->num_rows > 0) {
    while ($row = $services_result->fetch_assoc()) {
        $services[$row['services_id']] = $row;
    }
}

// Process messages
$message = '';
if (isset($_SESSION['schedule_message'])) {
    $message = '<div class="alert alert-success">' . $_SESSION['schedule_message'] . '</div>';
    unset($_SESSION['schedule_message']);
}
if (isset($_SESSION['schedule_error'])) {
    $message = '<div class="alert alert-danger">' . $_SESSION['schedule_error'] . '</div>';
    unset($_SESSION['schedule_error']);
}

// Selected dentist (if any)
$selected_dentist = isset($_GET['dentist_id']) ? intval($_GET['dentist_id']) : 0;
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dentist Scheduling | City Smiles Dental Clinic</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.4/index.global.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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

        .custom-modal {
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
            align-items: flex-start;
            /* Slight dark overlay */
        }

        #scheduleModal .modal-content,
        #helpModal .modal-content,
        #exceptionModal .modal-content, #editScheduleModal .modal-content, #editExceptionModal .modal-content{
            background-color: #ffffff;
            border-radius: 20px;
        }

        .modal-content {
            background: #ffffff;
            margin: auto;
            padding: 20px;
            border-radius: 10px;
            width: 90%;
            max-width: 500px;
            position: relative;
            margin-top: 10px;
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

        .dashboard-subtitle {
            color: var(--gray-600);
            font-size: 14px;
            margin-top: 5px;
        }

        .actions-container {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-left: 35%;
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

        /* Dentist Selection Filter */
        .filter-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 25px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
        }

        .filter-title {
            font-weight: 600;
            color: var(--gray-800);
            margin-bottom: 10px;
            font-size: 15px;
            width: 100%;
        }

        .dentist-select {
            flex-grow: 1;
            padding: 12px 15px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 14px;
            color: var(--gray-800);
            background: var(--white);
            transition: all 0.2s ease;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='16' height='16' fill='%236c757d' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 15px center;
            padding-right: 35px;
        }

        .dentist-select:focus {
            outline: none;
            border-color: var(--primary-lilac);
            box-shadow: 0 0 0 3px rgba(157, 125, 237, 0.15);
        }

        /* Week view tabs */
        .week-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-bottom: 25px;
        }

        .week-tab {
            flex: 1;
            min-width: 100px;
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 15px;
            text-align: center;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }

        .week-tab:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .week-tab.active {
            border-color: var(--primary-lilac);
        }

        .week-tab .day-name {
            font-weight: 600;
            color: var(--gray-700);
            margin-bottom: 5px;
            font-size: 14px;
        }

        .week-tab .hours {
            font-size: 12px;
            color: var(--gray-600);
        }

        .week-tab .status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            margin-top: 5px;
        }

        .week-tab .status.available {
            background-color: rgba(60, 179, 113, 0.1);
            color: var(--primary-green);
        }

        .week-tab .status.unavailable {
            background-color: rgba(220, 53, 69, 0.1);
            color: #dc3545;
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 25px;
        }

        @media (min-width: 992px) {
            .content-grid {
                grid-template-columns: 300px 1fr;
            }
        }

        /* Weekly Schedule Card */
        .card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            height: fit-content;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px;
            border-bottom: 1px solid var(--gray-200);
        }

        .card-title {
            font-size: 16px;
            font-weight: 600;
            color: var(--gray-800);
            margin: 0;
        }

        .card-body {
            padding: 20px;
        }

        /* Schedule Table */
        .schedule-table {
            width: 100%;
            border-collapse: collapse;
        }

        .schedule-table th {
            background: var(--gray-100);
            color: var(--gray-700);
            font-weight: 600;
            text-align: left;
            padding: 12px 15px;
            font-size: 13px;
            border-bottom: 1px solid var(--gray-300);
        }

        .schedule-table td {
            padding: 12px 15px;
            vertical-align: middle;
            border-bottom: 1px solid var(--gray-200);
            font-size: 13px;
        }

        .schedule-table tr:last-child td {
            border-bottom: none;
        }

        .schedule-table tr:hover {
            background-color: var(--gray-100);
        }

        /* Calendar Styling */
        .fc {
            font-family: 'Inter', sans-serif;
            font-size: 14px;
        }

        .fc .fc-toolbar-title {
            font-size: 18px;
            font-weight: 600;
            color: var(--gray-800);
        }

        .fc .fc-button-primary {
            background-color: var(--primary-lilac);
            border-color: var(--primary-lilac);
            font-weight: 500;
            box-shadow: none;
        }

        .fc .fc-button-primary:hover {
            background-color: var(--dark-lilac);
            border-color: var(--dark-lilac);
            box-shadow: 0 2px 5px rgba(123, 50, 171, 0.2);
        }

        .fc .fc-button-primary:not(:disabled).fc-button-active,
        .fc .fc-button-primary:not(:disabled):active {
            background-color: var(--dark-lilac);
            border-color: var (--dark-lilac);
        }

        .fc .fc-daygrid-day-number {
            padding: 8px;
            color: var(--gray-700);
        }

        .fc .fc-daygrid-day.fc-day-today {
            background-color: rgba(157, 125, 237, 0.1);
        }

        .fc .fc-event {
            border-radius: 6px;
            padding: 3px;
            font-size: 12px;
            cursor: pointer;
            border: none;
        }

        .fc-event-available {
            background-color: var(--light-green);
            color: var(--dark-green);
        }

        .fc-event-unavailable {
            background-color: #f8d7da;
            color: #721c24;
        }

        .fc-event-exception {
            background-color: #fff3cd;
            color: #856404;
        }

        .fc-event-booked {
            background-color: #cce5ff;
            color: #004085;
        }

        /* Button Styles */
        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            padding: 8px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        .btn-edit {
            background-color: #e0f7fa;
            color: #0097a7;
        }

        .btn-edit:hover {
            background-color: #b2ebf2;
        }

        .btn-delete {
            background-color: #ffebee;
            color: #d32f2f;
        }

        .btn-delete:hover {
            background-color: #ffcdd2;
        }

        .btn-action:hover {
            transform: translateY(-2px);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: var(--gray-700);
        }

        .form-control {
            width: 100%;
            padding: 12px 15px;
            border: 1px solid var(--gray-300);
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary-lilac);
            box-shadow: 0 0 0 3px rgba(157, 125, 237, 0.15);
        }

        .modal-footer {
            padding: 15px 20px;
            border-top: 1px solid var(--gray-200);
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        /* Toast Notifications */
        .toast-container {
            position: fixed;
            top: 20px;
            right: 20px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            z-index: 9999;
        }

        .toast {
            background: white;
            border-radius: 10px;
            padding: 15px;
            min-width: 300px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            display: flex;
            align-items: flex-start;
            gap: 15px;
            animation: slideIn 0.3s forwards, fadeOut 0.5s forwards 4.5s;
            transform: translateX(120%);
            opacity: 1;
        }

        @keyframes slideIn {
            to {
                transform: translateX(0);
            }
        }

        @keyframes fadeOut {
            to {
                opacity: 0;
            }
        }

        .toast-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }

        .toast-success .toast-icon {
            background: var(--primary-green);
            color: white;
        }

        .toast-error .toast-icon {
            background: #ff5252;
            color: white;
        }

        .toast-content {
            flex-grow: 1;
        }

        .toast-title {
            font-weight: 600;
            margin-bottom: 3px;
            color: var(--gray-900);
        }

        .toast-message {
            font-size: 13px;
            color: var(--gray-600);
        }

        .toast-close {
            color: var(--gray-400);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            padding: 0;
            margin-left: auto;
        }

        /* Responsive Adjustments */
        @media (max-width: 992px) {
            .app-container {
                margin-left: 0;
                padding-right: 20px;
                padding-left: 20px;
            }
        }

        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 15px;
            }

            .actions-container {
                width: 100%;
                justify-content: space-between;
            }
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
        }

        .empty-state i {
            font-size: 48px;
            color: var(--gray-400);
            margin-bottom: 15px;
        }

        .empty-state h3 {
            font-size: 18px;
            color: var(--gray-700);
            margin-bottom: 10px;
        }

        .empty-state p {
            color: var(--gray-600);
            max-width: 300px;
            margin: 0 auto;
        }
    </style>
</head>

<body>
    <?php include '../sidebar/sidebar.php'; ?>

    <div class="toast-container"></div>

    <div class="app-container">
        <div class="dashboard-header">
            <div>
                <h1 class="dashboard-title">Dentist Scheduling</h1>
                <p class="dashboard-subtitle">Manage dentist availability and schedules</p>
            </div>
            <div class="actions-container">
                <?php if ($selected_dentist > 0): ?>
                    <button class="btn-primary" id="addScheduleBtn">
                        <i class="fas fa-plus"></i> Add Schedule
                    </button>
                    <button class="btn-primary" id="addExceptionBtn">
                        <i class="fas fa-calendar-times"></i> Add Exception
                    </button>
                <?php endif; ?>
                <button class="btn-primary" id="helpBtn">
                    <i class="fas fa-question-circle"></i> Help
                </button>
            </div>
            <!-- Modals -->
            <div id="scheduleModal" class="custom-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Add Weekly Schedule</h2>
                    </div>
                    <div class="modal-body">
                        <form id="add-schedule-form" action="add_schedule.php" method="post">
                            <input type="hidden" name="dentist_id" value="<?php echo $selected_dentist; ?>">

                            <div class="form-group">
                                <label for="day_of_week" class="form-label">Day of Week</label>
                                <select class="form-control" id="day_of_week" name="day_of_week" required>
                                    <option value="">-- Select Day --</option>
                                    <option value="1">Monday</option>
                                    <option value="2">Tuesday</option>
                                    <option value="3">Wednesday</option>
                                    <option value="4">Thursday</option>
                                    <option value="5">Friday</option>
                                    <option value="6">Saturday</option>
                                    <option value="0">Sunday</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="start_time" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="start_time" name="start_time" required>
                            </div>

                            <div class="form-group">
                                <label for="end_time" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="end_time" name="end_time" required>
                            </div>

                            <div class="form-group">
                                <label for="max_appointments" class="form-label">Maximum Appointments per Slot</label>
                                <input type="number" class="form-control" id="max_appointments" name="max_appointments"
                                    min="1" value="1" required>
                                <small class="text-muted">How many patients can be scheduled during the same time
                                    slot</small>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="is_available"
                                        name="is_available" value="1" checked>
                                    <label class="form-check-label" for="is_available">
                                        Available for appointments
                                    </label>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-action btn-secondary modal-close-btn">Cancel</button>
                        <button type="submit" form="add-schedule-form" class="btn-primary">Save Schedule</button>
                    </div>
                </div>
            </div>
            <div id="exceptionModal" class="custom-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Add Schedule Exception</h2>
                    </div>
                    <div class="modal-body">
                        <form id="add-exception-form" action="add_exception.php" method="post">
                            <input type="hidden" name="dentist_id" value="<?php echo $selected_dentist; ?>">

                            <div class="form-group">
                                <label for="exception_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="exception_date" name="exception_date"
                                    required min="<?php echo date('Y-m-d'); ?>">
                            </div>

                            <div class="form-group">
                                <label for="exception_type" class="form-label">Exception Type</label>
                                <select class="form-control" id="exception_type" name="exception_type" required>
                                    <option value="unavailable">Day Off (Unavailable)</option>
                                    <option value="custom">Custom Hours</option>
                                </select>
                            </div>

                            <div id="custom-hours-container" style="display: none;">
                                <div class="form-group">
                                    <label for="exception_start_time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="exception_start_time"
                                        name="exception_start_time">
                                </div>

                                <div class="form-group">
                                    <label for="exception_end_time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="exception_end_time"
                                        name="exception_end_time">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="reason" class="form-label">Reason (Optional)</label>
                                <input type="text" class="form-control" id="reason" name="reason"
                                    placeholder="e.g., Holiday, Conference, etc.">
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-action btn-secondary modal-close-btn">Cancel</button>
                        <button type="submit" form="add-exception-form" class="btn-primary">Save Exception</button>
                    </div>
                </div>
            </div>

            <!-- Edit Schedule Modal -->
            <div id="editScheduleModal" class="custom-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Edit Weekly Schedule</h2>
                    </div>
                    <div class="modal-body">
                        <form id="edit-schedule-form" action="update_schedule.php" method="post">
                            <input type="hidden" name="availability_id" id="edit_availability_id">
                            <input type="hidden" name="dentist_id" value="<?php echo $selected_dentist; ?>">

                            <div class="form-group">
                                <label for="edit_start_time" class="form-label">Start Time</label>
                                <input type="time" class="form-control" id="edit_start_time" name="start_time" required>
                            </div>

                            <div class="form-group">
                                <label for="edit_end_time" class="form-label">End Time</label>
                                <input type="time" class="form-control" id="edit_end_time" name="end_time" required>
                            </div>

                            <div class="form-group">
                                <label for="edit_max_appointments" class="form-label">Maximum Appointments per
                                    Slot</label>
                                <input type="number" class="form-control" id="edit_max_appointments"
                                    name="max_appointments" min="1" value="1" required>
                            </div>

                            <div class="form-group">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="edit_is_available"
                                        name="is_available" value="1">
                                    <label class="form-check-label" for="edit_is_available">
                                        Available for appointments
                                    </label>
                                </div>
                            </div>
                        </form>

                        <form id="delete-schedule-form" action="delete_schedule.php" method="post" class="mt-4">
                            <input type="hidden" name="availability_id" id="delete_availability_id">
                            <input type="hidden" name="dentist_id" value="<?php echo $selected_dentist; ?>">
                            <button type="submit" class="btn-action btn-delete w-100" id="delete-schedule-btn">
                                <i class="fas fa-trash-alt"></i> Delete Schedule
                            </button>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-action btn-secondary modal-close-btn">Cancel</button>
                        <button type="submit" form="edit-schedule-form" class="btn-primary">Update Schedule</button>
                    </div>
                </div>
            </div>
            <div id="helpModal" class="custom-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Scheduling Help</h2>
                    </div>
                    <div class="modal-body">
                        <h5>How Scheduling Works</h5>
                    </div>
                    <div class="modal-body">
                        <h5>How Scheduling Works</h5>
                        <p>The scheduling system allows you to manage dentist availability in two ways:</p>

                        <h6>1. Weekly Schedule</h6>
                        <p>Set the regular working hours for each day of the week. This is the default schedule that
                            repeats
                            every week.</p>

                        <h6>2. Exceptions</h6>
                        <p>Create exceptions for specific dates (holidays, vacation days, etc.) that override the
                            regular weekly
                            schedule.</p>

                        <h5>Adding a Weekly Schedule</h5>
                        <ol>
                            <li>Select a dentist from the dropdown</li>
                            <li>Click "Add Schedule" button</li>
                            <li>Select the day of the week</li>
                            <li>Set the start and end times</li>
                            <li>Specify the maximum number of appointments that can be booked during each time slot</li>
                            <li>Click "Save Schedule"</li>
                        </ol>

                        <h5>Adding an Exception</h5>
                        <ol>
                            <li>Select a dentist from the dropdown</li>
                            <li>Click "Add Exception" button</li>
                            <li>Select the specific date</li>
                            <li>Choose either "Day Off" or "Custom Hours"</li>
                            <li>If "Custom Hours", set the start and end times</li>
                            <li>Optionally add a reason for the exception</li>
                            <li>Click "Save Exception"</li>
                        </ol>

                        <h5>Calendar View</h5>
                        <p>The calendar shows the dentist's availability at a glance:</p>
                        <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-top: 10px;">
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <span
                                    style="width: 15px; height: 15px; background-color: #9aeaa1; display: inline-block; border-radius: 3px;"></span>
                                <span>Available</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <span
                                    style="width: 15px; height: 15px; background-color: #f8d7da; display: inline-block; border-radius: 3px;"></span>
                                <span>Unavailable</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <span
                                    style="width: 15px; height: 15px; background-color: #fff3cd; display: inline-block; border-radius: 3px;"></span>
                                <span>Exception</span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 5px;">
                                <span
                                    style="width: 15px; height: 15px; background-color: #cce5ff; display: inline-block; border-radius: 3px;"></span>
                                <span>Booked</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-primary modal-close-btn">Close</button>
                    </div>
                </div>
            </div>
            
            <!-- Edit Exception Modal -->
            <div id="editExceptionModal" class="custom-modal">
                <div class="modal-content">
                    <div class="modal-header">
                        <h2 class="modal-title">Edit Schedule Exception</h2>
                    </div>
                    <div class="modal-body">
                        <form id="edit-exception-form" action="update_exception.php" method="post">
                            <input type="hidden" name="exception_id" id="edit_exception_id">
                            <input type="hidden" name="dentist_id" value="<?php echo $selected_dentist; ?>">

                            <div class="form-group">
                                <label for="edit_exception_date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="edit_exception_date" name="exception_date" required>
                            </div>

                            <div class="form-group">
                                <label for="edit_exception_type" class="form-label">Exception Type</label>
                                <select class="form-control" id="edit_exception_type" name="exception_type" required>
                                    <option value="unavailable">Day Off (Unavailable)</option>
                                    <option value="custom">Custom Hours</option>
                                </select>
                            </div>

                            <div id="edit-custom-hours-container" style="display: none;">
                                <div class="form-group">
                                    <label for="edit_exception_start_time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="edit_exception_start_time" name="exception_start_time">
                                </div>

                                <div class="form-group">
                                    <label for="edit_exception_end_time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="edit_exception_end_time" name="exception_end_time">
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="edit_reason" class="form-label">Reason (Optional)</label>
                                <input type="text" class="form-control" id="edit_reason" name="reason" placeholder="e.g., Holiday, Conference, etc.">
                            </div>
                        </form>

                        <form id="delete-exception-form" action="delete_exception.php" method="post" class="mt-4">
                            <input type="hidden" name="exception_id" id="delete_exception_id">
                            <input type="hidden" name="dentist_id" value="<?php echo $selected_dentist; ?>">
                            <button type="submit" class="btn-action btn-delete w-100" id="delete-exception-btn">
                                <i class="fas fa-trash-alt"></i> Delete Exception
                            </button>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn-action btn-secondary modal-close-btn">Cancel</button>
                        <button type="submit" form="edit-exception-form" class="btn-primary">Update Exception</button>
                    </div>
                </div>
            </div>
        </div>

        <?php echo $message; ?>

        <div class="filter-container">
            <div class="filter-title">Select a dentist to manage their schedule</div>
            <form method="get" action="schedule.php" class="w-100">
                <select name="dentist_id" id="dentist_select" class="dentist-select" onchange="this.form.submit()">
                    <option value="">-- Select Dentist --</option>
                    <?php
                    if ($dentists_result && $dentists_result->num_rows > 0) {
                        mysqli_data_seek($dentists_result, 0); // Reset pointer
                        while ($dentist = $dentists_result->fetch_assoc()) {
                            $selected = ($selected_dentist == $dentist['id']) ? 'selected' : '';
                            echo "<option value='" . $dentist['id'] . "' $selected>" .
                                $dentist['first_name'] . ' ' . $dentist['last_name'] .
                                "</option>";
                        }
                    }
                    ?>
                </select>
            </form>
        </div>

        <?php if ($selected_dentist > 0): ?>
            <!-- Weekly schedule tabs -->
            <div class="week-tabs" id="week-tabs">
                <div class="week-tab" data-day="1">
                    <div class="day-name">Monday</div>
                    <div class="hours">Loading...</div>
                    <div class="status">...</div>
                </div>
                <div class="week-tab" data-day="2">
                    <div class="day-name">Tuesday</div>
                    <div class="hours">Loading...</div>
                    <div class="status">...</div>
                </div>
                <div class="week-tab" data-day="3">
                    <div class="day-name">Wednesday</div>
                    <div class="hours">Loading...</div>
                    <div class="status">...</div>
                </div>
                <div class="week-tab" data-day="4">
                    <div class="day-name">Thursday</div>
                    <div class="hours">Loading...</div>
                    <div class="status">...</div>
                </div>
                <div class="week-tab" data-day="5">
                    <div class="day-name">Friday</div>
                    <div class="hours">Loading...</div>
                    <div class="status">...</div>
                </div>
                <div class="week-tab" data-day="6">
                    <div class="day-name">Saturday</div>
                    <div class="hours">Loading...</div>
                    <div class="status">...</div>
                </div>
                <div class="week-tab" data-day="0">
                    <div class="day-name">Sunday</div>
                    <div class="hours">Loading...</div>
                    <div class="status">...</div>
                </div>
            </div>

            <div class="content-grid">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Weekly Schedule</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="schedule-table" id="weekly-schedule-table">
                                <thead>
                                    <tr>
                                        <th>Day</th>
                                        <th>Hours</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="3" class="text-center">Loading schedule...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title">Calendar View</h5>
                    </div>
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>
            </div>

        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-alt"></i>
                <h3>No Dentist Selected</h3>
                <p>Please select a dentist above to view and manage their schedule.</p>
            </div>
        <?php endif; ?>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.4/index.global.min.js"></script>
    <script>
        document.addEventListener("DOMContentLoaded", function () {
    // Add click event listeners to all modal close buttons
    const closeButtons = document.querySelectorAll(".modal-close-btn");
    closeButtons.forEach(button => {
        button.addEventListener("click", function() {
            // Find the closest parent modal and hide it
            const modal = this.closest(".custom-modal");
            if (modal) {
                modal.style.display = "none";
            }
        });
    });
});
        document.addEventListener("DOMContentLoaded", function () {
            const modals = {
                addScheduleBtn: document.getElementById("scheduleModal"),
                addExceptionBtn: document.getElementById("exceptionModal"),
                helpBtn: document.getElementById("helpModal")
            };

            Object.keys(modals).forEach(btnId => {
                const btn = document.getElementById(btnId);
                const modal = modals[btnId];

                if (btn && modal) {
                    btn.addEventListener("click", () => modal.style.display = "block");
                }
            });

            const closeButtons = document.querySelectorAll(".custom-modal .close");
            closeButtons.forEach(btn =>
                btn.addEventListener("click", () =>
                    btn.closest(".custom-modal").style.display = "none"
                )
            );

            window.addEventListener("click", function (event) {
                Object.values(modals).forEach(modal => {
                    if (event.target == modal) modal.style.display = "none";
                });
            });
        });
        document.addEventListener('DOMContentLoaded', function () {
            // Modal handlers
            const modals = [{ btn: '#addScheduleBtn', modal: '#addScheduleModal' },
            { btn: '#addExceptionBtn', modal: '#addExceptionModal' },
            { btn: '#helpBtn', modal: '#helpModal' }
            ];

            modals.forEach(item => {
                const btn = document.querySelector(item.btn);
                const modal = document.querySelector(item.modal);
                const closeButtons = modal?.querySelectorAll('.modal-close, .modal-close-btn');

                if (btn && modal) {
                    btn.addEventListener('click', function () {
                        modal.style.display = 'flex';
                    });
                }

                if (closeButtons) {
                    closeButtons.forEach(button => {
                        button.addEventListener('click', function () {
                            modal.style.display = 'none';
                        });
                    });
                }

                // Close when clicking outside
                if (modal) {
                    modal.addEventListener('click', function (e) {
                        if (e.target === modal) {
                            modal.style.display = 'none';
                        }
                    });
                }
            });

            // Handle exception type change
            document.getElementById('exception_type')?.addEventListener('change', function () {
                var customHoursContainer = document.getElementById('custom-hours-container');
                if (this.value === 'custom') {
                    customHoursContainer.style.display = 'block';
                    document.getElementById('exception_start_time').required = true;
                } else {
                    customHoursContainer.style.display = 'none';
                    document.getElementById('exception_start_time').required = false;
                    document.getElementById('exception_end_time').required = false;
                }
            });

            // Handle edit exception type change
            document.getElementById('edit_exception_type')?.addEventListener('change', function () {
                var customHoursContainer = document.getElementById('edit-custom-hours-container');
                if (this.value === 'custom') {
                    customHoursContainer.style.display = 'block';
                    document.getElementById('edit_exception_start_time').required = true;
                    document.getElementById('edit_exception_end_time').required = true;
                } else {
                    customHoursContainer.style.display = 'none';
                    document.getElementById('edit_exception_start_time').required = false;
                    document.getElementById('edit_exception_end_time').required = false;
                }
            });

            <?php if ($selected_dentist > 0): ?>
                // Load weekly schedule for the week tab display
                loadWeeklyTabs();

                // Load weekly schedule for the table
                loadWeeklySchedule();

                // Initialize calendar
                var calendarEl = document.getElementById('calendar');
                if (calendarEl) {
                    var calendar = new FullCalendar.Calendar(calendarEl, {
                        initialView: 'dayGridMonth',
                        headerToolbar: {
                            left: 'prev,next today',
                            center: 'title',
                            right: 'dayGridMonth,timeGridWeek'
                        },
                        events: 'get_schedule_events.php?dentist_id=<?php echo $selected_dentist; ?>',
                        eventClick: function (info) {
                            var eventType = info.event.extendedProps.type;

                            if (eventType === 'exception') {
                                var exceptionId = info.event.extendedProps.exceptionId;
                                openEditExceptionModal(exceptionId);
                            } else if (eventType === 'regular') {
                                var availabilityId = info.event.extendedProps.availabilityId;
                                openEditScheduleModal(availabilityId);
                            }
                        }
                    });
                    calendar.render();

                    // Store the calendar instance globally for access
                    window.mainCalendar = calendar;
                }
            <?php endif; ?>

            // Week tab click event
            document.querySelectorAll('.week-tab').forEach(tab => {
                tab.addEventListener('click', function () {
                    const day = this.getAttribute('data-day');
                    const availabilities = window.weeklySchedules?.find(s => s.day_of_week == day);

                    if (availabilities) {
                        openEditScheduleModal(availabilities.availability_id);
                    } else {
                        // Pre-select the day in the add modal
                        document.getElementById('day_of_week').value = day;
                        document.querySelector('#addScheduleModal').style.display = 'flex';
                    }
                });
            });
        });

        // Store weekly schedules globally
        window.weeklySchedules = [];

        function loadWeeklyTabs() {
            // Use the new dedicated endpoint for JSON data
            fetch('get_weekly_tabs.php?dentist_id=<?php echo $selected_dentist; ?>')
                .then(response => {
                    if (!response.ok) {
                        throw new Error('Network response was not ok ' + response.statusText);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        console.error(data.error);
                        return;
                    }

                    window.weeklySchedules = data;

                    // Clear all tabs first
                    document.querySelectorAll('.week-tab').forEach(tab => {
                        const dayNumber = tab.getAttribute('data-day');
                        const hoursEl = tab.querySelector('.hours');
                        const statusEl = tab.querySelector('.status');

                        hoursEl.textContent = 'Not Set';
                        statusEl.textContent = 'Unavailable';
                        statusEl.className = 'status unavailable';
                    });

                    // Update tabs with schedule data
                    data.forEach(schedule => {
                        const tab = document.querySelector(`.week-tab[data-day="${schedule.day_of_week}"]`);
                        if (tab) {
                            const hoursEl = tab.querySelector('.hours');
                            const statusEl = tab.querySelector('.status');

                            // Use formatted time for display
                            hoursEl.textContent = `${schedule.start_time_formatted} - ${schedule.end_time_formatted}`;

                            if (schedule.is_available == 1) {
                                statusEl.textContent = 'Available';
                                statusEl.className = 'status available';
                            } else {
                                statusEl.textContent = 'Unavailable';
                                statusEl.className = 'status unavailable';
                            }
                        }
                    });
                })
                .catch(error => {
                    console.error('Error loading weekly tabs:', error);
                    // Display a more user-friendly message in the UI
                    document.querySelectorAll('.week-tab').forEach(tab => {
                        const hoursEl = tab.querySelector('.hours');
                        hoursEl.textContent = 'Error loading data';
                    });
                });
        }

        function loadWeeklySchedule() {
            fetch('get_weekly_schedule.php?dentist_id=<?php echo $selected_dentist; ?>')
                .then(response => response.text())
                .then(data => {
                    document.getElementById('weekly-schedule-table').querySelector('tbody').innerHTML = data;
                })
                .catch(error => {
                    console.error('Error loading weekly schedule:', error);
                    showToast('Error', 'Could not load weekly schedule.', 'error');
                });
        }

        function openEditScheduleModal(availabilityId) {
            fetch(`get_schedule_details.php?availability_id=${availabilityId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        showToast('Error', data.error, 'error');
                        return;
                    }
                    document.getElementById('edit_availability_id').value = data.availability_id;
                    document.getElementById('delete_availability_id').value = data.availability_id;
                    document.getElementById('edit_start_time').value = data.start_time.substring(0, 5);
                    document.getElementById('edit_end_time').value = data.end_time.substring(0, 5);
                    document.getElementById('edit_max_appointments').value = data.max_appointments;
                    document.getElementById('edit_is_available').checked = data.is_available == 1;

                    // Show the modal
                    const editModal = document.getElementById('editScheduleModal');
                    if (editModal) {
                        editModal.style.display = 'block';
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error', 'Failed to load schedule details', 'error');
                });
        }
        function openEditExceptionModal(exceptionId) {
            fetch(`get_exception_details.php?exception_id=${exceptionId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('edit_exception_id').value = data.exception_id;
                    document.getElementById('delete_exception_id').value = data.exception_id;
                    document.getElementById('edit_exception_date').value = data.exception_date;
                    document.getElementById('edit_exception_type').value = data.exception_type;
                    document.getElementById('edit_reason').value = data.reason || '';

                    // Handle custom hours
                    var customHoursContainer = document.getElementById('edit-custom-hours-container');
                    if (data.exception_type === 'custom') {
                        customHoursContainer.style.display = 'block';
                        document.getElementById('edit_exception_start_time').value = data.start_time;
                        document.getElementById('edit_exception_end_time').value = data.end_time;
                    } else {
                        customHoursContainer.style.display = 'none';
                    }

                    // Show the modal
                    document.getElementById('editExceptionModal').style.display = 'flex';
                })
                .catch(error => {
                    console.error('Error getting exception details:', error);
                    showToast('Error', 'Could not load exception details.', 'error');
                });
        }

        // Add a confirm dialog to delete buttons
        document.getElementById('delete-schedule-btn')?.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this schedule?')) {
                e.preventDefault();
            }
        });

        document.getElementById('delete-exception-btn')?.addEventListener('click', function (e) {
            if (!confirm('Are you sure you want to delete this exception?')) {
                e.preventDefault();
            }
        });

        // Show toast notification
        function showToast(title, message, type) {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
    <div class="toast-icon">
        <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}"></i>
    </div>
    <div class="toast-content">
        <div class="toast-title">${title}</div>
        <div class="toast-message">${message}</div>
    </div>
    <button class="toast-close">&times;</button>
`;

            document.querySelector(".toast-container").appendChild(toast);

            // Auto-remove toast after animation
            setTimeout(() => {
                toast.remove();
            }, 5000);

            // Manual close
            toast.querySelector(".toast-close").addEventListener('click', function () {
                toast.remove();
            });
        }

        // Form submit handlers with AJAX
        const forms = [
            {
                id: 'add-schedule-form',
                url: 'add_schedule.php',
                modalId: '#addScheduleModal',
                successMessage: 'Schedule added successfully.'
            },
            {
                id: 'add-exception-form',
                url: 'add_exception.php',
                modalId: '#addExceptionModal',
                successMessage: 'Exception added successfully.'
            },
            {
                id: 'edit-schedule-form',
                url: 'update_schedule.php',
                modalId: '#editScheduleModal',
                successMessage: 'Schedule updated successfully.'
            },
            {
                id: 'edit-exception-form',
                url: 'update_exception.php',
                modalId: '#editExceptionModal',
                successMessage: 'Exception updated successfully.'
            }
        ];

        forms.forEach(form => {
            const formEl = document.getElementById(form.id);
            if (formEl) {
                formEl.addEventListener('submit', function (e) {
                    e.preventDefault();
                    const formData = new FormData(formEl);

                    fetch(form.url, {
                        method: 'POST',
                        body: formData
                    })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                showToast('Success', form.successMessage, 'success');
                                document.querySelector(form.modalId).style.display = 'none';

                                // Refresh data
                                loadWeeklyTabs();
                                loadWeeklySchedule();

                                // Refresh calendar
                                const calendarEl = document.getElementById('calendar');
                                if (calendarEl && window.mainCalendar) {
                                    window.mainCalendar.refetchEvents();
                                } else {
                                    // If the calendar isn't accessible, reload the page
                                    setTimeout(() => location.reload(), 1000);
                                }
                            } else {
                                showToast('Error', data.message || 'An error occurred.', 'error');
                            }
                        })
                        .catch(error => {
                            console.error(`Error submitting ${form.id}:`, error);
                            showToast('Error', 'Failed to save changes. Please try again.', 'error');
                        });
                });
            }
        });

        document.getElementById('add-schedule-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('add_schedule.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Success', data.message, 'success');
                        const scheduleModal = document.getElementById('scheduleModal');
                        if (scheduleModal) {
                            scheduleModal.style.display = 'none'; // Ensure the modal exists before accessing its style
                        }
                        loadWeeklyTabs(); // Refresh weekly tabs
                        loadWeeklySchedule(); // Refresh weekly schedule table
                    } else {
                        showToast('Error', data.message, 'error');
                    }
                })
                .catch(error => {
                    console.error('Error submitting form:', error);
                    showToast('Error', 'An unexpected error occurred.', 'error');
                });
        });

        // Ensure the toast container exists in the DOM
        document.addEventListener('DOMContentLoaded', function () {
            let toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) {
                toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container';
                document.body.appendChild(toastContainer);
            }
        });

        // Fix the showToast function
        function showToast(title, message, type) {
            const toastContainer = document.querySelector('.toast-container');
            if (!toastContainer) return;

            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.innerHTML = `
                <div class="toast-icon">
                    <i class="fas fa-${type === 'success' ? 'check' : 'exclamation'}"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${title}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close">&times;</button>
            `;

            toastContainer.appendChild(toast);

            // Auto-remove toast after 5 seconds
            setTimeout(() => {
                toast.remove();
            }, 5000);

            // Manual close
            toast.querySelector('.toast-close').addEventListener('click', () => {
                toast.remove();
            });
        }

        // Fix the form submission handler for adding schedules
        document.getElementById('add-schedule-form').addEventListener('submit', function (e) {
            e.preventDefault();
            const formData = new FormData(this);

            fetch('add_schedule.php', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Success', data.message, 'success');
                        const scheduleModal = document.getElementById('scheduleModal');
                        if (scheduleModal) {
                            scheduleModal.style.display = 'none'; // Close the modal
                        }
                        loadWeeklyTabs(); // Refresh weekly tabs
                        loadWeeklySchedule(); // Refresh weekly schedule table
                    } else {
                        showToast('Error', data.message || 'Failed to add schedule.', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error submitting form:', error);
                    showToast('Error', 'An unexpected error occurred.', 'error');
                });
        });
    </script>
</body>

</html>