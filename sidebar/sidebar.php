<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Default values in case session variables are not set
$userName = "Guest User";
$userRole = "Visitor";

// Check if user is logged in and fetch name and role from session
if (isset($_SESSION['user_id'])) {
    // Connect to the database to get fresh user data
    include_once '../database.php';

    $userId = $_SESSION['user_id'];
    $query = "SELECT CONCAT(first_name, ' ', last_name) AS full_name, role, profile_picture FROM users WHERE id = ?";

    if ($stmt = mysqli_prepare($conn, $query)) {
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            $userName = $row['full_name'];
            $userRole = ucfirst($row['role']); // Capitalize first letter
            
            // Store profile picture in session for consistent access
            if (!empty($row['profile_picture'])) {
                $_SESSION['profile_picture'] = $row['profile_picture'];
            }
        }

        mysqli_stmt_close($stmt);
    }
}

// Get current page to highlight active menu item
$current_page = basename($_SERVER['PHP_SELF']);
?>

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<style>
    /* Scoped sidebar styles - these won't affect the rest of the application */
    .cs-sidebar-wrapper {
        --cs-primary-green: #3cb371;
        --cs-light-green: #9aeaa1;
        --cs-dark-green: #297859;
        --cs-primary-lilac: #9d7ded;
        --cs-light-lilac: #e0d4f9;
        --cs-dark-lilac: #7B32AB;
        --cs-white: #ffffff;
        --cs-off-white: #f8f9fa;
        --cs-gray-100: #f8f9fa;
        --cs-gray-200: #e9ecef;
        --cs-gray-300: #dee2e6;
        --cs-gray-400: #ced4da;
        --cs-gray-500: #adb5bd;
        --cs-gray-600: #6c757d;
        --cs-gray-700: #495057;
        --cs-gray-800: #343a40;
        --cs-gray-900: #212529;
        --cs-transition: all 0.3s ease;
        --cs-header-height: 70px;
        --cs-sidebar-width: 250px;
        --cs-sidebar-collapsed-width: 70px;
        --cs-border-radius: 12px;
        --cs-box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    }

    /* Let's ensure we're not affecting the body styling */
    /* Main content container */
    .cs-content {
        margin-left: var(--cs-sidebar-width);
        padding: calc(var(--cs-header-height) + 20px) 24px 24px;
        transition: var(--cs-transition);
    }

    body.cs-sidebar-collapsed .cs-content {
        margin-left: var(--cs-sidebar-collapsed-width);
    }

    #profileDropdown::after {
        display: none !important;
    }

    /* Header styles */
    .cs-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: var(--cs-header-height);
        background: white;
        box-shadow: 0 1px 10px rgba(0, 0, 0, 0.05);
        display: flex;
        align-items: center;
        padding: 0 24px 0 calc(var(--cs-sidebar-width) + 24px);
        z-index: 100;
        transition: var(--cs-transition);
    }

    body.cs-sidebar-collapsed .cs-header {
        padding-left: calc(var(--cs-sidebar-collapsed-width) + 24px);
    }

    .cs-header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        width: 100%;
    }

    .cs-page-title {
        font-size: 1.25rem;
        font-weight: 600;
        color: var(--cs-dark-lilac);
    }

    .cs-header .cs-logo-section {
        display: none;
        /* Hidden by default, only shown in mobile */
    }

    /* Right side elements */
    .cs-right-side {
        display: flex;
        align-items: center;
        gap: 20px;
    }

    .cs-notification {
        position: relative;
    }

    .cs-notification-icon {
        width: 24px;
        height: 24px;
        cursor: pointer;
        color: var(--cs-gray-600);
        transition: var(--cs-transition);
    }

    .cs-notification-icon:hover {
        color: var(--cs-primary-lilac);
    }

    .cs-notification-badge {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: var(--cs-primary-lilac);
        color: white;
        border-radius: 50%;
        width: 16px;
        height: 16px;
        font-size: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
    }

    .cs-profile {
        display: flex;
    align-items: center;
    gap: 15px !important; /* Increased from 8px to 12px and added !important */
    cursor: pointer;
    padding: 6px 12px;
    border-radius: 50px;
    transition: var(--cs-transition);
    }

    .cs-profile:hover {
        background-color: var(--cs-gray-100);
    }
    .cs-profile {
    text-decoration: none !important;
    }
    .cs-profile-image {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        object-fit: cover;
        border: 2px solid var(--cs-light-lilac);
    }

    .cs-profile-info {
        display: flex;
        flex-direction: column;
    }

    .cs-profile-name {
        font-size: 0.875rem;
        font-weight: 600;
        color: var(--cs-gray-900);
        white-space: nowrap;
        line-height: 1.2;
    }

    .cs-profile-role {
        font-size: 0.75rem;
        color: var(--cs-gray-600);
        white-space: nowrap;
    }

    /* Sidebar styles */
    .cs-sidebar {
        position: fixed;
        top: 0;
        left: 0;
        height: 100vh;
        width: var(--cs-sidebar-width);
        background: linear-gradient(to bottom, var(--cs-dark-green), #1d5842);
        color: white;
        z-index: 200;
        transition: var(--cs-transition);
        box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        display: flex;
        flex-direction: column;
    }

    body.cs-sidebar-collapsed .cs-sidebar {
        width: var(--cs-sidebar-collapsed-width);
    }

    .cs-sidebar-header {
        height: var(--cs-header-height);
        padding: 0 20px;
        display: flex;
        align-items: center;
        border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    }

    .cs-logo-wrapper {
        display: flex;
        align-items: center;
        gap: 12px;
        width: 100%;
        overflow: hidden;
    }

    .cs-logo {
        height: 40px;
        width: auto;
        transition: var(--cs-transition);
        flex-shrink: 0;
    }

    .cs-logo-text {
        font-size: 1.125rem;
        font-weight: 600;
        color: white;
        white-space: nowrap;
        opacity: 1;
        transition: var(--cs-transition);
    }

    body.cs-sidebar-collapsed .cs-logo-text {
        opacity: 0;
        width: 0;
    }

    .cs-sidebar-toggle {
        position: absolute;
        right: -12px;
        top: 76px;
        width: 24px;
        height: 24px;
        background-color: var(--cs-dark-green);
        border: 2px solid white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        cursor: pointer;
        z-index: 300;
        transition: var(--cs-transition);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    .cs-sidebar-toggle:hover {
        background-color: var(--cs-primary-lilac);
        transform: scale(1.1);
    }

    body.cs-sidebar-collapsed .cs-sidebar-toggle i {
        transform: rotate(180deg);
    }

    /* Menu styles */
    .cs-menu-container {
        flex-grow: 1;
        overflow-y: auto;
        padding: 15px 0;
        scrollbar-width: none;
    }

    .cs-menu-container::-webkit-scrollbar {
        display: none;
        /* Chrome, Safari, Edge */
    }

    .cs-menu-section {
        margin-bottom: 8px;
    }

    .cs-menu-section-title {
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        color: rgba(255, 255, 255, 0.6);
        padding: 0 8px;
        /* Changed from 0 20px to match menu item margins */
        margin: 12px 8px 8px;
        /* Added horizontal margin to align with menu items */
        white-space: nowrap;
        opacity: 1;
        transition: var(--cs-transition);
    }

    body.cs-sidebar-collapsed .cs-menu-section-title {
        opacity: 0;
    }

    .cs-menu-items {
        list-style: none;
        padding: 0;
        /* Ensure no default padding */
        margin: 0;
        /* Ensure no default margin */
    }

    .cs-menu-item {
        position: relative;
        margin: 4px 8px;
        border-radius: 12px;
        transition: var(--cs-transition);
    }

    .cs-menu-item.active {
        background: rgba(255, 255, 255, 0.1);
    }

    .cs-menu-item.active::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        height: 100%;
        width: 4px;
        background-color: var(--cs-primary-lilac);
        border-radius: 0 4px 4px 0;
    }

    .cs-menu-item a {
        display: flex;
        align-items: center;
        padding: 12px;
        text-decoration: none;
        color: rgba(255, 255, 255, 0.85);
        font-size: 0.9rem;
        font-weight: 500;
        border-radius: 12px;
        transition: var(--cs-transition);
    }

    .cs-menu-item:hover {
        background-color: rgba(255, 255, 255, 0.1);
    }

    .cs-menu-item.active a {
        color: white;
    }

    .cs-menu-icon-wrapper {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 36px;
        height: 36px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.1);
        margin-right: 12px;
        transition: var(--cs-transition);
        flex-shrink: 0;
    }

    .cs-menu-item.active .cs-menu-icon-wrapper {
        background: var(--cs-primary-lilac);
    }

    .cs-menu-icon {
        width: 18px;
        height: 18px;
        opacity: 0.85;
        transition: var(--cs-transition);
    }

    .cs-menu-item-text {
        white-space: nowrap;
        opacity: 1;
        transition: var(--cs-transition);
    }

    body.cs-sidebar-collapsed .cs-menu-item-text {
        opacity: 0;
        width: 0;
        margin-left: -10px;
    }

    body.cs-sidebar-collapsed .cs-menu-icon-wrapper {
        margin-right: 0;
    }

    /* Footer section */
    .cs-sidebar-footer {
        padding: 16px;
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        margin-top: auto;
    }

    .cs-sidebar-footer-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

   
    /* Tooltips for collapsed sidebar */
    body.cs-sidebar-collapsed .cs-menu-item a::after,
    body.cs-sidebar-collapsed .cs-logout-button::after {
        content: attr(data-tooltip);
        position: absolute;
        left: 100%;
        top: 50%;
        transform: translateY(-50%);
        background-color: var(--cs-gray-800);
        color: white;
        padding: 6px 10px;
        border-radius: 6px;
        font-size: 0.75rem;
        white-space: nowrap;
        opacity: 0;
        pointer-events: none;
        transition: opacity 0.2s ease;
        z-index: 1000;
    }

    body.cs-sidebar-collapsed .cs-menu-item:hover a::after,
    body.cs-sidebar-collapsed .cs-sidebar-footer:hover .cs-logout-button::after {
        opacity: 1;
    }

    /* Mobile responsive styles */
    @media (max-width: 992px) {
        .cs-sidebar-wrapper {
            --cs-sidebar-width: 0px;
            --cs-sidebar-collapsed-width: 0px;
        }

        body.cs-sidebar-open .cs-sidebar {
            width: 280px;
        }

        .cs-sidebar {
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.3);
        }

        .cs-header {
            padding-left: 24px;
        }

        .cs-header .cs-logo-section {
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .cs-header .cs-logo {
            height: 36px;
        }

        .cs-header .cs-logo-text {
            font-size: 1.2rem;
            font-weight: 600;
            color: var(--cs-dark-lilac);
        }

        .cs-menu-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            border-radius: 10px;
            background-color: var(--cs-gray-100);
            cursor: pointer;
            transition: var(--cs-transition);
        }

        .cs-menu-toggle:hover {
            background-color: var(--cs-gray-200);
        }

        .cs-sidebar-toggle {
            display: none;
        }

        body.cs-sidebar-collapsed .cs-menu-item {
            margin-left: 8px;
            margin-right: 8px;
        }
    }

    @media (min-width: 993px) {
        .cs-menu-toggle {
            display: none;
        }
    }

    /* Dropdown styles */
    .dropdown-toggle::after {
        display: none !important;
    }

    .dropdown-menu {
        min-width: 200px;
        padding: 0.5rem 0;
        margin: 0.125rem 0 0;
        font-size: 0.875rem;
        border: none;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
        border-radius: 0.5rem;
    }

    .dropdown-item {
        padding: 0.5rem 1.25rem;
        display: flex;
        align-items: center;
        color: var(--cs-gray-700);
    }

    .dropdown-item:hover {
        background-color: rgba(123, 50, 171, 0.1);
        color: var(--cs-dark-lilac);
    }

    .dropdown-divider {
        margin: 0.25rem 0;
        border-top: 1px solid var(--cs-gray-200);
    }
    
