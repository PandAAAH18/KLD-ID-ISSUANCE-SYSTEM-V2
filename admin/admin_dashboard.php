<?php
require_once '../includes/config.php';
require_once 'admin.php';
require_once 'admin_header.php'; // Include the header

$admin = new Admin();

// quick counts
$stats = $admin->getStats();

// pending requests table
$pending = $admin->getPendingRequests();

// student list (page 1)
$students = $admin->listStudents(1, 15, $_GET['search'] ?? '');
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>
<body>
    
</body>
</html>