<?php
/* ----------  BOOTSTRAP  ---------- */
session_start();
require_once 'classes/UserManager.php';

if (($_SESSION['user_type'] ?? '') !== 'admin') {
    redirect('../index.php');
}
require_once 'admin_header.php';

$adminModel = new UserManager();
$message = '';
$error   = '';

/* =================  HANDLE POST ACTIONS  ================= */
// 1. ADD USER
if (isset($_POST['add_user'])) {
    $ok = $adminModel->addUser($_POST);
    if ($ok) {
        $message = 'User added successfully!';
    } else {
        $error = 'Failed to add user.';
    }
}

// 2. UPDATE USER
if (isset($_POST['update_user'])) {
    $ok = $adminModel->updateUser((int)$_POST['user_id'], $_POST);
    if ($ok) {
        $message = 'User updated successfully!';
    } else {
        $error = 'Update failed.';
    }
}

// 3. DELETE USER
if (isset($_POST['delete_user'])) {
    $ok = $adminModel->deleteUser((int)$_POST['user_id']);
    if ($ok) {
        $message = 'User deleted successfully!';
    } else {
        $error = 'Delete failed.';
    }
}

// 4. RESET PASSWORD
if (isset($_POST['reset_password'])) {
    $user_id = (int)$_POST['user_id'];
    $reset_mode = $_POST['reset_mode'] ?? 'auto';
    $custom_password = $_POST['custom_password'] ?? '';
    
    // If custom mode is selected but no password provided
    if ($reset_mode === 'custom' && empty($custom_password)) {
        $error = 'Please enter a custom password.';
    } else {
        // Only pass custom password if custom mode is selected and password is provided
        $password_to_use = ($reset_mode === 'custom' && !empty($custom_password)) ? $custom_password : '';
        
        $ok = $adminModel->resetUserPassword($user_id, $password_to_use);
        if ($ok) {
            $message = 'Password reset successfully!';
        } else {
            $error = 'Password reset failed.';
        }
    }
}

// 5. BULK ACTIONS
if (isset($_POST['bulk_action']) && isset($_POST['user_ids'])) {
    $result = $adminModel->bulkUserAction($_POST['bulk_action'], $_POST['user_ids'], $_POST);
    if ($result['success']) {
        $message = $result['message'];
    } else {
        $error = $result['message'];
    }
}

/* =================  READ DATA WITH PAGINATION  ================= */
// Pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = isset($_GET['per_page']) ? max(5, min(100, intval($_GET['per_page']))) : 50;

$filters = [
    'name'      => $_GET['name']      ?? '',
    'email'     => $_GET['email']     ?? '',
    'role'      => $_GET['role']      ?? '',
    'status'    => $_GET['status']    ?? '',
    'verified'  => $_GET['verified']  ?? '',
];

// Get paginated users and total count
$users = $adminModel->getUsers($filters, $page, $perPage);
$totalUsers = $adminModel->countUsers($filters);
$totalPages = ceil($totalUsers / $perPage);

$roles = ['admin','student','staff'];

