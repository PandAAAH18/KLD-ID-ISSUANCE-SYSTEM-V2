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
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
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
                <!-- QR Code Scanner Section -->
<div class="admin-card">
    <div class="admin-card-header">
        <span><i class="fas fa-qrcode"></i> QR Code Scanner</span>
    </div>
    <div class="admin-card-body">
        <div class="action-section">
            <h3><i class="fas fa-camera"></i> Scan Student ID QR Code</h3>
            
            <!-- Mode Selection -->
            <div class="mode-selection" style="margin-bottom: 20px;">
                <div class="mode-buttons" style="display: flex; gap: 10px; margin-bottom: 15px;">
                    <button id="camera-mode" class="btn-admin btn-primary mode-btn active">
                        <i class="fas fa-camera"></i> Camera Scan
                    </button>
                    <button id="file-mode" class="btn-admin btn-secondary mode-btn">
                        <i class="fas fa-file-upload"></i> Upload Image
                    </button>
                </div>
            </div>

            <!-- Camera Scanner Container -->
            <div id="camera-container" class="scanner-mode">
                <!-- Camera Selection -->
                <div style="margin-bottom: 15px;">
                    <label for="camera-select">Select Camera:</label>
                    <select id="camera-select" class="form-select" style="margin-top: 5px;">
                        <option value="">Loading cameras...</option>
                    </select>
                </div>

                <!-- Scanner -->
                <div id="qr-reader" style="width: 100%; max-width: 500px; margin: 0 auto; border: 2px solid var(--school-gray); border-radius: 8px; overflow: hidden;"></div>
                
                <!-- Camera Controls -->
                <div style="text-align: center; margin-top: 15px;">
                    <button id="start-scanner" class="btn-admin btn-primary">
                        <i class="fas fa-play"></i> Start Scanner
                    </button>
                    <button id="stop-scanner" class="btn-admin btn-secondary" style="display: none;">
                        <i class="fas fa-stop"></i> Stop Scanner
                    </button>
                    <button id="restart-scanner" class="btn-admin btn-outline">
                        <i class="fas fa-redo"></i> Restart Scanner
                    </button>
                </div>
            </div>

            <!-- File Upload Container -->
            <div id="file-container" class="scanner-mode" style="display: none;">
                <!-- File Drop Zone -->
                <div id="file-drop-zone" style="border: 2px dashed var(--school-gray); border-radius: 8px; padding: 40px 20px; text-align: center; cursor: pointer; transition: all 0.3s ease; background: var(--school-gray-light);">
                    <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: var(--school-gray); margin-bottom: 15px;"></i>
                    <h4>Drop QR Code Image Here</h4>
                    <p style="color: var(--school-gray-dark); margin-bottom: 15px;">or click to select file</p>
                    <p style="font-size: 0.8rem; color: var(--school-gray-dark);">
                        Supported formats: JPG, PNG, GIF, WebP
                    </p>
                    <input type="file" id="file-input" accept="image/*" style="display: none;">
                </div>

                <!-- File Preview -->
                <div id="file-preview" style="margin-top: 20px; display: none;">
                    <h5>Image Preview:</h5>
                    <img id="preview-image" style="max-width: 300px; max-height: 300px; border: 1px solid var(--school-gray); border-radius: 4px;">
                    <div style="margin-top: 10px;">
                        <button id="scan-file" class="btn-admin btn-primary">
                            <i class="fas fa-search"></i> Scan QR Code
                        </button>
                        <button id="clear-file" class="btn-admin btn-outline">
                            <i class="fas fa-times"></i> Clear
                        </button>
                    </div>
                </div>
            </div>

            <!-- Scanner Results -->
            <div id="qr-reader-results" style="margin-top: 20px;"></div>

            <!-- Manual Input Fallback -->
            <div style="border-top: 1px solid #eee; padding-top: 20px; margin-top: 20px;">
                <h4><i class="fas fa-keyboard"></i> Or Enter Student ID Manually</h4>
                <form id="manual-search-form" style="display: flex; gap: 10px; margin-top: 10px;">
                    <input type="text" id="student-id-input" placeholder="Enter Student ID or ID Number" 
                           class="form-input" style="flex: 1;">
                    <button type="submit" class="btn-admin btn-primary">
                        <i class="fas fa-search"></i> Search
                    </button>
                </form>
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
<script src="https://cdn.jsdelivr.net/npm/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
// Mobile menu toggle and basic dashboard functionality
document.addEventListener('DOMContentLoaded', function() {
    // Mobile menu
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
    
    // Initialize QR Scanner after DOM is loaded
    initializeQRScanner();
});

