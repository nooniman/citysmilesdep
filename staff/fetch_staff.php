<?php
include '../database.php';

$sql = "SELECT id, first_name, last_name, middle_name, email, contact, gender, role, image FROM users WHERE role IN ('staff', 'assistant', 'intern', 'dentist')";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $full_name = htmlspecialchars($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
        $image_path = !empty($row['image']) ? htmlspecialchars($row['image']) : 'default.jpg';

        echo "<tr>
                 
            <td>
            <div class='staff-image-container'>
                <img src='<?= $image_path ?>' alt='Staff Photo' class='staff-image'>
            </div>
        </td>

                <td>$full_name</td>
                <td>{$row['gender']}</td>
                <td>{$row['contact']}</td>
                <td>{$row['email']}</td>
                <td>{$row['role']}</td>
                <td>
                    <button class='edit-btn' data-id='{$row['id']}' data-first='{$row['first_name']}' data-last='{$row['last_name']}' 
                            data-middle='{$row['middle_name']}' data-gender='{$row['gender']}' data-contact='{$row['contact']}' 
                            data-email='{$row['email']}' data-role='{$row['role']}' data-image='$image_path'>
                        <i class='fas fa-edit'></i>
                    </button>
                    <button class='delete-btn' data-id='{$row['id']}'><i class='fas fa-trash-alt'></i></button>
                </td>
              </tr>";
    }
} else {
    echo "<tr><td colspan='7'>No staff found</td></tr>";
}

$conn->close();
?>