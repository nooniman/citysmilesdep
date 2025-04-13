<?php
// filepath: c:\xampp\htdocs\CitySmilesRepo\old\schedule\get_exceptions.php
require_once __DIR__ . '/../admin_check.php';
include '../database.php';

$dentist_id = isset($_GET['dentist_id']) ? intval($_GET['dentist_id']) : 0;

if ($dentist_id <= 0) {
    echo "<tr><td colspan='3' class='text-center'>Invalid dentist ID</td></tr>";
    exit;
}

// Get exceptions (only upcoming ones)
$today = date('Y-m-d');
$sql = "SELECT * FROM schedule_exceptions 
        WHERE user_id = ? AND exception_date >= ? 
        ORDER BY exception_date 
        LIMIT 10";
$stmt = $conn->prepare($sql);
$stmt->bind_param('is', $dentist_id, $today);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo "<tr><td colspan='3' class='text-center'>No upcoming exceptions</td></tr>";
} else {
    while ($row = $result->fetch_assoc()) {
        $date = date('M d, Y', strtotime($row['exception_date']));
        
        if ($row['is_available'] && $row['start_time'] && $row['end_time']) {
            $start = date('g:i A', strtotime($row['start_time']));
            $end = date('g:i A', strtotime($row['end_time']));
            $status = "<span class='badge bg-warning'>Custom Hours<br>{$start} - {$end}</span>";
        } else {
            $status = "<span class='badge bg-danger'>Not Available</span>";
        }
        
        $reason = !empty($row['reason']) ? 
            "<small class='d-block text-muted'>{$row['reason']}</small>" : '';
        
        echo "<tr>";
        echo "<td>{$date}{$reason}</td>";
        echo "<td>{$status}</td>";
        echo "<td>";
        echo "<button type='button' class='btn btn-sm btn-primary' onclick='openEditExceptionModal({$row['exception_id']})'>
                <i class='fas fa-edit'></i>
              </button>";
        echo "</td>";
        echo "</tr>";
    }
}
?>