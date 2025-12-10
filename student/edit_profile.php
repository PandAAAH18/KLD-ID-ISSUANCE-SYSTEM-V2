<?php
require_once __DIR__ . '/../includes/config.php';

// Flag to prevent student_header.php's unwrapped sidebar scripts from running
// This page uses its own properly wrapped DOMContentLoaded sidebar code
echo '<script>window.SKIP_HEADER_SIDEBAR_INIT = true;</script>';

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
    error_log('Edit Profile FILES: ' . json_encode($_FILES));

    /* password change handling */
    $old_pwd = trim($_POST['old_password'] ?? '');
    $new_pwd = trim($_POST['new_password'] ?? '');
    $confirm_pwd = trim($_POST['confirm_password'] ?? '');

    // Debug: Log password field values
    error_log("Password fields - Old: '" . $old_pwd . "' (len=" . strlen($old_pwd) . "), New: '" . $new_pwd . "' (len=" . strlen($new_pwd) . "), Confirm: '" . $confirm_pwd . "' (len=" . strlen($confirm_pwd) . ")");

    $passwordUpdated = false;
    // Only validate password if user is trying to change it (any field is not empty)
    if (!empty($old_pwd) || !empty($new_pwd) || !empty($confirm_pwd)) {
        error_log('Password change detected - validating...');
        // If any password field is filled, all are required
        if (empty($old_pwd)) {
            $error_msg = 'Please enter your current password to change it.';
        } elseif (empty($new_pwd)) {
            $error_msg = 'Please enter a new password.';
        } elseif (empty($confirm_pwd)) {
            $error_msg = 'Please confirm your new password.';
        } elseif ($new_pwd !== $confirm_pwd) {
            $error_msg = 'New password and confirmation do not match.';
        } elseif (strlen($new_pwd) < 6) {
            $error_msg = 'New password must be at least 6 characters long.';
        }
        
        if (empty($error_msg)) {
            // Verify old password
            $user = $stuObj->findByEmail($_SESSION['email']);
            if (!$user || !password_verify($old_pwd, $user['password_hash'])) {
                $error_msg = 'Current password is incorrect.';
            } else {
                // Mark that password should be updated after student data
                $passwordUpdated = true;
                $newPasswordHash = password_hash($new_pwd, PASSWORD_DEFAULT);
            }
        }
    } else {
        error_log('No password change - all fields empty');
    }

    /* Only proceed if no password errors */
    if (empty($error_msg)) {
        /* Validate file uploads before processing */
        $fileUploadErrors = [];

        // Profile Photo Upload
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK) {
            error_log('Processing profile photo upload: ' . print_r($_FILES['profile_photo'], true));
            $validation = $stuObj->validateFile($_FILES['profile_photo'], [
                'max_size' => 5242880, // 5MB
                'mime_types' => ['image/jpeg', 'image/png'],
                'extensions' => ['jpg', 'jpeg', 'png']
            ]);
            if (!$validation['valid']) {
                $fileUploadErrors['profile_photo'] = $validation['errors'];
                error_log('Profile photo validation failed: ' . print_r($validation['errors'], true));
            } else {
                try {
                    $data['photo'] = $stuObj->saveUploadedFile($_FILES['profile_photo'], 'student_photos');
                    error_log('Profile photo uploaded successfully: ' . $data['photo']);
                } catch (Throwable $e) {
                    $fileUploadErrors['profile_photo'] = [$e->getMessage()];
                    error_log('Profile photo upload error: ' . $e->getMessage());
                }
            }
        } elseif (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $fileUploadErrors['profile_photo'] = ['Upload error occurred'];
            error_log('Profile photo upload error code: ' . $_FILES['profile_photo']['error']);
        }

        // Signature Upload with automatic background removal
        if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK) {
            error_log('Processing signature upload: ' . print_r($_FILES['signature'], true));
            $validation = $stuObj->validateFile($_FILES['signature'], [
                'max_size' => 5242880, // 5MB
                'mime_types' => ['image/jpeg', 'image/png'],
                'extensions' => ['jpg', 'jpeg', 'png']
            ]);
            if (!$validation['valid']) {
                $fileUploadErrors['signature'] = $validation['errors'];
                error_log('Signature validation failed: ' . print_r($validation['errors'], true));
            } else {
                try {
                    // Process signature to remove white background
                    $tmpFile = $_FILES['signature']['tmp_name'];
                    $ext = strtolower(pathinfo($_FILES['signature']['name'], PATHINFO_EXTENSION));
                    
                    // Load image based on type
                    if ($ext === 'png') {
                        $source = imagecreatefrompng($tmpFile);
                    } elseif (in_array($ext, ['jpg', 'jpeg'])) {
                        $source = imagecreatefromjpeg($tmpFile);
                    } else {
                        throw new Exception('Unsupported image format for background removal');
                    }
                    
                    if ($source === false) {
                        throw new Exception('Failed to load signature image');
                    }
                    
                    // Get dimensions
                    $width = imagesx($source);
                    $height = imagesy($source);
                    
                    // Create a new transparent image
                    $output = imagecreatetruecolor($width, $height);
                    imagesavealpha($output, true);
                    $transparent = imagecolorallocatealpha($output, 0, 0, 0, 127);
                    imagefill($output, 0, 0, $transparent);
                    
                    // Copy pixels, making white/near-white pixels transparent
                    for ($y = 0; $y < $height; $y++) {
                        for ($x = 0; $x < $width; $x++) {
                            $rgb = imagecolorat($source, $x, $y);
                            $r = ($rgb >> 16) & 0xFF;
                            $g = ($rgb >> 8) & 0xFF;
                            $b = $rgb & 0xFF;
                            
                            // If pixel is white or light-colored (threshold 180), make it transparent
                            // This removes white, off-white, light gray, beige, and medium gray backgrounds
                            if ($r >= 180 && $g >= 180 && $b >= 180) {
                                continue; // Skip light pixels (already transparent)
                            }
                            
                            // Copy non-white pixels
                            $color = imagecolorallocate($output, $r, $g, $b);
                            imagesetpixel($output, $x, $y, $color);
                        }
                    }
                    
                    // Prepare destination directory and filename
                    $dir = __DIR__ . '/../uploads/student_signatures/';
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    
                    $filename = uniqid() . '_' . time() . '.png';
                    $finalPath = $dir . $filename;
                    
                    // Save processed image directly to final destination
                    if (!imagepng($output, $finalPath, 9)) {
                        imagedestroy($source);
                        imagedestroy($output);
                        throw new Exception('Failed to save processed signature image');
                    }
                    
                    // Clean up image resources
                    imagedestroy($source);
                    imagedestroy($output);
                    
                    // Verify file was created
                    if (!file_exists($finalPath)) {
                        throw new Exception('Processed signature file not found at destination');
                    }
                    
                    $data['signature'] = $filename;
                    error_log('Signature uploaded successfully with background removed: ' . $data['signature']);
                } catch (Throwable $e) {
                    $fileUploadErrors['signature'] = [$e->getMessage()];
                    error_log('Signature upload error: ' . $e->getMessage());
                }
            }
        } elseif (isset($_FILES['signature']) && $_FILES['signature']['error'] !== UPLOAD_ERR_NO_FILE) {
            $fileUploadErrors['signature'] = ['Upload error occurred'];
            error_log('Signature upload error code: ' . $_FILES['signature']['error']);
        }

        // Certificate of Registration (COR) Upload
        if (isset($_FILES['cor_photo']) && $_FILES['cor_photo']['error'] === UPLOAD_ERR_OK) {
            error_log('Processing COR upload: ' . print_r($_FILES['cor_photo'], true));
            $validation = $stuObj->validateFile($_FILES['cor_photo'], [
                'max_size' => 10485760, // 10MB
                'mime_types' => ['image/jpeg', 'image/png', 'application/pdf'],
                'extensions' => ['jpg', 'jpeg', 'png', 'pdf']
            ]);
            if (!$validation['valid']) {
                $fileUploadErrors['cor_photo'] = $validation['errors'];
                error_log('COR validation failed: ' . print_r($validation['errors'], true));
            } else {
                try {
                    $data['cor'] = $stuObj->saveUploadedFile($_FILES['cor_photo'], 'student_cor');
                    error_log('COR uploaded successfully: ' . $data['cor']);
                } catch (Throwable $e) {
                    $fileUploadErrors['cor_photo'] = [$e->getMessage()];
                    error_log('COR upload error: ' . $e->getMessage());
                }
            }
        } elseif (isset($_FILES['cor_photo']) && $_FILES['cor_photo']['error'] !== UPLOAD_ERR_NO_FILE) {
            $fileUploadErrors['cor_photo'] = ['Upload error occurred'];
            error_log('COR upload error code: ' . $_FILES['cor_photo']['error']);
        }

        if (!empty($fileUploadErrors)) {
            $error_msg = 'File upload error(s) occurred. Please check your files and try again.';
            $validation_errors = $fileUploadErrors;
            // Debug: Log file upload errors
            error_log('File upload errors: ' . print_r($fileUploadErrors, true));
        } else {
            // Update student profile
            $result = $stuObj->updateStudent($stu['id'], $data);
            
            if ($result['success']) {
                $msg = $result['message'];
                
                /* Update password in users table if changed */
                if ($passwordUpdated && isset($newPasswordHash)) {
                    try {
                        $db = $stuObj->getDb();
                        $stmt = $db->prepare("UPDATE users SET password_hash = :hash WHERE email = :email");
                        $stmt->execute([
                            ':hash' => $newPasswordHash,
                            ':email' => $_SESSION['email']
                        ]);
                        $msg .= ' Password changed successfully!';
                    } catch (Throwable $e) {
                        error_log('Password update error: ' . $e->getMessage());
                        $error_msg = 'Profile updated but password change failed. Please try again.';
                    }
                }
                
                /* re-read row */
                $stu = $stuObj->findById($stu['id']);
            } else {
                error_log('Edit Profile Error: ' . json_encode($result));
                $error_msg = $result['message'];
                $validation_errors = $result['errors'] ?? [];
            }
        }
    }
}
?>

        <!-- FORM CONTAINER -->
        <div class="form-container">
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

                /* Enhanced Form Styling */
                .form-container {
                    max-width: 1100px;
                    margin: 30px auto;
                    background: white;
                    border-radius: 16px;
                    box-shadow: var(--shadow-md);
                    overflow: hidden;
                    animation: slideInUp 0.5s ease-out;
                }

                @keyframes slideInUp {
                    from {
                        opacity: 0;
                        transform: translateY(20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                .form-body {
                    padding: 50px;
                }

                .form-section-title {
                    font-size: 1.4rem;
                    font-weight: 700;
                    color: var(--primary-dark);
                    margin-bottom: 28px;
                    margin-top: 40px;
                    padding-bottom: 14px;
                    border-bottom: 3px solid var(--primary-light);
                    display: flex;
                    align-items: center;
                    gap: 14px;
                    position: relative;
                    letter-spacing: 0.3px;
                    animation: fadeInLeft 0.6s ease-out;
                }

                @keyframes fadeInLeft {
                    from {
                        opacity: 0;
                        transform: translateX(-20px);
                    }
                    to {
                        opacity: 1;
                        transform: translateX(0);
                    }
                }

                .form-section-title::before {
                    content: '';
                    position: absolute;
                    bottom: -3px;
                    left: 0;
                    width: 80px;
                    height: 3px;
                    background: var(--accent-orange);
                    transition: width 0.4s ease;
                }

                .form-section-title:hover::before {
                    width: 120px;
                }

                .form-section-title:first-of-type {
                    margin-top: 0;
                }

                .form-section-title i {
                    color: var(--accent-orange);
                    font-size: 1.5rem;
                    background: linear-gradient(135deg, rgba(255, 152, 0, 0.1) 0%, rgba(255, 152, 0, 0.05) 100%);
                    padding: 10px;
                    border-radius: 10px;
                    transition: transform 0.3s ease;
                }

                .form-section-title:hover i {
                    transform: scale(1.1) rotate(5deg);
                }

                .form-grid {
                    display: grid;
                    grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
                    gap: 28px;
                    margin-bottom: 28px;
                }

                .form-grid.full {
                    grid-template-columns: 1fr;
                }

                .form-group {
                    display: flex;
                    flex-direction: column;
                    animation: fadeIn 0.5s ease-out;
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

                .form-group label {
                    font-size: 1rem;
                    font-weight: 700;
                    color: var(--primary-dark);
                    margin-bottom: 10px;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    letter-spacing: 0.2px;
                    text-transform: uppercase;
                    font-size: 0.85rem;
                }

                .form-group.required label::after {
                    content: '*';
                    color: #dc3545;
                    font-weight: bold;
                    font-size: 1.1rem;
                }

                .form-group input,
                .form-group select,
                .form-group textarea {
                    padding: 14px 16px;
                    border: 2px solid #e8e8e8;
                    border-radius: 10px;
                    font-size: 1rem;
                    font-family: inherit;
                    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                    background-color: #fafafa;
                    color: #333;
                }

                .form-group input:hover,
                .form-group select:hover,
                .form-group textarea:hover {
                    border-color: var(--primary-light);
                    background-color: #fff;
                }

                .form-group input:focus,
                .form-group select:focus,
                .form-group textarea:focus {
                    outline: none;
                    border-color: var(--primary-medium);
                    background-color: white;
                    box-shadow: 0 0 0 4px rgba(46, 125, 50, 0.1), 0 4px 12px rgba(46, 125, 50, 0.08);
                    transform: translateY(-2px);
                }

                .form-group input::placeholder,
                .form-group textarea::placeholder {
                    color: #999;
                    font-style: italic;
                }

                .form-group textarea {
                    resize: vertical;
                    min-height: 130px;
                    line-height: 1.6;
                }

                /* Password Section */
                .password-section {
                    margin-top: 45px;
                    padding-top: 35px;
                    border-top: 2px solid #e8e8e8;
                }

                .password-toggle-btn {
                    display: inline-flex;
                    align-items: center;
                    gap: 12px;
                    padding: 16px 28px;
                    background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-medium) 100%);
                    color: white;
                    border: none;
                    border-radius: 12px;
                    font-size: 1rem;
                    font-weight: 700;
                    cursor: pointer;
                    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                    box-shadow: 0 4px 16px rgba(46, 125, 50, 0.25);
                    position: relative;
                    overflow: hidden;
                    letter-spacing: 0.3px;
                }

                .password-toggle-btn::before {
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

                .password-toggle-btn:hover::before {
                    width: 300px;
                    height: 300px;
                }

                .password-toggle-btn:hover {
                    transform: translateY(-4px);
                    box-shadow: 0 8px 24px rgba(46, 125, 50, 0.35);
                    background: linear-gradient(135deg, var(--primary-medium) 0%, var(--primary-dark) 100%);
                }

                .password-toggle-btn:active {
                    transform: translateY(-2px);
                }

                .password-toggle-btn i {
                    font-size: 1.2rem;
                    transition: transform 0.3s ease;
                }

                .password-toggle-btn:hover i {
                    transform: scale(1.2) rotate(5deg);
                }

                .password-box {
                    display: none;
                    margin-top: 28px;
                    padding: 30px;
                    background: linear-gradient(135deg, #fffbf0 0%, #fff8e1 100%);
                    border-radius: 12px;
                    border: 2px solid var(--accent-orange);
                    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.1);
                    animation: slideDown 0.4s ease-out;
                }

                @keyframes slideDown {
                    from {
                        opacity: 0;
                        max-height: 0;
                        padding: 0 30px;
                    }
                    to {
                        opacity: 1;
                        max-height: 500px;
                        padding: 30px;
                    }
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
                    background: linear-gradient(45deg, #f0f0f0 25%, transparent 25%, transparent 75%, #f0f0f0 75%), linear-gradient(45deg, #f0f0f0 25%, transparent 25%, transparent 75%, #f0f0f0 75%);
                    background-size: 20px 20px;
                    background-position: 0 0, 10px 10px;
                    border: 1px solid #e0e0e0;
                    padding: 10px;
                }

                .file-name {
                    font-size: 0.85rem;
                    color: #666;
                    margin: 12px 0;
                    word-break: break-all;
                }

                .file-status-box {
                    padding: 16px;
                    background: #fafafa;
                    border: 1px solid #e0e0e0;
                    border-radius: 8px;
                }

                .temp-preview {
                    margin-top: 12px;
                    padding: 12px;
                    background: #e8f5e9;
                    border-radius: 8px;
                    border: 2px dashed #4caf50;
                    animation: fadeIn 0.3s ease;
                }

                @keyframes fadeIn {
                    from {
                        opacity: 0;
                        transform: translateY(-10px);
                    }
                    to {
                        opacity: 1;
                        transform: translateY(0);
                    }
                }

                .temp-preview img {
                    max-width: 100%;
                    max-height: 200px;
                    border-radius: 4px;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                }

                .btn-replace {
                    display: inline-flex;
                    align-items: center;
                    gap: 10px;
                    background: linear-gradient(135deg, var(--accent-orange) 0%, var(--accent-orange-dark) 100%);
                    color: white;
                    border: none;
                    padding: 12px 20px;
                    border-radius: 10px;
                    font-size: 0.9rem;
                    font-weight: 700;
                    cursor: pointer;
                    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                    margin-top: 14px;
                    box-shadow: 0 4px 12px rgba(255, 152, 0, 0.25);
                    position: relative;
                    overflow: hidden;
                    letter-spacing: 0.3px;
                    z-index: 20;
                    pointer-events: auto;
                }

                .btn-replace::before {
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

                .btn-replace:hover::before {
                    width: 300px;
                    height: 300px;
                }

                .btn-replace:hover {
                    transform: translateY(-3px);
                    box-shadow: 0 8px 20px rgba(255, 152, 0, 0.4);
                }

                .btn-replace:active {
                    transform: translateY(-1px);
                }

                .btn-replace i {
                    transition: transform 0.3s ease;
                }

                .btn-replace:hover i {
                    transform: rotate(180deg);
                }

                .file-input-wrapper {
                    margin-top: 12px;
                    padding: 12px;
                    background: #f8f9fa;
                    border: 2px dashed #4caf50;
                    border-radius: 8px;
                    transition: all 0.3s ease;
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
                    background: white;
                }

                .file-input-wrapper input[type="file"]:hover {
                    border-color: #4caf50;
                }

                .file-hint {
                    font-size: 0.8rem;
                    color: #666;
                    margin-top: 8px;
                    font-style: italic;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                }

                .file-hint::before {
                    content: 'ℹ️';
                    font-style: normal;
                }

                /* Action Buttons */
                .form-actions {
                    display: flex;
                    gap: 20px;
                    margin-top: 45px;
                    padding-top: 35px;
                    border-top: 2px solid #e8e8e8;
                    flex-wrap: wrap;
                    justify-content: center;
                }

                .btn-primary,
                .btn-secondary {
                    padding: 16px 36px;
                    border: none;
                    border-radius: 12px;
                    font-size: 1.05rem;
                    font-weight: 700;
                    cursor: pointer;
                    transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
                    text-decoration: none;
                    display: inline-flex;
                    align-items: center;
                    justify-content: center;
                    gap: 12px;
                    min-width: 200px;
                    position: relative;
                    overflow: hidden;
                    letter-spacing: 0.5px;
                }

                .btn-primary::before,
                .btn-secondary::before {
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

                .btn-primary:hover::before,
                .btn-secondary:hover::before {
                    width: 300px;
                    height: 300px;
                }

                .btn-primary {
                    background: linear-gradient(135deg, var(--primary-light) 0%, var(--primary-medium) 100%);
                    color: white;
                    box-shadow: 0 6px 20px rgba(46, 125, 50, 0.25), 0 2px 8px rgba(46, 125, 50, 0.15);
                }

                .btn-primary:hover {
                    transform: translateY(-4px);
                    box-shadow: 0 12px 32px rgba(46, 125, 50, 0.35), 0 4px 12px rgba(46, 125, 50, 0.2);
                    background: linear-gradient(135deg, var(--primary-medium) 0%, var(--primary-dark) 100%);
                    color: white;
                }

                .btn-primary:active {
                    transform: translateY(-2px);
                    box-shadow: 0 6px 20px rgba(46, 125, 50, 0.3);
                }

                .btn-primary i,
                .btn-secondary i {
                    font-size: 1.2rem;
                    transition: transform 0.3s ease;
                }

                .btn-primary:hover i,
                .btn-secondary:hover i {
                    transform: scale(1.2) rotate(5deg);
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
                @media (max-width: 1024px) {
                    /* Hide collapse toggle on mobile - only show hamburger and close button */
                    .sidebar-toggle {
                        display: none !important;
                    }

                    .form-body {
                        padding: 35px;
                    }

                    .form-grid {
                        gap: 24px;
                    }
                }

                @media (max-width: 768px) {
                    .form-container {
                        margin: 20px auto;
                        border-radius: 12px;
                    }

                    .form-body {
                        padding: 28px;
                    }

                    .form-grid {
                        grid-template-columns: 1fr;
                        gap: 20px;
                    }

                    .form-section-title {
                        font-size: 1.2rem;
                        margin-bottom: 20px;
                        margin-top: 32px;
                    }

                    .form-section-title i {
                        font-size: 1.3rem;
                    }

                    .form-actions {
                        flex-direction: column;
                        gap: 16px;
                    }

                    .btn-primary,
                    .btn-secondary {
                        width: 100%;
                        max-width: 100%;
                    }

                    .file-upload-grid {
                        grid-template-columns: 1fr;
                    }

                    .back-to-top {
                        width: 48px;
                        height: 48px;
                        bottom: 20px;
                        right: 20px;
                        font-size: 1.3rem;
                    }

                    .password-toggle-btn {
                        width: 100%;
                        justify-content: center;
                    }
                }

                @media (max-width: 480px) {
                    .form-container {
                        margin: 15px;
                        border-radius: 10px;
                    }

                    .form-body {
                        padding: 20px;
                    }

                    .form-grid {
                        gap: 16px;
                    }

                    .form-group input,
                    .form-group select,
                    .form-group textarea {
                        padding: 12px 14px;
                        font-size: 0.95rem;
                    }

                    .form-section-title {
                        font-size: 1.1rem;
                        margin: 28px 0 18px 0;
                        gap: 10px;
                    }

                    .form-section-title i {
                        font-size: 1.2rem;
                        padding: 8px;
                    }

                    .password-box {
                        padding: 20px;
                    }

                    .password-toggle-btn {
                        padding: 14px 24px;
                        font-size: 0.95rem;
                    }

                    .btn-primary,
                    .btn-secondary {
                        padding: 14px 28px;
                        font-size: 1rem;
                    }

                    .btn-replace {
                        width: 100%;
                        justify-content: center;
                    }

                    .back-to-top {
                        width: 45px;
                        height: 45px;
                        bottom: 15px;
                        right: 15px;
                        font-size: 1.2rem;
                    }
                }

                /* Validation States */
                .form-group input:invalid:not(:placeholder-shown),
                .form-group select:invalid {
                    border-color: var(--primary-light);
                    background-color: #fff5f5;
                }

                .form-group input:valid:not(:placeholder-shown),
                .form-group select:valid {
                    border-color: var(--primary-light);
                    background-color: #f1f8f4;
                }

                /* Loading State */
                .btn-primary:disabled {
                    opacity: 0.6;
                    cursor: not-allowed;
                    transform: none;
                    box-shadow: none;
                }

                .btn-primary:disabled:hover {
                    transform: none;
                    box-shadow: none;
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
                            <i class="fas fa-lock"></i>
                            <span>Change Password</span>
                        </button>
                        <div id="pwdBox" class="password-box">
                            <div class="form-grid">
                                <div class="form-group required">
                                    <label>Current Password</label>
                                    <input type="password" name="old_password" id="old_password" placeholder="Enter your current password" disabled>
                                </div>
                            </div>

                            <div class="form-grid">
                                <div class="form-group required">
                                    <label>New Password</label>
                                    <input type="password" name="new_password" id="new_password" placeholder="Minimum 8 characters" disabled>
                                </div>
                                <div class="form-group required">
                                    <label>Confirm New Password</label>
                                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Re-enter new password" disabled>
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
                                        <button type="button" class="btn-replace" onclick="toggleFileInput(this)">
                                            <i class="fas fa-sync-alt"></i> Replace File
                                        </button>
                                        <div class="file-input-wrapper" style="display: none; margin-top: 10px;">
                                            <input type="file" name="profile_photo" accept="image/jpeg,image/png,.jpg,.jpeg,.png">
                                            <div class="file-hint">JPG, PNG (Max 5MB)</div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="file-input-wrapper">
                                        <input type="file" name="profile_photo" accept="image/jpeg,image/png,.jpg,.jpeg,.png">
                                        <div class="file-hint">JPG, PNG (Max 5MB)</div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <div class="form-group">
                                <label class="file-upload-label">Signature</label>
                                <?php if (!empty($stu['signature'])): ?>
                                    <div class="file-status-box">
                                        <div class="file-status-indicator">File Uploaded</div>
                                        <img src="../uploads/student_signatures/<?= htmlspecialchars($stu['signature']) ?>" alt="Current Signature" class="file-preview-image signature-preview" crossorigin="anonymous">
                                        <p class="file-name">Current: <?= htmlspecialchars($stu['signature']) ?></p>
                                        <button type="button" class="btn-replace" onclick="toggleFileInput(this)">
                                            <i class="fas fa-sync-alt"></i> Replace File
                                        </button>
                                        <div class="file-input-wrapper" style="display: none; margin-top: 10px;">
                                            <input type="file" name="signature" accept="image/jpeg,image/png,.jpg,.jpeg,.png">
                                            <div class="file-hint">JPG, PNG (Max 5MB)</div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="file-input-wrapper">
                                        <input type="file" name="signature" accept="image/jpeg,image/png,.jpg,.jpeg,.png">
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
                                        <button type="button" class="btn-replace" onclick="toggleFileInput(this)">
                                            <i class="fas fa-sync-alt"></i> Replace File
                                        </button>
                                        <div class="file-input-wrapper" style="display: none; margin-top: 10px;">
                                            <input type="file" name="cor_photo" accept="image/jpeg,image/png,application/pdf,.jpg,.jpeg,.png,.pdf">
                                            <div class="file-hint">JPG, PNG, PDF (Max 10MB)</div>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="file-input-wrapper">
                                        <input type="file" name="cor_photo" accept="image/jpeg,image/png,application/pdf,.jpg,.jpeg,.png,.pdf">
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

    <!-- Back to Top Button -->
    <button id="backToTopBtn" class="back-to-top" onclick="scrollToTop()" title="Back to top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // ▬▬▬▬ GLOBAL FUNCTIONS ACCESSIBLE FROM ONCLICK ▬▬▬▬
        
        // Toggle file input for replacement
        function toggleFileInput(button) {
            const fileInputWrapper = button.nextElementSibling;
            
            if (!fileInputWrapper || !fileInputWrapper.classList.contains('file-input-wrapper')) {
                console.error('File input wrapper not found', button, fileInputWrapper);
                return;
            }
            
            const computedStyle = window.getComputedStyle(fileInputWrapper);
            const isHidden = computedStyle.display === 'none';
            
            if (isHidden) {
                fileInputWrapper.style.display = 'block';
                button.innerHTML = '<i class="fas fa-times"></i> Cancel';
                button.style.background = 'linear-gradient(135deg, #f44336 0%, #d32f2f 100%)';
                button.style.color = 'white';
            } else {
                fileInputWrapper.style.display = 'none';
                button.innerHTML = '<i class="fas fa-sync-alt"></i> Replace File';
                button.style.background = '';
                button.style.color = '';
                
                const fileInput = fileInputWrapper.querySelector('input[type="file"]');
                if (fileInput) {
                    fileInput.value = '';
                }
                
                const tempPreview = fileInputWrapper.querySelector('.temp-preview');
                if (tempPreview) {
                    tempPreview.remove();
                }
            }
        }

        // Scroll to top
        function scrollToTop() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        }

        // Toggle password section
        function togglePasswordSection() {
            const pwdBox = document.getElementById('pwdBox');
            const toggleBtn = document.getElementById('togglePwdBtn');
            const oldPwd = document.getElementById('old_password');
            const newPwd = document.getElementById('new_password');
            const confirmPwd = document.getElementById('confirm_password');
            
            pwdBox.classList.toggle('active');
            
            if (pwdBox.classList.contains('active')) {
                toggleBtn.innerHTML = '<i class="fas fa-lock-open"></i><span>Cancel Password Change</span>';
                // Enable password fields when section is open
                oldPwd.disabled = false;
                newPwd.disabled = false;
                confirmPwd.disabled = false;
            } else {
                toggleBtn.innerHTML = '<i class="fas fa-lock"></i><span>Change Password</span>';
                // Clear and disable password fields when section is closed
                oldPwd.value = '';
                newPwd.value = '';
                confirmPwd.value = '';
                oldPwd.disabled = true;
                newPwd.disabled = true;
                confirmPwd.disabled = true;
            }
        }

        // Handle form submit
        function handleSubmit(event) {
            event.preventDefault();
            const errors = validateForm();

            if (errors.length > 0) {
                errors.forEach((error, index) => {
                    setTimeout(() => {
                        showNotification(error, 'warning');
                    }, index * 500);
                });
                return false;
            }

            // Check if any files are selected
            const fileInputs = document.querySelectorAll('input[type="file"]');
            let filesSelected = 0;
            fileInputs.forEach(input => {
                if (input.files && input.files.length > 0) {
                    filesSelected++;
                }
            });

            let message = 'Saving your profile changes...';
            if (filesSelected > 0) {
                message = `Uploading ${filesSelected} file(s) and saving your profile changes...`;
            }

            Swal.fire({
                title: 'Confirm Save',
                html: message,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#4caf50',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, save changes!',
                cancelButtonText: 'Cancel',
                draggable: true,
                didOpen: (modal) => {
                    modal.style.borderRadius = '12px';
                    modal.style.boxShadow = '0px 8px 32px rgba(0, 0, 0, 0.2)';
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Processing...',
                        html: 'Please wait while we save your changes.',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    event.target.closest('form').submit();
                }
            });
        }

        // Form validation
        function validateForm() {
            const firstName = document.querySelector('input[name="first_name"]').value.trim();
            const lastName = document.querySelector('input[name="last_name"]').value.trim();
            const contactNumber = document.querySelector('input[name="contact_number"]').value.trim();
            const course = document.querySelector('select[name="course"]').value.trim();
            const yearLevel = document.querySelector('select[name="year_level"]').value.trim();

            const errors = [];

            if (!firstName) errors.push('First name is required');
            if (!lastName) errors.push('Last name is required');
            if (!contactNumber) {
                errors.push('Contact number is required');
            } else if (!/^[0-9\s\-\+\(\)]{10,}$/.test(contactNumber)) {
                errors.push('Contact number must be valid (at least 10 digits)');
            }
            if (!course) errors.push('Course selection is required');
            if (!yearLevel) errors.push('Year level selection is required');

            return errors;
        }

        // Show notification
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

        // ▬▬▬▬ DOM READY EVENT LISTENERS ▬▬▬▬
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
        // ▬▬▬▬ BACK-TO-TOP SCROLL DETECTION ▬▬▬▬
        window.addEventListener('scroll', function() {
            const backToTopBtn = document.getElementById('backToTopBtn');
            if (backToTopBtn) {
                if (window.scrollY > 300) {
                    backToTopBtn.style.display = 'flex';
                } else {
                    backToTopBtn.style.display = 'none';
                }
            }
        });

        // ▬▬▬▬ FILE PREVIEW FUNCTIONALITY ▬▬▬▬
        document.addEventListener('DOMContentLoaded', function() {
            const fileInputs = document.querySelectorAll('input[type="file"]');
            
            fileInputs.forEach(input => {
                input.addEventListener('change', function(e) {
                    const file = e.target.files[0];
                    if (file) {
                        const fileSize = (file.size / (1024 * 1024)).toFixed(2); // Convert to MB
                        const fileName = file.name;
                        
                        // Show file selection notification with instructions
                        Swal.fire({
                            title: 'File Selected!',
                            html: `
                                <div style="text-align: left;">
                                    <p><strong>File:</strong> ${fileName}</p>
                                    <p><strong>Size:</strong> ${fileSize} MB</p>
                                    <hr style="margin: 15px 0;">
                                    <p style="color: #ff9800; font-weight: bold;">
                                        <i class="fas fa-info-circle"></i> Don't forget to click "Save Changes" button below to upload this file!
                                    </p>
                                </div>
                            `,
                            icon: 'info',
                            confirmButtonColor: '#4caf50',
                            confirmButtonText: 'Got it!',
                            timer: 5000,
                            timerProgressBar: true,
                            didOpen: (modal) => {
                                modal.style.borderRadius = '12px';
                                modal.style.boxShadow = '0px 8px 32px rgba(0, 0, 0, 0.2)';
                            }
                        });
                        
                        // Preview image if it's an image file
                        if (file.type.startsWith('image/')) {
                            const reader = new FileReader();
                            reader.onload = function(event) {
                                // Create a temporary preview
                                const tempPreview = document.createElement('div');
                                tempPreview.style.cssText = 'margin-top: 10px; padding: 10px; background: #e8f5e9; border-radius: 6px; border: 2px dashed #4caf50;';
                                tempPreview.innerHTML = `
                                    <p style="margin: 0 0 8px 0; font-weight: 600; color: #2e7d32; font-size: 0.85rem;">
                                        <i class="fas fa-eye"></i> New Preview (will upload when you save):
                                    </p>
                                    <img src="${event.target.result}" style="max-width: 100%; max-height: 200px; border-radius: 4px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" alt="Preview">
                                `;
                                
                                // Remove any existing preview in the file-input-wrapper
                                const existingPreview = input.closest('.file-input-wrapper').querySelector('.temp-preview');
                                if (existingPreview) {
                                    existingPreview.remove();
                                }
                                
                                tempPreview.className = 'temp-preview';
                                input.closest('.file-input-wrapper').appendChild(tempPreview);
                            };
                            reader.readAsDataURL(file);
                        }
                    }
                });
            });
        });
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
    </script>

    <script>
        // Automatic Background Removal for Signature Images (with Debug Logging)
        (function() {
            'use strict';
            const THRESHOLD = 180; // Lowered threshold to catch more background colors
            
            function removeWhiteBg(img, returnUrl = false) {
                console.log('removeWhiteBg called:', img.src, 'returnUrl:', returnUrl);
                
                if (!returnUrl && img.dataset.bgRemoved) {
                    console.log('Already processed, skipping');
                    return;
                }
                
                const w = img.naturalWidth || img.width;
                const h = img.naturalHeight || img.height;
                console.log('Image dimensions:', w, 'x', h);
                
                if (!w || !h) {
                    console.warn('Invalid dimensions');
                    return returnUrl ? img.src : null;
                }
                
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = w;
                canvas.height = h;
                
                try {
                    ctx.drawImage(img, 0, 0, w, h);
                    const imgData = ctx.getImageData(0, 0, w, h);
                    const data = imgData.data;
                    
                    let pixelsChanged = 0;
                    for (let i = 0; i < data.length; i += 4) {
                        if (data[i] >= THRESHOLD && data[i+1] >= THRESHOLD && data[i+2] >= THRESHOLD) {
                            data[i+3] = 0;
                            pixelsChanged++;
                        }
                    }
                    
                    console.log('Pixels made transparent:', pixelsChanged);
                    
                    ctx.putImageData(imgData, 0, 0);
                    const result = canvas.toDataURL('image/png');
                    
                    if (returnUrl) return result;
                    
                    img.src = result;
                    img.dataset.bgRemoved = 'true';
                    console.log('Background removed successfully!');
                } catch(e) {
                    console.error('Background removal failed:', e);
                    console.error('Error details:', e.name, e.message);
                    return returnUrl ? img.src : null;
                }
            }
            
            function processExisting() {
                console.log('processExisting called');
                const images = document.querySelectorAll('.file-preview-image.signature-preview');
                console.log('Found signature images:', images.length);
                
                images.forEach((img, index) => {
                    console.log(`Processing image ${index}:`, img.src);
                    
                    if (!img.crossOrigin) {
                        console.log('Setting crossOrigin=anonymous');
                        img.crossOrigin = 'anonymous';
                    }
                    
                    if (img.complete && img.naturalWidth) {
                        console.log('Image already loaded, processing now');
                        removeWhiteBg(img);
                    } else {
                        console.log('Image not loaded yet, waiting for load event');
                        img.addEventListener('load', () => {
                            console.log('Load event fired');
                            removeWhiteBg(img);
                        }, {once: true});
                    }
                });
            }
            
            function setupUploadHandler() {
                document.querySelectorAll('input[name="signature"]').forEach(input => {
                    input.addEventListener('change', function(e) {
                        const file = e.target.files[0];
                        if (!file || !file.type.startsWith('image/')) return;
                        
                        const reader = new FileReader();
                        reader.onload = function(evt) {
                            const img = new Image();
                            img.onload = function() {
                                const processed = removeWhiteBg(img, true);
                                if (!processed) return;
                                
                                const wrapper = input.closest('.file-input-wrapper');
                                let preview = wrapper.querySelector('.temp-preview');
                                
                                if (!preview) {
                                    preview = document.createElement('div');
                                    preview.className = 'temp-preview';
                                    preview.style.cssText = 'margin-top:12px;padding:12px;background:linear-gradient(45deg,#f0f0f0 25%,transparent 25%,transparent 75%,#f0f0f0 75%),linear-gradient(45deg,#f0f0f0 25%,transparent 25%,transparent 75%,#f0f0f0 75%);background-size:20px 20px;background-position:0 0,10px 10px;border-radius:8px;border:2px solid #4caf50';
                                    wrapper.appendChild(preview);
                                }
                                
                                preview.innerHTML = '<p style="margin:0 0 8px;font-weight:600;color:#2e7d32;font-size:0.85rem"><i class="fas fa-check-circle"></i> Preview (background removed):</p><img src="' + processed + '" style="max-width:100%;max-height:200px;border-radius:4px" alt="Preview">';
                            };
                            img.src = evt.target.result;
                        };
                        reader.readAsDataURL(file);
                    });
                });
            }
            
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', function() {
                    processExisting();
                    setupUploadHandler();
                });
            } else {
                processExisting();
                setupUploadHandler();
            }
        })();
    </script>

    <script src="../assets/js/bootstrap.bundle.min.js"></script>
</body>

</html>