<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit();
}
include '../database.php';

// Get list of services
$servicesQuery = $conn->query("SELECT * FROM services");
$services = [];
while ($row = $servicesQuery->fetch_assoc()) {
    $services[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Book Appointment</title>
    <!-- Include the sidebar styles so it appears correctly -->
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <style>
        /* Booking form styles */
        .booking-form {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            max-width: 500px;
            margin: 20px auto;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .booking-form label {
            display: block;
            font-weight: bold;
            margin-top: 10px;
        }
        .booking-form input,
        .booking-form select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }
        .booking-form button {
            margin-top: 20px;
            width: 100%;
            padding: 10px;
            background-color: #4CAF50;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .booking-form button:hover {
            background-color: #000069;
        }
        /* Adjust content margin to accommodate the sidebar */
        .content {
            margin-left: 20%; /* or use your sidebar width */
            padding: 20px;
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>
    <div class="content">
        <h1>Book Your Appointment</h1>
        <div class="booking-form">
            <form action="process_booking.php" method="POST">
                <!-- Use the logged in user's id -->
                <input type="hidden" name="patient_id" value="<?php echo $_SESSION['user_id']; ?>">
                <label for="service">Select Service:</label>
                <select name="service_id" id="service" required>
                    <?php foreach ($services as $service) { ?>
                        <option value="<?php echo $service['services_id']; ?>">
                            <?php echo $service['name']; ?>
                        </option>
                    <?php } ?>
                </select>
                <label for="appointment_date">Appointment Date:</label>
                <input type="date" id="appointment_date" name="appointment_date" required>
                <label for="start_time">Start Time:</label>
                <input type="time" id="start_time" name="start_time" required>
                <label for="end_time">End Time:</label>
                <input type="time" id="end_time" name="end_time" required>
                <button type="submit">Book Appointment</button>
            </form>
        </div>
    </div>
</body>
</html>