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

<!-- PAGE CONTENT STARTS HERE -->

    <!-- BACK-TO-TOP BUTTON -->
    <button id="backToTopBtn" class="back-to-top" onclick="scrollToTop()" title="Back to top">
        <i class="fas fa-arrow-up"></i>
    </button>

    <!-- INCOMPLETE PROFILE WARNING -->
    <?php if ($incomplete): ?>
        <div class="id-application-wrapper">
            <div class="alert-warning-enhanced">
                <i class="fas fa-exclamation-circle"></i>
                <div class="alert-content">
                    <strong>Incomplete Profile</strong>
                    <p>Please complete your profile first before requesting an ID.</p>
                </div>
                <a href="edit_profile.php" class="btn-complete-profile">
                    <i class="fas fa-check-circle"></i> Complete Profile
                </a>
            </div>
        </div>
    <?php else: ?> 
    <!-- PAGE TITLE -->
    <div class="id-application-wrapper">
            <!-- DIGITAL ID SECTION -->
            <?php if (!empty($student['digital_id_front']) || !empty($student['digital_id_back'])): ?>
                <div class="app-card-enhanced">
                    <div class="app-card-header-enhanced">
                        <i class="fas fa-credit-card"></i>
                        <span>Your Current Digital ID</span>
                    </div>
                    <div class="app-card-body-enhanced">
                        <div class="digital-id-display-enhanced">
                            <?php if (!empty($student['digital_id_front'])): ?>
                                <div class="id-card-preview-enhanced">
                                    <div class="id-card-label-enhanced">Front Side</div>
                                    <img src="../uploads/digital_id/<?= htmlspecialchars($student['digital_id_front']) ?>" alt="Digital ID Front" class="id-card-image-enhanced">
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($student['digital_id_back'])): ?>
                                <div class="id-card-preview-enhanced">
                                    <div class="id-card-label-enhanced">Back Side</div>
                                    <img src="../uploads/digital_id/<?= htmlspecialchars($student['digital_id_back']) ?>" alt="Digital ID Back" class="id-card-image-enhanced">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php else: ?>
            <?php endif; ?>

            <!-- APPLICATION FORM -->
            <div class="app-card-enhanced">
                <div class="app-card-header-enhanced">
                    <i class="fas fa-file-alt"></i>
                    <span>ID Application Form</span>
                </div>
                <div class="app-card-body-enhanced">
                    <!-- SUCCESS MESSAGE -->
                    <?php if ($msg): ?>
                        <div class="alert-message alert-<?= $msgType === 'success' ? 'success' : 'error' ?>">
                            <i class="fas fa-<?= $msgType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
                            <span><?= htmlspecialchars($msg) ?></span>
                        </div>
                    <?php endif; ?>

                    <form method="post" enctype="multipart/form-data" onsubmit="handleSubmit(event)">

                        <!-- STEP 1: PHOTO UPLOAD -->
                        <div class="form-section-enhanced">
                            <div class="form-section-header">
                                <h3>Upload ID Photo</h3>
                            </div>
                            <p class="form-section-subtitle">A recent professional photo (3x4 or 4x6) in JPG, PNG format</p>

                            <div class="photo-upload-container-enhanced" onclick="document.getElementById('photoInput').click();">
                                <div class="upload-icon"><i class="fas fa-cloud-upload-alt"></i></div>
                                <div class="upload-text">Click to upload ID photo</div>
                                <div class="upload-subtext">or drag and drop (JPG, PNG • Max 5MB)</div>
                                <button type="button" class="upload-button-enhanced">
                                    <i class="fas fa-folder-open"></i> Choose File
                                </button>
                                <input type="file" id="photoInput" name="id_photo" accept="image/jpeg,image/png">
                            </div>

                            <div class="photo-preview-container-enhanced" id="previewContainer" style="display: none;">
                                <div class="photo-preview-label-enhanced">Photo Preview</div>
                                <img id="photoPreview" class="photo-preview-enhanced" alt="Preview">
                                <div class="current-photo-note-enhanced">Current profile photo will be used if no new photo is uploaded</div>
                            </div>
                        </div>

                        <!-- STEP 2: PERSONAL DETAILS -->
                        <div class="form-section-enhanced">
                            <div class="form-section-header">
                                <h3>Verify Personal Details</h3>
                            </div>

                            <div class="form-row">
                                <div class="form-group-enhanced">
                                    <label></i> Full Name</label>
                                    <input type="text" value="<?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>" readonly>
                                </div>
                                <div class="form-group-enhanced">
                                    <label></i> Student ID</label>
                                    <input type="text" value="<?= htmlspecialchars($student['student_id']) ?>" readonly>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group-enhanced">
                                    <label></i> Email Address</label>
                                    <input type="email" value="<?= htmlspecialchars($student['email']) ?>" readonly>
                                </div>
                                <div class="form-group-enhanced">
                                    <label></i> Contact Number</label>
                                    <input type="tel" value="<?= htmlspecialchars($student['contact_number']) ?>" readonly>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group-enhanced">
                                    <label></i> Course</label>
                                    <input type="text" value="<?= htmlspecialchars($student['course']) ?>" readonly>
                                </div>
                                <div class="form-group-enhanced">
                                    <label></i> Year Level</label>
                                    <input type="text" value="<?= htmlspecialchars($student['year_level']) ?>" readonly>
                                </div>
                            </div>

                            <div class="form-row full">
                                <div class="form-group-enhanced">
                                    <label for="requestType"></i> Request Type *</label>
                                    <select id="requestType" name="request_type" required onchange="toggleReasonField()">
                                        <option value="">-- Select Request Type --</option>
                                        <option value="new">New ID Application</option>
                                        <option value="replacement">Replacement (Lost/Damaged)</option>
                                        <option value="update_information">Update Information</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-row full" id="reasonGroup" style="display: none;">
                                <div class="form-group-enhanced">
                                    <label for="reason"></i> Reason for Request *</label>
                                    <textarea id="reason" name="reason" placeholder="Please provide details about your request..." rows="4"></textarea>
                                </div>
                            </div>

                            <div class="photo-preview-section-enhanced">
                                <div class="photo-preview-label-enhanced">Current Profile Photo (Used on ID)</div>
                                <img src="<?= htmlspecialchars('../uploads/student_photos/' . $student['photo']) ?>" alt="Profile Photo" class="photo-preview-current-enhanced">
                                <div class="current-photo-note-enhanced">This will be used on your ID unless you upload a new photo above</div>
                            </div>
                        </div>

                        <!-- STEP 3: CONFIRMATION -->
                        <div class="form-section-enhanced">
                            <div class="form-section-header">
                                <h3>Confirm & Submit</h3>
                            </div>

                            <ul class="confirmation-list-enhanced">
                                <li><i class="fas fa-check-circle"></i> I confirm that all information provided is accurate and complete</li>
                                <li><i class="fas fa-check-circle"></i> I understand the ID will be processed within 3-5 business days</li>
                                <li><i class="fas fa-check-circle"></i> I will receive notification via email when the ID is ready for pickup</li>
                                <li><i class="fas fa-check-circle"></i> The photo will be used as per school regulations</li>
                            </ul>

                            <div class="confirmation-checkbox-enhanced">
                                <input type="checkbox" id="confirmCheckbox" required>
                                <label for="confirmCheckbox">
                                    I confirm that all details are correct and I authorize submission of this application
                                </label>
                            </div>
                        </div>

                        <!-- ACTION BUTTONS -->
                        <div class="form-actions-enhanced">
                            <button type="submit" class="btn-submit-enhanced">
                                <i class="fas fa-paper-plane"></i> Submit Application
                            </button>
                            <a href="student_home.php" class="btn-cancel-enhanced">
                                <i class="fas fa-home"></i> Back to Home
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>

    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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
            --shadow-xl: 0 12px 36px rgba(0, 0, 0, 0.2);
            --transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        /* ID APPLICATION WRAPPER */
        .id-application-wrapper {
            max-width: 1100px;
            margin: 30px auto;
            padding: 30px 20px;
            animation: fadeInUp 0.6s ease-out;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* PAGE HEADER */
        .page-header-enhanced {
            background: linear-gradient(135deg, white 0%, #f9f9f9 100%);
            border-left: 6px solid var(--primary-dark);
            border-radius: 12px;
            padding: 35px;
            margin-bottom: 35px;
            box-shadow: var(--shadow-md);
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 25px;
            animation: slideInDown 0.5s ease-out;
        }

        .page-header-enhanced:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .page-header-icon {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-medium) 100%);
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 40px;
            flex-shrink: 0;
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.3);
        }

        .page-header-text {
            flex: 1;
        }

        .page-header-enhanced h1 {
            margin: 0 0 10px 0;
            color: var(--primary-dark);
            font-size: 2.2rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .page-header-enhanced p {
            margin: 0;
            color: #666;
            font-size: 1.05rem;
            line-height: 1.5;
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

        /* ALERT WARNING */
        .alert-warning-enhanced {
            background: linear-gradient(135deg, rgba(255, 193, 7, 0.1) 0%, rgba(255, 193, 7, 0.05) 100%);
            border: 2px solid #fbc02d;
            border-radius: 12px;
            padding: 25px 30px;
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 35px;
            animation: slideInDown 0.4s ease-out;
        }

        .alert-warning-enhanced i {
            font-size: 32px;
            color: #f57f17;
            flex-shrink: 0;
        }

        .alert-content {
            flex: 1;
        }

        .alert-content strong {
            color: #f57f17;
            font-size: 1.1rem;
            display: block;
            margin-bottom: 5px;
        }

        .alert-content p {
            margin: 0;
            color: #666;
            font-size: 0.95rem;
        }

        .btn-complete-profile {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            background: linear-gradient(135deg, var(--accent-orange) 0%, var(--accent-orange-dark) 100%);
            color: white;
            padding: 14px 28px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 6px 20px rgba(255, 152, 0, 0.25), 0 2px 8px rgba(255, 152, 0, 0.15);
            position: relative;
            overflow: hidden;
            letter-spacing: 0.3px;
        }

        .btn-complete-profile::before {
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

        .btn-complete-profile:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-complete-profile:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(255, 152, 0, 0.35), 0 4px 12px rgba(255, 152, 0, 0.2);
            color: white;
        }

        .btn-complete-profile:active {
            transform: translateY(-2px);
        }

        .btn-complete-profile i {
            transition: transform 0.3s ease;
        }

        .btn-complete-profile:hover i {
            transform: scale(1.2) rotate(5deg);
        }

        /* ALERT MESSAGE */
        .alert-message {
            padding: 18px 20px;
            border-radius: 10px;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 12px;
            font-weight: 600;
            animation: slideInDown 0.4s ease-out;
        }

        .alert-success {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(76, 175, 80, 0.05) 100%);
            border-left: 5px solid var(--primary-light);
            color: var(--primary-dark);
        }

        .alert-error {
            background: linear-gradient(135deg, rgba(244, 67, 54, 0.1) 0%, rgba(244, 67, 54, 0.05) 100%);
            border-left: 5px solid #f44336;
            color: #d32f2f;
        }

        .alert-message i {
            font-size: 1.3rem;
        }

        /* CARD ENHANCED */
        .app-card-enhanced {
            background: white;
            border-radius: 14px;
            box-shadow: var(--shadow-md);
            overflow: hidden;
            margin-bottom: 30px;
            transition: var(--transition);
            animation: fadeIn 0.5s ease-out;
        }

        .app-card-enhanced:hover {
            box-shadow: var(--shadow-lg);
        }

        .app-card-header-enhanced {
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

        .app-card-header-enhanced i {
            font-size: 22px;
        }

        .app-card-body-enhanced {
            padding: 35px;
        }

        /* FORM SECTION */
        .form-section-enhanced {
            margin-bottom: 40px;
            padding-bottom: 40px;
            border-bottom: 2px solid #e8e8e8;
        }

        .form-section-enhanced:last-child {
            border-bottom: none;
            margin-bottom: 0;
            padding-bottom: 0;
        }

        .form-section-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 15px;
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .form-section-header i {
            color: var(--accent-orange);
            font-size: 1.4rem;
        }

        .form-section-header h3 {
            margin: 0;
        }

        .form-section-subtitle {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 25px;
            line-height: 1.6;
        }

        /* PHOTO UPLOAD */
        .photo-upload-container-enhanced {
            border: 3px dashed var(--primary-light);
            border-radius: 12px;
            padding: 40px 30px;
            text-align: center;
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.03) 0%, rgba(76, 175, 80, 0.01) 100%);
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 15px;
        }

        .photo-upload-container-enhanced:hover {
            border-color: var(--accent-orange);
            background: linear-gradient(135deg, rgba(255, 152, 0, 0.05) 0%, rgba(255, 152, 0, 0.02) 100%);
        }

        .upload-icon {
            font-size: 48px;
            color: var(--primary-light);
        }

        .upload-text {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-dark);
        }

        .upload-subtext {
            font-size: 0.95rem;
            color: #666;
        }

        .upload-button-enhanced {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-medium) 100%);
            color: white;
            border: none;
            padding: 14px 32px;
            border-radius: 10px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            display: inline-flex;
            align-items: center;
            gap: 10px;
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.25), 0 2px 8px rgba(76, 175, 80, 0.15);
            position: relative;
            overflow: hidden;
            letter-spacing: 0.3px;
        }

        .upload-button-enhanced::before {
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

        .upload-button-enhanced:hover::before {
            width: 300px;
            height: 300px;
        }

        .upload-button-enhanced:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(76, 175, 80, 0.35), 0 4px 12px rgba(76, 175, 80, 0.2);
            background: linear-gradient(135deg, var(--primary-medium) 0%, var(--primary-dark) 100%);
        }

        .upload-button-enhanced:active {
            transform: translateY(-2px);
        }

        .upload-button-enhanced i {
            transition: transform 0.3s ease;
        }

        .upload-button-enhanced:hover i {
            transform: scale(1.2) rotate(5deg);
        }

        #photoInput {
            display: none;
        }

        /* PHOTO PREVIEW */
        .photo-preview-container-enhanced {
            margin-top: 25px;
            padding: 25px;
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f0f0 100%);
            border-radius: 10px;
            text-align: center;
        }

        .photo-preview-label-enhanced {
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 0.95rem;
            margin-bottom: 15px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
        }

        .photo-preview-enhanced {
            max-width: 100%;
            max-height: 300px;
            border-radius: 8px;
            border: 2px solid #ddd;
            margin: 0 auto 15px;
            display: block;
            transition: var(--transition);
        }

        .photo-preview-enhanced:hover {
            transform: scale(1.02);
            box-shadow: var(--shadow-md);
        }

        .photo-preview-section-enhanced {
            margin-top: 30px;
            padding: 25px;
            background: linear-gradient(135deg, rgba(255, 152, 0, 0.05) 0%, rgba(255, 152, 0, 0.02) 100%);
            border: 2px solid var(--accent-orange);
            border-radius: 10px;
            text-align: center;
        }

        .photo-preview-current-enhanced {
            width: 150px;
            height: 150px;
            border-radius: 10px;
            border: 3px solid var(--primary-light);
            object-fit: cover;
            margin: 15px auto;
            display: block;
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.2);
        }

        .photo-preview-current-enhanced:hover {
            transform: scale(1.05);
            box-shadow: 0 8px 20px rgba(76, 175, 80, 0.3);
        }

        .current-photo-note-enhanced {
            color: #666;
            font-size: 0.9rem;
            margin-top: 12px;
            line-height: 1.5;
        }

        /* FORM ROW */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 25px;
        }

        .form-row.full {
            grid-template-columns: 1fr;
        }

        /* FORM GROUP */
        .form-group-enhanced {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .form-group-enhanced label {
            font-weight: 700;
            color: var(--primary-dark);
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 8px;
            letter-spacing: 0.2px;
        }

        .form-group-enhanced label i {
            color: var(--accent-orange);
            font-size: 1rem;
        }

        .form-group-enhanced input,
        .form-group-enhanced select,
        .form-group-enhanced textarea {
            padding: 16px 18px;
            border: 2px solid #e8e8e8;
            border-radius: 10px;
            font-size: 1rem;
            font-family: inherit;
            background: #fafafa;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            color: #333;
        }

        .form-group-enhanced input::placeholder,
        .form-group-enhanced textarea::placeholder {
            color: #999;
            font-style: italic;
        }

        .form-group-enhanced input:hover,
        .form-group-enhanced select:hover,
        .form-group-enhanced textarea:hover {
            border-color: var(--primary-light);
            background: #fff;
        }

        .form-group-enhanced input:focus,
        .form-group-enhanced select:focus,
        .form-group-enhanced textarea:focus {
            outline: none;
            border-color: var(--primary-medium);
            background: white;
            box-shadow: 0 0 0 4px rgba(76, 175, 80, 0.1), 0 4px 12px rgba(76, 175, 80, 0.08);
            transform: translateY(-2px);
        }

        .form-group-enhanced input[readonly] {
            background: linear-gradient(135deg, #f5f5f5 0%, #ebebeb 100%);
            color: #666;
            cursor: not-allowed;
            border-color: #ddd;
        }

        /* CONFIRMATION LIST */
        .confirmation-list-enhanced {
            list-style: none;
            padding: 0;
            margin: 0 0 25px 0;
        }

        .confirmation-list-enhanced li {
            padding: 15px;
            margin-bottom: 12px;
            background: linear-gradient(135deg, #f8f9fa 0%, #f0f0f0 100%);
            border-left: 4px solid var(--primary-light);
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 12px;
            color: #333;
            font-weight: 600;
        }

        .confirmation-list-enhanced i {
            color: var(--primary-light);
            font-size: 1.1rem;
            flex-shrink: 0;
        }

        /* CONFIRMATION CHECKBOX */
        .confirmation-checkbox-enhanced {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 18px;
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.05) 0%, rgba(76, 175, 80, 0.02) 100%);
            border: 2px solid var(--primary-light);
            border-radius: 10px;
        }

        .confirmation-checkbox-enhanced input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: var(--primary-light);
        }

        .confirmation-checkbox-enhanced label {
            margin: 0;
            cursor: pointer;
            font-weight: 600;
            color: var(--primary-dark);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .confirmation-checkbox-enhanced label i {
            color: var(--primary-light);
        }

        /* DIGITAL ID DISPLAY */
        .digital-id-display-enhanced {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
        }

        .id-card-preview-enhanced {
            text-align: center;
        }

        .id-card-label-enhanced {
            font-weight: 700;
            color: var(--primary-dark);
            margin-bottom: 12px;
            font-size: 0.95rem;
        }

        .id-card-image-enhanced {
            width: 100%;
            max-width: 400px;
            border-radius: 12px;
            border: 3px solid var(--primary-light);
            box-shadow: 0 8px 24px rgba(76, 175, 80, 0.2);
            transition: var(--transition);
            display: block;
            margin: 0 auto;
        }

        .id-card-image-enhanced:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 32px rgba(76, 175, 80, 0.3);
        }

        /* FORM ACTIONS */
        .form-actions-enhanced {
            display: flex;
            gap: 20px;
            margin-top: 45px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .btn-submit-enhanced,
        .btn-cancel-enhanced {
            padding: 16px 36px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 1.05rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            border: none;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            letter-spacing: 0.5px;
            min-width: 220px;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }

        .btn-submit-enhanced::before,
        .btn-cancel-enhanced::before {
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

        .btn-submit-enhanced:hover::before,
        .btn-cancel-enhanced:hover::before {
            width: 300px;
            height: 300px;
        }

        .btn-submit-enhanced {
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-medium) 100%);
            color: white;
            box-shadow: 0 6px 20px rgba(46, 125, 50, 0.25), 0 2px 8px rgba(46, 125, 50, 0.15);
        }

        .btn-submit-enhanced:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 32px rgba(46, 125, 50, 0.35), 0 4px 12px rgba(46, 125, 50, 0.2);
            background: linear-gradient(135deg, var(--primary-medium) 0%, var(--primary-dark) 100%);
        }

        .btn-submit-enhanced:active {
            transform: translateY(-2px);
        }

        .btn-submit-enhanced i,
        .btn-cancel-enhanced i {
            font-size: 1.2rem;
            transition: transform 0.3s ease;
        }

        .btn-submit-enhanced:hover i,
        .btn-cancel-enhanced:hover i {
            transform: scale(1.2) rotate(5deg);
        }

        .btn-cancel-enhanced {
            background: linear-gradient(135deg, #ffffff 0%, #f8f9fa 100%);
            color: var(--primary-dark);
            border: 2px solid var(--primary-light);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .btn-cancel-enhanced:hover {
            background: linear-gradient(135deg, rgba(76, 175, 80, 0.1) 0%, rgba(76, 175, 80, 0.05) 100%);
            border-color: var(--primary-medium);
            transform: translateY(-4px);
            box-shadow: 0 8px 24px rgba(76, 175, 80, 0.2);
        }

        .btn-cancel-enhanced:active {
            transform: translateY(-2px);
        }

        /* BACK TO TOP */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 55px;
            height: 55px;
            background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-medium) 100%);
            color: white;
            border: none;
            border-radius: 50%;
            font-size: 22px;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 20px rgba(76, 175, 80, 0.25), 0 2px 8px rgba(76, 175, 80, 0.15);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: 1000;
        }

        .back-to-top:hover {
            transform: translateY(-6px) scale(1.1);
            box-shadow: 0 12px 32px rgba(76, 175, 80, 0.35), 0 4px 12px rgba(76, 175, 80, 0.2);
            background: linear-gradient(135deg, var(--primary-medium) 0%, var(--primary-dark) 100%);
        }

        .back-to-top:active {
            transform: translateY(-4px) scale(1.05);
        }

        .back-to-top i {
            transition: transform 0.3s ease;
        }

        .back-to-top:hover i {
            transform: translateY(-2px);
        }

        /* RESPONSIVE DESIGN */
        @media (max-width: 1024px) {
            .id-application-wrapper {
                padding: 25px 15px;
                margin: 20px auto;
            }

            .app-card-body-enhanced {
                padding: 30px;
            }
        }

        @media (max-width: 768px) {
            .id-application-wrapper {
                padding: 20px 15px;
                margin: 15px;
            }

            .page-header-enhanced {
                flex-direction: column;
                text-align: center;
                padding: 30px;
            }

            .page-header-icon {
                width: 65px;
                height: 65px;
                font-size: 34px;
            }

            .page-header-enhanced h1 {
                font-size: 1.8rem;
            }

            .app-card-body-enhanced {
                padding: 25px;
            }

            .form-section-enhanced {
                margin-bottom: 32px;
                padding-bottom: 32px;
            }

            .photo-upload-container-enhanced {
                padding: 35px 25px;
            }

            .form-actions-enhanced {
                flex-direction: column;
                gap: 16px;
            }

            .btn-submit-enhanced,
            .btn-cancel-enhanced {
                width: 100%;
                max-width: 100%;
            }

            .back-to-top {
                bottom: 20px;
                right: 20px;
                width: 50px;
                height: 50px;
                font-size: 20px;
            }

            .form-row {
                gap: 20px;
            }
        }

        @media (max-width: 480px) {
            .id-application-wrapper {
                padding: 15px 10px;
                margin: 10px;
            }

            .page-header-enhanced {
                padding: 25px 20px;
            }

            .page-header-enhanced h1 {
                font-size: 1.5rem;
            }

            .page-header-enhanced p {
                font-size: 0.95rem;
            }

            .page-header-icon {
                width: 55px;
                height: 55px;
                font-size: 28px;
            }

            .app-card-header-enhanced {
                padding: 20px 22px;
                font-size: 16px;
            }

            .app-card-body-enhanced {
                padding: 20px;
            }

            .form-section-header {
                font-size: 1.15rem;
            }

            .form-row {
                grid-template-columns: 1fr;
                gap: 18px;
            }

            .form-section-enhanced {
                margin-bottom: 28px;
                padding-bottom: 28px;
            }

            .photo-upload-container-enhanced {
                padding: 30px 20px;
            }

            .upload-icon {
                font-size: 40px;
            }

            .btn-submit-enhanced,
            .btn-cancel-enhanced {
                padding: 14px 28px;
                font-size: 1rem;
            }

            .back-to-top {
                bottom: 15px;
                right: 15px;
                width: 48px;
                height: 48px;
                font-size: 18px;
            }

            .alert-warning-enhanced {
                flex-direction: column;
                text-align: center;
                padding: 22px 20px;
            }

            .btn-complete-profile {
                width: 100%;
                justify-content: center;
            }
        }
    </style>

    <script>
        // ============ NOTIFICATION HELPER ============
        function showNotification(message, type) {
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

        // ============ FORM SUBMISSION HANDLER ============
        function handleSubmit(event) {
            event.preventDefault();

            const confirmCheckbox = document.getElementById('confirmCheckbox');
            if (!confirmCheckbox || !confirmCheckbox.checked) {
                showNotification('Please confirm all details before submitting', 'warning');
                return false;
            }

            // Show success notification and wait for user confirmation before submitting
            Swal.fire({
                title: 'Success',
                html: 'Application submitted successfully! You will be notified once processed.',
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
                if (result.isConfirmed) {
                    event.target.submit();
                }
            });
        }

        // ============ TOGGLE REASON FIELD ============
        function toggleReasonField() {
            const select = document.querySelector('select[name="request_type"]');
            const reasonGroup = document.getElementById('reasonGroup');
            const reasonInput = document.querySelector('textarea[name="reason"]');

            if (select && reasonGroup && reasonInput) {
                if (select.value === 'replacement' || select.value === 'update_information') {
                    reasonGroup.style.display = 'block';
                    reasonInput.required = true;
                } else {
                    reasonGroup.style.display = 'none';
                    reasonInput.required = false;
                }
            }
        }

        // ============ PAGE LOAD INITIALIZATION ============
        document.addEventListener('DOMContentLoaded', function() {
            // Animate form sections
            const formSections = document.querySelectorAll('.form-section-enhanced');
            formSections.forEach((section, index) => {
                section.style.animation = `fadeIn 0.5s ease-out ${index * 0.1}s both`;
            });

            // Initialize photo upload
            initializePhotoUpload();

            // Initialize drag and drop
            initializeDragDrop();

            // Initialize back to top button
            initializeBackToTop();

            // Initialize form validation
            initializeFormValidation();

            // Initialize form input focus effects
            initializeFormInputFocus();
        });

        // ============ PHOTO UPLOAD INITIALIZATION ============
        function initializePhotoUpload() {
            const photoInput = document.getElementById('photoInput');
            const photoPreview = document.getElementById('photoPreview');
            const previewContainer = document.getElementById('previewContainer');

            if (!photoInput) return;

            photoInput.addEventListener('change', function(e) {
                const file = this.files[0];
                if (!file) return;

                // Validate file type
                if (!file.type.startsWith('image/')) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid File',
                        text: 'Please select a valid image file',
                        confirmButtonColor: '#2e7d32'
                    });
                    this.value = '';
                    return;
                }

                // Validate file size (max 5MB)
                if (file.size > 5 * 1024 * 1024) {
                    Swal.fire({
                        icon: 'error',
                        title: 'File Too Large',
                        text: 'Please select an image smaller than 5MB',
                        confirmButtonColor: '#2e7d32'
                    });
                    this.value = '';
                    return;
                }

                // Display preview
                const reader = new FileReader();
                reader.onload = function(event) {
                    if (photoPreview && previewContainer) {
                        photoPreview.src = event.target.result;
                        previewContainer.style.display = 'block';
                        previewContainer.style.animation = 'fadeIn 0.4s ease-out';
                    }
                };
                reader.readAsDataURL(file);
            });
        }

        // ============ DRAG AND DROP INITIALIZATION ============
        function initializeDragDrop() {
            const uploadContainer = document.querySelector('.photo-upload-container-enhanced') || 
                                    document.querySelector('.photo-upload-container');
            const photoInput = document.getElementById('photoInput');

            if (!uploadContainer) return;

            uploadContainer.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadContainer.style.borderColor = 'var(--accent-orange)';
                uploadContainer.style.background = 'linear-gradient(135deg, rgba(255, 152, 0, 0.1) 0%, rgba(255, 152, 0, 0.05) 100%)';
            });

            uploadContainer.addEventListener('dragleave', () => {
                uploadContainer.style.borderColor = 'var(--primary-light)';
                uploadContainer.style.background = 'linear-gradient(135deg, rgba(76, 175, 80, 0.03) 0%, rgba(76, 175, 80, 0.01) 100%)';
            });

            uploadContainer.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadContainer.style.borderColor = 'var(--primary-light)';
                uploadContainer.style.background = 'linear-gradient(135deg, rgba(76, 175, 80, 0.03) 0%, rgba(76, 175, 80, 0.01) 100%)';
                
                const files = e.dataTransfer.files;
                if (files.length > 0 && photoInput) {
                    photoInput.files = files;
                    const event = new Event('change', { bubbles: true });
                    photoInput.dispatchEvent(event);
                }
            });
        }

        // ============ BACK TO TOP BUTTON ============
        function initializeBackToTop() {
            const backToTopBtn = document.querySelector('.back-to-top') || document.getElementById('backToTopBtn');
            if (!backToTopBtn) return;

            window.addEventListener('scroll', function() {
                if (window.scrollY > 300) {
                    backToTopBtn.style.display = 'flex';
                } else {
                    backToTopBtn.style.display = 'none';
                }
            });

            backToTopBtn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }

        // ============ FORM VALIDATION ============
        function initializeFormValidation() {
            const form = document.querySelector('form');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                const requiredFields = this.querySelectorAll('[required]');
                let isValid = true;
                let firstInvalidField = null;

                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.style.borderColor = '#f44336';
                        field.style.boxShadow = '0 0 0 4px rgba(244, 67, 54, 0.1)';
                        if (!firstInvalidField) firstInvalidField = field;
                    } else {
                        field.style.borderColor = '#e0e0e0';
                        field.style.boxShadow = '';
                    }
                });

                const confirmCheckbox = form.querySelector('.confirmation-checkbox-enhanced input[type="checkbox"]') ||
                                      form.querySelector('input[name="confirmation"]');
                if (confirmCheckbox && !confirmCheckbox.checked) {
                    isValid = false;
                }

                if (!isValid) {
                    e.preventDefault();
                    if (firstInvalidField) {
                        firstInvalidField.focus();
                        firstInvalidField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                    Swal.fire({
                        icon: 'warning',
                        title: 'Incomplete Form',
                        text: 'Please fill all required fields and confirm the information is correct',
                        confirmButtonColor: '#2e7d32'
                    });
                }
            });
        }

        // ============ FORM INPUT FOCUS EFFECTS ============
        function initializeFormInputFocus() {
            const formInputs = document.querySelectorAll('input, select, textarea');
            formInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    if (this.className.includes('form-group-enhanced') || 
                        (this.parentElement && this.parentElement.className.includes('form-group-enhanced'))) {
                        this.style.boxShadow = '0 0 0 4px rgba(76, 175, 80, 0.1)';
                    }
                });

                input.addEventListener('blur', function() {
                    if (this.value.trim() === '') {
                        this.style.boxShadow = '';
                    }
                });
            });
        }
    </script>
    </script>
            </div><!-- End admin-content -->
        </main><!-- End admin-main -->
    </div><!-- End admin-wrapper -->
</body>
</html>