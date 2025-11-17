<?php
// admin_header.php
// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../includes/login.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard | School Portal</title>
    <!-- Local Bootstrap CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg school-navbar">
        <div class="container">
            <a class="navbar-brand" href="admin_dashboard.php">
                <i class="fas fa-school me-2"></i>School Portal - Admin
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="adminNavbar">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link school-nav-link" href="admin_dashboard.php">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link school-nav-link" href="admin_students.php">
                            <i class="fas fa-users me-1"></i>Students
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link school-nav-link" href="admin_id.php">
                            <i class="fas fa-id-card me-1"></i>ID Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link school-nav-link" href="admin_user.php">
                            <i class="fas fa-user-cog me-1"></i>User Management
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link school-nav-link" href="admin_reports.php">
                            <i class="fas fa-chart-bar me-1"></i>Reports
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link school-nav-link" href="admin_logs.php">
                            <i class="fas fa-clipboard-list me-1"></i>System Logs
                        </a>
                    </li>
                </ul>
                
                <div class="navbar-nav">
                    <span class="nav-link school-nav-link">
                        <i class="fas fa-user me-1"></i><?= htmlspecialchars($_SESSION['email'] ?? 'Admin') ?>
                    </span>
                    <a class="nav-link school-nav-link" href="../includes/logout.php">
                        <i class="fas fa-sign-out-alt me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">