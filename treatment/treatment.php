<?php
session_start();

// Check admin access
if (
    !isset($_SESSION['user_id']) || !isset($_SESSION['role']) ||
    !in_array($_SESSION['role'], ['admin', 'staff', 'dentist', 'assistant', 'intern'])
) {
    // Return unauthorized for AJAX requests
    header('HTTP/1.1 401 Unauthorized');
    echo json_encode(['error' => 'Unauthorized access']);
    exit();
}
?>

<?php
include '../sidebar/sidebar.php';
include '../database.php';
include '../includes/soft_delete.php';

// Get treatment statistics
$total_treatments = 0;
$completed_treatments = 0;
$pending_treatments = 0;

$stats_sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN a.status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN a.status = 'confirmed' AND a.appointment_date >= CURDATE() THEN 1 ELSE 0 END) as pending
              FROM treatments t
              JOIN appointments a ON t.appointment_id = a.appointment_id";

$stats_result = mysqli_query($conn, $stats_sql);
if ($stats_result && mysqli_num_rows($stats_result) > 0) {
    $stats = mysqli_fetch_assoc($stats_result);
    $total_treatments = $stats['total'];
    $completed_treatments = $stats['completed'];
    $pending_treatments = $stats['pending'];
}

$showDeleted = isset($_GET['show_deleted']) && $_GET['show_deleted'] == '1';

// Replace your current SQL query with:
$sql = "SELECT a.appointment_id,
               a.appointment_date,
               a.appointment_time,
               a.status,
               CONCAT(p.first_name, ' ', p.last_name) AS patient_name,
               s.name AS service_name,
               t.service_id,
               t.treatment_id,
               t.notes,
               t.is_deleted,
               t.deleted_at,
               pr.prescription_id
        FROM treatments t
        JOIN appointments a ON t.appointment_id = a.appointment_id
        JOIN patients p ON a.patient_id = p.patient_info_id
        JOIN services s ON t.service_id = s.services_id
        LEFT JOIN prescriptions pr ON a.appointment_id = pr.appointment_id
        WHERE " . (!$showDeleted ? "(t.is_deleted = 0 OR t.is_deleted IS NULL) AND a.status = 'confirmed'" :
    "(t.is_deleted = 1)") . "
        ORDER BY " . ($showDeleted ? "t.deleted_at DESC" : "a.appointment_date DESC, a.appointment_time DESC");

