<?php
require_once '../includes/config.php';
require_once 'student_header.php'; // Include the header
require_once 'student.php';

// Check if user is logged in as student
if (
    !isset($_SESSION['user_id'], $_SESSION['user_type'], $_SESSION['student_id']) ||
    $_SESSION['user_type'] !== 'student'
) {
    header('Location: ../login.php');
    exit();
}

// Fetch student data from database
$student = (new Student())->findById((int)$_SESSION['student_id']);
if (!$student) {
    header('Location: ../login.php');
    exit();
}

// Prepare data
$studentName = htmlspecialchars($student['first_name'] . ' ' . $student['last_name']);
$studentID = htmlspecialchars($student['student_id'] ?? 'N/A');
$course = htmlspecialchars($student['course']);
$yearSection = htmlspecialchars($student['year_level']);
$contact_number = htmlspecialchars($student['contact_number']);
$address = htmlspecialchars($student['address']);
$avatar = $student['photo'] ? '../uploads/student_photos/' . htmlspecialchars($student['photo']) : '../uploads/default_avatar.png';
$qrcode = "../uploads/sample_qr.png"; // You can update this path as needed
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Home</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f3f3f3;
            margin: 0;
            padding: 0;
            padding-top: 80px;
        }
        
        /* ▬▬▬▬ WELCOME BOX ▬▬▬▬ */
        .welcome-box {
            width: 95%;
            margin: 20px auto;
            background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
            border-left: 8px solid #1b5e20;
            padding: 25px 80px;
            border-radius: 12px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.12);
            display: flex;
            gap: 35px;
            align-items: center;
            transition: box-shadow 0.3s ease, transform 0.3s ease;
        }

        .welcome-box:hover {
            box-shadow: 0px 8px 28px rgba(0, 0, 0, 0.18);
            transform: translateY(-3px);
        }

        .welcome-box img {
            width: 120px;
            height: 126px;
            border-radius: 50%;
            border: 4px solid #1b5e20;
            object-fit: cover;
            flex-shrink: 0;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.18);
        }

        .welcome-content-wrapper {
            display: flex;
            gap: 50px;
            align-items: flex-start;
            flex: 1;
            justify-content: space-between;
        }

        .welcome-info {
            flex: 1;
            min-width: 300px;
        }

        .welcome-info h2 {
            margin: 0 0 12px 0;
            color: #1b5e20;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.5px;
            line-height: 1.3;
            display: flex;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 0.3px;
            text-transform: uppercase;
            box-shadow: 0px 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .status-badge.enrolled {
            background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
            color: white;
            border: 1px solid #2e7d32;
        }

        .status-badge.not-enrolled {
            background: linear-gradient(135deg, #ff9800 0%, #f57c00 100%);
            color: white;
            border: 1px solid #e65100;
        }

        .status-badge::before {
            content: '';
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: currentColor;
            opacity: 0.7;
        }

        .welcome-info p {
            margin: 8px 0;
            color: #666;
            font-size: 15px;
            line-height: 1.7;
            font-weight: 500;
        }

        .welcome-info p strong {
            color: #1b5e20;
            font-weight: 700;
        }

        .welcome-nav {
            display: flex;
            flex-direction: column;
            gap: 22px;
            min-width: 200px;
            justify-content: flex-start;
        }

        .welcome-nav a {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 14px 24px;
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: 0px 3px 8px rgba(27, 94, 32, 0.25);
            border: none;
            white-space: nowrap;
            letter-spacing: 0.3px;
        }

        .welcome-nav a:hover {
            background: linear-gradient(135deg, #0d3817 0%, #061f10 100%);
            box-shadow: 0px 6px 16px rgba(13, 56, 23, 0.35);
            transform: translateY(-3px);
        }

        .welcome-nav a:active {
            transform: translateY(-1px);
            box-shadow: 0px 3px 8px rgba(13, 56, 23, 0.25);
        }

        @media (max-width: 1024px) {
            .welcome-content-wrapper {
                gap: 35px;
            }

            .welcome-nav {
                min-width: 160px;
            }
        }

        @media (max-width: 768px) {
            .welcome-box {
                flex-direction: column;
                align-items: flex-start;
                gap: 25px;
                padding: 25px 20px;
            }

            .welcome-content-wrapper {
                width: 100%;
                flex-direction: column;
                gap: 20px;
            }

            .welcome-info {
                min-width: 100%;
            }

            .welcome-nav {
                width: 100%;
                flex-direction: column;
                flex-wrap: wrap;
                gap: 12px;
            }

            .welcome-nav a {
                width: 100%;
                padding: 12px 16px;
                font-size: 14px;
            }

            .welcome-box img {
                width: 100px;
                height: 100px;
            }

            .welcome-info h2 {
                font-size: 24px;
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .status-badge {
                padding: 7px 14px;
                font-size: 12px;
            }

            .welcome-info p {
                font-size: 14px;
            }
        }

        @media (max-width: 480px) {
            .welcome-box {
                padding: 20px 15px;
                gap: 15px;
            }

            .welcome-nav {
                gap: 8px;
                width: 100%;
            }

            .welcome-nav a {
                padding: 10px 12px;
                font-size: 13px;
                width: 100%;
            }

            .welcome-info h2 {
                font-size: 22px;
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }

            .status-badge {
                padding: 6px 12px;
                font-size: 11px;
            }

            .welcome-info p {
                margin: 5px 0;
                font-size: 13px;
            }
        }

        /* ▬▬▬▬ ID CARD FLIP ▬▬▬▬ */
        .id-container {
            width: 95%;
            margin: 40px auto;
            perspective: 1000px;
            padding: 35px;
            background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
            border-radius: 14px;
            border-left: 8px solid #1b5e20;
            box-shadow: 0px 4px 16px rgba(0, 0, 0, 0.12);
            cursor: pointer;
            position: relative;
            z-index: 1;

            transition: all 0.3s ease;
        }

        .id-container:hover {
            box-shadow: 0px 8px 30px rgba(0, 0, 0, 0.18);
            transform: translateY(-3px);
        }


        .id-container h3 {
            margin: 0 0 28px 0;
            color: #1b5e20;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.5px;
            text-align: center;
            line-height: 1.3;
        }

        .id-card {
            width: 380px;
            height: 240px;
            position: relative;
            margin: auto;
            transition: transform 0.6s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            transform-style: preserve-3d;
            cursor: pointer;
            box-shadow: 0px 8px 24px rgba(0, 0, 0, 0.15);
            border-radius: 14px;
        }

        .id-card.flipped {
            transform: rotateY(180deg);
        }

        .id-side {
            width: 380px;
            height: 240px;
            border-radius: 14px;
            position: absolute;
            backface-visibility: hidden;
            overflow: hidden;
            box-shadow: 0px 8px 24px rgba(0, 0, 0, 0.15);
        }

        .id-front {
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: #fff;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            height: 100%;
            border: 2px solid #1b5e20;
            position: relative;
        }

        .id-front::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.05) 50%, transparent 70%);
            pointer-events: none;
        }

        .id-front h3 {
            margin: 0 0 14px 0;
            font-size: 16px;
            font-weight: 800;
            letter-spacing: 0.5px;
            line-height: 1.4;
            text-transform: uppercase;
            position: relative;
            z-index: 1;
        }

        .id-front .student-details {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            gap: 15px;
            position: relative;
            z-index: 1;
        }

        .id-front .student-details p {
            margin: 5px 0;
            font-size: 12px;
            font-weight: 700;
            line-height: 1.4;
            letter-spacing: 0.2px;
        }

        .id-front .student-details p strong {
            display: block;
            font-size: 13px;
            margin-bottom: 2px;
            text-transform: uppercase;
            opacity: 0.95;
        }

        .id-front img {
            width: 90px;
            height: 90px;
            border-radius: 10px;
            border: 3px solid white;
            object-fit: cover;
            background: white;
            flex-shrink: 0;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.25);
            position: relative;
            z-index: 2;
        }

        .id-back {
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            align-items: center;
            transform: rotateY(180deg);
            border: 2px solid #1b5e20;
            padding: 20px 15px;
            gap: 12px;
            text-align: center;
            height: 100%;
            position: relative;
        }

        .id-back::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, transparent 30%, rgba(255, 255, 255, 0.05) 50%, transparent 70%);
            pointer-events: none;
        }

        .id-back-label {
            position: relative;
            z-index: 1;
            color: #fff;
            font-size: 12px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .id-back p {
            margin: 2px 0;
            font-size: 11px;
            color: #fff;
            font-weight: 700;
            line-height: 1.4;
            letter-spacing: 0.2px;
            position: relative;
            z-index: 1;
        }

        .id-back p:first-child {
            font-size: 13px;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 4px;
            letter-spacing: 0.5px;
        }

        .qr-wrapper {
            position: relative;
            z-index: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 6px;
        }

        .qr-label {
            font-size: 10px;
            color: rgba(255, 255, 255, 0.85);
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.2px;
        }

        .id-back img {
            width: 140px;
            height: 140px;
            background: white;
            padding: 8px;
            border-radius: 10px;
            border: 3px solid white;
            object-fit: cover;
            box-shadow: 0px 6px 16px rgba(0, 0, 0, 0.3);
            position: relative;
            z-index: 1;
        }

        @media (max-width: 768px) {
            .id-back {
                padding: 16px 12px;
                gap: 10px;
            }

            .id-back p {
                font-size: 10px;
            }

            .id-back p:first-child {
                font-size: 12px;
                margin-bottom: 3px;
            }

            .qr-label {
                font-size: 9px;
            }

            .id-back img {
                width: 115px;
                height: 115px;
                border-width: 2px;
                padding: 6px;
            }
        }

        @media (max-width: 480px) {
            .id-back {
                padding: 12px 10px;
                gap: 8px;
            }

            .id-back-label {
                font-size: 11px;
                margin-bottom: 4px;
            }

            .id-back p {
                font-size: 9px;
            }

            .id-back p:first-child {
                font-size: 10px;
                margin-bottom: 2px;
            }

            .qr-label {
                font-size: 8px;
            }

            .id-back img {
                width: 95px;
                height: 95px;
                border-width: 2px;
                padding: 5px;
            }
        }

        @media (max-width: 768px) {
            .id-container {
                margin: 30px auto;
                padding: 25px;
            }

            .id-container h3 {
                font-size: 24px;
                margin-bottom: 22px;
            }

            .id-card {
                width: 320px;
                height: 200px;
            }

            .id-side {
                width: 320px;
                height: 200px;
            }

            .id-front {
                padding: 16px;
            }

            .id-front h3 {
                font-size: 14px;
                margin-bottom: 12px;
            }

            .id-front .student-details p {
                font-size: 11px;
            }

            .id-front img {
                width: 75px;
                height: 75px;
            }

            .id-back img {
                width: 115px;
                height: 115px;
            }
        }

        @media (max-width: 480px) {
            .id-container {
                margin: 25px auto;
                padding: 18px;
            }

            .id-container h3 {
                font-size: 20px;
                margin-bottom: 16px;
            }

            .id-card {
                width: 270px;
                height: 170px;
            }

            .id-side {
                width: 270px;
                height: 170px;
            }

            .id-front {
                padding: 12px;
            }

            .id-front h3 {
                font-size: 12px;
                margin-bottom: 8px;
            }

            .id-front .student-details {
                gap: 10px;
            }

            .id-front .student-details p {
                font-size: 10px;
            }

            .id-front img {
                width: 65px;
                height: 65px;
                border-width: 2px;
            }

            .id-back {
                padding: 12px;
                gap: 8px;
            }

            .id-back p {
                font-size: 10px;
            }

            .id-back p:first-child {
                font-size: 11px;
            }

            .id-back img {
                width: 95px;
                height: 95px;
            }
        }

        /* ▬▬▬▬ STUDENT FUNCTIONS TABLE ▬▬▬▬ */
        .func-table-container {
            width: 95%;
            margin: 40px auto;
            background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
            padding: 30px 35px;
            border-radius: 12px;
            box-shadow: 0px 4px 15px rgba(0, 0, 0, 0.12);
            border-left: 8px solid #1b5e20;
            transition: box-shadow 0.3s ease, transform 0.3s ease;
        }

        .func-table-container:hover {
            box-shadow: 0px 8px 28px rgba(0, 0, 0, 0.18);
            transform: translateY(-3px);
        }

        .func-table-container h3 {
            margin: 0 0 20px 0;
            color: #1b5e20;
            font-size: 26px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .func-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        .func-table th,
        .func-table td {
            padding: 16px 18px;
            border-bottom: 1px solid #e0e0e0;
            font-size: 15px;
        }

        .func-table th {
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: #fff;
            text-align: left;
            font-weight: 700;
            letter-spacing: 0.3px;
        }

        .func-table tbody tr {
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .func-table tbody tr:hover {
            background: linear-gradient(90deg, rgba(27, 94, 32, 0.08) 0%, rgba(27, 94, 32, 0.03) 100%);
            border-left: 4px solid #1b5e20;
            padding-left: 14px;
            box-shadow: inset 0px 0px 10px rgba(27, 94, 32, 0.08);
        }

        .func-table td {
            color: #444;
            font-weight: 500;
        }

        .func-table td:first-child {
            font-weight: 700;
            color: #1b5e20;
            font-size: 16px;
        }

        .func-table tbody tr:last-child td {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .func-table-container {
                padding: 20px 20px;
                margin: 30px auto;
            }

            .func-table-container h3 {
                font-size: 22px;
                margin-bottom: 15px;
            }

            .func-table th,
            .func-table td {
                padding: 12px 14px;
                font-size: 14px;
            }

            .func-table td:first-child {
                font-size: 15px;
            }
        }

        @media (max-width: 480px) {
            .func-table-container {
                padding: 15px 15px;
                margin: 20px auto;
            }

            .func-table-container h3 {
                font-size: 20px;
                margin-bottom: 12px;
            }

            .func-table th,
            .func-table td {
                padding: 10px 12px;
                font-size: 13px;
            }

            .func-table td:first-child {
                font-size: 14px;
            }
        }
    </style>
</head>

<body class="admin-body">
    <!-- BACK TO TOP BUTTON -->
    <button id="backToTopBtn" class="back-to-top" onclick="scrollToTop()">↑</button>

    <!-- ▬▬▬▬ DIGITAL ID (PORTRAIT VERSION) ▬▬▬▬ -->
    <style>
        .portrait-id-container {
            width: 95%;
            margin: 95px auto;
            perspective: 1000px;
        }

        .portrait-id-card {
            width: 420px;
            height: 675px;
            position: relative;
            margin: auto;
            transition: transform 0.6s;
            transform-style: preserve-3d;
            cursor: pointer;
        }

        .portrait-id-card.flipped {
            transform: rotateY(180deg);
        }

        .portrait-side {
            width: 450px;
            height: 600px;
            border-radius: 12px;
            position: absolute;
            backface-visibility: hidden;
            overflow: hidden;
            box-shadow: 0px 3px 8px rgba(0, 0, 0, 0.3);
            background: #fff;
        }

        /* ---------------- FRONT ---------------- */
        .portrait-front {
            background: #fff;
            border: 2px solid #1b5e20;
        }

        .pf-header {
            background: #1b5e20;
            padding: 20px;
            text-align: center;
            color: white;
            font-weight: bold;
            font-size: 20px;
        }

        .pf-photo {
            width: 180px;
            height: 225px;
            border: 2px solid #1b5e20;
            margin: 15px auto;
            display: block;
            object-fit: cover;
            background: white;
        }

        .pf-details {
            text-align: center;
            padding: 5px 15px;
        }

        .pf-details p {
            margin: 4px 0;
            font-size: 18px;
            font-weight: bold;
        }

        .pf-idnumber {
            margin-top: 10px;
            font-size: 24px;
            font-weight: bold;
            color: #000;
        }

        .pf-signature {
            width: 100%;
            text-align: center;
            margin-top: 25px;
        }

        .pf-signature-line {
            border-top: 1px solid #333;
            width: 75%;
            margin: auto;
            margin-top: 5px;
            font-size: 12px;
        }

        /* ---------------- BACK ---------------- */
        .portrait-back {
            transform: rotateY(180deg);
            background: white;
            border: 2px solid #1b5e20;
            padding: 20px;
            text-align: center;
        }

        .back-title {
            color: #1b5e20;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .qr-code {
            width: 200px;
            height: 200px;
            margin: 20px auto;
            display: block;
            background: white;
            padding: 8px;
            border-radius: 10px;
            border: 1px solid #ccc;
            object-fit: cover;
        }

        .reg-signature {
            margin-top: 30px;
            font-size: 12px;
            border-top: 1px solid #333;
            width: 60%;
            margin-left: auto;
            margin-right: auto;
            padding-top: 5px;
        }

        .id-actions {
            margin-top: 20px;
            display: flex;
            flex-direction: column;
            gap: 12px;
            width: 80%;
            margin-left: auto;
            margin-right: auto;
        }

        .id-btn {
            background: #1b5e20;
            color: white;
            border: none;
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 14px;
            font-weight: bold;
            cursor: pointer;
            letter-spacing: 0.5px;
            transition: 0.3s ease;
        }

        .id-btn:hover {
            background: #0d3817;
            transform: translateY(-2px);
        }

        .id-btn:active {
            transform: translateY(0);
        }

        /* ▬▬▬▬ BACK TO TOP BUTTON ▬▬▬▬ */
        .back-to-top {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: white;
            border: none;
            font-size: 24px;
            font-weight: 700;
            cursor: pointer;
            display: none;
            align-items: center;
            justify-content: center;
            box-shadow: 0 6px 20px rgba(27, 94, 32, 0.3);
            transition: all 0.3s ease;
            z-index: 999;
        }

        .back-to-top:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 28px rgba(27, 94, 32, 0.4);
        }

        .back-to-top:active {
            transform: translateY(-1px);
        }

        @media (max-width: 768px) {
            .back-to-top {
                bottom: 20px;
                right: 20px;
                width: 45px;
                height: 45px;
                font-size: 20px;
            }
        }
    </style>

    <div class="portrait-id-container">
        <div class="portrait-id-card">

            <!-- FRONT -->
            <div class="portrait-side portrait-front">
                <div class="pf-header">KOLEHIYO NG LUNGSOD NG DASMARIÑAS</div>

                <img src="<?php echo $avatar; ?>" class="pf-photo">

                <div class="pf-details">
                    <p><?php echo $studentName; ?></p>
                    <p><?php echo $course; ?></p>
                    <p class="pf-idnumber"><?php echo $studentID; ?></p>
                </div>

                <div class="pf-signature">
                    <img src="../uploads/signature.png" width="120">
                    <div class="pf-signature-line">SIGNATURE OF CARDHOLDER</div>
                </div>
            </div>

            <!-- BACK -->
            <div class="portrait-side portrait-back">
                <p class="back-title">In case of emergency, contact:</p>
                <p><strong>Marlyn Concepcion</strong></p>
                <p>09462274362</p>

                <img src="<?php echo $qrcode; ?>" class="qr-code">

                <div class="reg-signature">
                    Registrar Signature
                </div>

                <div class="id-actions">
                    <button onclick="downloadVisualID(event)" class="id-btn download-btn">
                        Download Visual ID
                    </button>

                    <button onclick="scanQRCode(event)" class="id-btn scan-btn">
                        Scan QR Code
                    </button>
                </div>
            </div>

        </div>
    </div>

    <!-- ▬▬▬▬ WELCOME BOX ▬▬▬▬ -->
    <div class="welcome-box">
        <img src="<?php echo $avatar; ?>" alt="Avatar">
        <div class="welcome-content-wrapper">
            <div class="welcome-info">
                <h2>
                    Welcome, <?php echo $studentName; ?> !
                    <span class="status-badge enrolled">Enrolled</span>
                </h2>
                <p><strong>ID:</strong> <?php echo $studentID; ?></p>
                <p><strong>Course:</strong> <?php echo $course; ?></p>
                <p><strong>Year & Section:</strong> <?php echo $yearSection; ?></p>
                <p><strong>Contact Number:</strong> <?php echo $contact_number; ?></p>
                <p><strong>Address:</strong> <?php echo $address; ?></p>
            </div>
            <div class="welcome-nav">
                <a href="student_profile.php">My Profile</a>
                <a href="student_id.php">My ID</a>
                <a href="edit_profile.php">Edit Profile</a>
            </div>
        </div>
    </div>

    <!-- ▬▬▬▬ STUDENT FUNCTIONS ▬▬▬▬ -->
    <div class="func-table-container">
        <h3>Quick Access</h3>
        <table class="func-table">
            <thead>
                <tr>
                    <th>Action</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
                <tr onclick="window.location.href='attendance_history.php'">
                    <td>View Attendance History</td>
                    <td>Check your log-in and log-out records.</td>
                </tr>
                <tr onclick="window.location.href='id_status.php'">
                    <td>Check ID Issuance Status</td>
                    <td>Monitor the approval and release of your school ID.</td>
                </tr>
                <tr onclick="window.location.href='notifications.php'">
                    <td>Receive Email Notifications</td>
                    <td>Get updates like account approval and ID ready status.</td>
                </tr>
            </tbody>
        </table>
    </div>

</body>

</html>

<script>
    // ▬▬▬▬ BACK TO TOP BUTTON FUNCTIONALITY ▬▬▬▬
    const backToTopBtn = document.getElementById('backToTopBtn');

    window.addEventListener('scroll', function() {
        if (window.scrollY > 300) {
            backToTopBtn.style.display = 'flex';
        } else {
            backToTopBtn.style.display = 'none';
        }
    });

    function scrollToTop() {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    }

    // ▬▬▬▬ PORTRAIT ID CARD FLIP ▬▬▬▬
    document.querySelector('.portrait-id-card').addEventListener('click', function() {
        this.classList.toggle('flipped');
    });

    function downloadVisualID(e) {
        e.stopPropagation();
        alert('Visual ID download feature coming soon!');
    }

    function scanQRCode(e) {
        e.stopPropagation();
        alert('QR Code scanner feature coming soon!');
    }
</script>