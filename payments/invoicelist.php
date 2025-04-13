<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <link rel="stylesheet" href="../payments/payments.css">
    <link rel="stylesheet" href="styles.css"> <!-- Link to CSS -->
    <script src="script.js" defer></script> <!-- Link to JavaScript -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>City Smile Dental Clinic</title>
</head>

<body>

    <?php include '../sidebar/sidebar.php'; ?>
    <div class="content">
        <section class="dashboard-content">
            <div class="header-container">
                <div class="text-payment">
                    <h1>Invoice List</h1>
                </div>
                <div class="addinvoice-container">
                    <button class="addinvoice-button" id="openModalBtn">Add Invoice</button>
                </div>
            </div>
            <div class="container">
                <div class="export-buttons">
                    <button class="export">Excel</button>
                    <button class="export">PDF</button>
                    <input type="text" class="head" placeholder="Search...">
                </div>

                <table>
                    <tr>
                        <th>Invoice ID</th>
                        <th>Date</th>
                        <th>Patient Name</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                    <tr>
                        <td>#013</td>
                        <td>02/22/2022</td>
                        <td>Jones Ivan Francisco</td>
                        <td>₱2500.00</td>
                        <td class="status-paid">Paid</td>
                        <td>
                            <img src="../images/menu icon.png" alt="Menu" class="menu-icon" onclick="toggleDropdown()">
                            <div class="dropdown" id="dropdownMenu">
                                <div class="dropdown-content" onclick="handleOptionClick('Option 1')">Edit</div>
                                <div class="dropdown-content" onclick="handleOptionClick('Option 2')">Print</div>
                                <div class="dropdown-content" onclick="handleOptionClick('Option 3')">Delete</div>
                            </div>
                        </td>
                    </tr>
                </table>
            </div>
        </section>
    </div>

    <!-- Modal for Adding Invoice -->
    <div id="invoiceModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeModalBtn">&times;</span>
            <h2>Add Invoice</h2>
            <form id="invoiceForm">
                <h3>Patient Information</h3>
                <label for="patientSelect">Select Patient:</label>
                <select id="patientSelect" required>
                    <option value="" disabled selected>Select a patient</option>
                </select>

                <label for="email">Email:</label>
                <input type="email" id="email" placeholder="jones@example.com" required readonly class="gen-info">

                <label for="contact">Contact:</label>
                <input type="tel" id="contact" placeholder="09667721883" required readonly class="gen-info">

                <label for="address">Address:</label>
                <input type="text" id="address" placeholder="Talon-Talon, Zamboanga City" required readonly
                    class="gen-info">

                <div class="modal-actions">
                    <button type="button" id="cancelBtn">Cancel</button>
                    <button type="submit">Next</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal for Invoice Details -->
    <div id="invoiceDetailsModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeDetailsModalBtn">&times;</span>
            <h2>Add Invoice</h2>
            <form id="invoiceDetailsForm">
                <h3>Invoice Details</h3>
                <h5>Fill Invoice Details</h5>
                <label for="invoiceNumber">Invoice #:</label>
                <input type="text" id="invoiceNumber" required>

                <label for="paymentMethod">Payment Method:</label>
                <select id="paymentMethod" required>
                    <option value="" disabled selected>Select Payment</option>
                    <option value="cash">Cash</option>
                    <option value="credit">Credit Card</option>
                    <option value="debit">Debit Card</option>
                </select>

                <label for="invoiceDate">Invoice Date:</label>
                <input type="date" id="invoiceDate" required>

                <label for="description">Description (Optional):</label>
                <textarea id="description" rows="4"></textarea>

                <div class="modal-actions">
                    <button type="button" id="backBtn">Back</button>
                    <button type="submit">Next</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal for Order Summary -->
    <div id="orderSummaryModal" class="modal">
    <div class="modal-content">
        <span class="close" id="closeOrderSummaryModalBtn">&times;</span>
        <h2>Add Invoice</h2>
        <form id="orderSummaryForm">
            <h3>Order Summary</h3>
            <h5>Services</h5>

            <div id="serviceContainer">
                <!-- Initial dropdown (no remove button) -->
                <div class="service-entry">
                    <label for="service">Select a Service:</label>
                    <select name="service[]" class="service-dropdown">
                        <option value="" disabled selected>Select a service</option>
                        <option value="Oral Prophylaxis">Oral Prophylaxis</option>
                        <option value="Tooth Restoration">Tooth Restoration</option>
                        <option value="Tooth Extraction">Tooth Extraction</option>
                        <option value="Dentures">Dentures</option>
                        <option value="Veneer">Veneer</option>
                        <option value="Tooth Whitening">Tooth Whitening</option>
                        <option value="Root Canal Therapy">Root Canal Therapy</option>
                        <option value="Fixed Bridge">Fixed Bridge</option>
                        <option value="Jacket Crown">Jacket Crown</option>
                        <option value="Retainer">Retainer</option>
                        <option value="Braces">Braces</option>
                    </select>
                </div>
            </div>

            <button type="button" id="addServiceBtn">Add Another Service</button>

        </form>




