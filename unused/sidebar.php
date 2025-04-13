<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="stylesheet" href="../home/home.css">
    <link rel="stylesheet" href="test.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>City Smile Dental Clinic Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

</head>
<body>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <div class="header">
        <img class="logo" src="../images/Screenshot__522_-removebg-preview.png"> 
        <h1 style="color:#000069; padding:15px; margin-top:9px;">City Smile Dental Clinic</h1>
    </div>
    <div class="sidebar">
        <h2>City Smile Dental Clinic</h2>
        <div>
            <aside>
                <button class="menu-btn fa fa-chevron-left"></button>
                <ul class="menu-items">
                    <li>
                        <a href="../dashboard/dashboard.php"><span class="icon fa fa-house"></span><span class="item-name"><img class="dashboard-icon" src="../icons/house.png">Dashboard</span></a>
                    </li>
                    <li>
                        <a href="../staff/staff.php"><span class="icon fa fa-layer-group"></span><span class="item-name"><img class="dashboard-icon" src="../icons/staff.png">Staff</span></a>
                    </li>
                    <li>
                        <a href="../patients/patients.php"><span class="icon fa fa-chart-line"></span><span class="item-name"><img class="dashboard-icon" src="../icons/examination.png">Patients</span></a>
                    </li>
                    <li class="dropdown">
                        <a href="#" class="dropdown-toggle">
                            <span class="icon fa fa-chart-simple"></span>
                            <span class="item-name"><img class="dashboard-icon" src="../icons/clipboard.png"> Appointment List</span>
                            <span class="fa fa-chevron-down"></span>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a href="../appointmentlist/walkin.php"><input type="radio" id="walk-in" name="appointment-type"><label for="walk-in">Walk In Request</label></a></li>
                            <li><a href="../appointmentlist/online.php"><input type="radio" id="online" name="appointment-type"><label for="online">Online Request</label></a></li>
                        </ul>
                    </li>
                    <li>
                        <a href="../schedule/schedule.php"><span class="icon fa fa-user"></span><span class="item-name"><img class="dashboard-icon" src="../icons/schedule.png">Schedule</span></a>
                    </li>
                    <li>
                        <a href="#"><span class="icon fa fa-gear"></span><span class="item-name"><img class="dashboard-icon" src="../icons/prescription.png">Prescription</span></a>
                    </li>
                    <li>
                        <a href="#"><span class="icon fa fa-comment-dots"></span><span class="item-name"><img class="dashboard-icon" src="../icons/syringe.png">Treatment</span></a>
                    </li>
                    <li>
                        <a href="../analytics/analytics.php"><span class="icon fa fa-comment-dots"></span><span class="item-name"><img class="dashboard-icon" src="../icons/graph.png">Analytics</span></a>
                    </li>
                </ul>
                <a href="#" class="admin-profile">
                    <img class="profile" src="../images/Group 1 (1).png" alt="Admin Profile">
                    <span class="admin-text">Admin</span>
                    <img class="logout" src="../icons/logout.png" alt="Logout">
                </a>
            </aside>
        </div>
    </div>
    <div class="content">
        <!-- Content area can be added here if needed -->
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const dropdowns = document.querySelectorAll(".dropdown");

        dropdowns.forEach((dropdown) => {
            const toggle = dropdown.querySelector(".dropdown-toggle");
            const menu = dropdown.querySelector(".dropdown-menu");

            toggle.addEventListener("click", function (event) {
                event.preventDefault();
                const isOpen = menu.style.display === "block";
                document.querySelectorAll(".dropdown-menu").forEach((m) => m.style.display = "none");
                menu.style.display = isOpen ? "none" : "block";
            });
        });

        document.addEventListener("click", function (event) {
            if (!event.target.closest(".dropdown")) {
                document.querySelectorAll(".dropdown-menu").forEach((menu) => {
                    menu.style.display = "none";
                });
            }
        });

        // Redirect when a radio button is selected
        document.querySelectorAll("input[name='appointment-type']").forEach((radio) => {
            radio.addEventListener("change", function () {
                if (this.id === "walk-in") {
                    window.location.href = "../appointmentlist/walkin.php";
                } else if (this.id === "online") {
                    window.location.href = "../appointmentlist/online.php";
                }
            });
        });
    });
</script>

</body>
</html>