<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/student.php';

if (!isset($_SESSION['user_id'], $_SESSION['user_type'], $_SESSION['student_id']) ||
    $_SESSION['user_type'] !== 'student') {
    header('Location: ../index.php'); exit();
}

$stuObj = new Student();
$stu    = $stuObj->findById((int)$_SESSION['student_id']);
if (!$stu) { header('Location: ../index.php'); exit(); }

/* ---------- handle post ---------- */
$msg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    /* text fields */
    $data = [
        'first_name'         => trim($_POST['first_name']   ?? ''),
        'last_name'          => trim($_POST['last_name']    ?? ''),
        'contact_number'     => trim($_POST['contact_number'] ?? ''),
        'dob'                => trim($_POST['dob']          ?? ''),
        'gender'             => trim($_POST['gender']       ?? ''),
        'course'             => trim($_POST['course']       ?? ''),
        'year_level'         => trim($_POST['year_level']   ?? ''),
        'blood_type'         => trim($_POST['blood_type']   ?? ''),
        'emergency_contact'  => trim($_POST['emergency_contact'] ?? ''),
    ];

    /* optional password */
    $pwd = trim($_POST['password'] ?? '');
    if ($pwd !== '') $data['password_hash'] = password_hash($pwd, PASSWORD_DEFAULT);

    /* files */
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === UPLOAD_ERR_OK)
        $data['photo'] = $stuObj->saveUploadedFile($_FILES['profile_photo'], 'student_photos');

    if (isset($_FILES['cor_photo']) && $_FILES['cor_photo']['error'] === UPLOAD_ERR_OK)
        $data['cor'] = $stuObj->saveUploadedFile($_FILES['cor_photo'], 'student_cor');

    if (isset($_FILES['signature']) && $_FILES['signature']['error'] === UPLOAD_ERR_OK)
        $data['signature'] = $stuObj->saveUploadedFile($_FILES['signature'], 'student_signatures');

    $stuObj->updateStudent($stu['id'], $data);
    $msg = 'Profile updated.';
    /* re-read row */
    $stu = $stuObj->findById($stu['id']);
}
function ordinal(int $n): string {
    $s = ['th','st','nd','rd'];
    $m = $n % 100;
    return $n . ($s[($m-20)%10] ?? $s[$m] ?? $s[0]);
}
?>

<!doctype html>
<html>
<head><meta charset="utf-8"><title>Edit Profile</title></head>
<body>

<h2>Edit Profile</h2>
<?php if ($msg) echo "<p>$msg</p>"; ?>

<form method="post" enctype="multipart/form-data">
    First Name:<br>
    <input type="text" name="first_name" value="<?=htmlspecialchars($stu['first_name'])?>" required><br><br>

    Last Name:<br>
    <input type="text" name="last_name" value="<?=htmlspecialchars($stu['last_name'])?>" required><br><br>

    <!-- 1) PASSWORD (hidden until button clicked) -->
    <button type="button" onclick="document.getElementById('pwdBox').style.display='block'; this.disabled=true;">Change Password</button>
    <div id="pwdBox" style="display:none;">
        New Password:<br>
        <input type="password" name="password"><br><br>
    </div>

    Contact #:<br>
    <input type="text" name="contact_number" value="<?=htmlspecialchars($stu['contact_number'])?>" required><br><br>

    Date of Birth:<br>
    <input type="date" name="dob" value="<?=htmlspecialchars($stu['dob'] ?? '')?>"><br><br>

    <!-- 2) GENDER -->
    Gender:<br>
    <select name="gender">
        <option value="">-- select --</option>
        <option value="Male"   <?=isset($stu['gender']) && $stu['gender']==='Male'   ? 'selected' : ''?>>Male</option>
        <option value="Female" <?=isset($stu['gender']) && $stu['gender']==='Female' ? 'selected' : ''?>>Female</option>
    </select><br><br>

    Course:<br>
<select name="course" required>
    <option value="">-- select --</option>
    <?php
    $courses = [
        'BS Information System',
        'BS Computer Science',
        'BS Engineering',
        'BS Psychology',
        'BS Nursing',
        'BS Midwifery'
    ];
    foreach ($courses as $c):
        $sel = (isset($stu['course']) && $stu['course'] === $c) ? 'selected' : '';
        echo '<option value="'.htmlspecialchars($c).'" '.$sel.'>'.htmlspecialchars($c).'</option>';
    endforeach;
    ?>
</select><br><br>

    <!-- 4) BLOOD TYPE -->
    Blood Type:<br>
    <select name="blood_type">
        <option value="">-- select --</option>
        <?php
        $bloodOpts = ['A+','A-','B+','B-','AB+','AB-','O+','O-'];
        foreach ($bloodOpts as $bt)
            echo '<option value="'.htmlspecialchars($bt).'"'
               . (isset($stu['blood_type']) && $stu['blood_type']===$bt ? ' selected' : '')
               . '>'.htmlspecialchars($bt).'</option>';
        ?>
    </select><br><br>

    Emergency Contact:<br>
    <input type="text" name="emergency_contact" value="<?=htmlspecialchars($stu['emergency_contact'] ?? '')?>"><br><br>

    Profile Photo:<br>
    <input type="file" name="profile_photo" accept=".jpg,.jpeg,.png"><br><br>

    COR Photo:<br>
    <input type="file" name="cor_photo" accept=".jpg,.jpeg,.png,.pdf"><br><br>

    Signature (image):<br>
    <input type="file" name="signature" accept=".jpg,.jpeg,.png"><br><br>

    <button type="submit">Save Changes</button>
</form>

</body>
</html>