<?php
include '../database.php'; // Use your existing MySQLi connection

header("Content-Type: application/json");

try {
    // Start transaction
    $conn->begin_transaction();

    // File upload function
    function uploadFile($fileKey, $target_dir = "uploads/")
    {
        if (!empty($_FILES[$fileKey]["name"])) {
            $image_name = basename($_FILES[$fileKey]["name"]);
            $target_file = $target_dir . uniqid() . "_" . $image_name;

            if (move_uploaded_file($_FILES[$fileKey]["tmp_name"], $target_file)) {
                return $target_file;
            } else {
                throw new Exception("Error uploading $fileKey.");
            }
        }
        return null;
    }

    // Upload files
    $image_path = uploadFile("patient_image");
    $intraoral_exam_image_path = uploadFile("intraoral_exam_image");
    $xray_image_path = uploadFile("xray_image");

    // Get POST data
    $patientData = json_decode($_POST['patientData'], true);
    $historyData = json_decode($_POST['historyData'], true);
    $healthData = json_decode($_POST['healthData'], true);

    // Check if this is a walk-in appointment
    $appointmentType = $_POST['appointmentType'] ?? null;
    $appointmentDate = $_POST['appointmentDate'] ?? null;
    $appointmentTime = $_POST['appointmentTime'] ?? null;

    // Insert into `patients` table
    $stmt = $conn->prepare("
        INSERT INTO patients (
            last_name, first_name, middle_name, gender, contact_number,
            address, birth_date, occupation, civil_status, religion,
            email, image_path, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->bind_param(
        "ssssssssssss",
        $patientData['last_name'],
        $patientData['first_name'],
        $patientData['middle_name'],
        $patientData['gender'],
        $patientData['contact_number'],
        $patientData['address'],
        $patientData['birth_date'],
        $patientData['occupation'],
        $patientData['civil_status'],
        $patientData['religion'],
        $patientData['email'],
        $image_path
    );
    if (!$stmt->execute()) {
        throw new Exception("Error inserting patient: " . $stmt->error);
    }
    $patient_info_id = $conn->insert_id;
    $stmt->close();

    // Insert into `patient_history` table with image paths
    $stmt = $conn->prepare("
        INSERT INTO patient_history (
            patient_info_id, previous_dentist, last_dental_visit,
            intraoral_exam_image, xray_image, past_dental_issues,
            previous_treatment, chief_complaint, physician_name, 
            physician_specialty, physician_office_address, 
            physician_office_number, created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    $stmt->bind_param(
        "isssssssssss",
        $patient_info_id,
        $historyData['previous_dentist'],
        $historyData['last_dental_visit'],
        $intraoral_exam_image_path,
        $xray_image_path,
        $historyData['past_dental_issues'],
        $historyData['previous_treatment'],
        $historyData['chief_complaint'],
        $historyData['physician_name'],
        $historyData['physician_specialty'],
        $historyData['physician_office_address'],
        $historyData['physician_office_number']
    );
    if (!$stmt->execute()) {
        throw new Exception("Error inserting patient history: " . $stmt->error);
    }
    $stmt->close();

    $medical_conditions = isset($healthData['medical_conditions']) ? $healthData['medical_conditions'] : '';

    if (is_array($medical_conditions)) {
        $medical_conditions = implode(", ", $medical_conditions); // Convert array to string
    }



    $stmt = $conn->prepare("
    INSERT INTO patient_health_declaration (
        patient_info_id, height, weight, good_health, 
        under_medical_treatment, medical_condition, hospital_admission,
        hospital_admission_reason, surgical_operation, 
        surgical_operation_details, taking_medications, medication_details,
        allergy_local_anesthetic, allergy_sulfur, allergy_aspirin,
        allergy_latex, allergy_penicillin, allergy_other, smoking, drugs,
        pregnant, nursing, birth_control, blood_type, blood_pressure,
        medical_conditions, created_at, updated_at, bleeding_time
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW(), ?)
    ");
    $stmt->bind_param(
        "iisssssssssssssssssssssssss",
        $patient_info_id,
        $healthData['height'],
        $healthData['weight'],
        $healthData['good_health'],
        $healthData['under_medical_treatment'],
        $healthData['medical_condition'],
        $healthData['hospital_admission'],
        $healthData['hospital_admission_reason'],
        $healthData['surgical_operation'],
        $healthData['surgical_operation_details'],
        $healthData['taking_medications'],
        $healthData['medication_details'],
        $healthData['allergy_local_anesthetic'],
        $healthData['allergy_sulfur'],
        $healthData['allergy_aspirin'],
        $healthData['allergy_latex'],
        $healthData['allergy_penicillin'],
        $healthData['allergy_other'],
        $healthData['smoking'],
        $healthData['drugs'],
        $healthData['pregnant'],
        $healthData['nursing'],
        $healthData['birth_control'],
        $healthData['blood_type'],
        $healthData['blood_pressure'],
        $medical_conditions, // Now correctly formatted
        $healthData['bleeding_time']
    );

    if (!$stmt->execute()) {
        throw new Exception("Error inserting health declaration: " . $stmt->error);
    }
    $stmt->close();

    // If "walk-in" appointment, insert a new row into `appointments`
    if ($appointmentType === 'walk-in') {
        $stmt = $conn->prepare("
            INSERT INTO appointments (
                patient_id, appointment_date, appointment_time,
                status, visit_type, created_at, updated_at,
                intraoral_exam_image, xray_image
            ) VALUES (?, ?, ?, 'pending', 'walk-in', NOW(), NOW(), ?, ?)
        ");
        $stmt->bind_param(
            "issss",
            $patient_info_id,
            $appointmentDate,
            $appointmentTime,
            $intraoral_exam_image_path,
            $xray_image_path
        );
        if (!$stmt->execute()) {
            throw new Exception("Error inserting walk-in appointment: " . $stmt->error);
        }
        $stmt->close();
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(["success" => true]);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(["success" => false, "error" => $e->getMessage()]);
}
?>