</style>

<div class="cs-sidebar-wrapper">
    <!-- Mobile Menu Toggle Button (only visible on small screens) -->
    <div class="cs-menu-toggle" id="cs-mobile-menu-toggle">
        <i class="fas fa-bars" style="font-size: 20px;"></i>
    </div>

    <!-- Main Sidebar -->
    <aside class="cs-sidebar">
        <div class="cs-sidebar-header">
            <div class="cs-logo-wrapper">
                <img src="../images/Screenshot__522_-removebg-preview.png" alt="Logo" class="cs-logo">
                <div class="cs-logo-text">City Smiles</div>
            </div>
        </div>

        <!-- Collapse/Expand Toggle -->
        <div class="cs-sidebar-toggle" id="cs-sidebar-toggle">
            <i class="fas fa-chevron-left" style="font-size: 12px;"></i>
        </div>

        <div class="cs-menu-container">
            <div class="cs-menu-section">
                <div class="cs-menu-section-title">Main Navigation</div>
                <ul class="cs-menu-items">
                    <li class="cs-menu-item <?php echo $current_page == 'dashboard.php' ? 'active' : ''; ?>">
                        <a href="../dashboard/dashboard.php" data-tooltip="Dashboard">
                            <div class="cs-menu-icon-wrapper">
                                <img src="../icons/home.png" class="cs-menu-icon" alt="Dashboard">
                            </div>
                            <span class="cs-menu-item-text">Dashboard</span>
                        </a>
                    </li>
                    <li class="cs-menu-item <?php echo $current_page == 'appointments.php' ? 'active' : ''; ?>">
                        <a href="../appointmentlist/appointments.php" data-tooltip="Appointments">
                            <div class="cs-menu-icon-wrapper">
                                <img src="../icons/appointment.png" class="cs-menu-icon" alt="Appointments">
                            </div>
                            <span class="cs-menu-item-text">Appointments</span>
                        </a>
                    </li>
                    <li class="cs-menu-item <?php echo $current_page == 'schedule.php' ? 'active' : ''; ?>">
                        <a href="../schedule/schedule.php" data-tooltip="Schedule">
                            <div class="cs-menu-icon-wrapper">
                                <img src="../icons/image (4).png" class="cs-menu-icon" alt="Schedule">
                            </div>
                            <span class="cs-menu-item-text">Schedule</span>
                        </a>
                    </li>
                    <li class="cs-menu-item <?php echo $current_page == 'treatment.php' ? 'active' : ''; ?>">
                        <a href="../treatment/treatment.php" data-tooltip="Treatment">
                            <div class="cs-menu-icon-wrapper">
                                <img src="../icons/image (5).png" class="cs-menu-icon" alt="Treatment">
                            </div>
                            <span class="cs-menu-item-text">Treatment</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="cs-menu-section">
                <div class="cs-menu-section-title">Management</div>
                <ul class="cs-menu-items">
                    <li class="cs-menu-item <?php echo $current_page == 'patient.php' ? 'active' : ''; ?>">
                        <a href="../patients/patient.php" data-tooltip="Patients">
                            <div class="cs-menu-icon-wrapper">
                                <img src="../icons/patients.png" class="cs-menu-icon" alt="Patients">
                            </div>
                            <span class="cs-menu-item-text">Patients</span>
                        </a>
                    </li>
                    <li class="cs-menu-item <?php echo $current_page == 'staffs.php' ? 'active' : ''; ?>">
                        <a href="../staff/staffs.php" data-tooltip="Staff">
                            <div class="cs-menu-icon-wrapper">
                                <img src="../icons/staff.png" class="cs-menu-icon" alt="Staff">
                            </div>
                            <span class="cs-menu-item-text">Staff</span>
                        </a>
                    </li>
                    <li
                        class="cs-menu-item <?php echo $current_page == 'payments.php' || $current_page == 'paymentslist.php' ? 'active' : ''; ?>">
                        <a href="../payments/payments.php" data-tooltip="Payments">
                            <div class="cs-menu-icon-wrapper">
                                <img src="../icons/image (6).png" class="cs-menu-icon" alt="Payments">
                            </div>
                            <span class="cs-menu-item-text">Payments</span>
                        </a>
                    </li>
                </ul>
            </div>

            <div class="cs-menu-section">
                <div class="cs-menu-section-title">Configuration</div>
                <ul class="cs-menu-items">
                    <li class="cs-menu-item <?php echo $current_page == 'customize.php' ? 'active' : ''; ?>">
                        <a href="../customize/customize.php" data-tooltip="Customize Website">
                            <div class="cs-menu-icon-wrapper">
                                <img src="../icons/image (7).png" class="cs-menu-icon" alt="Customize">
                            </div>
                            <span class="cs-menu-item-text">Customize Website</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

      
    </aside>

    <!-- Main Header -->
    <header class="cs-header">
        <div class="cs-header-content">
            <div class="cs-logo-section">
                <!-- This logo section only shows in mobile view -->
                <div id="cs-mobile-menu-toggle" class="cs-menu-toggle">
                    <i class="fas fa-bars"></i>
                </div>
                <img src="../images/Screenshot__522_-removebg-preview.png" alt="Logo" class="cs-logo">
                <div class="cs-logo-text">City Smiles</div>
            </div>

            <h1 class="cs-page-title"><?php echo ucfirst(str_replace('.php', '', $current_page)); ?></h1>

            

                <div class="dropdown">
                    <a href="#" class="d-flex align-items-center gap-2 cs-profile" id="profileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                       
                        <div class="cs-profile-info">
                            <div class="cs-profile-name"><?php echo htmlspecialchars($userName); ?></div>
                            <div class="cs-profile-role"><?php echo htmlspecialchars($userRole); ?></div>
                        </div>
                        <img src="<?php echo !empty($_SESSION['profile_picture']) ? htmlspecialchars($_SESSION['profile_picture']) : '../icons/profile.png'; ?>" alt="Profile" class="cs-profile-image">
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="profileDropdown">
                        <li><a class="dropdown-item" href="../account/account.php"><i class="fas fa-user-circle me-2"></i>My Profile</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../login/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Sign Out</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </header>
