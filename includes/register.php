<?php
require_once 'config.php';
require_once 'User.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = new User();
    $ok  = $user->create(
        $_POST['email'],
        $_POST['password'],
        'student'                 // default role
    );

    if ($ok) {
        $_SESSION['flash'] = 'Account created, please log in.';
        redirect('login.php');
    } else {
        $error = 'Registration failed (email already used?)';
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Register</title>
</head>
<body>
<h2>Register</h2>
<?php if (isset($error)) echo "<p style='color:red'>$error</p>"; ?>
<form method="post">
    <label>Full name: <input name="full_name" required></label><br>
    <label>Email: <input type="email" name="email" required></label><br>
    <label>Password: <input type="password" name="password" required minlength="6"></label><br>
    <button type="submit">Create account</button>
</form>
<p>Already have an account? <a href="login.php">Login here</a></p>
</body>
</html>