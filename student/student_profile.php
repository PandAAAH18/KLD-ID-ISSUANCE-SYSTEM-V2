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
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #f0f3f8 100%);
            padding-top: 80px;
            color: #2c3e50;
            line-height: 1.6;
            min-height: 100vh;
        }

        /* ▬▬▬▬ MAIN WRAPPER ▬▬▬▬ */
        .profile-wrapper {
            max-width: 950px;
            margin: 30px auto;
            padding: 0 20px;
        }

        /* ▬▬▬▬ PROFILE CARD ▬▬▬▬ */
        .profile-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 25px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: 1px solid rgba(0, 0, 0, 0.05);
            transition: all 0.4s ease;
            margin-bottom: 30px;
        }

        .profile-container:hover {
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.12);
            transform: translateY(-2px);
        }

        .profile-card-header {
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: white;
            padding: 32px 35px;
            font-size: 18px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
        }

        .profile-card-body {
            padding: 45px 40px;
        }

        /* ▬▬▬▬ PHOTO SECTION ▬▬▬▬ */
        .profile-photo-section {
            text-align: center;
            margin-bottom: 50px;
            padding-bottom: 40px;
            border-bottom: 2px solid #e8e8e8;
        }

        .profile-photo {
            width: 180px;
            height: 180px;
            border-radius: 50%;
            border: 6px solid #1b5e20;
            object-fit: cover;
            display: block;
            margin: 0 auto 20px;
            box-shadow: 0 12px 30px rgba(27, 94, 32, 0.2);
            transition: all 0.3s ease;
        }

        .profile-photo:hover {
            transform: scale(1.05);
            box-shadow: 0 15px 40px rgba(27, 94, 32, 0.3);
        }

        .profile-name {
            font-size: 32px;
            font-weight: 800;
            color: #1b5e20;
            margin-bottom: 8px;
            letter-spacing: -0.5px;
        }

        .profile-student-id {
            font-size: 14px;
            color: #999;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* ▬▬▬▬ INFO GRID ▬▬▬▬ */
        .profile-info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin-bottom: 30px;
        }

        .info-item {
            padding: 20px;
            background: linear-gradient(135deg, #fafbfc 0%, #f5f6f8 100%);
            border-left: 5px solid #1b5e20;
            border-radius: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .info-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 16px rgba(27, 94, 32, 0.1);
            background: linear-gradient(135deg, #f0f8f5 0%, #e8f1ed 100%);
        }

        .info-label {
            font-weight: 700;
            color: #1b5e20;
            font-size: 12px;
            margin-bottom: 8px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            opacity: 0.85;
        }

        .info-value {
            color: #2c3e50;
            font-size: 15px;
            font-weight: 500;
            word-break: break-word;
        }

        /* ▬▬▬▬ ACTION BUTTONS ▬▬▬▬ */
        .profile-actions {
            display: flex;
            gap: 16px;
            justify-content: center;
            padding-top: 30px;
            border-top: 2px solid #e8e8e8;
            margin-top: 40px;
            flex-wrap: wrap;
        }

        .profile-actions a,
        .profile-actions button {
            padding: 12px 32px;
            border: none;
            border-radius: 8px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .profile-actions a.btn-primary {
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: white;
        }

        .profile-actions a.btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 25px rgba(27, 94, 32, 0.35);
        }

        .profile-actions a.btn-secondary {
            background: #f0f0f0;
            color: #2c3e50;
            border: 2px solid #d0d0d0;
        }

        .profile-actions a.btn-secondary:hover {
            background: #e8e8e8;
            border-color: #1b5e20;
            color: #1b5e20;
            transform: translateY(-2px);
        }

        /* ▬▬▬▬ RESPONSIVE ▬▬▬▬ */
        @media (max-width: 768px) {
            .profile-wrapper {
                margin: 20px auto;
            }

            .profile-header {
                padding: 35px 25px;
            }

            .profile-header h2 {
                font-size: 28px;
            }

            .profile-card-body {
                padding: 30px 25px;
            }

            .profile-photo {
                width: 140px;
                height: 140px;
                border-width: 5px;
            }

            .profile-name {
                font-size: 26px;
            }

            .profile-info-grid {
                grid-template-columns: 1fr;
                gap: 16px;
            }

            .profile-actions {
                flex-direction: column;
            }

            .profile-actions a,
            .profile-actions button {
                width: 100%;
            }
        }

        @media (max-width: 576px) {
            body {
                padding-top: 70px;
            }

            .profile-wrapper {
                padding: 0 15px;
            }

            .profile-header {
                padding: 30px 20px;
                margin-bottom: 25px;
            }

            .profile-header h2 {
                font-size: 24px;
            }

            .profile-card-body {
                padding: 20px 15px;
            }

            .profile-photo-section {
                margin-bottom: 30px;
                padding-bottom: 25px;
            }

            .profile-photo {
                width: 120px;
                height: 120px;
            }

            .profile-name {
                font-size: 22px;
            }

            .info-item {
                padding: 15px;
            }

            .profile-actions {
                gap: 10px;
            }

            .profile-actions a,
            .profile-actions button {
                padding: 10px 20px;
                font-size: 13px;
            }
        }

        /* Back-to-Top Button */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 24px;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(27, 94, 32, 0.3);
            z-index: 999;
            transition: all 0.3s ease;
        }

        .back-to-top:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(27, 94, 32, 0.4);
            background: linear-gradient(135deg, #0d3817 0%, #051a0f 100%);
        }

        .back-to-top:active {
            transform: translateY(-2px);
        }

        @media (max-width: 576px) {
            .back-to-top {
                width: 45px;
                height: 45px;
                bottom: 20px;
                right: 20px;
                font-size: 20px;
            }
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

</body>

</html>