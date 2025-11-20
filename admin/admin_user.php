<?php
/* ----------  BOOTSTRAP (same order you already use)  ---------- */
session_start();
require_once 'admin.php';

if (($_SESSION['user_type'] ?? '') !== 'admin') {
    redirect('../index.php');
}

$adminModel = new Admin();               // or your global PDO variable
$message = '';
$error   = '';

/* =================  HANDLE POST ACTIONS  ================= */
// 1.  ADD USER
if (isset($_POST['add_user'])) {
    $ok = $adminModel->addUser($_POST);
    if ($ok) {
        $message = 'User added.';
    } else {
        $error = 'Failed to add user.';
    }
}

// 2.  UPDATE USER
if (isset($_POST['update_user'])) {
    $ok = $adminModel->updateUser((int)$_POST['user_id'], $_POST);
    if ($ok) {
        $message = 'User updated.';
    } else {
        $error = 'Update failed.';
    }
}

// 3.  RESET PASSWORD
if (isset($_POST['reset_password'])) {
    $mode = $_POST['reset_mode'];               // 'auto' or 'custom'
    $pwd  = $mode === 'auto' ? '' : $_POST['custom_password'];
    $ok   = $adminModel->resetUserPassword((int)$_POST['user_id'], $pwd);
    if ($ok) {
        $message = 'Password reset.';
    } else {
        $error = 'Reset failed.';
    }
}

// 4.  SOFT DELETE
if (isset($_POST['delete_user'])) {
    $ok = $adminModel->deleteUser((int)$_POST['user_id']);
    if ($ok) {
        $message = 'User deleted.';
    } else {
        $error = 'Delete failed.';
    }
}

// 5.  BULK ACTIONS
if (isset($_POST['bulk_action']) && !empty($_POST['user_ids'])) {
    $ids = array_map('intval', $_POST['user_ids']);
    switch ($_POST['bulk_action']) {
        case 'delete':
            $adminModel->bulkDeleteUsers($ids);
            $message = 'Bulk delete done.';
            break;
        case 'change_role':
            $newRole = $_POST['bulk_role'] ?? '';
            if ($newRole) {
                $adminModel->bulkChangeRole($ids, $newRole);
                $message = 'Roles updated.';
            }
            break;
        case 'change_status':
            $newStatus = $_POST['bulk_status'] ?? '';
            if ($newStatus) {
                $adminModel->bulkChangeStatus($ids, $newStatus);
                $message = 'Status updated.';
            }
            break;
        case 'export':
            $adminModel->bulkExportUsers($ids);
            exit;   // csv download
    }
}

/* =================  READ DATA  ================= */
// filters from GET (kept in query string for bookmarking)
$filters = [
    'name'      => $_GET['name']      ?? '',
    'email'     => $_GET['email']     ?? '',
    'role'      => $_GET['role']      ?? '',
    'status'    => $_GET['status']    ?? '',
    'verified'  => $_GET['verified']  ?? '',
];

$users = $adminModel->getUsers($filters);   // returns array (see functions below)
$roles = ['admin','student','staff'];       // whatever you allow
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>User Management</title>
</head>

<body>

    <h1>User Management</h1>

    <?php if ($message): ?><p style="color:green"><?= htmlspecialchars($message) ?></p><?php endif; ?>
    <?php if ($error):   ?><p style="color:red"><?=   htmlspecialchars($error) ?></p><?php endif; ?>

    <!-- 1.  ADD USER  -->
<h2>Add New User</h2>
<form method="post">
    <label>First Name:</label>
    <input name="first_name" required>

    <label>Last Name:</label>
    <input name="last_name" required>

    <label>Type:</label>
    <select name="role" required>
        <option value="">-- select --</option>
        <option value="student">Student</option>
        <option value="admin">Admin</option>
    </select>

    <input type="hidden" name="add_user">
    <button type="submit">Create</button>
    <br><small>Email will be built automatically (first-letter(s) + last name @kld.edu.ph)</small>
</form>

    <!-- 2.  SEARCH / FILTER  -->
    <h2>Search / Filter</h2>
    <form method="get">
        Name: <input name="name" value="<?= htmlspecialchars($filters['name']) ?>" placeholder="any part of name">
        Email: <input name="email" value="<?= htmlspecialchars($filters['email']) ?>" placeholder="email">
        Role:
        <select name="role">
            <option value="">-- any --</option>
            <?php foreach ($roles as $r): ?>
            <option value="<?= htmlspecialchars($r) ?>"
                <?= isset($filters['role']) && $filters['role']===$r ? 'selected':'' ?>><?= htmlspecialchars($r) ?>
            </option>
            <?php endforeach; ?>
        </select>
        Status:
        <select name="status">
            <option value="">-- any --</option>
            <option value="pending" <?= $filters['status']==='pending' ? 'selected':'' ?>>Pending</option>
            <option value="approved" <?= $filters['status']==='approved'? 'selected':'' ?>>Approved</option>
        </select>
        Verified:
        <select name="verified">
            <option value="">-- any --</option>
            <option value="1" <?= $filters['verified']==='1' ? 'selected':'' ?>>Yes</option>
            <option value="0" <?= $filters['verified']==='0' ? 'selected':'' ?>>No</option>
        </select>
        <button type="submit">Filter</button>
        <a href="?">Clear</a>
    </form>

    <!-- 3.  BULK ACTIONS  -->
    <h2>Bulk Actions</h2>
    <form method="post" id="bulkForm" onsubmit="return confirm('Proceed with bulk action?')">
        <select name="bulk_action" required>
            <option value="">-- choose --</option>
            <option value="delete">Soft-delete selected</option>
            <option value="change_role">Change role</option>
            <option value="change_status">Change status</option>
            <option value="export">Export selected</option>
        </select>

        <span id="bulkRole" style="display:none"> to
            <select name="bulk_role"><?php foreach ($roles as $r) echo "<option>$r</option>"; ?></select>
        </span>
        <span id="bulkStatus" style="display:none"> to
            <select name="bulk_status">
                <option>pending</option>
                <option>approved</option>
            </select>
        </span>

        <button type="submit">Apply</button>
        <a href="?">Clear</a>
    </form>

    <?php
