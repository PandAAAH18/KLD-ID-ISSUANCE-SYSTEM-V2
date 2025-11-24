<?php
// admin_header.php
// Check if user is admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    redirect('../index.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard | School Portal</title>
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
            --accent-yellow: #ffd600;
            --accent-yellow-light: #ffecb3;
            --sidebar-width: 260px;
            --sidebar-collapsed-width: 70px;
            --header-height: 70px;
            --transition-speed: 0.3s;
        }
        
        .admin-body {
            background: #f8f9fa;
            min-height: 100vh;
            overflow-x: hidden;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            transition: all var(--transition-speed) ease;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            overflow-x: hidden;
            transform: translateX(0);
        }
        
        .admin-sidebar.closed {
            transform: translateX(-100%);
        }
        
        /* Collapsed State */
        .admin-sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }
        
        .admin-sidebar.collapsed.closed {
            transform: translateX(-100%);
        }
        
        .sidebar-close-btn {
            display: none;
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: white;
            font-size: 1.3rem;
            cursor: pointer;
            padding: 8px 12px;
            border-radius: 4px;
            transition: all var(--transition-speed) ease;
            flex-shrink: 0;
        }
        
        .sidebar-close-btn:hover {
            background: rgba(255, 255, 255, 0.2);
        }
        
        .admin-sidebar.collapsed .sidebar-brand span,
        .admin-sidebar.collapsed .nav-item span {
            display: none;
            opacity: 0;
        }
        
        .admin-sidebar.collapsed .nav-link {
            padding: 14px 20px;
            justify-content: center;
        }
        
        .admin-sidebar.collapsed .nav-link i {
            margin-right: 0;
            font-size: 1.3rem;
        }
        
        .admin-sidebar.collapsed .sidebar-toggle {
            transform: rotate(180deg);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(0, 0, 0, 0.1);
            position: sticky;
            top: 0;
            z-index: 1;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        
        .sidebar-brand {
            color: white;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.3rem;
            display: flex;
            align-items: center;
            gap: 12px;
            white-space: nowrap;
            overflow: hidden;
            transition: all var(--transition-speed) ease;
        }
        
        .sidebar-brand i {
            color: var(--accent-yellow);
            font-size: 1.5rem;
            flex-shrink: 0;
        }
        
        .sidebar-toggle {
            background: none;
            border: none;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            padding: 5px;
            border-radius: 4px;
            transition: all var(--transition-speed) ease;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        
        .sidebar-toggle:hover {
            background: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-item {
            margin-bottom: 5px;
            position: relative;
        }
        
        .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 14px 25px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 12px;
            transition: all var(--transition-speed) ease;
            border-left: 4px solid transparent;
            font-weight: 500;
            white-space: nowrap;
        }
        
        .nav-link:hover {
            color: white;
            background: rgba(255, 255, 255, 0.1);
            border-left-color: var(--accent-yellow);
        }
        
        .nav-link.active {
            color: white;
            background: rgba(255, 255, 255, 0.15);
            border-left-color: var(--accent-yellow);
        }
        
        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
            flex-shrink: 0;
            transition: all var(--transition-speed) ease;
        }
        
        /* Main Content Area */
        .admin-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            transition: margin-left var(--transition-speed) ease;
        }
        
        .admin-main.sidebar-collapsed {
            margin-left: var(--sidebar-collapsed-width);
        }
        
        .admin-header {
            background: white;
            height: var(--header-height);
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 999;
        }
        
        .header-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }
        
        .header-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: var(--primary-dark);
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .breadcrumb {
            display: flex;
            align-items: center;
            margin: 0;
            padding: 0;
            list-style: none;
            font-size: 0.9rem;
            color: #6c757d;
        }
        
        .breadcrumb-item + .breadcrumb-item::before {
            content: ">";
            padding: 0 8px;
        }
        
        .breadcrumb-item.active {
            color: var(--primary-medium);
            font-weight: 500;
        }
        
        .user-menu {
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px 16px;
            background: #f8f9fa;
            border-radius: 25px;
            font-weight: 500;
            color: var(--primary-dark);
            cursor: pointer;
            transition: all var(--transition-speed) ease;
            border: 1px solid transparent;
            min-width: 0;
        }
        
        .user-info:hover {
            background: #e9ecef;
            border-color: #dee2e6;
        }
        
        .user-info i {
            color: var(--primary-medium);
            flex-shrink: 0;
        }
        
        .user-name {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 120px;
        }
        
        /* Dropdown Menu */
        .user-dropdown {
            position: absolute;
            top: 100%;
            right: 0;
            background: white;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
            min-width: 200px;
            padding: 8px 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all var(--transition-speed) ease;
            z-index: 2000;
            border: 1px solid #e0e0e0;
            pointer-events: none;
        }
        
        .user-dropdown.active {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
            pointer-events: auto;
        }
        
        .dropdown-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px 20px;
            color: #333;
            text-decoration: none;
            transition: all var(--transition-speed) ease;
            border: none;
            background: none;
            width: 100%;
            text-align: left;
            font-size: 0.9rem;
            cursor: pointer;
        }
        
        .dropdown-item:hover {
            background: #f8f9fa;
            color: var(--primary-dark);
        }
        
        .dropdown-item i {
            width: 16px;
            color: var(--primary-medium);
            flex-shrink: 0;
        }
        
        .dropdown-divider {
            height: 1px;
            background: #e0e0e0;
            margin: 8px 0;
        }
        
        .logout-item {
            color: #dc3545 !important;
        }
        
        .logout-item:hover {
            background: #fff5f5 !important;
            color: #dc3545 !important;
        }
        
        /* Content Container */
        .admin-content {
            padding: 30px;
            background: #f8f9fa;
            min-height: calc(100vh - var(--header-height));
        }
        
        /* Mobile Menu Button */
        .mobile-menu-btn {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            color: var(--primary-dark);
            cursor: pointer;
            padding: 8px;
            border-radius: 6px;
            transition: background-color var(--transition-speed) ease;
        }
        
        .mobile-menu-btn:hover {
            background: #f8f9fa;
        }
        
        /* Tooltip for collapsed sidebar */
        .nav-item .tooltip-text {
            position: absolute;
            left: 100%;
            top: 50%;
            transform: translateY(-50%);
            background: #333;
            color: white;
            padding: 8px 12px;
            border-radius: 4px;
            font-size: 0.8rem;
            white-space: nowrap;
            opacity: 0;
            visibility: hidden;
            transition: opacity var(--transition-speed);
            z-index: 1001;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            pointer-events: none;
        }
        
        .nav-item .tooltip-text::before {
            content: '';
            position: absolute;
            right: 100%;
            top: 50%;
            transform: translateY(-50%);
            border-width: 5px;
            border-style: solid;
            border-color: transparent #333 transparent transparent;
        }
        
        .admin-sidebar.collapsed .nav-item:hover .tooltip-text {
            opacity: 1;
            visibility: visible;
        }
        
        /* Mobile Responsive */
        @media (max-width: 1024px) {
            .admin-sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .admin-sidebar.closed {
                transform: translateX(-100%);
            }
            
            .admin-sidebar.mobile-open {
                transform: translateX(0);
            }
            
            .admin-sidebar.mobile-open.closed {
                transform: translateX(0);
            }
            
            .sidebar-close-btn {
                display: flex;
                align-items: center;
                justify-content: center;
            }
            
            .admin-sidebar.collapsed {
                transform: translateX(-100%);
                width: 280px;
            }
            
            .admin-sidebar.mobile-open.collapsed {
                transform: translateX(0);
                width: 280px;
            }
            
            .admin-sidebar.mobile-open.collapsed .sidebar-brand span,
            .admin-sidebar.mobile-open.collapsed .nav-item span {
                display: flex;
                opacity: 1;
            }
            
            .admin-sidebar.mobile-open.collapsed .nav-link {
                padding: 14px 25px;
                justify-content: flex-start;
            }
            
            .admin-sidebar.mobile-open.collapsed .nav-link i {
                margin-right: 12px;
                font-size: 1.1rem;
            }
            
            .admin-main {
                margin-left: 0;
            }
            
            .admin-main.sidebar-collapsed {
                margin-left: 0;
            }
            
            .mobile-menu-btn {
                display: block;
            }
            
            .admin-header {
                padding: 0 20px;
                gap: 15px;
            }
            
            .header-left {
                gap: 12px;
            }
            
            .header-title {
                font-size: 1.3rem;
            }
            
            .user-menu {
                gap: 10px;
            }
            
            .user-name {
                max-width: 100px;
            }
            
            .breadcrumb {
                display: none;
            }
            
            .sidebar-toggle {
                display: none;
            }
        }
        
        @media (max-width: 768px) {
            .admin-header {
                padding: 0 15px;
            }
            
            .header-title {
                font-size: 1.2rem;
            }
            
            .user-info {
                padding: 6px 12px;
            }
            
            .user-name {
                max-width: 80px;
                font-size: 0.9rem;
            }
            
            .admin-content {
                padding: 20px 15px;
            }
            
            .sidebar-brand {
                font-size: 1.1rem;
            }
        }
        
        @media (max-width: 576px) {
            .admin-header {
                padding: 0 12px;
                height: 60px;
            }
            
            .header-title {
                font-size: 1rem;
                max-width: 120px;
            }
            
            .user-name {
                display: none;
            }
            
            .user-info {
                padding: 6px 8px;
                gap: 8px;
            }
            
            .user-info i {
                font-size: 1.3rem;
            }
            
            .admin-content {
                padding: 15px 12px;
            }
            
            .mobile-menu-btn {
                font-size: 1.3rem;
                padding: 6px;
            }
            
            .sidebar-brand {
                font-size: 1rem;
            }
            
            .sidebar-nav {
                padding: 15px 0;
            }
            
            .nav-link {
                padding: 12px 20px;
                font-size: 0.95rem;
            }
        }
        
        @media (max-width: 480px) {
            .admin-header {
                padding: 0 10px;
                height: 55px;
            }
            
            .header-title {
                font-size: 0.95rem;
                max-width: 100px;
            }
            
            .header-left {
                gap: 10px;
            }
            
            .mobile-menu-btn {
                font-size: 1.2rem;
                padding: 5px;
            }
            
            .user-info {
                padding: 5px 6px;
                min-width: auto;
            }
            
            .admin-content {
                padding: 12px 10px;
                min-height: calc(100vh - 55px);
            }
            
            .sidebar-header {
                padding: 20px 15px;
            }
            
            .sidebar-brand {
                font-size: 0.95rem;
                gap: 8px;
            }
            
            .sidebar-brand i {
                font-size: 1.2rem;
            }
            
            .nav-link {
                padding: 12px 18px;
                gap: 10px;
                font-size: 0.9rem;
            }
            
            .nav-link i {
                font-size: 1rem;
            }
        }
        
        /* Overlay for mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }
        
        .sidebar-overlay.active {
            display: block;
        }
        
        /* Click outside to close */
        .dropdown-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: transparent;
            z-index: 1500;
            display: none;
            pointer-events: none;
        }
        
        .dropdown-overlay.active {
            display: block;
            pointer-events: auto;
        }
        
        /* Prevent horizontal scroll */
        body {
            overflow-x: hidden;
        }
        
        /* Smooth transitions */
        * {
            transition: color var(--transition-speed) ease, background-color var(--transition-speed) ease, border-color var(--transition-speed) ease;
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
                <a href="admin_dashboard.php" class="sidebar-brand">
                    <span>Admin Panel</span>
                </a>
                <button class="sidebar-close-btn" id="sidebarCloseBtn" title="Close sidebar">
                    <i class="fas fa-times"></i>
                </button>
                <button class="sidebar-toggle" id="sidebarToggle" title="Collapse sidebar">
                    <i class="fas fa-chevron-left"></i>
                </button>
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin_dashboard.php' ? 'active' : '' ?>" href="admin_dashboard.php">
                        <i class="fas fa-tachometer-alt"></i>
                        <span>Dashboard</span>
                        <span class="tooltip-text">Dashboard</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin_students.php' ? 'active' : '' ?>" href="admin_students.php">
                        <i class="fas fa-users"></i>
                        <span>Students</span>
                        <span class="tooltip-text">Students</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin_id.php' ? 'active' : '' ?>" href="admin_id.php">
                        <i class="fas fa-id-card"></i>
                        <span>ID Management</span>
                        <span class="tooltip-text">ID Management</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin_user.php' ? 'active' : '' ?>" href="admin_user.php">
                        <i class="fas fa-user-cog"></i>
                        <span>User Management</span>
                        <span class="tooltip-text">User Management</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin_reports.php' ? 'active' : '' ?>" href="admin_reports.php">
                        <i class="fas fa-chart-bar"></i>
                        <span>Reports</span>
                        <span class="tooltip-text">Reports</span>
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'admin_logs.php' ? 'active' : '' ?>" href="admin_logs.php">
                        <i class="fas fa-clipboard-list"></i>
                        <span>System Logs</span>
                        <span class="tooltip-text">System Logs</span>
                    </a>
                </div>
                <div style="border-top: 1px solid rgba(255, 255, 255, 0.1); margin-top: 20px; padding-top: 20px;">
                    <div class="nav-item">
                        <a class="nav-link" href="../includes/logout.php" title="Logout">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                            <span class="tooltip-text">Logout</span>
                        </a>
                    </div>
                </div>
            </nav>
        </aside>
        
        <!-- Main Content Area -->
        <main class="admin-main" id="adminMain">
            <!-- Top Header -->
            <header class="admin-header">
                <div class="header-left">
                    <button class="mobile-menu-btn" id="mobileMenuBtn">
                        <i class="fas fa-bars"></i>
                    </button>
                    <div>
                        <h1 class="header-title" id="pageTitle">Admin Dashboard</h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb" id="breadcrumb">
                                <li class="breadcrumb-item"><a href="admin_dashboard.php">Home</a></li>
                                <li class="breadcrumb-item active" id="currentPageBreadcrumb">Dashboard</li>
                            </ol>
                        </nav>
                    </div>
                </div>
                
                <div class="user-menu">
                    <div class="user-info">
                        <i class="fas fa-user-circle"></i>
                        <span class="user-name"><?= htmlspecialchars($_SESSION['email'] ?? 'Admin') ?></span>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="admin-content">
                <!-- Page content will be inserted here -->

<script>
// Mobile sidebar toggle
document.getElementById('mobileMenuBtn').addEventListener('click', function() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    sidebar.classList.add('mobile-open');
    overlay.classList.add('active');
    
    // Prevent body scroll when sidebar is open
    document.body.style.overflow = 'hidden';
});