// Get user counts for statistics (without filters for stats)
$totalUsersAll = $adminModel->countUsers([]);
$adminUsers = $adminModel->countUsers(['role' => 'admin']);
$studentUsers = $adminModel->countUsers(['role' => 'student']);
$verifiedUsers = $adminModel->countUsers(['verified' => 1]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>User Management - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Pagination Styles */
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
    <!-- Back to Top Button -->
    <button class="back-to-top" onclick="scrollToTop()">
        <i class="fas fa-chevron-up"></i>
    </button>

    <div class="admin-container">
        <!-- ========== PAGE HEADER ========== -->
        <div class="page-header">
            <h2><i class="fas fa-users-cog"></i> User Management</h2>
            <p>Manage system users, roles, and permissions</p>
        </div>

        <!-- ========== MESSAGES ========== -->
        <?php if ($message): ?>
        <div class="alert-banner alert-success">
            <i class="fas fa-check-circle"></i>
            <div><?= htmlspecialchars($message) ?></div>
        </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
        <div class="alert-banner alert-error">
            <i class="fas fa-exclamation-circle"></i>
            <div><?= htmlspecialchars($error) ?></div>
        </div>
        <?php endif; ?>

        <!-- ========== STATISTICS DASHBOARD ========== -->
        <div class="stats-dashboard">
            <div class="stat-card">
                <div class="stat-number"><?= $totalUsersAll ?></div>
                <div class="stat-label">Total Users</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $adminUsers ?></div>
                <div class="stat-label">Administrators</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $studentUsers ?></div>
                <div class="stat-label">Students</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $verifiedUsers ?></div>
                <div class="stat-label">Verified Users</div>
            </div>
        </div>

        <!-- ========== QUICK ACTIONS ========== -->
        <div class="action-section">
            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            <p>Add new users or perform bulk operations</p>
            <div class="bulk-actions">
                <a href="#addUserPopup" class="btn btn-primary">
                    <i class="fas fa-user-plus"></i> Add New User
                </a>
                <a href="#filterPopup" class="btn btn-outline">
                    <i class="fas fa-filter"></i> Filter Users
                </a>
                <a href="#bulkActionPopup" class="btn btn-outline">
                    <i class="fas fa-tasks"></i> Bulk Actions
                </a>
            </div>
        </div>

        <!-- ========== CURRENT FILTERS DISPLAY ========== -->
        <?php if (array_filter($filters)): ?>
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fas fa-filter"></i> Active Filters</span>
            </div>
            <div class="admin-card-body">
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php foreach ($filters as $key => $value): ?>
                        <?php if (!empty($value)): ?>
                            <span class="status-badge status-pending">
                                <?= htmlspecialchars($key) ?>: <?= htmlspecialchars($value) ?>
                                <a href="?" style="color: inherit; margin-left: 5px;">×</a>
                            </span>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <a href="?" class="btn btn-small btn-outline">Clear All Filters</a>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ========== PAGINATION CONTROLS ========== -->
        <div class="bulk-section">
            <div class="bulk-actions">
                <span style="font-weight: 600;">
                    <i class="fas fa-database"></i> 
                    Showing <?= count($users) ?> of <?= $totalUsers ?> users
                    (Page <?= $page ?> of <?= $totalPages ?>)
                </span>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <!-- Results per page selector -->
                    <div class="results-per-page">
                        <span style="font-size: 0.8rem; color: #666;">Show:</span>
                        <select onchange="changePerPage(this.value)">
                            <option value="5" <?= $perPage == 5 ? 'selected' : '' ?>>5</option>
                            <option value="10" <?= $perPage == 10 ? 'selected' : '' ?>>10</option>
                            <option value="50" <?= $perPage == 50 ? 'selected' : '' ?>>50</option>
                        </select>
                    </div>

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

        <!-- ========== MAIN USER TABLE ========== -->
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fas fa-list"></i> Users List</span>
                <span class="badge"><?= count($users) ?> users</span>
            </div>
            <div class="admin-card-body">
                <?php if (empty($users)): ?>
                    <div class="empty-state">
                        <i class="fas fa-users-slash"></i>
                        <h4>No Users Found</h4>
                        <p>No users match your current filter criteria</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th class="select-all-cell">
                                        <input type="checkbox" id="selAll" class="form-check-input">
                                    </th>
                                    <th>ID</th>
                                    <th>User Information</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Verification</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $u): ?>
                                <tr>
                                    <td class="select-all-cell">
                                        <input type="checkbox" class="rowChk form-check-input" value="<?= $u['user_id'] ?>">
                                    </td>
                                    <td><?= $u['user_id'] ?></td>
                                    <td>
                                        <div style="font-weight: 600;"><?= htmlspecialchars($u['full_name']) ?></div>
                                        <div style="font-size: 0.85rem; color: #666;"><?= htmlspecialchars($u['email']) ?></div>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $u['role'] === 'admin' ? 'status-generated' : 'status-registered' ?>">
                                            <?= htmlspecialchars($u['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $u['status'] === 'approved' ? 'status-active' : 'status-pending' ?>">
                                            <?= htmlspecialchars($u['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="status-badge <?= $u['is_verified'] ? 'status-verified' : 'status-unverified' ?>">
                                            <?= $u['is_verified'] ? 'Verified' : 'Pending' ?>
                                        </span>
                                    </td>
                                    <td><?= date('M d, Y', strtotime($u['created_at'])) ?></td>
                                    <td>
                                        <div class="action-buttons">
                                            <!-- EDIT BUTTON -->
                                            <a href="#editUserPopup<?= $u['user_id'] ?>" 
                                               class="btn-admin btn-edit" title="Edit User">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            
                                            <!-- RESET PASSWORD BUTTON -->
                                            <a href="#resetPopup<?= $u['user_id'] ?>" 
                                               class="btn-admin btn-secondary" title="Reset Password">
                                                <i class="fas fa-key"></i>
                                            </a>
                                            
                                            <!-- DELETE BUTTON -->
                                            <form method="post" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                                                <input type="hidden" name="delete_user">
                                                <button type="submit" 
                                                        class="btn-admin btn-danger" 
                                                        title="Delete User"
                                                        onclick="return confirm('Are you sure you want to delete this user?')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- ========== BOTTOM PAGINATION ========== -->
                    <?php if ($totalPages > 1): ?>
                    <div style="margin-top: 20px; display: flex; justify-content: center;">
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
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ========== ADD USER POPUP ========== -->
    <div id="addUserPopup" class="popup-overlay">
        <div class="popup-content">
            <button class="popup-close">&times;</button>
            <h2><i class="fas fa-user-plus"></i> Add New User</h2>
            <form method="post">
                <div class="form-row">
                    <div class="form-group">
                        <label>First Name:</label>
                        <input type="text" name="first_name" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label>Last Name:</label>
                        <input type="text" name="last_name" class="form-input" required>
                    </div>
                </div>
                <div class="form-group">
                    <label>Role:</label>
                    <select name="role" class="form-select" required>
                        <option value="">-- Select Role --</option>
                        <option value="student">Student</option>
                        <option value="admin">Admin</option>
                        <option value="staff">Staff</option>
                    </select>
                </div>
                <div class="form-actions">
                    <input type="hidden" name="add_user">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create User
                    </button>
                    <a href="#" class="btn btn-outline">Cancel</a>
                </div>
            </form>
            <p style="margin-top: 15px; font-size: 0.85rem; color: #666;">
                <i class="fas fa-info-circle"></i> Email will be generated automatically (first-letter(s) + last name @kld.edu.ph)
            </p>
        </div>
    </div>

    <!-- ========== FILTER POPUP ========== -->
    <div id="filterPopup" class="popup-overlay">
        <div class="popup-content">
            <button class="popup-close">&times;</button>
            <h2><i class="fas fa-filter"></i> Search & Filter Users</h2>
            <form method="get">
                <div class="form-row">
                    <div class="form-group">
                        <label>Name:</label>
                        <input type="text" name="name" class="form-input" 
                               value="<?= htmlspecialchars($filters['name']) ?>" 
                               placeholder="Search by name">
                    </div>
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" class="form-input"
                               value="<?= htmlspecialchars($filters['email']) ?>" 
                               placeholder="Search by email">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Role:</label>
                        <select name="role" class="form-select">
                            <option value="">-- Any Role --</option>
                            <?php foreach ($roles as $r): ?>
                            <option value="<?= htmlspecialchars($r) ?>" <?= $filters['role']===$r ? 'selected':'' ?>>
                                <?= htmlspecialchars(ucfirst($r)) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status:</label>
                        <select name="status" class="form-select">
                            <option value="">-- Any Status --</option>
                            <option value="pending" <?= $filters['status']==='pending' ?'selected':'' ?>>Pending</option>
                            <option value="approved" <?= $filters['status']==='approved'?'selected':'' ?>>Approved</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label>Verified:</label>
                    <select name="verified" class="form-select">
                        <option value="">-- Any Verification --</option>
                        <option value="1" <?= $filters['verified']==='1' ? 'selected':'' ?>>Verified</option>
                        <option value="0" <?= $filters['verified']==='0' ? 'selected':'' ?>>Not Verified</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> Apply Filters
                    </button>
                    <a href="?" class="btn btn-outline">Clear All</a>
                    <a href="#" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- ========== BULK ACTIONS POPUP ========== -->
    <div id="bulkActionPopup" class="popup-overlay">
        <div class="popup-content">
            <button class="popup-close">&times;</button>
            <h2><i class="fas fa-tasks"></i> Bulk Actions</h2>
            <form method="post" id="bulkForm">
                <div class="form-group">
                    <label>Action:</label>
                    <select name="bulk_action" class="form-select" required onchange="toggleBulkOptions(this.value)">
                        <option value="">-- Choose Action --</option>
                        <option value="delete">Delete Selected Users</option>
                        <option value="change_role">Change Role</option>
                        <option value="change_status">Change Status</option>
                        <option value="export">Export Selected</option>
                    </select>
                </div>
                <div class="form-group" id="bulkRole" style="display:none">
                    <label>Change Role To:</label>
                    <select name="bulk_role" class="form-select">
                        <?php foreach ($roles as $r): ?>
                            <option value="<?= $r ?>"><?= ucfirst($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group" id="bulkStatus" style="display:none">
                    <label>Change Status To:</label>
                    <select name="bulk_status" class="form-select">
                        <option value="pending">Pending</option>
                        <option value="approved">Approved</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary" onclick="return confirmBulkAction()">
                        <i class="fas fa-play"></i> Apply to Selected
                    </button>
                    <a href="#" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- ========== INDIVIDUAL USER POPUPS ========== -->
    <?php foreach ($users as $u): ?>
    <!-- EDIT USER POPUP -->
    <div id="editUserPopup<?= $u['user_id'] ?>" class="popup-overlay">
        <div class="popup-content">
            <button class="popup-close">&times;</button>
            <h2><i class="fas fa-edit"></i> Edit User #<?= $u['user_id'] ?></h2>
            <form method="post">
                <div class="form-group">
                    <label>Full Name:</label>
                    <input name="full_name" class="form-input" value="<?= htmlspecialchars($u['full_name']) ?>" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input name="email" type="email" class="form-input" value="<?= htmlspecialchars($u['email']) ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Role:</label>
                        <select name="role" class="form-select" required>
                            <?php foreach ($roles as $r): ?>
                            <option value="<?= htmlspecialchars($r) ?>" <?= $u['role']===$r?'selected':'' ?>>
                                <?= htmlspecialchars(ucfirst($r)) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Status:</label>
                        <select name="status" class="form-select" required>
                            <option value="pending" <?= $u['status']==='pending' ?'selected':'' ?>>Pending</option>
                            <option value="approved" <?= $u['status']==='approved'?'selected':'' ?>>Approved</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-checkbox">
                        <input type="checkbox" name="is_verified" value="1" <?= $u['is_verified']?'checked':'' ?>>
                        Mark as Verified
                    </label>
                </div>
                <div class="form-actions">
                    <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                    <input type="hidden" name="update_user">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Changes
                    </button>
                    <a href="#" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- RESET PASSWORD POPUP -->
    <div id="resetPopup<?= $u['user_id'] ?>" class="popup-overlay">
    <div class="popup-content">
        <button class="popup-close">&times;</button>
        <h2><i class="fas fa-key"></i> Reset Password</h2>
        <p>Reset password for <?= htmlspecialchars($u['full_name']) ?></p>
        <form method="post">
            <div class="form-group">
                <label class="form-radio">
                    <input type="radio" name="reset_mode" value="auto" checked> 
                    Auto-generate secure password
                </label>
                <label class="form-radio">
                    <input type="radio" name="reset_mode" value="custom">
                    Set custom password:
                </label>
                <input name="custom_password" class="form-input" placeholder="Enter new password" 
                       style="margin-top: 5px;" id="customPassword<?= $u['user_id'] ?>" disabled>
            </div>
            <div class="form-actions">
                <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                <input type="hidden" name="reset_password">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sync"></i> Reset Password
                </button>
                <a href="#" class="btn btn-outline">Cancel</a>
            </div>
        </form>
    </div>
</div>
    <?php endforeach; ?>

    <script>
    // Select All functionality
    document.getElementById('selAll').onclick = function() {
        document.querySelectorAll('.rowChk').forEach(ch => ch.checked = this.checked);
    };

    // Bulk action options toggle
    function toggleBulkOptions(action) {
        document.getElementById('bulkRole').style.display = (action === 'change_role') ? 'block' : 'none';
        document.getElementById('bulkStatus').style.display = (action === 'change_status') ? 'block' : 'none';
    }

    // Confirm bulk action
    function confirmBulkAction() {
        const checked = document.querySelectorAll('.rowChk:checked');
        if (!checked.length) {
            alert('Please select at least one user');
            return false;
        }
        return confirm(`Apply this action to ${checked.length} selected user(s)?`);
    }

    // Before bulk submit, copy checked IDs
    document.getElementById('bulkForm').onsubmit = function(e) {
        const checked = document.querySelectorAll('.rowChk:checked');
        if (!checked.length) return false;
        
        checked.forEach(ch => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'user_ids[]';
            inp.value = ch.value;
            e.target.appendChild(inp);
        });
        return true;
    };

    // Toggle custom password field
    document.querySelectorAll('input[name="reset_mode"]').forEach(radio => {
        radio.addEventListener('change', function() {
            const customField = this.closest('form').querySelector('input[name="custom_password"]');
            customField.disabled = this.value !== 'custom';
            if (this.value === 'custom') {
                customField.focus();
            }
        });
    });

    // Change results per page
    function changePerPage(perPage) {
        const params = new URLSearchParams(window.location.search);
        params.set('per_page', perPage);
        params.delete('page'); // Go back to first page
        window.location.href = '?' + params.toString();
    }

    // Back to top functionality
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

    // Close popups when clicking outside
    document.querySelectorAll('.popup-overlay').forEach(overlay => {
        overlay.addEventListener('click', function(e) {
            if (e.target === this) {
                window.location.hash = '';
            }
        });
    });
    </script>
</body>
</html>