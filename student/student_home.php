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

// Fetch issued ID data
$issuedId = (new Student())->getIssuedId((int)$_SESSION['student_id']);
$digitalIdFile = ($issuedId && !empty($issuedId['digital_id_file'])) ? $issuedId['digital_id_file'] : null;

// Prepare data
$studentName = htmlspecialchars(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
$studentID = htmlspecialchars($student['student_id'] ?? 'N/A');
$course = htmlspecialchars($student['course'] ?? 'Not Specified');
$yearSection = htmlspecialchars($student['year_level'] ?? 'Not Specified');
$contact_number = htmlspecialchars($student['contact_number'] ?? 'Not Provided');
$address = htmlspecialchars($student['address'] ?? 'Not Provided');
$emergency_contact_name = htmlspecialchars($student['emergency_contact_name'] ?? 'Not Provided');
$emergency_contact = htmlspecialchars($student['emergency_contact'] ?? 'Not Provided');

// Avatar handling with better fallback
$avatar = null;
$avatar_initials = '';
$use_initials = false;

if ($student['photo']) {
    $avatar_path = '../uploads/student_photos/' . htmlspecialchars($student['photo']);
    if (file_exists($avatar_path)) {
        $avatar = $avatar_path;
    }
}

if (!$avatar) {
    $use_initials = true;
    $first_initial = strtoupper(substr($student['first_name'] ?? '', 0, 1));
    $last_initial = strtoupper(substr($student['last_name'] ?? '', 0, 1));
    $avatar_initials = $first_initial . $last_initial;
    if (empty($avatar_initials)) {
        $avatar_initials = 'ST'; // Default for "Student"
    }
}

$signature = $student['signature'] ? '../uploads/student_signatures/' . htmlspecialchars($student['signature']) : null;
$qrcode = "../uploads/sample_qr.png";
?>

<!-- PAGE CONTENT STARTS HERE -->

<link href="../assets/css/student.css" rel="stylesheet">
<style>
    .avatar-placeholder {
        background: linear-gradient(135deg, #e0e0e0, #bdbdbd);
        color: #757575;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 50%;
        border: 2px solid #e0e0e0;
        position: relative;
        overflow: hidden;
    }

    .avatar-placeholder::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: linear-gradient(135deg, rgba(224, 224, 224, 0.8), rgba(189, 189, 189, 0.8));
        z-index: 1;
    }

    .avatar-placeholder i {
        position: relative;
        z-index: 2;
        font-size: 2em;
        opacity: 0.8;
    }

    .student-photo.avatar-placeholder {
        width: 80px;
        height: 80px;
    }

    .student-photo.avatar-placeholder i {
        font-size: 2.5em;
    }

    .welcome-avatar.avatar-placeholder {
        width: 80px;
        height: 80px;
        border: 3px solid #e0e0e0;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease;
    }

    .welcome-box:hover .welcome-avatar.avatar-placeholder {
        transform: scale(1.05);
        border-color: #bdbdbd;
    }
</style>

<!-- BACK-TO-TOP BUTTON -->
<button id="backToTopBtn" class="back-to-top" onclick="scrollToTop()" title="Back to top">
    <i class="fas fa-arrow-up"></i>
</button>

<div class="portrait-id-container">
    <div class="portrait-id-card">
        <div class="portrait-side portrait-front"
            style="background-image: url('../assets/images/id_front.png'); background-size: contain; background-position: inherit; background-repeat: round;">
            <div
                style="display: flex; flex-direction: column; align-items: center; justify-content: center; flex: 1; margin-top: 35px;">
                <div class="photo-container">
                    <?php if ($use_initials): ?>
                        <div class="student-photo avatar-placeholder">
                            <i class="fas fa-user"></i>
                        </div>
                    <?php else: ?>
                        <img src="<?php echo $avatar; ?>" alt="Student Photo" class="student-photo">
                    <?php endif; ?>
                </div>
                <div class="student-info">
                    <p class="student-name"><?php echo $studentName; ?></p>
                    <p class="student-course"><?php echo $course; ?></p>
                    <p class="student-id-number"><?php echo $studentID; ?></p>
                </div>
            </div>
            <div class="signature-section">
                <?php if ($signature): ?>
                    <img src="<?php echo $signature; ?>" alt="Student Signature" class="id-signature-image">
                <?php else: ?>
                    <div class="signature-placeholder"></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="portrait-side portrait-back"
            style="background-image: url('../assets/images/id_back.png'); background-size: contain; background-position: inherit; background-repeat: round; padding: 0;">
            <div
                style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100%; padding: 20px; box-sizing: border-box;">
                <!-- Emergency Contact Information -->
                <div class="emergency-contact-section" style="text-align: center; color: #333;">
                    <div class="emergency-contact-info" style="line-height: 1.4; margin-top: 80px; font-weight: bold;">
                        <p style="margin: 5px 0; font-size: 20px; "><strong></strong>
                            <?php echo $emergency_contact_name; ?></p>
                        <p style="margin: 5px 0; font-size: 15px; "><strong></strong> <?php echo $emergency_contact; ?>
                        </p>
                    </div>
                </div>
                <!-- QR Code Section -->
                <div class="qr-code-section" style="margin-bottom: 20px; margin-left: 60%;">
                    <?php
                    // Update QR code path
                    $qrcode_path = "../uploads/qr/" . htmlspecialchars($student['qr_code'] ?? $studentID . '.png');
                    if (file_exists($qrcode_path)):
                    ?>
                        <img src="<?php echo $qrcode_path; ?>" alt="Student QR Code" class="qr-code-image"
                            style="width: 120px; height: 120px; border: 2px solid #333;">
                    <?php else: ?>
                        <div class="qr-code-placeholder"
                            style="width: 120px; height: 120px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; border: 2px dashed #ccc;">
                            <span style="color: #666; font-size: 12px;">QR Code</span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<div class="welcome-box">
    <?php if ($use_initials): ?>
        <div class="welcome-avatar avatar-placeholder">
            <i class="fas fa-user"></i>
        </div>
    <?php else: ?>
        <img src="<?php echo $avatar; ?>" alt="Avatar">
    <?php endif; ?>
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
            <a href="edit_profile.php">Edit Profile</a>
            <?php if ($digitalIdFile && file_exists('../uploads/digital_id/' . $digitalIdFile)): ?>
            <button onclick="toggleDownloadMenu()" style="background: #ff9800; color: white; border: none; padding: 12px 24px; border-radius: 8px; cursor: pointer; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-id-card"></i> Generated IDs
                <i class="fas fa-chevron-down" style="font-size: 0.8em;"></i>
            </button>
            <div id="downloadMenu" style="display: none; position: absolute; background: white; box-shadow: 0 4px 12px #f57c00 ; border-radius: 8px; margin-top: 8px; z-index: 1000; min-width: 180px;">
                <a href="../uploads/digital_id/<?php echo htmlspecialchars($digitalIdFile); ?>" target="_blank" style="display: block; padding: 12px 20px; color: #333; text-decoration: none; border-bottom: 1px solid #f0f0f0;">
                    <i class="fas fa-eye" style="color: #007bff; margin-right: 8px;"></i> View ID
                </a>
                <a href="../uploads/digital_id/<?php echo htmlspecialchars($digitalIdFile); ?>" download style="display: block; padding: 12px 20px; color: #333; text-decoration: none; border-bottom: 1px solid #f0f0f0;">
                    <i class="fas fa-download" style="color: #28a745; margin-right: 8px;"></i> Download PDF
                </a>
                <a href="#" onclick="window.open('../uploads/digital_id/<?php echo addslashes(htmlspecialchars($digitalIdFile)); ?>', '_blank').print(); return false;" style="display: block; padding: 12px 20px; color: #333; text-decoration: none;">
                    <i class="fas fa-print" style="color: #6c757d; margin-right: 8px;"></i> Print ID
                </a>
            </div>
            <?php else: ?>
            <span style="background: #e0e0e0; color: #666; padding: 12px 24px; border-radius: 8px; font-weight: 600; display: flex; align-items: center; gap: 8px;">
                <i class="fas fa-id-card"></i> No Generated ID
            </span>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="func-table-container">
    <h3>Quick Access</h3>

    <div class="id-status-tracker">
        <h3>ID Application Status Tracker</h3>
        <div class="status-grid">
            <div class="status-item">
                <div class="status-label">Application Status</div>
                <div
                    class="status-value status-badge status-<?php echo strtolower(str_replace(' ', '-', $idStatus)); ?>">
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
                <div class="status-value">
                    <?php echo $idRequest ? htmlspecialchars($idRequest['request_type']) : 'N/A'; ?></div>
            </div>
        </div>
    </div>

    <!-- Past ID History -->
    <div class="id-history-section">
        <div>
            <div class="id-history-header-left" onclick="toggleHistorySection(this)">
                <h3>Past ID History</h3>
                <span class="history-toggle">▼</span>
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
                                        <td><span
                                                class="type-badge type-<?php echo strtolower(str_replace(' ', '-', $record['request_type'])); ?>"><?php echo htmlspecialchars($record['request_type']); ?></span>
                                        </td>
                                        <td><?php echo htmlspecialchars($record['reason']); ?></td>
                                        <td><span
                                                class="status-badge-small status-<?php echo strtolower(str_replace(' ', '-', $record['status'])); ?>"><?php echo htmlspecialchars($record['status']); ?></span>
                                        </td>
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
                    <span class="history-empty-state-icon"></span>
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

</div>
</main>
</div>
</body>

</html>

<script>
    function toggleHistorySection(headerElement) {
        const section = headerElement.closest('.id-history-header') ? headerElement.closest('.id-history-header').closest(
            '.id-history-section') : headerElement.closest('.id-history-section');
        section.classList.toggle('collapsed');
    }

    function cleanHistoryDisplay(event) {
        event.stopPropagation();
        const historySection = event.target.closest('.id-history-section');
        const tableContent = historySection.querySelector('.history-table-content');
        const emptyState = historySection.querySelector('.history-empty-state');
        tableContent.style.animation = 'fadeOut 0.3s ease-out forwards';
        setTimeout(() => {
            tableContent.style.display = 'none';
            emptyState.classList.add('active');
            emptyState.style.animation = 'fadeIn 0.3s ease-out';
        }, 300);
    }

    function restoreHistoryDisplay(event) {
        event.stopPropagation();
        const historySection = event.target.closest('.id-history-section');
        const tableContent = historySection.querySelector('.history-table-content');
        const emptyState = historySection.querySelector('.history-empty-state');
        emptyState.style.animation = 'fadeOut 0.3s ease-out forwards';
        setTimeout(() => {
            emptyState.classList.remove('active');
            tableContent.style.display = 'block';
            tableContent.style.animation = 'fadeIn 0.3s ease-out';
        }, 300);
    }

    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        @keyframes fadeOut {
            from { opacity: 1; }
            to { opacity: 0; }
        }
    `;
    document.head.appendChild(style);

    const backToTopBtn = document.getElementById('backToTopBtn');
    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            backToTopBtn.style.display = 'flex';
        } else {
            backToTopBtn.style.display = 'none';
        }
    });

    function toggleDownloadMenu() {
        const menu = document.getElementById('downloadMenu');
        if (menu) {
            menu.style.display = menu.style.display === 'none' ? 'block' : 'none';
        }
    }

    document.addEventListener('click', function(event) {
        const menu = document.getElementById('downloadMenu');
        if (menu && !event.target.closest('.welcome-nav')) {
            menu.style.display = 'none';
        }
    });

    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

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