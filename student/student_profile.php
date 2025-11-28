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
// Avatar handling with better fallback
$avatar = null;
$avatar_initials = '';
$use_initials = false;

if ($stu['photo']) {
    $avatar_path = '../uploads/student_photos/' . htmlspecialchars($stu['photo']);
    if (file_exists($avatar_path)) {
        $avatar = $avatar_path;
    }
}

if (!$avatar) {
    $use_initials = true;
    $first_initial = strtoupper(substr($stu['first_name'] ?? '', 0, 1));
    $last_initial = strtoupper(substr($stu['last_name'] ?? '', 0, 1));
    $avatar_initials = $first_initial . $last_initial;
    if (empty($avatar_initials)) {
        $avatar_initials = 'ST'; // Default for "Student"
    }
}

$fullName   = htmlspecialchars(($stu['first_name'] ?? '') . ' ' . ($stu['last_name'] ?? ''));
?>

<!-- PAGE CONTENT STARTS HERE -->

    <!-- BACK-TO-TOP BUTTON -->
    <button id="backToTopBtn" class="back-to-top" onclick="scrollToTop()" title="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <div class="profile-wrapper">
        <div class="profile-container">
            <!-- PROFILE CARD -->
            <div class="profile-card-enhanced">
                <!-- CARD HEADER -->
                <div class="profile-card-header-enhanced">
                    <i class="fas fa-id-card"></i>
                    <span>Student Information</span>
                </div>

                <!-- CARD BODY -->
                <div class="profile-card-body-enhanced">
                    <!-- Photo Section -->
                    <div class="profile-photo-section-enhanced">
                        <div class="profile-photo-wrapper">
                            <?php if ($use_initials): ?>
                                <div class="profile-photo-enhanced avatar-placeholder">
                                    <i class="fas fa-user"></i>
                                </div>
                            <?php else: ?>
                                <img src="<?= $avatar ?>" alt="Profile Photo" class="profile-photo-enhanced">
                            <?php endif; ?>
                            <div class="profile-photo-overlay"></div>
                        </div>
                        <div class="profile-info-primary">
                            <div class="profile-name-enhanced"><?= $fullName ?></div>
                            <div class="profile-student-id-enhanced"><i class="fas fa-qrcode"></i> <?= htmlspecialchars($stu['student_id'] ?? 'N/A') ?></div>
                        </div>
                    </div>

                    <!-- Info Sections -->
                    <!-- ACADEMIC INFORMATION -->
                    <div class="profile-section-enhanced">
                        <div class="profile-section-title">
                            <span>Academic Information</span>
                        </div>
                        <div class="info-grid-enhanced">
                            <div class="info-item-enhanced">
                                <div class="info-label-enhanced"></i> Course</div>
                                <div class="info-value-enhanced"><?= htmlspecialchars($stu['course'] ?? 'Not Specified') ?></div>
                            </div>
                            <div class="info-item-enhanced">
                                <div class="info-label-enhanced"></i> Year Level</div>
                                <div class="info-value-enhanced"><?= htmlspecialchars($stu['year_level'] ?? 'Not Specified') ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- PERSONAL INFORMATION -->
                    <div class="profile-section-enhanced">
                        <div class="profile-section-title">
                            <span>Personal Information</span>
                        </div>
                        <div class="info-grid-enhanced">
                            <div class="info-item-enhanced">
                                <div class="info-label-enhanced"></i> Date of Birth</div>
                                <div class="info-value-enhanced"><?= htmlspecialchars($stu['dob'] ?? 'N/A') ?></div>
                            </div>
                            <div class="info-item-enhanced">
                                <div class="info-label-enhanced"></i> Gender</div>
                                <div class="info-value-enhanced"><?= htmlspecialchars($stu['gender'] ?? 'N/A') ?></div>
                            </div>
                            <div class="info-item-enhanced">
                                <div class="info-label-enhanced"></i> Blood Type</div>
                                <div class="info-value-enhanced"><?= htmlspecialchars($stu['blood_type'] ?? 'N/A') ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- CONTACT INFORMATION -->
                    <div class="profile-section-enhanced">
                        <div class="profile-section-title">
                            <span>Contact Information</span>
                        </div>
                        <div class="info-grid-enhanced">
                            <div class="info-item-enhanced full-width">
                                <div class="info-label-enhanced"></i> Email</div>
                                <div class="info-value-enhanced"><?= htmlspecialchars($stu['email'] ?? 'Not Provided') ?></div>
                            </div>
                            <div class="info-item-enhanced full-width">
                                <div class="info-label-enhanced"></i> Contact Number</div>
                                <div class="info-value-enhanced"><?= htmlspecialchars($stu['contact_number'] ?? 'Not Provided') ?></div>
                            </div>
                            <div class="info-item-enhanced full-width">
                                <div class="info-label-enhanced"></i> Address</div>
                                <div class="info-value-enhanced"><?= htmlspecialchars($stu['address'] ?? 'N/A') ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- EMERGENCY INFORMATION -->
                    <div class="profile-section-enhanced">
                        <div class="profile-section-title">
                            <span>Emergency Contact</span>
                        </div>
                        <div class="info-grid-enhanced">
                            <div class="info-item-enhanced full-width">
                                <div class="info-label-enhanced"> Contact Name</div>
                                <div class="info-value-enhanced"><?= htmlspecialchars($stu['emergency_contact_name'] ?? 'N/A') ?></div>
                            </div>
                            <div class="info-item-enhanced full-width">
                                <div class="info-label-enhanced"> Contact Number</div>
                                <div class="info-value-enhanced"><?= htmlspecialchars($stu['emergency_contact'] ?? 'N/A') ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- Signature Section -->
                    <div class="profile-section-enhanced signature-section-enhanced">
                        <div class="profile-section-title">
                            <span>Your Signature</span>
                        </div>
                        <?php if (!empty($stu['signature'])): ?>
                            <div class="signature-container-enhanced">
                                <img src="../uploads/student_signatures/<?= htmlspecialchars($stu['signature'] ?? '') ?>" alt="Student Signature" class="signature-image-enhanced">
                                <div class="signature-status">
                                    <span>Signature on file</span>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="signature-placeholder-enhanced">
                                <p>No signature uploaded yet.</p>
                                <a href="edit_profile.php" class="btn-upload-signature">
                                    </i> Upload Signature
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="profile-actions-enhanced">
                        <a href="edit_profile.php" class="btn-action btn-primary">
                            <i class="fas fa-edit"></i>
                            <span>Edit Profile</span>
                        </a>
                        <a href="student_home.php" class="btn-action btn-secondary">
                            <i class="fas fa-home"></i>
                            <span>Back to Home</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ▬▬▬▬ BACK-TO-TOP FUNCTIONALITY ▬▬▬▬
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

        // ▬▬▬▬ ANIMATIONS ON LOAD ▬▬▬▬
        document.addEventListener('DOMContentLoaded', function() {
            // Scroll profile header into view smoothly
            const profileHeader = document.querySelector('.profile-header-enhanced');
            if (profileHeader) {
                profileHeader.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        });
    </script>

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

        /* PROFILE WRAPPER */
        .profile-wrapper {
            max-width: 1200px;
            margin: 0 auto;
            padding: 30px 20px;
        }

        /* ENHANCED HEADER */
        .profile-header-enhanced {
            background: linear-gradient(135deg, white 0%, #f9f9f9 100%);
            border-left: 6px solid var(--primary-dark);
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 35px;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            animation: slideInDown 0.5s ease-out;
        }

        .profile-header-enhanced:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .profile-header-content {
            display: flex;
            align-items: center;
            gap: 25px;
        }

        .profile-header-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-medium) 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 32px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }

        .profile-header-text {
            flex: 1;
        }

        .profile-header-enhanced h1 {
            margin: 0 0 8px 0;
            color: var(--primary-dark);
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .profile-header-enhanced p {
            margin: 0;
            color: #666;
            font-size: 1.05rem;
            line-height: 1.5;
        }

        /* PROFILE CARD */
        .profile-card-enhanced {
            background: white;
            border-radius: 14px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            transition: var(--transition);
            animation: fadeIn 0.5s ease-out;
        }

        .profile-card-enhanced:hover {
            box-shadow: var(--shadow-lg);
        }

        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* CARD HEADER */
        .profile-card-header-enhanced {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-medium) 100%);
            color: white;
            padding: 25px 30px;
            font-size: 18px;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 15px;
            letter-spacing: 0.3px;
        }

        .profile-card-header-enhanced i {
            font-size: 22px;
        }

        /* CARD BODY */
        .profile-card-body-enhanced {
            padding: 35px;
        }

        /* PHOTO SECTION */
        .profile-photo-section-enhanced {
            display: flex;
            align-items: center;
            gap: 30px;
            margin-bottom: 40px;
            padding-bottom: 40px;
            border-bottom: 2px solid #e8e8e8;
        }

        .profile-photo-wrapper {
            position: relative;
            flex-shrink: 0;
        }

        .profile-photo-enhanced {
            width: 150px;
            height: 150px;
            border-radius: 14px;
            object-fit: cover;
            border: 4px solid var(--primary-light);
            box-shadow: 0 8px 20px rgba(76, 175, 80, 0.2);
            transition: var(--transition);
        }

        .profile-photo-enhanced.avatar-placeholder {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-light);
            font-size: 60px;
            border: 4px solid var(--primary-light);
        }

        .profile-photo-enhanced.avatar-placeholder:hover {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-medium) 100%);
            color: white;
        }

        .profile-photo-enhanced:hover {
            transform: scale(1.05);
            box-shadow: 0 12px 28px rgba(76, 175, 80, 0.3);
        }

        .profile-photo-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(76, 175, 80, 0.1);
            border-radius: 14px;
            opacity: 0;
            transition: var(--transition);
        }

        .profile-photo-wrapper:hover .profile-photo-overlay {
            opacity: 1;
        }

        .profile-info-primary {
            flex: 1;
        }

        .profile-name-enhanced {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0 0 8px 0;
            letter-spacing: 0.5px;
        }

        .profile-student-id-enhanced {
            font-size: 1.1rem;
            color: var(--accent-orange);
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* SECTIONS */
        .profile-section-enhanced {
            margin-bottom: 35px;
            animation: fadeIn 0.6s ease-out;
        }

        .profile-section-title {
            font-size: 1.2rem;
            font-weight: 700;
            color: var(--primary-dark);
            margin: 0 0 20px 0;
            padding-bottom: 12px;
            border-bottom: 3px solid var(--primary-light);
            display: flex;
            align-items: center;
            gap: 12px;
            letter-spacing: 0.2px;
        }

        .profile-section-title i {
            color: var(--accent-orange);
            font-size: 1.2rem;
        }

        /* INFO GRID */
        .info-grid-enhanced {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .info-grid-enhanced.single {
            grid-template-columns: 1fr;
        }

        /* INFO ITEM */
        .info-item-enhanced {
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f0f0 100%);
            padding: 18px;
            border-radius: 10px;
            border-left: 4px solid var(--primary-light);
            transition: var(--transition);
        }

        .info-item-enhanced:hover {
            background: white;
            box-shadow: var(--shadow-md);
            transform: translateY(-3px);
            border-left-color: var(--primary-light);
        }

        .info-item-enhanced.full-width {
            grid-column: 1 / -1;
        }

        .info-label-enhanced {
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .info-label-enhanced i {
            color: var(--accent-orange);
            font-size: 1rem;
        }

        .info-value-enhanced {
            font-size: 1.1rem;
            color: #333;
            font-weight: 600;
            line-height: 1.6;
            word-break: break-word;
        }

        /* SIGNATURE SECTION */
        .signature-section-enhanced {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.05) 0%, rgba(76, 175, 80, 0.02) 100%);
            padding: 30px;
            border: 2px solid var(--primary-light);
            border-radius: 12px;
        }

        .signature-container-enhanced {
            background: white;
            border-radius: 10px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
            box-shadow: var(--shadow-md);
        }

        .signature-image-enhanced {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            border: 2px solid #ddd;
            transition: var(--transition);
        }

        .signature-image-enhanced:hover {
            transform: scale(1.02);
            box-shadow: 0 12px 28px rgba(76, 175, 80, 0.3);
        }

        .signature-status {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--primary-light);
            font-weight: 700;
            font-size: 0.95rem;
        }

        .signature-placeholder-enhanced {
            background: white;
            border: 3px dashed var(--primary-light);
            border-radius: 12px;
            padding: 40px 30px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .signature-placeholder-enhanced i {
            font-size: 48px;
            color: var(--primary-light);
        }

        .signature-placeholder-enhanced p {
            color: #666;
            margin: 0;
            font-size: 1.05rem;
            font-weight: 600;
        }

        .btn-upload-signature {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: linear-gradient(135deg, var(--accent-orange) 0%, var(--accent-orange-dark) 100%);
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
        }

        .btn-upload-signature:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.4);
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-medium) 100%);
            color: white;
        }

        /* ACTION BUTTONS */
        .profile-actions-enhanced {
            display: flex;
            gap: 20px;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 2px solid #e0e0e0;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 16px 32px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1.05rem;
            border: none;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            letter-spacing: 0.5px;
            flex: 1;
            min-width: 220px;
            max-width: 280px;
            position: relative;
            overflow: hidden;
        }

        .btn-action::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn-action:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-action i {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .btn-action:hover i {
            transform: scale(1.2) rotate(5deg);
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-medium) 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(46, 125, 50, 0.25), 0 2px 8px rgba(46, 125, 50, 0.15);
            border: 2px solid transparent;
        }

        .btn-primary:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(46, 125, 50, 0.35), 0 4px 12px rgba(46, 125, 50, 0.2);
            color: white;
            background: linear-gradient(135deg, var(--primary-medium) 0%, var(--primary-dark) 100%);
        }

        .btn-primary:active {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(46, 125, 50, 0.3);
        }

        .btn-secondary {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            color: var(--primary-dark);
            border: 2px solid var(--primary-light);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .btn-secondary:hover {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(76, 175, 80, 0.05) 100%);
            border-color: var(--primary-medium);
            transform: translateY(-4px);
            color: var(--primary-dark);
            box-shadow: 0 8px 24px rgba(76, 175, 80, 0.2);
        }

        .btn-secondary:active {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        /* BACK TO TOP */
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
            font-size: 20px;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            z-index: 1000;
        }

        .back-to-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 32px rgba(27, 94, 32, 0.4);
        }

        /* RESPONSIVE DESIGN */
        @media (max-width: 768px) {
            .profile-wrapper {
                padding: 15px;
            }

            .profile-header-content {
                flex-direction: column;
                text-align: center;
            }

            .profile-header-icon {
                width: 60px;
                height: 60px;
                font-size: 28px;
            }

            .profile-header-enhanced h1 {
                font-size: 1.6rem;
            }

            .profile-photo-section-enhanced {
                flex-direction: column;
                text-align: center;
                margin-bottom: 30px;
            }

            .profile-name-enhanced {
                font-size: 1.5rem;
            }

            .info-grid-enhanced {
                grid-template-columns: 1fr;
                gap: 15px;
            }

            .profile-card-body-enhanced {
                padding: 20px;
            }

            .profile-card-header-enhanced {
                padding: 20px;
                font-size: 16px;
            }

            .btn-action {
                width: 100%;
                max-width: 100%;
                flex: 1 1 100%;
            }

            .profile-actions-enhanced {
                flex-direction: column;
                gap: 12px;
            }

            .back-to-top {
                bottom: 20px;
                right: 20px;
                width: 45px;
                height: 45px;
                font-size: 18px;
            }
        }

        @media (max-width: 480px) {
            .profile-header-enhanced {
                padding: 20px;
                margin-bottom: 20px;
            }

            .profile-header-enhanced h1 {
                font-size: 1.3rem;
            }

            .profile-header-enhanced p {
                font-size: 0.9rem;
            }

            .profile-photo-enhanced {
                width: 120px;
                height: 120px;
            }

            .profile-name-enhanced {
                font-size: 1.2rem;
            }

            .profile-section-title {
                font-size: 1rem;
                margin: 20px 0 15px 0;
            }

            .profile-card-body-enhanced {
                padding: 15px;
            }

            .info-item-enhanced {
                padding: 15px;
            }

            .signature-section-enhanced {
                padding: 20px;
            }
        }
    </style>
            </div><!-- End admin-content -->
        </main><!-- End admin-main -->
    </div><!-- End admin-wrapper -->
</body>

</html>