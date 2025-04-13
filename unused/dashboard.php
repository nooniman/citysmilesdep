<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../dashboard/dashboard.css">
    <!-- FullCalendar CSS -->
    <link href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.4/index.global.min.css" rel="stylesheet">
    <title>Dashboard</title>
</head>

<body>
    <?php
    include '../sidebar/sidebar.php';
    include '../database.php';


    

    // Fetch total patients
    $query = "SELECT COUNT(*) AS totalPatients FROM patients";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $totalPatients = $row['totalPatients'];

    // Fetch total appointments
    $query = "SELECT COUNT(*) AS appointments FROM appointments";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $appointments = $row['appointments'];

    // Fetch pending requests (appointments)
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
    $query = "SELECT COUNT(*) AS cancelledAppointments 
              FROM appointments
              WHERE status = 'cancelled'";
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $cancelledAppointments = $row['cancelledAppointments'];

    // Fetch appointments for FullCalendar
    // 'appointments' references 'users' for patient_id
    // Adjust column names if yours differ
    $calendarEvents = [];
    $queryAppointments = "
        SELECT a.appointment_id, a.appointment_date, a.appointment_time, 
               a.status, u.first_name, u.last_name
        FROM appointments AS a
        JOIN users AS u ON a.patient_id = u.id
    ";
    $resultEvents = mysqli_query($conn, $queryAppointments);
    if ($resultEvents && mysqli_num_rows($resultEvents) > 0) {
        while ($rowEvent = mysqli_fetch_assoc($resultEvents)) {
            $title = 'Appointment: ' . $rowEvent['first_name'] . ' ' . $rowEvent['last_name'];
            // Combine date/time for FullCalendar's "start"
            $start = $rowEvent['appointment_date'] . 'T' . $rowEvent['appointment_time'];

            // You can also color-code by status if desired, e.g.:
            // $color = $rowEvent['status'] === 'cancelled' ? '#ff5c5c' : '#257e4a';
    
            $calendarEvents[] = [
                'id' => $rowEvent['appointment_id'],
                'title' => $title,
                'start' => $start,
                // 'color' => $color
            ];
        }
    }

    mysqli_close($conn);
    ?>
    <div class="main-content">

<div class="dashboard"></div>

    <div class="total-patient">
        <div class="info">
            <p>Total Patients</p>
            <h3>143</h3> 
        </div>
        <img src="../icons/image 71.png" alt="Patients Icon">
    </div>

    <div class="pending-request">
        <div class="info">
            <p>Pending Requests</p>
            <h3>100</h3> 
        </div>
        <img src="../icons/clock.png" alt="Patients Icon">
    </div>

    <div class="new-patients">
        <div class="info">
            <p>New Patients</p>
            <h3>10</h3> 
        </div>
        <img src="../images/newpatients.png" alt="Patients Icon">
    </div>
</div>

</div>
    <!-- <div class="content">
        
        <div class="dashboard-cards-container">
            <div class="dashboard-cards">
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo $totalPatients; ?></h3>
                        <img class="icon" src="../icons/image 71.png" alt="icon">
                    </div>
                    <p>Total Patients</p>
                    <a href="#" class="more-info">More Info</a>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo $appointments; ?></h3>
                        <img class="icon" src="../icons/image 74.png" alt="icon">
                    </div>
                    <p>Appointments</p>
                    <a href="#" class="more-info">More Info</a>
                </div>
                <div class="card">
                    <div class="card-header">
                        <h3><?php echo $pendingRequests; ?></h3>
                        <img class="icon" src="../icons/image 77.png" alt="icon">
                    </div>
                    <p>Pending Request</p>
                    <a href="#" class="more-info">More Info</a>
                </div>

            </div>
        </div>

        
        <div id="calendar"></div>
    </div> -->

    <!-- FullCalendar JS -->
    <!-- <script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.4/index.global.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var calendarEl = document.getElementById('calendar');

            // Pull in PHP array of events
            var eventsData = <?php echo json_encode($calendarEvents); ?>;

            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                events: eventsData
            });

            calendar.render();
        });
    </script> -->
</body>

</html>