// Sidebar close button (mobile only)
document.getElementById('sidebarCloseBtn').addEventListener('click', function(e) {
    e.preventDefault();
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    sidebar.classList.remove('mobile-open');
    overlay.classList.remove('active');
    document.body.style.overflow = '';
});

document.getElementById('sidebarOverlay').addEventListener('click', function() {
    document.getElementById('adminSidebar').classList.remove('mobile-open');
    this.classList.remove('active');
    document.body.style.overflow = '';
});

// Sidebar collapse/expand toggle (desktop only)
document.getElementById('sidebarToggle').addEventListener('click', function() {
    const sidebar = document.getElementById('adminSidebar');
    const main = document.getElementById('adminMain');
    
    sidebar.classList.toggle('collapsed');
    main.classList.toggle('sidebar-collapsed');
    
    // Update toggle icon
    const icon = this.querySelector('i');
    if (sidebar.classList.contains('collapsed')) {
        icon.className = 'fas fa-chevron-right';
    } else {
        icon.className = 'fas fa-chevron-left';
    }
    
    // Save state to localStorage
    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
});

// User dropdown toggle
// REMOVED - logout moved to sidebar

// Close window resize (for mobile orientation changes)
window.addEventListener('resize', function() {
    // Removed dropdown logic
});

// Update page title and breadcrumb based on current page
const pageTitles = {
    'admin_dashboard.php': {
        title: 'Dashboard Overview',
        breadcrumb: 'Dashboard'
    },
    'admin_students.php': {
        title: 'Student Management',
        breadcrumb: 'Students'
    },
    'admin_id.php': {
        title: 'ID Card Management',
        breadcrumb: 'ID Management'
    },
    'admin_user.php': {
        title: 'User Management',
        breadcrumb: 'User Management'
    },
    'admin_reports.php': {
        title: 'Reports & Analytics',
        breadcrumb: 'Reports'
    },
    'admin_logs.php': {
        title: 'System Logs',
        breadcrumb: 'System Logs'
    }
};

