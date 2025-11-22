<?php
/* ----------  BOOTSTRAP  ---------- */
session_start();
require_once 'classes/UserManager.php';

if (($_SESSION['user_type'] ?? '') !== 'admin') {
    redirect('../index.php');
}

$adminModel = new UserManager();
$message = '';
$error   = '';

/* =================  HANDLE POST ACTIONS  ================= */
// [KEEP ALL YOUR EXISTING PHP CODE HERE - it stays exactly the same]
// 1. ADD USER
if (isset($_POST['add_user'])) {
    $ok = $adminModel->addUser($_POST);
    if ($ok) {
        $message = 'User added.';
    } else {
        $error = 'Failed to add user.';
    }
}

// 2. UPDATE USER
if (isset($_POST['update_user'])) {
    $ok = $adminModel->updateUser((int)$_POST['user_id'], $_POST);
    if ($ok) {
        $message = 'User updated.';
    } else {
        $error = 'Update failed.';
    }
}

// [KEEP ALL OTHER PHP CODE...]

/* =================  READ DATA  ================= */
$filters = [
    'name'      => $_GET['name']      ?? '',
    'email'     => $_GET['email']     ?? '',
    'role'      => $_GET['role']      ?? '',
    'status'    => $_GET['status']    ?? '',
    'verified'  => $_GET['verified']  ?? '',
];

