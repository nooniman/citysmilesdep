<?php
session_start();
include '../database.php';

// Ensure a valid user session exists
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] == 0) {
  die("User not logged in. Please log in first.");
}
$user_id = $_SESSION['user_id'];

// Use p.user_id (not p.patient_info_id) to match the users table.
$sql = "SELECT pr.date_prescripted, pr.medicine, pr.notes 
        FROM prescriptions pr
        JOIN appointments a ON pr.appointment_id = a.appointment_id
        JOIN patients p ON a.patient_id = p.patient_info_id
        WHERE p.user_id = ?
        ORDER BY pr.created_at DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Prescriptions | City Smile Dental Clinic</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f5f7fa;
      color: #333;
      margin: 0;
      padding: 0;
    }

    .content {
      margin-left: 0;
      margin-top: 12vh;
      padding: 30px;
      background-color: #f5f7fa;
      min-height: calc(100vh - 12vh);
    }

    .page-title {
      margin-bottom: 20px;
      color: #333;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .page-title i {
      color: #7B32AB;
      font-size: 1.5rem;
    }

    .prescription-card {
      background: white;
      border-radius: 12px;
      box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
      padding: 25px;
      margin-bottom: 25px;
    }

    .card-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      border-bottom: 1px solid #eaeaea;
      padding-bottom: 15px;
    }

    .card-header h2 {
      font-size: 1.5rem;
      color: #7B32AB;
      margin: 0;
      font-weight: 600;
    }

    .prescription-table {
      width: 100%;
      border-collapse: collapse;
    }

    .prescription-table th {
      background-color: #f8f9fc;
      color: #555;
      font-weight: 600;
      text-align: left;
      padding: 15px;
      font-size: 0.9rem;
      border-bottom: 2px solid #eaeaea;
      text-transform: uppercase;
    }

    .prescription-table td {
      padding: 15px;
      border-bottom: 1px solid #eaeaea;
      color: #555;
      font-size: 0.95rem;
    }

    .prescription-table tr:hover {
      background-color: #f8f9fc;
    }

    .prescription-date {
      font-weight: 500;
      color: #7B32AB;
    }

    .prescription-notes {
      max-width: 300px;
      white-space: pre-wrap;
    }

    .empty-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 50px 20px;
      text-align: center;
      color: #888;
    }

    .empty-state i {
      font-size: 3rem;
      color: #d1d1d1;
      margin-bottom: 20px;
    }

    .empty-state p {
      max-width: 400px;
      margin: 0 auto;
    }

    @media (max-width: 992px) {
      .content {
        margin-left: 0;
        padding: 20px;
      }

      .prescription-table th,
      .prescription-table td {
        padding: 12px 10px;
      }
    }
  </style>
</head>

<body>
  <?php include 'user_sidebar.php'; ?>

  <main class="content">
    <div class="page-title">
      <i class="fa-solid fa-prescription-bottle-medical"></i>
      <h1>My Prescriptions</h1>
    </div>

    <div class="prescription-card">
      <div class="card-header">
        <h2>Prescription History</h2>
      </div>

      <?php if ($result && $result->num_rows > 0): ?>
        <table class="prescription-table">
          <thead>
            <tr>
              <th>Date Prescribed</th>
              <th>Medication</th>
              <th>Instructions</th>
            </tr>
          </thead>
          <tbody>
            <?php while ($row = $result->fetch_assoc()): ?>
              <tr>
                <td class="prescription-date">
                  <?php echo date("F j, Y", strtotime($row['date_prescripted'])); ?>
                </td>
                <td>
                  <strong><?php echo htmlspecialchars($row['medicine']); ?></strong>
                </td>
                <td class="prescription-notes">
                  <?php echo nl2br(htmlspecialchars($row['notes'])); ?>
                </td>
              </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="empty-state">
          <i class="fa-solid fa-clipboard-list"></i>
          <h3>No Prescriptions Found</h3>
          <p>You don't have any prescriptions in your record yet. After your dental appointment, any medications
            prescribed will appear here.</p>
        </div>
      <?php endif; ?>
    </div>
  </main>

  <script>
    // Optional: Add interactive elements like collapsible sections if needed
    document.addEventListener('DOMContentLoaded', function () {
      // Any JavaScript functionality can go here
    });
  </script>
</body>

</html>