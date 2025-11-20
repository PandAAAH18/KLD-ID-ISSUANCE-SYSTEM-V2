<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/student_header.php';
require_once __DIR__.'/student.php';

if (!isset($_SESSION['user_id'], $_SESSION['user_type'], $_SESSION['student_id']) ||
    $_SESSION['user_type'] !== 'student') {
    header('Location: ../index.php'); exit();
}

$stuObj = new Student();
$stu    = $stuObj->findById((int)$_SESSION['student_id']);
if (!$stu) { header('Location: ../index.php'); exit(); }

/* ---------- handle post ---------- */
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* text fields */
    $data = [
        'first_name'         => trim($_POST['first_name']   ?? ''),
        'last_name'          => trim($_POST['last_name']    ?? ''),
        'contact_number'     => trim($_POST['contact_number'] ?? ''),
        'dob'                => trim($_POST['dob']          ?? ''),
        'gender'             => trim($_POST['gender']       ?? ''),
        'course'             => trim($_POST['course']       ?? ''),
        'year_level'         => trim($_POST['year_level']   ?? ''),
        'blood_type'         => trim($_POST['blood_type']   ?? ''),
        'emergency_contact'  => trim($_POST['emergency_contact'] ?? ''),
        'address'            => trim($_POST['address']      ?? ''),
    ];

    /* optional password */
    $pwd = trim($_POST['password'] ?? '');
    if ($pwd !== '') $data['password_hash'] = password_hash($pwd, PASSWORD_DEFAULT);

    /* files */
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK)
        $data['photo'] = $stuObj->saveUploadedFile($_FILES['profile_photo'], 'student_photos');

    if (isset($_FILES['cor_photo']) && $_FILES['cor_photo']['error'] === UPLOAD_ERR_OK)
        $data['cor'] = $stuObj->saveUploadedFile($_FILES['cor_photo'], 'student_cor');

    if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK)
        $data['signature'] = $stuObj->saveUploadedFile($_FILES['signature'], 'student_signatures');

    $stuObj->updateStudent($stu['id'], $data);
    $msg = 'Profile updated successfully!';
    /* re-read row */
    $stu = $stuObj->findById($stu['id']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f3f3;
            margin: 0;
            padding: 0;
            padding-top: 80px;
        }

        /* ▬▬▬▬ ALERT MESSAGE ▬▬▬▬ */
        .alert-message {
            width: 95%;
            margin: 20px auto;
            padding: 15px;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
            border-radius: 5px;
            display: none;
        }

        .alert-message.show {
            display: block;
        }

        /* ▬▬▬▬ FORM CONTAINER ▬▬▬▬ */
        .form-container {
            width: 95%;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0px 3px 7px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .form-section-header {
            background: #1b5e20;
            color: white;
            padding: 20px;
            font-size: 18px;
            font-weight: bold;
        }

        .form-body {
            padding: 30px;
        }

        /* ▬▬▬▬ FORM GROUPS ▬▬▬▬ */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            color: #1b5e20;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: Arial, sans-serif;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1b5e20;
            box-shadow: 0 0 5px rgba(27, 94, 32, 0.3);
        }

        /* ▬▬▬▬ FORM GRID ▬▬▬▬ */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .form-full {
            grid-column: 1 / -1;
        }

        /* ▬▬▬▬ FILE INPUT ▬▬▬▬ */
        .file-input-wrapper {
            border: 2px dashed #1b5e20;
            padding: 15px;
            border-radius: 5px;
            background: #f8f9fa;
            text-align: center;
        }

        .file-input-wrapper input[type="file"] {
            padding: 0;
            border: none;
            background: none;
        }

        .file-input-wrapper input[type="file"]::file-selector-button {
            padding: 8px 15px;
            background: #1b5e20;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }

        .file-input-wrapper input[type="file"]::file-selector-button:hover {
            background: #0d3817;
        }

        /* ▬▬▬▬ BUTTONS ▬▬▬▬ */
        .form-actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn-primary,
        .btn-secondary {
            padding: 12px 30px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }

        .btn-primary {
            background: #1b5e20;
            color: white;
        }

        .btn-primary:hover {
            background: #0d3817;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        .btn-info {
            background: #17a2b8;
            color: white;
            padding: 8px 15px;
            font-size: 14px;
        }

        .btn-info:hover {
            background: #138496;
        }

        /* ▬▬▬▬ PASSWORD SECTION ▬▬▬▬ */
        #pwdBox {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin-top: 10px;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-container,
            .page-header {
                width: 100%;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
            }
        }
    </style>
