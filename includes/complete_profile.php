<?php
/* -----------------------------------------------------
   complete_profile.php  (plain form, no css)
   Uses getDb() from User class for DB connection
   COR saved to ../uploads/student_cor/<email>_cor.ext
----------------------------------------------------- */

require_once 'config.php';
require_once 'User.php';

/* ---------- 1. Guard-clauses ------------- */
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'student') {
    header('Location: ../index.php');
    exit();
}

$userObj = new User();
$student = $userObj->findStudentbyEmail($_SESSION['email']);
if (!$student) {
    session_destroy();
    header('Location: ../index.php');
    exit();
}

/* ---------- 2. Allowed courses ----------- */
$allowed_courses = [
    'BS Information System',
    'BS Computer Science',
    'BS Engineering',
    'BS Psychology',
    'BS Nursing',
    'BS Midwifery'
];

/* ---------- 3. On POST ------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* ---- 3-a. Text inputs ---------------- */
    $first_name    = trim($_POST['first_name']   ?? '');
    $last_name     = trim($_POST['last_name']    ?? '');
    $contact       = trim($_POST['contact_number'] ?? '');
    $course        = trim($_POST['course']       ?? '');
    $address       = trim($_POST['address']      ?? '');

    $errors = [];

    if ($first_name === '')   $errors[] = 'First name is required.';
    if ($last_name  === '')   $errors[] = 'Last name is required.';
    if ($contact    === '')   $errors[] = 'Contact number is required.';
    if ($address    === '')   $errors[] = 'Address is required.';
    if (!in_array($course, $allowed_courses, true))
                              $errors[] = 'Invalid course selected.';

    /* ---- 3-b. COR file ------------------- */
    $cor_name = null;
    if (isset($_FILES['cor_photo']) && $_FILES['cor_photo']['error'] === UPLOAD_ERR_OK) {

        $f   = $_FILES['cor_photo'];
        $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];

        if (!in_array($ext, $allowed_ext, true))
            $errors[] = 'Only JPG, PNG or PDF is allowed for COR.';
        else {

            $base    = preg_replace('/[^a-zA-Z0-9._-]/', '_', $_SESSION['email']);
            $newName = $base . '_cor.' . $ext;

            $destDir  = __DIR__ . '/../uploads/student_cor/';
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            $destPath = $destDir . $newName;

            if (move_uploaded_file($f['tmp_name'], $destPath))
                $cor_name = $newName;
            else
                $errors[] = 'Failed to move uploaded COR.';
        }
    } else {
        $errors[] = 'COR photo is required.';
    }

    /* ---- 3-c. Save if no errors ---------- */
    if (!$errors) {

        $db = $userObj->getDb();   // <-- use the provided method

        $sql = "UPDATE student
                SET first_name    = :first,
                    last_name     = :last,
                    contact_number= :contact,
                    course        = :course,
                    address       = :address,
                    cor     = :cor
                WHERE id = :id";

        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':first'   => $first_name,
            ':last'    => $last_name,
            ':contact' => $contact,
            ':course'  => $course,
            ':address' => $address,
            ':cor'     => $cor_name,
            ':id'      => $student['id']
        ]);

        $success = 'Profile completed successfully! Welcome to KLD School Portal.';
    }
}

/* ---------- 4. Pre-fill existing data ---- */
$first_name    = $student['first_name']    ?? '';
$last_name     = $student['last_name']     ?? '';
$contact_number= $student['contact_number']?? '';
$course        = $student['course']        ?? '';
$address       = $student['address']       ?? '';
?>
<!-- ---------- 5. Modern HTML form --------- -->
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="KLD School ID Issuance System - Complete Profile">
    <title>Complete Profile | KLD School Portal</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Local Bootstrap CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/login.css" rel="stylesheet">
    <!-- Preload background image -->
    <link rel="preload" href="../assets/images/building.jpg" as="image">
