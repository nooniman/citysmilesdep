<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="modal.css">
    <title>Document</title>
</head>

<body>

    <!-- Add Patient Modal -->
    <div id="addPatientModal" class="modal">
        <div class="modal-content">
            <h2 style=" font-size:2rem;">Add Patient</h2>

            <!-- Image Upload Container -->
            <div class="image-upload-container">
                <label for="patient_image" class="profile-placeholder" id="imagePreview">
                    <input type="file" id="patient_image" name="patient_image" accept="image/*"
                        onchange="previewImage(event)">
                    <span>Upload Photo</span>
                </label>
            </div>

            <!-- Patient Form -->
            <div class="patient-form">
                <h3>Patient Information</h3>

                <div class="form-row">
                    <input type="text" id="last_name" name="last_name" placeholder="Last Name" required>
                    <input type="text" id="first_name" name="first_name" placeholder="First Name" required>
                    <input type="text" id="middle_name" name="middle_name" placeholder="Middle Name">
                </div>

                <div class="form-row">
                    <select id="gender" name="gender">
                        <option>Male</option>
                        <option>Female</option>
                        <option>Other</option>
                    </select>
                    <input type="number" placeholder="Contact Number" id="contact_number" name="contact_number">
                </div>

                <div class="form-row">
                    <input type="text" id="address" name="address" placeholder="Address">
                </div>

                <div class="form-row">
                    <input type="date" placeholder="Birth Date" id="birth_date" name="birth_date">
                </div>

                <div class="form-row">
                    <input type="text" placeholder="Occupation" id="occupation" name="occupation">
                    <input type="text" placeholder="Civil Status" id="civil_status" name="civil_status">
                    <input type="text" placeholder="Religion" id="religion" name="religion">
                </div>

                <div class="form-row">
                    <input type="email" placeholder="Email" id="email" name="email">
                </div>

                <!-- Submit Button -->
                <div class="modal-buttons">
                    <button class="btn cancel" onclick="closeModal('addPatientModal')">Cancel</button>
                    <button class="btn next" onclick="openDentalHistory()">Next</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Dental History Modal -->
    <div id="dentalHistoryModal" class="modal">
        <div class="modal-content">
            <h3>Dental History</h3>
                <input type="text" placeholder="Previous Dentist" id="previous_dentist" name="previous_dentist">
            <div class="file-item">
                <label  for="last_dental_visit"><strong>Last Dental Visit</strong></label>
                <input type="date" placeholder="" id="last_dental_visit" name="last_dental_visit">
            </div>
            <div class="file-container">

            <div class="file-item">
    <label for="intraoral_exam_image"><strong>Intraoral Exam</strong></label>
    <label class="custom-file-upload">
        <input type="file" accept="image/*" id="intraoral_exam_image" name="intraoral_exam_image">
        Choose File
    </label>
</div>

<div class="file-item">
    <label for="xray_image"><strong>X-ray</strong></label>
    <label class="custom-file-upload">
        <input type="file" accept="image/*" id="xray_image" name="xray_image">
        Choose File
    </label>
