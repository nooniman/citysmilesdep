<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit();
}

include '../database.php';

// Fetch user details
$patient_id = $_SESSION['user_id'];
$userQuery = $conn->query("SELECT * FROM users WHERE id = $patient_id");
$user = $userQuery->fetch_assoc();

// Fetch appointments by joining with 'patients' to match the current user
$appointments = [];
$result = $conn->query("
    SELECT a.appointment_date, a.appointment_time, a.status
    FROM appointments a
    JOIN patients p ON a.patient_id = p.patient_info_id
    WHERE p.user_id = $patient_id
");

while ($row = $result->fetch_assoc()) {
    $appointments[] = [
        'appointment_date' => $row['appointment_date'],
        'appointment_time' => $row['appointment_time'],
        'status' => $row['status'],
    ];
}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../userdashboard/dashboard.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>City Smile Dental Clinic Dashboard</title>

    <!-- Load Bootstrap and Font Awesome FIRST - before sidebar is included -->

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

    <!-- Then load other stylesheets -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 1.5rem;
            width: 400px;
            border-radius: 10px;
            text-align: center;
            position: relative;
            border-top: 10px solid #000069;
            max-height: 70vh;
            overflow-y: auto;
            scrollbar-width: thin;
            scrollbar-color: #000069 white;
        }

        .modal-content::-webkit-scrollbar {
            width: 8px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: #4CAF50;
            border-radius: 10px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: white;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 15px;
            font-size: 22px;
            cursor: pointer;
        }

        /* Table styles */
        table {
            width: 100%;
            border-collapse: collapse;
        }

        th,
        td {
            text-align: left;
            padding: 8px;
            border-bottom: 1px solid #ddd;
        }

        th {
            background-color: #f5f5f5;
        }

        /* Appointment styles */
        .appointments-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }

        .tab-button {
            padding: 8px 16px;
            border: none;
            background-color: #000069;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
        }

        .tab-button:hover {
            background-color: #4B0082;
        }

        .tab-button.active {
            background-color: #000080;
            color: white;
        }

        .request-appointment {
            border: 2px solid #000080;
            background: white;
            color: #000080;
            padding: 8px 16px;
            border-radius: 4px;
            cursor: pointer;
            display: block;
            margin: 10px 0;
        }

        .request-appointment:hover {
            background: #000080;
            color: white;
        }

        .status.pending {
            background: #d4c100;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
        }

        .cancel-appointment {
            background: red;
            color: white;
            padding: 8px 16px;
            border: none;
            margin-left: 70%;
            border-radius: 4px;
            cursor: pointer;
        }

        .cancel-appointment:hover {
            background: darkred;
        }

        /* Form styles */
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
            width: 100%;
            margin: 0 auto;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            width: 100%;
        }

        input,
        select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 16px;
        }

        .btn {
            padding: 0.6rem 1.2rem;
            border-radius: 5px;
            cursor: pointer;
            width: 48%;
        }

        .btn.cancel {
            color: red;
            background: none;
        }

        .btn.cancel:hover {
            border: 1px solid red;
        }

        .btn.next {
            background: #000069;
            color: white;
        }

        .btn.next:hover {
            background: blue;
        }

        /* Profile picture upload styles */
        .image-upload-container {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            margin-bottom: 10px;
        }

        .profile-placeholder {
            width: 10em;
            height: 10em;
            border: 2px dashed #ccc;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            background-color: #f9f9f9;
            color: #999;
            font-size: 1.2em;
            position: relative;
            overflow: hidden;
            cursor: pointer;
        }

        .profile-placeholder img {
            width: 80%;
            height: 80%;
            object-fit: cover;
        }

        .image-upload-container input[type="file"] {
            position: absolute;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }

        /* Password modal */
        #passwordModal {
            display: none;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80%;
            max-width: 400px;
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.1);
        }

        /* Modal buttons */
        .modal-buttons {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 10px;
            width: 100%;
            padding-left: 10px;
        }

        .modal-buttons button {
            font-size: 14px;
            background: none;
            border: none;
            padding: 5px 10px;
            cursor: pointer;
        }

        .modal-buttons button:hover {
            border: 1px solid #000069;
        }

        .modal-buttons button.active {
            font-weight: bold;
            color: navy;
            border-bottom: 2px solid navy;
        }

        /* Cancel and submit buttons */
        .cancel-btn {
            background: white;
            border: 2px solid navy;
            color: navy;
            font-weight: bold;
        }

        .cancel-btn:hover {
            border: 1px solid red;
        }

        .submit-btn {
            background: navy;
            color: white;
            font-weight: bold;
        }

        .submit-btn:hover {
            opacity: 0.6;
        }

        .content {
            margin-left: 0;
            margin-top: 12vh;
            padding: 30px;
            background-color: #f5f7fa;
            min-height: calc(100vh - 12vh);
        }

        /* Other dashboard specific styles */
        .two-column {
            display: grid;
            grid-template-columns: 1fr 2fr;
            /* Left column 1fr, right column 2fr */
            grid-gap: 50px;
        }

        .patient-card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            width: 100%;
            max-width: 350px;
        }

        .card {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
            flex: 1;
            min-width: 300px;
        }

        .card-title,
        .card h3 {
            color: #297859;
            margin-bottom: 15px;
            border-bottom: 1px solid #e9ecef;
            padding-bottom: 10px;
        }

        .patient-photo-centered {
            text-align: center;
            margin-bottom: 15px;
        }

        .patient-photo-centered img {
            border-radius: 50%;
            border: 3px solid #7B32AB;
            width: 80px;
            height: 80px;
            object-fit: cover;
        }

        .patient-name {
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 5px;
        }

        .patient-email {
            text-align: center;
            color: #6c757d;
            margin-bottom: 15px;
        }

        .patient-details {
            border-top: 1px solid #e9ecef;
            padding-top: 15px;
        }

        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }

        .detail-label {
            color: #6c757d;
            font-weight: 500;
        }

        .detail-value {
            color: #212529;
            font-weight: 500;
        }

        .time-format {
            font-weight: 500;
            color: #7B32AB;
        }

        #calendar {
            margin-top: 15px;
            height: 300px;
        }

        @media (max-width: 992px) {
            .content {
                margin-left: 0;
                padding: 15px;
            }

            .two-column {
                flex-direction: column;
            }

            .patient-card {
                max-width: 100%;
            }

            .dashboard-card {
                background-color: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                padding: 30px;
                margin-bottom: 20px;
                width: 95%;
                line-height: 2.5;
            }
        }

        .page-title {
            margin-bottom: 20px;
            color: #333;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .page-title i {
            color: #7B32AB;
            font-size: 1.5rem;
        }

        .prescription-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 25px;
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            border-bottom: 1px solid #eaeaea;
            padding-bottom: 15px;
        }

        .card-header h2 {
            font-size: 1.5rem;
            color: #7B32AB;
            margin: 0;
            font-weight: 600;
        }

        .prescription-table {
            width: 100%;
            border-collapse: collapse;
        }

        .prescription-table th {
            background-color: #f8f9fc;
            color: #555;
            font-weight: 600;
            text-align: left;
            padding: 15px;
            font-size: 0.9rem;
            border-bottom: 2px solid #eaeaea;
            text-transform: uppercase;
        }

        .prescription-table td {
            padding: 15px;
            border-bottom: 1px solid #eaeaea;
            color: #555;
            font-size: 0.95rem;
        }

        .prescription-table tr:hover {
            background-color: #f8f9fc;
        }

        .prescription-date {
            font-weight: 500;
            color: #7B32AB;
        }

        .prescription-notes {
            max-width: 300px;
            white-space: pre-wrap;
        }

        .empty-state {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 50px 20px;
            text-align: center;
            color: #888;
        }

        .empty-state i {
            font-size: 3rem;
            color: #d1d1d1;
            margin-bottom: 20px;
        }

        .empty-state p {
            max-width: 400px;
            margin: 0 auto;
        }

        @media (max-width: 992px) {
            .content {
                margin-left: 0;
                padding: 20px;
            }

            .prescription-table th,
            .prescription-table td {
                padding: 12px 10px;
            }
        }
    </style>