// QR Scanner functionality
let html5QrcodeScanner;
let currentCameraId = null;
let currentFile = null;

function initializeQRScanner() {
    // Mode Switching - Wait for elements to exist
    const cameraModeBtn = document.getElementById('camera-mode');
    const fileModeBtn = document.getElementById('file-mode');
    
    if (cameraModeBtn && fileModeBtn) {
        cameraModeBtn.addEventListener('click', function() {
            switchMode('camera');
        });

        fileModeBtn.addEventListener('click', function() {
            switchMode('file');
        });
    } else {
        console.error('Mode buttons not found');
        return;
    }

    // Initialize other QR scanner components
    populateCameras();
    setupFileHandlers();
    setupCameraControls();
    setupManualSearch();
}

function switchMode(mode) {
    // Update active button
    document.querySelectorAll('.mode-btn').forEach(btn => {
        btn.classList.remove('active', 'btn-primary');
        btn.classList.add('btn-secondary');
    });
    
    if (mode === 'camera') {
        document.getElementById('camera-mode').classList.add('active', 'btn-primary');
        document.getElementById('camera-mode').classList.remove('btn-secondary');
        document.getElementById('camera-container').style.display = 'block';
        document.getElementById('file-container').style.display = 'none';
        
        // Stop file scanner if running
        if (html5QrcodeScanner && document.getElementById('start-scanner').style.display === 'none') {
            document.getElementById('stop-scanner').click();
        }
    } else {
        document.getElementById('file-mode').classList.add('active', 'btn-primary');
        document.getElementById('file-mode').classList.remove('btn-secondary');
        document.getElementById('camera-container').style.display = 'none';
        document.getElementById('file-container').style.display = 'block';
        
        // Stop camera scanner if running
        if (html5QrcodeScanner) {
            html5QrcodeScanner.stop().then(() => {
                console.log("Scanner stopped on mode change");
            }).catch(error => {
                console.log("Scanner cleanup:", error);
            });
            document.getElementById('stop-scanner').style.display = 'none';
            document.getElementById('start-scanner').style.display = 'inline-block';
        }
    }
}

// Camera Management
async function populateCameras() {
    try {
        const devices = await Html5Qrcode.getCameras();
        const cameraSelect = document.getElementById('camera-select');
        
        if (!cameraSelect) {
            console.error('Camera select element not found');
            return;
        }
        
        cameraSelect.innerHTML = '';
        devices.forEach(device => {
            const option = document.createElement('option');
            option.value = device.id;
            option.text = device.label || `Camera ${cameraSelect.length + 1}`;
            cameraSelect.appendChild(option);
        });
        
        if (devices.length > 0) {
            currentCameraId = devices[0].id;
        } else {
            showResult('error', 'No cameras found. Please check your device permissions.');
        }
    } catch (error) {
        console.error('Error getting cameras:', error);
        showResult('error', 'Could not access camera. Please check permissions.');
    }
}

// Initialize Camera Scanner
function initializeCameraScanner(cameraId = null) {
    const config = {
        fps: 10,
        qrbox: { width: 250, height: 250 }
    };

    // Clear any existing scanner
    const qrReaderElement = document.getElementById("qr-reader");
    if (!qrReaderElement) {
        showResult('error', 'QR reader element not found.');
        return;
    }
    
    qrReaderElement.innerHTML = '';
    
    // Use Html5Qrcode for manual camera control
    html5QrcodeScanner = new Html5Qrcode("qr-reader");
    
    const cameraIdToUse = cameraId || currentCameraId;
    
    if (!cameraIdToUse) {
        showResult('error', 'No camera selected or available.');
        return;
    }
    
    // Start the camera
    html5QrcodeScanner.start(
        cameraIdToUse,
        config,
        onScanSuccess,
        onScanFailure
    ).then(() => {
        console.log('Camera scanner started successfully');
        showResult('success', 'Camera scanner started. Point at a QR code to scan.');
    }).catch(error => {
        console.error('Scanner initialization failed:', error);
        showResult('error', 'Failed to start camera: ' + error);
        document.getElementById('stop-scanner').style.display = 'none';
        document.getElementById('start-scanner').style.display = 'inline-block';
    });
}

