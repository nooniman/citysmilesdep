<!-- Add Patient Modal -->
<div id="addPatientModal" class="modal">
    <div class="modal-content">
        <h2>Add Patient</h2>
        <div class="image-upload-container">
            <label for="profileImageInput">
                <div class="profile-placeholder" id="imagePreview">
                    <span>Upload Photo</span>
                </div>
            </label>
            <input type="file" id="profileImageInput" accept="image/*" onchange="previewImage(event)">
        </div>
        <h3>Patient Information</h3>
        <div class="form-group">
            <input type="text" placeholder="Last Name" required>
            <input type="text" placeholder="First Name" required>
            <input type="text" placeholder="Middle Name">
        </div>
        <div class="form-group">
            <select required>
                <option value="">Select Gender</option>
                <option>Male</option>
                <option>Female</option>
            </select>
            <input type="tel" placeholder="Contact Number" required>
        </div>
        <input type="text" placeholder="Address" required>
        <input type="date" placeholder="Birth Date" required>
        <div class="form-group">
            <input type="text" placeholder="Occupation">
            <input type="text" placeholder="Civil Status">
            <input type="text" placeholder="Religion">
        </div>
        <input type="email" placeholder="Email" required>
        <div class="modal-buttons">
            <button class="btn cancel" onclick="closeModal('addPatientModal')">Cancel</button>
            <button class="btn next" onclick="openDentalHistory()">Next</button>
        </div>
    </div>
</div>

<!-- Dental History Modal -->
<div id="dentalHistoryModal" class="modal">
    <div class="modal-content">
        <h2>Add Patient</h2>
        <h3>Dental History</h3>
        <div class="form-group">
            <input type="text" placeholder="Previous Dentist">
            <input type="text" placeholder="Last Dental Visit">
        </div>
        <div class="File-Container">
            <label>Intraoral Exam <input type="file" accept="image/*"></label>
            <label>Xray <input type="file" accept="image/*"></label>
        </div>
        <input type="text" placeholder="Past Dental Issues">
        <input type="text" placeholder="Previous Treatment">
        <input type="text" placeholder="Chief Complaint">
        <h3>Medical History</h3>
        <input type="text" placeholder="Name of Physician">
        <input type="text" placeholder="Specialty, if applicable">
        <input type="text" placeholder="Office Address">
        <input type="text" placeholder="Office Number">
        <div class="modal-buttons">
            <button class="btn back" onclick="goBackToPatientInfo()">Back</button>
            <button class="btn next" onclick="openMedicalHistory()">Next</button>
        </div>
    </div>
</div>

<!-- Medical History Modal -->
<div id="medicalHistoryModal" class="modal">
    <div class="modal-content">
        <h2>Add Patient</h2>
        <form id="medicalHistoryForm">
            <label for="height">Height:</label>
            <input type="text" id="height" required><br><br>
            <label for="weight">Weight:</label>
            <input type="text" id="weight" required><br><br>
            <h3>Medical History</h3>
            <label>1. Are you in good health?</label><br>
            <input type="radio" id="health-yes" name="health" value="yes"><label for="health-yes">Yes</label>
            <input type="radio" id="health-no" name="health" value="no"><label for="health-no">No</label>
            <input type="text" id="condition" placeholder="If yes, what is the condition being treated?"><br><br>
            <label>2. Have you ever had a serious illness or surgical operation?</label><br>
            <input type="radio" id="surgery-yes" name="surgery" value="yes"><label for="surgery-yes">Yes</label>
            <input type="radio" id="surgery-no" name="surgery" value="no"><label for="surgery-no">No</label><br><br>
            <label>3. Have you ever been hospitalized?</label><br>
            <input type="radio" id="hospitalization-yes" name="hospitalization" value="yes"><label
                for="hospitalization-yes">Yes</label>
            <input type="radio" id="hospitalization-no" name="hospitalization" value="no"><label
                for="hospitalization-no">No</label>
            <input type="text" id="hospitalization-details" placeholder="If yes, please specify"><br><br>
            <label>4. Are you taking any prescription/non-prescription medication?</label><br>
            <input type="radio" id="medication-yes" name="medication" value="yes"><label
                for="medication-yes">Yes</label>
            <input type="radio" id="medication-no" name="medication" value="no"><label
                for="medication-no">No</label><br><br>
            <label>5. Do you use tobacco products?</label><br>
            <input type="radio" id="tobacco-yes" name="tobacco" value="yes"><label for="tobacco-yes">Yes</label>
            <input type="radio" id="tobacco-no" name="tobacco" value="no"><label for="tobacco-no">No</label><br><br>
            <label>6. Do you use alcohol, cocaine or other dangerous drugs?</label><br>
            <input type="radio" id="drugs-yes" name="drugs" value="yes"><label for="drugs-yes">Yes</label>
            <input type="radio" id="drugs-no" name="drugs" value="no"><label for="drugs-no">No</label><br><br>
            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>
            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>
            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>
            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>
            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>
            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>
            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>
            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>
            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>
            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>
            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>
            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>
            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>
            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>
            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>
            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>
            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>

            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>
            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>
            <label>7. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin</label>
            <br><br>
            <div class="modal-buttons">
                <button class="btn cancel" onclick="closeModal('medicalHistoryModal')">Cancel</button>
                <input type="submit" class="btn next" value="Submit">
            </div>
        </form>
    </div>
</div>