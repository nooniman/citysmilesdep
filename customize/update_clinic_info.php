<?php
include '../database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $flexible_schedule = mysqli_real_escape_string($conn, $_POST['flexible_schedule']);
    $experience = mysqli_real_escape_string($conn, $_POST['experience']);
    $transparent_pricing = mysqli_real_escape_string($conn, $_POST['transparent_pricing']);
    $easy_appointment = mysqli_real_escape_string($conn, $_POST['easy_appointment']);

    $query = "UPDATE clinic_info SET 
              flexible_schedule='$flexible_schedule', 
              experience='$experience', 
              transparent_pricing='$transparent_pricing', 
              easy_appointment='$easy_appointment'
              WHERE id=1"; // Assuming there's only one row

    if (mysqli_query($conn, $query)) {
        echo "Clinic information updated successfully!";
    } else {
        echo "Error updating clinic information: " . mysqli_error($conn);
    }

    mysqli_close($conn);
}
?>