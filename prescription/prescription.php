<?php
session_start();

// Check admin access
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || 
    !in_array($_SESSION['role'], ['admin', 'staff', 'dentist', 'assistant', 'intern'])) {
    // Return unauthorized for AJAX requests
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <link rel="stylesheet" href="prescription.css">
    <title>Prescription</title>
</head>

<body>
    <?php include '../sidebar/sidebar.php';
    ?>


    <div class="content">
        <div class="prescription-text">
            <h1>Prescription</h1>
        </div>
        <!-- First Container -->
        <div class="header-container">
            <div class="text-prescription">
                <h1>Prescription List</h1>
            </div>
            <div class="addprescription-container">
                <a href="add_prescription.php"> <button class="addprescription-button">Add Prescription</button> </a>
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
                        <th>Date</th>
                        <th>Medicine</th>
                        <th>Notes</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Jones Van Francisco</td>
                        <td>Male</td>
                        <td>Muriatic Acid</td>
                        <td></td>
                        <td>
                            <button class="view"><i class="fas fa-eye"></i></button>
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