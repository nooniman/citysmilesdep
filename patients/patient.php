<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\patients\patient.php
session_start();

// Check for proper role access
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'staff', 'dentist', 'intern', 'assistant'])) {
    header('Location: ../login.php');
    exit();
}

include '../database.php';

// Add the new code here - Replace everything from line 11 to 39
// Check if we should show deleted records (admin only)
$showDeleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] == '1' && $_SESSION['role'] == 'admin';

// Get patient statistics
$stats = [
    'total' => 0,
    'monthly' => 0,
    'with_appts' => 0
];

// Total patients - only count non-deleted by default
$q1 = "SELECT COUNT(*) as total FROM patients WHERE " . ($showDeleted ? "is_deleted = 1" : "is_deleted = 0 OR is_deleted IS NULL");
$r1 = $conn->query($q1);
if ($r1 && $r1->num_rows > 0) {
    $row = $r1->fetch_assoc();
    $stats['total'] = (int) $row['total'];
}

// Patients created this month - only count non-deleted by default
$q2 = "SELECT COUNT(*) as monthly FROM patients 
       WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) 
       AND YEAR(created_at) = YEAR(CURRENT_DATE())
       AND " . ($showDeleted ? "is_deleted = 1" : "is_deleted = 0 OR is_deleted IS NULL");
$r2 = $conn->query($q2);
if ($r2 && $r2->num_rows > 0) {
    $row = $r2->fetch_assoc();
    $stats['monthly'] = (int) $row['monthly'];
}

// Patients with appointments - only count non-deleted by default
$q3 = "SELECT COUNT(DISTINCT p.patient_info_id) as with_appts 
       FROM patients p
       JOIN appointments a ON p.patient_info_id = a.patient_id
       WHERE " . ($showDeleted ? "p.is_deleted = 1" : "p.is_deleted = 0 OR p.is_deleted IS NULL");
$r3 = $conn->query($q3);
if ($r3 && $r3->num_rows > 0) {
    $row = $r3->fetch_assoc();
    $stats['with_appts'] = (int) $row['with_appts'];
}

// Get patients based on deletion status
$patients = [];
$query = "SELECT 
            patient_info_id AS id,
            first_name, 
            middle_name,
            last_name, 
            gender, 
            contact_number, 
            email, 
            birth_date,
            created_at";

// Add deleted_at field if showing deleted records
if ($showDeleted) {
    $query .= ", deleted_at";
}

$query .= " FROM patients 
         WHERE " . ($showDeleted ? "is_deleted = 1" : "is_deleted = 0 OR is_deleted IS NULL") . "
         ORDER BY " . ($showDeleted ? "deleted_at DESC" : "created_at DESC");

$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $patients[] = $row;
    }
}

