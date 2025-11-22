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

// Fetch ID request status
$idRequest = (new Student())->getLatestIdRequest((int)$_SESSION['student_id']);
$idStatus = $idRequest ? $idRequest['status'] : 'Not Applied';
$idSubmitDate = $idRequest ? date('M d, Y', strtotime($idRequest['created_at'])) : 'N/A';
$idUpdateDate = $idRequest ? date('M d, Y h:i A', strtotime($idRequest['updated_at'] ?? $idRequest['created_at'])) : 'N/A';

// Fetch ID request history
$idHistory = (new Student())->getIdRequestHistory((int)$_SESSION['student_id']);

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
            background: linear-gradient(135deg, #ffffff 0%, #fafafa 100%);
            padding: 45px 45px;
            border-radius: 18px;
            box-shadow: 0px 4px 16px rgba(0, 0, 0, 0.08), 0px 0px 32px rgba(27, 94, 32, 0.04);
            border-left: 8px solid #1b5e20;
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            overflow: hidden;
        }

        .func-table-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(ellipse 600px 400px at 100% 0%, rgba(27, 94, 32, 0.03) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }

        .func-table-container > * {
            position: relative;
            z-index: 1;
        }

        .func-table-container:hover {
            box-shadow: 0px 12px 40px rgba(0, 0, 0, 0.12), 0px 0px 48px rgba(27, 94, 32, 0.08);
            transform: translateY(-6px);
        }

        .func-table-container h3 {
            margin: 0 0 40px 0;
            color: #1b5e20;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 12px;
            line-height: 1.3;
        }

        .func-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 0;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0px 2px 8px rgba(0, 0, 0, 0.06);
        }

        .func-table th,
        .func-table td {
            padding: 20px 24px;
            border-bottom: 1px solid #f0f0f0;
            font-size: 15px;
        }

        .func-table th {
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: #fff;
            text-align: left;
            font-weight: 800;
            letter-spacing: 0.4px;
            text-transform: uppercase;
            font-size: 12px;
            position: relative;
        }

        .func-table th::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, rgba(255, 255, 255, 0) 0%, rgba(255, 255, 255, 0.2) 50%, rgba(255, 255, 255, 0) 100%);
        }

        .func-table tbody tr {
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            cursor: pointer;
            position: relative;
        }

        .func-table tbody tr::before {
            content: '';
            position: absolute;
            left: 0;
            top: 0;
            bottom: 0;
            width: 4px;
            background: linear-gradient(180deg, #1b5e20 0%, #0d3817 100%);
            transform: scaleY(0);
            transform-origin: center;
            transition: transform 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .func-table tbody tr:hover {
            background: linear-gradient(90deg, rgba(27, 94, 32, 0.06) 0%, rgba(27, 94, 32, 0.02) 100%);
            box-shadow: 0px 4px 12px rgba(27, 94, 32, 0.1);
            transform: translateX(4px);
        }

        .func-table tbody tr:hover::before {
            transform: scaleY(1);
        }

        .func-table td {
            color: #555;
            font-weight: 500;
            position: relative;
        }

        .func-table td:first-child {
            font-weight: 700;
            color: #1b5e20;
            font-size: 16px;
            letter-spacing: 0.3px;
        }

        .func-table tbody tr:last-child td {
            border-bottom: none;
        }

        @media (max-width: 768px) {
            .func-table-container {
                padding: 30px 30px;
                margin: 30px auto;
                border-radius: 16px;
            }

            .func-table-container h3 {
                font-size: 24px;
                margin-bottom: 30px;
            }

            .func-table th,
            .func-table td {
                padding: 16px 16px;
                font-size: 14px;
            }

            .func-table td:first-child {
                font-size: 15px;
            }
        }

        @media (max-width: 480px) {
            .func-table-container {
                padding: 22px 18px;
                margin: 20px auto;
                border-radius: 14px;
            }

            .func-table-container h3 {
                font-size: 20px;
                margin-bottom: 22px;
            }

            .func-table th,
            .func-table td {
                padding: 14px 12px;
                font-size: 13px;
            }

            .func-table td:first-child {
                font-size: 14px;
            }
        }

        /* ID Status Tracker Styles */
        .id-status-tracker {
            background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
            border-left: 8px solid #1b5e20;
            border-radius: 16px;
            padding: 40px 40px;
            margin-bottom: 40px;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .id-status-tracker:hover {
            box-shadow: 0px 12px 40px rgba(0, 0, 0, 0.12);
            transform: translateY(-4px);
        }

        .id-status-tracker h3 {
            margin: 0 0 35px 0;
            color: #1b5e20;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 20px;
        }

        .status-item {
            background: white;
            padding: 24px 26px;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            border: 2px solid #e8e8e8;
            border-left: 4px solid #1b5e20;
            position: relative;
            overflow: hidden;
        }

        .status-item::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: linear-gradient(90deg, #1b5e20 0%, #0d3817 100%);
            transform: scaleX(0);
            transform-origin: left;
            transition: transform 0.3s ease;
        }

        .status-item:hover {
            box-shadow: 0 6px 20px rgba(27, 94, 32, 0.15);
            transform: translateY(-4px);
            border-color: #1b5e20;
        }

        .status-item:hover::before {
            transform: scaleX(1);
        }

        .status-label {
            font-size: 12px;
            font-weight: 700;
            color: #0d3817;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .status-label::before {
            content: '';
            width: 6px;
            height: 6px;
            background: #1b5e20;
            border-radius: 50%;
            display: inline-block;
        }

        .status-value {
            font-size: 16px;
            font-weight: 700;
            color: #1b5e20;
            word-break: break-word;
            line-height: 1.5;
        }

        .status-badge {
            display: inline-block;
            padding: 10px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
            text-align: center;
            min-width: 140px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .status-badge:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .status-badge.status-not-applied {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            color: #1565c0;
            border: 1px solid #90caf9;
        }

        .status-badge.status-pending {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            color: #e65100;
            border: 1px solid #ffb74d;
        }

        .status-badge.status-pending-approval {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            color: #e65100;
            border: 1px solid #ffb74d;
        }

        .status-badge.status-for-printing {
            background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
            color: #6a1b9a;
            border: 1px solid #ce93d8;
        }

        .status-badge.status-ready-for-pickup {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            color: #1b5e20;
            border: 1px solid #81c784;
        }

        .status-badge.status-completed {
            background: linear-gradient(135deg, #e8f5e9 0%, #a5d6a7 100%);
            color: #0d3817;
            border: 1px solid #66bb6a;
            font-weight: 800;
        }

        /* ID History Section */
        .id-history-section {
            margin-top: 40px;
            padding-top: 0;
            border-top: none;
            background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
            border-left: 8px solid #1b5e20;
            border-radius: 16px;
            padding: 0;
            box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .id-history-section:hover {
            box-shadow: 0px 12px 40px rgba(0, 0, 0, 0.12);
            transform: translateY(-4px);
        }

        .id-history-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 40px 40px;
            cursor: pointer;
            background: linear-gradient(135deg, #ffffff 0%, #f9f9f9 100%);
            transition: all 0.3s ease;
        }

        .id-history-header:hover {
            background: linear-gradient(135deg, #f5faf5 0%, #f0f5f0 100%);
        }

        .id-history-header-left {
            display: flex;
            align-items: center;
            gap: 12px;
            flex: 1;
            cursor: pointer;
        }

        .id-history-header-right {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .clean-history-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 8px 14px;
            background: linear-gradient(135deg, #ff6b6b 0%, #ee5a52 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(255, 107, 107, 0.25);
            letter-spacing: 0.2px;
            text-transform: uppercase;
        }

        .clean-history-btn:hover {
            background: linear-gradient(135deg, #ee5a52 0%, #d63031 100%);
            box-shadow: 0 4px 12px rgba(255, 107, 107, 0.35);
            transform: translateY(-2px);
        }

        .clean-history-btn:active {
            transform: translateY(0);
        }

        .clean-history-btn::before {
            content: '🗑️';
            font-size: 14px;
        }

        .history-empty-state {
            display: none;
            background: #f0f5f0;
            padding: 40px;
            border-radius: 12px;
            text-align: center;
            border: 2px dashed #81c784;
        }

        .history-empty-state.active {
            display: block;
        }

        .history-empty-state-content {
            color: #1b5e20;
            margin-bottom: 20px;
        }

        .history-empty-state-icon {
            font-size: 48px;
            margin-bottom: 15px;
            display: block;
        }

        .history-empty-state h4 {
            margin: 0 0 8px 0;
            font-size: 18px;
            font-weight: 700;
            color: #1b5e20;
        }

        .history-empty-state p {
            margin: 0;
            font-size: 14px;
            color: #666;
            font-weight: 500;
        }

        .restore-history-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            padding: 10px 18px;
            background: linear-gradient(135deg, #4caf50 0%, #388e3c 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 2px 8px rgba(76, 175, 80, 0.25);
            letter-spacing: 0.2px;
            text-transform: uppercase;
            margin-top: 15px;
        }

        .restore-history-btn:hover {
            background: linear-gradient(135deg, #388e3c 0%, #2e7d32 100%);
            box-shadow: 0 4px 12px rgba(76, 175, 80, 0.35);
            transform: translateY(-2px);
        }

        .restore-history-btn:active {
            transform: translateY(0);
        }

        .restore-history-btn::before {
            content: '↶';
            font-size: 16px;
        }

        .id-history-section h3 {
            margin: 0;
            color: #1b5e20;
            font-size: 28px;
            font-weight: 700;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .history-toggle {
            font-size: 20px;
            color: #1b5e20;
            font-weight: 800;
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            flex-shrink: 0;
        }

        .id-history-section.collapsed .history-toggle {
            transform: rotate(-90deg);
        }

        .history-content {
            max-height: 2000px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            padding: 0 40px 40px 40px;
            animation: expandHistory 0.4s ease-out;
        }

        .id-history-section.collapsed .history-content {
            max-height: 0;
            padding: 0 40px;
            animation: none;
        }

        @keyframes expandHistory {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        .history-table-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            box-shadow: 0 3px 12px rgba(0, 0, 0, 0.08);
        }

        .history-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        .history-table thead {
            background: linear-gradient(135deg, #1b5e20 0%, #0d3817 100%);
            color: white;
        }

        .history-table th {
            padding: 18px 20px;
            font-weight: 700;
            text-align: left;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 2px solid rgba(27, 94, 32, 0.2);
        }

        .history-table td {
            padding: 16px 20px;
            border-bottom: 1px solid #e8e8e8;
            font-size: 14px;
            color: #555;
            font-weight: 500;
        }

        .history-table tbody tr {
            transition: all 0.3s ease;
        }

        .history-table tbody tr:hover {
            background: linear-gradient(90deg, rgba(27, 94, 32, 0.08) 0%, rgba(27, 94, 32, 0.03) 100%);
            border-left: 4px solid #1b5e20;
            padding-left: 16px;
            box-shadow: inset 0px 0px 10px rgba(27, 94, 32, 0.05);
        }

        .history-table tbody tr:last-child td {
            border-bottom: none;
        }

        .type-badge {
            display: inline-block;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: capitalize;
        }

        .type-badge.type-new {
            background: #e3f2fd;
            color: #1565c0;
            border: 1px solid #90caf9;
        }

        .type-badge.type-replacement {
            background: #fff3e0;
            color: #e65100;
            border: 1px solid #ffb74d;
        }

        .type-badge.type-update-information {
            background: #f3e5f5;
            color: #6a1b9a;
            border: 1px solid #ce93d8;
        }

        .status-badge-small {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 700;
            text-transform: capitalize;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.08);
            transition: all 0.3s ease;
        }

        .status-badge-small:hover {
            transform: scale(1.05);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.12);
        }

        .status-badge-small.status-not-applied {
            background: linear-gradient(135deg, #e3f2fd 0%, #bbdefb 100%);
            color: #1565c0;
            border: 1px solid #90caf9;
        }

        .status-badge-small.status-pending {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            color: #e65100;
            border: 1px solid #ffb74d;
        }

        .status-badge-small.status-pending-approval {
            background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%);
            color: #e65100;
            border: 1px solid #ffb74d;
        }

        .status-badge-small.status-for-printing {
            background: linear-gradient(135deg, #f3e5f5 0%, #e1bee7 100%);
            color: #6a1b9a;
            border: 1px solid #ce93d8;
        }

        .status-badge-small.status-ready-for-pickup {
            background: linear-gradient(135deg, #e8f5e9 0%, #c8e6c9 100%);
            color: #1b5e20;
            border: 1px solid #81c784;
        }

        .status-badge-small.status-completed {
            background: linear-gradient(135deg, #e8f5e9 0%, #a5d6a7 100%);
            color: #0d3817;
            border: 1px solid #66bb6a;
            font-weight: 800;
        }

        .empty-history {
            background: #f8f9fa;
            padding: 50px 40px;
            border-radius: 12px;
            text-align: center;
            color: #999;
            font-weight: 500;
            border: 2px dashed #ddd;
        }

        .empty-history p {
            margin: 0;
            font-size: 15px;
        }

        @media (max-width: 768px) {
            .id-history-section {
                margin-top: 30px;
                padding: 0;
            }

            .id-history-header {
                padding: 28px 25px;
            }

            .id-history-section h3 {
                font-size: 24px;
                margin-bottom: 0;
            }

            .history-content {
                padding: 0 25px 28px 25px;
            }

            .id-history-section.collapsed .history-content {
                padding: 0 25px;
            }

            .history-table th,
            .history-table td {
                padding: 14px 12px;
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .id-history-section {
                margin-top: 20px;
                padding: 0;
                border-left-width: 6px;
            }

            .id-history-header {
                padding: 20px 16px;
            }

            .id-history-section h3 {
                font-size: 20px;
                margin-bottom: 0;
            }

            .history-toggle {
                font-size: 18px;
            }

            .history-content {
                padding: 0 16px 20px 16px;
            }

            .id-history-section.collapsed .history-content {
                padding: 0 16px;
            }

            .history-table th,
            .history-table td {
                padding: 12px 10px;
                font-size: 12px;
            }

            .type-badge {
                padding: 6px 10px;
                font-size: 11px;
            }

            .status-badge-small {
                padding: 5px 10px;
                font-size: 11px;
            }

            .empty-history {
                padding: 35px 20px;
            }

            .empty-history p {
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
        <!-- ID Status Tracker -->
        <div class="id-status-tracker">
            <h3>📋 ID Application Status Tracker</h3>
            <div class="status-grid">
                <div class="status-item">
                    <div class="status-label">Application Status</div>
                    <div class="status-value status-badge status-<?php echo strtolower(str_replace(' ', '-', $idStatus)); ?>">
                        <?php echo htmlspecialchars($idStatus); ?>
                    </div>
                </div>
                <div class="status-item">
                    <div class="status-label">Date Submitted</div>
                    <div class="status-value"><?php echo $idSubmitDate; ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">Latest Update</div>
                    <div class="status-value"><?php echo $idUpdateDate; ?></div>
                </div>
                <div class="status-item">
                    <div class="status-label">Request Type</div>
                    <div class="status-value"><?php echo $idRequest ? htmlspecialchars($idRequest['request_type']) : 'N/A'; ?></div>
                </div>
            </div>
        </div>

        <!-- Past ID History -->
        <div class="id-history-section">
            <div class="id-history-header">
                <div class="id-history-header-left" onclick="toggleHistorySection(this)">
                    <h3>📜 Past ID History</h3>
                    <span class="history-toggle">▼</span>
                </div>
                <div class="id-history-header-right">
                    <button class="clean-history-btn" onclick="cleanHistoryDisplay(event)" title="Hide history records temporarily">
                        Hide
                    </button>
                </div>
            </div>
            <div class="history-content">
                <div class="history-table-content">
                    <?php if (count($idHistory) > 0): ?>
                        <div class="history-table-wrapper">
                            <table class="history-table">
                                <thead>
                                    <tr>
                                        <th>Request Date</th>
                                        <th>Request Type</th>
                                        <th>Reason</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($idHistory as $record): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y h:i A', strtotime($record['created_at'])); ?></td>
                                            <td><span class="type-badge type-<?php echo strtolower(str_replace(' ', '-', $record['request_type'])); ?>"><?php echo htmlspecialchars($record['request_type']); ?></span></td>
                                            <td><?php echo htmlspecialchars($record['reason']); ?></td>
                                            <td><span class="status-badge-small status-<?php echo strtolower(str_replace(' ', '-', $record['status'])); ?>"><?php echo htmlspecialchars($record['status']); ?></span></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="empty-history">
                            <p>No past ID requests found.</p>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="history-empty-state">
                    <div class="history-empty-state-content">
                        <span class="history-empty-state-icon">👋</span>
                        <h4>History Hidden</h4>
                        <p>Your history records are hidden but still saved in the database.</p>
                    </div>
                    <button class="restore-history-btn" onclick="restoreHistoryDisplay(event)">
                        Restore
                    </button>
                </div>
            </div>
        </div>
    </div>

</body>

</html>

<script>
    // ▬▬▬▬ ID HISTORY TOGGLE FUNCTIONALITY ▬▬▬▬
    function toggleHistorySection(headerElement) {
        const section = headerElement.closest('.id-history-header') ? headerElement.closest('.id-history-header').closest('.id-history-section') : headerElement.closest('.id-history-section');
        section.classList.toggle('collapsed');
    }

    // ▬▬▬▬ CLEAN HISTORY DISPLAY ▬▬▬▬
    function cleanHistoryDisplay(event) {
        event.stopPropagation();
        const historySection = event.target.closest('.id-history-section');
        const tableContent = historySection.querySelector('.history-table-content');
        const emptyState = historySection.querySelector('.history-empty-state');

        // Fade out table
        tableContent.style.animation = 'fadeOut 0.3s ease-out forwards';

        setTimeout(() => {
            tableContent.style.display = 'none';
            emptyState.classList.add('active');
            emptyState.style.animation = 'fadeIn 0.3s ease-out';
        }, 300);
    }

    // ▬▬▬▬ RESTORE HISTORY DISPLAY ▬▬▬▬
    function restoreHistoryDisplay(event) {
        event.stopPropagation();
        const historySection = event.target.closest('.id-history-section');
        const tableContent = historySection.querySelector('.history-table-content');
        const emptyState = historySection.querySelector('.history-empty-state');

        // Fade out empty state
        emptyState.style.animation = 'fadeOut 0.3s ease-out forwards';

        setTimeout(() => {
            emptyState.classList.remove('active');
            tableContent.style.display = 'block';
            tableContent.style.animation = 'fadeIn 0.3s ease-out';
        }, 300);
    }

    // Add fade animations
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }

        @keyframes fadeOut {
            from {
                opacity: 1;
            }
            to {
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

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