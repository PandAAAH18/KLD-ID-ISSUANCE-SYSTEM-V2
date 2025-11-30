<?php
session_start();
require_once 'classes/StudentManager.php';

// Check admin access
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    http_response_code(403);
    exit('Access denied');
}

$studentModel = new StudentManager();

// Log for debugging (remove after verification)
error_log("student_details.php GET params: " . print_r($_GET, true));

$studentId = null;
$student = null;

// Handle ?id=NUMERIC (from admin_students.php links)
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $studentId = (int)$_GET['id'];
    $student = $studentModel->getStudentById($studentId);
}
// Handle ?student_id= (from dashboard/QR/manual search) - supports both internal ID (numeric) and business student_id (string)
elseif (isset($_GET['student_id']) && !empty(trim($_GET['student_id']))) {
    $inputId = trim($_GET['student_id']);
    
    if (is_numeric($inputId)) {
        // Treat as internal DB id
        $tempStudent = $studentModel->getStudentById((int)$inputId);
    } else {
        // Treat as business student_id
        $tempStudent = $studentModel->getStudentByStudentId($inputId);
    }
    
    if ($tempStudent) {
        $studentId = $tempStudent['id'];
        $student = $tempStudent;
        error_log("Resolved student_id '$inputId' to DB id $studentId");
    } else {
        error_log("No student found for input: $inputId");
        header('Location: admin_students.php?error=no_student_found');
        exit;
    }
}

if (!$student) {
    error_log("No valid student found (final check)");
    header('Location: admin_students.php?error=no_student_found');
    exit;
}

error_log("Student loaded successfully: id=$studentId, name=" . $student['first_name'] . ' ' . $student['last_name']);


// Handle form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle COR deletion
    if (isset($_POST['delete_cor'])) {
        if (!empty($student['cor'])) {
            $corPath = '../uploads/student_cor/' . $student['cor'];
            if (file_exists($corPath)) {
                unlink($corPath);
            }
            
            $db = $studentModel->getDb();
            $sql = "UPDATE student SET cor = NULL WHERE id = :id";
            $stmt = $db->prepare($sql);
            if ($stmt->execute([':id' => $studentId])) {
                $message = '✓ COR file deleted successfully!';
                $messageType = 'success';
                $student = $studentModel->getStudentById($studentId);
            } else {
                $message = '✗ Failed to delete COR file.';
                $messageType = 'error';
            }
        }
    }
    
    // Handle Signature deletion
    elseif (isset($_POST['delete_signature'])) {
        if (!empty($student['signature'])) {
            $sigPath = '../uploads/student_signatures/' . $student['signature'];
            if (file_exists($sigPath)) {
                unlink($sigPath);
            }
            
            $db = $studentModel->getDb();
            $sql = "UPDATE student SET signature = NULL WHERE id = :id";
            $stmt = $db->prepare($sql);
            if ($stmt->execute([':id' => $studentId])) {
                $message = '✓ Signature file deleted successfully!';
                $messageType = 'success';
                $student = $studentModel->getStudentById($studentId);
            } else {
                $message = '✗ Failed to delete signature file.';
                $messageType = 'error';
            }
        }
    }
    
    // Handle Delete photo
    elseif (isset($_POST['delete_photo'])) {
        if (!empty($student['photo_path'])) {
            $photoPath = '../uploads/student_photos/' . $student['photo_path'];
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }
            
            $db = $studentModel->getDb();
            $sql = "UPDATE student SET photo_path = NULL WHERE id = :id";
            $stmt = $db->prepare($sql);
            if ($stmt->execute([':id' => $studentId])) {
                $message = '✓ Photo deleted successfully!';
                $messageType = 'success';
                $student = $studentModel->getStudentById($studentId);
            } else {
                $message = '✗ Failed to delete photo.';
                $messageType = 'error';
            }
        }
    }
    
    // Handle regular form updates
    else {
        // Remove student_id from POST to avoid overwriting
        $postData = $_POST;
        unset($postData['student_id']);

        // Filter empty fields
        $updateData = array_filter($postData, function($value) {
            return $value !== '' && $value !== null;
        });

        if (!empty($updateData)) {
            $result = $studentModel->updateStudent($studentId, $updateData);
            
            if ($result) {
                $message = '✓ Student information updated successfully!';
                $messageType = 'success';
                
                // Refresh student data
                $student = $studentModel->getStudentById($studentId);
            } else {
                $message = '✗ Failed to update student information.';
                $messageType = 'error';
            }
        }
    }
}

