<?php
require_once '../includes/config.php';
require_once 'student_header.php'; // Include the header
require_once 'student.php';

// Check if user is logged in as student
if (
    !isset($_SESSION['user_id'], $_SESSION['user_type'], $_SESSION['student_id']) ||
    $_SESSION['user_type'] !== 'student'
) {
    header('Location: ../login.php');
    exit();
}

// Fetch student data from database
$student = (new Student())->findById((int)$_SESSION['student_id']);
if (!$student) {
    header('Location: ../login.php');
    exit();
}

// Fetch ID request status
$idRequest = (new Student())->getLatestIdRequest((int)$_SESSION['student_id']);
$idStatus = $idRequest ? $idRequest['status'] : 'Not Applied';
$idSubmitDate = $idRequest ? date('M d, Y', strtotime($idRequest['created_at'])) : 'N/A';
$idUpdateDate = $idRequest ? date('M d, Y h:i A', strtotime($idRequest['updated_at'] ?? $idRequest['created_at'])) : 'N/A';

// Fetch ID request history
$idHistory = (new Student())->getIdRequestHistory((int)$_SESSION['student_id']);

// Prepare data
$studentName = htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
$studentID = htmlspecialchars($student['student_id'] ?? 'N/A');
$course = htmlspecialchars($student['course']);
$yearSection = htmlspecialchars($student['year_level']);
$contact_number = htmlspecialchars($student['contact_number']);
$address = htmlspecialchars($student['address']);
$emergency_contact_name = htmlspecialchars($student['emergency_contact_name'] ?? 'Not Provided');
$emergency_contact = htmlspecialchars($student['emergency_contact'] ?? 'Not Provided');
$avatar = $student['photo'] ? '../uploads/student_photos/' . htmlspecialchars($student['photo']) : '../uploads/default_avatar.png';
$signature = $student['signature'] ? '../uploads/student_signatures/' . htmlspecialchars($student['signature']) : null;
$qrcode = "../uploads/sample_qr.png"; // You can update this path as needed
?>

