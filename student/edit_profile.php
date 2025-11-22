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
    <link href="../assets/css/student.css" rel="stylesheet">
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