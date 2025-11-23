<?php
// student_header.php
// Check if user is student
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'student') {
    redirect('../index.php');
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Portal | KLD</title>
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Local Bootstrap CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        :root {
            --primary-dark: #1b5e20;
            --primary-medium: #2e7d32;
            --primary-light: #4caf50;
            --accent-orange: #ff9800;
            --accent-orange-dark: #f57c00;
            --sidebar-width: 280px;
            --header-height: 75px;
            --transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.12);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.15);
        }
        
        * {
            box-sizing: border-box;
        }
        
        .admin-body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%);
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
        }
        
        /* Modern Sidebar Navigation */
        .admin-wrapper {
            display: flex;
            min-height: 100vh;
        }
        
        .admin-sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-dark) 0%, #0d3817 100%);
            color: white;
            position: fixed;
            height: 100vh;
            left: 0;
            top: 0;
            z-index: 1000;
            transition: var(--transition);
            box-shadow: 8px 0 32px rgba(0, 0, 0, 0.2);
            overflow-y: auto;
            overflow-x: hidden;
        }
        
        .admin-sidebar::-webkit-scrollbar {
            width: 8px;
        }
        
        .admin-sidebar::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.05);
        }
        
        .admin-sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.25);
            border-radius: 4px;
        }
        
        .admin-sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.4);
        }
        
        .sidebar-header {
            padding: 35px 20px;
            border-bottom: 2px solid rgba(255, 255, 255, 0.12);
            background: linear-gradient(135deg, rgba(0, 0, 0, 0.1) 0%, rgba(0, 0, 0, 0.05) 100%);
            text-align: center;
            position: relative;
        }
        
        .sidebar-header::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 20%;
            right: 20%;
            height: 2px;
            background: linear-gradient(90deg, transparent 0%, var(--accent-orange) 50%, transparent 100%);
        }
        
        .sidebar-brand {
            color: white;
            text-decoration: none;
            font-weight: 800;
            font-size: 1.4rem;
            display: flex;
            align-items: center;
            gap: 14px;
            justify-content: center;
            letter-spacing: 0.6px;
            transition: var(--transition);
        }
        
        .sidebar-brand:hover {
            transform: scale(1.02);
        }
        
        .sidebar-brand i {
            color: var(--accent-orange);
            font-size: 1.8rem;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
            transition: var(--transition);
        }
        
        .sidebar-brand:hover i {
            transform: rotate(5deg) scale(1.1);
        }
        
        .sidebar-nav {
            padding: 25px 0;
        }
        
        .nav-item {
            margin-bottom: 10px;
            padding: 0 12px;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.85);
            padding: 13px 18px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 15px;
            transition: var(--transition);
            border-left: 5px solid transparent;
            border-radius: 8px;
            font-weight: 550;
            position: relative;
            overflow: hidden;
        }
        
        .nav-link::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.08);
            transform: translateX(-100%);
            transition: var(--transition);
            z-index: -1;
        }
        
        .nav-link i {
            width: 24px;
            text-align: center;
            font-size: 1.2rem;
            transition: var(--transition);
            flex-shrink: 0;
        }
        
        .nav-link span {
            transition: var(--transition);
            flex: 1;
        }
        
        .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.12);
            border-left-color: var(--accent-orange);
            padding-left: 22px;
            transform: translateX(3px);
        }
        
        .nav-link:hover::before {
            transform: translateX(0);
        }
        
        .nav-link:hover i {
            transform: scale(1.2) rotate(-5deg);
            color: var(--accent-orange);
        }
        
        .nav-link.active {
            color: white;
            background: linear-gradient(90deg, rgba(255, 152, 0, 0.25) 0%, rgba(255, 255, 255, 0.1) 100%);
            border-left-color: var(--accent-orange);
            padding-left: 22px;
            box-shadow: inset -4px 0 12px rgba(255, 152, 0, 0.15);
            font-weight: 700;
        }
        
        .nav-link.active i {
            color: var(--accent-orange);
            transform: scale(1.25);
        }

        .nav-link[href*="logout"] {
            color: rgba(255, 100, 100, 0.95);
            margin-top: 20px;
            border-top: 2px solid rgba(255, 255, 255, 0.1);
            padding-top: 20px;
        }

        .nav-link[href*="logout"]:hover {
            color: #ff6b6b;
            background: rgba(255, 100, 100, 0.15);
            border-left-color: #ff6b6b;
        }

        .nav-link[href*="logout"] i {
            color: #ff6b6b;
        }

        .nav-link[href*="logout"]:hover i {
            color: #ff5252;
        }
        
        .sidebar-footer {
            padding: 20px 0;
            border-top: 2px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
            background: rgba(0, 0, 0, 0.1);
        }
        
        /* Main Content Area */
        .admin-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        .admin-header {
            background: white;
            height: var(--header-height);
            border-bottom: 2px solid #e8eef5;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 40px;
            box-shadow: var(--shadow-md);
            position: sticky;
            top: 0;
            z-index: 999;
            backdrop-filter: blur(10px);
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .header-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0;
            letter-spacing: 0.3px;
            transition: var(--transition);
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 20px;
            background: linear-gradient(135deg, #f0f8f5 0%, #e8f5e9 100%);
            border-radius: 25px;
            font-weight: 600;
            color: var(--primary-dark);
            border: 2px solid rgba(27, 94, 32, 0.15);
            transition: var(--transition);
            max-width: 250px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .user-info:hover {
            border-color: rgba(27, 94, 32, 0.3);
            box-shadow: var(--shadow-sm);
        }
        
        .user-info i {
            color: var(--accent-orange);
            font-size: 1.3rem;
            flex-shrink: 0;
        }
        
        .logout-btn {
            background: linear-gradient(135deg, var(--accent-orange) 0%, var(--accent-orange-dark) 100%);
            color: white;
            border: none;
            padding: 11px 26px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            transition: var(--transition);
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(255, 152, 0, 0.3);
            font-size: 0.95rem;
        }
        
        .logout-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(255, 152, 0, 0.4);
            color: white;
        }
        
        .logout-btn:active {
            transform: translateY(-1px);
        }
        
        /* Content Container */
        .admin-content {
            padding: 40px;
            background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%);
            flex: 1;
            overflow-y: auto;
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .admin-sidebar {
                width: 290px;
                transform: translateX(-100%);
                box-shadow: 8px 0 32px rgba(0, 0, 0, 0.25);
            }
            
            .admin-sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .mobile-menu-btn {
                display: flex;
                align-items: center;
                justify-content: center;
                background: none;
                border: 2px solid var(--primary-dark);
                border-radius: 8px;
                font-size: 1.3rem;
                color: var(--primary-dark);
                cursor: pointer;
                transition: var(--transition);
                width: 45px;
                height: 45px;
            }
            
            .mobile-menu-btn:hover {
                color: var(--accent-orange);
                border-color: var(--accent-orange);
                background: rgba(255, 152, 0, 0.08);
                transform: scale(1.05);
            }
            
            .admin-header {
                padding: 0 20px;
                height: 70px;
            }
            
            .header-title {
                font-size: 1.4rem;
            }
            
            .admin-content {
                padding: 25px;
            }
            
            .user-menu {
                gap: 12px;
            }
            
            .user-info {
                padding: 8px 16px;
                font-size: 0.9rem;
                max-width: 180px;
            }
            
            .logout-btn {
                padding: 10px 20px;
                font-size: 0.85rem;
            }
        }
        
        @media (max-width: 576px) {
            .sidebar-brand span {
                display: none;
            }
            
            .sidebar-brand {
                font-size: 1.1rem;
            }
            
            .user-info span {
                display: none;
            }
            
            .logout-btn span {
                display: none;
            }
            
            .admin-header {
                padding: 0 15px;
            }
            
            .header-left {
                gap: 12px;
            }
            
            .header-title {
                font-size: 1.2rem;
            }
        }
        
        /* Desktop only */
        @media (min-width: 769px) {
            .mobile-menu-btn {
                display: none !important;
            }
        }
        
        /* Overlay for mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            z-index: 999;
            display: none;
            transition: opacity 0.3s ease;
            opacity: 0;
            backdrop-filter: blur(3px);
        }
        
        .sidebar-overlay.active {
            display: block;
            opacity: 1;
        }
    </style>
</head>

<body class="admin-body">
    <!-- Mobile Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    
    <div class="admin-wrapper">
        <!-- Sidebar Navigation -->
        <aside class="admin-sidebar" id="adminSidebar">
            <div class="sidebar-header">
                <a href="student_home.php" class="sidebar-brand">
                    <i class="fas fa-graduation-cap"></i>
                    <span>Student Portal</span>
                </a>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'student_home.php' ? 'active' : '' ?>" href="student_home.php">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'student_profile.php' ? 'active' : '' ?>" href="student_profile.php">
                        <i class="fas fa-user-circle"></i>
                        <span>Profile</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'student_id.php' ? 'active' : '' ?>" href="student_id.php">
                        <i class="fas fa-id-card"></i>
                        <span>My ID</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'student_help.php' ? 'active' : '' ?>" href="student_help.php">
                        <i class="fas fa-question-circle"></i>
                        <span>Help</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link" href="../includes/logout.php">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content Area -->
        <main class="admin-main">
            <!-- Top Header -->
            <header class="admin-header">
                <div class="header-left">
                    <button class="mobile-menu-btn" id="mobileMenuBtn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="header-title" id="pageTitle">Student Portal</h1>
                </div>
                
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-envelope"></i>
                        <span><?= htmlspecialchars($_SESSION['email'] ?? 'Student') ?></span>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="admin-content">
            </div>
        </main>
    </div>
    
    <script>
        // Mobile menu toggle
        const mobileMenuBtn = document.getElementById('mobileMenuBtn');
        const adminSidebar = document.getElementById('adminSidebar');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        if (mobileMenuBtn) {
            mobileMenuBtn.addEventListener('click', function() {
                adminSidebar.classList.toggle('mobile-open');
                sidebarOverlay.classList.toggle('active');
            });
        }
        
        if (sidebarOverlay) {
            sidebarOverlay.addEventListener('click', function() {
                adminSidebar.classList.remove('mobile-open');
                sidebarOverlay.classList.remove('active');
            });
        }
        
        // Close sidebar when a nav link is clicked on mobile
        const navLinks = document.querySelectorAll('.nav-link');
        navLinks.forEach(link => {
            link.addEventListener('click', function() {
                if (window.innerWidth <= 768) {
                    adminSidebar.classList.remove('mobile-open');
                    sidebarOverlay.classList.remove('active');
                }
            });
        });
        
        // Update page title based on current page
        function updatePageTitle() {
            const activeLink = document.querySelector('.nav-link.active');
            if (activeLink) {
                const titleText = activeLink.querySelector('span').textContent;
                document.getElementById('pageTitle').textContent = titleText;
            }
        }
        
        updatePageTitle();
    </script>
</body>

</html>