<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\schedule\debug_calendar.php
require_once '../database.php';

// Enable full error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Calendar Debug Tool</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.css">
    <style>
        .fc-day-available { background-color: rgba(0, 255, 0, 0.1) !important; }
        .time-slot {
            padding: 8px 12px;
            margin: 5px;
            border: 1px solid #ccc;
            border-radius: 4px;
            cursor: pointer;
        }
        .time-slot.unavailable {
            background-color: #f8d7da;
            border-color: #f5c6cb;
            color: #721c24;
            cursor: not-allowed;
            opacity: 0.7;
        }
        .response-box {
            background-color: #f8f9fa;
            border-radius: 4px;
            padding: 10px;
            font-family: monospace;
            font-size: 12px;
            height: 200px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Calendar Debugging Tool</h1>
        
        <div class="row mb-4">
            <div class="col">
                <div class="card">
                    <div class="card-header">
                        <h5>Database Status</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Check dentist_availability table
                        $table_check = $conn->query("SHOW TABLES LIKE 'dentist_availability'");
                        $availability_exists = $table_check->num_rows > 0;
                        
                        echo "<p>dentist_availability table: " . ($availability_exists ? "✅ Exists" : "❌ Missing") . "</p>";
                        
                        if ($availability_exists) {
                            $count_query = "SELECT COUNT(*) as count FROM dentist_availability";
                            $count_result = $conn->query($count_query);
                            $count = $count_result->fetch_assoc()['count'];
                            echo "<p>Records in dentist_availability: $count</p>";
                            
                            $dentist_query = "SELECT COUNT(DISTINCT user_id) as count FROM dentist_availability";
                            $dentist_result = $conn->query($dentist_query);
                            $dentist_count = $dentist_result->fetch_assoc()['count'];
                            echo "<p>Unique dentists with availability: $dentist_count</p>";
                        }
                        
                        // Check appointments table
                        $appt_table_check = $conn->query("SHOW TABLES LIKE 'appointments'");
                        $appt_exists = $appt_table_check->num_rows > 0;
                        
                        echo "<p>appointments table: " . ($appt_exists ? "✅ Exists" : "❌ Missing") . "</p>";
                        
                        if ($appt_exists) {
                            $columns_query = "SHOW COLUMNS FROM appointments";
                            $columns_result = $conn->query($columns_query);
                            $columns = [];
                            while ($row = $columns_result->fetch_assoc()) {
                                $columns[] = $row['Field'];
                            }
                            echo "<p>Columns: " . implode(", ", $columns) . "</p>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Test Settings</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="service_id" class="form-label">Service</label>
                            <select id="service_id" class="form-select">
                                <option value="">-- Select Service --</option>
                                <?php
                                $services_query = "SELECT services_id, name FROM services ORDER BY name";
                                $services_result = $conn->query($services_query);
                                if ($services_result) {
                                    while ($service = $services_result->fetch_assoc()) {
                                        echo "<option value='{$service['services_id']}'>{$service['name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="dentist_id" class="form-label">Dentist</label>
                            <select id="dentist_id" class="form-select">
                                <option value="">Any Available Dentist</option>
                                <?php
                                $dentists_query = "SELECT id, first_name, last_name FROM users WHERE role = 'dentist' ORDER BY last_name, first_name";
                                $dentists_result = $conn->query($dentists_query);
                                if ($dentists_result) {
                                    while ($dentist = $dentists_result->fetch_assoc()) {
                                        echo "<option value='{$dentist['id']}'>Dr. {$dentist['first_name']} {$dentist['last_name']}</option>";
                                    }
                                }
                                ?>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="selected_date" class="form-label">Selected Date</label>
                            <input type="date" id="selected_date" class="form-control" value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        
                        <div class="d-grid gap-2">
                            <button id="test-dates" class="btn btn-primary">Test Available Dates</button>
                            <button id="test-slots" class="btn btn-secondary">Test Time Slots</button>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5>API Response</h5>
                    </div>
                    <div class="card-body">
                        <div id="response" class="response-box">
                            Select options and click a button to test
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5>Calendar Preview</h5>
                    </div>
                    <div class="card-body">
                        <div id="calendar"></div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5>Time Slots</h5>
                    </div>
                    <div class="card-body">
                        <div id="time-slots" class="row"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.11.3/main.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let calendar;
            const calendarEl = document.getElementById('calendar');
            
            // Initialize calendar
            if (calendarEl) {
                calendar = new FullCalendar.Calendar(calendarEl, {
                    initialView: 'dayGridMonth',
                    headerToolbar: {
                        left: 'prev,next today',
                        center: 'title',
                        right: 'dayGridMonth'
                    },
                    dateClick: function(info) {
                        document.getElementById('selected_date').value = info.dateStr;
                    }
                });
                calendar.render();
            }
            
            // Test Dates button
            document.getElementById('test-dates').addEventListener('click', function() {
                const serviceId = document.getElementById('service_id').value;
                const dentistId = document.getElementById('dentist_id').value;
                
                if (!serviceId) {
                    alert('Please select a service');
                    return;
                }
                
                const responseEl = document.getElementById('response');
                responseEl.innerHTML = 'Loading available dates...';
                
                const startDate = calendar.view.activeStart.toISOString().split('T')[0];
                const endDate = calendar.view.activeEnd.toISOString().split('T')[0];
                
                fetch(`get_available_dates.php?start=${startDate}&end=${endDate}&dentist_id=${dentistId || ''}&service_id=${serviceId}`)
                    .then(response => response.text())
                    .then(text => {
                        responseEl.innerHTML = text;
                        
                        try {
                            const data = JSON.parse(text);
                            
                            // Update calendar with new events
                            calendar.removeAllEvents();
                            calendar.addEventSource(data);
                        } catch (e) {
                            console.error('Error parsing JSON:', e);
                        }
                    })
                    .catch(error => {
                        responseEl.innerHTML = 'Error: ' + error;
                    });
            });
            
            // Test Time Slots button
            document.getElementById('test-slots').addEventListener('click', function() {
                const serviceId = document.getElementById('service_id').value;
                const dentistId = document.getElementById('dentist_id').value;
                const date = document.getElementById('selected_date').value;
                
                if (!serviceId || !date) {
                    alert('Please select a service and date');
                    return;
                }
                
                const responseEl = document.getElementById('response');
                const slotsEl = document.getElementById('time-slots');
                
                responseEl.innerHTML = 'Loading time slots...';
                slotsEl.innerHTML = 'Loading...';
                
                fetch(`get_available_slots.php?date=${date}&dentist_id=${dentistId || ''}&service_id=${serviceId}`)
                    .then(response => response.text())
                    .then(text => {
                        responseEl.innerHTML = text;
                        
                        try {
                            const data = JSON.parse(text);
                            
                            if (data.error) {
                                slotsEl.innerHTML = `<div class="alert alert-danger">${data.error}</div>`;
                                return;
                            }
                            
                            if (!Array.isArray(data) || data.length === 0) {
                                slotsEl.innerHTML = '<div class="alert alert-warning">No available time slots found for this date</div>';
                                return;
                            }
                            
                            let html = '';
                            data.forEach(slot => {
                                const time = slot.time.split(':');
                                let hours = parseInt(time[0]);
                                const minutes = time[1];
                                const ampm = hours >= 12 ? 'PM' : 'AM';
                                hours = hours % 12 || 12;
                                const formattedTime = `${hours}:${minutes} ${ampm}`;
                                
                                const availableClass = slot.available ? '' : 'unavailable';
                                
                                html += `
                                    <div class="col-md-4 col-6">
                                        <button class="time-slot ${availableClass}" 
                                                data-time="${slot.time}" 
                                                data-dentist="${slot.dentist_id}">
                                            ${formattedTime} - ${slot.dentist_name || 'Unknown'}
                                        </button>
                                    </div>
                                `;
                            });
                            
                            slotsEl.innerHTML = html;
                        } catch (e) {
                            console.error('Error parsing JSON:', e);
                            slotsEl.innerHTML = `<div class="alert alert-danger">Error parsing response: ${e.message}</div>`;
                        }
                    })
                    .catch(error => {
                        responseEl.innerHTML = 'Error: ' + error;
                        slotsEl.innerHTML = '<div class="alert alert-danger">Error loading time slots</div>';
                    });
            });
        });
    </script>
</body>
</html>