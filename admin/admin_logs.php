<?php
// admin_log.php  (updated for new AuditLogger structure)
require_once 'admin.php';

session_start();
if (($_SESSION['user_type'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Access denied');
}
require_once 'admin_header.php';

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
    // numeric = record_id , otherwise match admin name
    if (is_numeric($search)) {
        $where[]  = 'al.record_id = :rid';
        $params[':rid'] = $search;
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
             FROM audit_logs al
             LEFT JOIN users u ON u.user_id = al.user_id
             $sqlWhere";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalRows = (int) $countStmt->fetchColumn();

$dataSql = "SELECT al.*, u.full_name AS admin_name
            FROM audit_logs al
            LEFT JOIN users u ON u.user_id = al.user_id
            $sqlWhere
            ORDER BY al.created_at DESC
            LIMIT 500";
$dataStmt = $db->prepare($dataSql);
$dataStmt->execute($params);
$logs = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

/* ----------  QUICK HELPER  ---------- */
function html($s) { return htmlspecialchars($s ?? '', ENT_QUOTES); }
function sel($field, $value) { return isset($_GET[$field]) && $_GET[$field] === $value ? 'selected' : ''; }

// Helper to format JSON data for display
function formatAuditData($jsonData) {
    if (empty($jsonData)) return '-';
    
    $data = json_decode($jsonData, true);
    if (json_last_error() !== JSON_ERROR_NONE || empty($data)) {
        return '-';
    }
    
    $output = '';
    foreach ($data as $key => $value) {
        if (is_array($value)) {
            $value = json_encode($value);
        } elseif (is_bool($value)) {
            $value = $value ? 'true' : 'false';
        }
        $output .= htmlspecialchars("$key: $value") . "\n";
    }
    return $output;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Audit Log</title>
    <style>
        table {
            border-collapse: collapse;
            width: 100%;
            margin: 20px 0;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
            position: sticky;
            top: 0;
        }
        pre {
            margin: 0;
            white-space: pre-wrap;
            font-family: inherit;
            font-size: 12px;
            max-width: 300px;
            max-height: 100px;
            overflow: auto;
        }
        form {
            background: #f9f9f9;
            padding: 20px;
            margin: 20px 0;
            border-radius: 5px;
        }
        label {
            display: inline-block;
            margin: 0 15px 10px 0;
        }
        input, select {
            padding: 5px;
            margin-left: 5px;
        }
        button {
            padding: 6px 12px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }
    </style>
</head>

<body>

    <div class="container">
        <h1>Audit Log</h1>

        <!-- ===== SEARCH FORM ===== -->
        <form method="get" action="">
            <div>
                <label>
                    Search (admin name OR record id):
                    <input type="text" name="search" value="<?= html($search) ?>" placeholder="john doe / 123">
                </label>

                <label>
                    Action:
                    <select name="action">
                        <option value="">-- any --</option>
                        <?php
                        // pull distinct actions from the new table structure
                        $actions = $db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
                        foreach ($actions as $a): ?>
                        <option value="<?= html($a) ?>" <?= sel('action', $a) ?>><?= html($a) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            
            <div style="margin-top: 10px;">
                <label>
                    From:
                    <input type="date" name="date_from" value="<?= html($dateFrom) ?>">
                </label>

                <label>
                    To:
                    <input type="date" name="date_to" value="<?= html($dateTo) ?>">
                </label>

                <button type="submit">Filter</button>
                <a href="?" style="margin-left: 10px;">Clear</a>
            </div>
        </form>

        <p>Total matches: <?= $totalRows ?> (max 500 displayed)</p>

        <!-- ===== RESULT TABLE ===== -->
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>When</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Table</th>
                    <th>Record ID</th>
                    <th>Old Values</th>
                    <th>New Values</th>
                    <th>IP</th>
                    <th>User Agent</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$logs): ?>
                <tr>
                    <td colspan="10" style="text-align: center;">No records found.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($logs as $row): ?>
                <tr>
                    <td><?= html($row['id']) ?></td>
                    <td><?= html($row['created_at']) ?></td>
                    <td><?= html($row['admin_name'] ?: 'System/' . ($row['user_id'] ?? 'N/A')) ?></td>
                    <td>
                        <span style="
                            padding: 2px 6px;
                            border-radius: 3px;
                            font-size: 12px;
                            font-weight: bold;
                            background: <?= match($row['action']) {
                                'insert' => '#d4edda',
                                'update' => '#fff3cd', 
                                'delete' => '#f8d7da',
                                'reset_password' => '#cce7ff',
                                'bulk_delete' => '#f8d7da',
                                'bulk_role_change' => '#fff3cd',
                                'bulk_status_change' => '#fff3cd',
                                'export' => '#e2e3e5',
                                default => '#e2e3e5'
                            } ?>;
                            color: <?= match($row['action']) {
                                'insert' => '#155724',
                                'update' => '#856404',
                                'delete' => '#721c24',
                                'reset_password' => '#004085',
                                'bulk_delete' => '#721c24',
                                'bulk_role_change' => '#856404',
                                'bulk_status_change' => '#856404',
                                'export' => '#383d41',
                                default => '#383d41'
                            } ?>;
                        ">
                            <?= html($row['action']) ?>
                        </span>
                    </td>
                    <td><?= html($row['table_name']) ?></td>
                    <td><?= html($row['record_id']) ?></td>
                    <td>
                        <pre><?= formatAuditData($row['old_data']) ?></pre>
                    </td>
                    <td>
                        <pre><?= formatAuditData($row['new_data']) ?></pre>
                    </td>
                    <td><?= html($row['ip_address']) ?></td>
                    <td>
                        <span title="<?= html($row['user_agent']) ?>">
                            <?= html(substr($row['user_agent'] ?? '', 0, 30)) ?><?= strlen($row['user_agent'] ?? '') > 30 ? '...' : '' ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

    <script>
// Mobile sidebar toggle
document.getElementById('mobileMenuBtn').addEventListener('click', function() {
    document.getElementById('adminSidebar').classList.toggle('mobile-open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
});

document.getElementById('sidebarOverlay').addEventListener('click', function() {
    document.getElementById('adminSidebar').classList.remove('mobile-open');
    this.classList.remove('active');
});

// Update page title based on current page
const pageTitles = {
    'admin_dashboard.php': 'Dashboard Overview',
    'admin_students.php': 'Student Management',
    'admin_id.php': 'ID Card Management',
    'admin_user.php': 'User Management',
    'admin_reports.php': 'Reports & Analytics',
    'admin_logs.php': 'System Logs'
};

const currentPage = '<?= basename($_SERVER['PHP_SELF']) ?>';
if (pageTitles[currentPage]) {
    document.getElementById('pageTitle').textContent = pageTitles[currentPage];
}
</script>
</body>

</html>