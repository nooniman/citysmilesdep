// Modal functionality
const invoiceModal = document.getElementById("invoiceModal");
const invoiceDetailsModal = document.getElementById("invoiceDetailsModal");
const orderSummaryModal = document.getElementById("orderSummaryModal");
const receiptModal = document.getElementById("receiptModal");

document.getElementById("openModalBtn").onclick = function() {
    invoiceModal.style.display = "block";
};

document.getElementById("closeModalBtn").onclick = function() {
    invoiceModal.style.display = "none";
};

document.getElementById("cancelBtn").onclick = function() {
    invoiceModal.style.display = "none";
};

document.getElementById("closeDetailsModalBtn").onclick = function() {
    invoiceDetailsModal.style.display = "none";
};

document.getElementById("backBtn").onclick = function() {
    invoiceDetailsModal.style.display = "none";
    invoiceModal.style.display = "block"; // Go back to the first modal
};

document.getElementById("closeOrderSummaryModalBtn").onclick = function() {
    orderSummaryModal.style.display = "none";
};

document.getElementById("orderBackBtn").onclick = function() {
    orderSummaryModal.style.display = "none";
    invoiceDetailsModal.style.display = "block"; // Go back to the second modal
};

document.getElementById("closeReceiptModalBtn").onclick = function() {
    receiptModal.style.display = "none";
};

document.getElementById("closeReceiptBtn").onclick = function() {
    receiptModal.style.display = "none";
};

window.onclick = function(event) {
    if (event.target == invoiceModal) {
        invoiceModal.style.display = "none";
    }
    if (event.target == invoiceDetailsModal) {
        invoiceDetailsModal.style.display = "none";
    }
    if (event.target == orderSummaryModal) {
        orderSummaryModal.style.display = "none";
    }
    if (event.target == receiptModal) {
        receiptModal.style.display = "none";
    }
};

document.getElementById("invoiceForm").onsubmit = function(event) {
    event.preventDefault();
    invoiceModal.style.display = "none"; // Close first modal
    invoiceDetailsModal.style.display = "block"; // Open second modal
};

document.getElementById("invoiceDetailsForm").onsubmit = function(event) {
    event.preventDefault();
    invoiceDetailsModal.style.display = "none"; // Close second modal
    orderSummaryModal.style.display = "block"; // Open third modal
};

document.getElementById("orderSummaryForm").onsubmit = function(event) {
    event.preventDefault();

    // Capture values for receipt
    document.getElementById("receiptInvoiceNumber").textContent = document.getElementById("invoiceNumberSummary").value;
    document.getElementById("receiptPaymentMethod").textContent = document.getElementById("paymentMethodSummary").value;
    document.getElementById("receiptInvoiceDate").textContent = document.getElementById("invoiceDateSummary").value;
    document.getElementById("receiptDescription").textContent = document.getElementById("descriptionSummary").value;

    // Close third modal and show receipt
    orderSummaryModal.style.display = "none"; // Close third modal
    receiptModal.style.display = "block"; // Open receipt modal
};

// Dropdown functionality
function toggleDropdown() {
    const dropdown = document.getElementById('dropdownMenu');
    dropdown.style.display = dropdown.style.display === 'block' ? 'none' : 'block';
}

function handleOptionClick(option) {
    alert('You clicked: ' + option);
    toggleDropdown(); // Close dropdown after selection
}

// Close dropdown if clicked outside
window.onclick = function(event) {
    if (!event.target.matches('.menu-icon')) {
        const dropdowns = document.getElementsByClassName("dropdown");
        for (let i = 0; i < dropdowns.length; i++) {
            dropdowns[i].style.display = "none";
        }
    }
}

document.addEventListener("DOMContentLoaded", function () {
    const patientSelect = document.getElementById("patientSelect");
    const emailInput = document.getElementById("email");
    const contactInput = document.getElementById("contact");
    const addressInput = document.getElementById("address");

    // Function to fetch patients and populate the dropdown
    function fetchPatients() {
        fetch("get_patient.php") // Adjust the path based on your structure
            .then(response => response.json())
            .then(data => {
                patientSelect.innerHTML = '<option value="" disabled selected>Select a patient</option>';
                data.forEach(patient => {
                    let option = document.createElement("option");
                    option.value = patient.patient_info_id;
                    option.textContent = `${patient.last_name}, ${patient.first_name}`;
                    option.dataset.email = patient.email;
                    option.dataset.contact = patient.contact_number;
                    option.dataset.address = patient.address;
                    patientSelect.appendChild(option);
                });
            })
            .catch(error => console.error("Error fetching patients:", error));
    }

    // Event listener to autofill details when a patient is selected
    patientSelect.addEventListener("change", function () {
        let selectedOption = patientSelect.options[patientSelect.selectedIndex];
        emailInput.value = selectedOption.dataset.email || "";
        contactInput.value = selectedOption.dataset.contact || "";
        addressInput.value = selectedOption.dataset.address || "";
    });

    // Fetch patients on page load
    fetchPatients();
});