// Handle new patient submission
if (isset($_POST['submit_patient'])) {
    $fname = trim($_POST['fname'] ?? '');
    $lname = trim($_POST['lname'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $contact = trim($_POST['contact'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if ($fname && $lname && $gender && $contact && $email) {
        $stmt = $conn->prepare("INSERT INTO patients (first_name, last_name, gender, contact_number, email, created_at) 
                               VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param('sssss', $fname, $lname, $gender, $contact, $email);

        if ($stmt->execute()) {
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// Handle X-ray image upload
if (isset($_POST['upload_xray_image'])) {
    $patientInfoId = intval($_POST['patient_info_id']);
    $xrayImage = $_FILES['xray_image'] ?? null;

    if ($xrayImage && $xrayImage['error'] === UPLOAD_ERR_OK) {
        $imageData = file_get_contents($xrayImage['tmp_name']);
        $query = "UPDATE patient_history SET xray_image = ? WHERE patient_info_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("bi", $imageData, $patientInfoId);

        if ($stmt->execute()) {
            header('Location: ' . $_SERVER['PHP_SELF']);
            exit;
        }
    }
}

// Fetch patient history dynamically
function getPatientHistory($conn, $patientInfoId)
{
    $query = "SELECT previous_dentist, last_dental_visit, intraoral_exam_image 
              FROM patient_history 
              WHERE patient_info_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $patientInfoId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}

// Fetch patient health declaration dynamically
function getPatientHealthDeclaration($conn, $patientInfoId)
{
    $query = "SELECT * FROM patient_health_declaration WHERE patient_info_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $patientInfoId);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Management | City Smiles Dental</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="patient.css">
    <style>
        .menu-content {
            display: none;
            position: absolute;
            background: white;
            border: 1px solid #ccc;
            box-shadow: 2px 2px 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            min-width: 150px;
            border-radius: 6px;
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


        /* Modal Styles */
        .modal {
            display: block;
            /* Set to block for demo purposes */
            position: fixed;
            z-index: 1050;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 5% auto;
            border-radius: 10px;
            width: 800px;
            max-width: 90%;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            animation: modalSlideIn 0.3s;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-header {
            background: linear-gradient(135deg, #9d7ded, #6ace70);
            color: white;
            padding: 15px 20px;
            border-radius: 10px 10px 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .close-btn {
            color: white;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }

        .modal-body {
            padding: 20px;
        }

        /* Animation */
        @keyframes modalSlideIn {
            from {
                transform: translateY(-50px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        /* Tab Styles */
        .tab-container {
            border-bottom: 1px solid #eee;
            display: flex;
            overflow-x: auto;
            margin-bottom: 20px;
            padding: 30px 0;
        }

        .tab-button {
            padding: 12px 20px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #777;
            position: relative;
            transition: all 0.3s;
        }

        .tab-button:hover {
            color: #333;
        }

        .tab-button.active {
            color: #6ace70;
            font-weight: 600;
        }

        .tab-button.active::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100%;
            height: 3px;
            background: linear-gradient(135deg, #9d7ded, #6ace70);
            border-radius: 3px 3px 0 0;
        }

        .tab-content {
            display: none;
            padding: 10px 0;
        }

        .tab-content.active {
            display: block;
        }

        /* Patient Info Styles */
        .patient-info-card {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .patient-info-row {
            display: flex;
            flex-wrap: wrap;
            margin-bottom: 15px;
        }

        .patient-info-col {
            flex: 1;
            min-width: 200px;
            margin-bottom: 10px;
        }

        .info-label {
            font-size: 12px;
            color: #777;
            margin-bottom: 5px;
        }

        .info-value {
            font-weight: 500;
            color: #333;
        }

        /* Chart and Image Styles */
        .chart-container {
            background: white;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .chart-heading {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
        }

        .health-declaration-item {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }

        .health-declaration-item:last-child {
            border-bottom: none;
        }

        /* Button Styles */
        .action-button {
            background: linear-gradient(135deg, #9d7ded, #6ace70);
            color: white;
            border: none;
            padding: 10px 18px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 20px;
        }

        .action-button:hover {
            background: linear-gradient(135deg, #8a65e0, #57bb5e);
            transform: translateY(-2px);
            transition: all 0.2s;
        }

        .chart-image {
            max-width: 100%;
            border-radius: 8px;
        }
    </style>
</head>

<body>
    <?php include '../sidebar/sidebar.php'; ?>

    <!-- Main Content Area -->
    <div class="main-content">
        <div class="header-container">
            <div id="patient-text">
                <h1>Patient Management</h1>
                <p>View and manage patient information</p>
            </div>
            <div class="add_patient-container">
                <button class="add_patient-button" onclick="openModal()">
                    <i class="fas fa-plus"></i> Add New Patient
                </button>
            </div>
        </div>

        <!-- Stats Section -->
        <div class="stats-container">
            <div class="stat-card">
                <div class="stat-icon" style="background: linear-gradient(135deg, #9d7ded, #e0d4f9);">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Patients</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #fff3cd; color: #856404;">
                    <i class="fas fa-user-plus"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['monthly']; ?></div>
                    <div class="stat-label">New This Month</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="background: #e0f7fa; color: #0097a7;">
                    <i class="fas fa-calendar-check"></i>
                </div>
                <div class="stat-info">
                    <div class="stat-value"><?php echo $stats['with_appts']; ?></div>
                    <div class="stat-label">With Appointments</div>
                </div>
            </div>
        </div>

        <!-- Patients Table Section -->
        <div class="patient-section">
            <div class="search-container">
                <input type="text" id="searchPatient" placeholder="Search patients..." onkeyup="searchTable()">
            </div>

            <?php if ($_SESSION['role'] == 'admin'): ?>
                <div class="deleted-toggle">
                    <label for="showDeletedToggle"
                        style="display: inline-flex; align-items: center; cursor: pointer; margin-bottom: 10px;">
                        <span style="margin-right: 10px;">Show deleted patients</span>
                        <div class="toggle-switch">
                            <input type="checkbox" id="showDeletedToggle" <?php echo isset($_GET['show_deleted']) && $_GET['show_deleted'] == '1' ? 'checked' : ''; ?>>
                            <span class="toggle-slider"></span>
                        </div>
                    </label>
                </div>

                <script>
                    document.getElementById('showDeletedToggle').addEventListener('change', function () {
                        const url = new URL(window.location.href);
                        if (this.checked) {
                            url.searchParams.set('show_deleted', '1');
                        } else {
                            url.searchParams.delete('show_deleted');
                        }
                        window.location.href = url.toString();
                    });
                </script>
            <?php endif; ?>

            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Gender</th>
                            <th>Contact</th>

                            <th>Registration Date</th>
                        </tr>
                    </thead>
                    <tbody id="patientTableBody">
                        <?php if (count($patients) > 0): ?>
                            <?php foreach ($patients as $patient):
                                // Get patient initials
                                $initials = strtoupper(substr($patient['first_name'], 0, 1) . substr($patient['last_name'], 0, 1));
                                ?>
                                <tr onclick="toggleMenu(event, this)"
                                    class="<?= isset($patient['deleted_at']) ? 'deleted-record' : '' ?>"
                                    data-id="P-<?php echo str_pad($patient['id'], 5, '0', STR_PAD_LEFT); ?>"
                                    data-name="<?php echo htmlspecialchars($patient['first_name']); ?>"
                                    data-middle-name="<?php echo htmlspecialchars($patient['middle_name']); ?>"
                                    data-last-name="<?php echo htmlspecialchars($patient['last_name']); ?>"
                                    data-birth-date="<?php echo htmlspecialchars($patient['birth_date']); ?>"
                                    data-gender="<?php echo htmlspecialchars($patient['gender']); ?>"
                                    data-contact="<?php echo htmlspecialchars($patient['contact_number']); ?>"
                                    data-email="<?php echo htmlspecialchars($patient['email']); ?>"
                                    data-created="<?php echo date('M d, Y', strtotime($patient['created_at'])); ?>">

                                    <td>P-<?php echo str_pad($patient['id'], 5, '0', STR_PAD_LEFT); ?></td>
                                    <td>
                                        <div class="patient-info">
                                            <div class="patient-avatar"><?= $initials ?></div>
                                            <div>
                                                <div class="patient-name">
                                                    <?php echo htmlspecialchars($patient['first_name'] . ' ' . $patient['last_name']); ?>
                                                </div>
                                                <div class="patient-email"
                                                    title="<?php echo htmlspecialchars($patient['email']); ?>">
                                                    <?php echo htmlspecialchars($patient['email']); ?>
                                                </div>
                                                <?php if (isset($patient['deleted_at'])): ?>
                                                    <div class="deletion-info">Deleted:
                                                        <?php echo date('M d, Y', strtotime($patient['deleted_at'])); ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($patient['gender']); ?></td>
                                    <td><?php echo htmlspecialchars($patient['contact_number']); ?></td>

                                    <td>
                                        <div class="datetime-info">
                                            <div class="date-display">
                                                <i class="far fa-calendar-alt"></i>
                                                <?php echo date('M d, Y', strtotime($patient['created_at'])); ?>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="no-data">No patients found.</td> <!-- Changed from 6 to 5 -->
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Context Menu -->
    <div id="contextMenu" class="menu-content">
        <ul>
            <li id="showModalBtn" onclick="patientHistory()">Patient Details</li>
            <li onclick="removePatientFromList()">Remove</li>
        </ul>
    </div>

    <!-- Patient Details Modal -->
    <div id="patientDetailsModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-circle"></i> Patient Details - <span id="patientNameHeader">Loading...</span>
                </h2>
                <span class="close-btn">&times;</span>
            </div>

            <!-- Patient Quick Info Card -->
            <div class="patient-info-card">
                <div class="patient-info-row">
                    <div class="patient-info-col">
                        <div class="info-label">Patient ID</div>
                        <div class="info-value" id="patientId">Loading...</div>
                    </div>
                    <div class="patient-info-col">
                        <div class="info-label">Name</div>
                        <div class="info-value" id="patientName">Loading...</div>
                    </div>
                    <div class="patient-info-col">
                        <div class="info-label">Gender</div>
                        <div class="info-value" id="patientGender">Loading...</div>
                    </div>
                    <div class="patient-info-col">
                        <div class="info-label">Age</div>
                        <div class="info-value" id="patientAge">Loading...</div>
                    </div>
                </div>
                <div class="patient-info-row">
                    <div class="patient-info-col">
                        <div class="info-label">Contact</div>
                        <div class="info-value" id="patientContact">Loading...</div>
                    </div>
                    <div class="patient-info-col">
                        <div class="info-label">Email</div>
                        <div class="info-value" id="patientEmail">Loading...</div>
                    </div>
                    <div class="patient-info-col">
                        <div class="info-label">Registration Date</div>
                        <div class="info-value" id="patientRegDate">Loading...</div>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="tab-container">
                <button class="tab-button active" data-tab="patient-history">
                    <i class="fas fa-history"></i> Patient History
                </button>
                <button class="tab-button" data-tab="xray-chart">
                    <i class="fas fa-x-ray"></i> X-Ray Chart
                </button>
                <button class="tab-button" data-tab="intraoral-chart">
                    <i class="fas fa-tooth"></i> Intraoral Chart
                </button>
                <button class="tab-button" data-tab="health-declaration">
                    <i class="fas fa-notes-medical"></i> Health Declaration
                </button>
            </div>

            <div class="modal-body">
                <!-- Patient History Tab Content -->
                <div id="patient-history" class="tab-content active">
                    <h3 class="chart-heading">Dental History</h3>
                    <div class="patient-info-row">
                        <div class="patient-info-col">
                            <div class="info-label">Last Dental Visit</div>
                            <div class="info-value" id="lastDentalVisit">Loading...</div>
                        </div>
                        <div class="patient-info-col">
                            <div class="info-label">Previous Dentist</div>
                            <div class="info-value" id="previousDentist">Loading...</div>
                        </div>
                    </div>
                    <div class="patient-info-row">
                        <div class="patient-info-col">
                            <div class="info-label">Chief Complaint</div>
                            <div class="info-value" id="chiefComplaint">Loading...</div>
                        </div>
                    </div>
                </div>

                <!-- X-Ray Chart Tab Content -->
                <div id="xray-chart" class="tab-content">
                    <h3 class="chart-heading">X-Ray Images</h3>
                    <button class="action-button" onclick="openXrayModal(selectedPatientId)">
                        <i class="fas fa-upload"></i> Upload X-Ray
                    </button>
                    <div class="chart-container" id="xrayImages">
                        <!-- Dynamic X-ray content will be inserted here -->
                    </div>
                </div>

                <!-- Intraoral Chart Tab Content -->
                <div id="intraoral-chart" class="tab-content">
                    <h3 class="chart-heading">Intraoral Examination</h3>
                    <div class="chart-container" id="intraoralImages">
                        <!-- Dynamic intraoral content will be inserted here -->
                    </div>
                </div>

                <!-- Health Declaration Tab Content -->
                <div id="health-declaration" class="tab-content">
                    <h3 class="chart-heading">Patient Health Information</h3>

                    <!-- Vital Statistics -->
                    <div class="chart-container">
                        <h4 style="margin-bottom: 15px; font-size: 16px;">Vital Statistics</h4>
                        <div class="patient-info-row">
                            <div class="patient-info-col">
                                <div class="info-label">Height</div>
                                <div class="info-value" id="height">Loading...</div>
                            </div>
                            <div class="patient-info-col">
                                <div class="info-label">Weight</div>
                                <div class="info-value" id="weight">Loading...</div>
                            </div>
                            <div class="patient-info-col">
                                <div class="info-label">Blood Type</div>
                                <div class="info-value" id="bloodType">Loading...</div>
                            </div>
                            <div class="patient-info-col">
                                <div class="info-label">Blood Pressure</div>
                                <div class="info-value" id="bloodPressure">Loading...</div>
                            </div>
                        </div>
                    </div>

                    <!-- Health Declaration Content -->
                    <div id="healthDeclarationContent">
                        <!-- Dynamic health declaration content will be inserted here -->
                    </div>

                </div>
            </div>
        </div>
    </div>

    <!-- X-Ray Upload Modal -->
    <div id="xrayUploadModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Upload X-Ray Image</h2>
                <span class="close-btn" onclick="closeXrayUploadModal()">&times;</span>
            </div>
            <form method="post" enctype="multipart/form-data">
                <input type="hidden" name="patient_info_id" id="xrayPatientInfoId">
                <div class="form-group">
                    <label for="xray_image">Select X-Ray Image</label>
                    <input type="file" name="xray_image" id="xray_image" accept="image/*" required>
                </div>
                <div class="form-actions">
                    <button type="button" onclick="closeXrayUploadModal()" class="cancel-btn">Cancel</button>
                    <button type="submit" name="upload_xray_image" class="submit-btn">Upload</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Add Patient Modal -->
    <div id="patientModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2>Add New Patient</h2>
                <span class="close-btn" onclick="closeModal()">&times;</span>
            </div>
            <form id="patientForm" method="post">
                <div class="form-group">
                    <label for="fname">First Name</label>
                    <input type="text" id="fname" name="fname" required>
                </div>
                <div class="form-group">
                    <label for="lname">Last Name</label>
                    <input type="text" id="lname" name="lname" required>
                </div>
                <div class="form-group">
                    <label for="gender">Gender</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="contact">Contact Number</label>
                    <input type="number" id="contact" name="contact" required>
                </div>
                <div class="form-group">
                    <label for="email">Email Address</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-actions">
                    <button type="button" onclick="closeModal()" class="cancel-btn">Cancel</button>
                    <button type="submit" name="submit_patient" value="1" class="submit-btn">Save Patient</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Custom Delete Confirmation Modal -->
    <div class="modal fade" id="delete-confirm-modal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel"
        aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">
                        <i class="fas fa-exclamation-triangle"></i>
                        Confirm Patient Removal
                    </h5>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to remove this patient? The record will remain in the database for 5 years
                        before permanent deletion.</p>
                    <p>This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirm-delete">
                        <i class="fas fa-trash me-1"></i>
                        Remove Patient
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById("patientModal").style.display = "block";
        }

        function closeModal() {
            document.getElementById("patientModal").style.display = "none";
        }

        function openXrayUploadModal() {
            document.getElementById("xrayUploadModal").style.display = "block";
        }

        function closeXrayUploadModal() {
            document.getElementById("xrayUploadModal").style.display = "none";
        }

        window.onclick = function (event) {
            const modal = document.getElementById("patientModal");
            if (event.target === modal) {
                modal.style.display = "none";
            }
        }

        function searchTable() {
            let input = document.getElementById("searchPatient");
            let filter = input.value.toUpperCase();
            let tbody = document.getElementById("patientTableBody");
            let tr = tbody.getElementsByTagName("tr");

            for (let i = 0; i < tr.length; i++) {
                let nameCell = tr[i].getElementsByTagName("td")[1];
                if (nameCell) {
                    let textValue = nameCell.textContent || nameCell.innerText;
                    tr[i].style.display = textValue.toUpperCase().indexOf(filter) > -1 ? "" : "none";
                }
            }
        }

        let selectedPatientId = null;
        let selectedRow = null;

        function toggleMenu(event, row) {
            event.stopPropagation();

            const menu = document.getElementById("contextMenu");

            // Get patient ID from the row's first <td>
            selectedPatientId = row.cells[0].textContent.trim(); // e.g., P-00001
            selectedRow = row;

            const rect = row.getBoundingClientRect();
            const rowCenter = rect.left + (rect.width / 2);
            const menuWidth = 150;

            const top = rect.top + window.scrollY + rect.height + 5;
            const left = rowCenter - (menuWidth / 2);

            menu.style.display = "block";
            menu.style.top = `${top}px`;
            menu.style.left = `${left}px`;

            selectedRow.dataset.middleName = row.getAttribute('data-middle-name');
            selectedRow.dataset.lastName = row.getAttribute('data-last-name');
            selectedRow.dataset.birthDate = row.getAttribute('data-birth-date');
        }

        document.addEventListener("click", () => {
            document.getElementById("contextMenu").style.display = "none";
        });

        // to close menu when magcscroll ka outside menu
        document.addEventListener("scroll", () => {
            document.getElementById("contextMenu").style.display = "none";
        }, true);

        function removePatientFromList() {
            if (!selectedPatientId) return;

            // Show the confirmation modal instead of browser confirm
            const deleteModal = new bootstrap.Modal(document.getElementById('delete-confirm-modal'));
            deleteModal.show();

            // Set up the confirmation button's one-time click handler
            $("#confirm-delete").one("click", function () {
                deleteModal.hide();

                fetch('soft_delete_patient.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'id=' + encodeURIComponent(selectedPatientId)
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove from UI with animation
                            $(selectedRow).fadeOut(400, function () {
                                $(this).hide();
                            });

                            // Show toast notification
                            showToast("Success", "Patient removed successfully", "success");

                            selectedPatientId = null;
                            selectedRow = null;
                        } else {
                            showToast("Error", data.message || "An error occurred", "error");
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        showToast("Error", "Something went wrong. Please try again.", "error");
                    });
            });
        }


        // Toast notification function
        function showToast(title, message, type) {
            // Create toast container if it doesn't exist
            if (!document.querySelector('.toast-container')) {
                const toastContainer = document.createElement('div');
                toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
                toastContainer.style.zIndex = '1070';
                document.body.appendChild(toastContainer);
            }

            // Create a unique ID for this toast
            const toastId = 'toast-' + Date.now();

            // Set icon based on type
            let icon = '';
            let bgClass = '';

            switch (type) {
                case 'success':
                    icon = '<i class="fas fa-check-circle me-2"></i>';
                    bgClass = 'bg-success';
                    break;
                case 'error':
                    icon = '<i class="fas fa-exclamation-circle me-2"></i>';
                    bgClass = 'bg-danger';
                    break;
                case 'warning':
                    icon = '<i class="fas fa-exclamation-triangle me-2"></i>';
                    bgClass = 'bg-warning';
                    break;
                default:
                    icon = '<i class="fas fa-info-circle me-2"></i>';
                    bgClass = 'bg-info';
            }

            // Create toast HTML
            const toastHtml = `
        <div id="${toastId}" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header ${bgClass} text-white">
                ${icon}
                <strong class="me-auto">${title}</strong>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body">
                ${message}
            </div>
        </div>
    `;

            // Add toast to container
            document.querySelector('.toast-container').innerHTML += toastHtml;

            // Initialize and show the toast
            const toastElement = document.getElementById(toastId);
            const toast = new bootstrap.Toast(toastElement, { delay: 3000 });
            toast.show();
        }

        // Demo functionality for tabs
        document.addEventListener('DOMContentLoaded', function () {
            // Tab switching functionality
            const tabButtons = document.querySelectorAll('.tab-button');
            const tabContents = document.querySelectorAll('.tab-content');

            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    // Remove active class from all buttons and contents
                    tabButtons.forEach(btn => btn.classList.remove('active'));
                    tabContents.forEach(content => content.classList.remove('active'));

                    // Add active class to current button
                    button.classList.add('active');

                    // Show corresponding content
                    const tabId = button.dataset.tab;
                    document.getElementById(tabId).classList.add('active');
                });
            });

            // Modal functionality
            const modal = document.getElementById('patientDetailsModal');
            const showModalBtn = document.getElementById('showModalBtn');
            const closeBtn = document.querySelector('.close-btn');

            showModalBtn.addEventListener('click', () => {
                modal.style.display = 'block';
            });

            closeBtn.addEventListener('click', () => {
                modal.style.display = 'none';
            });

            window.addEventListener('click', (event) => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });

        // Update modal header and patient-info-card with correct details
        function patientHistory() {
            const modal = document.getElementById('patientDetailsModal');
            const patientId = selectedRow.dataset.id.replace('P-', ''); // Extract numeric ID
            const patientFirstName = selectedRow.dataset.name;
            const patientMiddleName = selectedRow.dataset.middleName || ''; // Middle name
            const patientLastName = selectedRow.dataset.lastName;
            const patientBirthDate = selectedRow.dataset.birthDate;
            const patientGender = selectedRow.dataset.gender;
            const patientContact = selectedRow.dataset.contact;
            const patientEmail = selectedRow.dataset.email;
            const patientRegDate = selectedRow.dataset.created;

            // Combine full name for content
            const fullName = `${patientFirstName} ${patientMiddleName} ${patientLastName}`.trim();

            // Combine name for header (first name + last name only)
            const headerName = `${patientFirstName} ${patientLastName}`.trim();

            // Calculate age from birthdate
            const birthDate = new Date(patientBirthDate);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const monthDiff = today.getMonth() - birthDate.getMonth();
            if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }

            // Update modal header
            document.getElementById('patientNameHeader').textContent = headerName;

            // Update patient-info-card
            document.getElementById('patientId').textContent = `P-${patientId}`;
            document.getElementById('patientName').textContent = fullName;
            document.getElementById('patientGender').textContent = patientGender;
            document.getElementById('patientAge').textContent = age;
            document.getElementById('patientContact').textContent = patientContact;
            document.getElementById('patientEmail').textContent = patientEmail;
            document.getElementById('patientRegDate').textContent = patientRegDate;

            // Fetch patient history dynamically
            fetch(`get_patient_history.php?patient_info_id=${patientId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('lastDentalVisit').textContent = data.last_dental_visit || 'No record';
                    document.getElementById('previousDentist').textContent = data.previous_dentist || 'No record';
                    document.getElementById('chiefComplaint').textContent = data.chief_complaint || 'No record';

                    // Dynamically fetch and display intraoral exam image
                    const intraoralImagesContainer = document.getElementById('intraoralImages');
                    intraoralImagesContainer.innerHTML = ''; // Clear previous content
                    if (data.intraoral_exam_image && data.intraoral_exam_image.startsWith('data:image')) {
                        const img = document.createElement('img');
                        img.src = data.intraoral_exam_image; // Use the base64-encoded image data directly
                        img.alt = 'Intraoral Exam Image';
                        img.style.maxWidth = '100%';
                        img.style.borderRadius = '8px';
                        intraoralImagesContainer.appendChild(img);
                    } else {
                        intraoralImagesContainer.textContent = 'No intraoral exam image available.';
                    }
                })
                .catch(error => {
                    console.error('Error fetching patient history:', error);
                    document.getElementById('lastDentalVisit').textContent = 'Error loading';
                    document.getElementById('previousDentist').textContent = 'Error loading';
                    document.getElementById('chiefComplaint').textContent = 'Error loading';

                    const intraoralImagesContainer = document.getElementById('intraoralImages');
                    intraoralImagesContainer.textContent = 'Error loading intraoral exam image.';
                });

            // Fetch patient health declaration dynamically
            fetch(`get_patient_health_declaration.php?patient_info_id=${patientId}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('height').textContent = data.height || 'No record';
                    document.getElementById('weight').textContent = data.weight || 'No record';
                    document.getElementById('bloodType').textContent = data.blood_type || 'No record';
                    document.getElementById('bloodPressure').textContent = data.blood_pressure || 'No record';

                    const healthDeclarationContent = document.getElementById('healthDeclarationContent');
                    healthDeclarationContent.innerHTML = `
                        <div class="health-declaration-item"><strong>Good Health:</strong> ${data.good_health ? 'Yes' : 'No'}</div>
                        <div class="health-declaration-item"><strong>Under Medical Treatment:</strong> ${data.under_medical_treatment ? 'Yes' : 'No'}</div>
                        <div class="health-declaration-item"><strong>Medical Condition:</strong> ${data.medical_condition || 'None'}</div>
                        <div class="health-declaration-item"><strong>Hospital Admission:</strong> ${data.hospital_admission ? 'Yes' : 'No'}</div>
                        <div class="health-declaration-item"><strong>Hospital Admission Reason:</strong> ${data.hospital_admission_reason || 'None'}</div>
                        <div class="health-declaration-item"><strong>Surgical Operation:</strong> ${data.surgical_operation ? 'Yes' : 'No'}</div>
                        <div class="health-declaration-item"><strong>Surgical Operation Details:</strong> ${data.surgical_operation_details || 'None'}</div>
                        <div class="health-declaration-item"><strong>Taking Medications:</strong> ${data.taking_medications ? 'Yes' : 'No'}</div>
                        <div class="health-declaration-item"><strong>Medication Details:</strong> ${data.medication_details || 'None'}</div>
                        <div class="health-declaration-item"><strong>Allergies:</strong> 
                            Local Anesthetic: ${data.allergy_local_anesthetic ? 'Yes' : 'No'}, 
                            Sulfur: ${data.allergy_sulfur ? 'Yes' : 'No'}, 
                            Aspirin: ${data.allergy_aspirin ? 'Yes' : 'No'}, 
                            Latex: ${data.allergy_latex ? 'Yes' : 'No'}, 
                            Penicillin: ${data.allergy_penicillin ? 'Yes' : 'No'}, 
                            Other: ${data.allergy_other || 'None'}
                        </div>
                        <div class="health-declaration-item"><strong>Smoking:</strong> ${data.smoking ? 'Yes' : 'No'}</div>
                        <div class="health-declaration-item"><strong>Drugs:</strong> ${data.drugs ? 'Yes' : 'No'}</div>
                        <div class="health-declaration-item"><strong>Pregnant:</strong> ${data.pregnant ? 'Yes' : 'No'}</div>
                        <div class="health-declaration-item"><strong>Nursing:</strong> ${data.nursing ? 'Yes' : 'No'}</div>
                        <div class="health-declaration-item"><strong>Birth Control:</strong> ${data.birth_control ? 'Yes' : 'No'}</div>
                        <div class="health-declaration-item"><strong>Bleeding Time:</strong> ${data.bleeding_time || 'No record'}</div>
                    `;
                })
                .catch(error => {
                    console.error('Error fetching health declaration:', error);
                    document.getElementById('healthDeclarationContent').textContent = 'Error loading health declaration.';
                });

            modal.style.display = 'block';
        }

        // Open X-ray upload modal with patient ID
        function openXrayModal(patientId) {
            document.getElementById("xrayPatientInfoId").value = patientId;
            openXrayUploadModal();
        }
    </script>
</body>

</html>