<?php
require_once 'includes/config.php';
require_once 'admin/classes/EmailVerification.php';

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Invalid email address.';
    } else {
        try {
            $emailVerifier = new EmailVerification();
            
            if ($emailVerifier->resendVerificationEmail($email)) {
                $success = 'Verification email sent! Please check your inbox.';
            } else {
                $error = 'Could not resend verification email. Email may already be verified or user not found.';
            }
        } catch (\Exception $e) {
            error_log("Resend verification error: " . $e->getMessage());
            $error = 'An error occurred. Please try again later.';
        }
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Resend Email Verification - KLD School Portal">
    <title>Resend Verification | KLD School Portal</title>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Local Bootstrap CSS -->
    <link href="assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Preload background image -->
    <link rel="preload" href="assets/images/building.jpg" as="image">
    <style>
        :root{--primary-green:#2e7d32;--primary-dark:#1b5e20;--primary-light:#4caf50}
        .enhanced-bg{background:linear-gradient(135deg, rgba(27,94,32,0.85), rgba(46,125,50,0.85)), url('assets/images/building.jpg') center/cover no-repeat fixed;min-height:100vh}
        .bg-overlay{position:fixed;inset:0;background:linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0,0,0,0.15));z-index:0}
        .container.position-relative{z-index:3}
        .card{border-radius:14px;background:rgba(255,255,255,0.98);backdrop-filter:blur(8px)}
        .school-header{background:linear-gradient(90deg,var(--primary-green),var(--primary-dark));color:#fff;border-radius:12px 12px 0 0}
        .logo-image{width:64px;height:64px;border-radius:50%;margin:0 auto 8px;border:2px solid rgba(255,215,0,0.15);object-fit:cover;background:rgba(255,255,255,0.1);padding:4px}
        .animate-fade-in{animation:fadeInUp .6s ease-out}
        @keyframes fadeInUp{from{opacity:0;transform:translateY(20px)}to{opacity:1;transform:translateY(0)}}
        .school-input{border-radius:10px;padding:1rem}
        .btn-primary{background:linear-gradient(90deg,var(--primary-green),var(--primary-dark));border:none;border-radius:10px;padding:0.9rem 1rem}
        .btn-primary:hover{background:linear-gradient(90deg,var(--primary-dark),#0d3818)}
        .school-link{color:var(--primary-green);text-decoration:none;font-size:0.9rem;font-weight:500;transition:all 0.3s ease}
        .school-link:hover{color:var(--primary-dark);text-decoration:underline}
        @media (max-width:768px){.enhanced-bg{background-attachment:scroll}.card{margin:12px}}
    </style>
</head>
<body class="enhanced-bg">
    <div class="bg-overlay" aria-hidden="true"></div>
    <div class="container position-relative">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow-lg animate-fade-in">
                    <div class="card-header text-center py-3 school-header position-relative">
                        <div class="logo-container mb-2">
                            <img src="assets/images/kldlogo.png" alt="KLD School Logo" class="logo-image">
                        </div>
                        <h3 class="mb-0 fw-bold">Resend Verification</h3>
                        <p class="mb-0 text-muted">KLD School Portal</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Error Message -->
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger" role="alert">
                                <i class="fas fa-exclamation-circle me-2"></i><?php echo esc_html($error); ?>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Success Message -->
                        <?php if (isset($success)): ?>
                            <div class="alert alert-success" role="alert">
                                <i class="fas fa-check-circle me-2"></i><?php echo esc_html($success); ?>
                            </div>
                            <div class="d-grid gap-2 mt-3">
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Back to Login
                                </a>
                            </div>
                        <?php else: ?>
                            <p class="text-muted mb-3">
                                Enter your email address and we'll send you a new verification link.
                            </p>

                            <!-- Resend Form -->
                            <form method="post" class="login-form">
                                <div class="form-floating mb-3">
                                    <input type="email" class="form-control school-input" id="email" name="email" 
                                           placeholder="name@example.com" required>
                                    <label for="email"><i class="fas fa-envelope me-2"></i>Email address</label>
                                </div>

                                <div class="d-grid mb-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Resend Verification Email
                                    </button>
                                </div>
                            </form>

                            <div class="text-center mt-3">
                                <p class="mb-0">
                                    <a href="index.php" class="school-link">
                                        <i class="fas fa-arrow-left me-1"></i>Back to Login
                                    </a>
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Local Bootstrap JS -->
    <script src="assets/js/bootstrap.bundle.min.js"></script>
</body>
</html>
