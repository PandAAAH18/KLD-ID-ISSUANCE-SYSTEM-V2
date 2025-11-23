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

<!-- PAGE CONTENT STARTS HERE -->

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

                <!-- Action Buttons -->
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
            </div><!-- End admin-content -->
        </main><!-- End admin-main -->
    </div><!-- End admin-wrapper -->
</body>

</html>