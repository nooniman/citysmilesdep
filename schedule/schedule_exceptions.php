<?php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

// Get dentist ID from request or use default
$dentist_id = isset($_GET['dentist_id']) ? intval($_GET['dentist_id']) : 0;

// Get all dentists
$dentists_query = "SELECT id, CONCAT(first_name, ' ', last_name) AS dentist_name 
                  FROM users 
                  WHERE role = 'dentist' 
                  ORDER BY last_name, first_name";
$dentists_result = $conn->query($dentists_query);

// Process messages
$message = '';
if (isset($_SESSION['exception_message'])) {
    $message = '<div class="alert alert-success">' . $_SESSION['exception_message'] . '</div>';
    unset($_SESSION['exception_message']);
}
if (isset($_SESSION['exception_error'])) {
    $message = '<div class="alert alert-danger">' . $_SESSION['exception_error'] . '</div>';
    unset($_SESSION['exception_error']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Exceptions | City Smiles Dental Clinic</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="schedule.css">
    <style>
        .exceptions-container {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .date-filter {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            align-items: center;
        }
        
        .exception-card {
            background-color: #f9f9f9;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            border-left: 4px solid #9d7ded;
            transition: transform 0.2s;
        }
        
        .exception-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        .exception-card.unavailable {
            border-left-color: #dc3545;
        }
        
        .exception-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        
        .exception-date {
            font-weight: 600;
            font-size: 18px;
        }
        
        .exception-actions {
            display: flex;
            gap: 10px;
        }
        
        .exception-reason {
            font-style: italic;
            color: #666;
        }
        
        .badge-custom {
            padding: 5px 10px;
            border-radius: 15px;
            font-weight: 500;
            font-size: 12px;
        }
        
        .badge-available {
            background-color: #6ace70;
            color: white;
        }
        
        .badge-unavailable {
            background-color: #dc3545;
            color: white;
        }
    </style>
</head>

<body class="<?php echo isset($_SESSION['sidebar_collapsed']) && $_SESSION['sidebar_collapsed'] ? 'cs-sidebar-collapsed' : ''; ?>">
    <?php include '../sidebar/sidebar.php'; ?>

    <div class="app-container">
        <div class="header-container">
            <div id="schedule-text">
                <h1>Schedule Exceptions</h1>
                <p>Manage days off and custom scheduling</p>
            </div>
            <div class="addschedule-container">
                <a href="schedule.php" class="btn btn-outline-primary me-2">
                    <i class="fas fa-calendar-week"></i> Regular Schedule
                </a>
                <button type="button" class="btn btn-primary addschedule-button" data-bs-toggle="modal" data-bs-target="#addExceptionModal">
                    <i class="fas fa-plus"></i> Add Exception
                </button>
            </div>
        </div>

        <?php echo $message; ?>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">Select Dentist</h5>
                    </div>
                    <div class="card-body">
                        <form action="" method="GET" class="mb-0">
                            <div class="mb-3">
                                <label for="dentist_id" class="form-label">Dentist</label>
                                <select name="dentist_id" id="dentist_id" class="form-select" onchange="this.form.submit()">
                                    <option value="0">-- Select Dentist --</option>
                                    <?php while ($dentist = $dentists_result->fetch_assoc()): ?>
                                        <option value="<?php echo $dentist['id']; ?>" <?php echo $dentist_id == $dentist['id'] ? 'selected' : ''; ?>>
                                            Dr. <?php echo htmlspecialchars($dentist['dentist_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Filter Exceptions</h5>
                        <button type="button" class="btn btn-sm btn-light" id="clearFilters">Clear Filters</button>
                    </div>
                    <div class="card-body">
                        <div class="date-filter">
                            <div class="mb-0">
                                <label for="start_date" class="form-label">From</label>
                                <input type="date" id="start_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                            </div>
                            <div class="mb-0">
                                <label for="end_date" class="form-label">To</label>
                                <input type="date" id="end_date" class="form-control" value="<?php echo date('Y-m-d', strtotime('+3 months')); ?>">
                            </div>
                            <div class="mb-0">
                                <label for="exception_type" class="form-label">Type</label>
                                <select id="exception_type" class="form-select">
                                    <option value="all">All Types</option>
                                    <option value="available">Custom Hours</option>
                                    <option value="unavailable">Unavailable</option>
                                </select>
                            </div>
                            <div class="mb-0 mt-4">
                                <button type="button" class="btn btn-primary" id="applyFilters">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="exceptions-container">
            <h4 id="exceptions-title">Upcoming Exceptions</h4>
            <div id="exceptions-list" class="mt-4">
                <!-- Exceptions will be loaded here -->
                <div class="text-center py-5 text-muted" id="no-exceptions-message">
                    <i class="fas fa-calendar-times fa-3x mb-3"></i>
                    <h5>No exceptions found</h5>
                    <p>Select a dentist to view their schedule exceptions</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Exception Modal -->
    <div class="modal fade" id="addExceptionModal" tabindex="-1" aria-labelledby="addExceptionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="addExceptionModalLabel">Add Schedule Exception</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="addExceptionForm" action="add_exception.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="dentist_id" id="exception_dentist_id" value="<?php echo $dentist_id; ?>">
                        
                        <div class="mb-3">
                            <label for="exception_dentist_select" class="form-label">Dentist</label>
                            <select name="dentist_id" id="exception_dentist_select" class="form-select" required>
                                <option value="">-- Select Dentist --</option>
                                <?php 
                                $dentists_result->data_seek(0); // Reset the pointer
                                while ($dentist = $dentists_result->fetch_assoc()): 
                                ?>
                                    <option value="<?php echo $dentist['id']; ?>" <?php echo $dentist_id == $dentist['id'] ? 'selected' : ''; ?>>
                                        Dr. <?php echo htmlspecialchars($dentist['dentist_name']); ?>
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="exception_date" class="form-label">Exception Date</label>
                            <input type="date" class="form-control" id="exception_date" name="exception_date" required min="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Exception Type</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exception_type" id="type_unavailable" value="unavailable" checked>
                                <label class="form-check-label" for="type_unavailable">
                                    Unavailable (Day Off)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exception_type" id="type_custom" value="custom">
                                <label class="form-check-label" for="type_custom">
                                    Custom Hours
                                </label>
                            </div>
                        </div>
                        
                        <div id="custom_hours_section" style="display: none;">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label for="exception_start_time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="exception_start_time" name="exception_start_time">
                                </div>
                                <div class="col-6">
                                    <label for="exception_end_time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="exception_end_time" name="exception_end_time">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="reason" class="form-label">Reason (Optional)</label>
                            <textarea class="form-control" id="reason" name="reason" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Save Exception</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Exception Modal -->
    <div class="modal fade" id="editExceptionModal" tabindex="-1" aria-labelledby="editExceptionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title" id="editExceptionModalLabel">Edit Schedule Exception</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="editExceptionForm" action="update_exception.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="dentist_id" id="edit_exception_dentist_id" value="">
                        <input type="hidden" name="exception_id" id="edit_exception_id" value="">
                        
                        <div class="mb-3">
                            <label class="form-label">Exception Date</label>
                            <p id="edit_exception_date" class="form-control-plaintext"></p>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Exception Type</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exception_type" id="edit_type_unavailable" value="unavailable">
                                <label class="form-check-label" for="edit_type_unavailable">
                                    Unavailable (Day Off)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="exception_type" id="edit_type_custom" value="custom">
                                <label class="form-check-label" for="edit_type_custom">
                                    Custom Hours
                                </label>
                            </div>
                        </div>
                        
                        <div id="edit_custom_hours_section" style="display: none;">
                            <div class="row mb-3">
                                <div class="col-6">
                                    <label for="edit_exception_start_time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="edit_exception_start_time" name="exception_start_time">
                                </div>
                                <div class="col-6">
                                    <label for="edit_exception_end_time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="edit_exception_end_time" name="exception_end_time">
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_reason" class="form-label">Reason (Optional)</label>
                            <textarea class="form-control" id="edit_reason" name="reason" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-danger me-auto" id="deleteExceptionBtn">Delete Exception</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update Exception</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1" aria-labelledby="deleteConfirmModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-sm">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteConfirmModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete this exception?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form id="deleteExceptionForm" action="delete_exception.php" method="POST">
                        <input type="hidden" id="delete_exception_id" name="exception_id">
                        <button type="submit" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        $(document).ready(function() {
            // Toggle custom hours section based on exception type selection
            $('input[name="exception_type"]').change(function() {
                if ($(this).val() === 'custom') {
                    $('#custom_hours_section').show();
                    $('#exception_start_time, #exception_end_time').prop('required', true);
                } else {
                    $('#custom_hours_section').hide();
                    $('#exception_start_time, #exception_end_time').prop('required', false);
                }
            });
            
            // Same for edit modal
            $('input[name="exception_type"]').change(function() {
                if ($(this).val() === 'custom') {
                    $('#edit_custom_hours_section').show();
                    $('#edit_exception_start_time, #edit_exception_end_time').prop('required', true);
                } else {
                    $('#edit_custom_hours_section').hide();
                    $('#edit_exception_start_time, #edit_exception_end_time').prop('required', false);
                }
            });
            
            // Update hidden dentist_id field when dropdown changes
            $('#exception_dentist_select').change(function() {
                $('#exception_dentist_id').val($(this).val());
            });
            
            // Load exceptions when a dentist is selected
            if ($('#dentist_id').val() > 0) {
                loadExceptions();
            }
            
            // Apply filters button
            $('#applyFilters').click(function() {
                loadExceptions();
            });
            
            // Clear filters
            $('#clearFilters').click(function() {
                $('#start_date').val('<?php echo date('Y-m-d'); ?>');
                $('#end_date').val('<?php echo date('Y-m-d', strtotime('+3 months')); ?>');
                $('#exception_type').val('all');
                loadExceptions();
            });
            
            // Setup delete exception handler
            $('#deleteExceptionBtn').click(function() {
                const exceptionId = $('#edit_exception_id').val();
                $('#delete_exception_id').val(exceptionId);
                $('#editExceptionModal').modal('hide');
                $('#deleteConfirmModal').modal('show');
            });
            
            // Function to load exceptions
            function loadExceptions() {
                const dentistId = $('#dentist_id').val();
                if (!dentistId || dentistId == 0) return;
                
                const startDate = $('#start_date').val();
                const endDate = $('#end_date').val();
                const exceptionType = $('#exception_type').val();
                
                $.ajax({
                    url: 'get_exceptions_list.php',
                    type: 'GET',
                    data: {
                        dentist_id: dentistId,
                        start_date: startDate,
                        end_date: endDate,
                        type: exceptionType
                    },
                    success: function(response) {
                        $('#exceptions-list').html(response);
                        
                        // Update title with count
                        const count = $('.exception-card').length;
                        $('#exceptions-title').text(`Exceptions (${count})`);
                        
                        // Show/hide no exceptions message
                        if (count === 0) {
                            $('#no-exceptions-message').show();
                        } else {
                            $('#no-exceptions-message').hide();
                        }
                        
                        // Setup edit exception handlers
                        $('.edit-exception-btn').click(function() {
                            const exceptionId = $(this).data('id');
                            fetchExceptionDetails(exceptionId);
                        });
                    },
                    error: function(xhr, status, error) {
                        console.error("Error loading exceptions:", error);
                        $('#exceptions-list').html('<div class="alert alert-danger">Error loading exceptions. Please try again.</div>');
                    }
                });
            }
            
            // Function to fetch exception details for editing
            function fetchExceptionDetails(exceptionId) {
                $.ajax({
                    url: 'get_exception_details.php',
                    type: 'GET',
                    data: { exception_id: exceptionId },
                    dataType: 'json',
                    success: function(data) {
                        $('#edit_exception_id').val(data.exception_id);
                        $('#edit_exception_dentist_id').val(data.user_id || data.dentist_id);
                        $('#edit_exception_date').text(data.formatted_date);
                        $('#edit_reason').val(data.reason || '');
                        
                        if (data.is_available == 1) {
                            $('#edit_type_custom').prop('checked', true);
                            $('#edit_custom_hours_section').show();
                            $('#edit_exception_start_time').val(data.start_time);
                            $('#edit_exception_end_time').val(data.end_time);
                        } else {
                            $('#edit_type_unavailable').prop('checked', true);
                            $('#edit_custom_hours_section').hide();
                        }
                        
                        $('#editExceptionModal').modal('show');
                    },
                    error: function(xhr, status, error) {
                        console.error("Error fetching exception details:", error);
                        alert("Error loading exception details. Please try again.");
                    }
                });
            }
        });
    </script>
</body>
</html>