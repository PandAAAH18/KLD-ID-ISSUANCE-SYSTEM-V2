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

// Get action counts for statistics
$actionCounts = $db->query("
    SELECT action, COUNT(*) as count 
    FROM audit_logs 
    GROUP BY action 
    ORDER BY count DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get recent activity stats
$todayCount = $db->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$weekCount = $db->query("SELECT COUNT(*) FROM audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
$monthCount = $db->query("SELECT COUNT(*) FROM audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>System Logs - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .audit-data {
            margin: 0;
            white-space: pre-wrap;
            font-family: 'Courier New', monospace;
            font-size: 0.75rem;
            max-width: 300px;
            max-height: 100px;
            overflow: auto;
            background: #f8f9fa;
            padding: 8px;
            border-radius: 4px;
            border: 1px solid #e9ecef;
        }
        .user-agent {
            max-width: 200px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        .action-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
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
            <h2><i class="fas fa-clipboard-list"></i> System Audit Logs</h2>
            <p>Monitor system activities, user actions, and administrative changes</p>
        </div>

        <!-- ========== STATISTICS DASHBOARD ========== -->
        <div class="stats-dashboard">
            <div class="stat-card">
                <div class="stat-number"><?= $totalRows ?></div>
                <div class="stat-label">Total Logs</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $todayCount ?></div>
                <div class="stat-label">Today</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $weekCount ?></div>
                <div class="stat-label">This Week</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $monthCount ?></div>
                <div class="stat-label">This Month</div>
            </div>
        </div>

        <!-- ========== SEARCH AND FILTER SECTION ========== -->
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fas fa-search"></i> Search & Filter Logs</span>
            </div>
            <div class="admin-card-body">
                <form method="get" action="" class="filter-form">
                    <div class="form-row">
                        <div class="form-group">
                            <label>Search</label>
                            <input type="text" name="search" class="form-input" 
                                   value="<?= html($search) ?>" 
                                   placeholder="Admin name or record ID">
                        </div>
                        <div class="form-group">
                            <label>Action Type</label>
                            <select name="action" class="form-select">
                                <option value="">All Actions</option>
                                <?php
                                $actions = $db->query("SELECT DISTINCT action FROM audit_logs ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
                                foreach ($actions as $a): ?>
                                <option value="<?= html($a) ?>" <?= sel('action', $a) ?>>
                                    <?= html(ucfirst(str_replace('_', ' ', $a))) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label>Date From</label>
                            <input type="date" name="date_from" class="form-input" value="<?= html($dateFrom) ?>">
                        </div>
                        <div class="form-group">
                            <label>Date To</label>
                            <input type="date" name="date_to" class="form-input" value="<?= html($dateTo) ?>">
                        </div>
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div style="display: flex; gap: 10px;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter"></i> Apply Filters
                                </button>
                                <a href="?" class="btn btn-outline">
                                    <i class="fas fa-times"></i> Clear All
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- ========== ACTION STATISTICS ========== -->
        <?php if (empty($search) && empty($action) && empty($dateFrom) && empty($dateTo)): ?>
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fas fa-chart-bar"></i> Action Distribution</span>
            </div>
            <div class="admin-card-body">
                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                    <?php foreach ($actionCounts as $actionStat): ?>
                    <div class="status-badge 
                        <?= match($actionStat['action']) {
                            'insert' => 'status-approved',
                            'update' => 'status-pending',
                            'delete' => 'status-rejected',
                            'reset_password' => 'status-generated',
                            'bulk_delete' => 'status-rejected',
                            'bulk_role_change' => 'status-pending',
                            'bulk_status_change' => 'status-pending',
                            'export' => 'status-registered',
                            default => 'status-unregistered'
                        } ?>">
                        <?= html(ucfirst(str_replace('_', ' ', $actionStat['action']))) ?>: <?= $actionStat['count'] ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ========== RESULTS SUMMARY ========== -->
        <div class="bulk-section">
            <div class="bulk-actions">
                <span style="font-weight: 600;">
                    <i class="fas fa-database"></i> 
                    Showing <?= min(500, count($logs)) ?> of <?= $totalRows ?> log entries
                    <?php if ($totalRows > 500): ?>
                        <span style="color: #666; font-weight: normal;">(max 500 displayed)</span>
                    <?php endif; ?>
                </span>
                <button type="button" class="btn btn-export" onclick="exportLogs()">
                    <i class="fas fa-download"></i> Export Logs
                </button>
            </div>
        </div>

        <!-- ========== LOGS TABLE ========== -->
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fas fa-list"></i> Audit Log Entries</span>
                <span class="badge"><?= count($logs) ?></span>
            </div>
            <div class="admin-card-body">
                <?php if (empty($logs)): ?>
                    <div class="empty-state">
                        <i class="fas fa-search"></i>
                        <h4>No Logs Found</h4>
                        <p>No audit logs match your current filter criteria</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Timestamp</th>
                                    <th>Administrator</th>
                                    <th>Action</th>
                                    <th>Table</th>
                                    <th>Record ID</th>
                                    <th>Old Data</th>
                                    <th>New Data</th>
                                    <th>IP Address</th>
                                    <th>User Agent</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $row): ?>
                                <tr>
                                    <td><strong>#<?= html($row['id']) ?></strong></td>
                                    <td>
                                        <div style="font-weight: 600;"><?= date('M d, Y', strtotime($row['created_at'])) ?></div>
                                        <div style="font-size: 0.75rem; color: #666;"><?= date('H:i:s', strtotime($row['created_at'])) ?></div>
                                    </td>
                                    <td>
                                        <?php if ($row['admin_name']): ?>
                                            <div style="font-weight: 600;"><?= html($row['admin_name']) ?></div>
                                            <div style="font-size: 0.75rem; color: #666;">ID: <?= html($row['user_id']) ?></div>
                                        <?php else: ?>
                                            <span style="color: #666;">System</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="action-badge 
                                            <?= match($row['action']) {
                                                'insert' => 'status-approved',
                                                'update' => 'status-pending',
                                                'delete' => 'status-rejected',
                                                'reset_password' => 'status-generated',
                                                'bulk_delete' => 'status-rejected',
                                                'bulk_role_change' => 'status-pending',
                                                'bulk_status_change' => 'status-pending',
                                                'export' => 'status-registered',
                                                default => 'status-unregistered'
                                            } ?>">
                                            <?= html($row['action']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-size: 0.8rem;">
                                            <?= html($row['table_name']) ?>
                                        </code>
                                    </td>
                                    <td>
                                        <?php if ($row['record_id']): ?>
                                            <strong><?= html($row['record_id']) ?></strong>
                                        <?php else: ?>
                                            <span style="color: #666;">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <pre class="audit-data"><?= formatAuditData($row['old_data']) ?></pre>
                                    </td>
                                    <td>
                                        <pre class="audit-data"><?= formatAuditData($row['new_data']) ?></pre>
                                    </td>
                                    <td>
                                        <code style="font-size: 0.8rem;"><?= html($row['ip_address']) ?></code>
                                    </td>
                                    <td>
                                        <span class="user-agent" title="<?= html($row['user_agent']) ?>">
                                            <?= html($row['user_agent']) ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
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

        // Export logs functionality
        function exportLogs() {
            const params = new URLSearchParams(window.location.search);
            params.append('export', 'csv');
            window.location.href = '?' + params.toString();
        }

        // Auto-expand audit data on click
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('audit-data')) {
                const pre = e.target;
                if (pre.style.maxHeight === 'none') {
                    pre.style.maxHeight = '100px';
                    pre.style.position = 'static';
                } else {
                    pre.style.maxHeight = 'none';
                    pre.style.position = 'relative';
                    pre.style.zIndex = '10';
                }
            }
        });

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