const currentPage = '<?= basename($_SERVER['PHP_SELF']) ?>';
if (pageTitles[currentPage]) {
    document.getElementById('pageTitle').textContent = pageTitles[currentPage].title;
    document.getElementById('currentPageBreadcrumb').textContent = pageTitles[currentPage].breadcrumb;
}

// Handle escape key to close menus
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        document.getElementById('userDropdown')?.classList.remove('active');
        document.getElementById('dropdownOverlay')?.classList.remove('active');
        document.getElementById('adminSidebar').classList.remove('mobile-open');
        document.getElementById('sidebarOverlay').classList.remove('active');
        document.body.style.overflow = '';
    }
});

// Restore sidebar state from localStorage
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('adminSidebar');
    const main = document.getElementById('adminMain');
    const toggleBtn = document.getElementById('sidebarToggle');
    const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
    
    if (isCollapsed) {
        sidebar.classList.add('collapsed');
        main.classList.add('sidebar-collapsed');
        
        // Update toggle icon
        const icon = toggleBtn.querySelector('i');
        icon.className = 'fas fa-chevron-right';
    }
});

// Auto-close sidebar on mobile when clicking a link
document.querySelectorAll('.nav-link').forEach(link => {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 1024) {
            document.getElementById('adminSidebar').classList.remove('mobile-open');
            document.getElementById('sidebarOverlay').classList.remove('active');
            document.body.style.overflow = '';
        }
    });
});

// Close sidebar when window is resized to mobile size
window.addEventListener('resize', function() {
    if (window.innerWidth <= 1024) {
        document.getElementById('adminSidebar').classList.remove('mobile-open');
        document.getElementById('sidebarOverlay').classList.remove('active');
        document.body.style.overflow = '';
    }
});
</script>