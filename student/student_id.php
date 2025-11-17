<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/student.php';

/* ----- 1. auth ----- */
if (!isset($_SESSION['user_id']) || $_SESSION['user_type']!=='student') {
    header('Location: ../login.php'); exit();
}

$stuObj = new Student();
$student= $stuObj->findById($_SESSION['student_id']);   // returns full row
if (!$student) { header('Location: ../login.php'); exit(); }

/* ----- 2. profile completeness check ---- */
$required = [
    'student_id','email','first_name','last_name','year_level','course',
    'contact_number','address','photo','emergency_contact','signature','cor'
];
$incomplete = false;
foreach ($required as $col) {
    if (empty($student[$col])) { $incomplete = true; break; }
}

/* ----- 3. post-back ---- */
$msg='';
if ($_SERVER['REQUEST_METHOD']==='POST' && !$incomplete) {
    $type   = $_POST['request_type'] ?? '';
    $reason = trim($_POST['reason']  ?? '');

    if (!in_array($type, ['new','replacement','update_information']))
        $msg='Invalid request type.';
    else {
        if (($type==='replacement' || $type==='update_information') && $reason==='')
            $msg='Reason is required for replacement/update.';
        else {
            $stuObj->insertIdRequest(
                $student['id'],
                $type,
                $reason
            );
            $msg='Request submitted. You will be notified once processed.';
        }
    }
}
?>
<!doctype html>
<html>
<head>
<meta charset="utf-8">
<title>My ID</title>
</head>
<body>

<h2>My ID</h2>

<?php if ($incomplete): ?>
    <p style="color:red;">Please complete your profile first.</p>
<?php else: ?>

<!-- ===== DIGITAL ID CARD ===== -->
<?php if (!empty($student['digital_id_front']) && !empty($student['digital_id_back'])): ?>
    <h3>Digital ID</h3>
    <p>Front:</p>
    <img src="../uploads/digital_id/<?= htmlspecialchars($student['digital_id_front']) ?>" style="max-width:400px;"><br>
    <p>Back:</p>
    <img src="../uploads/digital_id/<?= htmlspecialchars($student['digital_id_back']) ?>" style="max-width:400px;"><br><br>
<?php else: ?>
    <p>Digital ID not yet generated.</p>
<?php endif; ?>

<!-- ===== REQUEST FORM ===== -->
<h3>Request Student ID</h3>
<?php if ($msg) echo "<p>$msg</p>"; ?>

<form method="post">
    Full Name:<br>
    <input type="text" value="<?= htmlspecialchars($student['first_name'].' '.$student['last_name']) ?>" readonly><br><br>

    Student ID:<br>
    <input type="text" value="<?= htmlspecialchars($student['student_id']) ?>" readonly><br><br>

    Email:<br>
    <input type="text" value="<?= htmlspecialchars($student['email']) ?>" readonly><br><br>

    Request Type:<br>
    <select name="request_type" required onchange="this.form.reason.style.display=(this.value==='replacement'||this.value==='update_information'?'block':'none')">
        <option value="">-- select --</option>
        <option value="new">New</option>
        <option value="replacement">Replacement</option>
        <option value="update_information">Update Information</option>
    </select><br><br>

    <textarea name="reason" placeholder="Reason (required for replacement/update)" style="display:none;" rows="4" cols="50"></textarea><br>

    Profile photo that will be used:<br>
    <img src="../uploads/student_photos/<?= htmlspecialchars($student['photo']) ?>" width="150"><br><br>

    <button type="submit">Submit Request</button>
</form>

<?php endif; ?>
</body>
</html>