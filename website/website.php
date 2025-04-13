<?php
include '../database.php';

// Fetch clinic info
$query = $conn->query("SELECT * FROM clinic_info LIMIT 1");
$clinic = $query->fetch_assoc();

// Fetch slider images
$sliderQuery = $conn->query("SELECT * FROM slider_images");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <link rel="stylesheet" href="../website/website.css">
    <link rel="stylesheet" href="../website/services.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap"
        rel="stylesheet">
    <meta name="theme-color" content="#1A73E8">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home</title>
    <style>
        .profile-button {
  display: inline-block;
  width: 40px;
  height: 40px;
  border-radius: 50%;
  overflow: hidden;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.12);
  transition: box-shadow 0.3s ease;
}

.profile-button:hover {
  box-shadow: 0 2px 6px rgba(0, 0, 0, 0.2);
}

.profile-button img {
  width: 100%;
  height: 100%;
  object-fit: cover;
  border-radius: 50%;
}
    </style>
</head>

<body>
    <div class="header">
        <div class="logo-wrapper">
            <a href="../website/website.php"><img src="../images/Screenshot__522_-removebg-preview.png" alt="Logo"
                    class="logo"></a>
            <h1>City Smile Dental Clinic</h1>
        </div>
        <ul>
            <ul><a href="../book/bookmain.html">BOOKING</a></ul>
            <div class="dropdown">
                <li class="services-toggle">
                    <a href="javascript:void(0)">SERVICES <img style="height: 12px; padding-left:5px;"
                            src="../icons/download.png"></a>
                    <div id="myDropdown" class="dropdown-content">
                        <?php
                        $servicesQuery = $conn->query("SELECT * FROM services");
                        while ($service = $servicesQuery->fetch_assoc()) {
                            echo '<a href="../website/services/service-details.php?id=' . $service['services_id'] . '"><h3 class="' . $service['services_id'] . '">' . $service['name'] . '</h3></a>';
                        }
                        ?>
                    </div>
                </li>
            </div>

            <ul><a href="../about/about.html">ABOUT</a></ul>
        </ul>
        <div>
            <?php
            // Start session if not already started
            if (session_status() == PHP_SESSION_NONE) {
                session_start();
            }

            // Fetch user data if logged in but user data isn't available
            if (isset($_SESSION['user_id']) && !isset($profilePicture)) {
                include_once '../database.php';
                $user_id = $_SESSION['user_id'];
                $stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($row = $result->fetch_assoc()) {
                    $profilePicture = $row['profile_picture'];
                }
            }

            // Check if user is logged in
            if (isset($_SESSION['user_id'])): ?>
                <!-- Profile icon for logged-in users -->
                <a href="<?php echo isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin' ? '../admindashboard/dashboard.php' : '../userdashboard/appointment.php'; ?>"
                    class="profile-button">
                    <img src="<?php echo !empty($profilePicture) ? htmlspecialchars($profilePicture) : '../icons/profile.png'; ?>"
                        alt="Profile">
                </a>
            <?php else: ?>
                <!-- Login button for guests -->
                <a href="../login/login.php" class="login">Login</a>
            <?php endif; ?>
        </div>
    </div>




    <div class="slider-container">
        <div class="slider-container">
            <div class="overlaytext">
                <h1>Dentist Services <br> that You Can Trust</h1>
                <p id="bookAppointmentBtn">Book an Appointment</p>
            </div>
            <div class="slider">
                <?php
                while ($slider = $sliderQuery->fetch_assoc()) {
                    echo '<div class="slide">';
                    echo '<img src="' . htmlspecialchars($slider['image_path']) . '" alt="Slider Image">';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
        <div class="controls">
            <button class="control prev">&#10094;</button>
            <button class="control next">&#10095;</button>
        </div>
        <div class="dots">
            <?php
            $sliderQuery->data_seek(0); // Reset pointer to the beginning
            $index = 0;
            while ($slider = $sliderQuery->fetch_assoc()) {
                $activeClass = $index === 0 ? 'active' : '';
                echo '<div class="dot ' . $activeClass . '" data-slide="' . $index . '"></div>';
                $index++;
            }
            ?>
        </div>
    </div>

    <div class="content-box">
        <div class="contact-item">
            <img src="../icons/clock.png" alt="Clock Icon">
            <div>
                <strong><?php echo $clinic['days']; ?></strong>
                <span><?php echo $clinic['hours']; ?></span>
            </div>
        </div>
        <div class="contact-item">
            <img src="../icons/image (1).png" alt="Phone Icon">
            <div>
                <strong>Call Us</strong>
                <span><?php echo $clinic['phone']; ?></span>
            </div>
        </div>
        <div class="contact-item">
            <img src="../icons/image (2).png" alt="Location Icon">
            <div>
                <strong>Address</strong>
                <span><?php echo $clinic['address']; ?></span>
            </div>
        </div>
    </div>

    <div class="blank">
        <div class="features-section">
            <div class="features-content">
                <div class="feature-item">
                    <img src="../icons/images.png" alt="Schedule Icon">
                    <div>
                        <strong>Flexible Schedule</strong>
                        <p><?php echo htmlspecialchars($clinic['flexible_schedule']); ?></p>
                    </div>
                </div>
                <div class="feature-item">
                    <img src="../icons/image (11).png" alt="Experience Icon">
                    <div>
                        <strong>Experience</strong>
                        <p><?php echo htmlspecialchars($clinic['experience']); ?></p>
                    </div>
                </div>
                <div class="feature-item">
                    <img src="../icons/image (3).png" alt="Pricing Icon">
                    <div>
                        <strong>Transparent Pricing</strong>
                        <p><?php echo htmlspecialchars($clinic['transparent_pricing']); ?></p>
                    </div>
                </div>
                <div class="feature-item">
                    <img src="../icons/image (23).png" alt="Appointment Icon">
                    <div>
                        <strong>Easy Appointment</strong>
                        <p><?php echo htmlspecialchars($clinic['easy_appointment']); ?></p>
                    </div>
                </div>
            </div>
            <div class="features-image">
                <?php if (!empty($clinic['feature_image'])): ?>
                    <img src="<?= htmlspecialchars($clinic['feature_image']) ?>" alt="Clinic Feature">
                <?php else: ?>
                    <!-- Default image -->
                    <img src="../images/image_placeholder.jpg" alt="Default Feature">
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div class="service">
        <h1>Services</h1>
    </div>

    <!-- Services Section -->
    <div class="services">
        <div class="services-slider-container">
            <div class="services-slider">
                <?php
                $servicesQuery = $conn->query("SELECT * FROM services");
                while ($service = $servicesQuery->fetch_assoc()) {
                    ?>
                    <div class="service-item">
                        <div class="service-image-container">
                            <a href="#"><img src="<?php echo $service['image_path']; ?>"
                                    alt="<?php echo $service['name']; ?>"></a>
                        </div>
                        <p><?php echo $service['name']; ?></p>
                    </div>
                    <?php
                }
                ?>
            </div>
        </div>

        <!-- Appointment Modal -->
        <div id="appointmentModal"
            style="display:none; position:fixed; top:10%; left:50%; transform:translateX(-50%); background:#fff; padding:20px; border:1px solid #000; z-index:1000;">
            <h2>Book an Appointment</h2>
            <form action="process_appointment.php" method="POST">
                <label for="patientName">Name:</label>
                <input type="text" id="patientName" name="patientName" required><br><br>
                <label for="service">Select Service:</label>
                <select id="service" name="service" required>
                    <?php
                    $servicesQuery = $conn->query("SELECT * FROM services");
                    while ($service = $servicesQuery->fetch_assoc()) {
                        echo '<option value="' . $service['services_id'] . '">' . $service['name'] . '</option>';
                    }
                    ?>
                </select><br><br>
                <label for="appointmentDate">Date:</label>
                <input type="date" id="appointmentDate" name="appointmentDate" required><br><br>
                <label for="startTime">Start Time:</label>
                <input type="time" id="startTime" name="startTime" required><br><br>
                <label for="endTime">End Time:</label>
                <input type="time" id="endTime" name="endTime" required><br><br>
                <button type="submit">Submit</button>
                <button type="button" id="closeModalBtn">Cancel</button>
            </form>
        </div>

        <!-- Navigation Buttons -->
        <button class="prev service-prev">&#10094;</button>
        <button class="next service-next">&#10095;</button>
    </div>

    </div>
    <footer class="footer">
        <div class="footer-container">
            <!-- Logo -->
            <div class="footer-logo">
                <img src="../images/Screenshot__522_-removebg-preview.png" alt="Logo">
            </div>

            <div class="footer-section">
                <h3>Dental Services</h3>
                <ul>
                    <li>→ Tooth Protection</li>
                    <li>→ Dental Implants</li>
                    <li>→ Dental Care</li>
                    <li>→ Teeth Whitening</li>
                </ul>
            </div>

            <div class="footer-section">
                <h3>Explore</h3>
                <ul>
                    <li>→ Home</li>
                    <li>→ Services</li>
                    <li>→ About</li>
                </ul>
            </div>

            <div class="footer-section contact-info">
                <h3>Have a Question?</h3>
                <p>
                    <img class="icon" src="../icons/location.png"> Centrali, Zone II, Zamboanga City
                </p>
                <p>
                    <img class="icon" src="../icons/phone.png"> +63 123 456 7892
                </p>
            </div>
        </div>
    </footer>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            let slideIndex = 0;
            const slides = document.querySelectorAll(".slider .slide");
            const prevButton = document.querySelector(".control.prev");
            const nextButton = document.querySelector(".control.next");
            const dots = document.querySelectorAll(".dots .dot");

            function showSlide(index) {
                slides.forEach((slide, i) => {
                    slide.style.display = (i === index) ? "block" : "none";
                    dots[i].classList.toggle("active", i === index);
                });
            }

            function moveSlide(step) {
                slideIndex = (slideIndex + step + slides.length) % slides.length;
                showSlide(slideIndex);
            }

            prevButton.addEventListener("click", () => moveSlide(-1));
            nextButton.addEventListener("click", () => moveSlide(1));

            dots.forEach((dot, index) => {
                dot.addEventListener("click", () => {
                    slideIndex = index;
                    showSlide(slideIndex);
                });
            });

            showSlide(slideIndex);
            setInterval(() => moveSlide(1), 4000);
        });



        document.addEventListener("DOMContentLoaded", function () {
            let serviceIndex = 0;
            const serviceSlider = document.querySelector(".services-slider");
            const serviceSlides = document.querySelectorAll(".service-item"); // FIXED: Select service items
            const servicePrev = document.querySelector(".service-prev");
            const serviceNext = document.querySelector(".service-next");

            if (!servicePrev || !serviceNext || !serviceSlider) {
                console.error("Buttons or slider not found!");
                return;
            }

            const visibleSlides = 4; // Number of services visible at once
            const totalSlides = serviceSlides.length;
            const slideWidth = serviceSlides[0].offsetWidth + 20; // FIXED: Include margin if needed
            const maxIndex = Math.max((totalSlides + 1) - visibleSlides, 0); // Ensure it doesn’t go negative

            function moveServiceSlide(step) {
                serviceIndex += step;
                if (serviceIndex > maxIndex) {
                    serviceIndex = 0; // Restart from beginning
                } else if (serviceIndex < 0) {
                    serviceIndex = maxIndex; // Go to last set
                }
                updateServiceSlide();
            }

            function updateServiceSlide() {
                const offset = -(serviceIndex * slideWidth);
                serviceSlider.style.transform = `translateX(${offset}px)`;
            }

            servicePrev.addEventListener("click", () => moveServiceSlide(-1));
            serviceNext.addEventListener("click", () => moveServiceSlide(1));

            setInterval(() => moveServiceSlide(1), 4000); // Auto-slide every 4 sec
        });

        document.addEventListener("DOMContentLoaded", function () {
            const toggle = document.querySelector(".services-toggle a");
            const dropdown = document.getElementById("myDropdown");

            toggle.addEventListener("click", function (e) {
                e.preventDefault();
                dropdown.classList.toggle("show");
            });

            // Optional: Close dropdown when clicking outside
            window.addEventListener("click", function (event) {
                if (!event.target.closest('.services-toggle')) {
                    dropdown.classList.remove("show");
                }
            });
        });

    </script>
</body>

</html>