</div>

<script>
    // Toggle sidebar collapsed state
    document.getElementById('cs-sidebar-toggle').addEventListener('click', function () {
        document.body.classList.toggle('cs-sidebar-collapsed');
        localStorage.setItem('cs-sidebar-collapsed', document.body.classList.contains('cs-sidebar-collapsed'));
    });

    // Toggle mobile menu
    document.querySelectorAll('#cs-mobile-menu-toggle').forEach(toggle => {
        toggle.addEventListener('click', function () {
            document.body.classList.toggle('cs-sidebar-open');
        });
    });

    // Close sidebar when clicking outside on mobile
    document.addEventListener('click', function (e) {
        if (window.innerWidth <= 992) {
            const sidebar = document.querySelector('.cs-sidebar');
            const mobileToggle = document.getElementById('cs-mobile-menu-toggle');

            if (!sidebar.contains(e.target) && !mobileToggle.contains(e.target) && document.body.classList.contains('cs-sidebar-open')) {
                document.body.classList.remove('cs-sidebar-open');
            }
        }
    });

    // Remember sidebar state
    document.addEventListener('DOMContentLoaded', function () {
        const sidebarCollapsed = localStorage.getItem('cs-sidebar-collapsed') === 'true';
        if (sidebarCollapsed) {
            document.body.classList.add('cs-sidebar-collapsed');
        }
        
        // Initialize Bootstrap dropdowns manually
        if (typeof bootstrap !== 'undefined') {
            var dropdownElementList = [].slice.call(document.querySelectorAll('[data-bs-toggle="dropdown"]'));
            dropdownElementList.map(function(dropdownToggleEl) {
                return new bootstrap.Dropdown(dropdownToggleEl);
            });
        }
    });
</script>