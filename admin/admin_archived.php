<?php
// admin_archived.php - Combined Archive for Students and Users
session_start();
require_once 'classes/StudentManager.php';
require_once 'classes/UserManager.php';

if (($_SESSION['user_type'] ?? '') !== 'admin') {
    redirect('../index.php');
}

$studentModel = new StudentManager();
$userModel = new UserManager();

// Determine active tab
$activeTab = $_GET['tab'] ?? 'students';
if (!in_array($activeTab, ['students', 'users'])) {
    $activeTab = 'students';
}

// Handle form actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($activeTab === 'students') {
        // Student Actions
        if (isset($_POST['restore_student'])) {
            $result = $studentModel->restoreStudent($_POST['student_id']);
            if ($result) {
                $message = "Student restored successfully!";
            } else {
                $error = "Failed to restore student!";
            }
        }
        
        if (isset($_POST['permanent_delete_student'])) {
            $result = $studentModel->permanentlyDeleteStudent($_POST['student_id']);
            if ($result) {
                $message = "Student permanently deleted!";
            } else {
                $error = "Failed to permanently delete student!";
            }
        }
        
        if (isset($_POST['bulk_action'])) {
            $studentIds = $_POST['student_ids'] ?? [];
            
            if ($_POST['bulk_action'] === 'restore' && !empty($studentIds)) {
                $result = $studentModel->bulkRestoreStudents($studentIds);
                if ($result) {
                    $message = "Selected students restored successfully!";
                } else {
                    $error = "Failed to restore students!";
                }
            }
            
            if ($_POST['bulk_action'] === 'permanent_delete' && !empty($studentIds)) {
                $result = $studentModel->bulkPermanentlyDeleteStudents($studentIds);
                if ($result) {
                    $message = "Selected students permanently deleted!";
                } else {
                    $error = "Failed to permanently delete students!";
                }
            }
        }
    } else {
        // User Actions
        if (isset($_POST['restore_user'])) {
            $result = $userModel->restoreUser($_POST['user_id']);
            if ($result) {
                $message = "User restored successfully!";
            } else {
                $error = "Failed to restore user!";
            }
        }
        
        if (isset($_POST['permanent_delete_user'])) {
            $result = $userModel->permanentlyDeleteUser($_POST['user_id']);
            if ($result) {
                $message = "User permanently deleted!";
            } else {
                $error = "Failed to permanently delete user!";
            }
        }
        
        if (isset($_POST['bulk_action'])) {
            $userIds = $_POST['user_ids'] ?? [];
            
            if ($_POST['bulk_action'] === 'restore' && !empty($userIds)) {
                $result = $userModel->bulkRestoreUsers($userIds);
                if ($result) {
                    $message = "Selected users restored successfully!";
                } else {
                    $error = "Failed to restore users!";
                }
            }
            
            if ($_POST['bulk_action'] === 'permanent_delete' && !empty($userIds)) {
                $result = $userModel->bulkPermanentlyDeleteUsers($userIds);
                if ($result) {
                    $message = "Selected users permanently deleted!";
                } else {
                    $error = "Failed to permanently delete users!";
                }
            }
        }
    }
}

// Get archived data
$archivedStudents = $studentModel->getArchivedStudents();
$archivedUsers = $userModel->getArchivedUsers();

// Pagination for students
$studentPage = isset($_GET['student_page']) ? max(1, intval($_GET['student_page'])) : 1;
$perPage = 10;
$studentOffset = ($studentPage - 1) * $perPage;
$totalStudents = count($archivedStudents);
$students = array_slice($archivedStudents, $studentOffset, $perPage);
$totalStudentPages = ceil($totalStudents / $perPage);

// Pagination for users
$userPage = isset($_GET['user_page']) ? max(1, intval($_GET['user_page'])) : 1;
$userOffset = ($userPage - 1) * $perPage;
$totalUsers = count($archivedUsers);
$users = array_slice($archivedUsers, $userOffset, $perPage);
$totalUserPages = ceil($totalUsers / $perPage);