// Handle photo upload
if (isset($_FILES['student_photo']) && $_FILES['student_photo']['error'] === UPLOAD_ERR_OK) {
    $photoResult = $studentModel->updateStudentPhoto($studentId, $_FILES['student_photo']);
    
    if ($photoResult['success']) {
        $message = '✓ Photo uploaded successfully!';
        $messageType = 'success';
        $student = $studentModel->getStudentById($studentId);
    } else {
        $message = '✗ ' . $photoResult['message'];
        $messageType = 'error';
    }
}

// Handle COR upload
if (isset($_FILES['student_cor']) && $_FILES['student_cor']['error'] === UPLOAD_ERR_OK) {
    $corResult = uploadStudentDocument($studentId, $_FILES['student_cor'], 'student_cor');
    
    if ($corResult['success']) {
        $message = '✓ COR photo uploaded successfully!';
        $messageType = 'success';
        $student = $studentModel->getStudentById($studentId);
    } else {
        $message = '✗ ' . $corResult['message'];
        $messageType = 'error';
    }
}

// Handle Signature upload
if (isset($_FILES['student_signature']) && $_FILES['student_signature']['error'] === UPLOAD_ERR_OK) {
    $signatureResult = uploadStudentDocument($studentId, $_FILES['student_signature'], 'student_signature');
    
    if ($signatureResult['success']) {
        $message = '✓ Signature photo uploaded successfully!';
        $messageType = 'success';
        $student = $studentModel->getStudentById($studentId);
    } else {
        $message = '✗ ' . $signatureResult['message'];
        $messageType = 'error';
    }
}