<<<<<<< HEAD
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Home</title>
    <link href="../assets/css/student.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary-dark: #1b5e20;
            --primary-medium: #2e7d32;
            --primary-light: #4caf50;
            --accent-orange: #ff9800;
            --accent-orange-dark: #f57c00;
            --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
            --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.12);
            --shadow-lg: 0 8px 24px rgba(0, 0, 0, 0.15);
            --transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }
        
        .admin-body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e8eef5 100%);
        }
        
        /* PORTRAIT ID CARD CONTAINER */
        .portrait-id-container {
            perspective: 1200px;
            padding: 30px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 500px;
        }
        
        .portrait-id-card {
            width: 450px;
            height: 600px;
            position: relative;
            transform-style: preserve-3d;
            transition: var(--transition);
            cursor: pointer;
        }
        
        .portrait-id-card:hover {
            transform: scale(1.02);
        }
        
        .portrait-id-card.flipped {
            transform: rotateY(180deg);
        }
        
        .portrait-side {
            width: 100%;
            height: 100%;
            border-radius: 16px;
            position: absolute;
            backface-visibility: hidden;
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            background: white;
            display: flex;
            flex-direction: column;
            padding: 30px 25px;
        }
        
        /* WELCOME BOX ENHANCEMENT */
        .welcome-box {
            width: 95%;
            margin: 40px auto;
            background: linear-gradient(135deg, white 0%, #f9f9f9 100%);
            border-left: 6px solid var(--primary-dark);
            padding: 35px;
            border-radius: 14px;
            box-shadow: var(--shadow-md);
            display: flex;
            gap: 40px;
            align-items: center;
            transition: var(--transition);
        }
        
        .welcome-box:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }
        
        .welcome-box img {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            border: 5px solid var(--primary-dark);
            object-fit: cover;
            flex-shrink: 0;
            box-shadow: 0 4px 16px rgba(27, 94, 32, 0.2);
            transition: var(--transition);
        }
        
        .welcome-box:hover img {
            transform: scale(1.05);
        }
        
        .welcome-content-wrapper {
            display: flex;
            gap: 50px;
            align-items: flex-start;
            flex: 1;
            justify-content: space-between;
        }
        
        .welcome-info {
            flex: 1;
            min-width: 300px;
        }
        
        .welcome-info h2 {
            margin: 0 0 16px 0;
            color: var(--primary-dark);
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.5px;
            line-height: 1.3;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }
        
        .welcome-info p {
            margin: 10px 0;
            color: #555;
            font-size: 15px;
            font-weight: 500;
            line-height: 1.6;
        }
        
        .welcome-info strong {
            color: var(--primary-dark);
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
        }
        
        .status-badge.enrolled {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-medium) 100%);
            color: white;
            border: 1px solid var(--primary-dark);
        }
        
        .welcome-nav {
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-end;
        }
        
        .welcome-nav a {
            padding: 11px 24px;
            background: linear-gradient(135deg, var(--accent-orange) 0%, var(--accent-orange-dark) 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            font-size: 14px;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.25);
            text-align: center;
            border: none;
        }
        
        .welcome-nav a:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 18px rgba(255, 152, 0, 0.35);
        }
        
        /* QUICK ACCESS & STATUS TRACKER */
        .func-table-container {
            width: 95%;
            margin: 40px auto;
            padding: 0;
        }
        
        .func-table-container > h3 {
            font-size: 24px;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0 0 25px 0;
            letter-spacing: 0.3px;
        }
        
        .id-status-tracker {
            background: white;
            border-radius: 14px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }
        
        .id-status-tracker:hover {
            box-shadow: var(--shadow-lg);
        }
        
        .id-status-tracker > h3 {
            margin: 0 0 25px 0;
            color: var(--primary-dark);
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
        }
        
        .status-item {
            background: linear-gradient(135deg, #f0f8f5 0%, #e8f5e9 100%);
            padding: 20px;
            border-radius: 10px;
            border: 2px solid rgba(27, 94, 32, 0.1);
            transition: var(--transition);
        }
        
        .status-item:hover {
            border-color: rgba(27, 94, 32, 0.25);
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }
        
        .status-label {
            font-size: 13px;
            font-weight: 700;
            color: var(--primary-dark);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        
        .status-value {
            font-size: 16px;
            font-weight: 700;
            color: #333;
            word-break: break-word;
        }
        
        .status-badge-small {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
        }
        
        /* ID HISTORY SECTION */
        .id-history-section {
            background: white;
            border-radius: 14px;
            overflow: hidden;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
        }
        
        .id-history-section:hover {
            box-shadow: var(--shadow-lg);
        }
        
        .id-history-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 25px 30px;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-medium) 100%);
            color: white;
            cursor: pointer;
        }
        
        .id-history-header-left {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }
        
        .id-history-header-left h3 {
            margin: 0;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.3px;
        }
        
        .history-toggle {
            font-size: 18px;
            transition: var(--transition);
        }
        
        .history-content {
            padding: 30px;
        }
        
        .history-table-wrapper {
            overflow-x: auto;
        }
        
        .history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        
        .history-table thead {
            background: linear-gradient(135deg, #f0f8f5 0%, #e8f5e9 100%);
        }
        
        .history-table th {
            padding: 15px;
            font-weight: 700;
            color: var(--primary-dark);
            text-align: left;
            border-bottom: 2px solid var(--primary-light);
            letter-spacing: 0.3px;
        }
        
        .history-table td {
            padding: 15px;
            border-bottom: 1px solid #e0e0e0;
            color: #555;
        }
        
        .history-table tbody tr:hover {
            background: rgba(27, 94, 32, 0.04);
        }
        
        .type-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            background: linear-gradient(135deg, var(--accent-orange) 0%, var(--accent-orange-dark) 100%);
            color: white;
        }
        
        /* BACK TO TOP BUTTON */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-medium) 100%);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            z-index: 100;
        }
        
        .back-to-top:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(27, 94, 32, 0.3);
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .portrait-id-card {
                width: 90%;
                max-width: 400px;
                height: auto;
                aspect-ratio: 3/4;
            }
            
            .welcome-box {
                flex-direction: column;
                text-align: center;
                gap: 25px;
            }
            
            .welcome-content-wrapper {
                flex-direction: column;
                gap: 20px;
            }
            
            .welcome-nav {
                align-items: center;
                width: 100%;
            }
            
            .welcome-nav a {
                width: 100%;
                max-width: 250px;
            }
            
            .status-grid {
                grid-template-columns: 1fr;
            }
            
            .back-to-top {
                bottom: 20px;
                right: 20px;
                width: 45px;
                height: 45px;
                font-size: 20px;
            }
        }
    </style>
</head>

<body class="admin-body">
    <!-- BACK TO TOP BUTTON -->
    <button id="backToTopBtn" class="back-to-top" onclick="scrollToTop()">↑</button>

    <div class="portrait-id-container">