</head>
<body>

    <!-- ALERT MESSAGE -->
    <?php if ($msg): ?>
    <div class="alert-message show">
        ✓ <?php echo htmlspecialchars($msg); ?>
    </div>
    <?php endif; ?>

    <!-- FORM CONTAINER -->
    <div class="form-container">
        <div class="form-section-header">
            Personal Information
        </div>

        <div class="form-body">
            <form method="post" enctype="multipart/form-data">
                
                <!-- BASIC INFO -->
                <div class="form-grid">
                    <div class="form-group">
                        <label>First Name *</label>
                        <input type="text" name="first_name" value="<?=htmlspecialchars($stu['first_name'])?>" required>
                    </div>

                    <div class="form-group">
                        <label>Last Name *</label>
                        <input type="text" name="last_name" value="<?=htmlspecialchars($stu['last_name'])?>" required>
                    </div>

                    <div class="form-group">
                        <label>Contact Number *</label>
                        <input type="text" name="contact_number" value="<?=htmlspecialchars($stu['contact_number'])?>" required>
                    </div>

                    <div class="form-group">
                        <label>Date of Birth</label>
                        <input type="date" name="dob" value="<?=htmlspecialchars($stu['dob'] ?? '')?>">
                    </div>

                    <div class="form-group">
                        <label>Gender</label>
                        <select name="gender">
                            <option value="">-- Select --</option>
                            <option value="Male" <?=isset($stu['gender']) && $stu['gender']==='Male' ? 'selected' : ''?>>Male</option>
                            <option value="Female" <?=isset($stu['gender']) && $stu['gender']==='Female' ? 'selected' : ''?>>Female</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Blood Type</label>
                        <select name="blood_type">
                            <option value="">-- Select --</option>
                            <?php
                            $bloodOpts = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
                            foreach ($bloodOpts as $bt)
                                echo '<option value="'.htmlspecialchars($bt).'"'
                                   . (isset($stu['blood_type']) && $stu['blood_type']===$bt ? ' selected' : '')
                                   . '>'.htmlspecialchars($bt).'</option>';
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Course *</label>
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
                                echo '<option value="'.htmlspecialchars($c).'" '.$sel.'>'.htmlspecialchars($c).'</option>';
                            endforeach;
                            ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Year Level *</label>
                        <select name="year_level" required>
                            <option value="">-- Select --</option>
                            <option value="1st Year" <?=isset($stu['year_level']) && $stu['year_level']==='1st Year' ? 'selected' : ''?>>1st Year</option>
                            <option value="2nd Year" <?=isset($stu['year_level']) && $stu['year_level']==='2nd Year' ? 'selected' : ''?>>2nd Year</option>
                            <option value="3rd Year" <?=isset($stu['year_level']) && $stu['year_level']==='3rd Year' ? 'selected' : ''?>>3rd Year</option>
                            <option value="4th Year" <?=isset($stu['year_level']) && $stu['year_level']==='4th Year' ? 'selected' : ''?>>4th Year</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Emergency Contact</label>
                        <input type="text" name="emergency_contact" value="<?=htmlspecialchars($stu['emergency_contact'] ?? '')?>">
                    </div>

                    <div class="form-group form-full">
                        <label>Address</label>
                        <textarea name="address" rows="3"><?=htmlspecialchars($stu['address'] ?? '')?></textarea>
                    </div>
                </div>

                <!-- PASSWORD SECTION -->
                <div style="margin-top: 25px; margin-bottom: 25px; border-top: 1px solid #ddd; padding-top: 25px;">
                    <button type="button" class="btn-info" onclick="document.getElementById('pwdBox').style.display='block'; this.disabled=true;">🔐 Change Password</button>
                    <div id="pwdBox" style="display:none; margin-top: 15px;">
                        <div class="form-group">
                            <label>New Password</label>
                            <input type="password" name="password" placeholder="Leave blank to keep current password">
                        </div>
                    </div>
                </div>

                <!-- FILE UPLOADS -->
                <div style="border-top: 1px solid #ddd; padding-top: 25px; margin-top: 25px;">
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Profile Photo</label>
                            <div class="file-input-wrapper">
                                <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png">
                                <p style="margin: 5px 0; color: #666; font-size: 13px;">JPG, PNG (Max 5MB)</p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Certificate of Registration (COR)</label>
                            <div class="file-input-wrapper">
                                <input type="file" name="cor_photo" accept=".jpg,.jpeg,.png,.pdf">
                                <p style="margin: 5px 0; color: #666; font-size: 13px;">JPG, PNG, PDF (Max 10MB)</p>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Signature</label>
                            <div class="file-input-wrapper">
                                <input type="file" name="signature" accept=".jpg,.jpeg,.png">
                                <p style="margin: 5px 0; color: #666; font-size: 13px;">JPG, PNG (Max 5MB)</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ACTION BUTTONS -->
                <div class="form-actions">
                    <button type="submit" class="btn-primary">Save Changes</button>
                    <a href="student_profile.php" class="btn-secondary">Back to Profile</a>
                </div>

            </form>
        </div>
    </div>

</body>
</html>