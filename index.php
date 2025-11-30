<?php
require_once 'includes/config.php';
require_once 'includes/user.php';
require_once 'admin/classes/EmailVerification.php';

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Email and password are required.';
    } else {
        try {
            $userObj = new User();
            $user = $userObj->findByEmail($email);

            if ($user && password_verify($password, $user['password_hash'])) {
                // Check if email is verified (skip in dev mode if REQUIRE_EMAIL_VERIFICATION is false)
                $requireVerification = defined('REQUIRE_EMAIL_VERIFICATION') ? REQUIRE_EMAIL_VERIFICATION : true;

                if ($requireVerification && !$user['is_verified']) {
                    $error = 'Please verify your email address first. Check your inbox at ' . htmlspecialchars($email) . ' for the verification link.';
                    $_SESSION['pending_verification_email'] = $email;
                } else {
                    // Email verified, proceed with login
                    $_SESSION['user_id']   = $user['user_id'];
                    $_SESSION['email']     = $user['email'];
                    $_SESSION['user_type'] = $user['role'];          // admin | student | teacher

                    if ($user['role'] === 'student') {
                        // Ensure student row exists
                        $pdo = $userObj->getDb();
                        $stmt = $pdo->prepare(
                            'INSERT IGNORE INTO student (email) VALUES (:email)'
                        );
                        $stmt->execute([':email' => $user['email']]);

                        $student = $userObj->findStudentbyEmail($user['email']);
                        $_SESSION['student_id'] = $student['id'];

                        // Check if profile is complete
                        if (empty($student['course'])) {
                            header('Location: includes/complete_profile.php');
                            exit();
                        }
                    }

                    // Redirect based on role
                    $goto = $user['role'] === 'admin'
                        ? 'admin/admin_dashboard.php'
                        : 'student/student_home.php';
                    header("Location: $goto");
                    exit();
                }
            } else {
                $error = 'Invalid email or password.';
            }
        } catch (\Exception $e) {
            error_log("Login error: " . $e->getMessage());
            $error = 'An error occurred during login. Please try again.';
        }
    }
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="KLD School ID Issuance System - Secure Login">
    <link rel="shortcut icon" href="assets/image/kldlogo.png" type="../assets/image/x-icon">
    <title>Login | KLD School Portal</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Local Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="assets/css/login.css" rel="stylesheet">
    <!-- Preload background image -->
    <link rel="preload" href="assets/images/building.jpg" as="image">

     <script src="assets/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JS -->
    <script src="assets/js/login.js"></script>

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
        .logo-image{width:64px;height:64px;border-radius:50%;margin:0 auto 8px;border:2px solid rgba(255,215,0,0.15);object-fit:cover;background:rgba(255,255,255,0.1);padding:4px}
        .animate-fade-in{animation:fadeInUp .6s ease-out}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .school-input{border-radius:10px;padding:1rem}
        .toggle-password-btn{border-color:rgba(46,125,50,0.3)!important;color:#6c757d!important}
        .toggle-password-btn:hover{background:var(--primary-green)!important;color:white!important;border-color:var(--primary-green)!important}
        .forgot-password-link{color:var(--primary-green);text-decoration:none;font-size:0.9rem;font-weight:500;transition:all 0.3s ease}
        .forgot-password-link:hover{color:var(--primary-dark);text-decoration:underline;transform:translateX(2px)}
        .btn-enhanced{background:linear-gradient(90deg,var(--primary-green),var(--primary-dark));color:#fff;border-radius:10px;padding:0.9rem 1rem;border:none}
        .btn-enhanced:active .btn-ripple{transform:scale(1);opacity:0}
        .divider{position:relative;text-align:center}
        .divider span{background:#fff;padding:0 12px;position:relative;z-index:2}
        .divider::before{content:'';position:absolute;left:0;right:0;top:50%;height:1px;background:rgba(0,0,0,0.06);z-index:1}
        .security-notice{display:flex;align-items:center;justify-content:center;gap:8px;padding:8px;border-radius:8px;background:rgba(46,125,50,0.06)}
        @media (max-width:768px){.enhanced-bg{background-attachment:scroll}.login-card{margin:12px}}
    </style>
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
                            <img src="assets/images/kldlogo.png" alt="KLD School Logo" class="logo-image">
                        </div>
                        <h3 class="mb-0 fw-bold">KLD School Portal</h3>
                        <p class="mb-0 text-muted">Secure access to school services</p>
                        <div class="header-decoration" aria-hidden="true"></div>
                    </div>

                    <div class="card-body p-4">
                        <!-- Error Message -->
                        <?php if (isset($error)): ?>
                            <div class="alert alert-warning school-alert" role="alert">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Login Form -->
                        <form method="post" class="login-form">
                            <div class="form-floating mb-3">
                                <input type="email" class="form-control school-input" id="email" name="email" placeholder="name@example.com" required>
                                <label for="email"><i class="fas fa-envelope me-2"></i>Email address</label>
                                <div class="input-decoration" aria-hidden="true"></div>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label"><i class="fas fa-lock me-2"></i>Password</label>
                                <input type="password" class="form-control school-input" id="password" name="password" placeholder="Enter your password" required>
                                <div class="text-end mt-2">
                                    <a href="forget_pass.php" class="forgot-password-link">
                                        <i class="fas fa-key me-1"></i>Forgot Password?
                                    </a>
                                </div>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn school-btn btn-enhanced">
                                    <i class="fas fa-sign-in-alt me-2"></i> Sign In
                                    <span class="btn-ripple" aria-hidden="true"></span>
                                </button>
                            </div>
                        </form>

                        <div class="text-center">
                            <div class="divider mb-3"><span>New to KLD?</span></div>
                            <p class="mb-3">
                                <a href="includes/register.php" class="school-link btn-link-enhanced">
                                    <i class="fas fa-user-plus me-2"></i>Create an account
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

    <!-- Inline enhanced styles + scripts for better UX (kept local for easy edits) -->
    <style>
        :root {
            --primary-green: #2e7d32;
            --primary-dark: #1b5e20;
            --primary-light: #4caf50
        }

        .enhanced-bg {
            background: linear-gradient(135deg, rgba(27, 94, 32, 0.85), rgba(46, 125, 50, 0.85)), url('assets/images/building.jpg') center/cover no-repeat fixed;
            min-height: 100vh
        }

        .bg-overlay {
            position: fixed;
            inset: 0;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.02), rgba(0, 0, 0, 0.15));
            z-index: 0
        }

        .particles-container {
            position: fixed;
            inset: 0;
            z-index: 0;
            pointer-events: none
        }

        .particle {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: rgba(255, 215, 0, 0.35);
            position: absolute;
            animation: float 6s linear infinite
        }

        .particle:nth-child(1) {
            left: 10%;
            top: 10%;
            animation-delay: 0s
        }

        .particle:nth-child(2) {
            left: 25%;
            top: 5%;
            animation-delay: 1.2s
        }

        .particle:nth-child(3) {
            left: 70%;
            top: 15%;
            animation-delay: 2.6s
        }

        @keyframes float {
            0% {
                transform: translateY(0)
            }

            50% {
                transform: translateY(-30px)
            }

            100% {
                transform: translateY(0)
            }
        }

        .container.position-relative {
            z-index: 3
        }

        .login-card {
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(8px)
        }

        .school-header {
            background: linear-gradient(90deg, var(--primary-green), var(--primary-dark));
            color: #fff;
            border-radius: 12px 12px 0 0
        }

        .logo-image {
            width: 64px;
            height: 64px;
            border-radius: 50%;
            margin: 0 auto 8px;
            border: 2px solid rgba(255, 215, 0, 0.15);
            object-fit: cover;
            background: rgba(255, 255, 255, 0.1);
            padding: 4px
        }

        .animate-fade-in {
            animation: fadeInUp .6s ease-out
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px)
            }

            to {
                opacity: 1;
                transform: translateY(0)
            }
        }

        .school-input {
            border-radius: 10px;
            padding: 1rem
        }

        .toggle-password-btn {
            border-color: rgba(46, 125, 50, 0.3) !important;
            color: #6c757d !important
        }

        .toggle-password-btn:hover {
            background: var(--primary-green) !important;
            color: white !important;
            border-color: var(--primary-green) !important
        }

        .forgot-password-link {
            color: var(--primary-green);
            text-decoration: none;
            font-size: 0.9rem;
            font-weight: 500;
            transition: all 0.3s ease
        }

        .forgot-password-link:hover {
            color: var(--primary-dark);
            text-decoration: underline;
            transform: translateX(2px)
        }

        .btn-enhanced {
            background: linear-gradient(90deg, var(--primary-green), var(--primary-dark));
            color: #fff;
            border-radius: 10px;
            padding: 0.9rem 1rem;
            border: none
        }

        .btn-enhanced:active .btn-ripple {
            transform: scale(1);
            opacity: 0
        }

        .divider {
            position: relative;
            text-align: center
        }

        .divider span {
            background: #fff;
            padding: 0 12px;
            position: relative;
            z-index: 2
        }

        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            right: 0;
            top: 50%;
            height: 1px;
            background: rgba(0, 0, 0, 0.06);
            z-index: 1
        }

        .security-notice {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 8px;
            border-radius: 8px;
            background: rgba(46, 125, 50, 0.06)
        }

        @media (max-width:768px) {
            .enhanced-bg {
                background-attachment: scroll
            }

            .login-card {
                margin: 12px
            }
        }
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