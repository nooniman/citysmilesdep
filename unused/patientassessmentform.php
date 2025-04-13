<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Assessment Form</title>
    <link rel="stylesheet" href="styles.css">
</head>
<body>
<?php include '../sidebar/sidebar.php'; ?>
    

    <div class="container">
    <h2>Patient Assessment Form</h2>
<form id="assessmentForm">
    <div class="section">
    <div class="section">
    <div class="patient-form">
        <h3>Patient Information</h3>
        <div class="form-section">
            <div class="image-container">
                <img src="path_to_image.jpg" alt="Patient Image" id="patient-image" />
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="last_name">Last Name:</label>
                    <input type="text" id="last_name" name="last_name" placeholder="Last Name" required>
                </div>
                <div class="form-group">
                    <label for="first_name">First Name:</label>
                    <input type="text" id="first_name" name="first_name" placeholder="First Name" required>
                </div>
                <div class="form-group">
                    <label for="middle_name">Middle Name:</label>
                    <input type="text" id="middle_name" name="middle_name" placeholder="Middle Name">
                </div>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="gender">Gender:</label>
                <select id="gender" name="gender">
                    <option>Male</option>
                    <option>Female</option>
                    <option>Other</option>
                </select>
            </div>
            <div class="form-group">
                <label for="birth_date">Birthday:</label>
                <input type="date" placeholder="Birth Date" id="birth_date" name="birth_date">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="civil_status">Civil Status:</label>
                <input type="text" placeholder="Civil Status" id="civil_status" name="civil_status">
            </div>
            <div class="form-group">
                <label for="occupation">Occupation:</label>
                <input type="text" placeholder="Occupation" id="occupation" name="occupation">
            </div>
            <div class="form-group">
                <label for="religion">Religion:</label>
                <input type="text" placeholder="Religion" id="religion" name="religion">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="contact_number">Contact Number:</label>
                <input type="text" placeholder="Contact Number" id="contact_number" name="contact_number">
            </div>
            <div class="form-group">
                <label for="email">Email Address:</label>
                <input type="email" placeholder="Email" id="email" name="email">
            </div>
            <div class="form-group">
                <label for="address">Address:</label>
                <input type="text" id="address" name="address" placeholder="Address">
            </div>
        </div>
    </div>
</div>
    </div>

    <div class="section">
    <h3>Dental History</h3>
    <div class="modal-content">
        <div class="form-row">
            <div class="file-item" style="flex: 1; margin-right: 10px;">
                <label for="previous_dentist"><strong>Previous Dentist</strong></label>
                <input type="text" placeholder="Previous Dentist" id="previous_dentist" name="previous_dentist">
            </div>
            <div class="file-item" style="flex: 1;">
                <label for="last_dental_visit"><strong>Last Dental Visit</strong></label>
                <input type="date" id="last_dental_visit" name="last_dental_visit">
            </div>
        </div>

        <div class="file-container" style="margin-top: 20px;">
            <div class="file-item-2">
                <label for="intraoral_exam_image">Intraoral Exam</label>
                <label class="custom-file-upload">
                    <input type="file" accept="image/*" id="intraoral_exam_image" name="intraoral_exam_image">
                    Choose File
                </label>
            </div>

            <div class="file-item-2">
                <label for="xray_image"><strong>X-ray</strong></label>
                <label class="custom-file-upload">
                    <input type="file" accept="image/*" id="xray_image" name="xray_image">
                    Choose File
                </label>
            </div>
        </div>

        <div class="form-row" style="margin-top: 20px;">
        <label for="">Past Dental Issue:</label>
            <input type="text" placeholder="Past Dental Issues" id="past_dental_issues" name="past_dental_issues">
        </div>
        
        <div class="form-row">
        <label for="">Previous Treatment:</label>
            <input type="text" placeholder="Previous Treatment" id="previous_treatment" name="previous_treatment">
        </div>
        
        <div class="form-row">
        <label for="">Chief Complaint:</label>
            <input type="text" placeholder="Chief Complaint" id="chief_complaint" name="chief_complaint">
        </div>
    </div>
</div>

