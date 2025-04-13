<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../sidebar/sidebar.css">
    <link rel="stylesheet" href="add_prescription.css">
    <title>Prescription</title>
</head>
<body>
<?php include '../sidebar/sidebar.php'; ?>

<div class="content">
<div class="prescription-text">
            <h1>Prescription</h1>
            </div>
            <!-- First Container -->
            <div class="header-container">
                <div class="text-prescription">
                    <h1>Prescription List</h1>
                </div>
                <div class="backpatient-container">
                <a href="prescription.php">
                   <button class="backprescription-button">Back</button>
                </a>
                </div>
            </div>

            <!-- Second Container -->
            <div class="container">
                <div class="top">
                    <label for="">Date:</label>
                    <input type="date" placeholder="Choose Date">
                    <label for="dropdown">Select Patient:</label>
                    <select id="dropdown" name="options">
                        <option value="" disabled selected>Select Patient name</option>
                        <option value="option1">Option 1</option>
                        <option value="option2">Option 2</option>
                        <option value="option3">Option 3</option>
                        <option value="option4">Option 4</option>
                    </select>
                </div>

                <div class="med-notes">
                        <div class="input-container">
                            <label for="medicine">Medicine</label>
                            <textarea name="medicine" id="medicine" rows="8" placeholder="Enter Prescribe Medicine here..."></textarea>
                        </div>
                        <div class="input-container">
                            <label for="notes">Notes</label>
                            <textarea name="notes" id="notes" rows="8" placeholder="Enter Notes here..."></textarea>
                        </div>
                    </div>
                    <div class="button-container">
                    <button class="save-button">Save Prescription</button>
                </div>
            </div>
</div>



</body>
</html>