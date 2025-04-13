<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="../website/website.css">
    <link rel="stylesheet" href="../website/services.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About</title>
</head>

<body>
    <div class="header">
        <div class="logo-wrapper">
            <a href="../website/website.php"><img src="../images/Screenshot__522_-removebg-preview.png" alt="Logo"
                    class="logo"></a>
            <h1>City Smile Dental Clinic</h1>
        </div>
        <ul>
            <ul><a href="../book/bookmain.html">HOME</a></ul>
            <div class="dropdown">
                <ul><a  onclick="myFunction()">SERVICES <img
                            style="height: 12px; padding-left:5px;" src="../icons/download.png"> </a></ul>
                <div id="myDropdown" class="dropdown-content">
                    <?php
                    include '../database.php';
                    $servicesQuery = $conn->query("SELECT * FROM services");
                    while ($service = $servicesQuery->fetch_assoc()) {
                        echo '<a href="../website/services/service-details.php?id=' . $service['services_id'] . '"><h3 class="' . $service['services_id'] . '">' . $service['name'] . '</h3></a>';
                    }
                    ?>
                </div>
            </div>
            <ul><a href="#">ABOUT</a></ul>
        </ul>
        <div>
            <a href="../login/login.php" class="login">Login</a>
        </div>
    </div>
    <div class="container">
    <!-- Title -->
    <h2 class="title">Know More About City Smile Dental Clinic</h2>

    <!-- Content -->
    <div class="content">
        <!-- Left Side: Image -->
        <div class="image-container">
            <img src="../images/image 135_enhanced.png" alt="Dentist with patient">
        </div>

        <!-- Right Side: Clinic Info -->
        <div class="info-section">
            <div class="info-header">Clinic Info</div>
            <div class="info-content">
                <p><strong>Monday-Friday</strong> <span>9:00AM - 5:00PM</span></p>
                <p><strong>Location:</strong> <span>Central II, Zone II, Zamboanga City</span></p>
                <p><strong>Contact:</strong> <span>+631234567892</span></p>
                <p><strong>City Smile Dental Clinic by:</strong><br> Dr. Almarhiza Susulan Kalinggalan</p>
                <p><strong>Facebook Page:</strong> <a href="https://www.facebook.com/profile.php?id=100090839152141" target="_blank">Visit Page</a></p>
            </div>
        </div>
    </div>
</div>
        
    </body>
    </html>