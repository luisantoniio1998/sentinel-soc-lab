<!DOCTYPE html>
<html>
<head>
    <title>Security Lab - Vulnerable Web App</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 800px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 { color: #333; }
        .warning {
            background: #fff3cd;
            border: 1px solid #ffc107;
            padding: 15px;
            border-radius: 4px;
            margin: 20px 0;
        }
        .links { margin-top: 30px; }
        .links a {
            display: inline-block;
            margin: 10px 10px 10px 0;
            padding: 10px 20px;
            background: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 4px;
        }
        .links a:hover { background: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Security Lab - Vulnerable Web Application</h1>

        <div class="warning">
            <strong>Warning:</strong> This application is intentionally vulnerable for educational purposes only.
            Do NOT deploy in production or expose to the internet!
        </div>

        <h2>Available Pages:</h2>
        <div class="links">
            <a href="login.php">Login Page (SQLi Vulnerable)</a>
            <a href="search.php">Search Products (SQLi Vulnerable)</a>
            <a href="upload.php">File Upload (RCE Vulnerable)</a>
            <a href="info.php">Server Info</a>
        </div>

        <h2>Test Credentials:</h2>
        <ul>
            <li><strong>Admin:</strong> admin / admin123</li>
            <li><strong>User:</strong> john / john123</li>
        </ul>

        <h2>Common Attack Vectors:</h2>
        <ul>
            <li>SQL Injection: <code>admin' OR '1'='1</code></li>
            <li>Union-based SQLi: <code>admin' UNION SELECT null,username,password FROM users--</code></li>
            <li>Boolean-based SQLi: <code>admin' AND '1'='1</code></li>
        </ul>
    </div>
</body>
</html>
