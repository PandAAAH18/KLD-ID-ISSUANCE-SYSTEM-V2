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
    header('Location: login.php');
    exit();
}

$userObj = new User();
$student = $userObj->findStudentbyEmail($_SESSION['email']);
if (!$student) {
    session_destroy();
    header('Location: login.php');
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

        header('Location: ../student/student_home.php');
        exit();
    }
}

/* ---------- 4. Pre-fill existing data ---- */
$first_name    = $student['first_name']    ?? '';
$last_name     = $student['last_name']     ?? '';
$contact_number= $student['contact_number']?? '';
$course        = $student['course']        ?? '';
$address       = $student['address']       ?? '';
?>
<!-- ---------- 5. Plain HTML form --------- -->
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Complete Profile</title>
</head>
<body>

<h2>Complete Your Profile</h2>

<?php if (!empty($errors)): ?>
    <ul style="color:red;">
        <?php foreach ($errors as $e) echo "<li>$e</li>"; ?>
    </ul>
<?php endif; ?>

<form method="post" enctype="multipart/form-data">
    <label>First Name:<br>
        <input type="text" name="first_name" value="<?=htmlspecialchars($first_name)?>" required>
    </label><br><br>

    <label>Last Name:<br>
        <input type="text" name="last_name" value="<?=htmlspecialchars($last_name)?>" required>
    </label><br><br>

    <label>Contact Number:<br>
        <input type="text" name="contact_number" value="<?=htmlspecialchars($contact_number)?>" required>
    </label><br><br>

    <label>Address:<br>
        <textarea name="address" required><?=htmlspecialchars($address)?></textarea>
    </label><br><br>

    <label>Course:<br>
        <select name="course" required>
            <option value="">-- select --</option>
            <?php foreach ($allowed_courses as $c): ?>
                <option value="<?=htmlspecialchars($c)?>" <?=($course===$c?'selected':'')?>>
                    <?=htmlspecialchars($c)?>
                </option>
            <?php endforeach; ?>
        </select>
    </label><br><br>

    <label>COR Photo (JPG/PNG/PDF):<br>
        <input type="file" name="cor_photo" accept=".jpg,.jpeg,.png,.pdf" required>
    </label><br><br>

    <button type="submit">Save Profile</button>
</form>

</body>
</html>