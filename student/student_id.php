<?php
require_once __DIR__.'/../includes/config.php';
require_once __DIR__.'/student_header.php';
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
$msgType = '';
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
            $msg='✓ Request submitted successfully! You will be notified once processed.';
            $msgType = 'success';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My ID</title>
    <link href="../assets/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f3f3;
            margin: 0;
            padding: 0;
        }

        /* ▬▬▬▬ HEADER ▬▬▬▬ */
        .page-header {
            width: 95%;
            margin: 10px auto 30px;
            background: #ffffff;
            border-left: 8px solid #1b5e20;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0px 3px 7px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
        }

        .page-header h2 {
            margin: 0;
            color: #1b5e20;
            font-size: 22px;
        }

        /* ▬▬▬▬ ALERT MESSAGE ▬▬▬▬ */
        .alert-message {
            width: 95%;
            margin: 20px auto;
            padding: 15px 20px;
            border-radius: 5px;
            display: none;
        }

        .alert-message.show {
            display: block;
        }

        .alert-success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .alert-error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .alert-warning {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
        }

        /* ▬▬▬▬ CONTAINER ▬▬▬▬ */
        .card-container {
            width: 95%;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 10px;
            box-shadow: 0px 3px 7px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background: #1b5e20;
            color: white;
            padding: 20px;
            font-size: 18px;
            font-weight: bold;
        }

        .card-body {
            padding: 30px;
        }

        /* ▬▬▬▬ DIGITAL ID SECTION ▬▬▬▬ */
        .digital-id-section {
            margin-bottom: 30px;
        }

        .id-image-container {
            text-align: center;
            margin: 20px 0;
        }

        .id-image-label {
            font-weight: bold;
            color: #1b5e20;
            margin-bottom: 10px;
            font-size: 14px;
        }

        .id-image {
            max-width: 100%;
            height: auto;
            max-height: 400px;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
            margin: 10px 0;
        }

        /* ▬▬▬▬ FORM STYLES ▬▬▬▬ */
        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            font-weight: bold;
            color: #1b5e20;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
            font-family: Arial, sans-serif;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #1b5e20;
            box-shadow: 0 0 5px rgba(27, 94, 32, 0.3);
        }

        .form-group input[readonly] {
            background: #f8f9fa;
            color: #666;
        }

        /* ▬▬▬▬ PHOTO PREVIEW ▬▬▬▬ */
        .photo-preview-section {
            text-align: center;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 5px;
            margin-top: 20px;
        }

        .photo-preview-label {
            font-weight: bold;
            color: #1b5e20;
            display: block;
            margin-bottom: 15px;
        }

        .photo-preview {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            border: 3px solid #1b5e20;
            object-fit: cover;
            display: block;
            margin: 0 auto;
        }

        /* ▬▬▬▬ BUTTONS ▬▬▬▬ */
        .form-actions {
            margin-top: 30px;
            display: flex;
            gap: 10px;
            justify-content: center;
        }

        .btn-primary,
        .btn-secondary {
            padding: 12px 30px;
            font-size: 16px;
            font-weight: bold;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.3s;
        }

        .btn-primary {
            background: #1b5e20;
            color: white;
        }

        .btn-primary:hover {
            background: #0d3817;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #5a6268;
        }

        /* ▬▬▬▬ INCOMPLETE PROFILE ▬▬▬▬ */
        .incomplete-message {
            background: #fff3cd;
            border: 1px solid #ffeeba;
            color: #856404;
            padding: 20px;
            border-radius: 5px;
            margin: 20px auto;
            width: 95%;
            text-align: center;
            font-weight: bold;
        }

        .incomplete-message a {
            color: #856404;
            font-weight: bold;
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .card-container,
            .page-header,
            .alert-message,
            .incomplete-message {
                width: 100%;
            }

            .form-actions {
                flex-direction: column;
            }

            .btn-primary,
            .btn-secondary {
                width: 100%;
            }

            .id-image {
                max-height: 300px;
            }
        }
    </style>
</head>
<body>

    <!-- HEADER -->
    <div class="page-header">
        <h2>My ID</h2>
    </div>

    <!-- INCOMPLETE PROFILE WARNING -->
    <?php if ($incomplete): ?>
        <div class="incomplete-message">
            ⚠️ Please complete your profile first before requesting an ID.<br>
            <a href="edit_profile.php">← Complete Profile</a>
        </div>
    <?php else: ?>

        <!-- SUCCESS/ERROR MESSAGE -->
        <?php if ($msg): ?>
            <div class="alert-message show alert-<?php echo $msgType ?: 'error'; ?>">
                <?php echo htmlspecialchars($msg); ?>
            </div>
        <?php endif; ?>

        <!-- DIGITAL ID SECTION -->
        <div class="card-container">
            <div class="card-header">
                📋 Your Digital ID
            </div>
            <div class="card-body">
                <?php if (!empty($student['digital_id_front']) && !empty($student['digital_id_back'])): ?>
                    <div class="digital-id-section">
                        <div class="id-image-container">
                            <div class="id-image-label">Front Side</div>
                            <img src="../uploads/digital_id/<?= htmlspecialchars($student['digital_id_front']) ?>" alt="Digital ID Front" class="id-image">
                        </div>
                        <div class="id-image-container">
                            <div class="id-image-label">Back Side</div>
                            <img src="../uploads/digital_id/<?= htmlspecialchars($student['digital_id_back']) ?>" alt="Digital ID Back" class="id-image">
                        </div>
                    </div>
                <?php else: ?>
                    <p style="text-align: center; color: #666; font-style: italic;">Digital ID has not been generated yet. Submit a request below to get started.</p>
                <?php endif; ?>
            </div>
        </div>

        <!-- REQUEST FORM SECTION -->
        <div class="card-container">
            <div class="card-header">
                📝 Request Student ID
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" value="<?= htmlspecialchars($student['first_name'].' '.$student['last_name']) ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Student ID</label>
                        <input type="text" value="<?= htmlspecialchars($student['student_id']) ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Email</label>
                        <input type="text" value="<?= htmlspecialchars($student['email']) ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label>Request Type *</label>
                        <select name="request_type" required onchange="toggleReasonField()">
                            <option value="">-- Select Request Type --</option>
                            <option value="new">New ID</option>
                            <option value="replacement">Replacement</option>
                            <option value="update_information">Update Information</option>
                        </select>
                    </div>

                    <div class="form-group" id="reasonGroup" style="display: none;">
                        <label>Reason *</label>
                        <textarea name="reason" placeholder="Please provide a reason for your request..." rows="4"></textarea>
                    </div>

                    <!-- PHOTO PREVIEW -->
                    <div class="photo-preview-section">
                        <span class="photo-preview-label">Profile Photo (will be used on ID)</span>
                        <img src="../uploads/student_photos/<?= htmlspecialchars($student['photo']) ?>" alt="Profile Photo" class="photo-preview">
                    </div>

                    <!-- ACTION BUTTONS -->
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">📤 Submit Request</button>
                        <a href="student_home.php" class="btn-secondary">← Back to Home</a>
                    </div>
                </form>
            </div>
        </div>

    <?php endif; ?>

    <script>
        function toggleReasonField() {
            const select = document.querySelector('select[name="request_type"]');
            const reasonGroup = document.getElementById('reasonGroup');
            const reasonInput = document.querySelector('textarea[name="reason"]');
            
            if (select.value === 'replacement' || select.value === 'update_information') {
                reasonGroup.style.display = 'block';
                reasonInput.required = true;
            } else {
                reasonGroup.style.display = 'none';
                reasonInput.required = false;
            }
        }
    </script>

</body>
</html>