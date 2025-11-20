<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/student.php';

if (!isset($_SESSION['user_id'], $_SESSION['user_type'], $_SESSION['student_id']) ||
    $_SESSION['user_type'] !== 'student') {
    header('Location: ../index.php'); exit();
}

$stu = (new Student())->findById((int)$_SESSION['student_id']);
if (!$stu) { header('Location: ../index.php'); exit(); }

/* ---- 1. Display block ---- */
$avatarPath = '../uploads/student_photos/'.htmlspecialchars($stu['photo']);
$fullName   = htmlspecialchars($stu['first_name'].' '.$stu['last_name']);
?>
<!doctype html>
<html>
<head><meta charset="utf-8"><title>My Profile</title></head>
<body>

<h2>Profile</h2>

<!-- avatar / header -->
<p><img src="<?=$avatarPath?>" width="150" alt="avatar"></p>

<p>
    <strong>Full Name:</strong> <?=$fullName?><br>
    <strong>Student ID:</strong> <?=htmlspecialchars($stu['student_id'])?><br>
    <strong>Email:</strong> <?=htmlspecialchars($stu['email'])?><br>
    <strong>Contact #:</strong> <?=htmlspecialchars($stu['contact_number'])?><br>
    <strong>Date of Birth:</strong> <?=htmlspecialchars($stu['dob'] ?? 'N/A')?><br>
    <strong>Gender:</strong> <?=htmlspecialchars($stu['gender'] ?? 'N/A')?><br>
    <strong>Course:</strong> <?=htmlspecialchars($stu['course'])?><br>
    <strong>Year:</strong> <?=htmlspecialchars($stu['year_level'])?><br>
    <strong>Blood Type:</strong> <?=htmlspecialchars($stu['blood_type'] ?? 'N/A')?><br>
    <strong>Emergency Contact:</strong> <?=htmlspecialchars($stu['emergency_contact'] ?? 'N/A')?><br>
</p>

<!-- 2. Edit trigger -->
<form action="edit_profile.php" method="get">
    <button type="submit">Edit / Upload Info</button>
</form>

</body>
</html>