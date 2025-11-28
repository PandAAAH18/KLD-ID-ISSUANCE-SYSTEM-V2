<?php
require_once 'includes/config.php';
require_once 'includes/user.php';

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_number = $_POST['student_number'] ?? '';
    $date_of_birth = $_POST['date_of_birth'] ?? '';
    
    if (empty($student_number) || empty($date_of_birth)) {
        $error = 'Please fill in all required fields.';
    } else {
        // Here you would normally verify the student number and date of birth
        // For now, we'll simulate a successful verification
        $message = 'Please check your registered email address (palcecathlyn20@gmail.com) to change your password';
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="KLD School - Forgot Password Verification">
    <title>Forgot Password | KLD School Portal</title>
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- SweetAlert2 CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    
    <!-- Local Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="assets/css/login.css" rel="stylesheet">
    
    <!-- Preload background image -->
    <link rel="preload" href="assets/images/building.jpg" as="image">
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
            <div class="col-md-6 col-lg-4">
                <div class="card login-card shadow-lg animate-fade-in">
                    <div class="card-header text-center py-3 school-header position-relative">
                        <div class="logo-container mb-2">
                            <div class="logo-circle"><i class="fas fa-graduation-cap logo-icon"></i></div>
                        </div>
                        <h3 class="mb-0 fw-bold">KLD School Portal</h3>
                        <p class="mb-0 text-muted">Secure access to school services</p>
                        <div class="header-decoration" aria-hidden="true"></div>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Forgot Password Form -->
                        <form id="forgot-password-form" class="login-form">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control school-input" id="student_number" name="student_number" placeholder="Student Number" required>
                                <label for="student_number"><i class="fas fa-id-card me-2"></i>Student Number</label>
                                <div class="input-decoration" aria-hidden="true"></div>
                            </div>

                            <div class="mb-3">
                                <label for="date_of_birth" class="form-label"><i class="fas fa-calendar me-2"></i>Date of Birth</label>
                                <input type="date" class="form-control school-input" id="date_of_birth" name="date_of_birth" required>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="button" id="verify-btn" class="btn school-btn btn-enhanced">
                                    <i class="fas fa-key me-2"></i> Reset Password
                                    <span class="btn-ripple" aria-hidden="true"></span>
                                </button>
                            </div>
                        </form>

                        <div class="text-center">
                            <div class="divider mb-3"><span>Remember your password?</span></div>
                            <p class="mb-3">
                                <a href="index.php" class="school-link btn-link-enhanced">
                                    <i class="fas fa-sign-in-alt me-2"></i>Back to Sign In
                                </a>
                            </p>
                            <div class="security-notice mt-2">
                                <i class="fas fa-shield-alt me-2"></i><small class="text-muted">Secured with SSL</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Local Bootstrap JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/login.js"></script>
    
    <!-- SweetAlert2 JS -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>

    <!-- Inline enhanced styles + scripts for better UX (kept local for easy edits) -->
    <style>
        :root{--primary-green:#2e7d32;--primary-dark:#1b5e20;--primary-light:#4caf50}
        .enhanced-bg{background:linear-gradient(135deg, rgba(27,94,32,0.85), rgba(46,125,50,0.85)), url('assets/images/building.jpg') center/cover no-repeat fixed;min-height:100vh}
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
        .logo-circle{width:64px;height:64px;border-radius:50%;background:rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;margin:0 auto 8px;border:2px solid rgba(255,215,0,0.15)}
        .logo-icon{color:#ffd700;font-size:1.35rem}
        .animate-fade-in{animation:fadeInUp .6s ease-out}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .school-input{border-radius:10px;padding:1rem}
        .btn-enhanced{background:linear-gradient(90deg,var(--primary-green),var(--primary-dark));color:#fff;border-radius:10px;padding:0.9rem 1rem;border:none}
        .btn-enhanced:active .btn-ripple{transform:scale(1);opacity:0}
        .divider{position:relative;text-align:center}
        .divider span{background:#fff;padding:0 12px;position:relative;z-index:2}
        .divider::before{content:'';position:absolute;left:0;right:0;top:50%;height:1px;background:rgba(0,0,0,0.06);z-index:1}
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
            
            // Verify button click handler with SweetAlert
            document.getElementById('verify-btn').addEventListener('click', function() {
                const studentNumber = document.getElementById('student_number').value.trim();
                const dateOfBirth = document.getElementById('date_of_birth').value;
                
                // Check if fields are filled
                if (!studentNumber || !dateOfBirth) {
                    Swal.fire({
                        title: "Missing Information!",
                        text: "Please fill in all required fields.",
                        icon: "info",
                        confirmButtonColor: "#3085d6",
                        confirmButtonText: "OK"
                    });
                    return;
                }
                
                // Show success confirmation
                Swal.fire({
                    title: "Success!",
                    text: "Password reset link has been sent to your registered email address.",
                    icon: "success",
                    confirmButtonColor: "#2e7d32",
                    confirmButtonText: "OK"
                });
            });
        });
    </script>
</body>
</html>
