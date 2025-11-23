<?php
session_start();
require_once 'classes/ReportsManager.php';

// Check admin access
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../index.php');
}

$reportsManager = new ReportsManager();

// Handle export request
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $reportsManager->exportStatsToCSV();
}

// Get all statistics
$stats = $reportsManager->getOverallStats();
$courseStats = $reportsManager->getStudentsByCourse();
$yearLevelStats = $reportsManager->getStudentsByYearLevel();
$profileStats = $reportsManager->getProfileCompletionStats();
$idGenStats = $reportsManager->getIdGenerationStats();
$idReqStats = $reportsManager->getIdRequestStats();
$userVerStats = $reportsManager->getUserVerificationStats();
$userStatusStats = $reportsManager->getUserStatusStats();
$recentActivities = $reportsManager->getRecentActivities(10);

require_once 'admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reports & Analytics - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .stats-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #eaeaea;
            text-align: center;
            height: 100%;
        }
        
        .stats-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        
        .stats-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 15px;
            font-size: 2rem;
        }
        
        .stats-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--school-green);
            margin: 10px 0;
            line-height: 1;
        }
        
        .stats-label {
            color: #666;
            font-weight: 500;
            font-size: 0.95rem;
            margin: 0;
        }
        
        .progress-container {
            background: #f8f9fa;
            border-radius: 10px;
            height: 20px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(135deg, var(--school-green) 0%, var(--school-green-dark) 100%);
            transition: width 0.3s ease;
        }
        
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }
        
        .data-table th {
            background: linear-gradient(135deg, var(--school-green) 0%, var(--school-green-dark) 100%);
            color: white;
            padding: 12px 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #eaeaea;
        }
        
        .data-table tr:hover {
            background-color: rgba(56, 142, 60, 0.05);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid var(--school-yellow);
        }
        
        .section-title {
            color: var(--school-green);
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .metric-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .chart-placeholder {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
            border: 1px solid #eaeaea;
            text-align: center;
            color: #666;
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        
        .chart-placeholder i {
            font-size: 3rem;
            margin-bottom: 15px;
            color: #ddd;
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
            <h2><i class="fas fa-chart-line"></i> Reports & Analytics</h2>
            <p>Comprehensive system statistics and performance metrics</p>
        </div>

        <!-- ========== QUICK ACTIONS ========== -->
        <div class="action-section">
            <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
            <p>Generate reports and export data</p>
            <div class="bulk-actions">
                <a href="?export=csv" class="btn btn-export">
                    <i class="fas fa-download"></i> Export to CSV
                </a>
                <button type="button" class="btn btn-outline" onclick="window.print()">
                    <i class="fas fa-print"></i> Print Report
                </button>
                <button type="button" class="btn btn-outline" onclick="refreshData()">
                    <i class="fas fa-sync"></i> Refresh Data
                </button>
            </div>
        </div>

        <!-- ========== KEY METRICS DASHBOARD ========== -->
        <div class="stats-dashboard">
            <div class="stat-card">
                <div class="stats-icon" style="background: linear-gradient(135deg, #007bff 0%, #0056b3 100%); color: white;">
                    <i class="fas fa-users"></i>
                </div>
                <div class="stats-number"><?= number_format($stats['total_students']) ?></div>
                <div class="stats-label">Total Students</div>
            </div>
            
            <div class="stat-card">
                <div class="stats-icon" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%); color: white;">
                    <i class="fas fa-user-tie"></i>
                </div>
                <div class="stats-number"><?= number_format($stats['total_users']) ?></div>
                <div class="stats-label">System Users</div>
            </div>
            
            <div class="stat-card">
                <div class="stats-icon" style="background: linear-gradient(135deg, #ffc107 0%, #e0a800 100%); color: white;">
                    <i class="fas fa-user-shield"></i>
                </div>
                <div class="stats-number"><?= number_format($stats['total_admins']) ?></div>
                <div class="stats-label">Admin Users</div>
            </div>
            
            <div class="stat-card">
                <div class="stats-icon" style="background: linear-gradient(135deg, #28a745 0%, #1e7e34 100%); color: white;">
                    <i class="fas fa-id-card"></i>
                </div>
                <div class="stats-number"><?= number_format($stats['total_ids_generated']) ?></div>
                <div class="stats-label">IDs Generated</div>
            </div>
        </div>

        <!-- ========== ID REQUESTS & PROFILE COMPLETION ========== -->
        <div class="form-row">
            <div class="form-group" style="flex: 1;">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <span><i class="fas fa-file-invoice"></i> ID Request Overview</span>
                    </div>
                    <div class="admin-card-body">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                            <span>Total ID Requests:</span>
                            <strong><?= number_format($stats['total_id_requests']) ?></strong>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 20px;">
                            <span>Pending Requests:</span>
                            <strong class="status-badge status-pending"><?= number_format($stats['pending_id_requests']) ?></strong>
                        </div>
                        <div class="progress-container">
                            <div class="progress-bar" 
                                 style="width: <?= $stats['total_id_requests'] > 0 ? ($stats['pending_id_requests'] / $stats['total_id_requests'] * 100) : 0 ?>%;">
                            </div>
                        </div>
                        <div style="text-align: center; margin-top: 10px; font-size: 0.85rem; color: #666;">
                            <?= $stats['total_id_requests'] > 0 ? round(($stats['pending_id_requests'] / $stats['total_id_requests'] * 100), 1) : 0 ?>% Pending
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group" style="flex: 1;">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <span><i class="fas fa-chart-pie"></i> Profile Completion</span>
                    </div>
                    <div class="admin-card-body">
                        <?php foreach ($profileStats as $stat): ?>
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; padding: 8px; background: #f8f9fa; border-radius: 6px;">
                            <span><?= htmlspecialchars($stat['status']) ?>:</span>
                            <strong><?= number_format($stat['count']) ?></strong>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== STUDENTS BY COURSE AND YEAR LEVEL ========== -->
        <div class="form-row">
            <div class="form-group" style="flex: 1;">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <span><i class="fas fa-book"></i> Students by Course</span>
                    </div>
                    <div class="admin-card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th style="text-align: right;">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($courseStats as $course): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($course['course'] ?? 'N/A') ?></td>
                                        <td style="text-align: right;">
                                            <span class="status-badge status-registered"><?= number_format($course['count']) ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group" style="flex: 1;">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <span><i class="fas fa-graduation-cap"></i> Students by Year Level</span>
                    </div>
                    <div class="admin-card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Year Level</th>
                                        <th style="text-align: right;">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($yearLevelStats as $year): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($year['year_level'] ?? 'N/A') ?></td>
                                        <td style="text-align: right;">
                                            <span class="status-badge status-generated"><?= number_format($year['count']) ?></span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== ID GENERATION AND REQUEST STATUS ========== -->
        <div class="form-row">
            <div class="form-group" style="flex: 1;">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <span><i class="fas fa-id-badge"></i> ID Generation Status</span>
                    </div>
                    <div class="admin-card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th style="text-align: right;">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($idGenStats as $status): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $statusClass = match($status['status']) {
                                                'pending' => 'status-pending',
                                                'generated' => 'status-generated',
                                                'printed' => 'status-approved',
                                                'delivered' => 'status-completed',
                                                'revoked' => 'status-rejected',
                                                default => 'status-unregistered'
                                            };
                                            ?>
                                            <span class="status-badge <?= $statusClass ?>"><?= ucfirst($status['status']) ?></span>
                                        </td>
                                        <td style="text-align: right;"><?= number_format($status['count']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group" style="flex: 1;">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <span><i class="fas fa-list-check"></i> ID Request Status</span>
                    </div>
                    <div class="admin-card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th style="text-align: right;">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($idReqStats as $status): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $statusClass = match($status['status']) {
                                                'pending' => 'status-pending',
                                                'approved' => 'status-approved',
                                                'generated' => 'status-generated',
                                                'rejected' => 'status-rejected',
                                                default => 'status-unregistered'
                                            };
                                            ?>
                                            <span class="status-badge <?= $statusClass ?>"><?= ucfirst($status['status']) ?></span>
                                        </td>
                                        <td style="text-align: right;"><?= number_format($status['count']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== USER STATISTICS ========== -->
        <div class="form-row">
            <div class="form-group" style="flex: 1;">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <span><i class="fas fa-check-circle"></i> User Verification Status</span>
                    </div>
                    <div class="admin-card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th style="text-align: right;">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($userVerStats as $status): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $statusClass = $status['status'] === 'Verified' ? 'status-verified' : 'status-pending';
                                            ?>
                                            <span class="status-badge <?= $statusClass ?>"><?= $status['status'] ?></span>
                                        </td>
                                        <td style="text-align: right;"><?= number_format($status['count']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-group" style="flex: 1;">
                <div class="admin-card">
                    <div class="admin-card-header">
                        <span><i class="fas fa-user-check"></i> User Account Status</span>
                    </div>
                    <div class="admin-card-body">
                        <div class="table-responsive">
                            <table class="data-table">
                                <thead>
                                    <tr>
                                        <th>Status</th>
                                        <th style="text-align: right;">Count</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($userStatusStats as $status): ?>
                                    <tr>
                                        <td>
                                            <?php 
                                            $statusClass = match($status['status']) {
                                                'active' => 'status-active',
                                                'pending' => 'status-pending',
                                                'inactive' => 'status-inactive',
                                                'suspended' => 'status-rejected',
                                                default => 'status-unregistered'
                                            };
                                            ?>
                                            <span class="status-badge <?= $statusClass ?>"><?= ucfirst($status['status']) ?></span>
                                        </td>
                                        <td style="text-align: right;"><?= number_format($status['count']) ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ========== RECENT ACTIVITIES ========== -->
        <div class="admin-card">
            <div class="admin-card-header">
                <span><i class="fas fa-history"></i> Recent Activities</span>
            </div>
            <div class="admin-card-body">
                <?php if (!empty($recentActivities)): ?>
                    <div class="table-responsive">
                        <table class="data-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Action</th>
                                    <th>Table</th>
                                    <th>Record ID</th>
                                    <th>Admin</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentActivities as $activity): ?>
                                <tr>
                                    <td><strong>#<?= $activity['id'] ?></strong></td>
                                    <td>
                                        <?php 
                                        $actionClass = match($activity['action']) {
                                            'insert' => 'status-approved',
                                            'update' => 'status-pending',
                                            'delete' => 'status-rejected',
                                            default => 'status-unregistered'
                                        };
                                        ?>
                                        <span class="status-badge <?= $actionClass ?>"><?= ucfirst($activity['action']) ?></span>
                                    </td>
                                    <td><code><?= htmlspecialchars($activity['table_name']) ?></code></td>
                                    <td><?= $activity['record_id'] ?></td>
                                    <td><?= htmlspecialchars($activity['admin_name'] ?? 'System') ?></td>
                                    <td><small class="text-muted"><?= date('M d, Y H:i', strtotime($activity['created_at'])) ?></small></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-inbox"></i>
                        <h4>No Activities Recorded</h4>
                        <p>No system activities have been recorded yet</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ========== CHART PLACEHOLDERS ========== -->
        <div class="form-row">
            <div class="form-group" style="flex: 2;">
                <div class="chart-placeholder">
                    <i class="fas fa-chart-bar"></i>
                    <h4>Analytics Charts</h4>
                    <p>Interactive charts and graphs will be displayed here</p>
                    <button class="btn btn-primary mt-3" onclick="showCharts()">
                        <i class="fas fa-chart-line"></i> Enable Charts
                    </button>
                </div>
            </div>
            <div class="form-group" style="flex: 1;">
                <div class="chart-placeholder">
                    <i class="fas fa-chart-pie"></i>
                    <h4>Distribution</h4>
                    <p>Data distribution visualization</p>
                </div>
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

        // Refresh data
        function refreshData() {
            window.location.reload();
        }

        // Show charts placeholder
        function showCharts() {
            alert('Chart functionality would be implemented here with a charting library like Chart.js');
        }

        // Auto-refresh every 5 minutes
        setInterval(refreshData, 300000);

        // Print functionality
        function printReport() {
            window.print();
        }
    </script>
</body>
</html>