/*  ----------  DATA TABLE (NO FORM WRAPPER)  ----------  */
?>
    <table border="1" cellpadding="5" cellspacing="0">
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
                <!--  check-boxes  (will be collected by JS)  -->
                <td><input type="checkbox" class="rowChk" value="<?= $u['user_id'] ?>"></td>

                <td><?= $u['user_id'] ?></td>
                <td><?= htmlspecialchars($u['full_name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['role']) ?></td>
                <td><?= htmlspecialchars($u['status']) ?></td>
                <td><?= $u['is_verified'] ? 'Yes' : 'No' ?></td>
                <td><?= $u['created_at'] ?></td>

                <td>
                    <form method="get" action="" style="display:inline">
                        <input type="hidden" name="edit" value="<?= $u['user_id'] ?>">
                        <button type="submit">Edit</button>
                    </form>

                    <form method="get" action="" style="display:inline">
                        <input type="hidden" name="reset" value="<?= $u['user_id'] ?>">
                        <button type="submit">Reset Pwd</button>
                    </form>
                    <form method="post" style="display:inline" onsubmit="return confirm('Delete?')">
                        <input type="hidden" name="user_id" value="<?= $u['user_id'] ?>">
                        <input type="hidden" name="delete_user">
                        <button type="submit">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <!-- 5.  EDIT USER MODAL (simple inline form)  -->
    <?php if (isset($_GET['edit'])):
    $uid = (int)$_GET['edit'];
    $u   = $adminModel->getUserById($uid);
    if ($u):
?>
    <h2>Edit User #<?= $uid ?></h2>
    <form method="post">
        <input type="hidden" name="user_id" value="<?= $uid ?>">
        <input type="hidden" name="update_user">
        Name: <input name="full_name" value="<?= htmlspecialchars($u['full_name']) ?>" required><br>
        Email: <input name="email" value="<?= htmlspecialchars($u['email']) ?>" required><br>
        Role:
        <select name="role" required>
            <?php foreach ($roles as $r): ?>
            <option value="<?= htmlspecialchars($r) ?>" <?= $u['role']===$r?'selected':'' ?>><?= htmlspecialchars($r) ?>
            </option>
            <?php endforeach; ?>
        </select><br>
        Status:
        <select name="status" required>
            <option value="pending" <?= $u['status']==='pending' ?'selected':'' ?>>Pending</option>
            <option value="approved" <?= $u['status']==='approved'?'selected':'' ?>>Approved</option>
        </select><br>
        Verified: <input type="checkbox" name="is_verified" value="1" <?= $u['is_verified']?'checked':'' ?>><br>
        <button type="submit">Save</button>
        <a href="?">Cancel</a>
    </form>
    <?php endif; endif; ?>

    <!-- 6.  RESET PASSWORD FORM  -->
    <?php if (isset($_GET['reset'])):
    $uid = (int)$_GET['reset'];
?>
    <h2>Reset Password for User #<?= $uid ?></h2>
    <form method="post">
        <input type="hidden" name="user_id" value="<?= $uid ?>">
        <input type="hidden" name="reset_password">
        <label><input type="radio" name="reset_mode" value="auto" checked> Auto-generate</label><br>
        <label><input type="radio" name="reset_mode" value="custom"> Set manually:</label>
        <input name="custom_password" placeholder="new password"><br>
        <button type="submit">Reset</button>
        <a href="?">Cancel</a>
    </form>
    <?php endif; ?>

    <!-- 7.  DELETE CONFIRMATION (GET fallback)  -->
    <?php if (isset($_GET['delete'])):
    $uid = (int)$_GET['delete']; ?>
    <h2>Confirm Delete</h2>
    <form method="post">
        <input type="hidden" name="user_id" value="<?= $uid ?>">
        <input type="hidden" name="delete_user">
        <button type="submit">Yes, delete</button>
        <a href="?">Cancel</a>
    </form>
    <?php endif; ?>

    <!-- tiny JS for select-all + bulk helper show/hide -->
    <script>
    /* select-all */
    document.getElementById('selAll').onclick = function() {
        document.querySelectorAll('.rowChk').forEach(ch => ch.checked = this.checked);
    };

    /* before bulk submit, copy checked IDs into the bulk form */
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

    /* show/hide sub-selects */
    document.querySelector('select[name="bulk_action"]').onchange = function() {
        document.getElementById('bulkRole').style.display = (this.value === 'change_role') ? 'inline' : 'none';
        document.getElementById('bulkStatus').style.display = (this.value === 'change_status') ? 'inline' : 'none';
    };
    </script>

</body>

</html>