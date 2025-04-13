<?php
include '../database.php';

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = $conn->prepare("SELECT * FROM services WHERE services_id = ?");
    $query->bind_param("i", $id);
    $query->execute();
    $result = $query->get_result();
    $service = $result->fetch_assoc();
} else {
    header("Location: customize.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $imagePath = $service['image_path'];

    // Handle image upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $uploadDir = '../website/image/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = basename($_FILES['image']['name']);
        $targetPath = $uploadDir . $filename;
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetPath)) {
            $imagePath = "image/" . $filename;
        } else {
            echo "Failed to move uploaded file.";
            exit;
        }
    }

    $stmt = $conn->prepare("UPDATE services SET name = ?, description = ?, image_path = ? WHERE services_id = ?");
    $stmt->bind_param("sssi", $name, $description, $imagePath, $id);
    if ($stmt->execute()) {
        header("Location: customize.php?success=Service updated successfully");
        exit();
    } else {
        echo "Error: " . $stmt->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../dashboard/dashboard.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css">
    <title>Edit Service</title>
    <style>
        :root {
            --sidebar-width: 300px;
            --sidebar-collapsed-width: 70px;
            --header-height: 70px;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, sans-serif;
        }

        .content {
            margin-left: var(--sidebar-width);
            padding: 20px;
            padding-top: calc(var(--header-height) + 20px);
            transition: margin-left 0.3s ease;
        }

        body.cs-sidebar-collapsed .content {
            margin-left: var(--sidebar-collapsed-width);
        }

        .form-container {
            max-width: 500px;
            /* Made the form smaller */
            background: #fff;
            padding: 20px;
            border: 1px solid #000069;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
        }

        .form-container h1 {
            text-align: center;
            margin-bottom: 20px;
        }

        .form-container label {
            font-weight: bold;
            margin-top: 10px;
        }

        .form-container input[type="text"],
        .form-container textarea,
        .form-container input[type="file"] {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .form-container button {
            background-color: #4CAF50;
            color: white;
            padding: 10px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }

        .form-container button:hover {
            background-color: #000069;
        }

        .current-image {
            text-align: center;
            margin-bottom: 20px;
        }

        .current-image img {
            max-width: 100%;
            height: auto;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        @media (max-width: 768px) {
            .content {
                margin-left: var(--sidebar-collapsed-width);
                padding: 10px;
            }

            .form-container {
                max-width: 100%;
                margin: 10px;
            }
        }
    </style>
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
        <div class="form-container">
            <h1>Edit Service</h1>
            <div class="current-image">
                <h5>Current Service Image</h5>
                <img src="../website/<?php echo htmlspecialchars($service['image_path']); ?>" alt="Service Image">
            </div>
            <form action="edit_service.php?id=<?php echo $id; ?>" method="POST" enctype="multipart/form-data">
                <label for="name">Service Name:</label>
                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($service['name']); ?>"
                    required>

                <label for="description">Description:</label>
                <textarea id="description" name="description"
                    required><?php echo htmlspecialchars($service['description']); ?></textarea>

                <label for="image">Replace Service Image:</label>
                <input type="file" id="image" name="image" accept="image/*">

                <button type="submit">Update Service</button>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const urlParams = new URLSearchParams(window.location.search);
            const successMessage = urlParams.get('success');

            if (successMessage) {
                showToast('Success', successMessage, 'success');
            }

            function showToast(title, message, type) {
                const toast = document.createElement('div');
                toast.className = `toast align-items-center text-bg-${type === 'success' ? 'success' : 'danger'} border-0`;
                toast.setAttribute('role', 'alert');
                toast.setAttribute('aria-live', 'assertive');
                toast.setAttribute('aria-atomic', 'true');

                toast.innerHTML = `
                    <div class="d-flex">
                        <div class="toast-body">
                            <strong>${title}</strong>: ${message}
                        </div>
                        <button type="button" class="btn-close me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
                    </div>
                `;

                document.querySelector('.toast-container').appendChild(toast);
                const bootstrapToast = new bootstrap.Toast(toast);
                bootstrapToast.show();
            }
        });
    </script>
</body>

</html>