$users = $adminModel->getUsers($filters);
$roles = ['admin','student','staff'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Management</title>
    <link rel="stylesheet" href="../assets/css/admin_user.css">
</head>

<body>

    <!-- ========== HEADER WITH ACTION BUTTONS ========== -->
    <div class="header-actions">
        <h1 class="page-title">User Management</h1>
        <div class="action-buttons-container">
            <a href="#addUserPopup" class="header-trigger-btn">Add New User</a>
            <a href="#filterPopup" class="header-trigger-btn">Filter</a>
            <a href="#bulkActionPopup" class="header-trigger-btn">Bulk Actions</a>
        </div>
    </div>

    <?php if ($message): ?><div class="message success"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    <?php if ($error): ?><div class="message error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <!-- ========== ADD USER POPUP ========== -->
    <div id="addUserPopup" class="popup-overlay">
        <div class="popup-content">
            <a href="#" class="popup-close">&times;</a>
            <h2>Add New User</h2>
            <form method="post">
                <div class="form-group">
                    <label>First Name:</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="form-group">
                    <label>Last Name:</label>
                    <input type="text" name="last_name" required>
                </div>
                <div class="form-group">
                    <label>Role:</label>
                    <select name="role" required>
                        <option value="">-- select --</option>
                        <option value="student">Student</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                <div class="form-actions">
                    <input type="hidden" name="add_user">
                    <button type="submit" class="btn btn-primary">Create</button>
                    <a href="#" class="btn btn-outline">Cancel</a>
                </div>
            </form>
            <small>Email will be built automatically (first-letter(s) + last name @kld.edu.ph)</small>
        </div>
    </div>

    <!-- ========== FILTER POPUP ========== -->
    <div id="filterPopup" class="popup-overlay">
        <div class="popup-content">
            <a href="#" class="popup-close">&times;</a>
            <h2>Search / Filter</h2>
            <form method="get">
                <div class="form-group">
                    <label>Name:</label>
                    <input type="text" name="name" value="<?= htmlspecialchars($filters['name']) ?>" placeholder="any part of name">
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" name="email" value="<?= htmlspecialchars($filters['email']) ?>" placeholder="email">
                </div>
                <div class="form-group">
                    <label>Role:</label>
                    <select name="role">
                        <option value="">-- any --</option>
                        <?php foreach ($roles as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>" <?= $filters['role']===$r ? 'selected':'' ?>><?= htmlspecialchars($r) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label>Status:</label>
                    <select name="status">
                        <option value="">-- any --</option>
                        <option value="pending" <?= $filters['status']==='pending' ? 'selected':'' ?>>Pending</option>
                        <option value="approved" <?= $filters['status']==='approved'? 'selected':'' ?>>Approved</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Verified:</label>
                    <select name="verified">
                        <option value="">-- any --</option>
                        <option value="1" <?= $filters['verified']==='1' ? 'selected':'' ?>>Yes</option>
                        <option value="0" <?= $filters['verified']==='0' ? 'selected':'' ?>>No</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="?" class="btn btn-outline">Clear</a>
                    <a href="#" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- ========== BULK ACTIONS POPUP ========== -->
    <div id="bulkActionPopup" class="popup-overlay">
        <div class="popup-content">
            <a href="#" class="popup-close">&times;</a>
            <h2>Bulk Actions</h2>
            <form method="post" id="bulkForm" onsubmit="return confirm('Proceed with bulk action?')">
                <div class="form-group">
                    <label>Action:</label>
                    <select name="bulk_action" required onchange="toggleBulkOptions(this.value)">
                        <option value="">-- choose --</option>
                        <option value="delete">Soft-delete selected</option>
                        <option value="change_role">Change role</option>
                        <option value="change_status">Change status</option>
                        <option value="export">Export selected</option>
                    </select>
                </div>
                <div class="form-group" id="bulkRole" style="display:none">
                    <label>Change Role To:</label>
                    <select name="bulk_role">
                        <?php foreach ($roles as $r) echo "<option>$r</option>"; ?>
                    </select>
                </div>
                <div class="form-group" id="bulkStatus" style="display:none">
                    <label>Change Status To:</label>
                    <select name="bulk_status">
                        <option>pending</option>
                        <option>approved</option>
                    </select>
                </div>
                <div class="form-actions">
                    <button type="submit" class="btn btn-primary">Apply</button>
                    <a href="#" class="btn btn-outline">Cancel</a>
                </div>
            </form>
        </div>
    </div>

    <!-- ========== MAIN USER TABLE ========== -->
    <table>
        <thead>
            <tr>
                <th><input type="checkbox" id="selAll"></th>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Status</th>
                <th>Verified</th>
                <th>Created</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><input type="checkbox" class="rowChk" value="<?= $u['user_id'] ?>"></td>
                <td><?= $u['user_id'] ?></td>
                <td><?= htmlspecialchars($u['full_name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['role']) ?></td>
                <td>
                    <?php if ($u['status'] === 'approved'): ?>
                        <span class="status-badge status-active"><?= htmlspecialchars($u['status']) ?></span>
                    <?php else: ?>
                        <span class="status-badge status-pending"><?= htmlspecialchars($u['status']) ?></span>
                    <?php endif; ?>
                </td>
                <td><?= $u['is_verified'] ? 'Yes' : 'No' ?></td>
                <td><?= $u['created_at'] ?></td>
                <td class="action-buttons">
                    <!-- EDIT BUTTON - Opens Edit Popup -->
                    <a href="#editUserPopup<?= $u['user_id'] ?>" class="btn btn-secondary btn-small">Edit</a>
                    
                    <!-- RESET PASSWORD BUTTON - Opens Reset Popup -->
                    <a href="#resetPopup<?= $u['user_id'] ?>" class="btn btn-secondary btn-small">Reset Pwd</a>
                    
                    <!-- DELETE BUTTON -->
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete?')">
                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                        <input type="hidden" name="delete_user">
                        <button type="submit" class="btn btn-danger btn-small">Delete</button>
                    </form>
                </td>
            </tr>

            <!-- ========== EDIT USER POPUP for each user ========== -->
            <div id="editUserPopup<?= $u['user_id'] ?>" class="popup-overlay">
                <div class="popup-content">
                    <a href="#" class="popup-close">&times;</a>
                    <h2>Edit User #<?= $u['user_id'] ?></h2>
                    <form method="post">
                        <div class="form-group">
                            <label>Name:</label>
                            <input name="full_name" value="<?= htmlspecialchars($u['full_name']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email:</label>
                            <input name="email" value="<?= htmlspecialchars($u['email']) ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Role:</label>
                            <select name="role" required>
                                <?php foreach ($roles as $r): ?>
                                <option value="<?= htmlspecialchars($r) ?>" <?= $u['role']===$r?'selected':'' ?>><?= htmlspecialchars($r) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Status:</label>
                            <select name="status" required>
                                <option value="pending" <?= $u['status']==='pending' ?'selected':'' ?>>Pending</option>
                                <option value="approved" <?= $u['status']==='approved'?'selected':'' ?>>Approved</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_verified" value="1" <?= $u['is_verified']?'checked':'' ?>>
                                Verified
                            </label>
                        </div>
                        <div class="form-actions">
                            <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                            <input type="hidden" name="update_user">
                            <button type="submit" class="btn btn-primary">Save</button>
                            <a href="#" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ========== RESET PASSWORD POPUP for each user ========== -->
            <div id="resetPopup<?= $u['user_id'] ?>" class="popup-overlay">
                <div class="popup-content">
                    <a href="#" class="popup-close">&times;</a>
                    <h2>Reset Password for User #<?= $u['user_id'] ?></h2>
                    <form method="post">
                        <div class="form-group">
                            <label><input type="radio" name="reset_mode" value="auto" checked> Auto-generate</label><br>
                            <label><input type="radio" name="reset_mode" value="custom"> Set manually:</label>
                            <input name="custom_password" placeholder="new password">
                        </div>
                        <div class="form-actions">
                            <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                            <input type="hidden" name="reset_password">
                            <button type="submit" class="btn btn-primary">Reset</button>
                            <a href="#" class="btn btn-outline">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
    /* select-all */
    document.getElementById('selAll').onclick = function() {
        document.querySelectorAll('.rowChk').forEach(ch => ch.checked = this.checked);
    };

    /* Bulk action options toggle */
    function toggleBulkOptions(action) {
        document.getElementById('bulkRole').style.display = (action === 'change_role') ? 'block' : 'none';
        document.getElementById('bulkStatus').style.display = (action === 'change_status') ? 'block' : 'none';
    }

    /* Before bulk submit, copy checked IDs */
    document.getElementById('bulkForm').onsubmit = function(e) {
        const checked = document.querySelectorAll('.rowChk:checked');
        if (!checked.length) {
            alert('No users selected');
            e.preventDefault();
            return false;
        }
        checked.forEach(ch => {
            const inp = document.createElement('input');
            inp.type = 'hidden';
            inp.name = 'user_ids[]';
            inp.value = ch.value;
            e.target.appendChild(inp);
        });
    };
    </script>

</body>
</html>