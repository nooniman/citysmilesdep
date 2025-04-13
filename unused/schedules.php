<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="schedule.css">
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule</title>
</head>
<body>
<?php include '../sidebar/sidebar.php'; ?>
<div class="content">

    <div class="schedule-text">
      <h1>Schedule</h1>
    </div>
    <!-- First Container -->
    <div class="header-container">
        <div class="text-schedule">
            <h1>Time Schedule List</h1>
        </div>
        <div class="addschedule-container">
            <button class="addschedule-button">Add Schedule</button>
        </div>
    </div>

    <!-- Second Container -->
    <div class="container">
        
        <div class="export-buttons">
            <button class="export">Excel</button>
            <button class="export">PDF</button>
            <input type="text" class="head" placeholder="Search..."> 
        </div>

        <table>
            <thead>
                <tr>
                    <th>Photo</th>
                    <th>Dentist</th>
                    <th>Day</th>
                    <th>Start Time</th>
                    <th>End Time</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <div class="image-container">
                            <img src="../images/girl.jpg" alt="Staff Photo">
                        </div>
                    </td>
                    <td>Marwinaaa</td>
                    <td>March 01, 2025-Tuesday</td>
                    <td>9:00am</td>
                    <td>11:00am</td>
                    <td>
                        <button class="edit"><i class="fas fa-edit"></i></button>
                        <button class="delete"><i class="fas fa-trash-alt"></i></button>
                    </td>
                </tr>
                
                <!-- Add more staff rows as needed -->
            </tbody>
        </table>
    </div>
</div>

</body>
</html>