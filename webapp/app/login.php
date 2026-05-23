<?php
require_once 'config.php';

session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $user_agent = $_SERVER['HTTP_USER_AGENT'];

    $conn = get_db_connection();

    // INTENTIONALLY VULNERABLE - No prepared statements!
    $sql = "SELECT * FROM users WHERE username='$username' AND password='$password'";

    $log_message = sprintf(
        "LOGIN_ATTEMPT | IP=%s | User=%s | Query=%s | UserAgent=%s",
        $ip, $username, $sql, substr($user_agent, 0, 100)
    );
    log_to_syslog($log_message, LOG_INFO);

    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        log_to_syslog("LOGIN_SUCCESS | IP=$ip | User=" . $user['username'], LOG_INFO);

        $stmt = $conn->prepare("INSERT INTO login_attempts (username, ip_address, user_agent, success) VALUES (?, ?, ?, 1)");
        $stmt->bind_param("sss", $username, $ip, $user_agent);
        $stmt->execute();

        header("Location: dashboard.php");
        exit();
    } else {
        log_to_syslog("LOGIN_FAILED | IP=$ip | User=$username", LOG_WARNING);

        $stmt = $conn->prepare("INSERT INTO login_attempts (username, ip_address, user_agent, success) VALUES (?, ?, ?, 0)");
        $stmt->bind_param("sss", $username, $ip, $user_agent);
        $stmt->execute();

        $error = "Invalid username or password";
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login - Security Lab</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .login-container {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            width: 350px;
        }
        h2 { margin-top: 0; color: #333; text-align: center; }
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 5px; color: #555; font-weight: bold; }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }
        input[type="submit"] {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
        }
        input[type="submit"]:hover { background: #5568d3; }
        .error {
            background: #f8d7da;
            color: #721c24;
            padding: 10px;
            border-radius: 4px;
            margin-bottom: 20px;
            border: 1px solid #f5c6cb;
        }
        .hint {
            margin-top: 20px;
            padding: 10px;
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            border-radius: 4px;
            font-size: 12px;
        }
        a { color: #667eea; text-decoration: none; }
    </style>
</head>
<body>
    <div class="login-container">
        <h2>Login</h2>
        <?php if (isset($error)): ?>
            <div class="error"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>
            </div>
            <input type="submit" value="Login">
        </form>

        <div class="hint">
            <strong>Hint for testing:</strong><br>
            Try: <code>admin' OR '1'='1'--</code> in username
        </div>

        <p style="text-align: center; margin-top: 20px;">
            <a href="index.php">Back to Home</a>
        </p>
    </div>
</body>
</html>
