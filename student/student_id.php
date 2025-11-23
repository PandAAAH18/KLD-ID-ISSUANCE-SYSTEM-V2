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

    <!-- INCOMPLETE PROFILE WARNING -->
    <?php if ($incomplete): ?>
        <div class="id-application-wrapper">
            <div class="alert-warning" style="padding: 16px 20px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; font-size: 14px;">
                <span style="font-size: 20px;">⚠️</span>
                <div>
                    <strong>Incomplete Profile</strong><br>
                    Please complete your profile first before requesting an ID. <a href="edit_profile.php" style="color: #856404; font-weight: bold;">Complete Profile →</a>
                </div>
            </div>
        </div>
    <?php else: ?> <!-- PAGE TITLE -->
        <div class="id-application-wrapper">
            <div class="page-title-section">
                <h1>Apply / Renew School ID</h1>
                <p>Complete the form below to apply for a new student ID or renew your existing one</p>
            </div>
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
                                        <option value="new">New ID Application</option>
                                        <option value="replacement">Replacement (Lost/Damaged)</option>
                                        <option value="update_information">Update Information</option>
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

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        // Show notification on submit
        function handleSubmit(event) {
            event.preventDefault();

            const confirmCheckbox = document.getElementById('confirmCheckbox');
            if (!confirmCheckbox.checked) {
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

        // Show draggable modal alert
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
            if (window.scrollY > 200) {
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
            </div><!-- End admin-content -->
        </main><!-- End admin-main -->
    </div><!-- End admin-wrapper -->
</body>
</html>