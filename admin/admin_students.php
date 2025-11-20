<?php
// admin_students.php
session_start();
require_once 'admin.php';
if (($_SESSION['user_type'] ?? '') !== 'admin') {
    redirect('../index.php');
}
$studentModel = new Admin();

if (isset($_GET['action']) && $_GET['action'] === 'get_student' && isset($_GET['id'])) {
    $student = $studentModel->getStudentById($_GET['id']);
    header('Content-Type: application/json');
    echo json_encode($student ?: []);
    exit;
}

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $redirect = true;
    // Add Student
    if (isset($_POST['add_student'])) {
        $result = $studentModel->addStudent([
            'first_name' => $_POST['first_name'],
            'last_name' => $_POST['last_name'],
            'email' => $_POST['email']
        ]);
        if ($result) {
            $message = "Student added successfully!";
        } else {
            $error = "Failed to add student!";
        }
    }
    
    // Update Student
    if (isset($_POST['update_student'])) {
        $result = $studentModel->updateStudent($_POST['student_id'], $_POST);
        if ($result) {
            $message = "Student updated successfully!";
        } else {
            $error = "Failed to update student!";
        }
    }
    
    // Delete Student
    if (isset($_POST['delete_student'])) {
        $result = $studentModel->deleteStudent($_POST['student_id']);
        if ($result) {
            $message = "Student deleted successfully!";
        } else {
            $error = "Failed to delete student!";
        }
    }
    
    // CSV Import
    if (isset($_POST['import_students']) && isset($_FILES['csv_file'])) {
        $result = $studentModel->importCSV($_FILES['csv_file']);
        
        if ($result['success_count'] > 0) {
            $message = "Successfully imported/updated {$result['success_count']} student records!";
        }
        if (!empty($result['errors'])) {
            $error = "Import completed with " . count($result['errors']) . " errors. See details below.";
            $import_errors = $result['errors'];
        }
    }
    
    // Bulk Actions
    if (isset($_POST['bulk_action'])) {
        $studentIds = $_POST['student_ids'] ?? [];
        
        if ($_POST['bulk_action'] === 'delete' && !empty($studentIds)) {
            $result = $studentModel->bulkDeleteStudents($studentIds);
            if ($result) {
                $message = "Selected students deleted successfully!";
            } else {
                $error = "Failed to delete students!";
            }
        }
        
        if ($_POST['bulk_action'] === 'export' && !empty($studentIds)) {
            $studentModel->bulkExportStudents($studentIds);
        }
    }
    
    // Photo Upload
    if (isset($_POST['upload_photo']) && isset($_FILES['student_photo'])) {
        $result = $studentModel->uploadStudentPhoto($_POST['student_id'], $_FILES['student_photo']);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
    
    // Delete Photo
    if (isset($_POST['delete_photo'])) {
        $result = $studentModel->deleteStudentPhoto($_POST['student_id']);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Handle individual Student ID assignment (GET request)
if (isset($_GET['action']) && $_GET['action'] === 'assign_id' && isset($_GET['email'])) {
    $success = $studentModel->assignStudentID($_GET['email'], $_GET['student_id']);
    
    if ($success) {
        $message = "Student ID assigned successfully!";
    } else {
        $error = "Error assigning Student ID.";
    }
    header("Location: admin_students.php");
    exit();
}

// Get filters from GET or POST
$filters = [
    'course' => $_GET['course'] ?? $_POST['course'] ?? '',
    'year_level' => $_GET['year_level'] ?? $_POST['year_level'] ?? '',
    'profile_completed' => $_GET['profile_completed'] ?? $_POST['profile_completed'] ?? ''
];

// Get students based on filters or search
$hasSearch = isset($_GET['search_keyword']) && !empty($_GET['search_keyword']);
$hasFilters = isset($_GET['course']) && $_GET['course'] !== '' || 
              isset($_GET['year_level']) && $_GET['year_level'] !== '' || 
              isset($_GET['profile_completed']) && $_GET['profile_completed'] !== '';

if ($hasSearch) {
    $students = $studentModel->searchStudents($_GET['search_keyword']);
} else if ($hasFilters) {
    $students = $studentModel->filterStudents($filters);
} else {
    $students = $studentModel->getAllStudents();
}
// Check account status for each student
foreach ($students as &$student) {
    $accountInfo = $studentModel->checkStudentHasAccount($student['email']);
    
    $student['has_account'] = ($accountInfo !== false);
    $student['user_data'] = $accountInfo;
    $student['role'] = $accountInfo['role'] ?? null;
    $student['is_verified'] = $accountInfo['is_verified'] ?? false;
}
unset($student);


// Get student counts for dashboard
$totalStudents = $studentModel->countStudentsByFilters(['deleted_at' => 'active']);
$completedProfiles = $studentModel->countStudentsByFilters(['profile_completed' => 1, 'deleted_at' => 'active']);
$incompleteProfiles = $studentModel->countStudentsByFilters(['profile_completed' => 0, 'deleted_at' => 'active']);
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Student Management</title>
</head>

<body>
    <h1>Student Management</h1>

    <!-- Display Messages -->
    <?php if (isset($message)): ?>
    <div style="color: green; padding: 10px; border: 1px solid green; margin: 10px 0;"><?= $message ?></div>
    <?php endif; ?>

    <?php if (isset($error)): ?>
    <div style="color: red; padding: 10px; border: 1px solid red; margin: 10px 0;"><?= $error ?></div>
    <?php endif; ?>

    <!-- Display Import Errors -->
    <?php if (isset($import_errors) && !empty($import_errors)): ?>
    <div style="color: orange; padding: 10px; border: 1px solid orange; margin: 10px 0;">
        <strong>Import Errors:</strong>
        <ul>
            <?php foreach ($import_errors as $import_error): ?>
            <li><?= htmlspecialchars($import_error) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <!-- Statistics Dashboard -->
    <div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">
        <h3>Statistics</h3>
        <p>Total Students: <?= $totalStudents ?></p>
        <p>Completed Profiles: <?= $completedProfiles ?></p>
        <p>Incomplete Profiles: <?= $incompleteProfiles ?></p>
    </div>

    <!-- Search and Filter Section -->
    <div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">
        <h3>Search & Filter</h3>

        <!-- Search Form -->
        <form method="GET" style="margin-bottom: 10px;">
            <input type="text" name="search_keyword" placeholder="Search by name or email"
                value="<?= $_GET['search_keyword'] ?? '' ?>">
            <button type="submit" name="search">Search</button>
            <a href="admin_students.php">Clear</a>
        </form>

        <!-- Filter Form -->
        <form method="GET">
            <select name="course">
                <option value="">All Courses</option>
                <option value="BS Computer Science"
                    <?= ($filters['course'] === 'BS Computer Science') ? 'selected' : '' ?>>BS Computer Science</option>
                <option value="BS Engineering" <?= ($filters['course'] === 'BS Engineering') ? 'selected' : '' ?>>BS
                    Engineering</option>
                <option value="BS Information System"
                    <?= ($filters['course'] === 'BS Information System') ? 'selected' : '' ?>>BS Information System
                </option>
                <option value="BS Nursing" <?= ($filters['course'] === 'BS Nursing') ? 'selected' : '' ?>>BS Nursing
                </option>
                <option value="BS Midwifery" <?= ($filters['course'] === 'BS Midwifery') ? 'selected' : '' ?>>BS
                    Midwifery</option>
                <option value="BS Psychology" <?= ($filters['course'] === 'BS Psychology') ? 'selected' : '' ?>>BS
                    Psychology</option>
            </select>

            <select name="year_level">
                <option value="">All Year Levels</option>
                <option value="1st Year" <?= ($filters['year_level'] === '1st Year') ? 'selected' : '' ?>>1st Year
                </option>
                <option value="2nd Year" <?= ($filters['year_level'] === '2nd Year') ? 'selected' : '' ?>>2nd Year
                </option>
                <option value="3rd Year" <?= ($filters['year_level'] === '3rd Year') ? 'selected' : '' ?>>3rd Year
                </option>
                <option value="4th Year" <?= ($filters['year_level'] === '4th Year') ? 'selected' : '' ?>>4th Year
                </option>
            </select>

            <select name="profile_completed">
                <option value="">All Profiles</option>
                <option value="1" <?= ($filters['profile_completed'] === '1') ? 'selected' : '' ?>>Completed</option>
                <option value="0" <?= ($filters['profile_completed'] === '0') ? 'selected' : '' ?>>Incomplete</option>
            </select>

            <button type="submit">Apply Filters</button>
        </form>
    </div>

    <!-- CSV Import Section -->
    <div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">
        <h3>Import Students from CSV</h3>
        <form method="POST" enctype="multipart/form-data">
            <input type="file" name="csv_file" accept=".csv" required>
            <button type="submit" name="import_students">Import CSV</button>
        </form>
        <p><small>CSV format: email, student_id, first_name, last_name</small></p>
    </div>

    <!-- Add Student Form -->
    <div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">
        <h3>Add New Student</h3>
        <form method="POST">
            <input type="text" name="first_name" placeholder="First Name" required>
            <input type="text" name="last_name" placeholder="Last Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <button type="submit" name="add_student">Add Student</button>
        </form>
    </div>

    <!-- Students List -->
    <div style="border: 1px solid #ccc; padding: 10px; margin: 10px 0;">
        <h3>Students List (<?= count($students) ?> students)</h3>

        <!-- Bulk Actions Form -->
        <form method="POST" id="bulkForm">
            <div style="margin-bottom: 10px;">
                <button type="submit" name="bulk_action" value="export">Export Selected</button>
                <button type="submit" name="bulk_action" value="delete"
                    onclick="return confirm('Are you sure you want to delete selected students?')">Delete
                    Selected</button>
            </div>

            <table border="1" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Student ID</th>
                        <th>Course</th>
                        <th>Year Level</th>
                        <th>Profile Completed</th>
                        <th>Account Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students as $student): ?>
                    <tr>
                        <td><input type="checkbox" name="student_ids[]" value="<?= $student['id'] ?>"
                                class="student-checkbox"></td>
                        <td><?= $student['id'] ?></td>
                        <td><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></td>
                        <td><?= htmlspecialchars($student['email']) ?></td>
                        <td>
                            <?php if (empty($student['student_id'])): ?>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="assign_id">
                                <input type="hidden" name="email" value="<?= $student['email'] ?>">
                                <input type="text" name="student_id" placeholder="Enter ID" size="10" required>
                                <button type="submit">Assign</button>
                            </form>
                            <?php else: ?>
                            <?= htmlspecialchars($student['student_id']) ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($student['course'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($student['year_level'] ?? 'N/A') ?></td>
                        <td><?= $student['profile_completed'] ? 'Yes' : 'No' ?></td>
                        <td>
                            <?php if (!empty($student['user_data']['user_data']['user_id'])): ?>
                            <span style="color: green;">✅ Registered</span><br>
                            Verified: <?= !empty($student['user_data']['user_data']['is_verified']) ? 'Yes' : 'No' ?>
                            <?php else: ?>
                            <span style="color: red;">❌ No account</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <!-- View Button that opens student details page -->
                            <a href="student_details.php?id=<?= $student['id'] ?>" style="text-decoration: none;">
                                <button type="button">View</button>
                            </a>

                            <!-- Delete Button -->
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                <button type="submit" name="delete_student"
                                    onclick="return confirm('Are you sure you want to delete this student?')">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="10" style="text-align: center;">No students found</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </form>
    </div>

    <!-- Edit Student Modal (hidden by default) -->
    <div id="editModal"
        style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 20px; border: 1px solid #ccc; z-index: 1000;">
        <h3>Edit Student</h3>
        <form method="POST" id="editForm">
            <input type="hidden" name="student_id" id="edit_student_id">
            <div>
                <label>First Name:</label>
                <input type="text" name="first_name" id="edit_first_name" required>
            </div>
            <div>
                <label>Last Name:</label>
                <input type="text" name="last_name" id="edit_last_name" required>
            </div>
            <div>
                <label>Email:</label>
                <input type="email" name="email" id="edit_email" required>
            </div>
            <div>
                <label>Student ID:</label>
                <input type="text" name="student_id" id="edit_student_id_field">
            </div>
            <div>
                <label>Course:</label>
                <input type="text" name="course" id="edit_course">
            </div>
            <div>
                <label>Year Level:</label>
                <input type="text" name="year_level" id="edit_year_level">
            </div>
            <div>
                <label>Contact Number:</label>
                <input type="text" name="contact_number" id="edit_contact_number">
            </div>
            <div>
                <button type="submit" name="update_student">Update Student</button>
                <button type="button" onclick="closeEditModal()">Cancel</button>
            </div>
        </form>
    </div>

    <script>
    // Select All functionality
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.student-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
    });

    // Edit Modal functions
    function openEditModal(studentId) {
        fetch('?action=get_student&id=' + studentId)
            .then(response => response.json())
            .then(student => {
                document.getElementById('edit_student_id').value = student.id;
                document.getElementById('edit_first_name').value = student.first_name || '';
                document.getElementById('edit_last_name').value = student.last_name || '';
                document.getElementById('edit_email').value = student.email || '';
                document.getElementById('edit_student_id_field').value = student.student_id || '';
                document.getElementById('edit_course').value = student.course || '';
                document.getElementById('edit_year_level').value = student.year_level || '';
                document.getElementById('edit_contact_number').value = student.contact_number || '';
                document.getElementById('editModal').style.display = 'block';
            })
            .catch(error => {
                console.error('Error fetching student:', error);
                alert('Error loading student data');
            });
    }

    function closeEditModal() {
        document.getElementById('editModal').style.display = 'none';
    }

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
        const modal = document.getElementById('editModal');
        if (event.target === modal) {
            closeEditModal();
        }
    });
    </script>
</body>

</html>