$result = mysqli_query($conn, $sql);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Treatment Management | City Smiles Dental</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="treatments.css">
    <style>
        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: var(--spacing-6);
            margin-bottom: var(--spacing-8);
        }

        .stat-card {
            background: white;
            border-radius: var(--radius-md);
            padding: var(--spacing-6);
            box-shadow: var(--shadow);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            text-decoration: none;
            color: inherit;
            display: flex;
            flex-direction: column;
            border-left: 4px solid transparent;
            min-height: 140px;
        }

        .stat-icon {
            position: absolute;
            top: var(--spacing-6);
            right: var(--spacing-6);
            width: 48px;
            height: 48px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.9;
        }

        .stat-icon i {
            font-size: 1.5rem;
        }

        .stat-details {
            flex-grow: 1;
        }

        .stat-value {
            font-size: 28px;
            font-weight: 600;
            color: var(--dark-lilac);
        }

        .stat-label {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--gray-600);
            margin-top: 0;
            margin-bottom: var(--spacing-3);
        }

        .treatment-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .treatment-card {
            background: white;
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            overflow: hidden;
            transition: transform 0.2s ease;
        }

        .treatment-card:hover {
            transform: translateY(-5px);
        }

        .treatment-header {
            background: linear-gradient(135deg, var(--primary-lilac), #6a63dd);
            color: white;
            padding: 15px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .treatment-header h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 600;
        }

        .treatment-date {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 50px;
            font-size: 13px;
        }

        .treatment-body {
            padding: 20px;
        }

        .treatment-service {
            font-weight: 500;
            margin-bottom: 10px;
            color: var(--gray-800);
        }

        .treatment-notes {
            background: var(--gray-100);
            padding: 10px 15px;
            border-radius: 8px;
            font-size: 14px;
            color: var(--gray-700);
            margin-bottom: 15px;
            min-height: 60px;
        }

        .treatment-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 15px;
        }

        .list-table {
            display: none;
        }

        .view-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }

        .toggle-btn {
            padding: 8px 15px;
            border: none;
            background: white;
            border-radius: var(--border-radius);
            cursor: pointer;
            box-shadow: var(--box-shadow);
            display: flex;
            align-items: center;
            gap: 5px;
            transition: all 0.2s ease;
        }

        .toggle-btn.active {
            background: var(--primary-lilac);
            color: white;
        }

        .no-treatments {
            grid-column: 1 / -1;
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            text-align: center;
            color: var(--gray-700);
        }

        .dashboard-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--dark-lilac);
            margin: 0;
        }

        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
        }

        .dashboard-container {
            margin-left: var(--sidebar-width);
            padding: calc(var(--header-height) + var(--spacing-6)) var(--spacing-8) var(--spacing-8);
            transition: margin-left 0.3s var(--transition-bezier);
            min-height: 100vh;
        }

        :root {
            /* Primary Colors */
            --primary-green: #3cb371;
            --light-green: #9aeaa1;
            --dark-green: #297859;
            --primary-lilac: #9d7ded;
            --light-lilac: #e0d4f9;
            --dark-lilac: #7B32AB;

            /* Neutral Colors */
            --white: #ffffff;
            --off-white: #f8f9fa;
            --gray-50: #f9fafb;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;

            /* Status Colors */
            --success: #10b981;
            --warning: #f59e0b;
            --danger: #ef4444;
            --info: #3b82f6;

            /* Shadow Variables */
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px -1px rgba(0, 0, 0, 0.1);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -2px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -4px rgba(0, 0, 0, 0.1);

            /* Border Radius */
            --radius-sm: 0.25rem;
            --radius: 0.5rem;
            --radius-md: 0.75rem;
            --radius-lg: 1rem;
            --radius-full: 9999px;

            /* Spacing */
            --spacing-1: 0.25rem;
            --spacing-2: 0.5rem;
            --spacing-3: 0.75rem;
            --spacing-4: 1rem;
            --spacing-5: 1.25rem;
            --spacing-6: 1.5rem;
            --spacing-8: 2rem;
            --spacing-10: 2.5rem;
            --spacing-12: 3rem;

            /* Transitions */
            --transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-slow: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-bezier: cubic-bezier(0.4, 0, 0.2, 1);

            /* Layout constants */
            --sidebar-width: 250px;
            --sidebar-collapsed-width: 70px;
            --header-height: 70px;

            /* Z-index layers */
            --z-dropdown: 1000;
            --z-sticky: 1020;
            --z-fixed: 1030;
            --z-modal-backdrop: 1040;
            --z-modal: 1050;
            --z-popover: 1060;
            --z-tooltip: 1070;
        }


        body.cs-sidebar-collapsed .dashboard-container {
            margin-left: var(--sidebar-collapsed-width);
        }

        .stat-icon {
            position: absolute;
            top: var(--spacing-6);
            right: var(--spacing-6);
            width: 48px;
            height: 48px;
            border-radius: var(--radius);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0.9;
        }

        .icon-pending {
            background: #fff3cd;
            color: #856404;
        }

        .icon-confirmed {
            background: #e0f7fa;
            color: #0097a7;
        }

        .icon-completed {
            background: #e8f5e9;
            color: #388e3c;
        }

        .icon-cancelled {
            background: #ffebee;
            color: #d32f2f;
        }
    </style>
</head>

