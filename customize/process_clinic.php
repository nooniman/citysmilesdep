<?php
include '../database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $validDays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
        $startDay = in_array($_POST['start_day'], $validDays) ? $_POST['start_day'] : 'Mon';
        $endDay = in_array($_POST['end_day'], $validDays) ? $_POST['end_day'] : 'Fri';
        $days = "$startDay-$endDay";

        // Convert to 12-hour format
        $opening_12h = date("h:i A", strtotime($_POST['opening_time']));
        $closing_12h = date("h:i A", strtotime($_POST['closing_time']));
        $hours = "$opening_12h - $closing_12h";

        $phone = preg_replace('/[^0-9]/', '', $_POST['phone']);
        if (strlen($phone) < 10 || strlen($phone) > 15) {
            throw new Exception('Invalid phone number');
        }

        $address = $conn->real_escape_string($_POST['address']);

        $stmt = $conn->prepare("UPDATE clinic_info SET days=?, hours=?, phone=?, address=? WHERE id=1");
        $stmt->bind_param("ssss", $days, $hours, $phone, $address);

        if (!$stmt->execute()) {
            throw new Exception($stmt->error);
        }

        echo json_encode(['status' => 'success', 'message' => 'Clinic info updated successfully']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
    }
}
?>