<div class="section">
    <h3>Medical History</h3>

    <div class="input-group">
        <label for="height">Height:</label>
        <input type="text" id="height" name="height" required>
    </div>
    <div class="input-group">
        <label for="weight">Weight:</label>
        <input type="text" id="weight" name="weight" required>
    </div>

    <!-- Table for Yes/No Questions -->
    <table class="medical-history-table">
        <thead>
            <tr>
                <th>Question</th>
                <th>Yes</th>
                <th>No</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>1. Are you in good health?</td>
                <td><input type="radio" name="good_health" value="yes"></td>
                <td><input type="radio" name="good_health" value="no"></td>
            </tr>
            <tr>
                <td>2. Are you under medical treatment now?</td>
                <td><input type="radio" name="under_medical_treatment" value="yes" onclick="toggleOtherOption('under_medical_treatment', 'under_medical_treatment_other')"></td>
                <td><input type="radio" name="under_medical_treatment" value="no" onclick="toggleOtherOption('under_medical_treatment', 'under_medical_treatment_other')"></td>
            </tr>
            <tr>
                <td>3. Have you ever had a serious illness or surgical operation?</td>
                <td><input type="radio" name="surgical_operation" value="yes" onclick="toggleOtherOption('surgical_operation', 'surgical_operation_other')"></td>
                <td><input type="radio" name="surgical_operation" value="no" onclick="toggleOtherOption('surgical_operation', 'surgical_operation_other')"></td>
            </tr>
            <tr>
                <td>4. Have you ever been hospitalized?</td>
                <td><input type="radio" name="hospital_admission" value="yes" onclick="toggleOtherOption('hospital_admission', 'hospital_admission_other')"></td>
                <td><input type="radio" name="hospital_admission" value="no" onclick="toggleOtherOption('hospital_admission', 'hospital_admission_other')"></td>
            </tr>
            <tr>
                <td>5. Are you taking any prescription/non-prescription medication?</td>
                <td><input type="radio" name="taking_medications" value="yes" onclick="toggleOtherOption('taking_medications', 'taking_medications_other')"></td>
                <td><input type="radio" name="taking_medications" value="no" onclick="toggleOtherOption('taking_medications', 'taking_medications_other')"></td>
            </tr>
            <tr>
                <td>6. Do you use tobacco products?</td>
                <td><input type="radio" name="smoking" value="yes"></td>
                <td><input type="radio" name="smoking" value="no"></td>
            </tr>
            <tr>
                <td>7. Do you use alcohol, cocaine, or other dangerous drugs?</td>
                <td><input type="radio" name="drugs" value="yes"></td>
                <td><input type="radio" name="drugs" value="no"></td>
            </tr>
            <label>10. For women only:</label><br>
            <label>Are you pregnant?</label><br>
            <div class="radio-group">
                <label>
                    <input type="radio" name="pregnant" id="pregnant" value="yes"> Yes
                </label>
                <label>
                    <input type="radio" name="pregnant" id="pregnant" value="no"> No
                </label><br>
            </div>
            <label>Are you nursing?</label><br>
            <div class="radio-group">
                <label>
                    <input type="radio" name="nursing" id="nursing" value="yes"> Yes
                </label>
                <label>
                    <input type="radio" name="nursing" id="nursing" value="no"> No
                </label><br>
            </div>
            <label>Are you taking birth control pills?</label><br>
            <div class="radio-group">
                <label>
                    <input type="radio" name="birth_control" id="birth_control" value="yes"> Yes
                </label>
                <label>
                    <input type="radio" name="birth_control" id="birth_control" value="no"> No
                </label><br>
            </div>
        </tbody>
    </table>

    <!-- Additional Fields for Conditional Questions -->
    <div id="under_medical_treatment_other" class="other-option" style="display: none;">
        <label>If so, what is the condition being treated?</label>
        <textarea name="medical_condition" id="medical_condition" rows="3" cols="50" placeholder="Type your answer here"></textarea><br>
    </div>
    <div id="surgical_operation_other" class="other-option" style="display: none;">
        <label>If so, what illness or operation?</label>
        <textarea name="surgical_operation_details" id="surgical_operation_details" rows="3" cols="50" placeholder="Type your answer here"></textarea><br>
    </div>
    <div id="hospital_admission_other" class="other-option" style="display: none;">
        <label>If so, please specify.</label>
        <textarea name="hospital_admission_reason" id="hospital_admission_reason" rows="3" cols="50" placeholder="Type your answer here"></textarea><br>
    </div>
    <div id="taking_medications_other" class="other-option" style="display: none;">
        <label>If so, please specify.</label>
        <textarea name="medication_details" id="medication_details" rows="3" cols="50" placeholder="Type your answer here"></textarea><br>
    </div>
</div>
        </form>
    </div>

    <script>
        function fillForm(name, gender, contact, email) {
            document.getElementById("name").value = name;
            document.getElementById("gender").value = gender;
            document.getElementById("contact").value = contact;
            document.getElementById("email").value = email;
        }
    </script>
</body>
</html>