<label for="price">Price</label>
<input type="number" id="price" name="price" step="0.01" min="0" placeholder="Enter price" />


                <label for="descriptionSummary">Description (Optional):</label>
                <textarea id="descriptionSummary" rows="4"></textarea>

                <div class="modal-actions">
                    <button type="button" id="orderBackBtn">Back</button>
                    <button type="submit">Add</button>
                </div>
            </form>
        </div>
    </div>
    </div>
</div>
    <!-- Modal for Receipt Summary -->
    <div id="receiptModal" class="modal">
        <div class="modal-content">
            <span class="close" id="closeReceiptModalBtn">&times;</span>
            <h2>Receipt Summary</h2>
            <p>Invoice # <span id="receiptInvoiceNumber"></span></p>
            <p>Payment Method: <span id="receiptPaymentMethod"></span></p>
            <p>Invoice Date: <span id="receiptInvoiceDate"></span></p>
            <p>Description: <span id="receiptDescription"></span></p>
            <h4>NOTE: TO BE EDITED ANG CONTENT DI KO GETS ANG MGA INPUTS SA FIGMA</h4>

            <div class="modal-actions">
                <button type="button" id="closeReceiptBtn">Close</button>
            </div>
        </div>
    </div>
    <script>
document.getElementById("addServiceBtn").addEventListener("click", function () {
    let serviceContainer = document.getElementById("serviceContainer");

    // Create a new service entry div
    let serviceEntry = document.createElement("div");
    serviceEntry.classList.add("service-entry");

    // Create a new select dropdown
    let select = document.createElement("select");
    select.name = "service[]";
    select.classList.add("service-dropdown");

    // Default option
    let defaultOption = document.createElement("option");
    defaultOption.value = "";
    defaultOption.disabled = true;
    defaultOption.selected = true;
    defaultOption.textContent = "Select a service";
    select.appendChild(defaultOption);

    // Service options
    let services = [
        "Oral Prophylaxis", "Tooth Restoration", "Tooth Extraction", "Dentures",
        "Veneer", "Tooth Whitening", "Root Canal Therapy", "Fixed Bridge",
        "Jacket Crown", "Retainer", "Braces"
    ];

    services.forEach(service => {
        let option = document.createElement("option");
        option.value = service;
        option.textContent = service;
        select.appendChild(option);
    });

    // Create a remove button (only for new dropdowns)
    let removeBtn = document.createElement("button");
    removeBtn.type = "button";
    removeBtn.textContent = "Remove";
    removeBtn.classList.add("removeServiceBtn");
    removeBtn.style.marginLeft = "10px";
    removeBtn.onclick = function () {
        serviceContainer.removeChild(serviceEntry);
    };

    // Append elements
    serviceEntry.appendChild(select);
    serviceEntry.appendChild(removeBtn);
    serviceContainer.appendChild(serviceEntry);
});
</script>
</body>

</html>