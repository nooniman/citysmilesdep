<?php
session_start();
include '../../database.php';

// Get patient ID from URL parameters or session
$patientId = $_GET['patient_id'] ?? $_SESSION['current_patient_id'] ?? null;
$appointmentId = $_GET['appointment_id'] ?? $_SESSION['current_appointment_id'] ?? null;

// Default patient name
$patientName = "[No patient selected]";

// Fetch patient details if ID is available
if ($patientId) {
    // Direct fetch from patients table if patient_id is available
    $stmt = $conn->prepare("SELECT CONCAT(first_name, ' ', last_name) as full_name FROM patients WHERE patient_id = ?");
    $stmt->bind_param("i", $patientId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $patientName = $row['full_name'];
    }
    $stmt->close();
} 
// If we have appointment ID but no patient ID, try to get patient from appointment
else if ($appointmentId) {
    $stmt = $conn->prepare("
        SELECT CONCAT(p.first_name, ' ', p.last_name) as full_name 
        FROM appointments a 
        JOIN patients p ON a.patient_id = p.patient_info_id 
        WHERE a.appointment_id = ?
    ");
    $stmt->bind_param("i", $appointmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        $patientName = $row['full_name'];
        // Also update the patientId for use in the rest of the script
        $stmtPatient = $conn->prepare("SELECT patient_id FROM appointments WHERE appointment_id = ?");
        $stmtPatient->bind_param("i", $appointmentId);
        $stmtPatient->execute();
        $patientResult = $stmtPatient->get_result();
        if ($patientRow = $patientResult->fetch_assoc()) {
            $patientId = $patientRow['patient_id'];
        }
        $stmtPatient->close();
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Orthodontist Dental Chart System">
    <title>City Smiles - Orthodontist Chart</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="chart.css">
    <script src="https://html2canvas.hertzen.com/dist/html2canvas.min.js"></script>
</head>
<body>
    <div class="header">
        <h1>Orthodontist Treatment Chart</h1>
        <div class="patient-info">
            <span id="patientName">Patient: <?php echo htmlspecialchars($patientName); ?></span>
            
            
        </div>
    </div>
    
    <div class="toolbar">
    <button id="backToTreatment" class="btn btn-secondary" onclick="window.location.href='../treatment.php'">
        <i class="fas fa-arrow-left"></i> Back
    </button>
    <button id="submitChart" class="btn btn-primary">
        <i class="fas fa-check-circle"></i> Submit Chart
    </button>       
    <button id="clearCanvas" class="btn btn-warning">
        <i class="fas fa-eraser"></i> Clear Drawing
    </button>
    <button id="print" class="btn btn-info">
        <i class="fas fa-print"></i> Print
    </button>

    <span class="teeth-type-spacer"></span>
    <span id="childTeeth" class="teeth-type-label" style="display:none;"><i class="fas fa-baby"></i> Child Teeth</span>
    <span id="adultTeeth" class="teeth-type-label" style="display:none;"><i class="fas fa-user"></i> Adult Teeth</span>
</div>
    <div class="chart-container">
        <div class="chart" id="chart">
            <div class="label" id="upperLabel">Upper Jaw</div>
            <div class="upper" id="upper"></div>
            <div class="label" id="lowerLabel">Lower Jaw</div>
            <div class="lower" id="lower"></div>
            
        </div>
    </div>
    
    <script src="chart.js"></script>
    <script>
        // Add print functionality
        document.getElementById('print').addEventListener('click', function() {
            window.print();
        });
    </script>
</body>
</html>