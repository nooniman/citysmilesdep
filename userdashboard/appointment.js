$(document).ready(function () {
  $("#submitAllForms").click(function (e) {
      e.preventDefault();

      let formData = new FormData();

      // Appointment Details
      formData.append("services_id", $("#service").val());
      formData.append("appointment_date", $("#appointmentDate").val());
      formData.append("appointment_time", $("#appointmentTime").val());
      
      // Dental History
      formData.append("previous_dentist", $("#previous_dentist").val());
      formData.append("last_dental_visit", $("#last_dental_visit").val());
      formData.append("intraoral_exam_image", $("#intraoral_exam_image")[0].files[0]);
      formData.append("xray_image", $("#xray_image")[0].files[0]);
      formData.append("past_dental_issues", $("#past_dental_issues").val());
      formData.append("previous_treatment", $("#previous_treatment").val());
      formData.append("chief_complaint", $("#chief_complaint").val());

      // Medical History
      formData.append("height", $("#height").val());
      formData.append("weight", $("#weight").val());
      formData.append("good_health", $("input[name='good_health']:checked").val());
      formData.append("under_medical_treatment", $("input[name='under_medical_treatment']:checked").val());
      formData.append("medical_condition", $("#medical_condition").val());
      formData.append("surgical_operation", $("input[name='surgical_operation']:checked").val());
      formData.append("surgical_operation_details", $("#surgical_operation_details").val());
      formData.append("hospital_admission", $("input[name='hospital_admission']:checked").val());
      formData.append("hospital_admission_reason", $("#hospital_admission_reason").val());
      formData.append("taking_medications", $("input[name='taking_medications']:checked").val());

      $.ajax({
          url: "submit_forms.php",
          type: "POST",
          data: formData,
          contentType: false,
          processData: false,
          success: function (response) {
              alert(response);
              location.reload();
          },
          error: function () {
              alert("Error submitting form.");
          }
      });
  });
});