=======
<!-- PAGE CONTENT STARTS HERE -->
>>>>>>> f77bc95ad377522d31bb7e87d8d97eddc1a29788
        <div class="portrait-id-card">

            <!-- FRONT -->
            <div class="portrait-side portrait-front">
                <!-- Decorative circular emblem background -->
                <div class="emblem-background"></div>

                <!-- Green Header -->
                <div class="id-header">
                    <h2>KOLEHIYO NG LUNGSOD NG DASMARIÑAS</h2>
                </div>

                <!-- Photo Section with Border -->
                <div class="photo-container">
                    <img src="<?php echo $avatar; ?>" alt="Student Photo" class="student-photo">
                </div>

                <!-- Student Information -->
                <div class="student-info">
                    <p class="student-name"><?php echo $studentName; ?></p>
                    <p class="student-course"><?php echo $course; ?></p>
                    <p class="student-id-number"><?php echo $studentID; ?></p>
                </div>

                <!-- Signature Section -->
                <div class="signature-section">
                    <?php if ($signature): ?>
                        <img src="<?php echo $signature; ?>" alt="Student Signature" class="id-signature-image">
                    <?php else: ?>
                        <div class="signature-placeholder"></div>
                    <?php endif; ?>
                    <p class="signature-label">SIGNATURE OF CARDHOLDER</p>
                </div>
            </div>

            <!-- BACK -->
            <div class="portrait-side portrait-back">
                <!-- Green Header -->
                <div class="id-header">
                    <h2>KOLEHIYO NG LUNGSOD NG DASMARIÑAS</h2>
                </div>

                <!-- Emergency Contact Section -->
                <div class="emergency-section">
                    <p class="emergency-title">In case of emergency, please contact:</p>
                    <p class="emergency-name"><?php echo $emergency_contact_name; ?></p>
                    <p class="emergency-contact"><?php echo $emergency_contact; ?></p>
                </div>

                <!-- QR Code and Registrar Signature Section -->
                <div class="bottom-section">
                    <div class="registrar-section">
                        <div class="signature-placeholder"></div>
                        <p class="registrar-label">Leo Guisseppe N. Dinglasan<br>REGISTRAR</p>
                    </div>
                    <div class="qr-section">
                        <img src="<?php echo $qrcode; ?>" alt="QR Code" class="qr-code">
                    </div>
                </div>

                <!-- Disclaimer -->
                <p class="id-disclaimer">This card is the property of KOLEHIYO NG LUNGSOD NG DASMARIÑAS. In case of loss, please return to the Registrar's Office.</p>

                <!-- Action Buttons -->
                <div class="id-actions">
                    <button onclick="downloadVisualID(event)" class="id-btn download-btn">
                        Download Visual ID
                    </button>
                    <button onclick="scanQRCode(event)" class="id-btn scan-btn">
                        Scan QR Code
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- ▬▬▬▬ WELCOME BOX ▬▬▬▬ -->
    <div class="welcome-box">
        <img src="<?php echo $avatar; ?>" alt="Avatar">
        <div class="welcome-content-wrapper">
            <div class="welcome-info">
                <h2>
                    Welcome, <?php echo $studentName; ?>
                    <span class="status-badge enrolled">Enrolled</span>
                </h2>
                <p><strong>ID:</strong> <?php echo $studentID; ?></p>
                <p><strong>Course:</strong> <?php echo $course; ?></p>
                <p><strong>Year & Section:</strong> <?php echo $yearSection; ?></p>
                <p><strong>Contact Number:</strong> <?php echo $contact_number; ?></p>
                <p><strong>Address:</strong> <?php echo $address; ?></p>
            </div>
            <div class="welcome-nav">
                <a href="student_profile.php">My Profile</a>
                <a href="student_id.php">My ID</a>
                <a href="edit_profile.php">Edit Profile</a>
            </div>
        </div>
    </div>

    <!-- ▬▬▬▬ STUDENT FUNCTIONS ▬▬▬▬ -->
    <div class="func-table-container">
        <h3>Quick Access</h3>
        <!-- ID Status Tracker -->
        <div class="id-status-tracker">
            <h3>ID Application Status Tracker</h3>
            <div class="status-grid">
                <div class="status-item">
                    <div class="status-label">Application Status</div>
                    <div class="status-value status-badge status-<?php echo strtolower(str_replace(' ', '-', $idStatus)); ?>">
                        <?php echo htmlspecialchars($idStatus); ?>
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-label">Date Submitted</div>
                    <div class="status-value"><?php echo $idSubmitDate; ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">Latest Update</div>
                    <div class="status-value"><?php echo $idUpdateDate; ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">Request Type</div>
                    <div class="status-value"><?php echo $idRequest ? htmlspecialchars($idRequest['request_type']) : 'N/A'; ?></div>
                </div>
            </div>
        </div>

        <!-- Past ID History -->
        <div class="id-history-section">
            <div class="id-history-header">
                <div class="id-history-header-left" onclick="toggleHistorySection(this)">
                    <h3>Past ID History</h3>
                    <span class="history-toggle">▼</span>
                </div>
                <div class="id-history-header-right">
                    <button class="clean-history-btn" onclick="cleanHistoryDisplay(event)" title="Hide history records temporarily">
                        Hide
                    </button>
                </div>
            </div>
            <div class="history-content">
                <div class="history-table-content">
                    <?php if (count($idHistory) > 0): ?>
                        <div class="history-table-wrapper">
                            <table class="history-table">
                                <thead>
                                    <tr>
                                        <th>Request Date</th>
                                        <th>Request Type</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($idHistory as $record): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y h:i A', strtotime($record['created_at'])); ?></td>
                                            <td><span class="type-badge type-<?php echo strtolower(str_replace(' ', '-', $record['request_type'])); ?>"><?php echo htmlspecialchars($record['request_type']); ?></span></td>
                                            <td><?php echo htmlspecialchars($record['reason']); ?></td>
                                            <td><span class="status-badge-small status-<?php echo strtolower(str_replace(' ', '-', $record['status'])); ?>"><?php echo htmlspecialchars($record['status']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-history">
                            <p>No past ID requests found.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="history-empty-state">
                    <div class="history-empty-state-content">
                        <span class="history-empty-state-icon">👋</span>
                        <h4>History Hidden</h4>
                        <p>Your history records are hidden but still saved in the database.</p>
                    </div>
                    <button class="restore-history-btn" onclick="restoreHistoryDisplay(event)">
                        Restore
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>

</html>
<script>
    // ▬▬▬▬ ID HISTORY TOGGLE FUNCTIONALITY ▬▬▬▬
    function toggleHistorySection(headerElement) {
        const section = headerElement.closest('.id-history-header') ? headerElement.closest('.id-history-header').closest('.id-history-section') : headerElement.closest('.id-history-section');
        section.classList.toggle('collapsed');
    }

    // ▬▬▬▬ CLEAN HISTORY DISPLAY ▬▬▬▬
    function cleanHistoryDisplay(event) {
        event.stopPropagation();
        const historySection = event.target.closest('.id-history-section');
        const tableContent = historySection.querySelector('.history-table-content');
        const emptyState = historySection.querySelector('.history-empty-state');

        // Fade out table
        tableContent.style.animation = 'fadeOut 0.3s ease-out forwards';

        setTimeout(() => {
            tableContent.style.display = 'none';
            emptyState.classList.add('active');
            emptyState.style.animation = 'fadeIn 0.3s ease-out';
        }, 300);
    }

    // ▬▬▬▬ RESTORE HISTORY DISPLAY ▬▬▬▬
    function restoreHistoryDisplay(event) {
        event.stopPropagation();
        const historySection = event.target.closest('.id-history-section');
        const tableContent = historySection.querySelector('.history-table-content');
        const emptyState = historySection.querySelector('.history-empty-state');

        // Fade out empty state
        emptyState.style.animation = 'fadeOut 0.3s ease-out forwards';

        setTimeout(() => {
            emptyState.classList.remove('active');
            tableContent.style.display = 'block';
            tableContent.style.animation = 'fadeIn 0.3s ease-out';
        }, 300);
    }

    // Add fade animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

    // ▬▬▬▬ BACK TO TOP BUTTON FUNCTIONALITY ▬▬▬▬
    const backToTopBtn = document.getElementById('backToTopBtn');

    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            backToTopBtn.style.display = 'flex';
        } else {
            backToTopBtn.style.display = 'none';
        }
    });

    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // ▬▬▬▬ PORTRAIT ID CARD FLIP ▬▬▬▬
    document.querySelector('.portrait-id-card').addEventListener('click', function() {
        this.classList.toggle('flipped');
    });

    function downloadVisualID(e) {
        e.stopPropagation();
        alert('Visual ID download feature coming soon!');
    }

    function scanQRCode(e) {
        e.stopPropagation();
        alert('QR Code scanner feature coming soon!');
    }
</script>
            </div><!-- End admin-content -->
        </main><!-- End admin-main -->
    </div><!-- End admin-wrapper -->
</body>
</html>