<?php
session_start();
include '../database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login/login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        // Add new family member
        if ($_POST['action'] === 'add') {
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $birthdate = $_POST['birthdate'];
            $gender = $_POST['gender'];
            $relationship = $_POST['relationship'];
            $contact = $_POST['contact'] ?? '';
            $email = $_POST['email'] ?? '';
            $medical_notes = $_POST['medical_notes'] ?? '';

            $stmt = $conn->prepare("INSERT INTO family_members 
                (user_id, first_name, last_name, birthdate, gender, relationship, contact_number, email, medical_notes)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("issssssss", $user_id, $first_name, $last_name, $birthdate, $gender, $relationship, $contact, $email, $medical_notes);

            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Family member added successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Failed to add family member: ' . $stmt->error . '</div>';
            }
        }

        // Delete family member
        else if ($_POST['action'] === 'delete' && isset($_POST['member_id'])) {
            $member_id = $_POST['member_id'];

            $stmt = $conn->prepare("DELETE FROM family_members WHERE member_id = ? AND user_id = ?");
            $stmt->bind_param("ii", $member_id, $user_id);

            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Family member removed successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Failed to remove family member.</div>';
            }
        }

        // Edit family member
        else if ($_POST['action'] === 'edit' && isset($_POST['member_id'])) {
            $member_id = $_POST['member_id'];
            $first_name = $_POST['first_name'];
            $last_name = $_POST['last_name'];
            $birthdate = $_POST['birthdate'];
            $gender = $_POST['gender'];
            $relationship = $_POST['relationship'];
            $contact = $_POST['contact'] ?? '';
            $email = $_POST['email'] ?? '';
            $medical_notes = $_POST['medical_notes'] ?? '';

            $stmt = $conn->prepare("UPDATE family_members SET 
                first_name = ?, last_name = ?, birthdate = ?, gender = ?, 
                relationship = ?, contact_number = ?, email = ?, medical_notes = ? 
                WHERE member_id = ? AND user_id = ?");
            $stmt->bind_param(
                "sssssssii",
                $first_name,
                $last_name,
                $birthdate,
                $gender,
                $relationship,
                $contact,
                $email,
                $medical_notes,
                $member_id,
                $user_id
            );

            if ($stmt->execute()) {
                $message = '<div class="alert alert-success">Family member updated successfully!</div>';
            } else {
                $message = '<div class="alert alert-danger">Failed to update family member.</div>';
            }
        }
    }
}

// Get all family members for the current user
$stmt = $conn->prepare("SELECT * FROM family_members WHERE user_id = ? ORDER BY first_name, last_name");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$family_members = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Family Members - City Smiles Dental Clinic</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f5f7fa;
        }

        .content {
            margin-left: 20%;
            margin-top: 12vh;
            padding: 30px;
            background-color: #f5f7fa;
            min-height: calc(100vh - 12vh);
        }

        .page-title {
            color: #297859;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
        }

        .page-title i {
            margin-right: 10px;
            color: #7B32AB;
            font-size: 1.8rem;
        }

        .family-section {
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            padding: 20px;
            margin-bottom: 30px;
        }

        .family-section .header {
            border-bottom: 1px solid #e9ecef;
            margin-bottom: 20px;
            padding-bottom: 10px;
            color: #297859;
        }

        .family-section .header h5 {
            font-weight: 600;
            margin-bottom: 0;
        }

        .member-card {
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            transition: all 0.3s;
        }

        .member-card:hover {
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05);
        }

        .member-card .member-name {
            font-weight: 600;
            color: #297859;
            margin-bottom: 10px;
        }

        .member-card .member-info {
            color: #6c757d;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .btn-primary {
            background-color: #297859;
            border-color: #297859;
        }

        .btn-primary:hover {
            background-color: #1f5a40;
            border-color: #1f5a40;
        }

        @media (max-width: 992px) {
            .content {
                margin-left: 0;
                padding: 15px;
            }
        }
    </style>
</head>