</div>
            </div>
            <input type="text" placeholder="Past Dental Issues" id="past_dental_issues" name="past_dental_issues">
            <input type="text" placeholder="Previous Treatment" id="previous_treatment" name="previous_treatment">
            <input type="text" placeholder="Chief Complaint" id="chief_complaint" name="chief_complaint">
            <h3 style="margin-top:1em">Medical History</h3>
            <input type="text" placeholder="Name of Physician" id="physician_name" name="physician_name">
            <input type="text" placeholder="Specialty, if applicable" id="physician_specialty"
                name="physician_specialty">
            <input type="text" placeholder="Office Address" id="physician_office_address"
                name="physician_office_address">
            <input type="text" placeholder="Office Number" id="physician_office_number" name="physician_office_number">
            <div class="modal-buttons">
                <button class="btn cancel" onclick="goBackToPatientInfo()">Back</button>
                <button class="btn next" onclick="openMedicalHistory()">Next</button>
            </div>
        </div>
    </div>

    <!-- Medical History Modal -->
    <div id="medicalHistoryModal" class="modal">
        <div class="modal-content">
            <div class="input-group">
                <label for="height">Height:</label>
                <input type="text" id="height" name="height" required>
            </div>
            <div class="input-group">
                <label for="weight">Weight:</label>
                <input type="text" id="weight" name="weight" required>
            </div>
            <h3>Medical History</h3>

            <table class="medical-history-table">
    <thead>
        <tr>
            <th>Question</th>
            <th>Yes</th>
            <th>No</th>
            <th>Additional Information</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>1. Are you in good health?</td>
            <td><input type="radio" name="good_health" id="good_health_yes" value="yes"></td>
            <td><input type="radio" name="good_health" id="good_health_no" value="no"></td>
            <td></td>
        </tr>
        <tr>
            <td>2. Are you under medical treatment now?</td>
            <td><input type="radio" name="under_medical_treatment" id="under_medical_treatment_yes" value="yes" onclick="toggleOtherOption('under_medical_treatment', 'under_medical_treatment_other')"></td>
            <td><input type="radio" name="under_medical_treatment" id="under_medical_treatment_no" value="no" onclick="toggleOtherOption('under_medical_treatment', 'under_medical_treatment_other')"></td>
            <td>
                <div id="under_medical_treatment_other" class="other-option" style="display: none;">
                    <textarea name="medical_condition" id="medical_condition" rows="3" cols="30" placeholder="Type your answer here"></textarea>
                </div>
            </td>
        </tr>
        <tr>
            <td>3. Have you ever had a serious illness or surgical operation?</td>
            <td><input type="radio" name="surgical_operation" id="surgical_operation_yes" value="yes" onclick="toggleOtherOption('surgical_operation', 'surgical_operation_other')"></td>
            <td><input type="radio" name="surgical_operation" id="surgical_operation_no" value="no" onclick="toggleOtherOption('surgical_operation', 'surgical_operation_other')"></td>
            <td>
                <div id="surgical_operation_other" class="other-option" style="display: none;">
                    <textarea name="surgical_operation_details" id="surgical_operation_details" rows="3" cols="30" placeholder="Type your answer here"></textarea>
                </div>
            </td>
        </tr>
        <tr>
            <td>4. Have you ever been hospitalized?</td>
            <td><input type="radio" name="hospital_admission" id="hospital_admission_yes" value="yes" onclick="toggleOtherOption('hospital_admission', 'hospital_admission_other')"></td>
            <td><input type="radio" name="hospital_admission" id="hospital_admission_no" value="no" onclick="toggleOtherOption('hospital_admission', 'hospital_admission_other')"></td>
            <td>
                <div id="hospital_admission_other" class="other-option" style="display: none;">
                    <textarea name="hospital_admission_reason" id="hospital_admission_reason" rows="3" cols="30" placeholder="Type your answer here"></textarea>
                </div>
            </td>
        </tr>
        <tr>
            <td>5. Are you taking any prescription/non-prescription medication?</td>
            <td><input type="radio" name="taking_medications" id="taking_medications_yes" value="yes" onclick="toggleOtherOption('taking_medications', 'taking_medications_other')"></td>
            <td><input type="radio" name="taking_medications" id="taking_medications_no" value="no" onclick="toggleOtherOption('taking_medications', 'taking_medications_other')"></td>
            <td>
                <div id="taking_medications_other" class="other-option" style="display: none;">
                    <textarea name="medication_details" id="medication_details" rows="3" cols="30" placeholder="Type your answer here"></textarea>
                </div>
            </td>
        </tr>
        <tr>
            <td>6. Do you use tobacco products?</td>
            <td><input type="radio" name="smoking" id="smoking_yes" value="yes"></td>
            <td><input type="radio" name="smoking" id="smoking_no" value="no"></td>
            <td></td>
        </tr>
        <tr>
            <td>7. Do you use alcohol, cocaine, or other dangerous drugs?</td>
            <td><input type="radio" name="drugs" id="drugs_yes" value="yes"></td>
            <td><input type="radio" name="drugs" id="drugs_no" value="no"></td>
            <td></td>
        </tr>
        <tr>
            <td>8. Are you pregnant?</td>
            <td><input type="radio" name="pregnant" id="pregnant_yes" value="yes"></td>
            <td><input type="radio" name="pregnant" id="pregnant_no" value="no"></td>
            <td></td>
        </tr>
        <tr>
            <td>9. Are you nursing?</td>
            <td><input type="radio" name="nursing" id="nursing_yes" value="yes"></td>
            <td><input type="radio" name="nursing" id="nursing_no" value="no"></td>
            <td></td>
        </tr>
        <tr>
            <td>10. Are you taking birth control pills?</td>
            <td><input type="radio" name="birth_control" id="birth_control_yes" value="yes"></td>
            <td><input type="radio" name="birth_control" id="birth_control_no" value="no"></td>
            <td></td>
        </tr>
    </tbody>
