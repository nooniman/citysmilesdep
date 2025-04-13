<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
include_once '../database.php';

// Fetch user details
$patient_id = $_SESSION['user_id'] ?? 0;
$userName = "Guest User";
$userRole = "Patient";

if ($patient_id > 0) {
    $userQuery = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $userQuery->bind_param("i", $patient_id);
    $userQuery->execute();
    $user = $userQuery->get_result()->fetch_assoc();

    if ($user) {
        $userName = $user['first_name'] . ' ' . $user['last_name'];
        $userRole = ucfirst($user['role']); // Capitalize first letter
    }
}

if (!isset($user) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $user_query = "SELECT * FROM users WHERE id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();
    $user = $user_result->fetch_assoc();
}
?>

<!-- Bootstrap CSS should be loaded first -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">

<style>
    /* Navbar Styles */
    .nav-container {
        width: 100%;
        font-family: 'Inter', sans-serif;
    }

    .navbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 12px 24px;
        background: linear-gradient(135deg, rgba(41, 120, 89, 0.7), rgba(123, 50, 171, 0.7));
        backdrop-filter: blur(10px);
        -webkit-backdrop-filter: blur(10px);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        z-index: 1000;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .navbar-brand {
        display: flex;
        align-items: center;
        gap: 12px;
    }

    .navbar-brand img {
        height: 40px;
        width: auto;
    }

    .navbar-brand h1 {
        color: white;
        font-size: 20px;
        font-weight: 600;
        margin: 0;
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.2);
    }

    .navbar-menu {
        display: flex;
        justify-content: center;
        flex-grow: 1;
    }

    .menu-items {
        display: flex;
        list-style: none;
        margin: 0;
        padding: 0;
        gap: 6px;
        /* Reduced from 10px if needed */
    }

    .menu-items li a {
        display: flex;
        align-items: center;
        color: white;
        text-decoration: none;
        padding: 6px 10px;
        /* Reduced padding from 10px 16px */
        border-radius: 5px;
        font-weight: 500;
        font-size: 13px;
        /* Added explicit font size */
        transition: all 0.3s ease;
    }

    .menu-items li a:hover {
        background-color: rgba(52, 168, 83, 0.5) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }

    .menu-items li a.active {
        background: rgba(255, 255, 255, 0.25);
        font-weight: 600;
    }

    .menu-icon {
        width: 14px;
        /* Reduced from 18px */
        height: 14px;
        /* Reduced from 18px */
        margin-right: 4px;
        /* Reduced from 8px */
        filter: brightness(0) invert(1);
    }

    .navbar-profile {
        display: flex;
        align-items: center;
        gap: 16px;
    }

    .notification-icon {
        width: 20px;
        height: 20px;
        cursor: pointer;
        filter: brightness(0) invert(1);
        transition: transform 0.2s ease;
    }

    .notification-icon:hover {
        transform: scale(1.1);
    }

    .profile {
        display: flex;
        align-items: center;
        gap: 10px;
        position: relative;
        /* Ensure proper dropdown positioning */
    }

    .profile-name {
        display: flex;
        flex-direction: column;
        text-align: right;
    }

    .profile-name strong {
        color: white;
        font-size: 14px;
        font-weight: 600;
    }

    .profile-name span {
        color: rgba(255, 255, 255, 0.8);
        font-size: 12px;
    }

    .profile-image {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid rgba(255, 255, 255, 0.7);
        transition: transform 0.2s;
        cursor: pointer;
    }

    .profile-image:hover {
        transform: scale(1.05);
    }

    /* Dropdown menu styling */
    .dropdown-menu {
        background-color: white;
        border-radius: 8px;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        padding: 8px 0;
        border: none;
        margin-top: 10px;
    }

    .dropdown-item {
        padding: 8px 20px;
        font-size: 14px;
        color: #333;
        transition: background-color 0.2s;
    }

    .dropdown-item:hover {
        background-color: rgba(41, 120, 89, 0.1);
        color: #297859;
    }

    .dropdown-divider {
        margin: 5px 0;
        border-top: 1px solid #f0f0f0;
    }

    /* Content adjustment */
    .content {
        padding-top: 80px !important;
        margin-left: 0 !important;
    }

    /* Mobile menu */
    .menu-toggle {
        display: none;
        background: none;
        border: none;
        color: white;
        font-size: 24px;
        cursor: pointer;
    }

    /* Hide the Bootstrap dropdown toggle arrow */
    .dropdown-toggle::after {
        display: none !important;
    }

    .website-btn {
        margin-left: 10px;
    }

    .website-btn:hover {
        background-color: rgba(52, 168, 83, 0.5) !important;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    }


    @media (max-width: 992px) {
        .navbar {
            padding: 10px 16px;
        }

        .website-btn {
            margin-top: 10px;
            margin-left: 0;
        }

        .navbar-menu {
            position: fixed;
            top: 64px;
            left: 0;
            right: 0;
            background: linear-gradient(135deg, rgba(41, 120, 89, 0.9), rgba(123, 50, 171, 0.9));
            padding: 20px;
            transform: translateY(-100%);
            transition: transform 0.3s ease;
            z-index: 999;
            display: block;
        }

        .navbar-menu.active {
            transform: translateY(0);
        }

        .menu-items {
            flex-direction: column;
            gap: 5px;
        }

        .menu-toggle {
            display: block;
        }

        .navbar-brand h1 {
            font-size: 18px;
        }

        .profile-name {
            display: none;
        }

        .menu-items li a {
            display: flex;
            align-items: center;
            color: white !important;
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 6px;
            font-weight: 500 !important;
            /* Added !important */
            transition: all 0.3s ease;
        }

        .menu-items li a:hover {
            background-color: rgba(52, 168, 83, 0.5) !important;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .menu-items li a.active {
            background: rgba(255, 255, 255, 0.25);
            font-weight: 600 !important;
            /* Added !important */
        }
    }
</style>

<div class="nav-container">
    <nav class="navbar">
        <div class="navbar-brand">
            <img src="../images/Screenshot__522_-removebg-preview.png" alt="Logo">
            <h1>City Smile Dental Clinic</h1>
        </div>

        <button class="menu-toggle" onclick="toggleMenu()">
            <i class="fas fa-bars"></i>
        </button>

        <div class="navbar-menu" id="navMenu">
            <ul class="menu-items">
                <li><a href="dashboard.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
                        <img src="../icons/home.png" class="menu-icon"> Dashboard
                    </a></li>
                <li><a href="appointment.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'appointment.php' ? 'active' : ''; ?>">
                        <img src="../icons/appointment.png" class="menu-icon"> Appointment
                    </a></li>
                <li><a href="family_members.php"
                        class="<?php echo basename($_SERVER['PHP_SELF']) == 'family_members.php' ? 'active' : ''; ?>">
                        <img src="../icons/family.png" class="menu-icon"> Family Members
                    </a></li>
                <li><a href="../website/website.php" class="website-btn">
                        <i class="fas fa-globe menu-icon"></i> Back to Website
                    </a></li>
            </ul>
        </div>

        <div class="navbar-profile">
            <div class="notification">
                <img src="../icons/notif.png" alt="Notification" class="notification-icon">
            </div>

            <div class="profile dropdown"> <!-- Added dropdown class directly to the container -->
                <div class="profile-name">
                    <strong><?php echo htmlspecialchars($userName); ?></strong>
                    <span><?php echo htmlspecialchars($userRole); ?></span>
                </div>

                <a href="#" class="dropdown-toggle" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false"
                    role="button">
                    <img src="<?php echo !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : '../icons/profile.png'; ?>"
                        alt="Profile Picture" class="profile-image">
                </a>
                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="profileDropdown">
                    <li><a class="dropdown-item" href="account.php">My Profile</a></li>
                    <li>
                        <hr class="dropdown-divider">
                    </li>
                    <li><a class="dropdown-item" href="../login/logout.php">Sign Out</a></li>
                </ul>
            </div>
        </div>
    </nav>
</div>

<!-- Add Bootstrap JS at the end -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
    function toggleMenu() {
        const navMenu = document.getElementById('navMenu');
        navMenu.classList.toggle('active');
    }

    // Hide menu when clicking outside
    document.addEventListener('click', function (event) {
        const navMenu = document.getElementById('navMenu');
        const menuToggle = document.querySelector('.menu-toggle');

        if (navMenu.classList.contains('active') &&
            !navMenu.contains(event.target) &&
            event.target !== menuToggle) {
            navMenu.classList.remove('active');
        }
    });

    // Initialize Bootstrap dropdowns - Using a more robust approach
    document.addEventListener('DOMContentLoaded', function () {
        setTimeout(function () {
            // Give Bootstrap time to load
            try {
                var dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
                dropdownElementList.map(function (element) {
                    return new bootstrap.Dropdown(element);
                });
                console.log('Dropdown initialized');
            } catch (e) {
                console.error('Dropdown initialization error:', e);
            }
        }, 100);
    });
</script>