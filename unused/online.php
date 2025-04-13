<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="online.css">
    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Online Request</title>
</head>
<body>
<?php include '../sidebar/sidebar.php'; ?>
 <div class="content">

            <div class="text">
            <h1>Online Request</h1>
            </div>
            <!-- First Container -->
            <div class="header-container">
                <div class="text-header">
                    <h1>Online Request Appointment List</h1>
                </div>
            </div>

            <!-- Second Container -->
            <div class="container">
                
                <div class="export-buttons">
                    <button class="export">Excel</button>
                    <button class="export">PDF</button>
                    <input type="text" class="head" placeholder="Search Patient Name..."> 
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Date Submitted</th>
                            <th>Appointment Date</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
    <tr>
        <td>Christian Quimba</td>
        <td>February 16, 2025</td>
        <td>February 19, 2025</td>
        <td>12:00pm</td>
        <td>4:00pm</td>
        <td><span class="status confirmed">Confirmed</span></td>
        <td>
            <div class="action-buttons">
                <button class="view"><i class="fas fa-eye"></i></button>
                <button class="edit"><i class="fas fa-edit"></i></button>
                <button class="delete"><i class="fas fa-trash-alt"></i></button>
            </div>
        </td>
    </tr>
    <tr>
        <td>Marwina Samson</td>
        <td>February 16, 2025</td>
        <td>February 19, 2025</td>
        <td>12:00pm</td>
        <td>4:00pm</td>
        <td><span class="status treated">Treated</span></td>
        <td>
            <div class="action-buttons">
                <button class="view"><i class="fas fa-eye"></i></button>
                <button class="edit"><i class="fas fa-edit"></i></button>
                <button class="delete"><i class="fas fa-trash-alt"></i></button>
            </div>
        </td>
    </tr>
    <tr>
        <td>Marwina Samson</td>
        <td>February 16, 2025</td>
        <td>February 19, 2025</td>
        <td>12:00pm</td>
        <td>4:00pm</td>
        <td><span class="status canceled">Canceled</span></td>
        <td>
            <div class="action-buttons">
                <button class="view"><i class="fas fa-eye"></i></button>
                <button class="edit"><i class="fas fa-edit"></i></button>
                <button class="delete"><i class="fas fa-trash-alt"></i></button>
            </div>
        </td>
    </tr>
    <tr>
        <td>Marwina Samson</td>
        <td>February 16, 2025</td>
        <td>February 19, 2025</td>
        <td>12:00pm</td>
        <td>4:00pm</td>
        <td><span class="status pending">Pending</span></td>
        <td>
            <div class="action-buttons">
                <button class="view"><i class="fas fa-eye"></i></button>
                <button class="edit"><i class="fas fa-edit"></i></button>
                <button class="delete"><i class="fas fa-trash-alt"></i></button>
            </div>
        </td>
    </tr>
</tbody>

                </table>
            </div>
    </div>

</body>

</html>