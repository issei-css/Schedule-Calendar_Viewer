<?php
// debug_login.php - Use this to see what's stored in Firebase
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'config.php';

$debug_users = null;
$debug_error = '';

// Fetch all users from Firebase
$url = FIREBASE_DB_URL . '/users.json' . (FIREBASE_DB_AUTH ? '?auth='.FIREBASE_DB_AUTH : '');

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
$curl_error = curl_error($ch);
curl_close($ch);

if ($curl_error) {
    $debug_error = "cURL Error: " . $curl_error;
} else {
    $debug_users = json_decode($response, true);
    if (!$debug_users) {
        $debug_error = "No users in database or invalid JSON response";
    }
}
?>
<!doctype html>
<html>
<head>
    <title>Debug - Users Database</title>
    <style>
        body {
            font-family: monospace;
            background: #f5f5f5;
            padding: 20px;
        }
        pre {
            background: white;
            padding: 20px;
            border-radius: 8px;
            overflow-x: auto;
            border: 1px solid #ddd;
        }
        h2 {
            color: #333;
        }
        .error {
            color: red;
            font-weight: bold;
        }
        .success {
            color: green;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <h1>🔍 Firebase Users Debug Info</h1>
    
    <h2>Firebase URL:</h2>
    <pre><?= htmlspecialchars(FIREBASE_DB_URL) ?></pre>
    
    <h2>Raw Response:</h2>
    <pre><?= htmlspecialchars($response) ?></pre>
    
    <h2>Decoded as Array:</h2>
    <pre><?php var_dump($debug_users); ?></pre>
    
    <?php if ($debug_error): ?>
        <h2 class="error">❌ Error:</h2>
        <pre><?= htmlspecialchars($debug_error) ?></pre>
    <?php else: ?>
        <h2 class="success">✅ Users Found: <?= count($debug_users ?? []) ?></h2>
    <?php endif; ?>
    
    <hr>
    <h2>Test Password Verification:</h2>
    <form method="POST">
        <input type="email" name="test_email" placeholder="Enter email to test">
        <input type="password" name="test_password" placeholder="Enter password to test">
        <button type="submit">Test Login</button>
    </form>
    
    <?php
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
        $test_email = $_POST['test_email'];
        $test_password = $_POST['test_password'];
        
        echo "<h3>Testing: $test_email</h3>";
        
        if ($debug_users && is_array($debug_users)) {
            $found = false;
            foreach ($debug_users as $id => $user) {
                if (isset($user['email']) && $user['email'] === $test_email) {
                    $found = true;
                    echo "<p><strong>Found user:</strong> ID = $id</p>";
                    echo "<pre>" . print_r($user, true) . "</pre>";
                    
                    if (isset($user['password'])) {
                        $is_valid = password_verify($test_password, $user['password']);
                        echo "<p><strong>Password verification:</strong> " . ($is_valid ? '✅ VALID' : '❌ INVALID') . "</p>";
                    } else {
                        echo "<p><strong>❌ No password field found!</strong></p>";
                    }
                    break;
                }
            }
            if (!$found) {
                echo "<p><strong>❌ Email not found in database</strong></p>";
            }
        }
    }
    ?>
    
    <hr>
    <p><a href="index.php">Back to Login</a></p>
</body>
</html>