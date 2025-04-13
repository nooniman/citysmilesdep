<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <link rel="stylesheet" href="patient.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <title>Patient Management</title>
    <style>
        .highlight {
            background-color: lightgreen !important;
        }
        .action-menu {
            position: relative;
            display: inline-block;
        }
        .menu-content {
            display: none;
            position: absolute;
            right: 0;
            background: white;
            border: 1px solid #ccc;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            min-width: 150px;
        }
        .menu-content ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        .menu-content ul li {
            padding: 10px;
            cursor: pointer;
        }
        .menu-content ul li:hover {
            background: #f0f0f0;
        }
        table tbody tr {
            cursor: pointer;
        }
    </style>
</head>
<body>
    <?php include '../sidebar/sidebar.php'; ?>
    <?php include 'add_modals.php'; ?>
    
    <div class="content">
        <div id="patient-text">
            <h1>Patient</h1>
        </div>
        
        <div class="header-container">
            <div class="text-patient">
                <h1>Patient List</h1>
            </div>
            <div class="addpatient-container">
                <button class="addpatient-button" onclick="addPatient()">Add Patient</button>
            </div>
        </div>
        <div class="container">
            <div class="export-buttons">
                <button class="export">Excel</button>
                <button class="export">PDF</button>
                <input type="text" class="head" placeholder="Search Patient Name...">
            </div>
            <table>
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>Patient Name</th>
                        <th>Gender</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody id="patientTable">
                    <tr onclick="toggleMenu(event, this)">
                        <td>
                            <div class='image-container'>
                                <img src='default.png' alt='Patient Photo'>
                            </div>
                        </td>
                        <td>John Doe</td>
                        <td>Male</td>
                        <td>123-456-7890</td>
                        <td>johndoe@example.com</td>
                        <td>To be Processed</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
    <div id="contextMenu" class="menu-content">
        <ul>
            <li onclick="patientAssessment()">Patient Assessment</li>
            <li onclick="patientHistory()">Patient History</li>
        </ul>
    </div>
    <script>
        function addPatient() {
            const patientTable = document.getElementById("patientTable");
            const newRow = document.createElement("tr");
            newRow.setAttribute("onclick", "toggleMenu(event, this)");
            newRow.innerHTML = `
                <td>
                    <div class='image-container'>
                        <img src='default.png' alt='Patient Photo'>
                    </div>
                </td>
                <td>Jane Doe</td>
                <td>Female</td>
                <td>987-654-3210</td>
                <td>janedoe@example.com</td>
                <td>New</td>
            `;
            patientTable.appendChild(newRow);
            newRow.classList.add("highlight");
            setTimeout(() => {
                newRow.classList.remove("highlight");
            }, 10000);
        }
        function toggleMenu(event, row) {
            event.stopPropagation();
            const menu = document.getElementById("contextMenu");
            menu.style.display = "block";
            menu.style.top = `${event.clientY}px`;
            menu.style.left = `${event.clientX}px`;
        }
        document.addEventListener("click", () => {
            document.getElementById("contextMenu").style.display = "none";
        });
        function patientAssessment() {
    window.location.href = "patientassessmentform.php";
}
        function patientHistory() {
            alert("Redirecting to Patient History");
        }
    </script>
</body>
</html>
