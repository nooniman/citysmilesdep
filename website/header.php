<?php
include_once '../../database.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Fetch user data if logged in
if (isset($_SESSION['user_id']) && !isset($profilePicture)) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $profilePicture = $row['profile_picture'];
    }
}
?>

<div class="header">
    <div class="logo-wrapper">
        <a href="../website.php"><img src="../../images/Screenshot__522_-removebg-preview.png" alt="Logo"
                class="logo"></a>
        <h1>City Smile Dental Clinic</h1>
    </div>
    <ul>
        <ul><a href="../book/bookmain.html">BOOKING</a></ul>
        <div class="dropdown">
            <li class="services-toggle">
                <a href="javascript:void(0)">SERVICES <img style="height: 12px; padding-left:5px;"
                        src="../../icons/download.png"></a>
                <div id="myDropdown" class="dropdown-content">
                    <?php
                    $servicesQuery = $conn->query("SELECT * FROM services");
                    while ($serviceItem = $servicesQuery->fetch_assoc()) { // Changed variable to $serviceItem
                        echo '<a href="../services/service-details.php?id=' . $serviceItem['services_id'] . '"><h3>' . $serviceItem['name'] . '</h3></a>';
                    }
                    ?>
                </div>
            </li>
        </div>
        <ul><a href="../about/about.html">ABOUT</a></ul>
    </ul>
    <div>
        <?php if (isset($_SESSION['user_id'])): ?>
            <a href="<?php echo isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin' ? '../admindashboard/dashboard.php' : '../userdashboard/appointment.php'; ?>"
                class="login">
                <img src="<?php echo !empty($profilePicture) ? htmlspecialchars($profilePicture) : '../icons/profile.png'; ?>"
                    alt="Profile"
                    style="width: 30px; height: 30px; vertical-align: middle; margin-right: 5px; border-radius: 50%; object-fit: cover;">
                My Account
            </a>
        <?php else: ?>
            <a href="../login/login.php" class="login">Login</a>
        <?php endif; ?>
    </div>
</div>