<body>
    <?php include 'user_sidebar.php'; ?>

    <div class="content">
        <div class="page-title">
            <i class="fas fa-users"></i>
            <h1>Family Members</h1>
        </div>

        <?php echo $message; ?>

        <div class="family-section">
            <div class="header d-flex justify-content-between align-items-center">
                <h5><i class="fas fa-users me-2"></i> Your Family Members</h5>
                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#addMemberModal">
                    <i class="fas fa-plus me-2"></i> Add Family Member
                </button>
            </div>

            <div class="row">
                <?php if (count($family_members) > 0): ?>
                    <?php foreach ($family_members as $member): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="member-card">
                                <div class="d-flex justify-content-between">
                                    <div class="member-name">
                                        <?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>
                                    </div>
                                    <div>
                                        <button class="btn btn-sm btn-outline-primary edit-member" data-bs-toggle="modal"
                                            data-bs-target="#editMemberModal" data-id="<?php echo $member['member_id']; ?>"
                                            data-firstname="<?php echo htmlspecialchars($member['first_name']); ?>"
                                            data-lastname="<?php echo htmlspecialchars($member['last_name']); ?>"
                                            data-birthdate="<?php echo $member['birthdate']; ?>"
                                            data-gender="<?php echo htmlspecialchars($member['gender']); ?>"
                                            data-relationship="<?php echo htmlspecialchars($member['relationship']); ?>"
                                            data-contact="<?php echo htmlspecialchars($member['contact_number']); ?>"
                                            data-email="<?php echo htmlspecialchars($member['email']); ?>"
                                            data-notes="<?php echo htmlspecialchars($member['medical_notes']); ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-danger delete-member" data-bs-toggle="modal"
                                            data-bs-target="#deleteMemberModal" data-id="<?php echo $member['member_id']; ?>"
                                            data-name="<?php echo htmlspecialchars($member['first_name'] . ' ' . $member['last_name']); ?>">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </div>
                                </div>
                                <div class="member-info">
                                    <i class="fas fa-user-tag me-2"></i>
                                    <?php echo htmlspecialchars($member['relationship']); ?>
                                </div>
                                <div class="member-info">
                                    <i class="fas fa-birthday-cake me-2"></i>
                                    <?php echo date('M d, Y', strtotime($member['birthdate'])); ?>
                                    (<?php echo calculateAge($member['birthdate']); ?> years)
                                </div>
                                <?php if (!empty($member['contact_number'])): ?>
                                    <div class="member-info">
                                        <i class="fas fa-phone me-2"></i> <?php echo htmlspecialchars($member['contact_number']); ?>
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($member['email'])): ?>
                                    <div class="member-info">
                                        <i class="fas fa-envelope me-2"></i> <?php echo htmlspecialchars($member['email']); ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-users fa-3x mb-3 text-muted"></i>
                        <h5 class="text-muted">No family members added yet</h5>
                        <p class="text-muted">Add your family members to book appointments for them.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Add Member Modal -->
    <div class="modal fade" id="addMemberModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user-plus me-2"></i> Add Family Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="family_members.php" method="post">
                        <input type="hidden" name="action" value="add">

                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" class="form-control" required>
                            </div>
                            <div class="col">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Relationship</label>
                            <select name="relationship" class="form-select" required>
                                <option value="">-- Select Relationship --</option>
                                <option value="Son">Son</option>
                                <option value="Daughter">Daughter</option>
                                <option value="Spouse">Spouse</option>
                                <option value="Parent">Parent</option>
                                <option value="Sibling">Sibling</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Birthdate</label>
                            <input type="date" name="birthdate" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" class="form-select" required>
                                <option value="">-- Select Gender --</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact Number (Optional)</label>
                            <input type="text" name="contact" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email (Optional)</label>
                            <input type="email" name="email" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Medical Notes (Optional)</label>
                            <textarea name="medical_notes" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Add Family Member</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Member Modal -->
    <div class="modal fade" id="editMemberModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-edit me-2"></i> Edit Family Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="family_members.php" method="post">
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="member_id" id="edit-member-id">

                        <!-- Same fields as add form but with id attributes for JS filling -->
                        <div class="row mb-3">
                            <div class="col">
                                <label class="form-label">First Name</label>
                                <input type="text" name="first_name" id="edit-first-name" class="form-control" required>
                            </div>
                            <div class="col">
                                <label class="form-label">Last Name</label>
                                <input type="text" name="last_name" id="edit-last-name" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Relationship</label>
                            <select name="relationship" id="edit-relationship" class="form-select" required>
                                <option value="">-- Select Relationship --</option>
                                <option value="Son">Son</option>
                                <option value="Daughter">Daughter</option>
                                <option value="Spouse">Spouse</option>
                                <option value="Parent">Parent</option>
                                <option value="Sibling">Sibling</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Birthdate</label>
                            <input type="date" name="birthdate" id="edit-birthdate" class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Gender</label>
                            <select name="gender" id="edit-gender" class="form-select" required>
                                <option value="">-- Select Gender --</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                                <option value="Other">Other</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Contact Number (Optional)</label>
                            <input type="text" name="contact" id="edit-contact" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Email (Optional)</label>
                            <input type="email" name="email" id="edit-email" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Medical Notes (Optional)</label>
                            <textarea name="medical_notes" id="edit-notes" class="form-control" rows="3"></textarea>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Member Modal -->
    <div class="modal fade" id="deleteMemberModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-trash-alt me-2"></i> Remove Family Member</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to remove <strong id="delete-member-name"></strong> from your family
                        members?</p>
                    <form action="family_members.php" method="post">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="member_id" id="delete-member-id">

                        <div class="d-flex justify-content-end">
                            <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Remove</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Helper function to calculate age
        function calculateAge(birthdate) {
            const today = new Date();
            const birthDate = new Date(birthdate);
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            return age;
        }

        // Fill edit modal with member data
        document.querySelectorAll('.edit-member').forEach(button => {
            button.addEventListener('click', function () {
                const memberId = this.getAttribute('data-id');
                const firstName = this.getAttribute('data-firstname');
                const lastName = this.getAttribute('data-lastname');
                const birthdate = this.getAttribute('data-birthdate');
                const gender = this.getAttribute('data-gender');
                const relationship = this.getAttribute('data-relationship');
                const contact = this.getAttribute('data-contact');
                const email = this.getAttribute('data-email');
                const notes = this.getAttribute('data-notes');

                document.getElementById('edit-member-id').value = memberId;
                document.getElementById('edit-first-name').value = firstName;
                document.getElementById('edit-last-name').value = lastName;
                document.getElementById('edit-birthdate').value = birthdate;
                document.getElementById('edit-gender').value = gender;
                document.getElementById('edit-relationship').value = relationship;
                document.getElementById('edit-contact').value = contact;
                document.getElementById('edit-email').value = email;
                document.getElementById('edit-notes').value = notes;
            });
        });

        // Fill delete modal with member info
        document.querySelectorAll('.delete-member').forEach(button => {
            button.addEventListener('click', function () {
                const memberId = this.getAttribute('data-id');
                const memberName = this.getAttribute('data-name');

                document.getElementById('delete-member-id').value = memberId;
                document.getElementById('delete-member-name').textContent = memberName;
            });
        });
    </script>
</body>

</html>

<?php
// Helper function to calculate age
function calculateAge($birthdate)
{
    $today = new DateTime();
    $birth = new DateTime($birthdate);
    $interval = $today->diff($birth);
    return $interval->y;
}
?>