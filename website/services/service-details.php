<?php
include '../../database.php';

if (isset($_GET['id'])) {
    $serviceId = $_GET['id'];
    $serviceQuery = $conn->prepare("SELECT * FROM services WHERE services_id = ?");
    $serviceQuery->bind_param('i', $serviceId);
    $serviceQuery->execute();
    $result = $serviceQuery->get_result();
    $service = $result->fetch_assoc();

    if ($service) {
        ?>
        <!DOCTYPE html>
        <html lang="en">

        <head>
            <link rel="stylesheet" href="../../website/website.css">
            <link rel="stylesheet" href="../../website/services.css">
            <link rel="preconnect" href="https://fonts.googleapis.com">
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo $service['name']; ?></title>
        </head>

        <body>
            <div> <?php include '../header.php'; ?></div>


            <!-- Service Details -->
            <div class="service-details">
                <h1><?php echo $service['name']; ?></h1>
                <div class="service-content">
                    <div class="image-container" style="background-image: url('../<?php echo $service['image_path']; ?>');">
                    </div>
                    <p><?php echo $service['description']; ?></p>
                </div>
                <a href="#" class="appointment-button">Make an Appointment</a>
            </div>

            <script>
                document.addEventListener("DOMContentLoaded", function () {
                    const toggle = document.querySelector(".services-toggle a");
                    const dropdown = document.getElementById("myDropdown");

                    toggle.addEventListener("click", function (e) {
                        e.preventDefault();
                        dropdown.classList.toggle("show");
                    });

                    window.addEventListener("click", function (event) {
                        if (!event.target.closest('.dropdown')) {
                            dropdown.classList.remove("show");
                        }
                    });
                });
            </script>

        </body>

        </html>
        <?php
    } else {
        echo "Service not found.";
    }
} else {
    echo "Invalid service ID.";
}
?>