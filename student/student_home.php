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
$avatar = $student['photo'] ? '../uploads/student_photos/' . htmlspecialchars($student['photo']) : '../uploads/default_avatar.png';
$qrcode = "../uploads/sample_qr.png"; // You can update this path as needed
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Home</title>
    <link href="../assets/css/student.css" rel="stylesheet">
</head>

<body class="admin-body">
    <!-- BACK TO TOP BUTTON -->
    <button id="backToTopBtn" class="back-to-top" onclick="scrollToTop()">↑</button>

    <div class="portrait-id-container">
        <div class="portrait-id-card">

            <!-- FRONT -->
            <div class="portrait-side portrait-front">
                <div class="pf-header">KOLEHIYO NG LUNGSOD NG DASMARIÑAS</div>

                <img src="<?php echo $avatar; ?>" class="pf-photo">

                <div class="pf-details">
                    <p><?php echo $studentName; ?></p>
                    <p><?php echo $course; ?></p>
                    <p class="pf-idnumber"><?php echo $studentID; ?></p>
                </div>

                <div class="pf-signature">
                    <img src="../uploads/signature.png" width="120">
                    <div class="pf-signature-line">SIGNATURE OF CARDHOLDER</div>
                </div>
            </div>

            <!-- BACK -->
            <div class="portrait-side portrait-back">
                <p class="back-title">In case of emergency, contact:</p>
                <p><strong>Marlyn Concepcion</strong></p>
                <p>09462274362</p>

                <img src="<?php echo $qrcode; ?>" class="qr-code">

                <div class="reg-signature">
                    Registrar Signature
                </div>

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
                    Welcome, <?php echo $studentName; ?> !
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