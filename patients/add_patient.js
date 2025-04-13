$(document).ready(function () {
  $("#submitAllForms").click(function (e) {
    e.preventDefault();
    console.log("Submit button clicked!");

    let formData = new FormData();

    // Files
    let patientImage = $("#patient_image")[0]?.files[0];
    let intraoralImage = $("#intraoral_exam_image")[0]?.files[0];
    let xrayImage = $("#xray_image")[0]?.files[0];

    // Collect multiple selected checkboxes
    let medicalConditions = [];
    $('input[name="medical_conditions[]"]:checked').each(function () {
      medicalConditions.push($(this).val());
    });


    // Append text data
    formData.append("patientData", JSON.stringify({
      last_name: $("#last_name").val(),
      first_name: $("#first_name").val(),
      middle_name: $("#middle_name").val(),
      gender: $("#gender").val(),
      contact_number: $("#contact_number").val(),
      address: $("#address").val(),
      birth_date: $("#birth_date").val(),
      occupation: $("#occupation").val(),
      civil_status: $("#civil_status").val(),
      religion: $("#religion").val(),
      email: $("#email").val()
    }));

    formData.append("historyData", JSON.stringify({
      previous_dentist: $("#previous_dentist").val(),
      last_dental_visit: $("#last_dental_visit").val(),
      past_dental_issues: $("#past_dental_issues").val(),
      previous_treatment: $("#previous_treatment").val(),
      chief_complaint: $("#chief_complaint").val(),
      physician_name: $("#physician_name").val(),
      physician_specialty: $("#physician_specialty").val(),
      physician_office_address: $("#physician_office_address").val(),
      physician_office_number: $("#physician_office_number").val()
    }));

    formData.append("healthData", JSON.stringify({
      height: $("#height").val(),
      weight: $("#weight").val(),
      good_health: $("#good_health").val(),
      under_medical_treatment: $("#under_medical_treatment").val(),
      medical_condition: $("#medical_condition").val(),
      hospital_admission: $("#hospital_admission").val(),
      hospital_admission_reason: $("#hospital_admission_reason").val(),
      surgical_operation: $("#surgical_operation").val(),
      surgical_operation_details: $("#surgical_operation_details").val(),
      taking_medications: $("#taking_medications").val(),
      medication_details: $("#medication_details").val(),
      allergy_local_anesthetic: $("#allergy_local_anesthetic").val(),
      allergy_sulfur: $("#allergy_sulfur").val(),
      allergy_aspirin: $("#allergy_aspirin").val(),
      allergy_latex: $("#allergy_latex").val(),
      allergy_penicillin: $("#allergy_penicillin").val(),
      allergy_other: $("#allergy_other").val(),
      smoking: $("#smoking").val(),
      drugs: $("#drugs").val(),
      pregnant: $("#pregnant").val(),
      nursing: $("#nursing").val(),
      birth_control: $("#birth_control").val(),
      blood_type: $("#blood_type").val(),
      blood_pressure: $("#blood_pressure").val(),
      medical_conditions: medicalConditions.join(", "), // Store as comma-separated string
      bleeding_time: $("#bleeding_time").val()
    }));

    // Append images if selected
    if (patientImage) {
      formData.append("patient_image", patientImage);
    }
    if (intraoralImage) {
      formData.append("intraoral_exam_image", intraoralImage);
    }
    if (xrayImage) {
      formData.append("xray_image", xrayImage);
    }

    // If user clicked "Add Walk-In"
    if (window.appointmentType === "walk-in") {
      formData.append("appointmentType", "walk-in");
      formData.append("appointmentDate", new Date().toISOString().slice(0, 10));
      formData.append("appointmentTime", "09:00:00");
    }

    $.ajax({
      url: "add_patient.php",
      type: "POST",
      data: formData,
      contentType: false,
      processData: false,
      dataType: "json",
      success: function (response) {
        console.log("Server Response:", response);
        if (response.success) {
          alert("Data successfully submitted!");
          location.reload();
        } else {
          alert("Error: " + response.error);
        }
      },
      error: function (xhr, status, error) {
        console.error("AJAX Error:", xhr.responseText);
        alert("An error occurred. Check console.");
      }
    });
  });
});