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
    <!-- Add Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .data-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8rem;
        }
        
        .data-table th {
            background: linear-gradient(135deg, var(--school-green) 0%, var(--school-green-dark) 100%);
            color: white;
            padding: 8px 10px;
            text-align: left;
            font-weight: 600;
        }
        
        .data-table td {
            padding: 8px 10px;
            border-bottom: 1px solid #eaeaea;
        }
        
        .data-table tr:hover {
            background-color: rgba(56, 142, 60, 0.05);
        }
        
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--school-yellow);
        }
        
        .section-title {
            color: var(--school-green);
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 1.1rem;
        }
        
        .chart-container {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
            border: 1px solid #eaeaea;
            margin-bottom: 20px;
        }
        
        .chart-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .chart-title {
            font-weight: 600;
            color: var(--school-green);
            min-width: 150px;
            text-align: center;
            font-size: 0.9rem;
        }
        
        .toggle-btn {
            background: var(--school-yellow);
            color: var(--school-dark);
            border: none;
            padding: 6px 12px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.8rem;
        }
        
        .toggle-btn:hover {
            background: #a08603;
            color: white;
        }
        
        .toggle-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        
        .chart-selection {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 8px;
            margin-top: 15px;
        }
        
        .chart-btn {
            background: #f8f9fa;
            border: 1px solid #eaeaea;
            padding: 8px 10px;
            border-radius: 6px;
            cursor: pointer;
            text-align: center;
            transition: all 0.3s ease;
            font-size: 0.8rem;
        }
        
        .chart-btn:hover {
            border-color: var(--school-yellow);
            background: var(--school-yellow-light);
        }
        
        .chart-btn.active {
            border-color: var(--school-green);
            background: var(--school-green);
            color: white;
        }
        
        .table-toggle {
            margin-top: 10px;
            text-align: center;
        }
        
        .compact-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 10px;
            margin-bottom: 15px;
            font-size: 0.85rem;
        }
        
        .stat-item {
            background: #f8f9fa;
            padding: 10px;
            border-radius: 6px;
            text-align: center;
            border-left: 3px solid var(--school-green);
        }
        
        .stat-number {
            font-weight: bold;
            color: var(--school-green);
            font-size: 1.1rem;
        }
        
        .stat-label {
            color: #666;
            font-size: 0.75rem;
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

        <!-- ========== COMPACT STATS OVERVIEW ========== -->
        <div class="compact-stats">
            <div class="stat-item">
                <div class="stat-number"><?= number_format($stats['total_students']) ?></div>
                <div class="stat-label">Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= number_format($stats['total_users']) ?></div>
                <div class="stat-label">System Users</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= number_format($stats['total_admins']) ?></div>
                <div class="stat-label">Admins</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= number_format($stats['total_ids_generated']) ?></div>
                <div class="stat-label">IDs Generated</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= number_format($stats['total_id_requests']) ?></div>
                <div class="stat-label">ID Requests</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?= number_format($stats['pending_id_requests']) ?></div>
                <div class="stat-label">Pending</div>
            </div>
        </div>

        <!-- ========== ALL-IN-ONE DATA VISUALIZATION ========== -->
        <div class="chart-container">
            <div class="section-header">
                <h3 class="section-title"><i class="fas fa-chart-bar"></i> Data Visualization</h3>
                <div class="chart-controls">
                    <button class="toggle-btn" onclick="prevChart()" id="prevBtn">
                        <i class="fas fa-chevron-left"></i> 
                    </button>
                    <span id="chartTitle" class="chart-title">User Distribution</span>
                    <button class="toggle-btn" onclick="nextChart()" id="nextBtn">
                        <i class="fas fa-chevron-right"></i>
                    </button>
                </div>
            </div>
            
            <!-- Main Chart Display -->
            <div id="mainChart">
                <canvas id="dynamicChart" height="350"></canvas>
            </div>
            
            <!-- Table View (Hidden by Default) -->
            <div id="tableView" style="display: none;">
                <div class="table-responsive" id="tableContent">
                    <!-- Table content will be loaded here -->
                </div>
            </div>
            
            <!-- Toggle Button -->
            <div class="table-toggle">
                <button class="toggle-btn" onclick="toggleChartTable()" id="toggleBtn">
                    <i class="fas fa-table"></i> Show Table
                </button>
            </div>
            
            <!-- Chart Selection Buttons -->
            <div class="chart-selection">
                <button class="chart-btn active" onclick="showChart(0)">
                    <i class="fas fa-users"></i> User Distribution
                </button>
                <button class="chart-btn" onclick="showChart(1)">
                    <i class="fas fa-book"></i> By Course
                </button>
                <button class="chart-btn" onclick="showChart(2)">
                    <i class="fas fa-graduation-cap"></i> Year Level
                </button>
                <button class="chart-btn" onclick="showChart(3)">
                    <i class="fas fa-id-badge"></i> ID Generation
                </button>
                <button class="chart-btn" onclick="showChart(4)">
                    <i class="fas fa-list-check"></i> ID Requests
                </button>
                <button class="chart-btn" onclick="showChart(5)">
                    <i class="fas fa-check-circle"></i> Verification
                </button>
                <button class="chart-btn" onclick="showChart(6)">
                    <i class="fas fa-user-check"></i> User Status
                </button>
                <button class="chart-btn" onclick="showChart(7)">
                    <i class="fas fa-chart-pie"></i> Profile Completion
                </button>
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
    </div>

    <script>
        // Chart data and configuration
        const charts = [
            {
                title: "User Distribution",
                type: "doughnut",
                labels: ["Students", "System Users", "Admin Users", "Undefined"],
                data: [
                    <?= $stats['total_students'] ?>,
                    <?= $stats['total_users'] - $stats['total_students'] - $stats['total_admins'] ?>,
                    <?= $stats['total_admins'] ?>,
                    <?= $stats['total_users'] - ($stats['total_students'] + ($stats['total_users'] - $stats['total_students'] - $stats['total_admins']) + $stats['total_admins']) ?>
                ],
                backgroundColor: ['#357737', '#b69b04', '#17a2b8', '#6c757d'],
                tableData: [
                    { type: "Students", count: <?= $stats['total_students'] ?> },
                    { type: "System Users", count: <?= $stats['total_users'] - $stats['total_students'] - $stats['total_admins'] ?> },
                    { type: "Admin Users", count: <?= $stats['total_admins'] ?> },
                    { type: "Undefined", count: <?= $stats['total_users'] - ($stats['total_students'] + ($stats['total_users'] - $stats['total_students'] - $stats['total_admins']) + $stats['total_admins']) ?> }
                ],
                tableHeaders: ['User Type', 'Count']
            },
            {
                title: "Students by Course",
                type: "bar",
                labels: <?= json_encode(array_map(function($item) { 
                    return $item['course'] ? $item['course'] : 'Undefined'; 
                }, $courseStats)) ?>,
                data: <?= json_encode(array_column($courseStats, 'count')) ?>,
                backgroundColor: <?= json_encode(array_map(function($item) {
                    return $item['course'] ? '#357737' : '#6c757d';
                }, $courseStats)) ?>,
                borderColor: <?= json_encode(array_map(function($item) {
                    return $item['course'] ? '#205022' : '#5a6268';
                }, $courseStats)) ?>,
                borderWidth: 1,
                tableData: <?= json_encode(array_map(function($item) {
                    return [
                        'course' => $item['course'] ? $item['course'] : 'Undefined',
                        'count' => $item['count']
                    ];
                }, $courseStats)) ?>,
                tableHeaders: ['Course', 'Count']
            },
            {
                title: "Students by Year Level",
                type: "bar", 
                labels: <?= json_encode(array_map(function($item) { 
                    return $item['year_level'] ? $item['year_level'] : 'Undefined'; 
                }, $yearLevelStats)) ?>,
                data: <?= json_encode(array_column($yearLevelStats, 'count')) ?>,
                backgroundColor: <?= json_encode(array_map(function($item) {
                    return $item['year_level'] ? '#357737' : '#6c757d';
                }, $yearLevelStats)) ?>,
                borderColor: <?= json_encode(array_map(function($item) {
                    return $item['year_level'] ? '#205022' : '#5a6268';
                }, $yearLevelStats)) ?>,
                borderWidth: 1,
                tableData: <?= json_encode(array_map(function($item) {
                    return [
                        'year_level' => $item['year_level'] ? $item['year_level'] : 'Undefined',
                        'count' => $item['count']
                    ];
                }, $yearLevelStats)) ?>,
                tableHeaders: ['Year Level', 'Count']
            },
            {
                title: "ID Generation Status",
                type: "pie",
                labels: <?= json_encode(array_map(function($item) { 
                    return $item['status'] ? ucfirst($item['status']) : 'Undefined'; 
                }, $idGenStats)) ?>,
                data: <?= json_encode(array_column($idGenStats, 'count')) ?>,
                backgroundColor: ['#357737', '#b69b04', '#17a2b8', '#28a745', '#dc3545', '#6c757d'],
                tableData: <?= json_encode(array_map(function($item) {
                    return [
                        'status' => $item['status'] ? ucfirst($item['status']) : 'Undefined',
                        'count' => $item['count']
                    ];
                }, $idGenStats)) ?>,
                tableHeaders: ['Status', 'Count']
            },
            {
                title: "ID Request Status", 
                type: "pie",
                labels: <?= json_encode(array_map(function($item) { 
                    return $item['status'] ? ucfirst($item['status']) : 'Undefined'; 
                }, $idReqStats)) ?>,
                data: <?= json_encode(array_column($idReqStats, 'count')) ?>,
                backgroundColor: <?= json_encode(array_map(function($item) {
                    $status = $item['status'] ? strtolower($item['status']) : 'undefined';
                    switch($status) {
                        case 'rejected': return '#dc3545';
                        case 'undefined': return '#6c757d';
                        case 'pending': return '#b69b04';
                        case 'approved': return '#357737';
                        default: return '#17a2b8';
                    }
                }, $idReqStats)) ?>,
                tableData: <?= json_encode(array_map(function($item) {
                    return [
                        'status' => $item['status'] ? ucfirst($item['status']) : 'Undefined',
                        'count' => $item['count']
                    ];
                }, $idReqStats)) ?>,
                tableHeaders: ['Status', 'Count']
            },
            {
                title: "User Verification Status",
                type: "doughnut", 
                labels: <?= json_encode(array_map(function($item) { 
                    return $item['status'] ? $item['status'] : 'Undefined'; 
                }, $userVerStats)) ?>,
                data: <?= json_encode(array_column($userVerStats, 'count')) ?>,
                backgroundColor: <?= json_encode(array_map(function($item) {
                    $status = $item['status'] ? strtolower($item['status']) : 'undefined';
                    switch($status) {
                        case 'verified': return '#357737';
                        case 'not verified': return '#b69b04';
                        case 'undefined': return '#6c757d';
                        default: return '#17a2b8';
                    }
                }, $userVerStats)) ?>,
                tableData: <?= json_encode(array_map(function($item) {
                    return [
                        'status' => $item['status'] ? $item['status'] : 'Undefined',
                        'count' => $item['count']
                    ];
                }, $userVerStats)) ?>,
                tableHeaders: ['Status', 'Count']
            },
            {
                title: "User Account Status",
                type: "doughnut",
                labels: <?= json_encode(array_map(function($item) { 
                    return $item['status'] ? ucfirst($item['status']) : 'Undefined'; 
                }, $userStatusStats)) ?>,
                data: <?= json_encode(array_column($userStatusStats, 'count')) ?>,
                backgroundColor: <?= json_encode(array_map(function($item) {
                    $status = $item['status'] ? strtolower($item['status']) : 'undefined';
                    switch($status) {
                        case 'pending': return '#b69b04';
                        case 'approved': return '#357737';
                        case 'undefined': return '#6c757d';
                        case 'active': return '#28a745';
                        case 'inactive': return '#dc3545';
                        default: return '#17a2b8';
                    }
                }, $userStatusStats)) ?>,
                tableData: <?= json_encode(array_map(function($item) {
                    return [
                        'status' => $item['status'] ? ucfirst($item['status']) : 'Undefined',
                        'count' => $item['count']
                    ];
                }, $userStatusStats)) ?>,
                tableHeaders: ['Status', 'Count']
            },
            {
                title: "Profile Completion Status",
                type: "pie",
                labels: <?= json_encode(array_map(function($item) { 
                    return $item['status'] ? $item['status'] : 'Undefined'; 
                }, $profileStats)) ?>,
                data: <?= json_encode(array_column($profileStats, 'count')) ?>,
                backgroundColor: <?= json_encode(array_map(function($item) {
                    $status = $item['status'] ? strtolower($item['status']) : 'undefined';
                    switch($status) {
                        case 'complete': return '#357737';
                        case 'incomplete': return '#dc3545';
                        case 'undefined': return '#6c757d';
                        case 'pending': return '#b69b04';
                        default: return '#17a2b8';
                    }
                }, $profileStats)) ?>,
                tableData: <?= json_encode(array_map(function($item) {
                    return [
                        'status' => $item['status'] ? $item['status'] : 'Undefined',
                        'count' => $item['count']
                    ];
                }, $profileStats)) ?>,
                tableHeaders: ['Status', 'Count']
            }
        ];

        let currentChartIndex = 0;
        let currentChart = null;

        // Initialize first chart
        document.addEventListener('DOMContentLoaded', function() {
            showChart(0);
        });

        function showChart(index) {
            currentChartIndex = index;
            const chartConfig = charts[index];
            
            // Update UI
            document.getElementById('chartTitle').textContent = chartConfig.title;
            updateChartButtons();
            updateNavigationButtons();
            
            // Destroy existing chart
            if (currentChart) {
                currentChart.destroy();
            }
            
            // Create new chart
            const ctx = document.getElementById('dynamicChart').getContext('2d');
            const config = {
                type: chartConfig.type,
                data: {
                    labels: chartConfig.labels,
                    datasets: [{
                        label: chartConfig.title,
                        data: chartConfig.data,
                        backgroundColor: chartConfig.backgroundColor,
                        borderColor: chartConfig.borderColor || '#fff',
                        borderWidth: chartConfig.borderWidth || 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                boxWidth: 12,
                                font: { size: 12 }
                            }
                        },
                        title: {
                            display: false
                        }
                    },
                    scales: chartConfig.type === 'bar' ? {
                        y: {
                            beginAtZero: true,
                            ticks: { 
                                stepSize: 1,
                                font: { size: 11 }
                            }
                        },
                        x: {
                            ticks: {
                                font: { size: 11 }
                            }
                        }
                    } : {}
                }
            };
            
            currentChart = new Chart(ctx, config);
            
            // Update table content
            updateTableContent(chartConfig.tableData, chartConfig.tableHeaders);
        }

        function nextChart() {
            if (currentChartIndex < charts.length - 1) {
                showChart(currentChartIndex + 1);
            }
        }

        function prevChart() {
            if (currentChartIndex > 0) {
                showChart(currentChartIndex - 1);
            }
        }

        function updateChartButtons() {
            document.querySelectorAll('.chart-btn').forEach((btn, index) => {
                btn.classList.toggle('active', index === currentChartIndex);
            });
        }

        function updateNavigationButtons() {
            document.getElementById('prevBtn').disabled = currentChartIndex === 0;
            document.getElementById('nextBtn').disabled = currentChartIndex === charts.length - 1;
        }

        function toggleChartTable() {
            const mainChart = document.getElementById('mainChart');
            const tableView = document.getElementById('tableView');
            const toggleBtn = document.getElementById('toggleBtn');
            
            if (mainChart.style.display !== 'none') {
                mainChart.style.display = 'none';
                tableView.style.display = 'block';
                toggleBtn.innerHTML = '<i class="fas fa-chart-bar"></i> Show Chart';
            } else {
                mainChart.style.display = 'block';
                tableView.style.display = 'none';
                toggleBtn.innerHTML = '<i class="fas fa-table"></i> Show Table';
            }
        }

        function updateTableContent(data, headers) {
            let tableHTML = `
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>${headers[0]}</th>
                            <th style="text-align: right;">${headers[1]}</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            data.forEach(item => {
                const key = Object.keys(item).find(k => k !== 'count');
                const value = item[key] || 'Undefined';
                tableHTML += `
                    <tr>
                        <td>${value}</td>
                        <td style="text-align: right;">${item.count}</td>
                    </tr>
                `;
            });
            
            tableHTML += '</tbody></table>';
            document.getElementById('tableContent').innerHTML = tableHTML;
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

        // Refresh data
        function refreshData() {
            window.location.reload();
        }
    </script>
</body>
</html>