function setupFileHandlers() {
    const fileDropZone = document.getElementById('file-drop-zone');
    const fileInput = document.getElementById('file-input');
    const previewImage = document.getElementById('preview-image');
    const filePreview = document.getElementById('file-preview');
    const scanFileBtn = document.getElementById('scan-file');
    const clearFileBtn = document.getElementById('clear-file');

    if (!fileDropZone || !fileInput || !scanFileBtn || !clearFileBtn) {
        console.error('File handler elements not found');
        return;
    }

    // Click to select file
    fileDropZone.addEventListener('click', () => {
        fileInput.click();
    });

    // Drag and drop events
    fileDropZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        fileDropZone.style.borderColor = 'var(--school-green)';
        fileDropZone.style.background = 'var(--school-gray)';
    });

    fileDropZone.addEventListener('dragleave', () => {
        fileDropZone.style.borderColor = 'var(--school-gray)';
        fileDropZone.style.background = 'var(--school-gray-light)';
    });

    fileDropZone.addEventListener('drop', (e) => {
        e.preventDefault();
        fileDropZone.style.borderColor = 'var(--school-gray)';
        fileDropZone.style.background = 'var(--school-gray-light)';
        
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            handleFileSelect(files[0]);
        }
    });

    // File input change
    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length > 0) {
            handleFileSelect(e.target.files[0]);
        }
    });

    // Scan file button
    scanFileBtn.addEventListener('click', async () => {
        if (!currentFile) {
            showResult('error', 'Please select an image file first.');
            return;
        }

        showResult('info', 'Scanning image for QR code...');

        try {
            // Use HTML5Qrcode for file scanning
            const html5QrCode = new Html5Qrcode("qr-reader-results");
            const decodedText = await html5QrCode.scanFile(currentFile, false);
            
            if (decodedText) {
                onScanSuccess(decodedText);
            } else {
                showResult('error', 'No QR code found in the image.');
            }
        } catch (error) {
            console.error('QR scan error:', error);
            showResult('error', 'Could not read QR code from image. Please try another image.');
        }
    });

    // Clear file button
    clearFileBtn.addEventListener('click', () => {
        currentFile = null;
        fileInput.value = '';
        if (filePreview) filePreview.style.display = 'none';
        if (fileDropZone) fileDropZone.style.display = 'block';
        document.getElementById('qr-reader-results').innerHTML = '';
    });
}

function handleFileSelect(file) {
    const filePreview = document.getElementById('file-preview');
    const fileDropZone = document.getElementById('file-drop-zone');
    const previewImage = document.getElementById('preview-image');

    // Validate file type
    const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!validTypes.includes(file.type)) {
        showResult('error', 'Please select a valid image file (JPG, PNG, GIF, WebP).');
        return;
    }

    // Validate file size (max 5MB)
    if (file.size > 5 * 1024 * 1024) {
        showResult('error', 'File size too large. Maximum size is 5MB.');
        return;
    }

    currentFile = file;

    // Show preview
    const reader = new FileReader();
    reader.onload = (e) => {
        if (previewImage) previewImage.src = e.target.result;
        if (filePreview) filePreview.style.display = 'block';
        if (fileDropZone) fileDropZone.style.display = 'none';
    };
    reader.readAsDataURL(file);
}