<body
    class="<?php echo isset($_SESSION['sidebar_collapsed']) && $_SESSION['sidebar_collapsed'] ? 'cs-sidebar-collapsed' : ''; ?>">

    <div class="dashboard-container">
        <div class="dashboard-header">
            <h1 class="dashboard-title">Treatment List</h1>
        </div>

        <!-- Statistics Cards -->
        <div class="stats-row">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-clipboard-list"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-value"><?php echo $total_treatments; ?></div>
                    <div class="stat-label">Total Treatments</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-value"><?php echo $completed_treatments; ?></div>
                    <div class="stat-label">Completed</div>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-calendar"></i>
                </div>
                <div class="stat-details">
                    <div class="stat-value"><?php echo $pending_treatments; ?></div>
                    <div class="stat-label">Pending</div>
                </div>
            </div>
        </div>

        <div class="container">
            <div class="export-buttons">
                <button class="export">Excel</button>
                <button class="export">PDF</button>
                <div class="search-container">
                    <i class="fas fa-search search-icon"></i>
                    <input type="text" id="treatmentSearch" class="search-input" placeholder="Search treatments...">
                </div>
                <div class="view-toggle">
                    <button class="toggle-btn active" id="cardView">
                        <i class="fas fa-th"></i> Cards
                    </button>
                    <button class="toggle-btn" id="tableView">
                        <i class="fas fa-list"></i> Table
                    </button>

                    <!-- Add deleted toggle -->
                    <?php if ($_SESSION['role'] === 'admin'): ?>
                        <div class="deleted-toggle">
                            <label for="showDeletedToggle" class="toggle-label">Show Deleted Treatments</label>
                            <label class="toggle-switch">
                                <input type="checkbox" id="showDeletedToggle" <?php echo $showDeleted ? 'checked' : ''; ?>>
                                <span class="toggle-slider"></span>
                            </label>
                        </div>
                    <?php endif; ?>
                </div>
            </div>


            <!-- Card View -->
            <div class="treatment-cards" id="cardViewContainer">
                <?php if ($result && mysqli_num_rows($result) > 0) {
                    while ($row = mysqli_fetch_assoc($result)) { ?>
                        <div class="treatment-card">
                            <div class="treatment-header">
                                <h3><?php echo htmlspecialchars($row['patient_name']); ?></h3>
                                <span
                                    class="treatment-date"><?php echo date("M j, Y", strtotime($row['appointment_date'])); ?></span>
                            </div>
                            <div class="treatment-body">
                                <div class="treatment-service">
                                    <i class="fas fa-tooth"></i> <?php echo htmlspecialchars($row['service_name']); ?>
                                </div>
                                <div class="treatment-notes">
                                    <i class="fas fa-clipboard-check"></i>
                                    <?php echo htmlspecialchars($row['notes']) ?: 'No notes available'; ?>
                                </div>
                                <div class="treatment-actions">
                                    <a href="../treatment/chart/chart.php?appointment_id=<?php echo $row['appointment_id']; ?>"
                                        class="viewchart-button">
                                        <i class="fas fa-chart-line"></i> View Chart
                                    </a>

                                    <?php if (empty($row['is_deleted'])): ?>
                                        <button class="edit" onclick='openEditModal("<?php echo $row['service_id']; ?>", 
             "<?php echo htmlspecialchars($row['notes']); ?>", 
             "<?php echo $row['appointment_id']; ?>")'>
                                            <i class="fas fa-edit"></i> Edit
                                        </button>

                                        <?php if ($_SESSION['role'] === 'admin'): ?>
                                            <button class="remove-btn" onclick="openRemoveModal(<?php echo $row['treatment_id']; ?>)">
                                                <i class="fas fa-trash"></i> Remove
                                            </button>
                                        <?php endif; ?>

                                    <?php else: ?>
                                        <div class="deletion-info">
                                            <i class="fas fa-clock"></i> Removed:
                                            <?php echo date('M d, Y', strtotime($row['deleted_at'])); ?>
                                        </div>

                                        <?php if ($_SESSION['role'] === 'admin'): ?>
                                            <button class="restore-btn"
                                                onclick="restoreTreatment(<?php echo $row['appointment_id']; ?>)">
                                                <i class="fas fa-undo"></i> Restore
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php }
                    // Reset the result pointer for table view
                    mysqli_data_seek($result, 0);
                } else { ?>
                    <div class="no-treatments">
                        <i class="fas fa-clipboard-list" style="font-size: 48px; color: #ddd; margin-bottom: 15px;"></i>
                        <h3>No treatments found</h3>
                        <p>There are no confirmed appointments with treatments.</p>
                    </div>
                <?php } ?>
            </div>

            <!-- Table View (Hidden initially) -->
            <div class="list-table" id="tableViewContainer">
                <table>
                    <thead>
                        <tr>
                            <th>Patient Name</th>
                            <th>Appointment Date</th>
                            <th>View Chart</th>
                            <th>Service</th>
                            <th>Notes</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result && mysqli_num_rows($result) > 0) { ?>
                            <?php while ($row = mysqli_fetch_assoc($result)) { ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['patient_name']); ?></td>
                                    <td><?php echo date("F j, Y - l", strtotime($row['appointment_date'])); ?></td>
                                    <td>
                                        <a href="../treatment/chart/chart.php?appointment_id=<?php echo $row['appointment_id']; ?>"
                                            class="viewchart-button">View Chart</a>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['service_name']); ?></td>
                                    <td><?php echo htmlspecialchars($row['notes']); ?></td>
                                    <td>
                                        <?php if (empty($row['is_deleted'])): ?>
                                            <button class='edit' onclick='openEditModal("<?php echo $row['service_id']; ?>", 
                 "<?php echo htmlspecialchars($row['notes']); ?>", 
                 "<?php echo $row['appointment_id']; ?>")'>
                                                <i class='fas fa-edit'></i>
                                            </button>

                                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                                <button class="remove-btn"
                                                    onclick="openRemoveModal(<?php echo $row['appointment_id']; ?>)">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            <?php endif; ?>

                                        <?php else: ?>
                                            <div class="deletion-info">
                                                Removed: <?php echo date('M d, Y', strtotime($row['deleted_at'])); ?>
                                            </div>

                                            <?php if ($_SESSION['role'] === 'admin'): ?>
                                                <button class="restore-btn"
                                                    onclick="restoreTreatment(<?php echo $row['appointment_id']; ?>)">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } else { ?>
                            <tr>
                                <td colspan="6">No confirmed appointments found.</td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Confirm Removal
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"
                        aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to remove this treatment? The record will remain in the database for
                        auditing purposes.</p>
                    <p>This action can be undone by an administrator.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="fas fa-trash me-1"></i>
                        Remove Treatment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // View toggle functionality
        document.getElementById('cardView').addEventListener('click', function () {
            document.getElementById('cardViewContainer').style.display = 'grid';
            document.getElementById('tableViewContainer').style.display = 'none';
            document.getElementById('cardView').classList.add('active');
            document.getElementById('tableView').classList.remove('active');
        });

        document.getElementById('tableView').addEventListener('click', function () {
            document.getElementById('cardViewContainer').style.display = 'none';
            document.getElementById('tableViewContainer').style.display = 'block';
            document.getElementById('cardView').classList.remove('active');
            document.getElementById('tableView').classList.add('active');
        });

        // Search functionality
        document.getElementById('treatmentSearch').addEventListener('keyup', function () {
            const searchText = this.value.toLowerCase();

            // Search in card view
            const cards = document.querySelectorAll('.treatment-card');
            cards.forEach(card => {
                const patientName = card.querySelector('.treatment-header h3').textContent.toLowerCase();
                const serviceName = card.querySelector('.treatment-service').textContent.toLowerCase();
                const notes = card.querySelector('.treatment-notes').textContent.toLowerCase();

                if (patientName.includes(searchText) || serviceName.includes(searchText) || notes.includes(searchText)) {
                    card.style.display = '';
                } else {
                    card.style.display = 'none';
                }
            });

            // Search in table view
            const rows = document.getElementById('tableViewContainer').querySelectorAll('tbody tr');
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                let found = false;

                cells.forEach(cell => {
                    if (cell.textContent.toLowerCase().includes(searchText)) {
                        found = true;
                    }
                });

                if (found) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        // Sidebar toggle functionality
        const sidebarToggle = document.querySelector('#sidebar-toggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', () => {
                document.body.classList.toggle('cs-sidebar-collapsed');
                const isCollapsed = document.body.classList.contains('cs-sidebar-collapsed');

                fetch('../save_sidebar_state.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ collapsed: isCollapsed })
                });
            });
        }

        // Edit modal functionality (placeholder)
        function openEditModal(serviceId, notes, appointmentId) {
            // Placeholder for modal functionality
            alert('Edit modal for service ID: ' + serviceId);
        }

        // Add Bootstrap JS for modals
        document.addEventListener('DOMContentLoaded', function () {


            // Initialize variables
            let selectedTreatmentId = null;
            const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

            // Show deleted toggle functionality
            document.getElementById('showDeletedToggle').addEventListener('change', function () {
                const url = new URL(window.location.href);
                if (this.checked) {
                    url.searchParams.set('show_deleted', '1');
                } else {
                    url.searchParams.delete('show_deleted');
                }
                window.location.href = url.toString();
            });
        });

        // Function to open the remove modal
        function openRemoveModal(treatmentId) {
            console.log("Opening modal for treatment ID:", treatmentId); // Debug

            // Store the treatment ID globally
            window.selectedTreatmentId = treatmentId;

            // Make sure modal is properly initialized
            const modalElement = document.getElementById('deleteModal');
            const modal = new bootstrap.Modal(modalElement);
            modal.show();

            // Set up the confirm button with the correct ID
            document.getElementById('confirmDelete').onclick = function () {
                removeTreatment(window.selectedTreatmentId);
            };
        }

        function removeTreatment(treatmentId) {
            console.log("Removing treatment ID:", treatmentId); // Debug

            // Show loading indicator
            const loadingElement = document.createElement('div');
            loadingElement.className = 'loading-overlay';
            loadingElement.innerHTML = '<div class="spinner"></div>';
            document.body.appendChild(loadingElement);

            // Use FormData to ensure proper parameter submission
            const formData = new FormData();
            formData.append('treatment_id', treatmentId);

            // Send AJAX request
            fetch('soft_delete_treatment.php', {
                method: 'POST',
                body: formData
            })
                .then(response => {
                    console.log("Response status:", response.status); // Debug
                    return response.json();
                })
                .then(data => {
                    console.log("Server response:", data); // Debug
                    if (data.success) {
                        alert(data.message);
                        // Force a hard reload with cache bypass
                        window.location.href = window.location.href.split('?')[0] +
                            '?timestamp=' + new Date().getTime();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                })
                .finally(() => {
                    document.body.removeChild(loadingElement);
                });
        }

        // Function to restore a treatment
        function restoreTreatment(treatmentId) {
            if (!confirm('Are you sure you want to restore this treatment?')) {
                return;
            }

            // Show loading indicator
            const loadingElement = document.createElement('div');
            loadingElement.className = 'loading-overlay';
            loadingElement.innerHTML = '<div class="spinner"></div>';
            document.body.appendChild(loadingElement);

            // Send AJAX request
            fetch('restore_treatment.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `treatment_id=${treatmentId}`
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Show success message
                        alert(data.message);
                        // Reload the page
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred. Please try again.');
                })
                .finally(() => {
                    // Remove loading indicator
                    document.body.removeChild(loadingElement);
                });
        }
    </script>



</body>

</html>