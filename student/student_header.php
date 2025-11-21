<?php
// admin_header.php
// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    redirect('../index.php');
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
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body class="admin-body">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg school-navbar">
        <div class="container-fluid">
            <!-- Brand -->
            <a class="navbar-brand school-navbar-brand" href="student_home.php">
                <div class="brand-content">
                    <span class="brand-icon">🎓</span>
                    <div class="brand-text">
                        <div class="brand-title">KLD Portal</div>
                        <div class="brand-subtitle">Student Hub</div>
                    </div>
                </div>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNavbar" aria-controls="adminNavbar" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="adminNavbar">
                <!-- Main Navigation Links -->
                <ul class="navbar-nav ms-auto me-auto">
                    <li class="nav-item">
                        <a class="nav-link school-nav-link" href="student_home.php">
                            <i class="fas fa-home"></i>
                            <span>Home</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link school-nav-link" href="student_profile.php">
                            <i class="fas fa-user-circle"></i>
                            <span>Profile</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link school-nav-link" href="student_id.php">
                            <i class="fas fa-id-card"></i>
                            <span>My ID</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link school-nav-link" href="student_help.php">
                            <i class="fas fa-headset"></i>
                            <span>Support</span>
                        </a>
                    </li>
                </ul>
                
                <!-- User Section -->
                <div class="navbar-nav ms-auto navbar-user-section">
                    <div class="nav-item user-info">
                        <span class="nav-link school-nav-link user-email">
                            <i class="fas fa-envelope"></i>
                            <span><?= htmlspecialchars($_SESSION['email'] ?? 'Student') ?></span>
                        </span>
                    </div>
                    <div class="nav-item">
                        <a class="nav-link school-nav-link logout-link" href="../includes/logout.php">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">