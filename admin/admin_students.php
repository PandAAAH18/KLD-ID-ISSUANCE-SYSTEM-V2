<?php
// admin_students.php
session_start();
require_once 'classes/StudentManager.php';
if (($_SESSION['user_type'] ?? '') !== 'admin') {
    redirect('../index.php');
}
$studentModel = new StudentManager();

if (isset($_GET['error'])) {
    switch($_GET['error']) {
        case 'no_student_found':
            $error = 'No student found with the provided ID. Please check the ID and try again.';
            break;
        case 'no_student':
            $error = 'No student selected.';
            break;
    }
}

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
        // Ensure we pass the internal numeric ID and map the visible student ID field
        $studentInternalId = isset($_POST['student_id']) ? (int) $_POST['student_id'] : 0;
        $postData = $_POST;
        // The edit form uses 'student_id_number' for the visible/student identifier to avoid name collision
        if (isset($postData['student_id_number'])) {
            // Map to DB column 'student_id'
            $postData['student_id'] = $postData['student_id_number'];
            unset($postData['student_id_number']);
        }

        $result = $studentModel->updateStudent($studentInternalId, $postData);
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

    if (isset($_POST['assign_student_id'])) {
    $email = $_POST['email'];
    $studentId = $_POST['student_id'];
    
    if (!empty($email) && !empty($studentId)) {
        $success = $studentModel->assignStudentID($email, $studentId);
        
        if ($success) {
            $message = "Student ID assigned successfully!";
        } else {
            $error = "Error assigning Student ID. The ID might already be in use.";
        }
    } else {
        $error = "Email and Student ID are required.";
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
    'profile_completed' => $_GET['profile_completed'] ?? $_POST['profile_completed'] ?? '',
    'account_status' => $_GET['account_status'] ?? $_POST['account_status'] ?? ''
];

// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 10; // Number of records per page
$offset = ($page - 1) * $perPage;

// Get students based on filters or search
$hasSearch = isset($_GET['search_keyword']) && !empty($_GET['search_keyword']);
$hasFilters = isset($_GET['course']) && $_GET['course'] !== '' || 
              isset($_GET['year_level']) && $_GET['year_level'] !== '' || 
              isset($_GET['profile_completed']) && $_GET['profile_completed'] !== '' ||
              isset($_GET['account_status']) && $_GET['account_status'] !== '';

if ($hasSearch) {
    // For search, we need to get all results first to count them, then apply pagination
    $allStudents = $studentModel->searchStudents($_GET['search_keyword']);
    $totalRows = count($allStudents);
    $students = array_slice($allStudents, $offset, $perPage);
} else if ($hasFilters) {
    // Separate account_status from other filters
    $otherFilters = [
        'course' => $filters['course'],
        'year_level' => $filters['year_level'],
        'profile_completed' => $filters['profile_completed']
    ];
    
    // Check if only account_status is filtered
    $onlyAccountStatus = empty($otherFilters['course']) && 
                         empty($otherFilters['year_level']) && 
                         empty($otherFilters['profile_completed']) &&
                         !empty($filters['account_status']);
    
    if ($onlyAccountStatus) {
        // If ONLY account_status is filtered, get all students first
        $allStudents = $studentModel->getAllStudents();
        $totalRows = count($allStudents);
        $students = array_slice($allStudents, $offset, $perPage);
    } else {
        // Otherwise, apply other filters first
        $allStudents = $studentModel->filterStudents($otherFilters);
        $totalRows = count($allStudents);
        $students = array_slice($allStudents, $offset, $perPage);
    }
} else {
    // Get all students with pagination
    $allStudents = $studentModel->getAllStudents();
    $totalRows = count($allStudents);
    $students = array_slice($allStudents, $offset, $perPage);
}

// Calculate total pages
$totalPages = ceil($totalRows / $perPage);

// Check account status for each student
foreach ($students as &$student) {
    $accountInfo = $studentModel->checkStudentHasAccount($student['email']);
    
    // If accountInfo is false, student is unregistered
    // If accountInfo is an array, check the has_account key
    if ($accountInfo === false) {
        $student['has_account'] = false;
        $student['user_data'] = null;
        $student['role'] = null;
        $student['is_verified'] = false;
    } else {
        $student['has_account'] = $accountInfo['has_account'] ?? false;
        $student['user_data'] = $accountInfo;
        $student['role'] = $accountInfo['user_data']['role'] ?? null;
        $student['is_verified'] = $accountInfo['user_data']['is_verified'] ?? false;
    }
}
unset($student);

// Apply account status filter if set
if (!empty($filters['account_status'])) {
    if ($filters['account_status'] === 'unregistered') {
        $students = array_filter($students, function($s) {
            return !$s['has_account'];
        });
    } elseif ($filters['account_status'] === 'registered') {
        $students = array_filter($students, function($s) {
            return $s['has_account'];
        });
    }
    // Re-index array after filtering
    $students = array_values($students);
}

require_once 'admin_header.php';

// Get student counts for dashboard
$totalStudents = $studentModel->countStudentsByFilters(['deleted_at' => 'active']);
$completedProfiles = $studentModel->countStudentsByFilters(['profile_completed' => 1, 'deleted_at' => 'active']);
$incompleteProfiles = $studentModel->countStudentsByFilters(['profile_completed' => 0, 'deleted_at' => 'active']);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin - Student Management</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    .pagination {
        display: flex;
        align-items: center;
        gap: 5px;
        margin-right: 15px;
    }

    .pagination-btn {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 6px 10px;
        background: #f8f9fa;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        color: #007bff;
        text-decoration: none;
        font-size: 0.8rem;
        transition: all 0.3s ease;
        min-width: 32px;
    }

    .pagination-btn:hover:not(.disabled) {
        background: #007bff;
        color: white;
        border-color: #007bff;
    }

    .pagination-btn.disabled {
        background: #f8f9fa;
        color: #6c757d;
        border-color: #dee2e6;
        cursor: not-allowed;
        opacity: 0.6;
    }

    .pagination-info {
        padding: 6px 12px;
        font-size: 0.8rem;
        color: #495057;
        font-weight: 500;
    }

    .results-per-page {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-right: 15px;
    }

    .results-per-page select {
        padding: 4px 8px;
        border: 1px solid #dee2e6;
        border-radius: 4px;
        font-size: 0.8rem;
    }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-users"></i> Student Management</h2>
            <p>Manage student records, profiles, and accounts</p>
        </div>

        <!-- Display Messages -->
        <?php if (isset($message)): ?>
        <div class="alert-banner alert-success">
            <i class="fas fa-check-circle"></i>
            <div><?= $message ?></div>
        </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
        <div class="alert-banner alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <div><?= $error ?></div>
        </div>
        <?php endif; ?>

        <!-- Display Import Errors -->
        <?php if (isset($import_errors) && !empty($import_errors)): ?>
        <div class="alert-banner alert-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Import completed with <?= count($import_errors) ?> errors:</strong>
                <ul style="margin: 10px 0 0 20px;">
                    <?php foreach ($import_errors as $import_error): ?>
                    <li><?= htmlspecialchars($import_error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        <?php endif; ?>

        <!-- Statistics Dashboard -->
        <div class="stats-dashboard">
            <div class="stat-card">
                <div class="stat-number"><?= $totalStudents ?></div>
                <div class="stat-label">Total Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $completedProfiles ?></div>
                <div class="stat-label">Completed Profiles</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $incompleteProfiles ?></div>
                <div class="stat-label">Incomplete Profiles</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= count($students) ?></div>
                <div class="stat-label">Currently Displayed</div>
            </div>
        </div>

        <!-- Quick Actions Section -->
        <div class="action-section">
            <h3><i class="fas fa-plus-circle"></i> Quick Actions</h3>
            <p>Add new students or import multiple records via CSV</p>
            
            <div class="form-row">
                <!-- Add Student Form -->
                <form method="POST" class="form-group" style="flex: 2;">
                    <div class="form-row">
                        <input type="text" name="first_name" class="form-input" placeholder="First Name" required>
                        <input type="text" name="last_name" class="form-input" placeholder="Last Name" required>
                        <input type="email" name="email" class="form-input" placeholder="Email Address" required>
                        <button type="submit" name="add_student" class="btn btn-primary" style="height: 46px;">
                            <i class="fas fa-user-plus"></i> Add Student
                        </button>
                    </div>
                </form>

                <!-- CSV Import Form -->
                <form method="POST" enctype="multipart/form-data" class="form-group" style="flex: 1;">
                    <div class="form-row">
                        <input type="file" name="csv_file" class="form-input" accept=".csv" required 
                               style="border: 2px dashed var(--school-gray); padding: 10px;">
                        <button type="submit" name="import_students" class="btn btn-secondary" style="height: 46px;">
                            <i class="fas fa-file-import"></i> Import CSV
                        </button>
                    </div>
                </form>
            </div>
            <p style="font-size: 0.85rem; color: #666; margin-top: 10px;">
                <i class="fas fa-info-circle"></i> CSV format: email, student_id, first_name, last_name
            </p>
        </div>

        <!-- Search and Filter Section -->
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fas fa-search"></i> Search & Filter Students</span>
            </div>
            <div class="admin-card-body">
                <!-- Search Form -->
                <form method="GET" class="search-form">
                    <input type="text" name="search_keyword" class="search-input" 
                           placeholder="Search by name, email, or student ID..."
                           value="<?= htmlspecialchars($_GET['search_keyword'] ?? '') ?>">
                    <button type="submit" name="search" class="btn btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                    <a href="admin_students.php" class="btn btn-outline">
                        <i class="fas fa-times"></i> Clear
                    </a>
                </form>

                <!-- Filter Form -->
                <form method="GET" class="filter-form">
                    <div class="form-group">
                        <label>Course</label>
                        <select name="course" class="form-select">
                            <option value="">All Courses</option>
                            <option value="BS Computer Science" <?= ($filters['course'] === 'BS Computer Science') ? 'selected' : '' ?>>BS Computer Science</option>
                            <option value="BS Engineering" <?= ($filters['course'] === 'BS Engineering') ? 'selected' : '' ?>>BS Engineering</option>
                            <option value="BS Information System" <?= ($filters['course'] === 'BS Information System') ? 'selected' : '' ?>>BS Information System</option>
                            <option value="BS Nursing" <?= ($filters['course'] === 'BS Nursing') ? 'selected' : '' ?>>BS Nursing</option>
                            <option value="BS Midwifery" <?= ($filters['course'] === 'BS Midwifery') ? 'selected' : '' ?>>BS Midwifery</option>
                            <option value="BS Psychology" <?= ($filters['course'] === 'BS Psychology') ? 'selected' : '' ?>>BS Psychology</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Year Level</label>
                        <select name="year_level" class="form-select">
                            <option value="">All Year Levels</option>
                            <option value="1st Year" <?= ($filters['year_level'] === '1st Year') ? 'selected' : '' ?>>1st Year</option>
                            <option value="2nd Year" <?= ($filters['year_level'] === '2nd Year') ? 'selected' : '' ?>>2nd Year</option>
                            <option value="3rd Year" <?= ($filters['year_level'] === '3rd Year') ? 'selected' : '' ?>>3rd Year</option>
                            <option value="4th Year" <?= ($filters['year_level'] === '4th Year') ? 'selected' : '' ?>>4th Year</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Account Status</label>
                        <select name="account_status" class="form-select">
                            <option value="">All Students</option>
                            <option value="registered" <?= ($filters['account_status'] === 'registered') ? 'selected' : '' ?>>Registered</option>
                            <option value="unregistered" <?= ($filters['account_status'] === 'unregistered') ? 'selected' : '' ?>>Unregistered</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" class="btn btn-primary" style="height: 46px;">
                            <i class="fas fa-filter"></i> Apply Filters
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Students List -->
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fas fa-list"></i> Students List</span>
                <span class="badge"><?= count($students) ?> students</span>
            </div>
            <div class="admin-card-body">
                <!-- Bulk Actions -->
                <div class="bulk-section">
                    <div class="bulk-actions">
                        <button type="submit" form="bulkForm" name="bulk_action" value="export" class="btn btn-export">
                            <i class="fas fa-download"></i> Export Selected
                        </button>
                        <button type="submit" form="bulkForm" name="bulk_action" value="delete" class="btn btn-danger"
                                onclick="return confirm('Are you sure you want to delete selected students?')">
                            <i class="fas fa-trash"></i> Delete Selected
                        </button>
                        <span id="selectedCount" class="text-muted">0 students selected</span>
                    </div>
                </div>

                <!-- Students Table -->
                <form method="POST" id="bulkForm">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th class="select-all-cell">
                                        <input type="checkbox" id="selectAll" class="form-check-input">
                                    </th>
                                    <th>ID</th>
                                    <th>Student Information</th>
                                    <th>Academic Details</th>
                                    <th>Account Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="7">
                                        <div class="empty-state">
                                            <i class="fas fa-users-slash"></i>
                                            <h4>No Students Found</h4>
                                            <p>No students match your current search criteria</p>
                                        </div>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($students as $student): ?>
                                <tr>
                                    <td class="select-all-cell">
                                        <input type="checkbox" name="student_ids[]" value="<?= $student['id'] ?>" 
                                               class="form-check-input student-checkbox">
                                    </td>
                                    <td><?= $student['id'] ?></td>
                                    <td>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?></div>
                                        <div style="font-size: 0.85rem; color: #666;"><?= htmlspecialchars($student['email']) ?></div>
                                        <div style="font-size: 0.85rem;">
                                            <?php if (empty($student['student_id'])): ?>
                                            <form method="POST" class="inline-form" style="margin-top: 5px;">
    <input type="hidden" name="email" value="<?= $student['email'] ?>">
    <div style="display: flex; gap: 5px;">
        <input type="text" name="student_id" class="form-input" 
               placeholder="Enter ID" required style="padding: 4px 8px; font-size: 0.8rem;">
        <button type="submit" name="assign_student_id" class="btn btn-small btn-primary">
            <i class="fas fa-id-card"></i>
        </button>
    </div>
</form>
                                            <?php else: ?>
                                            <span style="color: var(--school-green); font-weight: 500;">
                                                <i class="fas fa-id-card"></i> <?= htmlspecialchars($student['student_id']) ?>
                                            </span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($student['course'] ?? 'Not set') ?></div>
                                        <div style="font-size: 0.85rem; color: #666;"><?= htmlspecialchars($student['year_level'] ?? 'Not set') ?></div>
                                    </td>
                                    <td>
                                        <?php if ($student['has_account']): ?>
                                        <span class="status-badge status-registered">Registered</span>
                                        <div style="font-size: 0.75rem; margin-top: 2px;">
                                            <?= $student['is_verified'] ? 'Verified' : 'Pending' ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="status-badge status-unregistered">No Account</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="student_details.php?id=<?= $student['id'] ?>" 
                                               class="btn-admin btn-view" title="View Details">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <button type="button" onclick="openEditModal(<?= $student['id'] ?>)" 
                                                    class="btn-admin btn-edit" title="Edit Student">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <form method="POST" style="display: inline;">
                                                <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                                <button type="submit" name="delete_student" 
                                                        class="btn-admin btn-danger" title="Delete Student"
                                                        onclick="return confirm('Are you sure you want to delete this student?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </form>

                <!-- ========== RESULTS SUMMARY & PAGINATION ========== -->
                <div class="bulk-section">
                    <div class="bulk-actions">
                        <span style="font-weight: 600;">
                            <i class="fas fa-database"></i> 
                            Showing <?= min($perPage, count($students)) ?> of <?= $totalRows ?> students
                            (Page <?= $page ?> of <?= $totalPages ?>)
                        </span>
                        <div style="display: flex; gap: 10px; align-items: center;">
                            <!-- Pagination Controls -->
                            <div class="pagination">
                                <?php if ($page > 1): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => 1])) ?>" class="pagination-btn" title="First Page">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="pagination-btn" title="Previous Page">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="pagination-btn disabled">
                                        <i class="fas fa-angle-double-left"></i>
                                    </span>
                                    <span class="pagination-btn disabled">
                                        <i class="fas fa-angle-left"></i>
                                    </span>
                                <?php endif; ?>

                                <span class="pagination-info">
                                    Page <?= $page ?> of <?= $totalPages ?>
                                </span>

                                <?php if ($page < $totalPages): ?>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="pagination-btn" title="Next Page">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $totalPages])) ?>" class="pagination-btn" title="Last Page">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="pagination-btn disabled">
                                        <i class="fas fa-angle-right"></i>
                                    </span>
                                    <span class="pagination-btn disabled">
                                        <i class="fas fa-angle-double-right"></i>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Back to Top Button -->
        <button class="back-to-top" onclick="scrollToTop()">
            <i class="fas fa-chevron-up"></i>
        </button>
    </div>

    <!-- Edit Student Modal -->
    <div id="editModal" class="modal-overlay">
        <div class="modal-content">
            <div class="modal-header">
                <h3><i class="fas fa-edit"></i> Edit Student</h3>
                <button type="button" class="close-modal" onclick="closeEditModal()">&times;</button>
            </div>
            <form method="POST" id="editForm">
                <input type="hidden" name="student_id" id="edit_student_id">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name</label>
                        <input type="text" name="first_name" id="edit_first_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name</label>
                        <input type="text" name="last_name" id="edit_last_name" class="form-input" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" id="edit_email" class="form-input" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Student ID</label>
                        <input type="text" name="student_id_number" id="edit_student_id_field" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Course</label>
                        <input type="text" name="course" id="edit_course" class="form-input">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Year Level</label>
                        <input type="text" name="year_level" id="edit_year_level" class="form-input">
                    </div>
                    <div class="form-group">
                        <label>Contact Number</label>
                        <input type="text" name="contact_number" id="edit_contact_number" class="form-input">
                    </div>
                </div>
                <div class="form-actions">
                    <button type="submit" name="update_student" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Student
                    </button>
                    <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
    // Select All functionality
    document.getElementById('selectAll').addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.student-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateSelectedCount();
    });

    // Update selected count
    function updateSelectedCount() {
        const selected = document.querySelectorAll('.student-checkbox:checked').length;
        document.getElementById('selectedCount').textContent = `${selected} students selected`;
    }

    // Add event listeners to all checkboxes
    document.querySelectorAll('.student-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateSelectedCount);
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
                document.getElementById('editModal').style.display = 'flex';
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

    // Back to top functionality
    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // Show/hide back to top button
    window.addEventListener('scroll', function() {
        const backToTop = document.querySelector('.back-to-top');
        if (window.pageYOffset > 300) {
            backToTop.style.display = 'flex';
        } else {
            backToTop.style.display = 'none';
        }
    });

    // Initialize selected count
    updateSelectedCount();
    
    </script>
</body>

</html>