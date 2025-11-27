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
                            <img src="<?= $avatarPath ?>" alt="Profile Photo" class="profile-photo-enhanced">
                            <div class="profile-photo-overlay"></div>
                        </div>
                        <div class="profile-info-primary">
                            <div class="profile-name-enhanced"><?= $fullName ?></div>
                            <div class="profile-student-id-enhanced"><i class="fas fa-qrcode"></i> <?= htmlspecialchars($stu['student_id']) ?></div>
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
                                <div class="info-value-enhanced"><?= htmlspecialchars($stu['course']) ?></div>
                            </div>
                            <div class="info-item-enhanced">
                                <div class="info-label-enhanced"></i> Year Level</div>
                                <div class="info-value-enhanced"><?= htmlspecialchars($stu['year_level']) ?></div>
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
                                <div class="info-value-enhanced"><?= htmlspecialchars($stu['email']) ?></div>
                            </div>
                            <div class="info-item-enhanced full-width">
                                <div class="info-label-enhanced"></i> Contact Number</div>
                                <div class="info-value-enhanced"><?= htmlspecialchars($stu['contact_number']) ?></div>
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
                                <img src="../uploads/student_signatures/<?= htmlspecialchars($stu['signature']) ?>" alt="Student Signature" class="signature-image-enhanced">
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
            gap: 15px;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #e8e8e8;
            flex-wrap: wrap;
        }

        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 14px 28px;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 700;
            font-size: 1rem;
            border: none;
            cursor: pointer;
            transition: var(--transition);
            letter-spacing: 0.3px;
            flex: 1;
            min-width: 200px;
            max-width: 250px;
        }

        .btn-primary {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-medium) 100%);
            color: white;
            box-shadow: 0 4px 16px rgba(46, 125, 50, 0.3);
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 24px rgba(46, 125, 50, 0.4);
            color: white;
        }

        .btn-secondary {
            background: #f5f5f5;
            color: var(--primary-dark);
            border: 2px solid #ddd;
        }

        .btn-secondary:hover {
            background: rgba(76, 175, 80, 0.1);
            border-color: var(--primary-light);
            transform: translateY(-2px);
            color: var(--primary-dark);
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