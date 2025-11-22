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
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
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
                            <span>Help</span>
                        </a>
                    </li>
                </ul>

                <!-- User Section -->
                <div class="navbar-nav ms-auto navbar-user-section">

                    <!-- Email + Switch Account Dropdown -->
                    <div class="nav-item dropdown">
                        <a class="nav-link school-nav-link user-email dropdown-toggle"
                            href="#"
                            id="userDropdown"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                            <i class="fas fa-envelope"></i>
                            <span><?= htmlspecialchars($_SESSION['email'] ?? 'Student') ?></span>
                        </a>

                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">

                            <li class="dropdown-header">
                                Switch Account
                            </li>

                            <!-- Student Account -->
                            <li>
                                <a class="dropdown-item" href="../student/student_home.php">
                                    <i class="fas fa-user-graduate"></i> Student Account
                                </a>
                            </li>

                            <!-- Admin Account -->
                            <li>
                                <a class="dropdown-item" href="../admin/admin_home.php">
                                    <i class="fas fa-user-shield"></i> Admin Account
                                </a>
                            </li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>

                            <!-- Settings -->
                            <li>
                                <a class="dropdown-item" href="edit_profile.php">
                                    <i class="fas fa-cog"></i> Settings
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Logout -->
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