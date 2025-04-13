<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Treatment Modal</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        /* Modal Styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            width: 400px;
            box-shadow: 0px 4px 8px rgba(0, 0, 0, 0.2);
            border-top: 5px solid green;
        }

        .modal h2 {
            margin-bottom: 10px;
        }

        .modal label {
            display: block;
            text-align: left;
            margin-top: 10px;
            font-weight: bold;
        }

        .modal input, .modal textarea, .modal select {
            width: 100%;
            padding: 8px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .modal-buttons {
            display: flex;
            justify-content: space-between;
            margin-top: 15px;
        }

        .btn {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
        }

        .btn.cancel {
            background-color: red;
            color: white;
        }

        .btn.done {
            background-color: green;
            color: white;
        }

        .btn:hover {
            opacity: 0.8;
        }
    </style>
</head>
<body>

<!-- Button to Open Modal -->
<button id="openModalBtn">Add Treatment</button>

<!-- Modal Structure -->
<div id="treatmentModal" class="modal">
    <div class="modal-content">
        <h2>Add Treatment</h2>

        <label for="patient">Select Patient</label>
        <select id="patient">
            <option>Select Patient</option>
            <option>John Doe</option>
            <option>Jane Smith</option>
        </select>

        <label for="dateVisit">Date Visit</label>
        <input type="date" id="dateVisit">

        <label for="treatment">Treatment</label>
        <input type="text" id="treatment" disabled placeholder="Auto-filled">

        <label for="teeth">Number of Teeth/s</label>
        <input type="number" id="teeth">

        <label for="fees">Fees</label>
        <input type="number" id="fees">

        <label for="remarks">Remarks</label>
        <input type="text" id="remarks">

        <label for="description">Description</label>
        <textarea id="description"></textarea>

        <div class="modal-buttons">
            <button class="btn cancel" onclick="closeModal()">Cancel</button>
            <button class="btn done">Done</button>
        </div>
    </div>
</div>

<script>
    // Get modal and button elements
    const modal = document.getElementById("treatmentModal");
    const openModalBtn = document.getElementById("openModalBtn");

    // Open Modal Function
    openModalBtn.addEventListener("click", function() {
        modal.style.display = "flex";
    });

    // Close Modal Function
    function closeModal() {
        modal.style.display = "none";
    }

    // Close modal if clicked outside
    window.onclick = function(event) {
        if (event.target === modal) {
            closeModal();
        }
    };
</script>

</body>
</html>
