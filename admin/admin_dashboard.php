<?php
session_start();
require_once 'classes/ReportsManager.php';

// Check admin access
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$reportsManager = new ReportsManager();

// Get dashboard statistics
$stats = $reportsManager->getOverallStats();
$courseStats = $reportsManager->getStudentsByCourse();
$idGenStats = $reportsManager->getIdGenerationStats();
$recentActivities = $reportsManager->getRecentActivities(5);

require_once 'admin_header.php';
?>

                <!-- Dashboard Header -->
                <div class="page-header">
                    <h2>Welcome back, <?= htmlspecialchars($_SESSION['email'] ?? 'Admin') ?>!</h2>
                    <p>Here's an overview of your system statistics</p>
                </div>

                <!-- Key Statistics Cards -->
                <div class="stats-dashboard">
                    <!-- Students Card -->
                    <div class="stat-card">
                        <div class="stat-number"><?= number_format($stats['total_students']) ?></div>
                        <div class="stat-label">
                            <i class="fas fa-users"></i> Total Students
                        </div>
                        <div class="stat-footer">
                            <a href="admin_students.php" class="btn-admin btn-secondary btn-small">
                                View all <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Users Card -->
                    <div class="stat-card">
                        <div class="stat-number"><?= number_format($stats['total_users']) ?></div>
                        <div class="stat-label">
                            <i class="fas fa-user-tie"></i> Total Users
                        </div>
                        <div class="stat-footer">
                            <a href="admin_user.php" class="btn-admin btn-secondary btn-small">
                                Manage users <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                    <!-- IDs Generated Card -->
                    <div class="stat-card">
                        <div class="stat-number"><?= number_format($stats['total_ids_generated']) ?></div>
                        <div class="stat-label">
                            <i class="fas fa-id-card"></i> IDs Generated
                        </div>
                        <div class="stat-footer">
                            <a href="admin_id.php" class="btn-admin btn-secondary btn-small">
                                View IDs <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>

                    <!-- Pending Requests Card -->
                    <div class="stat-card">
                        <div class="stat-number text-warning"><?= number_format($stats['pending_id_requests']) ?></div>
                        <div class="stat-label">
                            <i class="fas fa-file-invoice"></i> Pending Requests
                        </div>
                        <div class="stat-footer">
                            <a href="admin_id.php" class="btn-admin btn-secondary btn-small">
                                Review requests <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Quick Overview Section -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <span><i class="fas fa-chart-pie"></i> System Overview</span>
                    </div>
                    <div class="admin-card-body">
                        <div class="filter-form">
                            <!-- ID Generation Overview -->
                            <div class="action-section">
                                <h3><i class="fas fa-chart-pie"></i> ID Generation Status</h3>
                                <div class="table-responsive">
                                    <table class="admin-table">
                                        <thead>
                                            <tr>
                                                <th>Status</th>
                                                <th class="text-end">Count</th>
                                                <th class="text-end">Percentage</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $totalIds = array_sum(array_column($idGenStats, 'count'));
                                            foreach ($idGenStats as $status): 
                                                $percentage = $totalIds > 0 ? ($status['count'] / $totalIds * 100) : 0;
                                                $badgeClass = match($status['status']) {
                                                    'pending' => 'status-pending',
                                                    'generated' => 'status-generated',
                                                    'printed' => 'status-completed',
                                                    'delivered' => 'status-verified',
                                                    default => 'status-inactive'
                                                };
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="status-badge <?= $badgeClass ?>">
                                                        <?= ucfirst($status['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <strong><?= number_format($status['count']) ?></strong>
                                                </td>
                                                <td class="text-end">
                                                    <?= round($percentage, 1) ?>%
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Top Courses -->
                            <div class="action-section">
                                <h3><i class="fas fa-book"></i> Top Courses by Enrollment</h3>
                                <div class="table-responsive">
                                    <table class="admin-table">
                                        <thead>
                                            <tr>
                                                <th>Course</th>
                                                <th class="text-end">Students</th>
                                                <th class="text-end">Progress</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $maxStudents = !empty($courseStats) ? max(array_column($courseStats, 'count')) : 0;
                                            $topCourses = array_slice($courseStats, 0, 5);
                                            foreach ($topCourses as $course): 
                                                $percentage = $maxStudents > 0 ? ($course['count'] / $maxStudents * 100) : 0;
                                            ?>
                                            <tr>
                                                <td><?= htmlspecialchars($course['course'] ?? 'N/A') ?></td>
                                                <td class="text-end">
                                                    <span class="status-badge status-active"><?= number_format($course['count']) ?></span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="progress-bar-container" style="width: 100px; display: inline-block;">
                                                        <div class="progress-bar" style="width: <?= $percentage ?>%; height: 8px; background: linear-gradient(135deg, var(--school-green) 0%, var(--school-green-dark) 100%); border-radius: 4px;"></div>
                                                    </div>
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

                <!-- Recent Activities and Quick Actions -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <span><i class="fas fa-history"></i> Recent Activities & Quick Actions</span>
                    </div>
                    <div class="admin-card-body">
                        <div class="filter-form">
                            <!-- Recent Activities -->
                            <div class="action-section">
                                <div class="header-actions">
                                    <h3><i class="fas fa-history"></i> Recent Activities</h3>
                                    <div class="action-buttons">
                                        <a href="admin_logs.php" class="btn-admin btn-view">
                                            View All Logs
                                        </a>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="admin-table">
                                        <thead>
                                            <tr>
                                                <th>Action</th>
                                                <th>Table</th>
                                                <th>Admin</th>
                                                <th>Time</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($recentActivities)): ?>
                                                <?php foreach ($recentActivities as $activity): ?>
                                                <tr>
                                                    <td>
                                                        <?php 
                                                        $actionBadge = match($activity['action']) {
                                                            'insert' => 'status-approved',
                                                            'update' => 'status-generated',
                                                            'delete' => 'status-rejected',
                                                            default => 'status-inactive'
                                                        };
                                                        ?>
                                                        <span class="status-badge <?= $actionBadge ?>">
                                                            <?= ucfirst($activity['action']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <code><?= htmlspecialchars($activity['table_name']) ?></code>
                                                    </td>
                                                    <td><?= htmlspecialchars($activity['admin_name'] ?? 'System') ?></td>
                                                    <td>
                                                        <?php 
                                                        $time = strtotime($activity['created_at']);
                                                        $now = time();
                                                        $diff = $now - $time;
                                                        
                                                        if ($diff < 60) {
                                                            echo "Just now";
                                                        } elseif ($diff < 3600) {
                                                            echo floor($diff / 60) . " min ago";
                                                        } elseif ($diff < 86400) {
                                                            echo floor($diff / 3600) . " hrs ago";
                                                        } else {
                                                            echo floor($diff / 86400) . " days ago";
                                                        }
                                                        ?>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="empty-state">
                                                        <i class="fas fa-inbox"></i>
                                                        <h4>No recent activities</h4>
                                                        <p>System activities will appear here</p>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Quick Actions -->
                            <div class="action-section">
                                <h3><i class="fas fa-bolt"></i> Quick Actions</h3>
                                <div class="action-buttons" style="display: grid; gap: 10px;">
                                    <a href="admin_students.php" class="btn-admin btn-primary">
                                        <i class="fas fa-user-plus"></i> Manage Students
                                    </a>
                                    <a href="admin_user.php" class="btn-admin btn-primary">
                                        <i class="fas fa-users-cog"></i> Manage Users
                                    </a>
                                    <a href="admin_id.php" class="btn-admin btn-primary">
                                        <i class="fas fa-id-card-alt"></i> ID Management
                                    </a>
                                    <a href="admin_reports.php" class="btn-admin btn-primary">
                                        <i class="fas fa-chart-bar"></i> View Reports
                                    </a>
                                    <a href="admin_logs.php" class="btn-admin btn-primary">
                                        <i class="fas fa-clipboard-list"></i> View Logs
                                    </a>
                                </div>

                                <div class="alert-banner alert-info" style="margin-top: 20px;">
                                    <i class="fas fa-info-circle"></i>
                                    <div>
                                        <strong>Pro Tip:</strong> Regularly review pending ID requests to keep operations running smoothly.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </main>
    </div>

    <!-- Custom Dashboard Styles -->
    <style>
        .stat-card {
            position: relative;
            overflow: hidden;
        }

        .stat-card .stat-number.text-warning {
            color: var(--school-yellow) !important;
        }

        .stat-footer {
            margin-top: 15px;
            padding-top: 15px;
            border-top: 1px solid var(--school-gray);
        }

        .stat-footer .btn-admin {
            width: 100%;
            justify-content: center;
        }

        .progress-bar-container {
            background-color: var(--school-gray);
            border-radius: 4px;
            overflow: hidden;
        }

        .alert-info {
            background: linear-gradient(135deg, #E3F2FD 0%, #BBDEFB 100%);
            border-left: 5px solid #2196F3;
            color: #1565C0;
        }

        /* Ensure proper spacing for the new layout */
        .admin-card-body .filter-form {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 25px;
        }

        @media (max-width: 1024px) {
            .admin-card-body .filter-form {
                grid-template-columns: 1fr;
                gap: 20px;
            }
        }
    </style>

    <!-- Scripts -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    <script>
        // Mobile menu toggle
        document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
            document.getElementById('adminSidebar').classList.toggle('mobile-open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        });

        // Close sidebar when clicking overlay
        document.getElementById('sidebarOverlay')?.addEventListener('click', function() {
            document.getElementById('adminSidebar').classList.remove('mobile-open');
            this.classList.remove('active');
        });

        // Update page title
        document.getElementById('pageTitle').textContent = 'Admin Dashboard';

        // Refresh stats every 60 seconds
        setInterval(function() {
            // Optional: Add auto-refresh functionality here
            console.log('Dashboard stats are up to date');
        }, 60000);

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