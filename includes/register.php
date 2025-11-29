<?php
require_once 'config.php';
require_once 'User.php';
require_once __DIR__ . '/../admin/classes/EmailVerification.php';

// Define constants if not already defined
if (!defined('SEND_VERIFICATION_EMAIL')) {
    define('SEND_VERIFICATION_EMAIL', true);
}

// Helper function
if (!function_exists('esc_html')) {
    function esc_html($text) {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $fullName = trim($_POST['full_name'] ?? '');

    // Basic validation
    if (empty($email) || empty($password) || empty($fullName)) {
        $error = 'All fields are required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        try {
            $user = new User();
            $result = $user->create($email, $password, 'student');

            if ($result) {
                // Get the newly created user's ID
                $newUser = $user->findByEmail($email);
                if ($newUser) {
                    $userId = $newUser['user_id'];

                    // Create and send verification email
                    $emailVerifier = new EmailVerification(); // Remove parameter
                    $token = $emailVerifier->createVerificationToken($userId, $email);

                    if ($token && SEND_VERIFICATION_EMAIL) {
                        if ($emailVerifier->sendVerificationEmail($email, $token, $fullName)) {
                            $_SESSION['message'] = "Account created successfully! Check your email to verify your account.";
                            $_SESSION['pending_verification_email'] = $email;
                            $success = 'Account created! A verification email has been sent to ' . esc_html($email);
                        } else {
                            $error = 'Account created, but verification email could not be sent. Please try again later.';
                        }
                    } else {
                        $success = 'Account created successfully! You can now log in.';
                    }
                } else {
                    $error = 'Account created but could not retrieve user information. Please try logging in.';
                }
            } else {
                $error = 'Registration failed. Email may already be in use or an error occurred.';
            }
        } catch (\Exception $e) {
            error_log("Registration error: " . $e->getMessage());
            $error = 'Registration failed. Please try again later.';
        }
    }
}
?>


<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="KLD School ID Issuance System - Create Account">
    <title>Register | KLD School Portal</title>
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
            <div class="col-md-6 col-lg-4">
                <div class="card login-card shadow-lg animate-fade-in">
                    <div class="card-header text-center py-3 school-header position-relative">
                        <div class="logo-container mb-2">
                            <img src="../assets/images/kldlogo.png" alt="KLD School Logo" class="logo-image">
                        </div>
                        <h3 class="mb-0 fw-bold">Create Account</h3>
                        <p class="mb-0 text-muted">Join KLD School Portal</p>
                        <div class="header-decoration" aria-hidden="true"></div>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Error Message -->
                        <?php if (isset($error)): ?>
                            <div class="alert alert-warning school-alert" role="alert">
                                <?php echo $error; ?>
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
                                        confirmButtonText: "Go to Login"
                                    }).then((result) => {
                                        if (result.isConfirmed) {
                                            window.location.href = '../index.php';
                                        }
                                    });
                                });
                            </script>
                        <?php endif; ?>

                        <!-- Registration Form -->
                        <form method="post" class="login-form">
                            <div class="form-floating mb-3">
                                <input type="text" class="form-control school-input" id="full_name" name="full_name" placeholder="Full Name" required>
                                <label for="full_name"><i class="fas fa-user me-2"></i>Full Name</label>
                                <div class="input-decoration" aria-hidden="true"></div>
                            </div>

                            <div class="form-floating mb-3">
                                <input type="email" class="form-control school-input" id="email" name="email" placeholder="name@example.com" required>
                                <label for="email"><i class="fas fa-envelope me-2"></i>Email address</label>
                                <div class="input-decoration" aria-hidden="true"></div>
                            </div>

                            <div class="mb-3">
                                <input type="password" class="form-control school-input" id="password" name="password" placeholder="Enter your password" required minlength="6">
                                <small class="form-text text-muted mt-1">
                                    <i class="fas fa-info-circle me-1"></i>Password must be at least 6 characters
                                </small>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn school-btn btn-enhanced">
                                    <i class="fas fa-user-plus me-2"></i> Create Account
                                    <span class="btn-ripple" aria-hidden="true"></span>
                                </button>
                            </div>
                        </form>

                        <div class="text-center">
                            <div class="divider mb-3"><span>Already have an account?</span></div>
                            <p class="mb-3">
                                <a href="../index.php" class="school-link btn-link-enhanced">
                                    <i class="fas fa-sign-in-alt me-2"></i>Sign In Here
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
        .toggle-password-btn{border-color:rgba(46,125,50,0.3)!important;color:#6c757d!important}
        .toggle-password-btn:hover{background:var(--primary-green)!important;color:white!important;border-color:var(--primary-green)!important}
        .school-link{color:var(--primary-green);text-decoration:none;font-size:0.9rem;font-weight:500;transition:all 0.3s ease}
        .school-link:hover{color:var(--primary-dark);text-decoration:underline;transform:translateX(2px)}
        .btn-enhanced{background:linear-gradient(90deg,var(--primary-green),var(--primary-dark));color:#fff;border-radius:10px;padding:0.9rem 1rem;border:none}
        .btn-enhanced:active .btn-ripple{transform:scale(1);opacity:0}
        .divider{position:relative;text-align:center}
        .divider span{background:#fff;padding:0 12px;position:relative;z-index:2}
        .divider::before{content:'';position:absolute;left:0;right:0;top:50%;height:1px;background:rgba(0,0,0,0.06);z-index:1}
        .security-notice{display:flex;align-items:center;justify-content:center;gap:8px;padding:8px;border-radius:8px;background:rgba(46,125,50,0.06)}
        @media (max-width:768px){.enhanced-bg{background-attachment:scroll}.login-card{margin:12px}}
    </style>

    <script>
        // Simple and reliable password toggle
        document.addEventListener('DOMContentLoaded', function() {
            const toggleBtn = document.getElementById('togglePassword');
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (toggleBtn && passwordField && eyeIcon) {
                toggleBtn.addEventListener('click', function() {
                    if (passwordField.type === 'password') {
                        passwordField.type = 'text';
                        eyeIcon.classList.remove('fa-eye');
                        eyeIcon.classList.add('fa-eye-slash');
                    } else {
                        passwordField.type = 'password';
                        eyeIcon.classList.remove('fa-eye-slash');
                        eyeIcon.classList.add('fa-eye');
                    }
                });
            }
        });

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