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
                <div class="mb-4">
                    <h2 class="mb-2">Welcome back, <?= htmlspecialchars($_SESSION['email'] ?? 'Admin') ?>!</h2>
                    <p class="text-muted">Here's an overview of your system statistics</p>
                </div>

                <!-- Key Statistics Cards -->
                <div class="row mb-4">
                    <!-- Students Card -->
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stat-card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="text-muted small mb-1">
                                            <i class="fas fa-users"></i> Total Students
                                        </p>
                                        <h3 class="mb-0 fw-bold"><?= number_format($stats['total_students']) ?></h3>
                                    </div>
                                    <div class="stat-icon bg-primary bg-opacity-10 rounded-3">
                                        <i class="fas fa-users fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-top border-opacity-10">
                                <a href="admin_students.php" class="text-decoration-none small text-primary">
                                    View all students <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Users Card -->
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stat-card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="text-muted small mb-1">
                                            <i class="fas fa-user-tie"></i> Total Users
                                        </p>
                                        <h3 class="mb-0 fw-bold"><?= number_format($stats['total_users']) ?></h3>
                                    </div>
                                    <div class="stat-icon bg-info bg-opacity-10 rounded-3">
                                        <i class="fas fa-user-tie fa-2x text-info"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-top border-opacity-10">
                                <a href="admin_user.php" class="text-decoration-none small text-info">
                                    Manage users <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- IDs Generated Card -->
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stat-card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="text-muted small mb-1">
                                            <i class="fas fa-id-card"></i> IDs Generated
                                        </p>
                                        <h3 class="mb-0 fw-bold"><?= number_format($stats['total_ids_generated']) ?></h3>
                                    </div>
                                    <div class="stat-icon bg-success bg-opacity-10 rounded-3">
                                        <i class="fas fa-id-card fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-top border-opacity-10">
                                <a href="admin_id.php" class="text-decoration-none small text-success">
                                    View IDs <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Requests Card -->
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="card stat-card border-0 shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <p class="text-muted small mb-1">
                                            <i class="fas fa-file-invoice"></i> Pending Requests
                                        </p>
                                        <h3 class="mb-0 fw-bold text-warning"><?= number_format($stats['pending_id_requests']) ?></h3>
                                    </div>
                                    <div class="stat-icon bg-warning bg-opacity-10 rounded-3">
                                        <i class="fas fa-file-invoice fa-2x text-warning"></i>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-top border-opacity-10">
                                <a href="admin_id.php" class="text-decoration-none small text-warning">
                                    Review requests <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Overview Section -->
                <div class="row mb-4">
                    <!-- ID Generation Overview -->
                    <div class="col-lg-6 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light border-0 py-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-chart-pie text-primary"></i> ID Generation Status
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-light">
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
                                                    'pending' => 'bg-warning',
                                                    'generated' => 'bg-info',
                                                    'printed' => 'bg-primary',
                                                    'delivered' => 'bg-success',
                                                    default => 'bg-secondary'
                                                };
                                            ?>
                                            <tr>
                                                <td>
                                                    <span class="badge <?= $badgeClass ?>">
                                                        <?= ucfirst($status['status']) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    <strong><?= number_format($status['count']) ?></strong>
                                                </td>
                                                <td class="text-end text-muted">
                                                    <?= round($percentage, 1) ?>%
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Top Courses -->
                    <div class="col-lg-6 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light border-0 py-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-book text-info"></i> Top Courses by Enrollment
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm table-hover mb-0">
                                        <thead class="table-light">
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
                                                    <span class="badge bg-primary"><?= number_format($course['count']) ?></span>
                                                </td>
                                                <td class="text-end">
                                                    <div class="progress" style="height: 20px; width: 100px;">
                                                        <div class="progress-bar" style="width: <?= $percentage ?>%"></div>
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
                <div class="row">
                    <!-- Recent Activities -->
                    <div class="col-lg-8 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light border-0 py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <h5 class="mb-0">
                                        <i class="fas fa-history text-success"></i> Recent Activities
                                    </h5>
                                    <a href="admin_logs.php" class="btn btn-sm btn-outline-primary">
                                        View All Logs
                                    </a>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0">
                                        <thead class="table-light">
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
                                                            'insert' => 'bg-success',
                                                            'update' => 'bg-info',
                                                            'delete' => 'bg-danger',
                                                            default => 'bg-secondary'
                                                        };
                                                        ?>
                                                        <span class="badge <?= $actionBadge ?>">
                                                            <?= ucfirst($activity['action']) ?>
                                                        </span>
                                                    </td>
                                                    <td>
                                                        <code class="text-muted small">
                                                            <?= htmlspecialchars($activity['table_name']) ?>
                                                        </code>
                                                    </td>
                                                    <td><?= htmlspecialchars($activity['admin_name'] ?? 'System') ?></td>
                                                    <td>
                                                        <small class="text-muted">
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
                                                        </small>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted py-4">
                                                        No recent activities
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="col-lg-4 mb-3">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-light border-0 py-3">
                                <h5 class="mb-0">
                                    <i class="fas fa-bolt text-warning"></i> Quick Actions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="admin_students.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-user-plus"></i> Manage Students
                                    </a>
                                    <a href="admin_user.php" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-users-cog"></i> Manage Users
                                    </a>
                                    <a href="admin_id.php" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-id-card-alt"></i> ID Management
                                    </a>
                                    <a href="admin_reports.php" class="btn btn-outline-warning btn-sm">
                                        <i class="fas fa-chart-bar"></i> View Reports
                                    </a>
                                    <a href="admin_logs.php" class="btn btn-outline-secondary btn-sm">
                                        <i class="fas fa-clipboard-list"></i> View Logs
                                    </a>
                                </div>

                                <hr class="my-3">

                                <div class="alert alert-info alert-sm" role="alert">
                                    <small>
                                        <i class="fas fa-info-circle"></i> 
                                        <strong>Pro Tip:</strong> Regularly review pending ID requests to keep operations running smoothly.
                                    </small>
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
            transition: all 0.3s ease;
            border-radius: 12px;
        }

        .stat-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12) !important;
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 10px;
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 123, 255, 0.05);
        }

        .alert-sm {
            padding: 0.75rem;
            margin-bottom: 0;
        }

        .progress {
            background-color: #e9ecef;
            border-radius: 4px;
        }

        .card {
            border-radius: 12px;
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