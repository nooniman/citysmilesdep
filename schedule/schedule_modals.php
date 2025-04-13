<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="schedule_modal.css">
    <title>Document</title>
</head>

<body>
    <!-- Add Schedule Modal -->
    <div class="modal" id="addschedule" style="display:none;">
        <div class="modal-content">
            <h2>Add Schedule</h2>
            <form method="POST">
                <?php while ($doctor = mysqli_fetch_assoc($doctorsResult)) { ?>
                    <img src="../images/<?php echo $doctor['image']; ?>" alt="Admin Photo"
                        style="width: 100px; height: 100px; border-radius: 50%; display: block; margin: 0 auto 10px;">
                    <select name="doctor" required>
                        <option value="" disabled selected>Select a Doctor</option>
                        <option value="<?php echo $doctor['id']; ?>">
                            <?php echo "Dr. " . $doctor['first_name'] . " " . $doctor['last_name']; ?>
                        </option>
                    <?php } ?>
                </select>
                <input type="date" name="date" required>
                <div class="time-inputs">
                    <input type="time" name="start_time" required> to
                    <input type="time" name="end_time" required>
                </div>
                <div class="button-container">
                    <button type="button" class="cancel" onclick="closeModal('addschedule')">Cancel</button>
                    <button type="submit" name="add_schedule" class="done">Done</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Schedule Modal -->
    <div class="modal" id="editschedule" style="display:none;">
        <div class="modal-content">
            <h2>Edit Schedule</h2>
            <form method="POST">
                <input type="hidden" name="schedule_id" id="edit_schedule_id">
                <select name="doctor" id="edit_doctor" required>
                    <?php while ($doctor = mysqli_fetch_assoc($doctorsResult)) { ?>
                        <option value="<?php echo $doctor['id']; ?>">
                            <?php echo "Dr. " . $doctor['first_name'] . " " . $doctor['last_name']; ?>
                        </option>
                    <?php } ?>
                </select>
                <input type="date" name="date" id="edit_date" required>
                <div class="time-inputs">
                    <input type="time" name="start_time" id="edit_start_time" required> to
                    <input type="time" name="end_time" id="edit_end_time" required>
                </div>
                <div class="button-container">
                    <button type="button" class="cancel" onclick="closeModal('editschedule')">Cancel</button>
                    <button type="submit" name="edit_schedule" class="done">Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal" id="confirmationModal" style="display:none;">
        <div class="modal-content">
            <h2>Are you sure you want to remove?</h2>
            <div class="button-container">
                <button class="cancel" onclick="closeConfirmation()">Cancel</button>
                <button class="done" onclick="deleteSchedule()">Yes</button>
            </div>
        </div>
    </div>
</body>

</html>