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

            <div class="question">
                <label>1. Are you in good health?</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="health" value="yes"> Yes
                    </label>
                    <label>
                        <input type="radio" name="health" value="no"> No
                    </label>
                </div>
            </div>

            <div class="question">
                <label>2. Are you under medical treatment now?</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="medical_treatment" value="yes"> Yes
                    </label>
                    <label>
                        <input type="radio" name="medical_treatment" value="no"> No
                    </label>
                </div>
                <label>If so, what is the condition being treated?</label>
                <input type="text" placeholder="Type your answer here"><br>
            </div>

            <div class="question">
                <label>3. Have you ever had a serious illness or surgical operation?</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="serious_illness" value="yes"> Yes
                    </label>
                    <label>
                        <input type="radio" name="serious_illness" value="no"> No
                    </label>
                </div>
                <label>If so, what illness or operation?</label>
                <input type="text" placeholder="Type your answer here"><br>
            </div>

            <div class="question">
                <label>4. Have you ever been hospitalized?</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="hospitalized" value="yes"> Yes
                    </label>
                    <label>
                        <input type="radio" name="hospitalized" value="no"> No
                    </label>
                </div>
                <label>If so, please specify.</label>
                <input type="text" placeholder="Type your answer here"><br>
            </div>

            <div class="question">
                <label>5. Are you taking any prescription/non-prescription medication?</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="medication" value="yes"> Yes
                    </label>
                    <label>
                        <input type="radio" name="medication" value="no"> No
                    </label>
                </div>
                <label>If so, please specify.</label>
                <input type="text" placeholder="Type your answer here"><br>
            </div>

            <div class="question">
                <label>6. Do you use tobacco products?</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="tobacco" value="yes"> Yes
                    </label>
                    <label>
                        <input type="radio" name="tobacco" value="no"> No
                    </label>
                </div>
            </div>

            <div class="question">
                <label>7. Do you use alcohol, cocaine or other dangerous drugs?</label>
                <div class="radio-group">
                    <label>
                        <input type="radio" name="drugs" value="yes"> Yes
                    </label>
                    <label>
                        <input type="radio" name="drugs" value="no"> No
                    </label>
                </div>
            </div>
            
            <label>8. Are you allergic to any of the following?</label><br>
            <input type="checkbox" id="local-anesthetic"><label for="local-anesthetic">Local Anesthetic</label>
            <input type="checkbox" id="sulfur-drugs"><label for="sulfur-drugs">Sulfur Drugs</label>
            <input type="checkbox" id="aspirin"><label for="aspirin">Aspirin</label>
            <input type="checkbox" id="latex"><label for="latex">Latex</label>
            <input type="checkbox" id="penicillin"><label for="penicillin">Penicillin, Antibiotics</label>
            <input type="checkbox" id="other"><label for="other">Other</label>

            <label>9. Bleeding time:</label><br>
            <input type="text" placeholder="Type your answer here"><br>

            <label>10. For women only:</label><br>
            <label>Are you pregnant?</label><br>
            <div class="radio-group">
                <label>
                    <input type="radio" name="pregnant" value="yes"> Yes
                </label>
                <label>
                    <input type="radio" name="pregnant" value="no"> No
                </label><br>
            </div>
            <label>Are you nursing?</label><br>
            <div class="radio-group">
                <label>
                    <input type="radio" name="nursing" value="yes"> Yes
                </label>
                <label>
                    <input type="radio" name="nursing" value="no"> No
                </label><br>
            </div>
            <label>Are you taking birth control pills?</label><br>
            <div class="radio-group">
                <label>
                    <input type="radio" name="birth_control" value="yes"> Yes
                </label>
                <label>
                    <input type="radio" name="birth_control" value="no"> No
                </label><br>
            </div>

            <label>11. Blood Type:</label><br>
            <input type="text" placeholder="Type your answer here"><br>

            <label>12. Blood Pressure:</label><br>
            <input type="text" placeholder="Type your answer here"><br>

            <label>13. Do you have or have you had any of the following?</label><br>
            <input type="checkbox" id="high-blood-pressure"><label for="high-blood-pressure">High Blood Pressure</label>
            <input type="checkbox" id="low-blood-pressure"><label for="low-blood-pressure">Low Blood Pressure</label>
            <input type="checkbox" id="epilepsy"><label for="epilepsy">Epilepsy/Convulsions</label>
            <input type="checkbox" id="aids"><label for="aids">AIDS/HIV Infection</label>
            <input type="checkbox" id="std"><label for="std">Sexually Transmitted Disease</label>
            <input type="checkbox" id="stomach-trouble"><label for="stomach-trouble">Stomach Trouble/Ulcer</label>
            <input type="checkbox" id="fainting-seizure"><label for="fainting-seizure">Fainting Seizure</label>
            <input type="checkbox" id="rapid-weight-loss"><label for="rapid-weight-loss">Rapid Weight Loss</label>
            <input type="checkbox" id="radiation-therapy"><label for="radiation-therapy">Radiation Therapy</label>
            <input type="checkbox" id="joint-replacement"><label for="joint-replacement">Joint Replacement/Implant</label>
            <input type="checkbox" id="heart-surgery"><label for="heart-surgery">Heart Surgery</label>
            <input type="checkbox" id="heart-murmur"><label for="heart-murmur">Heart Murmur</label>
            <input type="checkbox" id="hepatitis"><label for="hepatitis">Hepatitis/Liver Disease</label>
            <input type="checkbox" id="rheumatic-fever"><label for="rheumatic-fever">Rheumatic Fever</label>
            <input type="checkbox" id="high-fever-allergies"><label for="high-fever-allergies">High fever/Allergies</label>
            <input type="checkbox" id="hepatitis-jaundice"><label for="hepatitis-jaundice">Hepatitis/Jaundice</label>
            <input type="checkbox" id="tuberculosis"><label for="tuberculosis">Tuberculosis</label>
            <input type="checkbox" id="swollen-ankles"><label for="swollen-ankles">Swollen Ankles</label>
            <input type="checkbox" id="kidney-disease"><label for="kidney-disease">Kidney Disease</label>
            <input type="checkbox" id="diabetes"><label for="diabetes">Diabetes</label>
            <input type="checkbox" id="arthritis"><label for="arthritis">Arthritis/Rheumatism</label>
            <input type="checkbox" id="cancer"><label for="cancer">Cancer/Tumors</label>
            <input type="checkbox" id="anemia"><label for="anemia">Anemia</label>
            <input type="checkbox" id="angina"><label for="angina">Angina</label>
            <input type="checkbox" id="asthma"><label for="asthma">Asthma</label>
            <input type="checkbox" id="emphysema"><label for="emphysema">Emphysema</label>
            <input type="checkbox" id="bleeding-problems"><label for="bleeding-problems">Bleeding Problems</label>
            <input type="checkbox" id="blood-diseases"><label for="blood-diseases">Blood Diseases</label>
            <input type="checkbox" id="head-injuries"><label for="head-injuries">Head Injuries</label>
            <input type="checkbox" id="other"><label for="other">Other</label>
        </form>
    </div>
</div>

<style>
    .modal {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 2rem;
        background-color: rgba(0, 0, 0, 0.5);
    }

    .modal-content {
        background-color: white;
        padding: 2rem;
        border-radius: 0.5rem;
        width: 80vw;
        max-width: 600px;
    }

    label {
        display: block;
        margin-bottom: 0.5rem;
    }

    input[type="text"], input[type="radio"], input[type="checkbox"] {
        margin-bottom: 1rem;
    }

    .radio-group {
        display: flex;
        justify-content: flex-end;
        margin-bottom: 1rem;
    }

    .radio-group label {
        margin-left: 1rem;
    }
</style>