<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/student_header.php';
require_once __DIR__ . '/student.php';

if (
    !isset($_SESSION['user_id'], $_SESSION['user_type'], $_SESSION['student_id']) ||
    $_SESSION['user_type'] !== 'student'
) {
    header('Location: ../index.php');
    exit();
}

$stu = (new Student())->findById((int)$_SESSION['student_id']);
if (!$stu) {
    header('Location: ../index.php');
    exit();
}

/* ---- Prepare data ---- */
$avatarPath = $stu['photo'] ? '../uploads/student_photos/' . htmlspecialchars($stu['photo']) : '../uploads/default_avatar.png';
$fullName   = htmlspecialchars($stu['first_name'] . ' ' . $stu['last_name']);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/student.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
         
        .school-navbar {
            background: linear-gradient(135deg, var(--school-green) 0%, #2d5c2d 100%);
            border-bottom: 4px solid var(--school-yellow);
            padding: 9px 16px;
            position: relative;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
            width: 100%;
            height: auto;
            min-height: 65px;
            backdrop-filter: blur(10px);
            background-attachment: fixed;
        }
    </style>
</head>

<body>
    <!-- BACK-TO-TOP BUTTON -->
    <button id="backToTopBtn" class="back-to-top" onclick="scrollToTop()">↑</button>
    <!-- PROFILE CARD -->
    <div class="profile-wrapper">
        <div class="profile-container">
            <div class="profile-card-header">
                Student Information
            </div>

            <div class="profile-card-body">
                <!-- Photo Section -->
                <div class="profile-photo-section">
                    <img src="<?= $avatarPath ?>" alt="Profile Photo" class="profile-photo">
                    <div class="profile-name"><?= $fullName ?></div>
                    <div class="profile-student-id">ID: <?= htmlspecialchars($stu['student_id']) ?></div>
                </div>

                <!-- Info Grid -->
                <div class="profile-info-grid">
                    <div class="info-item">
                        <div class="info-label">Email</div>
                        <div class="info-value"><?= htmlspecialchars($stu['email']) ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Contact Number</div>
                        <div class="info-value"><?= htmlspecialchars($stu['contact_number']) ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Course</div>
                        <div class="info-value"><?= htmlspecialchars($stu['course']) ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Year Level</div>
                        <div class="info-value"><?= htmlspecialchars($stu['year_level']) ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Date of Birth</div>
                        <div class="info-value"><?= htmlspecialchars($stu['dob'] ?? 'N/A') ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Gender</div>
                        <div class="info-value"><?= htmlspecialchars($stu['gender'] ?? 'N/A') ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Blood Type</div>
                        <div class="info-value"><?= htmlspecialchars($stu['blood_type'] ?? 'N/A') ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Address</div>
                        <div class="info-value"><?= htmlspecialchars($stu['address'] ?? 'N/A') ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Emergency Contact Name</div>
                        <div class="info-value"><?= htmlspecialchars($stu['emergency_contact_name'] ?? 'N/A') ?></div>
                    </div>

                    <div class="info-item">
                        <div class="info-label">Emergency Contact Number</div>
                        <div class="info-value"><?= htmlspecialchars($stu['emergency_contact'] ?? 'N/A') ?></div>
                    </div>
                </div>

                <!-- Signature Section -->
                <div class="signature-display-section">
                    <div class="signature-section-title">Your Signature</div>
                    <?php if (!empty($stu['signature'])): ?>
                        <div class="signature-container">
                            <img src="../uploads/student_signatures/<?= htmlspecialchars($stu['signature']) ?>" alt="Student Signature" class="signature-image">
                        </div>
                    <?php else: ?>
                        <div class="signature-placeholder">
                            <p>No signature uploaded yet.</p>
                            <p><a href="edit_profile.php">Upload your signature</a></p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="profile-actions">
                    <a href="edit_profile.php" class="btn btn-primary">Edit Profile</a>
                    <a href="student_home.php" class="btn btn-secondary">Back to Home</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Back-to-Top Button Functionality
        window.addEventListener('scroll', function() {
            const backToTopBtn = document.getElementById('backToTopBtn');
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
    </script>
</body>

</html>