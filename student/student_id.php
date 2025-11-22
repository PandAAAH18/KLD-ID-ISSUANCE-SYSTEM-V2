<?php
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/student_header.php';
require_once __DIR__ . '/student.php';

/* ----- 1. auth ----- */
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

$stuObj = new Student();
$student = $stuObj->findById($_SESSION['student_id']);   // returns full row
if (!$student) {
    header('Location: ../index.php');
    exit();
}

/* ----- 2. profile completeness check ---- */
$required = [
    'student_id',
    'email',
    'first_name',
    'last_name',
    'year_level',
    'course',
    'contact_number',
    'address',
    'photo',
    'emergency_contact',
    'signature',
    'cor'
];
$incomplete = false;
foreach ($required as $col) {
    if (empty($student[$col])) {
        $incomplete = true;
        break;
    }
}

/* ----- 3. post-back ---- */
$msg = '';
$msgType = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$incomplete) {
    $type   = $_POST['request_type'] ?? '';
    $reason = trim($_POST['reason']  ?? '');

    if (!in_array($type, ['new', 'replacement', 'update_information']))
        $msg = 'Invalid request type.';
    else {
        if (($type === 'replacement' || $type === 'update_information') && $reason === '')
            $msg = 'Reason is required for replacement/update.';
        else {
            $stuObj->insertIdRequest(
                $student['id'],
                $type,
                $reason
            );
            $msg = '✓ Request submitted successfully! You will be notified once processed.';
            $msgType = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Apply / Renew School ID</title>
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

        /* ▬▬▬▬ MAIN CONTAINER ▬▬▬▬ */
        .id-application-wrapper {
            width: 95%;
            max-width: 1000px;
            margin: 30px auto;
        }

        /* ▬▬▬▬ PAGE TITLE ▬▬▬▬ */
        .page-title-section {
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: white;
            padding: 40px 30px;
            border-radius: 12px;
            margin-top: 90px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.15);
        }

        .page-title-section h1 {
            margin: 0 0 10px 0;
            font-size: 32px;
            font-weight: 700;
        }

        .page-title-section p {
            margin: 0;
            font-size: 16px;
            opacity: 0.9;
        }

        /* ▬▬▬▬ APPLICATION CARD ▬▬▬▬ */
        .app-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .app-card-header {
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: white;
            padding: 20px 30px;
            font-size: 18px;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .app-card-header svg {
            width: 24px;
            height: 24px;
        }

        .app-card-body {
            padding: 30px;
        }

        /* ▬▬▬▬ FORM SECTIONS ▬▬▬▬ */
        .form-section {
            margin-bottom: 30px;
        }

        .form-section-title {
            font-size: 16px;
            font-weight: 700;
            color: #1b5e20;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #b69b04;
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }

        .form-row.full {
            grid-template-columns: 1fr;
        }

        .form-group {
            margin-bottom: 0;
        }

        .form-group label {
            display: block;
            font-weight: 600;
            color: #333;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group.required label::after {
            content: ' *';
            color: #dc3545;
        }

        .form-group input[type="text"],
        .form-group input[type="email"],
        .form-group input[type="tel"],
        .form-group input[type="file"],
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 12px 15px;
            border: 1.5px solid #ddd;
            border-radius: 8px;
            font-size: 14px;
            font-family: inherit;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1b5e20;
            box-shadow: 0 0 8px rgba(27, 94, 32, 0.2);
            background-color: #fafafa;
        }

        .form-group input[readonly],
        .form-group input[disabled] {
            background-color: #f0f0f0;
            color: #666;
            cursor: not-allowed;
        }

        /* ▬▬▬▬ PHOTO UPLOAD SECTION ▬▬▬▬ */
        .photo-upload-container {
            background: #f8f9fa;
            border: 2px dashed #1b5e20;
            border-radius: 8px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .photo-upload-container:hover {
            background: #f0f8f5;
            border-color: #0d3817;
        }

        .photo-upload-container input[type="file"] {
            display: none;
        }

        .upload-icon {
            font-size: 48px;
            margin-bottom: 15px;
            color: #1b5e20;
        }

        .upload-text {
            font-weight: 600;
            color: #333;
            margin-bottom: 5px;
        }

        .upload-subtext {
            font-size: 13px;
            color: #666;
            margin-bottom: 15px;
        }

        .upload-button {
            display: inline-block;
            background: #1b5e20;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            transition: background 0.3s;
            font-weight: 500;
            border: none;
            font-size: 14px;
        }

        .upload-button:hover {
            background: #0d3817;
        }

        .photo-preview-container {
            margin-top: 20px;
        }

        .photo-preview-label {
            font-weight: 600;
            color: #1b5e20;
            display: block;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .photo-preview {
            width: 200px;
            height: 250px;
            border: 2px solid #1b5e20;
            border-radius: 8px;
            object-fit: cover;
            display: block;
            margin: 0 auto;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .photo-preview-small {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 3px solid #1b5e20;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .current-photo-note {
            font-size: 13px;
            color: #666;
            margin-top: 10px;
            font-style: italic;
        }

        /* ▬▬▬▬ CONFIRMATION SECTION ▬▬▬▬ */
        .confirmation-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .confirmation-list li {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .confirmation-list li:last-child {
            border-bottom: none;
        }

        .confirmation-list li::before {
            content: '✓';
            color: #28a745;
            font-weight: bold;
            margin-right: 12px;
            font-size: 16px;
        }

        .confirmation-checkbox {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
            margin: 20px 0;
        }

        .confirmation-checkbox input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
        }

        .confirmation-checkbox label {
            margin: 0;
            cursor: pointer;
            font-size: 14px;
            color: #333;
        }

        /* ▬▬▬▬ ALERT MESSAGES ▬▬▬▬ */
        .alert-banner {
            padding: 16px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-size: 14px;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .alert-info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }

        /* ▬▬▬▬ ACTION BUTTONS ▬▬▬▬ */
        .form-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #eee;
        }

        .btn-submit,
        .btn-cancel {
            padding: 14px 40px;
            font-size: 15px;
            font-weight: 600;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-submit {
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: white;
            box-shadow: 0 4px 12px rgba(27, 94, 32, 0.3);
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(27, 94, 32, 0.4);
        }

        .btn-submit:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .btn-cancel {
            background: #6c757d;
            color: white;
        }

        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }

        /* ▬▬▬▬ DIGITAL ID SECTION ▬▬▬▬ */
        .digital-id-display {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
        }

        .id-card-preview {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }

        .id-card-label {
            font-weight: 600;
            color: #1b5e20;
            margin-bottom: 15px;
            font-size: 14px;
        }

        .id-card-image {
            max-width: 100%;
            height: auto;
            max-height: 350px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        /* ▬▬▬▬ RESPONSIVE ▬▬▬▬ */
        @media (max-width: 768px) {
            .id-application-wrapper {
                width: 100%;
            }

            .form-row {
                grid-template-columns: 1fr;
            }

            .page-title-section {
                padding: 25px 20px;
            }

            .page-title-section h1 {
                font-size: 24px;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-submit,
            .btn-cancel {
                width: 100%;
            }

            .photo-preview {
                width: 150px;
                height: 200px;
            }
        }

        @media (max-width: 576px) {
            .app-card-body {
                padding: 20px;
            }

            .form-section-title {
                font-size: 15px;
            }

            .page-title-section h1 {
                font-size: 20px;
            }
        }

        /* ▬▬▬▬ HEADER ▬▬▬▬ */
        .page-header {
            width: 95%;
            margin: 10px auto 30px;
            background: #ffffff;
            border-left: 8px solid #1b5e20;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 3px 7px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .page-header h2 {
            margin: 0;
            color: #1b5e20;
            font-size: 22px;
        }

        /* ▬▬▬▬ ALERT MESSAGE ▬▬▬▬ */
        .alert-message {
            width: 95%;
            margin: 20px auto;
            padding: 15px 20px;
            border-radius: 5px;
            display: none;
        }

        .alert-message.show {
            display: block;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }

        /* ▬▬▬▬ CONTAINER ▬▬▬▬ */
        .card-container {
            width: 95%;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0px 3px 7px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: #1b5e20;
            color: white;
            padding: 20px;
            font-size: 18px;
            font-weight: bold;
        }

        .card-body {
            padding: 30px;
        }

        /* ▬▬▬▬ DIGITAL ID SECTION ▬▬▬▬ */
        .digital-id-section {
            margin-bottom: 30px;
        }

        .id-image-container {
            text-align: center;
            margin: 20px 0;
        }

        .id-image-label {
            font-weight: bold;
            color: #1b5e20;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .id-image {
            max-width: 100%;
            height: auto;
            max-height: 400px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            margin: 10px 0;
        }

        /* ▬▬▬▬ FORM STYLES ▬▬▬▬ */
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

        .form-group input[readonly] {
            background: #f8f9fa;
            color: #666;
        }

        /* ▬▬▬▬ PHOTO PREVIEW ▬▬▬▬ */
        .photo-preview-section {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-top: 20px;
        }

        .photo-preview-label {
            font-weight: bold;
            color: #1b5e20;
            display: block;
            margin-bottom: 15px;
        }

        .photo-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 3px solid #1b5e20;
            object-fit: cover;
            display: block;
            margin: 0 auto;
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

    <!-- INCOMPLETE PROFILE WARNING -->
    <?php if ($incomplete): ?>
        <div class="id-application-wrapper">
            <div class="alert-banner alert-warning">
                <span style="font-size: 20px;">⚠️</span>
                <div>
                    <strong>Incomplete Profile</strong><br>
                    Please complete your profile first before requesting an ID. <a href="edit_profile.php" style="color: #856404; font-weight: bold;">Complete Profile →</a>
                </div>
            </div>
        </div>
    <?php else: ?>

        <!-- PAGE TITLE -->
        <div class="id-application-wrapper">
            <div class="page-title-section">
                <h1>🎓 Apply / Renew School ID</h1>
                <p>Complete the form below to apply for a new student ID or renew your existing one</p>
            </div>

            <!-- APPLICATION FORM -->
            <div class="app-card">
                <?php if ($msg): ?>
                    <div class="alert-banner alert-<?php echo $msgType ?: 'error'; ?>">
                        <span style="font-size: 18px;"><?php echo $msgType === 'success' ? '✓' : '✕'; ?></span>
                        <span><?php echo htmlspecialchars($msg); ?></span>
                    </div>
                <?php endif; ?>

                <!-- DIGITAL ID SECTION -->
                <?php if (!empty($student['digital_id_front']) || !empty($student['digital_id_back'])): ?>
                    <div class="app-card">
                        <div class="app-card-header">
                            <span>📋</span> Your Current Digital ID
                        </div>
                        <div class="app-card-body">
                            <div class="digital-id-display">
                                <?php if (!empty($student['digital_id_front'])): ?>
                                    <div class="id-card-preview">
                                        <div class="id-card-label">Front Side</div>
                                        <img src="../uploads/digital_id/<?= htmlspecialchars($student['digital_id_front']) ?>" alt="Digital ID Front" class="id-card-image">
                                    </div>
                                <?php endif; ?>
                                <?php if (!empty($student['digital_id_back'])): ?>
                                    <div class="id-card-preview">
                                        <div class="id-card-label">Back Side</div>
                                        <img src="../uploads/digital_id/<?= htmlspecialchars($student['digital_id_back']) ?>" alt="Digital ID Back" class="id-card-image">
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                <?php endif; ?>

                <!-- APPLICATION FORM -->
                <div class="app-card">
                    <div class="app-card-header">
                        <span>📝</span> ID Application Form
                    </div>
                    <div class="app-card-body">
                        <form method="post" enctype="multipart/form-data" onsubmit="handleSubmit(event)">

                            <!-- STEP 1: PHOTO UPLOAD -->
                            <div class="form-section">
                                <div class="form-section-title">Upload ID Photo</div>
                                <p style="color: #666; font-size: 14px; margin-bottom: 20px;">A recent professional photo (3x4 or 4x6) in JPG, PNG format</p>

                                <div class="photo-upload-container" onclick="document.getElementById('photoInput').click();">
                                    <div class="upload-icon">📸</div>
                                    <div class="upload-text">Click to upload ID photo</div>
                                    <div class="upload-subtext">or drag and drop (JPG, PNG • Max 5MB)</div>
                                    <button type="button" class="upload-button">Choose File</button>
                                    <input type="file" id="photoInput" name="id_photo" accept="image/jpeg,image/png">
                                </div>

                                <div class="photo-preview-container" id="previewContainer" style="display: none;">
                                    <div class="photo-preview-label">Photo Preview</div>
                                    <img id="photoPreview" class="photo-preview" alt="Preview">
                                    <div class="current-photo-note">Current profile photo will be used if no new photo is uploaded</div>
                                </div>
                            </div>

                            <!-- STEP 2: PERSONAL DETAILS -->
                            <div class="form-section">
                                <div class="form-section-title">Verify Personal Details</div>

                                <div class="form-row">
                                    <div class="form-group required">
                                        <label>Full Name</label>
                                        <input type="text" value="<?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>" readonly>
                                    </div>
                                    <div class="form-group required">
                                        <label>Student ID</label>
                                        <input type="text" value="<?= htmlspecialchars($student['student_id']) ?>" readonly>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group required">
                                        <label>Email Address</label>
                                        <input type="email" value="<?= htmlspecialchars($student['email']) ?>" readonly>
                                    </div>
                                    <div class="form-group required">
                                        <label>Contact Number</label>
                                        <input type="tel" value="<?= htmlspecialchars($student['contact_number']) ?>" readonly>
                                    </div>
                                </div>

                                <div class="form-row">
                                    <div class="form-group required">
                                        <label>Course</label>
                                        <input type="text" value="<?= htmlspecialchars($student['course']) ?>" readonly>
                                    </div>
                                    <div class="form-group required">
                                        <label>Year Level</label>
                                        <input type="text" value="<?= htmlspecialchars($student['year_level']) ?>" readonly>
                                    </div>
                                </div>

                                <div class="form-row full">
                                    <div class="form-group required">
                                        <label>Request Type</label>
                                        <select name="request_type" required onchange="toggleReasonField()">
                                            <option value="">-- Select Request Type --</option>
                                            <option value="new">📋 New ID Application</option>
                                            <option value="replacement">🔄 Replacement (Lost/Damaged)</option>
                                            <option value="update_information">✏️ Update Information</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="form-row full" id="reasonGroup" style="display: none;">
                                    <div class="form-group required">
                                        <label>Reason for Request</label>
                                        <textarea name="reason" placeholder="Please provide details about your request..." rows="4"></textarea>
                                    </div>
                                </div>

                                <div class="photo-preview-container">
                                    <div class="photo-preview-label">Current Profile Photo (Used on ID)</div>
                                    <img src="<?= htmlspecialchars('../uploads/student_photos/' . $student['photo']) ?>" alt="Profile Photo" class="photo-preview-small" style="width: 150px; height: 150px; margin: 0 auto;">
                                    <div class="current-photo-note" style="text-align: center;">This will be used on your ID unless you upload a new photo above</div>
                                </div>
                            </div>

                            <!-- STEP 3: CONFIRMATION -->
                            <div class="form-section">
                                <div class="form-section-title">Confirm & Submit</div>

                                <ul class="confirmation-list">
                                    <li>I confirm that all information provided is accurate and complete</li>
                                    <li>I understand the ID will be processed within 3-5 business days</li>
                                    <li>I will receive notification via email when the ID is ready for pickup</li>
                                    <li>The photo will be used as per school regulations</li>
                                </ul>

                                <div class="confirmation-checkbox">
                                    <input type="checkbox" id="confirmCheckbox" required>
                                    <label for="confirmCheckbox">I confirm that all details are correct and I authorize submission of this application</label>
                                </div>
                            </div>

                            <!-- ACTION BUTTONS -->
                            <div class="form-actions">
                                <button type="submit" class="btn-submit">Submit Application</button>
                                <a href="student_home.php" class="btn-cancel">Back to Home</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

        <?php endif; ?>

        <script>
            // Show notification on submit
            function handleSubmit(event) {
                const confirmCheckbox = document.getElementById('confirmCheckbox');
                if (!confirmCheckbox.checked) {
                    event.preventDefault();
                    showNotification('Please confirm all details before submitting', 'warning');
                    return false;
                }

                // Show success notification before form submission
                showNotification('✓ Application submitted successfully! You will be notified once processed.', 'success');

                // Allow form to submit after 1.5 seconds
                setTimeout(() => {
                    event.target.submit();
                }, 1500);
            }

            // Show notification alert
            function showNotification(message, type) {
                // Remove existing notification if any
                const existingNotif = document.getElementById('notificationAlert');
                if (existingNotif) {
                    existingNotif.remove();
                }

                // Create notification element
                const notification = document.createElement('div');
                notification.id = 'notificationAlert';
                notification.className = `alert-banner alert-${type}`;
                notification.style.cssText = `
                position: fixed;
                top: 100px;
                right: 20px;
                left: auto;
                width: 400px;
                z-index: 9999;
                animation: slideIn 0.3s ease-out;
            `;

                const icon = type === 'success' ? '✓' : type === 'warning' ? '⚠️' : 'ℹ️';
                notification.innerHTML = `
                <span style="font-size: 18px; margin-right: 10px;">${icon}</span>
                <span>${message}</span>
            `;

                document.body.appendChild(notification);

                // Auto-remove after 4 seconds
                setTimeout(() => {
                    notification.style.animation = 'slideOut 0.3s ease-out';
                    setTimeout(() => {
                        notification.remove();
                    }, 300);
                }, 4000);
            }

            // Add animations
            const style = document.createElement('style');
            style.textContent = `
            @keyframes slideIn {
                from {
                    transform: translateX(100%);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }

            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(100%);
                    opacity: 0;
                }
            }

            @media (max-width: 768px) {
                #notificationAlert {
                    width: calc(100% - 40px) !important;
                    right: 20px !important;
                    left: 20px !important;
                }
            }
        `;
            document.head.appendChild(style);

            // Toggle reason field based on request type
            function toggleReasonField() {
                const select = document.querySelector('select[name="request_type"]');
                const reasonGroup = document.getElementById('reasonGroup');
                const reasonInput = document.querySelector('textarea[name="reason"]');

                if (select.value === 'replacement' || select.value === 'update_information') {
                    reasonGroup.style.display = 'block';
                    reasonInput.required = true;
                } else {
                    reasonGroup.style.display = 'none';
                    reasonInput.required = false;
                }
            }

            // Photo preview on upload
            const photoInput = document.getElementById('photoInput');
            const photoPreview = document.getElementById('photoPreview');
            const previewContainer = document.getElementById('previewContainer');

            photoInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function(event) {
                        photoPreview.src = event.target.result;
                        previewContainer.style.display = 'block';
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Drag and drop functionality
            const uploadContainer = document.querySelector('.photo-upload-container');
            uploadContainer.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadContainer.style.background = '#e8f5e9';
            });

            uploadContainer.addEventListener('dragleave', () => {
                uploadContainer.style.background = '#f8f9fa';
            });

            uploadContainer.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadContainer.style.background = '#f8f9fa';
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    photoInput.files = files;
                    const event = new Event('change', {
                        bubbles: true
                    });
                    photoInput.dispatchEvent(event);
                }
            });

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