</head>

<body>
    <?php include 'user_sidebar.php'; ?>

    <main class="content">
        <div class="two-column">
            <!-- Left Column: Patient Info -->
            <div class="patient-card">
                <h2 class="card-title">Patient Info</h2>
                <div class="patient-photo-centered">
                    <img src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : '../icons/profile.png'; ?>"
                        alt="Patient Photo">
                </div>
                <h3 class="patient-name"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h3>
                <p class="patient-email"><?php echo $user['email']; ?></p>
                <div class="patient-details">
                    <div class="detail-row">
                        <span class="detail-label">Gender</span>
                        <span class="detail-value"><?php echo $user['gender']; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Birthdate</span>
                        <span class="detail-value"><?php echo $user['birthdate']; ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Contact</span>
                        <span class="detail-value"><?php echo $user['contact']; ?></span>
                    </div>
                </div>
            </div>

            <!-- Right Column: Latest Appointment Details & Calendar -->
            <div class="prescription-card">
                <div class="card-header">
                    <h2>Prescription History</h2>
                </div>

                <?php if ($result && $result->num_rows > 0): ?>
                    <table class="prescription-table">
                        <thead>
                            <tr>
                                <th>Date Prescribed</th>
                                <th>Medication</th>
                                <th>Instructions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td class="prescription-date">
                                        <?php echo date("F j, Y", strtotime($row['date_prescripted'])); ?>
                                    </td>
                                    <td>
                                        <strong><?php echo htmlspecialchars($row['medicine']); ?></strong>
                                    </td>
                                    <td class="prescription-notes">
                                        <?php echo nl2br(htmlspecialchars($row['notes'])); ?>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fa-solid fa-clipboard-list"></i>
                        <h3>No Prescriptions Found</h3>
                        <p>You don't have any prescriptions in your record yet. After your dental appointment, any
                            medications
                            prescribed will appear here.</p>
                    </div>
                <?php endif; ?>
            </div>
    </main>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
    <script>
        $(document).ready(function () {
            $('#calendar').fullCalendar({
                events: [
                    <?php foreach ($appointments as $appointment): ?>{
                            title: '<?= $appointment['status'] ?>',
                            start: '<?= $appointment['appointment_date'] . " " . date("H:i", strtotime($appointment['appointment_time'])) ?>'
                        },
                    <?php endforeach; ?>
                ],
                editable: false,
                eventLimit: true
            });
        });
    </script>
</body>

</html>