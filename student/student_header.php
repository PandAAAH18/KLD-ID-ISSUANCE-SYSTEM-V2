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
<<<<<<< HEAD
    <title>Student Portal | KLD</title>
=======
    <title>Student Dashboard | School Portal</title>
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
    <!-- Font Awesome CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Local Bootstrap CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
<<<<<<< HEAD
    <link href="../assets/css/admin.css" rel="stylesheet">
=======
    <link href="../assets/css/student.css" rel="stylesheet">
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
    <style>
        :root {
            --primary-dark: #1b5e20;
            --primary-medium: #2e7d32;
            --primary-light: #4caf50;
<<<<<<< HEAD
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
=======
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
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
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
<<<<<<< HEAD
            transition: var(--transition);
            box-shadow: 8px 0 32px rgba(0, 0, 0, 0.2);
=======
            transition: all var(--transition-speed) ease;
            box-shadow: 4px 0 20px rgba(0, 0, 0, 0.1);
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
            overflow-y: auto;
            overflow-x: hidden;
        }
        
<<<<<<< HEAD
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
=======
        /* Collapsed State */
        .admin-sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
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
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
        }
        
        .sidebar-brand {
            color: white;
            text-decoration: none;
<<<<<<< HEAD
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
=======
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
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
        }
        
        .nav-link:hover {
            color: white;
<<<<<<< HEAD
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
=======
            background: rgba(255, 255, 255, 0.1);
            border-left-color: var(--accent-yellow);
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
        }
        
        .nav-link.active {
            color: white;
<<<<<<< HEAD
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
        
        .sidebar-footer {
            padding: 20px 0;
            border-top: 2px solid rgba(255, 255, 255, 0.1);
            margin-top: auto;
            background: rgba(0, 0, 0, 0.1);
=======
            background: rgba(255, 255, 255, 0.15);
            border-left-color: var(--accent-yellow);
        }
        
        .nav-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
            flex-shrink: 0;
            transition: all var(--transition-speed) ease;
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
        }
        
        /* Main Content Area */
        .admin-main {
            flex: 1;
            margin-left: var(--sidebar-width);
            min-height: 100vh;
<<<<<<< HEAD
            display: flex;
            flex-direction: column;
=======
            transition: margin-left var(--transition-speed) ease;
        }
        
        .admin-main.sidebar-collapsed {
            margin-left: var(--sidebar-collapsed-width);
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
        }
        
        .admin-header {
            background: white;
            height: var(--header-height);
<<<<<<< HEAD
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
=======
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.05);
            position: sticky;
            top: 0;
            z-index: 999;
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
        }
        
        .header-left {
            display: flex;
            align-items: center;
<<<<<<< HEAD
            gap: 20px;
        }
        
        .header-title {
            font-size: 1.75rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0;
            letter-spacing: 0.3px;
            transition: var(--transition);
=======
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
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
        }
        
        .user-menu {
            display: flex;
            align-items: center;
<<<<<<< HEAD
            gap: 20px;
=======
            gap: 15px;
            position: relative;
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
        }
        
        .user-info {
            display: flex;
            align-items: center;
<<<<<<< HEAD
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
=======
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
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
        }
        
        /* Content Container */
        .admin-content {
<<<<<<< HEAD
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
=======
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
            
            .admin-sidebar.collapsed {
                transform: translateX(-100%);
                width: 280px;
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
            }
            
            .admin-sidebar.mobile-open {
                transform: translateX(0);
            }
            
<<<<<<< HEAD
=======
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
                margin-right: 0;
                font-size: 1.1rem;
            }
            
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
            .admin-main {
                margin-left: 0;
            }
            
