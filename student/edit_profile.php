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
$validation_errors = [];
$newPasswordHash = null;

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

    error_log('Edit Profile POST Data: ' . json_encode($data));

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
                // Old password is correct, store the new password hash for later update to users table
                $newPasswordHash = password_hash($new_pwd, PASSWORD_DEFAULT);
                $msg = 'Password changed successfully! ';
            }
        }
    }

    /* Only proceed if no password errors */
    if (empty($error_msg)) {
        /* Validate file uploads before processing */
        $fileUploadErrors = [];

        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $validation = $stuObj->validateFile($_FILES['profile_photo'], [
                'max_size' => 5242880, // 5MB
                'mime_types' => ['image/jpeg', 'image/png'],
                'extensions' => ['jpg', 'jpeg', 'png']
            ]);
            if (!$validation['valid']) {
                $fileUploadErrors['profile_photo'] = $validation['errors'];
            } else {
                try {
                    $data['photo'] = $stuObj->saveUploadedFile($_FILES['profile_photo'], 'student_photos');
                } catch (Throwable $e) {
                    $fileUploadErrors['profile_photo'] = [$e->getMessage()];
                }
            }
        }

        if (isset($_FILES['signature']) && $_FILES['signature']['error'] !== UPLOAD_ERR_NO_FILE) {
            $validation = $stuObj->validateFile($_FILES['signature'], [
                'max_size' => 5242880, // 5MB
                'mime_types' => ['image/jpeg', 'image/png'],
                'extensions' => ['jpg', 'jpeg', 'png']
            ]);
            if (!$validation['valid']) {
                $fileUploadErrors['signature'] = $validation['errors'];
            } else {
                try {
                    $data['signature'] = $stuObj->saveUploadedFile($_FILES['signature'], 'student_signatures');
                } catch (Throwable $e) {
                    $fileUploadErrors['signature'] = [$e->getMessage()];
                }
            }
        }

        if (isset($_FILES['cor_photo']) && $_FILES['cor_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $validation = $stuObj->validateFile($_FILES['cor_photo'], [
                'max_size' => 10485760, // 10MB
                'mime_types' => ['image/jpeg', 'image/png', 'application/pdf'],
                'extensions' => ['jpg', 'jpeg', 'png', 'pdf']
            ]);
            if (!$validation['valid']) {
                $fileUploadErrors['cor_photo'] = $validation['errors'];
            } else {
                try {
                    $data['cor'] = $stuObj->saveUploadedFile($_FILES['cor_photo'], 'student_cor');
                } catch (Throwable $e) {
                    $fileUploadErrors['cor_photo'] = [$e->getMessage()];
                }
            }
        }

        if (!empty($fileUploadErrors)) {
            $error_msg = 'File upload error(s) occurred. Please check your files and try again.';
            $validation_errors = $fileUploadErrors;
        } else {
            // Update student profile
            $result = $stuObj->updateStudent($stu['id'], $data);
            
            if ($result['success']) {
                $msg = $result['message'];
                /* re-read row */
                $stu = $stuObj->findById($stu['id']);
            } else {
                error_log('Edit Profile Error: ' . json_encode($result));
                $error_msg = $result['message'];
                $validation_errors = $result['errors'] ?? [];
            }

            /* Update password in users table if a new password was set */
            if ($newPasswordHash !== null) {
                try {
                    $db = $stuObj->getDb();
                    $stmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE email = :email");
                    $stmt->execute([
                        ':hash' => $newPasswordHash,
                        ':email' => $_SESSION['email']
                    ]);
                } catch (Throwable $e) {
                    error_log('Password update error: ' . $e->getMessage());
                }
            }
        }
    }
}
?>

        <!-- FORM CONTAINER -->
        <div class="form-container">
            <style>
                /* Enhanced Form Styling */
                .form-container {
                    max-width: 1000px;
                    margin: 0 auto;
                    background: white;
                    border-radius: 12px;
                    box-shadow: 0 2px 12px rgba(0, 0, 0, 0.08);
                    overflow: hidden;
                }

                .form-body {
                    padding: 40px;
                }

                .form-section-title {
                    font-size: 1.3rem;
                    font-weight: 700;
                    color: #1b5e20;
                    margin-bottom: 24px;
                    margin-top: 32px;
                    padding-bottom: 12px;
                    border-bottom: 3px solid #2e7d32;
                    display: flex;
                    align-items: center;
                    gap: 12px;
                }

                .form-section-title:first-of-type {
                    margin-top: 0;
                }

                .form-section-title i {
                    color: #2e7d32;
                    font-size: 1.4rem;
                }

                .form-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
                    gap: 24px;
                    margin-bottom: 24px;
                }

                .form-grid.full {
                    grid-template-columns: 1fr;
                }

                .form-group {
                    display: flex;
                    flex-direction: column;
                }

                .form-group label {
                    font-size: 0.95rem;
                    font-weight: 600;
                    color: #333;
                    margin-bottom: 8px;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }

                .form-group.required label::after {
                    content: '*';
                    color: #dc3545;
                    font-weight: bold;
                }

                .form-group input,
                .form-group select,
                .form-group textarea {
                    padding: 12px 14px;
                    border: 1.5px solid #e0e0e0;
                    border-radius: 8px;
                    font-size: 0.95rem;
                    font-family: inherit;
                    transition: all 0.3s ease;
                    background-color: #fafafa;
                }

                .form-group input:focus,
                .form-group select:focus,
                .form-group textarea:focus {
                    outline: none;
                    border-color: #2e7d32;
                    background-color: white;
                    box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
                }

                .form-group input::placeholder,
                .form-group textarea::placeholder {
                    color: #999;
                }

                .form-group textarea {
                    resize: vertical;
                    min-height: 120px;
                }

                /* Password Section */
                .password-section {
                    margin-top: 40px;
                    padding-top: 32px;
                    border-top: 1px solid #e0e0e0;
                }

                .password-toggle-btn {
                    display: inline-flex;
                    align-items: center;
                    gap: 10px;
                    padding: 12px 20px;
                    background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
                    color: white;
                    border: none;
                    border-radius: 8px;
                    font-size: 0.95rem;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s ease;
                }

                .password-toggle-btn:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(46, 125, 50, 0.3);
                }

                .password-box {
                    display: none;
                    margin-top: 24px;
                    padding: 24px;
                    background: #f5f5f5;
                    border-radius: 8px;
                    border-left: 4px solid #ffd600;
                }

                .password-box.active {
                    display: block;
                }

                /* File Upload Section */
                .file-upload-section {
                    margin-top: 40px;
                    padding-top: 32px;
                    border-top: 1px solid #e0e0e0;
                }

                .file-upload-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                    gap: 24px;
                }

                .file-upload-card {
                    border: 2px dashed #e0e0e0;
                    border-radius: 12px;
                    padding: 20px;
                    text-align: center;
                    transition: all 0.3s ease;
                    background: #f9f9f9;
                }

                .file-upload-card:hover {
                    border-color: #2e7d32;
                    background: #fafbf9;
                }

                .file-upload-label {
                    display: block;
                    font-size: 0.95rem;
                    font-weight: 600;
                    color: #333;
                    margin-bottom: 16px;
                }

                .file-status-indicator {
                    display: inline-flex;
                    align-items: center;
                    gap: 6px;
                    background: #e8f5e9;
                    color: #2e7d32;
                    padding: 8px 12px;
                    border-radius: 6px;
                    font-size: 0.85rem;
                    font-weight: 600;
                    margin-bottom: 12px;
                }

                .file-status-indicator::before {
                    content: '✓';
                    font-weight: bold;
                    font-size: 1rem;
                }

                .file-preview-image {
                    max-width: 100%;
                    max-height: 150px;
                    border-radius: 8px;
                    margin: 12px 0;
                    display: block;
                }

                .signature-preview {
                    background: white;
                    border: 1px solid #e0e0e0;
                    padding: 10px;
                }

                .file-name {
                    font-size: 0.85rem;
                    color: #666;
                    margin: 12px 0;
                    word-break: break-all;
                }

                .btn-replace {
                    background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%) !important;
                    color: white !important;
                    border: none !important;
                    padding: 10px 16px !important;
                    border-radius: 6px !important;
                    font-size: 0.85rem !important;
                    font-weight: 600 !important;
                    cursor: pointer !important;
                    transition: all 0.3s ease !important;
                    margin-top: 12px !important;
                    display: inline-block !important;
                    position: relative !important;
                    z-index: 20 !important;
                    pointer-events: auto !important;
                }

                .btn-replace:hover {
                    transform: translateY(-2px);
                    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.3);
                }

                .file-input-wrapper {
                    margin-top: 12px;
                    position: relative;
                    z-index: 10;
                }

                .file-input-wrapper input[type="file"] {
                    display: block;
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 6px;
                    cursor: pointer;
                }

                .file-hint {
                    font-size: 0.8rem;
                    color: #999;
                    margin-top: 8px;
                    font-style: italic;
                }

                /* Action Buttons */
                .form-actions {
                    display: flex;
                    gap: 16px;
                    margin-top: 40px;
                    padding-top: 32px;
                    border-top: 1px solid #e0e0e0;
                    flex-wrap: wrap;
                }

                .btn-primary,
                .btn-secondary {
                    padding: 14px 28px;
                    border: none;
                    border-radius: 8px;
                    font-size: 0.95rem;
                    font-weight: 600;
                    cursor: pointer;
                    transition: all 0.3s ease;
                    text-decoration: none;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 10px;
                }

                .btn-primary {
                    background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
                    color: white;
                }

                .btn-primary:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 6px 20px rgba(46, 125, 50, 0.3);
                }

                .btn-primary:active {
                    transform: translateY(-1px);
                }

                .btn-secondary {
                    background: #f0f0f0;
                    color: #333;
                    border: 2px solid #e0e0e0;
                }

                .btn-secondary:hover {
                    background: #e0e0e0;
                    border-color: #999;
                }

                /* Back to Top Button */
                .back-to-top {
                    position: fixed;
                    bottom: 30px;
                    right: 30px;
                    width: 50px;
                    height: 50px;
                    background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
                    color: white;
                    border: none;
                    border-radius: 50%;
                    font-size: 1.5rem;
                    cursor: pointer;
                    display: none;
                    align-items: center;
                    justify-content: center;
                    z-index: 999;
                    transition: all 0.3s ease;
                    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
                }

                .back-to-top:hover {
                    transform: translateY(-5px);
                    box-shadow: 0 8px 20px rgba(46, 125, 50, 0.4);
                }

                /* Responsive Design */
                @media (max-width: 768px) {
                    .form-body {
                        padding: 24px;
                    }

                    .form-grid {
                        grid-template-columns: 1fr;
                        gap: 18px;
                    }

                    .form-section-title {
                        font-size: 1.1rem;
                        margin-bottom: 18px;
                    }

                    .form-actions {
                        flex-direction: column;
                    }

                    .btn-primary,
                    .btn-secondary {
                        width: 100%;
                    }

                    .file-upload-grid {
                        grid-template-columns: 1fr;
                    }

                    .back-to-top {
                        width: 45px;
                        height: 45px;
                        bottom: 20px;
                        right: 20px;
                        font-size: 1.2rem;
                    }
                }

                @media (max-width: 480px) {
                    .form-body {
                        padding: 16px;
                    }

                    .form-grid {
                        gap: 14px;
                    }

                    .form-group input,
                    .form-group select,
                    .form-group textarea {
                        padding: 10px 12px;
                        font-size: 0.9rem;
                    }

                    .form-section-title {
                        font-size: 1rem;
                        margin: 24px 0 16px 0;
                    }

                    .password-box {
                        padding: 16px;
                    }

                    .btn-primary,
                    .btn-secondary {
                        padding: 12px 20px;
                        font-size: 0.9rem;
                    }
                }

                /* Validation States */
                .form-group input:invalid:not(:placeholder-shown),
                .form-group select:invalid {
                    border-color: #dc3545;
                }

                .form-group input:valid:not(:placeholder-shown),
                .form-group select:valid {
                    border-color: #4caf50;
                }

                /* Loading State */
                .btn-primary:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                    transform: none;
                }

                /* Sidebar Toggle Button Styling */
                .sidebar-toggle {
                    background: none;
                    border: none;
                    color: white;
                    font-size: 1.2rem;
                    cursor: pointer;
                    padding: 8px 10px;
                    border-radius: 6px;
                    transition: all 0.3s ease;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    flex-shrink: 0;
                    position: relative;
                }

                .sidebar-toggle:hover {
                    background: rgba(255, 255, 255, 0.15);
                    transform: scale(1.1);
                }

                .sidebar-toggle:active {
                    background: rgba(255, 255, 255, 0.2);
                    transform: scale(0.95);
                }

                .sidebar-toggle i {
                    transition: transform 0.3s ease;
                }

                /* Collapsed sidebar toggle appearance */
                .admin-sidebar.collapsed .sidebar-toggle {
                    justify-content: center;
                    width: 100%;
                }
            </style>

            <div class="form-body">
                <!-- Alert Messages -->
                <?php if (!empty($msg)): ?>
                    <div style="background: #e8f5e9; border-left: 4px solid #4caf50; padding: 16px; border-radius: 6px; margin-bottom: 24px; color: #2e7d32; display: flex; align-items: flex-start; gap: 12px;">
                        <i class="fas fa-check-circle" style="font-size: 1.2rem; flex-shrink: 0; margin-top: 2px;"></i>
                        <div>
                            <strong>Success:</strong> <?= htmlspecialchars($msg) ?>
                        </div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($error_msg)): ?>
                    <div style="background: #ffebee; border-left: 4px solid #dc3545; padding: 16px; border-radius: 6px; margin-bottom: 24px; color: #c41c3b; display: flex; align-items: flex-start; gap: 12px;">
                        <i class="fas fa-exclamation-circle" style="font-size: 1.2rem; flex-shrink: 0; margin-top: 2px;"></i>
                        <div>
                            <strong>Error:</strong> <?= htmlspecialchars($error_msg) ?>
                            <?php if (!empty($validation_errors)): ?>
                                <ul style="margin: 8px 0 0 0; padding-left: 20px; font-size: 0.9rem;">
                                    <?php foreach ($validation_errors as $field => $errs): ?>
                                        <?php foreach ((array)$errs as $err): ?>
                                            <li><?= htmlspecialchars($err) ?></li>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </ul>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

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
                                <?php
                                    $yearLevels = ['1st Year', '2nd Year', '3rd Year', '4th Year'];
                                    $currentYear = $stu['year_level'] ?? '';
                                    // Normalize stored value to match one of the options
                                    $normalizedYear = $currentYear;
                                    if ($currentYear === '1') $normalizedYear = '1st Year';
                                    elseif ($currentYear === '2') $normalizedYear = '2nd Year';
                                    elseif ($currentYear === '3') $normalizedYear = '3rd Year';
                                    elseif ($currentYear === '4') $normalizedYear = '4th Year';
                                    
                                    foreach ($yearLevels as $yl):
                                        $sel = ($normalizedYear === $yl) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($yl) . '" ' . $sel . '>' . htmlspecialchars($yl) . '</option>';
                                    endforeach;
                                ?>
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
                    <div class="password-section">
                        <button type="button" class="password-toggle-btn" onclick="togglePasswordSection()" id="togglePwdBtn">
                            <span>Change Password</span>
                        </button>
                        <div id="pwdBox" class="password-box">
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
                        </div>
                    </div>

                    <!-- FILE UPLOADS SECTION -->
                    <div class="file-upload-section">
                        <div class="form-section-title">Upload Documents</div>
                        <div class="file-upload-grid">
                            <div class="form-group">
                                <label class="file-upload-label">Profile Photo</label>
                                <?php if (!empty($stu['photo'])): ?>
                                    <div class="file-status-box">
                                        <div class="file-status-indicator">File Uploaded</div>
                                        <img src="../uploads/student_photos/<?= htmlspecialchars($stu['photo']) ?>" alt="Current Photo" class="file-preview-image">
                                        <p class="file-name">Current: <?= htmlspecialchars($stu['photo']) ?></p>
                                        <button type="button" class="btn-replace" style="pointer-events: auto; cursor: pointer;">Replace File</button>
                                        <div class="file-input-wrapper" style="display: none; margin-top: 10px;">
                                            <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png">
                                            <div class="file-hint">JPG, PNG (Max 5MB)</div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="file-input-wrapper">
                                        <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png">
                                        <div class="file-hint">JPG, PNG (Max 5MB)</div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label class="file-upload-label">Signature</label>
                                <?php if (!empty($stu['signature'])): ?>
                                    <div class="file-status-box">
                                        <div class="file-status-indicator">File Uploaded</div>
                                        <img src="../uploads/student_signatures/<?= htmlspecialchars($stu['signature']) ?>" alt="Current Signature" class="file-preview-image signature-preview">
                                        <p class="file-name">Current: <?= htmlspecialchars($stu['signature']) ?></p>
                                        <button type="button" class="btn-replace" style="pointer-events: auto; cursor: pointer;">Replace File</button>
                                        <div class="file-input-wrapper" style="display: none; margin-top: 10px;">
                                            <input type="file" name="signature" accept=".jpg,.jpeg,.png">
                                            <div class="file-hint">JPG, PNG (Max 5MB)</div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="file-input-wrapper">
                                        <input type="file" name="signature" accept=".jpg,.jpeg,.png">
                                        <div class="file-hint">JPG, PNG (Max 5MB)</div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label class="file-upload-label">Certificate of Registration</label>
                                <?php if (!empty($stu['cor'])): ?>
                                    <div class="file-status-box">
                                        <div class="file-status-indicator">File Uploaded</div>
                                        <?php 
                                        $corPath = '../uploads/student_cor/' . htmlspecialchars($stu['cor']);
                                        $corExt = strtolower(pathinfo($stu['cor'], PATHINFO_EXTENSION));
                                        if (in_array($corExt, ['jpg', 'jpeg', 'png', 'gif'])): ?>
                                            <img src="<?= $corPath ?>" alt="Current COR" class="file-preview-image cor-preview">
                                        <?php else: ?>
                                            <div class="file-preview-placeholder">
                                                <div class="file-type"><?= strtoupper($corExt) ?></div>
                                            </div>
                                        <?php endif; ?>
                                        <p class="file-name">Current: <?= htmlspecialchars($stu['cor']) ?></p>
                                        <button type="button" class="btn-replace" style="pointer-events: auto; cursor: pointer;">Replace File</button>
                                        <div class="file-input-wrapper" style="display: none; margin-top: 10px;">
                                            <input type="file" name="cor_photo" accept=".jpg,.jpeg,.png,.pdf">
                                            <div class="file-hint">JPG, PNG, PDF (Max 10MB)</div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="file-input-wrapper">
                                        <input type="file" name="cor_photo" accept=".jpg,.jpeg,.png,.pdf">
                                        <div class="file-hint">JPG, PNG, PDF (Max 10MB)</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="form-actions">
                        <button type="submit" class="btn-primary" onclick="handleSubmit(event)">
                            <i class="fas fa-save"></i>
                            <span>Save Changes</span>
                        </button>
                        <a href="student_profile.php" class="btn-secondary">
                            <i class="fas fa-arrow-left"></i>
                            <span>Back to Profile</span>
                        </a>
                    </div>

                </form>
            </div>
        </div>

            </div>
        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Ensure DOM is ready before attaching event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile sidebar toggle
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            if (mobileMenuBtn) {
                mobileMenuBtn.addEventListener('click', function() {
                    const sidebar = document.getElementById('adminSidebar');
                    const overlay = document.getElementById('sidebarOverlay');

                    if (sidebar) sidebar.classList.add('mobile-open');
                    if (overlay) overlay.classList.add('active');

                    // Prevent body scroll when sidebar is open
                    document.body.style.overflow = 'hidden';
                });
            }

            // Sidebar close button (mobile only)
            const sidebarCloseBtn = document.getElementById('sidebarCloseBtn');
            if (sidebarCloseBtn) {
                sidebarCloseBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const sidebar = document.getElementById('adminSidebar');
                    const overlay = document.getElementById('sidebarOverlay');

                    if (sidebar) sidebar.classList.remove('mobile-open');
                    if (overlay) overlay.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }

            const sidebarOverlay = document.getElementById('sidebarOverlay');
            if (sidebarOverlay) {
                sidebarOverlay.addEventListener('click', function() {
                    const sidebar = document.getElementById('adminSidebar');
                    if (sidebar) sidebar.classList.remove('mobile-open');
                    this.classList.remove('active');
                    document.body.style.overflow = '';
                });
            }

            // Sidebar collapse/expand toggle (desktop only)
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('adminSidebar');
            const main = document.getElementById('adminMain');

            if (sidebarToggle && sidebar && main) {
                // Restore sidebar state from localStorage on page load
                const isCollapsed = localStorage.getItem('sidebarCollapsed') === 'true';
                if (isCollapsed) {
                    sidebar.classList.add('collapsed');
                    main.classList.add('sidebar-collapsed');
                    const icon = sidebarToggle.querySelector('i');
                    if (icon) {
                        icon.className = 'fas fa-chevron-right';
                    }
                }

                sidebarToggle.addEventListener('click', function(e) {
                    e.preventDefault();
                    sidebar.classList.toggle('collapsed');
                    main.classList.toggle('sidebar-collapsed');

                    // Update toggle icon
                    const icon = this.querySelector('i');
                    if (icon) {
                        if (sidebar.classList.contains('collapsed')) {
                            icon.className = 'fas fa-chevron-right';
                        } else {
                            icon.className = 'fas fa-chevron-left';
                        }
                    }

                    // Save state to localStorage
                    localStorage.setItem('sidebarCollapsed', sidebar.classList.contains('collapsed'));
                });
            }

            // Auto-close sidebar on mobile when clicking a link
            document.querySelectorAll('.nav-link').forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth <= 1024) {
                        const sidebar = document.getElementById('adminSidebar');
                        const overlay = document.getElementById('sidebarOverlay');
                        if (sidebar) sidebar.classList.remove('mobile-open');
                        if (overlay) overlay.classList.remove('active');
                        document.body.style.overflow = '';
                    }
                });
            });

            // Close sidebar when window is resized to mobile size
            window.addEventListener('resize', function() {
                if (window.innerWidth <= 1024) {
                    const sidebar = document.getElementById('adminSidebar');
                    const overlay = document.getElementById('sidebarOverlay');
                    if (sidebar) sidebar.classList.remove('mobile-open');
                    if (overlay) overlay.classList.remove('active');
                    document.body.style.overflow = '';
                }
            });
        });
    </script>

    <script>
        // ▬▬▬▬ DRAGGABLE MODAL NOTIFICATION SYSTEM ▬▬▬▬
        function showNotification(message, type = 'info') {
            const iconMap = {
                'success': 'success',
                'warning': 'warning',
                'error': 'error',
                'info': 'info'
            };

            const colorMap = {
                'success': '#4caf50',
                'warning': '#ff9800',
                'error': '#f44336',
                'info': '#2196f3'
            };

            Swal.fire({
                title: type.charAt(0).toUpperCase() + type.slice(1),
                html: message,
                icon: iconMap[type] || 'info',
                confirmButtonColor: colorMap[type] || '#2196f3',
                confirmButtonText: 'OK',
                draggable: true,
                allowOutsideClick: false,
                didOpen: (modal) => {
                    modal.style.borderRadius = '12px';
                    modal.style.boxShadow = '0px 8px 32px rgba(0, 0, 0, 0.2)';
                }
            });
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

            // If validation passes, show success message and submit form after user confirms
            Swal.fire({
                title: 'Success',
                html: 'Form data is complete. Saving...',
                icon: 'success',
                confirmButtonColor: '#4caf50',
                confirmButtonText: 'OK',
                draggable: true,
                allowOutsideClick: false,
                didOpen: (modal) => {
                    modal.style.borderRadius = '12px';
                    modal.style.boxShadow = '0px 8px 32px rgba(0, 0, 0, 0.2)';
                }
            }).then((result) => {
                // Submit form only after user clicks OK
                if (result.isConfirmed) {
                    event.target.closest('form').submit();
                }
            });
        }

        // ▬▬▬▬ PASSWORD TOGGLE ▬▬▬▬
        function togglePasswordSection() {
            const pwdBox = document.getElementById('pwdBox');
            const toggleBtn = document.getElementById('togglePwdBtn');
            pwdBox.classList.toggle('active');
            
            if (pwdBox.classList.contains('active')) {
                toggleBtn.innerHTML = '<i class="fas fa-lock-open"></i><span>Cancel Password Change</span>';
            } else {
                toggleBtn.innerHTML = '<i class="fas fa-lock"></i><span>Change Password</span>';
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

        // Initialize Replace File buttons when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DEBUG: Replace scripts loading (DOMContentLoaded #2)...');
            // Find all Replace File buttons and attach event listeners
            const replaceButtons = document.querySelectorAll('.btn-replace');
            console.log('DEBUG: Found ' + replaceButtons.length + ' replace buttons');
            
            replaceButtons.forEach((button, index) => {
                console.log('DEBUG: Attaching listener to button #' + index + ': ' + button.outerHTML.substring(0,50) + '...');
                button.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    console.log('DEBUG: Button #' + index + ' clicked!');
                    toggleFileInput(this);
                });
            });
        });

        // ▬▬▬▬ TOGGLE FILE INPUT FOR REPLACEMENT ▬▬▬▬
        function toggleFileInput(button) {
            console.log('DEBUG: toggleFileInput called for button:', button.textContent.trim());
            
            // The file-input-wrapper should be right after the button or nearby in the same box
            let fileInputWrapper = null;
            
            // First try: get the next sibling
            let sibling = button.nextElementSibling;
            while (sibling) {
                if (sibling.classList.contains('file-input-wrapper')) {
                    fileInputWrapper = sibling;
                    console.log('DEBUG: ✓ Found wrapper as next sibling');
                    break;
                }
                sibling = sibling.nextElementSibling;
            }
            
            // Second try: look in parent
            if (!fileInputWrapper && button.parentElement) {
                fileInputWrapper = button.parentElement.querySelector('.file-input-wrapper');
                if (fileInputWrapper) {
                    console.log('DEBUG: ✓ Found wrapper in parent element');
                } else {
                    console.log('DEBUG: No wrapper in parent');
                }
            }
            
            // Third try: look in closest form-group
            if (!fileInputWrapper) {
                const formGroup = button.closest('.form-group');
                if (formGroup) {
                    fileInputWrapper = formGroup.querySelector('.file-input-wrapper');
                    if (fileInputWrapper) {
                        console.log('DEBUG: ✓ Found wrapper in form-group');
                    } else {
                        console.log('DEBUG: No wrapper in form-group');
                    }
                } else {
                    console.log('DEBUG: No form-group ancestor');
                }
            }
            
            if (!fileInputWrapper) {
                console.error('DEBUG: ✗ Could not find file input wrapper!');
                console.log('DEBUG: Button HTML:', button.outerHTML);
                console.log('DEBUG: Button parent HTML:', button.parentElement ? button.parentElement.outerHTML.substring(0,200)+'...' : 'No parent');
                console.log('DEBUG: Button closest form-group:', button.closest('.form-group')?.outerHTML.substring(0,200)+'...');
                return;
            }
            
            // Toggle visibility
            const isCurrentlyHidden = fileInputWrapper.style.display === 'none' || fileInputWrapper.style.display === '';
            console.log('DEBUG: Is currently hidden:', isCurrentlyHidden);
            
            if (isCurrentlyHidden) {
                fileInputWrapper.style.display = 'block';
                button.textContent = '✕ Cancel';
                button.style.background = 'linear-gradient(135deg, #f44336 0%, #d32f2f 100%)';
                console.log('DEBUG: ✓ File input shown');
            } else {
                fileInputWrapper.style.display = 'none';
                button.textContent = 'Replace File';
                button.style.background = '';
                const fileInput = fileInputWrapper.querySelector('input[type="file"]');
                if (fileInput) {
                    fileInput.value = '';
                    console.log('DEBUG: ✓ File input cleared');
                }
                console.log('DEBUG: ✓ File input hidden');
            }
        }

        console.log('DEBUG: Pre-pageTitles scripts executed');
        // Update page title based on current page
        const pageTitlesData = {
            'edit_profile.php': {
                title: 'Edit Profile',
                breadcrumb: 'Edit Profile'
            }
        };

        const editCurrentPage = '<?= basename($_SERVER['PHP_SELF']) ?>';
        if (pageTitlesData[editCurrentPage]) {
            const pageTitleEl = document.getElementById('pageTitle');
            const breadcrumbEl = document.getElementById('currentPageBreadcrumb');
            if (pageTitleEl) pageTitleEl.textContent = pageTitlesData[editCurrentPage].title;
            if (breadcrumbEl) breadcrumbEl.textContent = pageTitlesData[editCurrentPage].breadcrumb;
        }
        console.log('DEBUG: pageTitles block executed successfully');
    </script>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>