<?php
require_once 'includes/config.php';
require_once 'admin/classes/EmailVerification.php';

$error = null;
$success = null;
$token = $_GET['token'] ?? '';

if (empty($token)) {
    $error = 'No verification token provided.';
} else {
    try {
        $emailVerifier = new EmailVerification();
        
        // Check if token is valid
        if (!$emailVerifier->isTokenValid($token)) {
            $error = 'Invalid or expired verification token. Please request a new verification email.';
        } else {
            // Verify the token
            $verifiedRecord = $emailVerifier->verifyToken($token);
            
            if ($verifiedRecord) {
                $success = 'Email verified successfully! You can now log in with your account.';
            } else {
                $error = 'Could not verify email. Token may be expired or invalid.';
            }
        }
    } catch (\Exception $e) {
        error_log("Email verification error: " . $e->getMessage());
        $error = 'An error occurred during email verification. Please try again later.';
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Email Verification - KLD School Portal">
    <title>Email Verification | KLD School Portal</title>
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
        .success-icon{font-size:64px;color:var(--primary-green);margin-bottom:20px}
        .error-icon{font-size:64px;color:#d32f2f;margin-bottom:20px}
        .btn-primary{background:linear-gradient(90deg,var(--primary-green),var(--primary-dark));border:none;border-radius:10px;padding:0.9rem 1rem}
        .btn-primary:hover{background:linear-gradient(90deg,var(--primary-dark),#0d3818)}
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
                        <h3 class="mb-0 fw-bold">Email Verification</h3>
                        <p class="mb-0 text-muted">KLD School Portal</p>
                    </div>
                    
                    <div class="card-body p-4 text-center">
                        <?php if ($success): ?>
                            <div class="success-icon">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="alert alert-success" role="alert">
                                <h5 class="alert-heading mb-2">Success!</h5>
                                <?php echo esc_html($success); ?>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Go to Login
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="error-icon">
                                <i class="fas fa-exclamation-circle"></i>
                            </div>
                            <div class="alert alert-danger" role="alert">
                                <h5 class="alert-heading mb-2">Verification Failed</h5>
                                <?php echo esc_html($error); ?>
                            </div>
                            <div class="d-grid gap-2">
                                <a href="index.php" class="btn btn-primary">
                                    <i class="fas fa-home me-2"></i>Back to Login
                                </a>
                            </div>
                            
                            <?php if (!empty($_SESSION['pending_verification_email'])): ?>
                                <hr>
                                <p class="text-muted small mb-2">Didn't receive the verification email?</p>
                                <form method="post" action="resend_verification.php" class="text-center">
                                    <input type="hidden" name="email" value="<?php echo esc_html($_SESSION['pending_verification_email']); ?>">
                                    <button type="submit" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-redo me-1"></i>Resend Email
                                    </button>
                                </form>
                            <?php endif; ?>
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
