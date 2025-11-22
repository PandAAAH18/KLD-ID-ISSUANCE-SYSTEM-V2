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

$stuObj = new Student();
$stu    = $stuObj->findById((int)$_SESSION['student_id']);
if (!$stu) {
    header('Location: ../index.php');
    exit();
}

/* ---------- handle post ---------- */
$msg = '';
$error_msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* text fields */
    $data = [
        'first_name'              => trim($_POST['first_name']   ?? ''),
        'last_name'               => trim($_POST['last_name']    ?? ''),
        'contact_number'          => trim($_POST['contact_number'] ?? ''),
        'dob'                     => trim($_POST['dob']          ?? ''),
        'gender'                  => trim($_POST['gender']       ?? ''),
        'course'                  => trim($_POST['course']       ?? ''),
        'year_level'              => trim($_POST['year_level']   ?? ''),
        'blood_type'              => trim($_POST['blood_type']   ?? ''),
        'emergency_contact_name'  => trim($_POST['emergency_contact_name'] ?? ''),
        'emergency_contact'       => trim($_POST['emergency_contact'] ?? ''),
        'address'                 => trim($_POST['address']      ?? ''),
    ];

    /* password change handling */
    $old_pwd = trim($_POST['old_password'] ?? '');
    $new_pwd = trim($_POST['new_password'] ?? '');
    $confirm_pwd = trim($_POST['confirm_password'] ?? '');

    if ($old_pwd !== '' || $new_pwd !== '' || $confirm_pwd !== '') {
        // If any password field is filled, all are required
        if (empty($old_pwd)) {
            $error_msg = 'Please enter your current password.';
        } elseif (empty($new_pwd)) {
            $error_msg = 'Please enter a new password.';
        } elseif (empty($confirm_pwd)) {
            $error_msg = 'Please confirm your new password.';
        } elseif ($new_pwd !== $confirm_pwd) {
            $error_msg = 'New password and confirmation do not match.';
        } elseif (strlen($new_pwd) < 8) {
            $error_msg = 'New password must be at least 8 characters long.';
        } else {
            // Verify old password
            $user = $stuObj->findByEmail($_SESSION['email']);
            if (!$user || !password_verify($old_pwd, $user['password_hash'])) {
                $error_msg = 'Current password is incorrect.';
            } else {
                // Old password is correct, set new password
                $data['password_hash'] = password_hash($new_pwd, PASSWORD_DEFAULT);
                $msg = 'Password changed successfully! ';
            }
        }
    }

    /* Only proceed if no password errors */
    if (empty($error_msg)) {
        /* files */
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK)
            $data['photo'] = $stuObj->saveUploadedFile($_FILES['profile_photo'], 'student_photos');

        if (isset($_FILES['cor_photo']) && $_FILES['cor_photo']['error'] === UPLOAD_ERR_OK)
            $data['cor'] = $stuObj->saveUploadedFile($_FILES['cor_photo'], 'student_cor');

        if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK)
            $data['signature'] = $stuObj->saveUploadedFile($_FILES['signature'], 'student_signatures');

        $stuObj->updateStudent($stu['id'], $data);

        /* Update password in users table if changed */
        if (isset($data['password_hash'])) {
            $db = $stuObj->getDb();
            $stmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE email = :email");
            $stmt->execute([
                ':hash' => $data['password_hash'],
                ':email' => $_SESSION['email']
            ]);
        }

        if (!$msg) $msg = 'Profile updated successfully!';
        /* re-read row */
        $stu = $stuObj->findById($stu['id']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f3f3f3;
            margin: 0;
            padding: 0;
            padding-top: 80px;
        }

        /* ▬▬▬▬ MAIN WRAPPER ▬▬▬▬ */
        .profile-wrapper {
            width: 95%;
            max-width: 1000px;
            margin: 30px auto;
        }

        /* ▬▬▬▬ PAGE HEADER ▬▬▬▬ */
        .edit-header {
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: white;
            padding: 30px 30px;
            border-radius: 12px;
            margin-top: 95px;
            margin-bottom: 40px;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .edit-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: -50px;
            width: 200px;
            height: 200px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 50%;
        }

        .edit-header h1 {
            margin: 0;
            font-size: 36px;
            font-weight: 800;
            letter-spacing: -0.5px;
        }

        .edit-header p {
            margin: 10px 0 0 0;
            font-size: 15px;
            opacity: 0.9;
        }

        /* ▬▬▬▬ ALERT MESSAGE ▬▬▬▬ */
        .alert-banner {
            padding: 18px 24px;
            border-radius: 8px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 15px;
            font-weight: 700;
            animation: slideDown 0.5s ease-out;
            border-left: 5px solid;
            display: none;
        }

        .alert-banner.show {
            display: flex;
        }

        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .alert-success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border-left-color: #dc3545;
            color: #721c24;
        }

        /* ▬▬▬▬ FORM CONTAINER ▬▬▬▬ */
        .form-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.08);
            overflow: hidden;
            border: 1px solid #f0f0f0;
            transition: all 0.3s ease;
        }

        .form-container:hover {
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }

        .form-section-header {
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: white;
            padding: 20px 30px;
            font-size: 18px;
            font-weight: 800;
            letter-spacing: -0.3px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .form-body {
            padding: 25px 30px;
        }

        /* ▬▬▬▬ SECTION TITLE ▬▬▬▬ */
        .form-section-title {
            font-size: 16px;
            font-weight: 800;
            color: #1b5e20;
            margin-top: 30px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid #b69b04;
            letter-spacing: -0.2px;
        }

        .form-section-title:first-child {
            margin-top: 0;
        }

        /* ▬▬▬▬ FORM GROUPS ▬▬▬▬ */
        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            font-weight: 700;
            color: #333;
            margin-bottom: 10px;
            font-size: 14px;
            letter-spacing: -0.2px;
        }

        .form-group.required label::after {
            content: ' *';
            color: #dc3545;
            font-weight: 900;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 13px 16px;
            border: 1.5px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            box-sizing: border-box;
            transition: all 0.3s ease;
            background: #fafafa;
        }

        .form-group input::placeholder,
        .form-group textarea::placeholder {
            color: #999;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1b5e20;
            box-shadow: 0 0 12px rgba(27, 94, 32, 0.15);
            background: white;
        }

        /* ▬▬▬▬ FORM GRID ▬▬▬▬ */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-grid.full {
            grid-template-columns: 1fr;
        }

        /* ▬▬▬▬ FILE INPUT ▬▬▬▬ */
        .file-input-wrapper {
            border: 2px dashed #1b5e20;
            padding: 20px;
            border-radius: 8px;
            background: #f8f9fa;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .file-input-wrapper:hover {
            background: #f0f8f5;
            border-color: #0d3817;
        }

        .file-input-wrapper input[type="file"] {
            padding: 0;
            border: none;
            background: none;
            cursor: pointer;
        }

        .file-input-wrapper input[type="file"]::file-selector-button {
            padding: 10px 20px;
            background: #1b5e20;
            color: white;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 700;
            transition: all 0.3s;
            font-size: 14px;
        }

        .file-input-wrapper input[type="file"]::file-selector-button:hover {
            background: #0d3817;
            transform: translateY(-2px);
        }

        .file-hint {
            font-size: 13px;
            color: #999;
            margin-top: 8px;
        }

        /* ▬▬▬▬ BUTTONS ▬▬▬▬ */
        .form-actions {
            margin-top: 40px;
            display: flex;
            gap: 15px;
            justify-content: center;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }

        .btn-primary,
        .btn-secondary {
            padding: 13px 40px;
            font-size: 15px;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
            letter-spacing: -0.2px;
        }

        .btn-primary {
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(27, 94, 32, 0.2);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(27, 94, 32, 0.3);
        }

        .btn-secondary {
            background: #f0f0f0;
            color: #333;
            border: 1.5px solid #ddd;
        }

        .btn-secondary:hover {
            background: #e8e8e8;
            border-color: #1b5e20;
            color: #1b5e20;
            transform: translateY(-2px);
        }

        .btn-info {
            background: #1b5e20;
            color: white;
            padding: 10px 18px;
            font-size: 14px;
            font-weight: 700;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-info:hover {
            background: #0d3817;
            transform: translateY(-2px);
        }

        /* ▬▬▬▬ PASSWORD SECTION ▬▬▬▬ */
        #pwdBox {
            background: linear-gradient(135deg, #fff9e6 0%, #fffbf0 100%);
            border: 1.5px solid #ffc107;
            padding: 20px;
            border-radius: 8px;
            margin-top: 15px;
            border-left: 4px solid #ffc107;
        }

        /* ▬▬▬▬ RESPONSIVE ▬▬▬▬ */
        @media (max-width: 768px) {
            .profile-wrapper {
                width: 100%;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-body {
                padding: 25px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
            }

            .edit-header {
                padding: 35px 20px;
            }

            .edit-header h1 {
                font-size: 28px;
            }
        }

        @media (max-width: 576px) {
            .form-body {
                padding: 20px;
            }

            .edit-header h1 {
                font-size: 24px;
            }

            .form-section-header {
                font-size: 16px;
                padding: 20px;
            }
        }

        /* ▬▬▬▬ FLOATING NOTIFICATION ▬▬▬▬ */
        .floating-notification {
            position: fixed;
            top: 100px;
            right: 20px;
            max-width: 400px;
            padding: 16px 20px;
            border-radius: 10px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            font-size: 14px;
            font-weight: 600;
            letter-spacing: 0.2px;
            animation: slideInRight 2.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 12px;
            backdrop-filter: blur(10px);
            border: 1px solid;
        }

        .floating-notification.warning {
            background: linear-gradient(135deg, #fff8e1 0%, #fff3cd 100%);
            color: #856404;
            border-color: #ffe69c;
        }

        .floating-notification.error {
            background: linear-gradient(135deg, #ffe5e5 0%, #ffcccb 100%);
            color: #721c24;
            border-color: #ff7b7b;
        }

        .floating-notification.success {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            color: #155724;
            border-color: #81c784;
        }

        .floating-notification.info {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            color: #0c5460;
            border-color: #81c784;
        }

        .floating-notification::before {
            content: '';
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: currentColor;
            flex-shrink: 0;
            display: inline-block;
        }

        .floating-notification.warning::before {
            content: '⚠️';
            width: auto;
            height: auto;
        }

        .floating-notification.error::before {
            content: '✕';
            width: auto;
            height: auto;
            font-weight: 800;
            font-size: 16px;
        }

        .floating-notification.success::before {
            content: '✓';
            width: auto;
            height: auto;
            font-weight: 800;
            font-size: 16px;
        }

        .floating-notification.info::before {
            content: 'ℹ';
            width: auto;
            height: auto;
            font-weight: 800;
            font-size: 16px;
        }

        @keyframes slideInRight {
            0% {
                opacity: 0;
                transform: translateX(400px);
            }
            100% {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes slideOutRight {
            0% {
                opacity: 1;
                transform: translateX(0);
            }
            100% {
                opacity: 0;
                transform: translateX(400px);
            }
        }

        .floating-notification.hide {
            animation: slideOutRight 2.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }

        @media (max-width: 768px) {
            .floating-notification {
                top: 90px;
                right: 12px;
                left: 12px;
                max-width: none;
            }
        }

        @media (max-width: 480px) {
            .floating-notification {
                top: 80px;
                right: 8px;
                left: 8px;
                max-width: none;
                padding: 14px 16px;
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

<body class="admin-body">

    <!-- BACK-TO-TOP BUTTON -->
    <button id="backToTopBtn" class="back-to-top" onclick="scrollToTop()">↑</button>

    <!-- PAGE HEADER -->
    <div class="profile-wrapper">
        <div class="edit-header">
            <h1>Edit Your Profile</h1>
            <p>Keep your information up-to-date and accurate</p>
        </div>

        <!-- FORM CONTAINER -->
        <div class="form-container">
            <div class="form-section-header">
                Personal Information
            </div>

            <div class="form-body">
                <form method="post" enctype="multipart/form-data">

                    <!-- BASIC INFO SECTION -->
                    <div class="form-section-title">Basic Information</div>
                    <div class="form-grid">
                        <div class="form-group required">
                            <label>First Name</label>
                            <input type="text" name="first_name" value="<?= htmlspecialchars($stu['first_name']) ?>" required>
                        </div>

                        <div class="form-group required">
                            <label>Last Name</label>
                            <input type="text" name="last_name" value="<?= htmlspecialchars($stu['last_name']) ?>" required>
                        </div>

                        <div class="form-group required">
                            <label>Contact Number</label>
                            <input type="text" name="contact_number" value="<?= htmlspecialchars($stu['contact_number']) ?>" required>
                        </div>

                        <div class="form-group">
                            <label>Date of Birth</label>
                            <input type="date" name="dob" value="<?= htmlspecialchars($stu['dob'] ?? '') ?>">
                        </div>

                        <div class="form-group">
                            <label>Gender</label>
                            <select name="gender">
                                <option value="">-- Select --</option>
                                <option value="Male" <?= isset($stu['gender']) && $stu['gender'] === 'Male' ? 'selected' : '' ?>>Male</option>
                                <option value="Female" <?= isset($stu['gender']) && $stu['gender'] === 'Female' ? 'selected' : '' ?>>Female</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Blood Type</label>
                            <select name="blood_type">
                                <option value="">-- Select --</option>
                                <?php
                                $bloodOpts = ['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'];
                                foreach ($bloodOpts as $bt)
                                    echo '<option value="' . htmlspecialchars($bt) . '"'
                                        . (isset($stu['blood_type']) && $stu['blood_type'] === $bt ? ' selected' : '')
                                        . '>' . htmlspecialchars($bt) . '</option>';
                                ?>
                            </select>
                        </div>
                    </div>

                    <!-- ACADEMIC INFO SECTION -->
                    <div class="form-section-title">Academic Information</div>
                    <div class="form-grid">
                        <div class="form-group required">
                            <label>Course</label>
                            <select name="course" required>
                                <option value="">-- Select --</option>
                                <?php
                                $courses = [
                                    'BS Information System',
                                    'BS Computer Science',
                                    'BS Engineering',
                                    'BS Psychology',
                                    'BS Nursing',
                                    'BS Midwifery'
                                ];
                                foreach ($courses as $c):
                                    $sel = (isset($stu['course']) && $stu['course'] === $c) ? 'selected' : '';
                                    echo '<option value="' . htmlspecialchars($c) . '" ' . $sel . '>' . htmlspecialchars($c) . '</option>';
                                endforeach;
                                ?>
                            </select>
                        </div>

                        <div class="form-group required">
                            <label>Year Level</label>
                            <select name="year_level" required>
                                <option value="">-- Select --</option>
                                <option value="1st Year" <?= isset($stu['year_level']) && $stu['year_level'] === '1st Year' ? 'selected' : '' ?>>1st Year</option>
                                <option value="2nd Year" <?= isset($stu['year_level']) && $stu['year_level'] === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                                <option value="3rd Year" <?= isset($stu['year_level']) && $stu['year_level'] === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                                <option value="4th Year" <?= isset($stu['year_level']) && $stu['year_level'] === '4th Year' ? 'selected' : '' ?>>4th Year</option>
                            </select>
                        </div>
                    </div>

                    <!-- CONTACT INFO SECTION -->
                    <div class="form-section-title">Contact & Emergency</div>
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Emergency Contact Name</label>
                            <input type="text" name="emergency_contact_name" value="<?= htmlspecialchars($stu['emergency_contact_name'] ?? '') ?>" placeholder="Full name">
                        </div>
                        <div class="form-group">
                            <label>Emergency Contact Number</label>
                            <input type="text" name="emergency_contact" value="<?= htmlspecialchars($stu['emergency_contact'] ?? '') ?>" placeholder="Phone number">
                        </div>
                    </div>

                    <div class="form-grid full">
                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" rows="4" placeholder="Your complete address"><?= htmlspecialchars($stu['address'] ?? '') ?></textarea>
                        </div>
                    </div>

                    <!-- PASSWORD SECTION -->
                    <div style="margin-top: 30px; margin-bottom: 30px; border-top: 1px solid #eee; padding-top: 30px;">
                        <button type="button" class="btn-info" onclick="togglePasswordSection()" id="togglePwdBtn">🔐 Change Password</button>
                        <div id="pwdBox" style="display:none; margin-top: 20px;">
                            <div class="form-grid">
                                <div class="form-group required">
                                    <label>Current Password</label>
                                    <input type="password" name="old_password" id="old_password" placeholder="Enter your current password">
                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="form-group required">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" id="new_password" placeholder="Minimum 8 characters">
                                </div>
                                <div class="form-group required">
                                    <label>Confirm New Password</label>
                                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter new password">
                                </div>
                            </div>

                            <div style="display: flex; gap: 10px; margin-top: 15px;">
                                <button type="button" class="btn-info" onclick="togglePasswordSection()" style="background: #999;">Cancel</button>
                            </div>
                        </div>
                    </div>

                    <!-- FILE UPLOADS SECTION -->
                    <div style="border-top: 1px solid #eee; padding-top: 30px; margin-top: 30px;">
                        <div class="form-section-title">Upload Documents</div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label>Profile Photo</label>
                                <div class="file-input-wrapper">
                                    <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png">
                                    <div class="file-hint">JPG, PNG (Max 5MB)</div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Certificate of Registration</label>
                                <div class="file-input-wrapper">
                                    <input type="file" name="cor_photo" accept=".jpg,.jpeg,.png,.pdf">
                                    <div class="file-hint">JPG, PNG, PDF (Max 10MB)</div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Signature</label>
                                <div class="file-input-wrapper">
                                    <input type="file" name="signature" accept=".jpg,.jpeg,.png">
                                    <div class="file-hint">JPG, PNG (Max 5MB)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="form-actions">
                        <button type="submit" class="btn-primary" onclick="handleSubmit(event)">Save Changes</button>
                        <a href="student_profile.php" class="btn-secondary">Back to Profile</a>
                    </div>

                </form>
            </div>
        </div>
    </div>

    <script>
        // ▬▬▬▬ FLOATING NOTIFICATION SYSTEM ▬▬▬▬
        function showNotification(message, type = 'info') {
            const notification = document.createElement('div');
            notification.className = `floating-notification ${type}`;
            notification.textContent = message;
            document.body.appendChild(notification);

            // Display for 5 seconds total: 2.5s slide-in + 2.5s slide-out
            setTimeout(() => {
                notification.classList.add('hide');
                setTimeout(() => notification.remove(), 5000);
            }, 5000);
        }

        // ▬▬▬▬ FORM VALIDATION ▬▬▬▬
        function validateForm() {
            const firstName = document.querySelector('input[name="first_name"]').value.trim();
            const lastName = document.querySelector('input[name="last_name"]').value.trim();
            const contactNumber = document.querySelector('input[name="contact_number"]').value.trim();
            const course = document.querySelector('select[name="course"]').value.trim();
            const yearLevel = document.querySelector('select[name="year_level"]').value.trim();

            const errors = [];

            if (!firstName) {
                errors.push('First name is required');
            }

            if (!lastName) {
                errors.push('Last name is required');
            }

            if (!contactNumber) {
                errors.push('Contact number is required');
            } else if (!/^[0-9\s\-\+\(\)]{10,}$/.test(contactNumber)) {
                errors.push('Contact number must be valid (at least 10 digits)');
            }

            if (!course) {
                errors.push('Course selection is required');
            }

            if (!yearLevel) {
                errors.push('Year level selection is required');
            }

            return errors;
        }

        // ▬▬▬▬ FORM SUBMIT HANDLER ▬▬▬▬
        function handleSubmit(event) {
            event.preventDefault();
            const errors = validateForm();

            if (errors.length > 0) {
                // Show error notifications for each missing field
                errors.forEach((error, index) => {
                    setTimeout(() => {
                        showNotification(error, 'warning');
                    }, index * 500);
                });

                return false;
            }

            // If validation passes, show success message and submit form
            showNotification('Form data is complete. Saving...', 'success');
            
            // Submit form after showing the notification
            setTimeout(() => {
                event.target.closest('form').submit();
            }, 500);
        }

        // ▬▬▬▬ PASSWORD TOGGLE ▬▬▬▬
        function togglePasswordSection() {
            const pwdBox = document.getElementById('pwdBox');
            const toggleBtn = document.getElementById('togglePwdBtn');
            if (pwdBox.style.display === 'none') {
                pwdBox.style.display = 'block';
                toggleBtn.textContent = '✕ Cancel Password Change';
            } else {
                pwdBox.style.display = 'none';
                toggleBtn.textContent = '🔐 Change Password';
                // Clear password fields
                document.getElementById('old_password').value = '';
                document.getElementById('new_password').value = '';
                document.getElementById('confirm_password').value = '';
            }
        }

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
    </script>

</html>