function setupCameraControls() {
    const startScannerBtn = document.getElementById('start-scanner');
    const stopScannerBtn = document.getElementById('stop-scanner');
    const restartScannerBtn = document.getElementById('restart-scanner');

    if (startScannerBtn) {
        startScannerBtn.addEventListener('click', function() {
            const cameraId = document.getElementById('camera-select').value;
            if (!cameraId) {
                showResult('error', 'Please select a camera first.');
                return;
            }
            initializeCameraScanner(cameraId);
            this.style.display = 'none';
            if (stopScannerBtn) stopScannerBtn.style.display = 'inline-block';
        });
    }

    if (stopScannerBtn) {
        stopScannerBtn.addEventListener('click', function() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => {
                    console.log("Scanner stopped successfully");
                    showResult('info', 'Camera scanner stopped.');
                }).catch(error => {
                    console.log("Scanner stop error:", error);
                });
            }
            this.style.display = 'none';
            if (startScannerBtn) startScannerBtn.style.display = 'inline-block';
        });
    }

    if (restartScannerBtn) {
        restartScannerBtn.addEventListener('click', function() {
            if (html5QrcodeScanner) {
                html5QrcodeScanner.stop().then(() => {
                    console.log("Scanner stopped for restart");
                    // Reinitialize after stopping
                    const cameraId = document.getElementById('camera-select').value;
                    if (cameraId) {
                        initializeCameraScanner(cameraId);
                        if (stopScannerBtn) stopScannerBtn.style.display = 'inline-block';
                        if (startScannerBtn) startScannerBtn.style.display = 'none';
                    }
                }).catch(error => {
                    console.log("Scanner restart error:", error);
                });
            } else {
                // If no scanner running, just start it
                const cameraId = document.getElementById('camera-select').value;
                if (cameraId) {
                    initializeCameraScanner(cameraId);
                    if (stopScannerBtn) stopScannerBtn.style.display = 'inline-block';
                    if (startScannerBtn) startScannerBtn.style.display = 'none';
                }
            }
        });
    }
}

function setupManualSearch() {
    const manualForm = document.getElementById('manual-search-form');
    if (manualForm) {
        manualForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const studentIdInput = document.getElementById('student-id-input');
            const studentId = studentIdInput ? studentIdInput.value.trim() : '';
            
            if (studentId) {
                window.location.href = `student_details.php?student_id=${encodeURIComponent(studentId)}`;
            } else {
                showResult('error', 'Please enter a student ID.');
            }
        });
    }
}

// Scanner Success Handler
function onScanSuccess(decodedText, decodedResult) {
    console.log('QR Code scanned:', decodedText);
    
    // Stop camera scanner if running
    if (html5QrcodeScanner && document.getElementById('camera-container').style.display !== 'none') {
        html5QrcodeScanner.stop().then(() => {
            console.log("Scanner stopped after successful scan");
        }).catch(error => {
            console.log("Scanner cleanup:", error);
        });
        document.getElementById('stop-scanner').style.display = 'none';
        document.getElementById('start-scanner').style.display = 'inline-block';
    }

    showResult('success', 'QR Code scanned successfully! Processing...');
    
    // Process the scanned data
    processScannedData(decodedText);
}

function onScanFailure(error) {
    // Don't show errors for normal operation
}

function processScannedData(scannedData) {
    let studentId = null;
    
    // Try to extract ID from URL (if QR contains a URL)
    try {
        const url = new URL(scannedData);
        const urlParams = new URLSearchParams(url.search);
        studentId = urlParams.get('id') || urlParams.get('student_id') || urlParams.get('student');
    } catch (e) {
        // If it's not a URL, try direct number extraction
        console.log('Not a URL, trying direct extraction');
    }
    
    // If direct ID was scanned or extraction from URL failed
    if (!studentId) {
        // Try to extract numbers from the scanned data
        const numbers = scannedData.match(/\d+/g);
        if (numbers && numbers.length > 0) {
            // Use the longest number sequence as potential ID
            studentId = numbers.reduce((longest, current) => 
                current.length > longest.length ? current : longest, '');
        }
    }
    
    // If we still don't have an ID, use the raw data (might be direct ID)
    if (!studentId && scannedData.trim().length > 0) {
        studentId = scannedData.trim();
    }
    
    if (studentId) {
        // Show success and redirect after short delay
        showResult('success', `Student ID found: ${studentId}. Redirecting...`);
        setTimeout(() => {
            window.location.href = `student_details.php?student_id=${encodeURIComponent(studentId)}`;
        }, 1500);
    } else {
        showResult('error', 'Invalid QR code. Could not extract student information.');
    }
}

// Result Display Function
function showResult(type, message) {
    const resultsDiv = document.getElementById('qr-reader-results');
    if (!resultsDiv) return;
    
    const icon = type === 'success' ? 'fa-check-circle' : 
                 type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle';
    const alertClass = type === 'success' ? 'alert-success' : 
                      type === 'error' ? 'alert-error' : 'alert-info';
    
    resultsDiv.innerHTML = `
        <div class="alert-banner ${alertClass}">
            <i class="fas ${icon}"></i>
            <div>${message}</div>
        </div>
    `;
}
</script>
</body>
</html>