<?php
// admin_log.php  (advanced search version)
require_once 'admin.php';

session_start();
if (($_SESSION['user_type'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied');
}

$adminModel = new Admin();
$db = $adminModel->getDb();

/* ----------  CAPTURE FILTERS  ---------- */
$search   = $_GET['search']   ?? '';          // free text (admin name or target id)
$action   = $_GET['action']   ?? '';          // insert/update/delete/import...
$dateFrom = $_GET['date_from'] ?? '';
$dateTo   = $_GET['date_to']   ?? '';

/* ----------  BUILD WHERE CLAUSE  ---------- */
$where = [];
$params = [];

if ($search !== '') {
    // numeric = target_id , otherwise match admin name
    if (is_numeric($search)) {
        $where[]  = 'al.target_id = :tid';
        $params[':tid'] = $search;
    } else {
        $where[]  = 'u.full_name LIKE :name';
        $params[':name'] = "%$search%";
    }
}

if ($action !== '') {
    $where[] = 'al.action = :action';
    $params[':action'] = $action;
}

if ($dateFrom !== '') {
    $where[] = 'al.created_at >= :df';
    $params[':df'] = $dateFrom . ' 00:00:00';
}

if ($dateTo !== '') {
    $where[] = 'al.created_at <= :dt';
    $params[':dt'] = $dateTo . ' 23:59:59';
}

$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

/* ----------  COUNT + FETCH (500 max)  ---------- */
$countSql = "SELECT COUNT(*) 
             FROM audit_log al
             LEFT JOIN users u ON u.user_id = al.admin_id
             $sqlWhere";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();

$dataSql = "SELECT al.*, u.full_name AS admin_name
            FROM audit_log al
            LEFT JOIN users u ON u.user_id = al.admin_id
            $sqlWhere
            ORDER BY al.created_at DESC
            LIMIT 500";
$dataStmt = $db->prepare($dataSql);
$dataStmt->execute($params);
$logs = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

/* ----------  QUICK HELPER  ---------- */
function html($s) { return htmlspecialchars($s ?? '', ENT_QUOTES); }
function sel($field, $value) { return isset($_GET[$field]) && $_GET[$field] === $value ? 'selected' : ''; }
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Audit Log</title>
</head>

<body>

    <h1>Audit Log</h1>

    <!-- ===== SEARCH FORM ===== -->
    <form method="get" action="">
        <label>
            Search (admin name OR target id):
            <input type="text" name="search" value="<?= html($search) ?>" placeholder="john doe / 123">
        </label>

        <label>
            Action:
            <select name="action">
                <option value="">-- any --</option>
                <?php
// pull distinct actions straight from the table
$actions = $db->query("SELECT DISTINCT action FROM audit_log ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
foreach ($actions as $a): ?>
                <option value="<?= html($a) ?>" <?= sel('action',$a) ?>><?= html($a) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            From:
            <input type="date" name="date_from" value="<?= html($dateFrom) ?>">
        </label>

        <label>
            To:
            <input type="date" name="date_to" value="<?= html($dateTo) ?>">
        </label>

        <button type="submit">Filter</button>
        <a href="?">Clear</a>
    </form>

    <p>Total matches: <?= $totalRows ?> (max 500 displayed)</p>

    <!-- ===== RESULT TABLE ===== -->
    <table border="1" cellpadding="5" cellspacing="0">
        <thead>
            <tr>
                <th>ID</th>
                <th>When</th>
                <th>Admin</th>
                <th>Action</th>
                <th>Type</th>
                <th>Target ID</th>
                <th>Old values</th>
                <th>New values</th>
                <th>IP</th>
            </tr>
        </thead>
        <tbody>
            <?php if (!$logs): ?>
            <tr>
                <td colspan="9">No records found.</td>
            </tr>
            <?php else: ?>
            <?php foreach ($logs as $row): ?>
            <tr>
                <td><?= html($row['id']) ?></td>
                <td><?= html($row['created_at']) ?></td>
                <td><?= html($row['admin_name'] ?: 'System/'.$row['admin_id']) ?></td>
                <td><?= html($row['action']) ?></td>
                <td><?= html($row['target_type']) ?></td>
                <td><?= html($row['target_id']) ?></td>
                <td>
                    <pre><?= html($row['old_values'] ?: '-') ?></pre>
                </td>
                <td>
                    <pre><?= html($row['new_values'] ?: '-') ?></pre>
                </td>
                <td><?= html($row['ip_address']) ?></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <p><a href="admin_students.php">Back to Students</a></p>

</body>

</html>