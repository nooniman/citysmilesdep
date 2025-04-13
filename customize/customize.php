<?php
session_start();

// Check admin access
if (
    !isset($_SESSION['user_id']) || !isset($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'staff', 'dentist'])
) {
    // Return unauthorized for AJAX requests
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}
?>
<?php
include '../database.php';

$query = $conn->query("SELECT * FROM clinic_info LIMIT 1");
$clinic = $query->fetch_assoc();

// Fetch slider images
$sliderQuery = $conn->query("SELECT * FROM slider_images");

// Fetch services
$servicesQuery = $conn->query("SELECT * FROM services");
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customize Website</title>
    <link rel="stylesheet" href="../dashboard/dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.css">
    <script src="//cdnjs.cloudflare.com/ajax/libs/timepicker/1.3.5/jquery.timepicker.min.js"></script>
    <link rel="stylesheet" href="customize.css">
</head>

<body>
    <?php include '../sidebar/sidebar.php'; ?>
    <div class="toast-container position-fixed top-0 end-0 p-3">
        <div id="liveToast" class="toast" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="toast-header">
                <strong class="me-auto toast-title">Notification</strong>
                <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
            <div class="toast-body"></div>
        </div>
    </div>

    <div class="content">
        <h1>Customize Website</h1>

        <div class="form-container">
            <!-- Form to add new slider image -->
            <form action="process_slider_image.php" method="POST" enctype="multipart/form-data">
                <h2>Add New Slider Image</h2>
                <label for="image">Slider Image:</label>
                <input type="file" id="image" name="image" accept="image/*" required>
                <button type="submit">Add Image</button>
            </form>

            <!-- List of existing slider images with delete options -->
            <div class="slider-images">
                <h2>Existing Slider Images</h2>
                <?php
                while ($slider = $sliderQuery->fetch_assoc()) {
                    echo '<div class="slider-image">';
                    echo '<img src="' . htmlspecialchars($slider['image_path']) . '" alt="Slider Image">';
                    echo '<button class="btn btn-danger btn-delete-slider" data-id="' . $slider['id'] . '">Delete</button>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>

        <div class="form-container">
            <form id="clinic-details-form" method="POST">
                <h2>Edit Clinic Information</h2>

                <!-- Day Range Selection -->
                <div class="day-range-selector">
                    <label>Operating Days:</label>
                    <div class="range-picker">
                        <?php
                        $days = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
                        $currentRange = explode('-', $clinic['days']);
                        $startDay = $currentRange[0] ?? 'Mon';
                        $endDay = $currentRange[1] ?? 'Fri';
                        ?>

                        <select name="start_day" required>
                            <?php foreach ($days as $day): ?>
                                <option value="<?= $day ?>" <?= $startDay === $day ? 'selected' : '' ?>>
                                    <?= $day ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <span>to</span>

                        <select name="end_day" required>
                            <?php foreach ($days as $day): ?>
                                <option value="<?= $day ?>" <?= $endDay === $day ? 'selected' : '' ?>>
                                    <?= $day ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <br>

                <!-- Time Inputs in 12-hour format -->
                <?php
                // Function to convert 24-hour time to 12-hour time
                function to12h($time24)
                {
                    return date("g:i A", strtotime($time24));
                }

                $clinicHours = $clinic['hours'] ?? '09:00 - 17:00';
                list($openTime24, $closeTime24) = explode(' - ', $clinicHours . ' - '); // ensure we always get two elements
                
                $opening = to12h(trim($openTime24));
                $closing = to12h(trim($closeTime24));
                ?>
                <div class="time-inputs">
                    <div>
                        <label>Opening Time:</label>
                        <select name="opening_time" required>
                            <?php
                            // Function to generate time options
                            function generateTimeOptions($selectedTime)
                            {
                                $times = [];
                                $currentTime = strtotime('6:00 AM'); // Starting from 6:00 AM
                                $endTime = strtotime('9:00 PM'); // Ending at 9:00 PM
                            
                                while ($currentTime <= $endTime) {
                                    $timeFormatted = date("g:i A", $currentTime);
                                    $selected = ($timeFormatted == $selectedTime) ? 'selected' : '';
                                    $times[] = "<option value='$timeFormatted' $selected>$timeFormatted</option>";
                                    $currentTime = strtotime('+30 minutes', $currentTime); // Add 30 minutes increment
                                }

                                return implode('', $times);
                            }

                            echo generateTimeOptions($opening); // Pre-fill the selected opening time
                            ?>
                        </select>
                    </div>
                    <div>
                        <label>Closing Time:</label>
                        <select name="closing_time" required>
                            <?php
                            echo generateTimeOptions($closing); // Pre-fill the selected closing time
                            ?>
                        </select>
                    </div>
                </div>




                <!-- Phone Number -->
                <label>Phone Number:</label>
                <input type="tel" name="phone" pattern="[0-9]{10,15}" title="10-15 digit phone number"
                    value="<?= htmlspecialchars($clinic['phone']) ?>" required>
                <br><br>

                <!-- Address -->
                <label>Address:</label>
                <textarea name="address" required><?= htmlspecialchars($clinic['address']) ?></textarea>
                <br><br>

                <button type="submit">Save Changes</button>
            </form>
        </div>

        <div class="form-container">
            <h2>Additional Clinic Information</h2>
            <form id="additional-clinic-info-form" method="POST" action="update_clinic_info.php">
                <label for="flexible_schedule">Flexible Schedule:</label>
                <textarea id="flexible_schedule"
                    name="flexible_schedule"><?php echo htmlspecialchars($clinic['flexible_schedule']); ?></textarea>

                <label for="experience">Experience:</label>
                <textarea id="experience"
                    name="experience"><?php echo htmlspecialchars($clinic['experience']); ?></textarea>

                <label for="transparent_pricing">Transparent Pricing:</label>
                <textarea id="transparent_pricing"
                    name="transparent_pricing"><?php echo htmlspecialchars($clinic['transparent_pricing']); ?></textarea>

                <label for="easy_appointment">Easy Appointment:</label>
                <textarea id="easy_appointment"
                    name="easy_appointment"><?php echo htmlspecialchars($clinic['easy_appointment']); ?></textarea>

                <button type="submit">Save Changes</button>
            </form>
        </div>

        <div class="form-container">
            <form action="process_feature_image.php" method="POST" enctype="multipart/form-data">
                <h2>Update Feature Image</h2>
                <?php if (!empty($clinic['feature_image'])): ?>
                    <div class="current-feature-image">
                        <img src="<?= htmlspecialchars($clinic['feature_image']) ?>" alt="Current Feature Image"
                            style="max-width: 300px; margin-bottom: 15px;">
                    </div>
                <?php endif; ?>
                <label for="feature_image">Select New Image:</label>
                <input type="file" id="feature_image" name="feature_image" accept="image/*" required>
                <button type="submit">Update Feature Image</button>
            </form>
        </div>

        <div class="form-container">
            <!-- Form to add new service -->
            <form action="process_service.php" method="POST" enctype="multipart/form-data">
                <h2>Add New Service</h2>
                <label for="name">Service Name:</label>
                <input type="text" id="name" name="name" required>
                <label for="description">Description:</label>
                <textarea id="description" name="description" required></textarea>
                <label for="image">Service Image:</label>
                <input type="file" id="image" name="image" accept="image/*" required>
                <button type="submit">Add Service</button>
            </form>

            <!-- Dropdown for existing services with edit and delete options -->
            <div class="dropdown">
                <span class="services-dropdown-toggle" style="cursor: pointer;">
                    Existing Services <span class="dropdown-arrow">&#9662;</span>
                </span>
                <div class="services-dropdown-content">
                    <?php
                    while ($service = $servicesQuery->fetch_assoc()) {
                        echo '<div class="service-item">';
                        echo '<span>' . htmlspecialchars($service['name']) . '</span>';
                        echo '<div>';
                        echo '<a href="edit_service.php?id=' . $service['services_id'] . '" class="btn btn-edit" style="margin-right: 10px;">Edit</a>';
                        echo '<a href="#" class="btn btn-delete-service" data-id="' . htmlspecialchars($service['services_id']) . '">Delete</a>';
                        echo '</div>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
        </div>
    </div>

    <script src="customize.js"> </script>
</body>

</html>