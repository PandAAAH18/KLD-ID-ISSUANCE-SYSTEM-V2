<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/student_header.php';
require_once __DIR__.'/student.php';

if (!isset($_SESSION['user_id'], $_SESSION['user_type'], $_SESSION['student_id']) ||
    $_SESSION['user_type'] !== 'student') {
    header('Location: ../login.php'); exit();
}

$stu = (new Student())->findById((int)$_SESSION['student_id']);
if (!$stu) { header('Location: ../login.php'); exit(); }

/* ---- Prepare data ---- */
$avatarPath = $stu['photo'] ? '../uploads/student_photos/'.htmlspecialchars($stu['photo']) : '../uploads/default_avatar.png';
$fullName   = htmlspecialchars($stu['first_name'].' '.$stu['last_name']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f3f3;
            margin: 0;
            padding: 0;
        }

        /* ▬▬▬▬ HEADER ▬▬▬▬ */
        .profile-header {
            width: 95%;
            margin: 10px auto 30px;
            background: #ffffff;
            border-left: 8px solid #1b5e20;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 3px 7px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .profile-header h2 {
            margin: 0;
            color: #1b5e20;
            font-size: 22px;
        }

        /* ▬▬▬▬ PROFILE CARD ▬▬▬▬ */
        .profile-container {
            width: 95%;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0px 3px 7px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .profile-card-header {
            background: #1b5e20;
            color: white;
            padding: 20px;
            font-size: 18px;
            font-weight: bold;
        }

        .profile-card-body {
            padding: 30px;
        }

        .profile-photo-section {
            text-align: center;
            margin-bottom: 30px;
        }

        .profile-photo {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 4px solid #1b5e20;
            object-fit: cover;
            display: block;
            margin: 0 auto;
        }

        .profile-name {
            font-size: 24px;
            font-weight: bold;
            color: #1b5e20;
            margin-top: 15px;
        }

        /* ▬▬▬▬ INFO GRID ▬▬▬▬ */
        .profile-info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .info-item {
            padding: 15px;
            background: #f8f9fa;
            border-left: 4px solid #1b5e20;
            border-radius: 5px;
        }

        .info-label {
            font-weight: bold;
            color: #1b5e20;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .info-value {
            color: #333;
            font-size: 16px;
        }

        /* ▬▬▬▬ ACTION BUTTONS ▬▬▬▬ */
        .profile-actions {
            margin-top: 30px;
            text-align: center;
        }

        .profile-actions a,
        .profile-actions button {
            display: inline-block;
            padding: 10px 20px;
            margin: 5px;
            background: #1b5e20;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            border: none;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.3s;
        }

        .profile-actions a:hover,
        .profile-actions button:hover {
            background: #0d3817;
        }

        @media (max-width: 768px) {
            .profile-info-grid {
                grid-template-columns: 1fr;
            }

            .profile-container,
            .profile-header {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="profile-header">
        <h2>My Profile</h2>
    </div>

    <!-- PROFILE CARD -->
    <div class="profile-container">
        <div class="profile-card-header">
            Student Information
        </div>

        <div class="profile-card-body">
            <!-- Photo Section -->
            <div class="profile-photo-section">
                <img src="<?=$avatarPath?>" alt="Profile Photo" class="profile-photo">
                <div class="profile-name"><?=$fullName?></div>
            </div>

            <!-- Info Grid -->
            <div class="profile-info-grid">
                <div class="info-item">
                    <div class="info-label">Student ID</div>
                    <div class="info-value"><?=htmlspecialchars($stu['student_id'])?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Email</div>
                    <div class="info-value"><?=htmlspecialchars($stu['email'])?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Contact Number</div>
                    <div class="info-value"><?=htmlspecialchars($stu['contact_number'])?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Date of Birth</div>
                    <div class="info-value"><?=htmlspecialchars($stu['dob'] ?? 'N/A')?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Gender</div>
                    <div class="info-value"><?=htmlspecialchars($stu['gender'] ?? 'N/A')?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Blood Type</div>
                    <div class="info-value"><?=htmlspecialchars($stu['blood_type'] ?? 'N/A')?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Course</div>
                    <div class="info-value"><?=htmlspecialchars($stu['course'])?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Year Level</div>
                    <div class="info-value"><?=htmlspecialchars($stu['year_level'])?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Emergency Contact</div>
                    <div class="info-value"><?=htmlspecialchars($stu['emergency_contact'] ?? 'N/A')?></div>
                </div>

                <div class="info-item">
                    <div class="info-label">Address</div>
                    <div class="info-value"><?=htmlspecialchars($stu['address'] ?? 'N/A')?></div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="profile-actions">
                <a href="edit_profile.php" class="btn btn-primary">Edit Profile</a>
                <a href="student_home.php" class="btn btn-secondary">Back to Home</a>
            </div>
        </div>
    </div>

</body>
</html>