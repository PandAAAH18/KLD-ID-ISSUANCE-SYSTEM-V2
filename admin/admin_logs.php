<?php
// admin_log.php  (updated for new AuditLogger structure)
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

/* ----------  HANDLE CSV EXPORT  ---------- */
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Output CSV headers
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="audit_logs_export_' . date('Y-m-d_H-i-s') . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    fputs($output, "\xEF\xBB\xBF");
    
    // CSV headers
    $headers = [
        'ID', 'Timestamp', 'Admin ID', 'Admin Name', 'Action', 
        'Table Name', 'Record ID', 'Old Data', 'New Data',
        'IP Address', 'User Agent'
    ];
    fputcsv($output, $headers);
    
    // CSV data rows
    foreach ($logs as $log) {
        // ... same formatting logic as above ...
        $row = [
            $log['id'],
            $log['created_at'],
            $log['user_id'] ?? '',
            $log['admin_name'] ?? 'System',
            $log['action'],
            $log['table_name'],
            $log['record_id'] ?? '',
            $oldDataFormatted,
            $newDataFormatted,
            $log['ip_address'] ?? '',
            $log['user_agent'] ?? ''
        ];
        
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

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

require_once 'admin_header.php';

// Get recent activity stats
$todayCount = $db->query("SELECT COUNT(*) FROM audit_logs WHERE DATE(created_at) = CURDATE()")->fetchColumn();
$weekCount = $db->query("SELECT COUNT(*) FROM audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetchColumn();
$monthCount = $db->query("SELECT COUNT(*) FROM audit_logs WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)")->fetchColumn();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Logs - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
.audit-details {
    display: none;
    position: absolute;
    background: white;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 12px;
    max-width: 350px;
    z-index: 1000; /* Increased z-index */
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
    margin-top: 5px;
    right: 10px;
    top: 100%;
}

/* Ensure the table cell has proper positioning */
.admin-table td {
    position: relative;
}

/* Style for the details button */
.btn-sm {
    padding: 4px 8px;
    font-size: 0.75rem;
    cursor: pointer;
    background: #007bff;
    color: white;
    border: none;
    border-radius: 4px;
    transition: background 0.3s;
}

.btn-sm:hover {
    background: #0056b3;
}
</style>
</head>

<body class="admin-body">
    <!-- Back to Top Button -->
    <button class="back-to-top" onclick="scrollToTop()">
        <i class="fas fa-chevron-up"></i>
    </button>

    <div class="admin-container">
        <!-- ========== PAGE HEADER ========== -->
        <div class="page-header" style="overflow: hidden;">
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
                                    <th>Admin</th>
                                    <th>Action</th>
                                    <th>Table</th>
                                    <th>Record ID</th>
                                    <th>Changes</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($logs as $row): ?>
                                <tr>
                                    <td><strong>#<?= html($row['id']) ?></strong></td>
                                    <td style="font-size: 0.9rem;">
                                        <div><?= date('M d', strtotime($row['created_at'])) ?></div>
                                        <div style="font-size: 0.8rem; color: #666;"><?= date('H:i', strtotime($row['created_at'])) ?></div>
                                    </td>
                                    <td style="font-size: 0.9rem;">
                                        <?php if ($row['admin_name']): ?>
                                            <div><?= substr(html($row['admin_name']), 0, 12) ?></div>
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
                                            <?= html(ucfirst(str_replace('_', ' ', $row['action']))) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <code style="background: #f8f9fa; padding: 2px 6px; border-radius: 3px; font-size: 0.75rem;">
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
                                    <td style="position: relative;">
    <?php 
        $oldData = formatAuditData($row['old_data']);
        $newData = formatAuditData($row['new_data']);
        $hasChanges = ($oldData !== '-' || $newData !== '-');
    ?>
    <?php if ($hasChanges): ?>
<button class="btn-audit-details" style="padding: 4px 8px; font-size: 0.75rem; cursor: pointer; background: #007bff; color: white; border: none; border-radius: 4px;" onclick="toggleAuditDetails(this)" title="View changes">            <i class="fas fa-eye"></i> Details
        </button>
        <div class="audit-details" style="display: none; position: absolute; background: white; border: 1px solid #ddd; border-radius: 6px; padding: 12px; max-width: 350px; z-index: 100; box-shadow: 0 4px 12px rgba(0,0,0,0.15); margin-top: 5px; right: 10px; top: 100%;">
            <strong style="font-size: 0.85rem; display: block; margin-bottom: 8px;">Old Data:</strong>
            <pre style="font-size: 0.75rem; background: #f5f5f5; padding: 6px; border-radius: 4px; max-height: 120px; overflow-y: auto; margin: 0 0 8px 0; white-space: pre-wrap; word-wrap: break-word;"><?= $oldData ?></pre>
            <strong style="font-size: 0.85rem; display: block; margin-bottom: 8px; margin-top: 8px;">New Data:</strong>
            <pre style="font-size: 0.75rem; background: #f5f5f5; padding: 6px; border-radius: 4px; max-height: 120px; overflow-y: auto; margin: 0; white-space: pre-wrap; word-wrap: break-word;"><?= $newData ?></pre>
            <button class="btn-sm" style="padding: 4px 8px; font-size: 0.75rem; margin-top: 8px; cursor: pointer;" onclick="this.closest('.audit-details').style.display='none';">Close</button>
        </div>
    <?php else: ?>
        <span style="color: #999; font-size: 0.9rem;">-</span>
    <?php endif; ?>
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

    // Toggle audit details popup - FIXED VERSION
    function toggleAuditDetails(button) {
        console.log('Button clicked'); // Debug log
        
        // Find the details div - next sibling
        const detailsDiv = button.nextElementSibling;
        console.log('Details div found:', detailsDiv); // Debug log
        
        if (detailsDiv && detailsDiv.classList.contains('audit-details')) {
            // Close all other details first
            document.querySelectorAll('.audit-details').forEach(d => {
                if (d !== detailsDiv) {
                    d.style.display = 'none';
                }
            });
            
            // Toggle current details
            if (detailsDiv.style.display === 'none' || detailsDiv.style.display === '') {
                detailsDiv.style.display = 'block';
                
                // Position the details popup properly
                const buttonRect = button.getBoundingClientRect();
                const detailsRect = detailsDiv.getBoundingClientRect();
                
                // Check if it would go off-screen to the right
                if (buttonRect.right + detailsRect.width > window.innerWidth) {
                    detailsDiv.style.right = '0';
                    detailsDiv.style.left = 'auto';
                }
                
                // Check if it would go off-screen to the bottom
                if (buttonRect.bottom + detailsRect.height > window.innerHeight) {
                    detailsDiv.style.bottom = '100%';
                    detailsDiv.style.top = 'auto';
                }
            } else {
                detailsDiv.style.display = 'none';
            }
        }
    }

    // Close details when clicking outside - FIXED VERSION
    document.addEventListener('click', function(e) {
        const isDetailsButton = e.target.closest('button') && 
                               (e.target.closest('button').textContent.includes('Details') || 
                                e.target.closest('button').querySelector('.fa-eye'));
        
        if (!e.target.closest('.audit-details') && !isDetailsButton) {
            document.querySelectorAll('.audit-details').forEach(d => {
                d.style.display = 'none';
            });
        }
    });

    // Export logs functionality
    function exportLogs() {
        const params = new URLSearchParams(window.location.search);
        params.append('export', 'csv');
        window.location.href = '?' + params.toString();
    }

    // Mobile sidebar toggle
    document.addEventListener('DOMContentLoaded', function() {
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', function() {
                document.getElementById('adminSidebar').classList.toggle('mobile-open');
                if (sidebarOverlay) {
                    sidebarOverlay.classList.toggle('active');
                }
            });
        }
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                document.getElementById('adminSidebar').classList.remove('mobile-open');
                this.classList.remove('active');
            });
        }

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
        const pageTitleElement = document.getElementById('pageTitle');
        if (pageTitleElement && pageTitles[currentPage]) {
            pageTitleElement.textContent = pageTitles[currentPage];
        }
    });
</script>
</body>
</html>