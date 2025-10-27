<?php
require 'auth_config.php';

$error = '';
$mode = $_GET['mode'] ?? 'login'; // 'login' or 'register'

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $name = trim($_POST['name'] ?? '');
        
        if (!$email || !$password || !$name) {
            $error = 'All fields are required';
            $mode = 'register';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters';
            $mode = 'register';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Invalid email format';
            $mode = 'register';
        } else {
            // First, fetch all users to check if email exists
            $url = FIREBASE_DB_URL . '/users.json' . (FIREBASE_DB_AUTH ? '?auth='.FIREBASE_DB_AUTH : '');
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            
            $existing_users = json_decode($response, true);
            $email_exists = false;
            
            // Check if email already registered
            if ($existing_users && is_array($existing_users)) {
                foreach ($existing_users as $user) {
                    if (isset($user['email']) && $user['email'] === $email) {
                        $email_exists = true;
                        break;
                    }
                }
            }
            
            if ($email_exists) {
                $error = 'Email already registered';
                $mode = 'register';
            } else {
                // Create new user
                $userData = [
                    'name' => $name,
                    'email' => $email,
                    'password' => password_hash($password, PASSWORD_BCRYPT),
                    'created_at' => date('Y-m-d H:i:s')
                ];
                
                $url = FIREBASE_DB_URL . '/users.json' . (FIREBASE_DB_AUTH ? '?auth='.FIREBASE_DB_AUTH : '');
                
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($userData));
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
                $response = curl_exec($ch);
                curl_close($ch);
                
                $result = json_decode($response, true);
                
                if (isset($result['name'])) {
                    $error = 'Account created successfully! Please login.';
                    $mode = 'login';
                } else {
                    $error = 'Failed to create account. Try again.';
                    $mode = 'register';
                }
            }
        }
    } elseif ($action === 'login') {
        if (!$email || !$password) {
            $error = 'Email and password are required';
            $mode = 'login';
        } else {
            // Fetch all users and search for email
            $url = FIREBASE_DB_URL . '/users.json' . (FIREBASE_DB_AUTH ? '?auth='.FIREBASE_DB_AUTH : '');
            
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            $response = curl_exec($ch);
            curl_close($ch);
            
            $users = json_decode($response, true);
            $user_found = null;
            $user_id = null;
            
            if ($users && is_array($users)) {
                foreach ($users as $id => $user) {
                    if (is_array($user) && isset($user['email']) && $user['email'] === $email) {
                        // Check password
                        if (isset($user['password']) && password_verify($password, $user['password'])) {
                            $user_found = $user;
                            $user_id = $id;
                            break;
                        } else {
                            // Email found but password wrong
                            $error = 'Invalid email or password';
                            $mode = 'login';
                            break;
                        }
                    }
                }
                
                if ($user_found) {
                    $_SESSION['user_id'] = $user_id;
                    $_SESSION['user_data'] = [
                        'name' => $user_found['name'],
                        'email' => $user_found['email']
                    ];
                    header('Location: list.php');
                    exit;
                } elseif (!$error) {
                    $error = 'Invalid email or password';
                    $mode = 'login';
                }
            } else {
                $error = 'Invalid email or password';
                $mode = 'login';
            }
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <title><?= $mode === 'login' ? 'Login' : 'Register' ?> - Attendance System</title>
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: "Poppins", sans-serif;
      background: linear-gradient(135deg, #5a9bd4, #7ac8e3);
      min-height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .auth-container {
      background: white;
      border-radius: 16px;
      box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
      width: 100%;
      max-width: 400px;
      padding: 40px;
      animation: slideIn 0.6s ease-out;
    }

    @keyframes slideIn {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .auth-header {
      text-align: center;
      margin-bottom: 30px;
    }

    .auth-header h1 {
      color: #333;
      font-size: 28px;
      margin-bottom: 5px;
    }

    .auth-header p {
      color: #999;
      font-size: 14px;
    }

    .form-group {
      margin-bottom: 18px;
    }

    label {
      display: block;
      color: #333;
      font-weight: 500;
      margin-bottom: 8px;
      font-size: 14px;
    }

    input {
      width: 100%;
      padding: 12px;
      border: 1px solid #ddd;
      border-radius: 8px;
      font-size: 14px;
      transition: all 0.3s ease;
      font-family: "Poppins", sans-serif;
    }

    input:focus {
      outline: none;
      border-color: #5a9bd4;
      box-shadow: 0 0 8px rgba(90, 155, 212, 0.3);
    }

    .error-message {
      background: #ffebee;
      color: #c62828;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 14px;
      border-left: 4px solid #c62828;
    }

    .success-message {
      background: #e8f5e9;
      color: #2e7d32;
      padding: 12px;
      border-radius: 8px;
      margin-bottom: 20px;
      font-size: 14px;
      border-left: 4px solid #2e7d32;
    }

    button {
      width: 100%;
      padding: 12px;
      background: #5a9bd4;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      font-size: 16px;
      cursor: pointer;
      transition: background 0.3s ease;
      font-family: "Poppins", sans-serif;
    }

    button:hover {
      background: #468cc1;
    }

    .toggle-mode {
      text-align: center;
      margin-top: 20px;
      color: #666;
      font-size: 14px;
    }

    .toggle-mode a {
      color: #5a9bd4;
      text-decoration: none;
      font-weight: 600;
      cursor: pointer;
    }

    .toggle-mode a:hover {
      text-decoration: underline;
    }

    .hidden {
      display: none;
    }

    .name-field {
      margin-bottom: 18px;
    }
  </style>
</head>
<body>
  <div class="auth-container">
    <!-- LOGIN FORM -->
    <div id="loginForm" class="<?= $mode !== 'login' ? 'hidden' : '' ?>">
      <div class="auth-header">
        <h1>🔐 Login</h1>
        <p>Access your attendance system</p>
      </div>

      <?php if ($error && $mode === 'login'): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="action" value="login">
        
        <div class="form-group">
          <label for="login-email">Email Address</label>
          <input type="email" id="login-email" name="email" required>
        </div>

        <div class="form-group">
          <label for="login-password">Password</label>
          <input type="password" id="login-password" name="password" required>
        </div>

        <button type="submit">Sign In</button>
      </form>

      <div class="toggle-mode">
        Don't have an account? <a onclick="toggleMode()">Register here</a>
      </div>
    </div>

    <!-- REGISTER FORM -->
    <div id="registerForm" class="<?= $mode !== 'register' ? 'hidden' : '' ?>">
      <div class="auth-header">
        <h1>📝 Create Account</h1>
        <p>Join the attendance system</p>
      </div>

      <?php if ($error && $mode === 'register'): ?>
        <div class="error-message"><?= htmlspecialchars($error) ?></div>
      <?php elseif ($error && $mode === 'login'): ?>
        <div class="success-message"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>

      <form method="POST">
        <input type="hidden" name="action" value="register">
        
        <div class="form-group">
          <label for="register-name">Full Name</label>
          <input type="text" id="register-name" name="name" required>
        </div>

        <div class="form-group">
          <label for="register-email">Email Address</label>
          <input type="email" id="register-email" name="email" required>
        </div>

        <div class="form-group">
          <label for="register-password">Password</label>
          <input type="password" id="register-password" name="password" placeholder="At least 6 characters" required>
        </div>

        <button type="submit">Create Account</button>
      </form>

      <div class="toggle-mode">
        Already have an account? <a onclick="toggleMode()">Login here</a>
      </div>
    </div>
  </div>

  <script>
    function toggleMode() {
      const loginForm = document.getElementById('loginForm');
      const registerForm = document.getElementById('registerForm');
      
      loginForm.classList.toggle('hidden');
      registerForm.classList.toggle('hidden');
      
      // Update URL without reload
      const newMode = loginForm.classList.contains('hidden') ? 'register' : 'login';
      window.history.replaceState({}, '', `index.php?mode=${newMode}`);
    }
  </script>
</body>
</html>