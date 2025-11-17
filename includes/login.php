<?php
require_once 'config.php';
require_once 'User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userObj = new User();
    $user = $userObj->findByEmail($_POST['email']);

    if ($user && password_verify($_POST['password'], $user['password_hash'])) {
        $_SESSION['user_id']   = $user['user_id'];
        $_SESSION['email']     = $user['email'];
        $_SESSION['user_type'] = $user['role'];          // admin | student | teacher

        if ($user['role'] === 'student') {
            $student = $userObj->findStudentbyEmail($user['email']);
            $_SESSION['student_id'] = $student['id'];

            // make sure the profile is complete
            if (empty($student['first_name'])) {
                header('Location: complete_profile.php');
                exit();
            }
        }

        // everyone else (or student with complete profile)
        $goto = $user['role'] === 'admin'
              ? '../admin/admin_dashboard.php'
              : '../student/student_home.php';
        header("Location: $goto");
        exit();
    }

    $error = 'Invalid email or password.';
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | School Portal</title>
    <!-- Local Bootstrap CSS -->
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="../assets/css/login.css" rel="stylesheet">
</head>
<body class="login-body">
    <div class="container">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-md-6 col-lg-4">
                <div class="card login-card">
                    <div class="card-header text-center py-3 school-header">
                        <h3 class="mb-0">School Portal</h3>
                        <p class="mb-0 text-muted">Sign in to your account</p>
                    </div>
                    
                    <div class="card-body p-4">
                        <!-- Error Message -->
                        <?php if (isset($error)): ?>
                            <div class="alert alert-warning school-alert" role="alert">
                                <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Login Form -->
                        <form method="post">
                            <div class="mb-3">
                                <label for="email" class="form-label">Email Address</label>
                                <input type="email" class="form-control school-input" id="email" name="email" placeholder="Enter your email" required>
                            </div>

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <div class="input-group">
                                    <input type="password" class="form-control school-input" id="password" name="password" placeholder="Enter your password" required>
                                    <button class="btn btn-outline-secondary toggle-password" type="button">
                                        <span class="show-text">Show</span>
                                        <span class="hide-text" style="display: none;">Hide</span>
                                    </button>
                                </div>
                            </div>

                            <div class="d-grid mb-3">
                                <button type="submit" class="btn school-btn">Sign In</button>
                            </div>
                        </form>

                        <div class="text-center">
                            <p class="mb-0">Don't have an account?
                                <a href="register.php" class="school-link">Register here</a>
                            </p>
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
</body>
</html>