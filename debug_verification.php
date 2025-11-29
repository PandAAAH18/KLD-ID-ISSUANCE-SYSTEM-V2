<?php
require_once 'includes/config.php';
require_once 'includes/db.php';
require_once 'admin/classes/EmailVerification.php';

echo "<h2>Email Verification Debug</h2>";

// Test with a specific token or get the latest one
$database = new Database();
$db = $database->getConnection();

// Get the latest verification token from database
$stmt = $db->query("SELECT * FROM email_verification ORDER BY created_at DESC LIMIT 1");
$latestToken = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$latestToken) {
    echo "No verification tokens found in database.<br>";
    exit;
}

echo "<h3>Latest Token from Database:</h3>";
echo "<pre>";
print_r($latestToken);
echo "</pre>";

$token = $latestToken['token'];
$email = $latestToken['email'];
$userId = $latestToken['user_id'];

echo "<h3>Testing Token: $token</h3>";

// Test 1: Check if user exists and their verification status
echo "<h4>1. User Status:</h4>";
$userStmt = $db->prepare("SELECT user_id, email, is_verified, verified, verified_at FROM users WHERE user_id = ?");
$userStmt->execute([$userId]);
$user = $userStmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "User found:<br>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
} else {
    echo "❌ User not found with ID: $userId<br>";
}

// Test 2: Check if token is valid using EmailVerification class
echo "<h4>2. Token Validation:</h4>";
$emailVerifier = new EmailVerification();

try {
    $isValid = $emailVerifier->isTokenValid($token);
    echo "isTokenValid('$token'): " . ($isValid ? 'TRUE' : 'FALSE') . "<br>";
    
    if (!$isValid) {
        // Check why it's invalid
        $checkStmt = $db->prepare("SELECT token, expires_at, is_verified FROM email_verification WHERE token = ?");
        $checkStmt->execute([$token]);
        $tokenInfo = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        echo "Token details:<br>";
        echo "<pre>";
        print_r($tokenInfo);
        echo "</pre>";
        
        if ($tokenInfo) {
            $now = date('Y-m-d H:i:s');
            $expired = $tokenInfo['expires_at'] < $now;
            echo "Current time: $now<br>";
            echo "Token expires: {$tokenInfo['expires_at']}<br>";
            echo "Is expired: " . ($expired ? 'YES' : 'NO') . "<br>";
            echo "Is verified: " . ($tokenInfo['is_verified'] ? 'YES' : 'NO') . "<br>";
        }
    }
} catch (Exception $e) {
    echo "❌ Error checking token validity: " . $e->getMessage() . "<br>";
}

// Test 3: Try to verify the token
echo "<h4>3. Token Verification:</h4>";
try {
    $result = $emailVerifier->verifyToken($token);
    if ($result) {
        echo "✅ verifyToken() returned: SUCCESS<br>";
        echo "<pre>";
        print_r($result);
        echo "</pre>";
    } else {
        echo "❌ verifyToken() returned: NULL (failed)<br>";
    }
} catch (Exception $e) {
    echo "❌ Error verifying token: " . $e->getMessage() . "<br>";
}

// Test 4: Check user status after verification attempt
echo "<h4>4. User Status After Verification Attempt:</h4>";
$userStmt->execute([$userId]);
$userAfter = $userStmt->fetch(PDO::FETCH_ASSOC);

if ($userAfter) {
    echo "User after verification attempt:<br>";
    echo "<pre>";
    print_r($userAfter);
    echo "</pre>";
    
    $wasVerified = $user['is_verified'] ?? $user['verified'] ?? 0;
    $isVerified = $userAfter['is_verified'] ?? $userAfter['verified'] ?? 0;
    
    echo "Was verified before: " . ($wasVerified ? 'YES' : 'NO') . "<br>";
    echo "Is verified after: " . ($isVerified ? 'YES' : 'NO') . "<br>";
}

// Test 5: Check database structure
echo "<h4>5. Database Structure Check:</h4>";
$tables = ['users', 'email_verification'];
foreach ($tables as $table) {
    echo "<h5>Table: $table</h5>";
    $stmt = $db->query("DESCRIBE $table");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "<pre>";
    print_r($columns);
    echo "</pre>";
}
?>