// Helper function for uploading student documents (COR, Signature, etc.)
function uploadStudentDocument($studentId, $file, $documentType) {
    try {
        global $studentModel;
        
        $student = $studentModel->getStudentById($studentId);
        if (!$student) {
            throw new Exception("Student not found");
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload error: " . getUploadErrorMessage($file['error']));
        }

        $maxSize = 5 * 1024 * 1024; // 5MB for documents
        if ($file['size'] > $maxSize) {
            throw new Exception("File too large. Maximum size: 5MB");
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'application/pdf'];
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];
        
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($mimeType, $allowedTypes) || !in_array($fileExtension, $allowedExtensions)) {
            throw new Exception("Invalid file type. Allowed: JPEG, PNG, GIF, WebP, PDF");
        }

        $uploadDir = '../uploads/' . $documentType . '/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // Get the database column name for this document type
        $columnName = $documentType;
        if ($documentType === 'student_cor') {
            $columnName = 'cor';
        } elseif ($documentType === 'student_signature') {
            $columnName = 'signature';
        }

        // Delete old file if exists
        if (!empty($student[$columnName])) {
            $oldPath = $uploadDir . $student[$columnName];
            if (file_exists($oldPath) && is_file($oldPath)) {
                unlink($oldPath);
            }
        }

        $sanitizedEmail = preg_replace('/[^a-zA-Z0-9._-]/', '_', $student['email']);
        $filename = $sanitizedEmail . '_' . $documentType . '_' . time() . '.' . $fileExtension;
        $filePath = $uploadDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $filePath)) {
            throw new Exception("Failed to save uploaded file");
        }

        // Update database
        global $db;
        if (!isset($db)) {
            $db = $studentModel->getDb();
        }
        
        $sql = "UPDATE student SET $columnName = :filename WHERE id = :id";
        $stmt = $db->prepare($sql);
        $success = $stmt->execute([
            ':filename' => $filename,
            ':id' => $studentId
        ]);

        if (!$success) {
            unlink($filePath);
            throw new Exception("Failed to update student record");
        }

        return [
            'success' => true,
            'filename' => $filename,
            'filepath' => $filePath,
            'message' => ucfirst(str_replace('_', ' ', $documentType)) . ' uploaded successfully'
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// Helper function for upload error messages
function getUploadErrorMessage($errorCode) {
    $uploadErrors = [
        UPLOAD_ERR_INI_SIZE => 'The uploaded file exceeds the upload_max_filesize directive in php.ini',
        UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the MAX_FILE_SIZE directive',
        UPLOAD_ERR_PARTIAL => 'The uploaded file was only partially uploaded',
        UPLOAD_ERR_NO_FILE => 'No file was uploaded',
        UPLOAD_ERR_NO_TMP_DIR => 'Missing a temporary folder',
        UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload',
    ];
    
    return $uploadErrors[$errorCode] ?? 'Unknown upload error';
}

require_once 'admin_header.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details - Admin Panel</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>

<body class="admin-body">
    <!-- Back to Top Button -->
    <button class="back-to-top" onclick="scrollToTop()">
        <i class="fas fa-chevron-up"></i>
    </button>

    <div class="admin-container">
        <!-- ========== PAGE HEADER ========== -->
        <div class="page-header">
            <h2>
                <i class="fas fa-user-circle"></i> 
                <?= htmlspecialchars($student['first_name'] . ' ' . $student['last_name']) ?>
            </h2>
            <p>View and manage student information</p>
        </div>

        <!-- ========== MESSAGES ========== -->
        <?php if ($message): ?>
        <div class="alert-banner alert-<?= $messageType ?>">
            <i class="fas fa-<?= $messageType === 'success' ? 'check-circle' : 'exclamation-circle' ?>"></i>
            <div><?= $message ?></div>
        </div>
        <?php endif; ?>

        <!-- ========== BACK BUTTON ========== -->
        <div style="margin-bottom: 20px;">
            <a href="admin_students.php" class="btn btn-outline">
                <i class="fas fa-arrow-left"></i> Back to Students
            </a>
        </div>

        <!-- ========== MAIN CONTENT GRID ========== -->
        <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 25px; margin-bottom: 25px;">

            <!-- ========== LEFT COLUMN: PHOTO & QUICK INFO ========== -->
            <div>
                <!-- Photo Section -->
                <div class="admin-card">
                    <div class="admin-card-header">
                        <span><i class="fas fa-camera"></i> Student Photo</span>
                    </div>
                    <div class="admin-card-body" style="text-align: center;">
                        <div id="photoContainer">
                            <?php if (!empty($student['photo'])): ?>
                            <img src="../uploads/student_photos/<?= htmlspecialchars($student['photo']) ?>" 
                                 alt="Student Photo" style="max-width: 100%; height: auto; border-radius: 8px; max-height: 250px; margin-bottom: 15px;">
                            <?php else: ?>
                            <div style="width: 100%; padding: 40px 20px; background: #f0f0f0; border-radius: 8px; color: #999; margin-bottom: 15px;">
                                <i class="fas fa-image" style="font-size: 3rem; display: block; margin-bottom: 10px;"></i>
                                <p>No photo uploaded</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Photo Upload Form -->
                        <form method="POST" enctype="multipart/form-data" style="margin-bottom: 10px;">
                            <div style="display: flex; gap: 10px; flex-direction: column;">
                                <input type="file" name="student_photo" accept="image/*" class="form-input" required 
                                       style="padding: 10px; font-size: 0.9rem;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload"></i> Upload Photo
                                </button>
                            </div>
                        </form>

                        <?php if (!empty($student['photo_path'])): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="delete_photo" value="1">
                            <button type="submit" class="btn btn-danger" style="width: 100%;"
                                    onclick="return confirm('Delete this photo?')">
                                <i class="fas fa-trash"></i> Delete Photo
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Status -->
                <div class="admin-card" style="margin-top: 20px;">
                    <div class="admin-card-header">
                        <span><i class="fas fa-info-circle"></i> Status</span>
                    </div>
                    <div class="admin-card-body">
                        <div style="margin-bottom: 15px;">
                            <div style="font-size: 0.85rem; color: #666; margin-bottom: 5px;">Profile Completion</div>
                            <span class="status-badge <?= $student['profile_completed'] ? 'status-completed' : 'status-incomplete' ?>">
                                <?= $student['profile_completed'] ? 'Complete' : 'Incomplete' ?>
                            </span>
                        </div>
                        <div style="margin-bottom: 15px;">
                            <div style="font-size: 0.85rem; color: #666; margin-bottom: 5px;">Record Created</div>
                            <div style="font-size: 0.9rem;">
                                <?= date('M d, Y', strtotime($student['created_at'] ?? 'now')) ?>
                            </div>
                        </div>
                        <?php if (!empty($student['updated_at'])): ?>
                        <div>
                            <div style="font-size: 0.85rem; color: #666; margin-bottom: 5px;">Last Updated</div>
                            <div style="font-size: 0.9rem;">
                                <?= date('M d, Y H:i', strtotime($student['updated_at'])) ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- COR Document -->
                <div class="admin-card" style="margin-top: 20px;">
                    <div class="admin-card-header">
                        <span><i class="fas fa-file-pdf"></i> Certificate of Registration (COR)</span>
                    </div>
                    <div class="admin-card-body" style="text-align: center;">
                        <div id="corContainer">
                            <?php if (!empty($student['cor'])): ?>
                            <div style="margin-bottom: 15px;">
                                <div style="font-size: 0.85rem; color: #666; margin-bottom: 10px;">Current COR File</div>
                                <?php if (strtolower(pathinfo($student['cor'], PATHINFO_EXTENSION)) === 'pdf'): ?>
                                <a href="../uploads/student_cor/<?= htmlspecialchars($student['cor']) ?>" 
                                   target="_blank" class="btn btn-view" style="display: inline-block;">
                                    <i class="fas fa-file-pdf"></i> View PDF
                                </a>
                                <?php else: ?>
                                <img src="../uploads/student_cor/<?= htmlspecialchars($student['cor']) ?>" 
                                     alt="Student COR" style="max-width: 100%; height: auto; border-radius: 8px; max-height: 200px;">
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div style="width: 100%; padding: 30px 20px; background: #f0f0f0; border-radius: 8px; color: #999; margin-bottom: 15px;">
                                <i class="fas fa-file" style="font-size: 2.5rem; display: block; margin-bottom: 10px;"></i>
                                <p>No COR uploaded</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- COR Upload Form -->
                        <form method="POST" enctype="multipart/form-data" style="margin-bottom: 10px;">
                            <div style="display: flex; gap: 10px; flex-direction: column;">
                                <input type="file" name="student_cor" accept="image/*,.pdf" class="form-input" required 
                                       style="padding: 10px; font-size: 0.9rem;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload"></i> Upload COR
                                </button>
                            </div>
                        </form>

                        <?php if (!empty($student['cor'])): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="delete_cor" value="1">
                            <button type="submit" class="btn btn-danger" style="width: 100%;"
                                    onclick="return confirm('Delete this COR file?')">
                                <i class="fas fa-trash"></i> Delete COR
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Signature Document -->
                <div class="admin-card" style="margin-top: 20px;">
                    <div class="admin-card-header">
                        <span><i class="fas fa-pen"></i> Student Signature</span>
                    </div>
                    <div class="admin-card-body" style="text-align: center;">
                        <div id="signatureContainer">
                            <?php if (!empty($student['signature'])): ?>
                            <div style="margin-bottom: 15px;">
                                <div style="font-size: 0.85rem; color: #666; margin-bottom: 10px;">Current Signature</div>
                                <?php if (strtolower(pathinfo($student['signature'], PATHINFO_EXTENSION)) === 'pdf'): ?>
                                <a href="../uploads/student_signatures/<?= htmlspecialchars($student['signature']) ?>" 
                                   target="_blank" class="btn btn-view" style="display: inline-block;">
                                    <i class="fas fa-file-pdf"></i> View PDF
                                </a>
                                <?php else: ?>
                                <img src="../uploads/student_signatures/<?= htmlspecialchars($student['signature']) ?>" 
                                     alt="Student Signature" style="max-width: 100%; height: auto; border-radius: 8px; max-height: 150px;">
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div style="width: 100%; padding: 30px 20px; background: #f0f0f0; border-radius: 8px; color: #999; margin-bottom: 15px;">
                                <i class="fas fa-pen" style="font-size: 2.5rem; display: block; margin-bottom: 10px;"></i>
                                <p>No signature uploaded</p>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Signature Upload Form -->
                        <form method="POST" enctype="multipart/form-data" style="margin-bottom: 10px;">
                            <div style="display: flex; gap: 10px; flex-direction: column;">
                                <input type="file" name="student_signature" accept="image/*,.pdf" class="form-input" required 
                                       style="padding: 10px; font-size: 0.9rem;">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-upload"></i> Upload Signature
                                </button>
                            </div>
                        </form>

                        <?php if (!empty($student['signature'])): ?>
                        <form method="POST" style="display: inline;">
                            <input type="hidden" name="delete_signature" value="1">
                            <button type="submit" class="btn btn-danger" style="width: 100%;"
                                    onclick="return confirm('Delete this signature file?')">
                                <i class="fas fa-trash"></i> Delete Signature
                            </button>
                        </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ========== RIGHT COLUMN: EDIT FORM ========== -->
            <div>
                <form method="POST" class="admin-card">
                    <div class="admin-card-header">
                        <span><i class="fas fa-edit"></i> Student Information</span>
                    </div>
                    <div class="admin-card-body">
                        <!-- Personal Information Section -->
                        <h4 style="color: var(--school-green); font-weight: 600; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
                            Personal Information
                        </h4>

                        <div class="form-row">
                            <div class="form-group">
                                <label>First Name *</label>
                                <input type="text" name="first_name" class="form-input" 
                                       value="<?= htmlspecialchars($student['first_name'] ?? '') ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Last Name *</label>
                                <input type="text" name="last_name" class="form-input" 
                                       value="<?= htmlspecialchars($student['last_name'] ?? '') ?>" required>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" class="form-input" 
                                   value="<?= htmlspecialchars($student['email'] ?? '') ?>" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Contact Number</label>
                                <input type="tel" name="contact_number" class="form-input" 
                                       value="<?= htmlspecialchars($student['contact_number'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Blood Type</label>
                                <select name="blood_type" class="form-select">
                                    <option value="">-- Select --</option>
                                    <option value="O+" <?= ($student['blood_type'] ?? '') === 'O+' ? 'selected' : '' ?>>O+</option>
                                    <option value="O-" <?= ($student['blood_type'] ?? '') === 'O-' ? 'selected' : '' ?>>O-</option>
                                    <option value="A+" <?= ($student['blood_type'] ?? '') === 'A+' ? 'selected' : '' ?>>A+</option>
                                    <option value="A-" <?= ($student['blood_type'] ?? '') === 'A-' ? 'selected' : '' ?>>A-</option>
                                    <option value="B+" <?= ($student['blood_type'] ?? '') === 'B+' ? 'selected' : '' ?>>B+</option>
                                    <option value="B-" <?= ($student['blood_type'] ?? '') === 'B-' ? 'selected' : '' ?>>B-</option>
                                    <option value="AB+" <?= ($student['blood_type'] ?? '') === 'AB+' ? 'selected' : '' ?>>AB+</option>
                                    <option value="AB-" <?= ($student['blood_type'] ?? '') === 'AB-' ? 'selected' : '' ?>>AB-</option>
                                </select>
                            </div>
                        </div>

                        <!-- Academic Information Section -->
                        <h4 style="color: var(--school-green); font-weight: 600; margin-top: 25px; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
                            Academic Information
                        </h4>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Student ID</label>
                                <input type="text" name="student_id" class="form-input" 
                                       value="<?= htmlspecialchars($student['student_id'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Year Level</label>
                                <select name="year_level" class="form-select">
                                    <option value="">-- Select --</option>
                                    <option value="1st Year" <?= ($student['year_level'] ?? '') === '1st Year' ? 'selected' : '' ?>>1st Year</option>
                                    <option value="2nd Year" <?= ($student['year_level'] ?? '') === '2nd Year' ? 'selected' : '' ?>>2nd Year</option>
                                    <option value="3rd Year" <?= ($student['year_level'] ?? '') === '3rd Year' ? 'selected' : '' ?>>3rd Year</option>
                                    <option value="4th Year" <?= ($student['year_level'] ?? '') === '4th Year' ? 'selected' : '' ?>>4th Year</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Course</label>
                            <select name="course" class="form-select">
                                <option value="">-- Select --</option>
                                <option value="BS Computer Science" <?= ($student['course'] ?? '') === 'BS Computer Science' ? 'selected' : '' ?>>BS Computer Science</option>
                                <option value="BS Engineering" <?= ($student['course'] ?? '') === 'BS Engineering' ? 'selected' : '' ?>>BS Engineering</option>
                                <option value="BS Information System" <?= ($student['course'] ?? '') === 'BS Information System' ? 'selected' : '' ?>>BS Information System</option>
                                <option value="BS Nursing" <?= ($student['course'] ?? '') === 'BS Nursing' ? 'selected' : '' ?>>BS Nursing</option>
                                <option value="BS Midwifery" <?= ($student['course'] ?? '') === 'BS Midwifery' ? 'selected' : '' ?>>BS Midwifery</option>
                                <option value="BS Psychology" <?= ($student['course'] ?? '') === 'BS Psychology' ? 'selected' : '' ?>>BS Psychology</option>
                            </select>
                        </div>

                        <!-- Address Information Section -->
                        <h4 style="color: var(--school-green); font-weight: 600; margin-top: 25px; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
                            Address Information
                        </h4>

                        <div class="form-group">
                            <label>Address</label>
                            <textarea name="address" class="form-input" rows="3" style="resize: vertical;">
<?= htmlspecialchars($student['address'] ?? '') ?></textarea>
                        </div>

                        <!-- Emergency Contact Section -->
                        <h4 style="color: var(--school-green); font-weight: 600; margin-top: 25px; margin-bottom: 15px; border-bottom: 2px solid #eee; padding-bottom: 10px;">
                            Emergency Contact
                        </h4>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Emergency Contact Name</label>
                                <input type="text" name="emergency_contact_name" class="form-input" 
                                       value="<?= htmlspecialchars($student['emergency_contact_name'] ?? '') ?>">
                            </div>
                            <div class="form-group">
                                <label>Emergency Contact Number</label>
                                <input type="tel" name="emergency_contact" class="form-input" 
                                       value="<?= htmlspecialchars($student['emergency_contact'] ?? '') ?>">
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-actions" style="margin-top: 30px;">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Changes
                            </button>
                            <a href="admin_students.php" class="btn btn-outline">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Back to Top Button
        function scrollToTop() {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        window.addEventListener('scroll', function() {
            const backToTop = document.querySelector('.back-to-top');
            if (window.pageYOffset > 300) {
                backToTop.style.display = 'flex';
            } else {
                backToTop.style.display = 'none';
            }
        });

        // Mobile sidebar toggle
        document.getElementById('mobileMenuBtn').addEventListener('click', function() {
            document.getElementById('adminSidebar').classList.toggle('mobile-open');
            document.getElementById('sidebarOverlay').classList.toggle('active');
        });

        document.getElementById('sidebarOverlay').addEventListener('click', function() {
            document.getElementById('adminSidebar').classList.remove('mobile-open');
            this.classList.remove('active');
        });
    </script>
</body>
</html>