</table>

            <label>11. Blood Type:</label>
            <select name="blood_type" id="blood_type" placeholder="Select Blood Type">
                <option disabled selected>Select Blood Type</option>
                <option value="A">A</option>
                <option value="B">B</option>
                <option value="AB">AB</option>
                <option value="O">O</option>
                <option value="O+">O+</option>
                <option value="Unknown">Unknown</option>
            </select><br><br>

            <label for="blood_pressure">12. Blood Pressure:</label>
            <input type="text" id="blood_pressure" name="blood_pressure" placeholder="Type your answer here">

            <label>13. Do you have or have you had any of the following?</label><br>
            <div class="checkbox-group">
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]"
                        value="High Blood Pressure">
                    <span>High Blood Pressure</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]"
                        value="Low Blood Pressure">
                    <span>Low Blood Pressure</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]"
                        value="Epilepsy/Convulsions">
                    <span>Epilepsy/Convulsions</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]"
                        value="AIDS/HIV Infection">
                    <span>AIDS/HIV Infection</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]"
                        value="Sexually Transmitted Disease">
                    <span>Sexually Transmitted Disease</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]"
                        value="Stomach Trouble/Ulcer">
                    <span>Stomach Trouble/Ulcer</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]" value="Fainting Seizure">
                    <span>Fainting Seizure</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]"
                        value="Rapid Weight Loss">
                    <span>Rapid Weight Loss</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]"
                        value="Radiation Therapy">
                    <span>Radiation Therapy</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]"
                        value="Joint Replacement/Implant">
                    <span>Joint Replacement/Implant</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]" value="Heart Surgery">
                    <span>Heart Surgery</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]" value="Heart Murmur">
                    <span>Heart Murmur</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]"
                        value="Hepatitis/Liver Disease">
                    <span>Hepatitis/Liver Disease</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]" value="Rheumatic Fever">
                    <span>Rheumatic Fever</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]"
                        value="High fever/Allergies">
                    <span>High fever/Allergies</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]"
                        value="Hepatitis/Jaundice">
                    <span>Hepatitis/Jaundice</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]" value="Tuberculosis">
                    <span>Tuberculosis</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]" value="Swollen Ankles">
                    <span>Swollen Ankles</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]" value="Kidney Disease">
                    <span>Kidney Disease</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]" value="Diabetes">
                    <span>Diabetes</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]"
                        value="Arthritis/Rheumatism">
                    <span>Arthritis/Rheumatism</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]" value="Cancer/Tumors">
                    <span>Cancer/Tumors</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]" value="Anemia">
                    <span>Anemia</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]" value="Angina">
                    <span>Angina</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]" value="Asthma">
                    <span>Asthma</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]" value="Emphysema">
                    <span>Emphysema</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]"
                        value="Bleeding Problems">
                    <span>Bleeding Problems</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]" value="Blood Diseases">
                    <span>Blood Diseases</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]" value="Head Injuries">
                    <span>Head Injuries</span>
                </label>
                <label class="custom-checkbox">
                    <input type="checkbox" id="medical_conditions" name="medical_conditions[]" value="Other">
                    <span>Other</span>
                </label>
            </div>

            <div class="modal-buttons">
            <button class="btn cancel" onclick="goBackToDentalHistory()">Back</button>
                <input type="submit" class="btn next" id="submitAllForms" value="Submit">

            </div>
        </div>
    </div>



    <script>
        function goBackToDentalHistory() {
    closeModal('medicalHistoryModal');
    openModal('dentalHistoryModal');
}
        function toggleOtherOption(questionId, otherOptionId) {
            var yesOption = document.getElementById(questionId + '_yes');
            var otherOption = document.getElementById(otherOptionId);
            if (yesOption.checked) {
                otherOption.style.display = 'block';
            } else {
                otherOption.style.display = 'none';
            }
        }
    </script>
    <script>
        function toggleOtherOption(questionId, otherOptionId) {
            var yesOption = document.getElementById(questionId + '_yes');
            var otherOption = document.getElementById(otherOptionId);
            if (yesOption.checked) {
                otherOption.style.display = 'block';
            } else {
                otherOption.style.display = 'none';
            }
        }
        function openModal(modalId) {
    document.body.classList.add("modal-open");
    document.getElementById(modalId).style.display = "flex";

    // Create and append the backdrop if it doesn’t exist
    if (!document.querySelector(".modal-backdrop")) {
        const backdrop = document.createElement("div");
        backdrop.classList.add("modal-backdrop");
        document.body.appendChild(backdrop);
    }
}

function closeModal(modalId) {
    document.body.classList.remove("modal-open");
    document.getElementById(modalId).style.display = "none";

    // Remove backdrop
    const backdrop = document.querySelector(".modal-backdrop");
    if (backdrop) backdrop.remove();
}

// Close modal when clicking outside
document.addEventListener("click", function (event) {
    const modal = document.querySelector(".modal");
    if (modal && event.target.classList.contains("modal-backdrop")) {
        closeModal(modal.id);
    }
});

    </script>


    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
        integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="add_patient.js"></script>

</body>

</html>