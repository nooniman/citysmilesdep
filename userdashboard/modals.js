// Function to open a modal
function openModal(id) {
  document.getElementById(id).classList.add("show");
  document.body.classList.add("modal-open"); // Prevent body scroll
}

// Function to close a modal
function closeModal(id) {
  document.getElementById(id).classList.remove("show");
  document.body.classList.remove("modal-open"); // Restore body scroll
}

// Function to open the edit modal
function openEditModal(modalId) {
  openModal(modalId); // Open the edit modal
}

//DELETE/PALITAN NYO LNG TO PAG IMPLEMENT NYO NA BACKEND
// Function to save patient information and proceed to the next step
function savePatientInfo() {

  closeModal('editPatientModal');

  openModal('dentalHistoryModal');
}

// Move from Add Patient to Dental History
function openDentalHistory() {
  document.getElementById("appointmentModal").style.display = "none";
  document.getElementById("dentalHistorymodal").style.display = "block";
}

// Move from Dental History to Medical History
function openMedicalHistory() {
  document.getElementById("dentalHistorymodal").style.display = "none";
  document.getElementById("medicalHistorymodal").style.display = "block";
}


function closeModal() {
  document.getElementById("appointmentModal").style.display = "none";
  document.getElementById("dentalHistorymodal").style.display = "none";
  document.getElementById("medicalHistorymodal").style.display = "none";
}

// Back from Dental History to Add Patient
function goBackToAppointment() {
  document.getElementById("dentalHistorymodal").style.display = "none";
  document.getElementById("appointmentModal").style.display = "block";
}

function goBackToDentalHistory() {
  document.getElementById("medicalHistorymodal").style.display = "none";
  document.getElementById("dentalHistorymodal").style.display = "block";
}

// Close modal when clicking outside of it
window.addEventListener("click", function (event) {
  const modals = document.querySelectorAll(".modal");
  modals.forEach(modal => {
      if (event.target === modal) {
          closeModal(modal.id);
      }
  });
});

// Add a keydown event listener for ESC key to close modal
window.addEventListener("keydown", function (event) {
  if (event.key === "Escape") {
      const modals = document.querySelectorAll(".modal");
      modals.forEach(modal => {
          if (modal.classList.contains("show")) {
              closeModal(modal.id);
          }
      });
  }
});


// Prevent form submission from refreshing the page
document.addEventListener("DOMContentLoaded", function () {
  const form = document.querySelector("#medicalHistoryForm");
  if (form) {
      form.addEventListener("submit", function (event) {
          event.preventDefault();
          alert("Form submitted successfully!"); // Replace with actual form handling
          closeModal("medicalHistoryModal");
      });
  }
});

// Attach the delete function to the delete button
document.querySelectorAll(".delete").forEach(button => {
  button.addEventListener("click", function () {
      deletePatient(); // Call the deletePatient function when clicked
  });
});

document.addEventListener("DOMContentLoaded", function () {
  let formData = {}; // Object to store form data

  function showModal(modalId) {
      document.querySelectorAll(".modal").forEach(modal => modal.style.display = "none");
      document.getElementById(modalId).style.display = "block";
  }

  function storeFormData(step) {
      let inputs = document.querySelectorAll(`#${step}Modal input, #${step}Modal select, #${step}Modal textarea`);
      inputs.forEach(input => {
          formData[input.name] = input.value;
      });
      console.log(formData);
  }

  document.querySelector(".btn.next").addEventListener("click", function (event) {
      event.preventDefault();
      storeFormData("appointment");
      showModal("dentalHistorymodal");
  });

  document.querySelector("#dentalHistoryModal .btn.next").addEventListener("click", function (event) {
      event.preventDefault();
      storeFormData("dentalHistory");
      showModal("medicalHistoryModal");
  });

  document.querySelector("#medicalHistoryModal .btn.submit").addEventListener("click", function (event) {
      event.preventDefault();
      storeFormData("medicalHistory");

      // Send AJAX request
      fetch("add_patient.php", {
          method: "POST",
          headers: {
              "Content-Type": "application/json",
          },
          body: JSON.stringify(formData),
      })
          .then(response => response.json())
          .then(data => {
              alert("Patient added successfully!");
              console.log(data);
          })
          .catch(error => console.error("Error:", error));
  });

  document.querySelector("#dentalHistoryModal .btn.cancel").addEventListener("click", function () {
      showModal("addPatientModal");
  });

  document.querySelector("#medicalHistoryModal .btn.cancel").addEventListener("click", function () {
      showModal("dentalHistoryModal");
  });

showModal("addPatientModal");
});