</head>
<body class="login-body enhanced-bg">
    <div class="bg-overlay" aria-hidden="true"></div>
    <div class="particles-container" aria-hidden="true">
        <div class="particle"></div>
        <div class="particle"></div>
        <div class="particle"></div>
    </div>
    <div class="container position-relative">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-8 col-lg-6">
                <div class="card login-card shadow-lg animate-fade-in">
                    <div class="card-header text-center py-3 school-header position-relative">
                        <div class="logo-container mb-2">
                            <img src="../assets/images/kldlogo.png" alt="KLD School Logo" class="logo-image">
                        </div>
                        <h3 class="mb-0 fw-bold">Complete Your Profile</h3>
                        <p class="mb-0 text-muted">Fill in your student information</p>
                        <div class="header-decoration" aria-hidden="true"></div>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Error Messages -->
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger school-alert" role="alert">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <strong>Please fix the following errors:</strong>
                                <ul class="mb-0 mt-2">
                                    <?php foreach ($errors as $e): ?>
                                        <li><?php echo htmlspecialchars($e); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Success Message -->
                        <?php if (isset($success)): ?>
                            <script>
                                document.addEventListener('DOMContentLoaded', function() {
                                    Swal.fire({
                                        title: "Success!",
                                        text: "<?php echo $success; ?>",
                                        icon: "success",
                                        confirmButtonColor: "#2e7d32",
                                        confirmButtonText: "Continue to Dashboard"
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            window.location.href = '../student/student_home.php';
                                        }
                                    });
                                });
                            </script>
                        <?php endif; ?>

                        <!-- Profile Form -->
                        <form method="post" enctype="multipart/form-data" class="login-form">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control school-input" id="first_name" name="first_name" value="<?=htmlspecialchars($first_name)?>" placeholder="First Name" required>
                                        <label for="first_name"><i class="fas fa-user me-2"></i>First Name</label>
                                        <div class="input-decoration" aria-hidden="true"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-floating mb-3">
                                        <input type="text" class="form-control school-input" id="last_name" name="last_name" value="<?=htmlspecialchars($last_name)?>" placeholder="Last Name" required>
                                        <label for="last_name"><i class="fas fa-user me-2"></i>Last Name</label>
                                        <div class="input-decoration" aria-hidden="true"></div>
                                    </div>
                                </div>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="tel" class="form-control school-input" id="contact_number" name="contact_number" value="<?=htmlspecialchars($contact_number)?>" placeholder="Contact Number" required>
                                <label for="contact_number"><i class="fas fa-phone me-2"></i>Contact Number</label>
                                <div class="input-decoration" aria-hidden="true"></div>
                            </div>

                            <div class="mb-3">
                                <label for="course" class="form-label"><i class="fas fa-graduation-cap me-2"></i>Course</label>
                                <select class="form-select school-input" id="course" name="course" required>
                                    <option value="">-- Select Course --</option>
                                    <?php foreach ($allowed_courses as $c): ?>
                                        <option value="<?=htmlspecialchars($c)?>" <?=($course===$c?'selected':'')?>>
                                            <?=htmlspecialchars($c)?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="address" class="form-label"><i class="fas fa-map-marker-alt me-2"></i>Address</label>
                                <textarea class="form-control school-input" id="address" name="address" rows="3" placeholder="Enter your complete address" required><?=htmlspecialchars($address)?></textarea>
                            </div>

                            <div class="mb-3">
                                <label for="cor_photo" class="form-label"><i class="fas fa-file-upload me-2"></i>COR Photo (Certificate of Registration)</label>
                                <input type="file" class="form-control school-input" id="cor_photo" name="cor_photo" accept=".jpg,.jpeg,.png,.pdf" required>
                                <small class="form-text text-muted mt-1">
                                    <i class="fas fa-info-circle me-1"></i>Accepted formats: JPG, PNG, PDF (Max 5MB)
                                </small>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn school-btn btn-enhanced">
                                    <i class="fas fa-save me-2"></i> Save Profile
                                    <span class="btn-ripple" aria-hidden="true"></span>
                                </button>
                            </div>
                        </form>

                        <div class="text-center">
                            <div class="security-notice mt-2">
                                <i class="fas fa-shield-alt me-2"></i><small class="text-muted">Your information is secured</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Local Bootstrap JS -->
    <script src="../assets/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="../assets/js/login.js"></script>

    <!-- Inline enhanced styles + scripts for better UX (kept local for easy edits) -->
    <style>
        :root{--primary-green:#2e7d32;--primary-dark:#1b5e20;--primary-light:#4caf50}
        .enhanced-bg{background:linear-gradient(135deg, rgba(27,94,32,0.85), rgba(46,125,50,0.85)), url('../assets/images/building.jpg') center/cover no-repeat fixed;min-height:100vh}
        .bg-overlay{position:fixed;inset:0;background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0,0,0,0.15));z-index:0}
        .particles-container{position:fixed;inset:0;z-index:0;pointer-events:none}
        .particle{width:6px;height:6px;border-radius:50%;background:rgba(255,215,0,0.35);position:absolute;animation:float 6s linear infinite}
        .particle:nth-child(1){left:10%;top:10%;animation-delay:0s}
        .particle:nth-child(2){left:25%;top:5%;animation-delay:1.2s}
        .particle:nth-child(3){left:70%;top:15%;animation-delay:2.6s}
        @keyframes float{0%{transform:translateY(0)}50%{transform:translateY(-30px)}100%{transform:translateY(0)}}
        .container.position-relative{z-index:3}
        .login-card{border-radius:14px;background:rgba(255,255,255,0.98);backdrop-filter:blur(8px)}
        .school-header{background:linear-gradient(90deg,var(--primary-green),var(--primary-dark));color:#fff;border-radius:12px 12px 0 0}
        .logo-image{width:64px;height:64px;border-radius:50%;margin:0 auto 8px;border:2px solid rgba(255,215,0,0.15);object-fit:cover;background:rgba(255,255,255,0.1);padding:4px}
        .animate-fade-in{animation:fadeInUp .6s ease-out}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .school-input{border-radius:10px;padding:1rem}
        .school-input:focus{border-color:var(--primary-green);box-shadow:0 0 0 0.2rem rgba(46,125,50,0.25)}
        .form-select.school-input{padding:1rem}
        .btn-enhanced{background:linear-gradient(90deg,var(--primary-green),var(--primary-dark));color:#fff;border-radius:10px;padding:0.9rem 1rem;border:none;transition:all 0.3s ease}
        .btn-enhanced:hover{transform:translateY(-2px);box-shadow:0 8px 25px rgba(46,125,50,0.3)}
        .btn-enhanced:active .btn-ripple{transform:scale(1);opacity:0}
        .school-alert{border-radius:10px;border:none;background:rgba(220,53,69,0.1);color:#721c24}
        .security-notice{display:flex;align-items:center;justify-content:center;gap:8px;padding:8px;border-radius:8px;background:rgba(46,125,50,0.06)}
        @media (max-width:768px){.enhanced-bg{background-attachment:scroll}.login-card{margin:12px}}
    </style>

    <script>
        // Simple ripple effect on button press - waits for DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.btn-enhanced').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    const ripple = this.querySelector('.btn-ripple');
                    if (ripple) {
                        ripple.style.width = '200px';
                        ripple.style.height = '200px';
                        ripple.style.opacity = '0.3';
                        setTimeout(() => {
                            ripple.style.width = '0';
                            ripple.style.height = '0';
                            ripple.style.opacity = '0';
                        }, 300);
                    }
                });
            });
        });
    </script>
</body>
</html>