<<<<<<< HEAD
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
=======
            .admin-main.sidebar-collapsed {
                margin-left: 0;
            }
            
            .mobile-menu-btn {
                display: block;
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
            }
            
            .admin-header {
                padding: 0 20px;
<<<<<<< HEAD
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
=======
                gap: 15px;
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
            }
            
            .header-left {
                gap: 12px;
            }
            
            .header-title {
<<<<<<< HEAD
                font-size: 1.2rem;
            }
        }
        
        /* Desktop only */
        @media (min-width: 769px) {
            .mobile-menu-btn {
                display: none !important;
=======
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
            
            .user-dropdown {
                right: -10px;
                min-width: 180px;
            }
        }
        
        @media (max-width: 576px) {
            .header-title {
                font-size: 1.1rem;
                max-width: 150px;
            }
            
            .user-name {
                display: none;
            }
            
            .user-info {
                padding: 8px;
            }
            
            .user-info .fa-chevron-down {
                display: none;
            }
            
            .admin-content {
                padding: 15px 10px;
            }
        }
        
        @media (max-width: 480px) {
            .header-title {
                font-size: 1rem;
                max-width: 120px;
            }
            
            .mobile-menu-btn {
                font-size: 1.3rem;
                padding: 6px;
            }
            
            .user-info {
                padding: 6px;
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
            }
        }
        
        /* Overlay for mobile */
        .sidebar-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
<<<<<<< HEAD
            background: rgba(0, 0, 0, 0.6);
            z-index: 999;
            display: none;
            transition: opacity 0.3s ease;
            opacity: 0;
            backdrop-filter: blur(3px);
=======
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
        }
        
        .sidebar-overlay.active {
            display: block;
<<<<<<< HEAD
            opacity: 1;
=======
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
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
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
<<<<<<< HEAD
                    <i class="fas fa-graduation-cap"></i>
                    <span>Student Portal</span>
                </a>
=======
                    <span>Student Portal</span>
                </a>
                <button class="sidebar-toggle" id="sidebarToggle" title="Collapse sidebar">
                    <i class="fas fa-chevron-left"></i>
                </button>
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
            </div>
            
            <nav class="sidebar-nav">
                <div class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'student_home.php' ? 'active' : '' ?>" href="student_home.php">
                        <i class="fas fa-home"></i>
                        <span>Home</span>
<<<<<<< HEAD
=======
                        <span class="tooltip-text">Home</span>
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'student_profile.php' ? 'active' : '' ?>" href="student_profile.php">
<<<<<<< HEAD
                        <i class="fas fa-user-circle"></i>
                        <span>Profile</span>
=======
                        <i class="fas fa-user"></i>
                        <span>My Profile</span>
                        <span class="tooltip-text">My Profile</span>
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'student_id.php' ? 'active' : '' ?>" href="student_id.php">
                        <i class="fas fa-id-card"></i>
<<<<<<< HEAD
                        <span>My ID</span>
=======
                        <span>Student ID</span>
                        <span class="tooltip-text">Student ID</span>
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
                    </a>
                </div>
                <div class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'student_help.php' ? 'active' : '' ?>" href="student_help.php">
                        <i class="fas fa-question-circle"></i>
<<<<<<< HEAD
                        <span>Help</span>
                    </a>
                </div>
=======
                        <span>Help & Support</span>
                        <span class="tooltip-text">Help & Support</span>
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
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
            </nav>
        </aside>
        
        <!-- Main Content Area -->
<<<<<<< HEAD
        <main class="admin-main">
=======
        <main class="admin-main" id="adminMain">
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
            <!-- Top Header -->
            <header class="admin-header">
                <div class="header-left">
                    <button class="mobile-menu-btn" id="mobileMenuBtn">
                        <i class="fas fa-bars"></i>
                    </button>
<<<<<<< HEAD
                    <h1 class="header-title" id="pageTitle">Student Portal</h1>
=======
                    <div>
                        <h1 class="header-title" id="pageTitle">Student Dashboard</h1>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb" id="breadcrumb">
                                <li class="breadcrumb-item"><a href="student_home.php">Home</a></li>
                                <li class="breadcrumb-item active" id="currentPageBreadcrumb">Dashboard</li>
                            </ol>
                        </nav>
                    </div>
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
                </div>
                
                <div class="user-menu">
                    <div class="user-info">
<<<<<<< HEAD
                        <i class="fas fa-envelope"></i>
                        <span><?= htmlspecialchars($_SESSION['email'] ?? 'Student') ?></span>
                    </div>
                    <a href="../includes/logout.php" class="logout-btn">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                    </a>
=======
                        <i class="fas fa-user-circle"></i>
                        <span class="user-name"><?= htmlspecialchars($_SESSION['email'] ?? 'Student') ?></span>
                    </div>
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="admin-content">
<<<<<<< HEAD
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
=======
                <!-- Page content will be inserted here -->

<script>
// Update page title and breadcrumb based on current page for student pages
const pageTitles = {
    'student_home.php': {
        title: 'Student Home',
        breadcrumb: 'Home'
    },
    'student_profile.php': {
        title: 'My Profile',
        breadcrumb: 'Profile'
    },
    'student_id.php': {
        title: 'Student ID',
        breadcrumb: 'Student ID'
    },
    'student_help.php': {
        title: 'Help & Support',
        breadcrumb: 'Help'
    }
};

const currentPage = '<?= basename($_SERVER['PHP_SELF']) ?>';
if (pageTitles[currentPage]) {
    document.getElementById('pageTitle').textContent = pageTitles[currentPage].title;
    document.getElementById('currentPageBreadcrumb').textContent = pageTitles[currentPage].breadcrumb;
}

// Mobile sidebar toggle
document.getElementById('mobileMenuBtn').addEventListener('click', function() {
    const sidebar = document.getElementById('adminSidebar');
    const overlay = document.getElementById('sidebarOverlay');
    
    sidebar.classList.toggle('mobile-open');
    overlay.classList.toggle('active');
    
    // Prevent body scroll when sidebar is open
    document.body.style.overflow = sidebar.classList.contains('mobile-open') ? 'hidden' : '';
});

document.getElementById('sidebarOverlay').addEventListener('click', function() {
    document.getElementById('adminSidebar').classList.remove('mobile-open');
    this.classList.remove('active');
    document.body.style.overflow = '';
});

// Sidebar collapse/expand toggle
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

// Handle escape key to close menus
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
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
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