require_once 'admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Admin - Archived Records</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    .tabs-container {
        background: white;
        border-radius: 8px;
        padding: 0;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .tabs {
        display: flex;
        border-bottom: 2px solid #e0e0e0;
        padding: 0;
        margin: 0;
        list-style: none;
    }

    .tab-item {
        flex: 1;
    }

    .tab-link {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        padding: 16px 24px;
        text-decoration: none;
        color: #666;
        font-weight: 500;
        transition: all 0.3s;
        border-bottom: 3px solid transparent;
        cursor: pointer;
    }

    .tab-link:hover {
        background: #f8f9fa;
        color: #333;
    }

    .tab-link.active {
        color: #1b5e20;
        border-bottom-color: #1b5e20;
        background: #f1f8f4;
    }

    .tab-content {
        display: none;
    }

    .tab-content.active {
        display: block;
    }

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

    .archive-warning {
        background: #fff3cd;
        border: 1px solid #ffc107;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .archive-warning i {
        color: #856404;
        font-size: 1.5rem;
    }

    .btn-restore {
        background: #28a745;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: background 0.3s;
    }

    .btn-restore:hover {
        background: #218838;
    }

    .btn-permanent-delete {
        background: #dc3545;
        color: white;
        border: none;
        padding: 6px 12px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: background 0.3s;
    }

    .btn-permanent-delete:hover {
        background: #c82333;
    }

    .status-badge {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 0.75rem;
        font-weight: 500;
    }

    .status-active { background: #d4edda; color: #155724; }
    .status-pending { background: #fff3cd; color: #856404; }
    .status-inactive { background: #f8d7da; color: #721c24; }
    </style>
</head>

<body>
    <div class="admin-container">
        <!-- Page Header -->
        <div class="page-header">
            <h2><i class="fas fa-archive"></i> Archived Records</h2>
            <p>View and manage deleted students and users - restore or permanently delete</p>
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

        <!-- Warning Banner -->
        <div class="archive-warning">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
                <strong>Warning:</strong> Permanently deleting records will remove all their data and associated files from the system. This action cannot be undone!
            </div>
        </div>

        <!-- Tabs -->
        <div class="tabs-container">
            <ul class="tabs">
                <li class="tab-item">
                    <a href="?tab=students" class="tab-link <?= $activeTab === 'students' ? 'active' : '' ?>">
                        <i class="fas fa-users"></i>
                        Archived Students (<?= $totalStudents ?>)
                    </a>
                </li>
                <li class="tab-item">
                    <a href="?tab=users" class="tab-link <?= $activeTab === 'users' ? 'active' : '' ?>">
                        <i class="fas fa-user-cog"></i>
                        Archived Users (<?= $totalUsers ?>)
                    </a>
                </li>
            </ul>
        </div>

        <!-- Students Tab Content -->
        <div class="tab-content <?= $activeTab === 'students' ? 'active' : '' ?>">
            <div class="admin-card">
                <div class="admin-card-header">
                    <span><i class="fas fa-users"></i> Archived Students</span>
                    <span class="badge"><?= count($students) ?> students</span>
                </div>
                <div class="admin-card-body">
                    <!-- Bulk Actions -->
                    <div class="bulk-section">
                        <div class="bulk-actions">
                            <button type="submit" form="studentBulkForm" name="bulk_action" value="restore" class="btn btn-restore">
                                <i class="fas fa-undo"></i> Restore Selected
                            </button>
                            <button type="submit" form="studentBulkForm" name="bulk_action" value="permanent_delete" class="btn btn-permanent-delete"
                                    onclick="return confirm('⚠️ WARNING: This will PERMANENTLY delete the selected students and ALL their data. This action CANNOT be undone!\n\nAre you absolutely sure?')">
                                <i class="fas fa-trash-alt"></i> Permanently Delete Selected
                            </button>
                            <span id="studentSelectedCount" class="text-muted">0 students selected</span>
                        </div>
                    </div>

                    <!-- Students Table -->
                    <form method="POST" id="studentBulkForm">
                        <input type="hidden" name="tab" value="students">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th class="select-all-cell">
                                            <input type="checkbox" id="selectAllStudents" class="form-check-input">
                                        </th>
                                        <th>ID</th>
                                        <th>Student Information</th>
                                        <th>Academic Details</th>
                                        <th>Deleted At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($students)): ?>
                                    <tr>
                                        <td colspan="6">
                                            <div class="empty-state">
                                                <i class="fas fa-archive"></i>
                                                <h4>No Archived Students</h4>
                                                <p>The student archive is empty</p>
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
                                            <?php if (!empty($student['student_id'])): ?>
                                            <div style="font-size: 0.85rem; color: #666;">
                                                <i class="fas fa-id-card"></i> <?= htmlspecialchars($student['student_id']) ?>
                                            </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div><?= htmlspecialchars($student['course'] ?? 'Not set') ?></div>
                                            <div style="font-size: 0.85rem; color: #666;"><?= htmlspecialchars($student['year_level'] ?? 'Not set') ?></div>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.85rem; color: #dc3545;">
                                                <i class="fas fa-calendar-times"></i>
                                                <?= date('M d, Y', strtotime($student['deleted_at'])) ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: #666;">
                                                <?= date('h:i A', strtotime($student['deleted_at'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                                    <button type="submit" name="restore_student" 
                                                            class="btn-restore" title="Restore Student"
                                                            onclick="return confirm('Restore this student?')">
                                                        <i class="fas fa-undo"></i> Restore
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="student_id" value="<?= $student['id'] ?>">
                                                    <button type="submit" name="permanent_delete_student" 
                                                            class="btn-permanent-delete" title="Permanently Delete Student"
                                                            onclick="return confirm('⚠️ WARNING: This will PERMANENTLY delete this student and ALL their data.\n\nStudent: <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>\nEmail: <?= htmlspecialchars($student['email']) ?>\n\nThis action CANNOT be undone!\n\nAre you absolutely sure?')">
                                                        <i class="fas fa-trash-alt"></i> Delete Forever
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

                    <!-- Pagination -->
                    <?php if ($totalStudentPages > 1): ?>
                    <div class="bulk-section">
                        <div class="bulk-actions">
                            <span style="font-weight: 600;">
                                <i class="fas fa-database"></i> 
                                Showing <?= min($perPage, count($students)) ?> of <?= $totalStudents ?> students
                                (Page <?= $studentPage ?> of <?= $totalStudentPages ?>)
                            </span>
                            <div class="pagination">
                                <?php if ($studentPage > 1): ?>
                                    <a href="?tab=students&student_page=1" class="pagination-btn">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                    <a href="?tab=students&student_page=<?= $studentPage - 1 ?>" class="pagination-btn">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="pagination-btn disabled"><i class="fas fa-angle-double-left"></i></span>
                                    <span class="pagination-btn disabled"><i class="fas fa-angle-left"></i></span>
                                <?php endif; ?>

                                <span class="pagination-info">Page <?= $studentPage ?> of <?= $totalStudentPages ?></span>

                                <?php if ($studentPage < $totalStudentPages): ?>
                                    <a href="?tab=students&student_page=<?= $studentPage + 1 ?>" class="pagination-btn">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                    <a href="?tab=students&student_page=<?= $totalStudentPages ?>" class="pagination-btn">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="pagination-btn disabled"><i class="fas fa-angle-right"></i></span>
                                    <span class="pagination-btn disabled"><i class="fas fa-angle-double-right"></i></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Users Tab Content -->
        <div class="tab-content <?= $activeTab === 'users' ? 'active' : '' ?>">
            <div class="admin-card">
                <div class="admin-card-header">
                    <span><i class="fas fa-user-cog"></i> Archived Users</span>
                    <span class="badge"><?= count($users) ?> users</span>
                </div>
                <div class="admin-card-body">
                    <!-- Bulk Actions -->
                    <div class="bulk-section">
                        <div class="bulk-actions">
                            <button type="submit" form="userBulkForm" name="bulk_action" value="restore" class="btn btn-restore">
                                <i class="fas fa-undo"></i> Restore Selected
                            </button>
                            <button type="submit" form="userBulkForm" name="bulk_action" value="permanent_delete" class="btn btn-permanent-delete"
                                    onclick="return confirm('⚠️ WARNING: This will PERMANENTLY delete the selected users and ALL their data. This action CANNOT be undone!\n\nAre you absolutely sure?')">
                                <i class="fas fa-trash-alt"></i> Permanently Delete Selected
                            </button>
                            <span id="userSelectedCount" class="text-muted">0 users selected</span>
                        </div>
                    </div>

                    <!-- Users Table -->
                    <form method="POST" id="userBulkForm">
                        <input type="hidden" name="tab" value="users">
                        <div class="table-responsive">
                            <table class="admin-table">
                                <thead>
                                    <tr>
                                        <th class="select-all-cell">
                                            <input type="checkbox" id="selectAllUsers" class="form-check-input">
                                        </th>
                                        <th>ID</th>
                                        <th>Full Name</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Deleted At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($users)): ?>
                                    <tr>
                                        <td colspan="7">
                                            <div class="empty-state">
                                                <i class="fas fa-archive"></i>
                                                <h4>No Archived Users</h4>
                                                <p>The user archive is empty</p>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td class="select-all-cell">
                                            <input type="checkbox" name="user_ids[]" value="<?= $user['user_id'] ?>" 
                                                   class="form-check-input user-checkbox">
                                        </td>
                                        <td><?= $user['user_id'] ?></td>
                                        <td style="font-weight: 600;"><?= htmlspecialchars($user['full_name']) ?></td>
                                        <td style="font-size: 0.9rem; color: #666;"><?= htmlspecialchars($user['email']) ?></td>
                                        <td>
                                            <span class="status-badge status-<?= $user['role'] === 'admin' ? 'inactive' : 'active' ?>">
                                                <?= ucfirst($user['role']) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div style="font-size: 0.85rem; color: #dc3545;">
                                                <i class="fas fa-calendar-times"></i>
                                                <?= date('M d, Y', strtotime($user['deleted_at'])) ?>
                                            </div>
                                            <div style="font-size: 0.75rem; color: #666;">
                                                <?= date('h:i A', strtotime($user['deleted_at'])) ?>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="action-buttons">
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                    <button type="submit" name="restore_user" 
                                                            class="btn-restore" title="Restore User"
                                                            onclick="return confirm('Restore this user?')">
                                                        <i class="fas fa-undo"></i> Restore
                                                    </button>
                                                </form>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                                    <button type="submit" name="permanent_delete_user" 
                                                            class="btn-permanent-delete" title="Permanently Delete User"
                                                            onclick="return confirm('⚠️ WARNING: This will PERMANENTLY delete this user.\n\nUser: <?= htmlspecialchars($user['full_name']) ?>\nEmail: <?= htmlspecialchars($user['email']) ?>\n\nThis action CANNOT be undone!\n\nAre you absolutely sure?')">
                                                        <i class="fas fa-trash-alt"></i> Delete Forever
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

                    <!-- Pagination -->
                    <?php if ($totalUserPages > 1): ?>
                    <div class="bulk-section">
                        <div class="bulk-actions">
                            <span style="font-weight: 600;">
                                <i class="fas fa-database"></i> 
                                Showing <?= min($perPage, count($users)) ?> of <?= $totalUsers ?> users
                                (Page <?= $userPage ?> of <?= $totalUserPages ?>)
                            </span>
                            <div class="pagination">
                                <?php if ($userPage > 1): ?>
                                    <a href="?tab=users&user_page=1" class="pagination-btn">
                                        <i class="fas fa-angle-double-left"></i>
                                    </a>
                                    <a href="?tab=users&user_page=<?= $userPage - 1 ?>" class="pagination-btn">
                                        <i class="fas fa-angle-left"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="pagination-btn disabled"><i class="fas fa-angle-double-left"></i></span>
                                    <span class="pagination-btn disabled"><i class="fas fa-angle-left"></i></span>
                                <?php endif; ?>

                                <span class="pagination-info">Page <?= $userPage ?> of <?= $totalUserPages ?></span>

                                <?php if ($userPage < $totalUserPages): ?>
                                    <a href="?tab=users&user_page=<?= $userPage + 1 ?>" class="pagination-btn">
                                        <i class="fas fa-angle-right"></i>
                                    </a>
                                    <a href="?tab=users&user_page=<?= $totalUserPages ?>" class="pagination-btn">
                                        <i class="fas fa-angle-double-right"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="pagination-btn disabled"><i class="fas fa-angle-right"></i></span>
                                    <span class="pagination-btn disabled"><i class="fas fa-angle-double-right"></i></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Back to Top Button -->
        <button class="back-to-top" onclick="scrollToTop()">
            <i class="fas fa-chevron-up"></i>
        </button>
    </div>

    <script>
    // Student checkboxes
    document.getElementById('selectAllStudents')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.student-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateStudentSelectedCount();
    });

    function updateStudentSelectedCount() {
        const selected = document.querySelectorAll('.student-checkbox:checked').length;
        document.getElementById('studentSelectedCount').textContent = `${selected} students selected`;
    }

    document.querySelectorAll('.student-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateStudentSelectedCount);
    });

    // User checkboxes
    document.getElementById('selectAllUsers')?.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.user-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = this.checked;
        });
        updateUserSelectedCount();
    });

    function updateUserSelectedCount() {
        const selected = document.querySelectorAll('.user-checkbox:checked').length;
        document.getElementById('userSelectedCount').textContent = `${selected} users selected`;
    }

    document.querySelectorAll('.user-checkbox').forEach(checkbox => {
        checkbox.addEventListener('change', updateUserSelectedCount);
    });

    // Back to top
    function scrollToTop() {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    window.addEventListener('scroll', function() {
        const backToTop = document.querySelector('.back-to-top');
        if (window.pageYOffset > 300) {
            backToTop.style.display = 'flex';
        } else {
            backToTop.style.display = 'none';
        }
    });

    // Initialize counts
    updateStudentSelectedCount();
    updateUserSelectedCount();
    </script>
</body>

</html>
