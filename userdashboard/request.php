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

// Fetch health declaration questions
$healthQuery = $conn->query("SELECT * FROM patient_health_declaration WHERE patient_info_id = $patient_id");
$health = $healthQuery->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="../userdashboard/dashboard.css">
    <meta charset="UTF-8">
    <title>City Smile Dental Clinic Dashboard</title>
    <style>
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            width: 300px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            text-align: center;
            position: relative;
        }

        .close {
            position: absolute;
            right: 15px;
            top: 15px;
            font-size: 20px;
            cursor: pointer;
        }
    </style>
</head>

<body>
    <header class="header">
        <div class="logo-wrapper">
            <img src="../images/Screenshot__522_-removebg-preview.png" alt="Logo" class="logo">
            <h1>City Smile Dental Clinic</h1>
            <a href="../website/website.php">
                <h3>→ Go back to website!</h3>
            </a>
        </div>
    </header>

    <?php include 'user_sidebar.php'; ?>

    <!-- Admin Profile -->
    <div class="admin-profile">
        <a href="../login/logout.php"><img src="../icons/logout.png" class="logout"></a>
        <span class="admin-text">Admin</span>
    </div>

    <main class="content">
        <div class="two-column">
            <!-- Left Column: Patient Info -->
            <div class="patient-card">
                <h2 class="card-title">Patient Info</h2>
                <div class="patient-photo-centered">
                    <img src="https://via.placeholder.com/80" alt="Patient Photo">
                </div>
                <h3 class="patient-name"><?php echo $user['first_name'] . ' ' . $user['last_name']; ?></h3>
                <p class="patient-email"><?php echo $user['email']; ?></p>
                <div class="patient-details">
                    <div class="detail-row"><span class="detail-label">Gender</span><span
                            class="detail-value"><?php echo $user['gender']; ?></span></div>
                    <div class="detail-row"><span class="detail-label">Birthdate</span><span
                            class="detail-value"><?php echo $user['birthdate']; ?></span></div>
                    <div class="detail-row"><span class="detail-label">Contact</span><span
                            class="detail-value"><?php echo $user['contact']; ?></span></div>
                </div>
            </div>

            <!-- Right Column: Appointment Section -->
            <div>
                <div class="card">
                    <div class="appointments-header">
                        <button style="background-color: #4Caf50;" class="tab-button active">Request
                            Appointment</button>
                        <button class="tab-button">Appointment</button>
                        <button class="tab-button">Prescription</button>
                    </div>
                    <div class="appointment-content">
                        <button class="request-appointment" onclick="openModal()">Create an Appointment</button>
                        <h3>Appointment Details</h3>
                        <p><strong>Appointment Date:</strong> Monday, 18 Feb 2025</p>
                        <p><strong>Time:</strong> 12:00pm - 4:00pm</p>
                        <p><strong>Status:</strong> <span class="status pending">Pending</span></p>
                        <button class="cancel-appointment">Cancel Appointment</button>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal for Requesting an Appointment -->
    <div id="appointmentModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Create an Appointment</h2>
            </div>
            <form action="process_appointment.php" method="POST">
                <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                <div class="form-group">
                    <label for="service">Service</label>
                    <select id="service" name="service_id" required>
                        <option value="" disabled selected>Select a service</option>
                        <?php
                        $servicesQuery = $conn->query("SELECT * FROM services");
                        while ($service = $servicesQuery->fetch_assoc()) {
                            echo '<option value="' . $service['services_id'] . '">' . $service['name'] . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="appointmentDate">Available Date</label>
                    <input type="date" id="appointmentDate" name="appointment_date" required>
                </div>
                <div class="form-group">
                    <label for="appointmentTime">Available Time</label>
                    <input type="time" id="appointmentTime" name="appointment_time" required>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="submit-btn">Submit</button>
                </div>
            </form>
        </div>
    </div>

    <!-- JavaScript to Open/Close Modal -->
    <script>
        function openModal() {
            document.getElementById("appointmentModal").style.display = "flex";
        }
        function closeModal() {
            document.getElementById("appointmentModal").style.display = "none";
        }
    </script>
</body>

</html>